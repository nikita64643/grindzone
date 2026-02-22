<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BalanceTopup;
use App\Models\PrivilegePurchase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function index(): Response
    {
        $topups = BalanceTopup::with('user:id,name,email')
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        $purchases = PrivilegePurchase::with('user:id,name,email')
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        return Inertia::render('admin/payments/Index', [
            'topups' => $topups->map(fn(BalanceTopup $t) => [
                'id' => $t->id,
                'user_id' => $t->user_id,
                'user_name' => $t->user?->name ?? $t->user?->email ?? '—',
                'amount' => (float) $t->amount,
                'coins' => (int) ($t->coins ?? 0),
                'order_id' => $t->order_id,
                'status' => $t->status,
                'created_at' => $t->created_at?->toIso8601String(),
            ])->all(),
            'purchases' => $purchases->map(fn(PrivilegePurchase $p) => [
                'id' => $p->id,
                'user_id' => $p->user_id,
                'user_name' => $p->user?->name ?? $p->user?->email ?? '—',
                'server_name' => $p->server_name,
                'privilege_name' => $p->privilege_name,
                'amount' => (float) $p->amount,
                'order_id' => $p->order_id,
                'status' => $p->status,
                'created_at' => $p->created_at?->toIso8601String(),
            ])->all(),
        ]);
    }

    public function updateTopupStatus(Request $request, BalanceTopup $topup): RedirectResponse
    {
        $request->validate([
            'status' => 'required|in:pending,completed,failed,refunded',
        ]);

        $oldStatus = $topup->status;
        $topup->update(['status' => $request->status]);

        if ($request->status === 'completed' && $oldStatus !== 'completed') {
            $credits = (int) ($topup->coins ?? $topup->amount ?? 0);
            if ($credits > 0) {
                $topup->user->increment('balance', $credits);
            }
        } elseif ($oldStatus === 'completed' && $request->status !== 'completed') {
            $credits = (int) ($topup->coins ?? $topup->amount ?? 0);
            if ($credits > 0) {
                $topup->user->decrement('balance', $credits);
            }
        }

        return back()->with('status', 'Статус пополнения обновлён.');
    }

    public function updatePurchaseStatus(Request $request, PrivilegePurchase $purchase): RedirectResponse
    {
        $request->validate([
            'status' => 'required|in:pending,completed,failed,refunded',
        ]);

        $oldStatus = $purchase->status;
        $purchase->update(['status' => $request->status]);

        if ($request->status === 'completed' && $oldStatus !== 'completed') {
            $purchase->user->donations()->create([
                'server_slug' => $purchase->server_slug,
                'server_name' => $purchase->server_name,
                'privilege_key' => $purchase->privilege_key,
                'privilege_name' => $purchase->privilege_name,
                'amount' => $purchase->amount,
            ]);
        }

        return back()->with('status', 'Статус покупки обновлён.');
    }
}
