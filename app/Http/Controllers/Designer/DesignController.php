<?php

namespace App\Http\Controllers\Designer;

use App\Http\Controllers\Controller;
use App\Models\Design;

class DesignController extends Controller
{
    public function index()
    {

        $designs = Design::all();

        return view('designer.designs.index', compact('designs'));
    }
}