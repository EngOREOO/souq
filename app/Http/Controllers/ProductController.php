<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Stancl\Tenancy\Tenancy;

class ProductController extends Controller
{
    public function store(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'description' => 'nullable|string',
        ]);

        // Ensure we're in the context of the tenant
        $tenant = Tenancy::getTenant();

        if (!$tenant) {
            return response()->json(['error' => 'No tenant found.'], 404);
        }

        // Store the product in the tenant's database
        $product = Product::create([
            'name' => $request->input('name'),
            'price' => $request->input('price'),
            'description' => $request->input('description'),
            'tenant_id' => $tenant->id
        ]);

        return response()->json($product, 201);
    }
}
