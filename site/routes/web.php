<?php

use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\PrivilegeController as AdminPrivilegeController;
use App\Http\Controllers\Admin\ServerController as AdminServerController;
use App\Http\Controllers\Admin\PackageController as AdminPackageController;
use App\Http\Controllers\Admin\ShopController as AdminShopController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\ServerStatusController;
use App\Http\Controllers\DonateController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TopsController;
use App\Http\Controllers\ServerController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/api/servers/status', [ServerStatusController::class, 'index'])->name('api.servers.status');
Route::post('/api/coupon/validate-balance', [\App\Http\Controllers\Api\CouponController::class, 'validateBalance'])->name('api.coupon.validate-balance')->middleware('auth');
Route::post('/api/coupon/validate-privilege', [\App\Http\Controllers\Api\CouponController::class, 'validatePrivilege'])->name('api.coupon.validate-privilege')->middleware('auth');

Route::get('/servers', [ServerController::class, 'index'])->name('servers.index');
Route::get('/servers/{server:slug}', [ServerController::class, 'show'])->name('servers.show');

Route::get('/news', [NewsController::class, 'index'])->name('news.index');
Route::get('/news/{news:slug}', [NewsController::class, 'show'])->name('news.show');

Route::get('/tops', [TopsController::class, 'index'])->name('tops.index');

Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index')->middleware('auth');

Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])->name('auth.social.redirect');
Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])->name('auth.social.callback');
Route::get('/auth/{provider}/link', [SocialAuthController::class, 'redirectLink'])->name('auth.social.link')->middleware('auth');
Route::delete('/auth/{provider}/link', [SocialAuthController::class, 'unlink'])->name('auth.social.unlink')->middleware('auth');

Route::get('/', function () {
    $servers = \App\Models\MinecraftServer::query()
        ->orderBy('version')
        ->orderBy('sort_order')
        ->orderBy('id')
        ->get();

    $serverList = $servers->isNotEmpty()
        ? $servers->map(fn($s) => ['name' => $s->name, 'version' => $s->version, 'port' => $s->port, 'slug' => $s->slug])->values()->all()
        : collect(config('minecraft.servers', []))->map(fn($s) => ['name' => $s['name'], 'version' => $s['version'], 'port' => $s['port'], 'slug' => null])->values()->all();

    $news = \Illuminate\Support\Facades\Schema::hasTable('news')
        ? \App\Models\News::published()
            ->orderByDesc('published_at')
            ->limit(4)
            ->get(['title', 'slug', 'image', 'published_at'])
            ->map(fn ($n) => [
                'title' => $n->title,
                'slug' => $n->slug,
                'date' => $n->published_at?->format('d.m.Y'),
                'image' => $n->image,
            ])
            ->all()
        : [
            ['title' => 'Запуск проекта GRINDZONE', 'slug' => 'zapusk-proekta', 'date' => now()->format('d.m.Y'), 'image' => 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=400'],
        ];
    $topPlaytime = \Illuminate\Support\Facades\Schema::hasTable('player_stats')
        ? \App\Models\PlayerStats::query()
            ->where('total_playtime_minutes', '>', 0)
            ->join('users', 'player_stats.user_id', '=', 'users.id')
            ->orderByDesc('player_stats.total_playtime_minutes')
            ->limit(10)
            ->get(['player_stats.total_playtime_minutes as minutes', 'users.name'])
            ->map(fn ($r, $i) => [
                'rank' => $i + 1,
                'name' => $r->name ?: 'Игрок',
                'minutes' => (int) $r->minutes,
            ])
            ->values()
            ->all()
        : [];

    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
        'servers' => $serverList,
        'initialServerStatus' => ServerStatusController::getCachedStatus(),
        'news' => $news,
        'topPlaytime' => $topPlaytime,
    ]);
})->name('home');

Route::get('shop', function () {
    return Inertia::render('Shop');
})->name('shop');

Route::get('forum', fn () => redirect('/'))->name('forum');
Route::get('about', fn () => redirect('/'))->name('about');

Route::get('howtoplay', function () {
    $servers = \App\Models\MinecraftServer::query()->orderBy('version')->get();
    $fromConfig = collect(config('minecraft.servers', []));
    $recommendedVersions = $servers->isNotEmpty()
        ? $servers->pluck('version')->map(fn ($v) => preg_replace('/\s+.*$/', '', $v))->unique()->values()->all()
        : $fromConfig->pluck('version')->map(fn ($v) => preg_replace('/\s+.*$/', '', $v))->unique()->values()->all();
    $serverAddress = config('minecraft.connect_address', config('minecraft.ping_host', 'localhost'));

    return Inertia::render('HowToPlay', [
        'serverAddress' => $serverAddress,
        'recommendedVersions' => ! empty($recommendedVersions) ? $recommendedVersions : ['1.16.5', '1.21.10'],
    ]);
})->name('howtoplay');

