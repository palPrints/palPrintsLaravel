<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    public function edit(Request $request): View
    {
        return view('customer.support', [
            'customer' => $request->user(),
        ]);
    }
}
