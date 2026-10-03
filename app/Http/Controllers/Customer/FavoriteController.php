<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Design;
use App\Models\DesignFavorite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    public function index(Request $request): View
    {
        $favorites = $request->user()
            ->favoriteDesigns()
            ->with(['designer', 'product'])
            ->where('status', 'published')
            ->whereHas('product', fn ($query) => $query->where('is_active', true))
            ->orderByPivot('created_at', 'desc')
            ->get()
            ->map(fn (Design $design) => $this->favoriteDesignData($design))
            ->values();

        return view('customer.favorites', [
            'favoriteDesigns' => $favorites,
        ]);
    }

    public function toggle(Request $request, Design $design): JsonResponse
    {
        abort_unless($design->status === 'published' && $design->product?->is_active, 404);

        $favorite = DesignFavorite::query()
            ->where('user_id', $request->user()->id)
            ->where('design_id', $design->id)
            ->first();

        if ($favorite) {
            $favorite->delete();

            return response()->json([
                'favorited' => false,
                'design_id' => (string) $design->id,
            ]);
        }

        DesignFavorite::query()->firstOrCreate([
            'user_id' => $request->user()->id,
            'design_id' => $design->id,
        ]);

        return response()->json([
            'favorited' => true,
            'design_id' => (string) $design->id,
        ]);
    }

    private function favoriteDesignData(Design $design): array
    {
        return [
            'id' => (string) $design->id,
            'title' => $design->title,
            'description' => $design->description ?: $design->product?->name,
            'designer' => $design->designer?->name ?? 'PalPrints Designer',
            'price' => (float) ($design->selling_price ?: $design->base_price),
            'image' => $design->image ? asset($design->image) : asset($this->fallbackImage($design)),
            'product' => $design->product?->name,
            'page' => $this->productPage($design),
            'favorite_url' => route('customer.designs.favorite', $design),
        ];
    }

    private function productPage(Design $design): string
    {
        return match (strtoupper((string) $design->product?->code)) {
            'TSHIRT-CLASSIC' => route('customer.tshirts'),
            'HOODIE-PREMIUM' => route('customer.hoodies'),
            'MUG-CERAMIC' => route('customer.mugs'),
            'STICKER-CUSTOM' => route('customer.stickers'),
            default => route('customer.store'),
        };
    }

    private function fallbackImage(Design $design): string
    {
        return match (strtoupper((string) $design->product?->code)) {
            'TSHIRT-CLASSIC' => 'front/assets/images/customer/tshirt.webp',
            'HOODIE-PREMIUM' => 'front/assets/images/customer/hoodie.png',
            'MUG-CERAMIC' => 'front/assets/images/customer/cup.webp',
            'STICKER-CUSTOM' => 'front/assets/images/customer/icons8-sticker-48.png',
            default => 'front/assets/images/customer/products/1.png',
        };
    }
}