Route::get('help', function () {
    $help = config('help', []);
    $help['initialServerStatus'] = \App\Http\Controllers\Api\ServerStatusController::getCachedStatus();

    return Inertia::render('Help', $help);
})->name('help');

Route::get('rules', function () {
    $rules = config('rules', []);
    $rules['initialServerStatus'] = \App\Http\Controllers\Api\ServerStatusController::getCachedStatus();

    return Inertia::render('Rules', $rules);
})->name('rules');

Route::get('requisites', function () {
    return Inertia::render('legal/Requisites');
})->name('legal.requisites');

Route::get('offer', function () {
    return Inertia::render('legal/Offer');
})->name('legal.offer');

Route::get('privacy', function () {
    return Inertia::render('legal/Privacy');
})->name('legal.privacy');

Route::get('donate', [DonateController::class, 'index'])->name('donate.index');
Route::get('donate/{serverSlug}', [DonateController::class, 'show'])->name('donate.show');
Route::post('donate', [DonateController::class, 'store'])->name('donate.store')->middleware('auth');

Route::post('payment/create', [PaymentController::class, 'create'])->name('payment.create')->middleware('auth');
Route::post('payment/create-balance-easydonate', [PaymentController::class, 'createBalanceEasyDonate'])->name('payment.create-balance-easydonate')->middleware('auth');
Route::post('payment/create-privilege', [PaymentController::class, 'createPrivilege'])->name('payment.create-privilege')->middleware('auth');
Route::post('payment/create-privilege-easydonate', [PaymentController::class, 'createPrivilegeEasyDonate'])->name('payment.create-privilege-easydonate')->middleware('auth');
Route::post('payment/webhook', [PaymentController::class, 'webhook'])->name('payment.webhook')->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
Route::post('payment/webhook-easydonate', [PaymentController::class, 'webhookEasyDonate'])->name('payment.webhook-easydonate')->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
Route::get('payment/success', [PaymentController::class, 'success'])->name('payment.success');
Route::get('payment/fail', [PaymentController::class, 'fail'])->name('payment.fail');
Route::get('payment/inprogress', [PaymentController::class, 'inprogress'])->name('payment.inprogress');

Route::get('profile', function () {
    return Inertia::render('Profile');
})->middleware(['auth', 'verified'])->name('profile.index');

Route::get('profile/stats', \App\Http\Controllers\ProfileStatsController::class)
    ->middleware(['auth', 'verified'])
    ->name('profile.stats');

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn() => redirect()->route('admin.servers.index'))->name('index');
    Route::get('/servers', [AdminServerController::class, 'index'])->name('servers.index');
    Route::get('/servers/{server:slug}', [AdminServerController::class, 'show'])->name('servers.show');
    Route::put('/servers/{server:slug}', [AdminServerController::class, 'update'])->name('servers.update');
    Route::get('/servers/{server:slug}/log', [AdminServerController::class, 'log'])->name('servers.log');
    Route::post('/servers/{server:slug}/restart', [AdminServerController::class, 'restart'])->name('servers.restart');
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::post('/users/{user}/grant-privilege', [AdminUserController::class, 'grantPrivilege'])->name('users.grant-privilege');
    Route::get('/privileges', [AdminPrivilegeController::class, 'index'])->name('privileges.index');
    Route::post('/privileges/apply-tab-prefixes', [AdminPrivilegeController::class, 'applyTabPrefixes'])->name('privileges.apply-tab-prefixes');
    Route::get('/privileges/{privilege}/edit', [AdminPrivilegeController::class, 'edit'])->name('privileges.edit');
    Route::put('/privileges/{privilege}', [AdminPrivilegeController::class, 'update'])->name('privileges.update');

    Route::get('/payments', [AdminPaymentController::class, 'index'])->name('payments.index');
    Route::put('/payments/topups/{topup}/status', [AdminPaymentController::class, 'updateTopupStatus'])->name('payments.topups.status');
    Route::put('/payments/purchases/{purchase}/status', [AdminPaymentController::class, 'updatePurchaseStatus'])->name('payments.purchases.status');

    Route::get('/packages', [AdminPackageController::class, 'index'])->name('packages.index');
    Route::put('/packages', [AdminPackageController::class, 'update'])->name('packages.update');
    Route::post('/packages/sync-easydonate', [AdminPackageController::class, 'syncEasyDonate'])->name('packages.sync-easydonate');
    Route::get('/shop', [AdminShopController::class, 'index'])->name('shop.index');
    Route::get('/shop/{server:slug}', [AdminShopController::class, 'edit'])->name('shop.edit');
    Route::put('/shop/{server:slug}', [AdminShopController::class, 'update'])->name('shop.update');
});

require __DIR__ . '/settings.php';
