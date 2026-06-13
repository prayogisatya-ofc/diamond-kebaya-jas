<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class WhatsAppNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_rental_creation_sends_fonnte_whatsapp_notification(): void
    {
        $this->withoutVite();

        config([
            'services.fonnte.enabled' => true,
            'services.fonnte.token' => 'test-fonnte-token',
            'services.fonnte.base_url' => 'https://api.fonnte.com',
            'services.fonnte.country_code' => '62',
        ]);

        Http::fake([
            'https://api.fonnte.com/send' => Http::response(['status' => true], 200),
        ]);

        $user = User::factory()->owner()->create();
        $customer = Customer::factory()->create([
            'name' => 'Sari Wijaya',
            'whatsapp_number' => '0812-9999-8888',
        ]);
        $product = Product::factory()->create([
            'name' => 'Kebaya Merah',
            'base_rental_price' => 150000,
        ]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Size M',
            'stock_quantity' => 2,
            'rental_price' => 175000,
        ]);

        $this->actingAs($user)
            ->post(route('rentals.store'), [
                'customer_mode' => 'existing',
                'customer_id' => $customer->id,
                'guarantee_type' => null,
                'pickup_at' => '2026-06-20 10:00:00',
                'return_due_at' => '2026-06-22 17:00:00',
                'custom_total_amount' => 300000,
                'initial_payment_amount' => 100000,
                'initial_payment_method' => 'cash',
                'items' => [
                    [
                        'product_id' => $product->id,
                        'product_variant_id' => $variant->id,
                        'quantity' => 2,
                        'unit_price' => 175000,
                        'discount_amount' => 50000,
                    ],
                ],
            ])
            ->assertRedirect();

        $rental = Rental::query()->firstOrFail();

        Http::assertSent(function ($request) use ($rental): bool {
            return $request->url() === 'https://api.fonnte.com/send'
                && $request->hasHeader('Authorization', 'test-fonnte-token')
                && $request['target'] === '6281299998888'
                && str_contains($request['message'], 'Sari Wijaya')
                && str_contains($request['message'], $rental->invoice_number)
                && str_contains($request['message'], 'Kebaya Merah')
                && str_contains($request['message'], route('public.rentals.invoice', $rental, absolute: false));
        });
    }

    public function test_public_signed_invoice_link_can_be_opened_without_login(): void
    {
        $this->withoutVite();

        $customer = Customer::factory()->create();
        $rental = Rental::factory()->create([
            'customer_id' => $customer->id,
        ]);

        $signedUrl = URL::temporarySignedRoute(
            'public.rentals.invoice',
            now()->addDay(),
            ['rental' => $rental],
        );

        $this->get($signedUrl)
            ->assertOk()
            ->assertSee($rental->invoice_number);
    }
}
