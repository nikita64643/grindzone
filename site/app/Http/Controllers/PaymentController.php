<?php

namespace App\Http\Controllers;

use App\Models\BalanceTopup;
use App\Models\BalanceTopupPackage;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\PrivilegePurchase;
use App\Services\LuckPermsSync;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Moneta\MonetaSdk;

class PaymentController extends Controller
{
    public function create(Request $request): RedirectResponse
    {
        $request->validate([
            'package_id' => 'required|integer|exists:balance_topup_packages,id',
            'coupon_code' => 'nullable|string|max:64',
        ]);

        $user = $request->user();
        if (! $user) {
            return redirect()->route('login')->with('error', 'Войдите в аккаунт для пополнения баланса.');
        }

        $package = BalanceTopupPackage::where('id', $request->package_id)->where('is_active', true)->first();
        if (! $package) {
            return back()->with('error', 'Пакет не найден или неактивен.')->with('open_balance_modal', true);
        }

        $amount = (float) $package->price;
        $coins = $package->total_coins;
        $couponId = null;

        $couponCode = trim((string) ($request->coupon_code ?? ''));
        if ($couponCode !== '') {
            $coupon = Coupon::where('code', $couponCode)->first();
            if ($coupon && $coupon->isValidFor($user->id, $amount, 'balance')) {
                $amount = $coupon->applyToPrice($amount);
                $coins = $coupon->applyToCoins($coins);
                $couponId = $coupon->id;
            }
        }

        try {
            $topup = BalanceTopup::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'coins' => $coins,
                'order_id' => 'bal_' . $user->id . '_' . Str::random(12),
                'status' => 'pending',
                'coupon_id' => $couponId,
            ]);

            $configPath = base_path('config/moneta');
            $mSdk = new MonetaSdk(
                $topup->order_id,
                $amount,
                'Пополнение баланса GrindZone — ' . $coins . ' монет',
                $configPath
            );

            $paymentLink = $mSdk->getAssistantPaymentLink();

