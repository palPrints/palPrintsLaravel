<?php

namespace App\Http\Controllers;

use App\Models\Product;

class ProductController extends Controller
{
    public function index()
    {
// ويبعتها ايجيب كل المنتجات
        $products = Product::all();

        return view('designs.index', compact('products'));
    }
}