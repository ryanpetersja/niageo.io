<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ProductResource;
use App\Models\Product;

class ProductController extends Controller
{
    /** Active products, for picking line items quickly on the phone. */
    public function index()
    {
        return ProductResource::collection(
            Product::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get()
        );
    }
}