            return redirect()->away($paymentLink);
        } catch (\Throwable $e) {
            Log::error('Moneta payment create error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Ошибка создания платежа: ' . $e->getMessage())->with('open_balance_modal', true);
        }
    }

    /**
     * Пополнение баланса через EasyDonate.
     */
    public function createBalanceEasyDonate(Request $request): RedirectResponse
    {
        if (! config('easydonate.enabled')) {
            return back()->with('error', 'Оплата через EasyDonate отключена.')->with('open_balance_modal', true);
        }

        $request->validate([
            'package_id' => 'required|integer|exists:balance_topup_packages,id',
            'coupon_code' => 'nullable|string|max:64',
        ]);

        $user = $request->user();
        if (! $user) {
            return redirect()->route('login')->with('error', 'Войдите в аккаунт.');
        }

        $package = BalanceTopupPackage::where('id', $request->package_id)->where('is_active', true)->first();
        if (! $package || $package->easydonate_product_id <= 0) {
            return back()->with('error', 'Пакет не настроен для EasyDonate.')->with('open_balance_modal', true);
        }

        $coins = $package->total_coins;
        $couponId = null;
        $couponCode = trim((string) ($request->coupon_code ?? ''));
        if ($couponCode !== '') {
            $coupon = Coupon::where('code', $couponCode)->first();
            if ($coupon && $coupon->isValidFor($user->id, (float) $package->price, 'balance')) {
                $coins = $coupon->applyToCoins($coins);
                $couponId = $coupon->id;
            }
        }

        $shopKey = config('easydonate.shop_key');
        if (empty($shopKey)) {
            return back()->with('error', 'EasyDonate не настроен.')->with('open_balance_modal', true);
        }

        $serverId = config('easydonate.balance_server_id', 0);
        if ($serverId <= 0) {
            $server = \App\Models\MinecraftServer::orderBy('id')->first();
            $serverId = $server ? (int) $server->easydonate_server_id : 0;
        }
        if ($serverId <= 0) {
            return back()->with('error', 'Сервер EasyDonate для пополнения не настроен.')->with('open_balance_modal', true);
        }

        $customer = trim($user->name ?? $user->email ?? '');
        if ($customer === '') {
            return back()->withErrors(['nickname' => 'Укажите имя в настройках профиля.']);
        }

        $params = [
            'customer' => $customer,
            'server_id' => $serverId,
            'products' => json_encode([(string) $package->easydonate_product_id => 1]),
            'email' => $user->email ?? 'noreply@example.com',
            'success_url' => $this->getEasyDonateSuccessUrl(),
        ];

        try {
            $response = Http::withHeaders(['Shop-Key' => $shopKey])
                ->get(config('easydonate.api_url'), $params);

            $data = $response->json();
            if (! ($data['success'] ?? false) || empty($data['response']['url'] ?? null)) {
                $msg = $data['response'] ?? $response->body() ?? 'Неизвестная ошибка';
                Log::warning('EasyDonate balance create failed', ['response' => $data]);
                return back()->with('error', 'Ошибка EasyDonate: ' . (is_string($msg) ? $msg : json_encode($msg)))->with('open_balance_modal', true);
            }

            $paymentId = $data['response']['payment']['id'] ?? null;
            $orderId = $paymentId ? 'ed_bal_' . $paymentId : 'ed_bal_' . Str::random(16);

            BalanceTopup::create([
                'user_id' => $user->id,
                'amount' => (float) $package->price,
                'coins' => $coins,
                'order_id' => $orderId,
                'status' => 'pending',
                'coupon_id' => $couponId,
            ]);

            return redirect()->away($data['response']['url']);
        } catch (\Throwable $e) {
            Log::error('EasyDonate balance create error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Ошибка: ' . $e->getMessage())->with('open_balance_modal', true);
        }
    }

    public function createPrivilege(Request $request): RedirectResponse
    {
        $request->validate([
            'server_slug' => 'required|string|max:255',
            'server_name' => 'required|string|max:255',
            'privilege_key' => 'required|string|max:64',
            'privilege_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:1|max:500000',
            'coupon_code' => 'nullable|string|max:64',
        ]);

        $user = $request->user();
        if (! $user) {
            return redirect()->route('login')->with('error', 'Войдите в аккаунт.');
        }

        $privilegeData = $this->getPrivilegeByKey($request->privilege_key);
        if (! $privilegeData) {
            return back()->withErrors(['privilege_key' => 'Выберите привилегию.']);
        }
        if (! $this->privilegeAvailableForServer($request->privilege_key, $request->server_slug)) {
            return back()->withErrors(['privilege_key' => 'Эта привилегия недоступна для выбранного сервера.']);
        }

        $amount = (float) $request->amount;
        $couponId = null;
        $couponCode = trim((string) ($request->coupon_code ?? ''));
        if ($couponCode !== '') {
            $coupon = Coupon::where('code', $couponCode)->first();
            if ($coupon && $coupon->isValidFor($user->id, $amount, 'privilege')) {
                $amount = $coupon->applyToPrice($amount);
                $couponId = $coupon->id;
            }
        }

        if (str_contains($request->server_slug, '1-21')) {
            $nick = trim($user->name ?? '');
            if ($nick === '') {
                return back()->withErrors([
                    'nickname' => 'Укажите ник в Minecraft в настройках профиля.',
                ]);
            }
        }

        try {
            $purchase = PrivilegePurchase::create([
                'user_id' => $user->id,
                'server_slug' => $request->server_slug,
                'server_name' => $request->server_name,
                'privilege_key' => $request->privilege_key,
                'privilege_name' => $request->privilege_name,
                'amount' => $amount,
                'order_id' => 'priv_' . $user->id . '_' . Str::random(12),
                'status' => 'pending',
                'coupon_id' => $couponId,
            ]);

            $configPath = base_path('config/moneta');
            $mSdk = new MonetaSdk(
                $purchase->order_id,
                $amount,
                'Привилегия ' . $request->privilege_name . ' — ' . $request->server_name,
                $configPath
            );

            return redirect()->away($mSdk->getAssistantPaymentLink());
        } catch (\Throwable $e) {
            Log::error('Moneta privilege payment create error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Ошибка создания платежа: ' . $e->getMessage());
        }
    }

    /**
     * Создание платежа через EasyDonate. Редирект на страницу оплаты EasyDonate.
     */
    public function createPrivilegeEasyDonate(Request $request): RedirectResponse
    {
        if (! config('easydonate.enabled')) {
            return back()->with('error', 'Оплата через EasyDonate отключена.');
        }

        $request->validate([
            'server_slug' => 'required|string|max:255',
            'server_name' => 'required|string|max:255',
            'privilege_key' => 'required|string|max:64',
            'privilege_name' => 'required|string|max:255',
        ]);

        $user = $request->user();
        if (! $user) {
            return redirect()->route('login')->with('error', 'Войдите в аккаунт.');
        }

        $privilegeData = $this->getPrivilegeByKey($request->privilege_key);
        if (! $privilegeData) {
            return back()->withErrors(['privilege_key' => 'Выберите привилегию.']);
        }
        if (! $this->privilegeAvailableForServer($request->privilege_key, $request->server_slug)) {
            return back()->withErrors(['privilege_key' => 'Эта привилегия недоступна для выбранного сервера.']);
        }

        $serverId = $this->getEasyDonateServerId($request->server_slug);
        $productId = $this->getEasyDonateProductId($request->privilege_key);

        if ($serverId <= 0 || $productId <= 0) {
            return back()->with('error', 'Сервер или привилегия не настроены для EasyDonate. Обратитесь к администратору.');
        }

        $nick = trim($user->name ?? '');
        if ($nick === '') {
            return back()->withErrors([
                'nickname' => 'Укажите ник в Minecraft в настройках профиля.',
            ]);
        }

        $shopKey = config('easydonate.shop_key');
        if (empty($shopKey)) {
            return back()->with('error', 'EasyDonate не настроен.');
        }

        $productsJson = [(string) $productId => 1];
        $params = [
            'customer' => $nick,
            'server_id' => $serverId,
            'products' => json_encode($productsJson),
            'email' => $user->email ?? 'noreply@example.com',
            'success_url' => $this->getEasyDonateSuccessUrl(),
        ];

        try {
            $response = Http::withHeaders([
                'Shop-Key' => $shopKey,
            ])->get(config('easydonate.api_url'), $params);

            $data = $response->json();
            if (! ($data['success'] ?? false) || empty($data['response']['url'] ?? null)) {
                $msg = $data['response'] ?? $response->body() ?? 'Неизвестная ошибка';
                Log::warning('EasyDonate create payment failed', [
                    'response' => $data,
                    'status' => $response->status(),
                ]);
                return back()->with('error', 'Ошибка EasyDonate: ' . (is_string($msg) ? $msg : json_encode($msg)));
            }

            return redirect()->away($data['response']['url']);
        } catch (\Throwable $e) {
            Log::error('EasyDonate payment create error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Ошибка создания платежа EasyDonate: ' . $e->getMessage());
        }
    }

    /**
     * Callback от EasyDonate после успешной оплаты.
     * Для привилегий — EasyDonate сам выдаёт товары. Для баланса — зачисляем монеты.
     * CSRF отключён для этого маршрута.
     */
    public function webhookEasyDonate(Request $request)
    {
        $payload = $request->all();
        Log::info('EasyDonate callback received', ['payload' => $payload]);

        $paymentId = $payload['payment_id'] ?? $payload['id'] ?? $payload['payment']['id'] ?? null;
        if ($paymentId) {
            $orderId = 'ed_bal_' . $paymentId;
            $topup = BalanceTopup::where('order_id', $orderId)->first();
            if ($topup && $topup->status !== 'completed') {
                $credits = $topup->coins ?? $topup->amount;
                $topup->user->increment('balance', $credits);
                $topup->update(['status' => 'completed']);
                if ($topup->coupon_id) {
                    $topup->coupon->increment('used_count');
                    CouponUsage::create([
                        'coupon_id' => $topup->coupon_id,
                        'user_id' => $topup->user_id,
                        'context' => 'balance',
                        'amount' => $topup->amount,
                    ]);
                }
            }
        }

        return response('OK', 200);
    }

    /**
     * Webhook от Moneta (PayURL). CSRF отключён для этого маршрута.
     */
    public function webhook(Request $request)
    {
        $orderId = $request->post('MNT_TRANSACTION_ID');
        $amount = (float) $request->post('MNT_AMOUNT', 0);

        if (! $orderId || $amount <= 0) {
            return response('BAD REQUEST', 400);
        }

        $configPath = base_path('config/moneta');
        $mSdk = new MonetaSdk($orderId, null, null, $configPath);

        if (str_starts_with($orderId, 'priv_')) {
            $purchase = PrivilegePurchase::where('order_id', $orderId)->first();
            if (! $purchase) {
                Log::warning('Moneta webhook: unknown privilege order_id', ['order_id' => $orderId]);
                return response('ORDER NOT FOUND', 404);
            }
            if ($purchase->status === 'completed') {
                $mSdk->responseToPaymentNotification();
                return;
            }

            $serverPort = $this->getServerPortBySlug($purchase->server_slug);
            $nick = trim($purchase->user->name ?? '');
            if (str_contains($purchase->server_slug, '1-21') && $serverPort !== null && $nick !== '') {
                $syncOk = app(LuckPermsSync::class)->syncDonation(
                    $purchase->server_slug,
                    $serverPort,
                    $purchase->privilege_key,
                    $nick,
                    $purchase->privilege_name
                );
                if (! $syncOk) {
                    Log::warning('Moneta webhook: LuckPerms sync failed for privilege purchase', ['order_id' => $orderId]);
                }
            }

            DB::transaction(function () use ($purchase, $amount) {
                $purchase->user->donations()->create([
                    'server_slug' => $purchase->server_slug,
                    'server_name' => $purchase->server_name,
                    'privilege_key' => $purchase->privilege_key,
                    'privilege_name' => $purchase->privilege_name,
                    'amount' => $amount,
                ]);
                $purchase->update(['status' => 'completed']);
                if ($purchase->coupon_id) {
                    $purchase->coupon->increment('used_count');
                    CouponUsage::create([
                        'coupon_id' => $purchase->coupon_id,
                        'user_id' => $purchase->user_id,
                        'context' => 'privilege',
                        'amount' => $amount,
                    ]);
                }
            });

            $mSdk->responseToPaymentNotification();
            return;
        }

        $topup = BalanceTopup::where('order_id', $orderId)->first();
        if (! $topup) {
            Log::warning('Moneta webhook: unknown order_id', ['order_id' => $orderId]);
            return response('ORDER NOT FOUND', 404);
        }

        if ($topup->status === 'completed') {
            $mSdk->responseToPaymentNotification();
            return;
        }

$credits = $topup->coins ?? $amount;
        $topup->user->increment('balance', $credits);
        $topup->update(['status' => 'completed']);

            if ($topup->coupon_id) {
                $topup->coupon->increment('used_count');
                CouponUsage::create([
                    'coupon_id' => $topup->coupon_id,
                    'user_id' => $topup->user_id,
                    'context' => 'balance',
                    'amount' => $topup->amount,
                ]);
            }

            $mSdk->responseToPaymentNotification();
    }

    private function getEasyDonateSuccessUrl(): string
    {
        $base = config('easydonate.public_url') ?: config('app.url');
        $url = rtrim($base, '/') . '/payment/success';
        return preg_replace('#:0(?=/|$)#', '', $url);
    }

    private function getPrivilegeByKey(string $key): ?array
    {
        if (Schema::hasTable('privileges')) {
            $p = \App\Models\Privilege::where('key', $key)->first();
            if ($p) {
                return ['name' => $p->name, 'price' => (float) $p->price];
            }
        }
        $config = config('donate.privileges', []);
        $p = $config[$key] ?? null;
        if ($p) {
            return ['name' => $p['name'] ?? $key, 'price' => (float) ($p['price'] ?? 0)];
        }
        return null;
    }

    private function privilegeAvailableForServer(string $privilegeKey, string $serverSlug): bool
    {
        if (! Schema::hasTable('privileges')) {
            return true;
        }
        $p = \App\Models\Privilege::where('key', $privilegeKey)->with('privilegeServers')->first();
        if (! $p) {
            return false;
        }
        $slugs = $p->getServerSlugs();
        if (empty($slugs)) {
            return true;
        }
        return in_array($serverSlug, $slugs, true);
    }

    private function getEasyDonateServerId(string $serverSlug): int
    {
        $server = \App\Models\MinecraftServer::where('slug', $serverSlug)->first();
        if ($server && $server->easydonate_server_id > 0) {
            return (int) $server->easydonate_server_id;
        }
        return 0;
    }

    private function getEasyDonateProductId(string $privilegeKey): int
    {
        if (Schema::hasTable('privileges')) {
            $p = \App\Models\Privilege::where('key', $privilegeKey)->first();
            if ($p && $p->easydonate_product_id > 0) {
                return (int) $p->easydonate_product_id;
            }
        }
        return 0;
    }

    private function getServerPortBySlug(string $serverSlug): ?int
    {
        $servers = config('minecraft.servers', []);
        foreach ($servers as $s) {
            if (Str::slug($s['name'] . '-' . $s['version']) === $serverSlug) {
                return (int) $s['port'];
            }
        }
        $server = \App\Models\MinecraftServer::where('slug', $serverSlug)->first();
        if ($server) {
            return (int) $server->port;
        }
        return null;
    }

    public function success(): Response
    {
        return Inertia::render('payment/Success');
    }

    public function fail(): Response
    {
        return Inertia::render('payment/Fail');
    }

    public function inprogress(Request $request): Response|RedirectResponse
    {
        $orderId = $request->query('MNT_TRANSACTION_ID');
        if ($orderId) {
            $topup = BalanceTopup::where('order_id', $orderId)->first();
            if ($topup?->status === 'completed') {
                return redirect()->route('payment.success')->with('status', 'Счёт оплачен');
            }
            $purchase = PrivilegePurchase::where('order_id', $orderId)->first();
            if ($purchase?->status === 'completed') {
                return redirect()->route('payment.success')->with('status', 'Счёт оплачен');
            }
        }

        return Inertia::render('payment/Inprogress');
    }
}
