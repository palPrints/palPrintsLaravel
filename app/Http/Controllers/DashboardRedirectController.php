<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardRedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse|View
    {
        $routeName = $request->user()->dashboardRouteName();

        if ($routeName === 'dashboard') {
            return view('dashboard', [
                'dashboardRole' => null,
                'dashboardTitle' => 'لوحة التحكم',
            ]);
        }

        return redirect()->route($routeName);
    }
}
