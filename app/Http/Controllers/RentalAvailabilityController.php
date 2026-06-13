<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use App\Services\RentalAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RentalAvailabilityController extends Controller
{
    public function __invoke(Request $request, RentalAvailabilityService $availabilityService): JsonResponse
    {
        $validated = $request->validate([
            'product_variant_id' => ['required', 'ulid', 'exists:product_variants,id'],
            'pickup_at' => ['required', 'date'],
            'return_due_at' => ['required', 'date', 'after:pickup_at'],
            'ignore_rental_id' => ['nullable', 'ulid', 'exists:rentals,id'],
        ]);

        $variant = ProductVariant::query()->findOrFail($validated['product_variant_id']);

        return response()->json([
            'availability' => $availabilityService->availabilityForVariant(
                $variant,
                $validated['pickup_at'],
                $validated['return_due_at'],
                $validated['ignore_rental_id'] ?? null
            ),
        ]);
    }
}
