<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountStatusController extends Controller
{
    public function __invoke(Request $request): RedirectResponse|View
    {
        $user = $request->user();

        if (! $user->supportsOnboarding()) {
            return redirect()->route($user->dashboardRouteName());
        }

        $role = $user->primaryRole();
        $status = $user->approvalStatus();
        $latestRequest = $user->approvalRequests()->latest('id')->first();
        $roleForUi = $role === 'print_provider' ? 'printer' : $role;
        $roleLabel = match ($role) {
            'designer' => 'مصمم',
            'print_provider' => 'مطبعة',
            'delivery_partner' => 'شركة توصيل',
            default => 'حساب',
        };
        $prefix = match ($role) {
            'designer' => 'DSN',
            'print_provider' => 'PRN',
            'delivery_partner' => 'DLV',
            default => 'ACC',
        };

        return view('auth.account-status', [
            'accountRole' => $roleForUi,
            'accountRoleLabel' => $roleLabel,
            'accountApprovalStatus' => $status,
            'accountReference' => $latestRequest?->request_number
                ?? sprintf('%s-%s-%06d', $prefix, $user->created_at->format('Y'), $user->id),
        ]);
    }
}
