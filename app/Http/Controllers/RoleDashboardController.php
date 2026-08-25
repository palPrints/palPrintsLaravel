<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleDashboardController extends Controller
{
    private const TITLES = [
        'admin' => 'لوحة تحكم الإدارة',
        'customer' => 'لوحة تحكم العميل',
        'designer' => 'لوحة تحكم المصمم',
        'print_provider' => 'لوحة تحكم المطبعة',
        'delivery_partner' => 'لوحة تحكم التوصيل',
    ];

    public function show(Request $request): View
    {
        $role = (string) $request->route('dashboard_role');
        abort_unless($request->user()->hasRole($role), 403);

        return view('dashboard', [
            'dashboardRole' => $role,
            'dashboardTitle' => self::TITLES[$role] ?? 'لوحة التحكم',
        ]);
    }
}
