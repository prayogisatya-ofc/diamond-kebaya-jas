<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Rental;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoreSettingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_owner_can_view_store_setting_page(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)
            ->get(route('settings.edit'))
            ->assertOk()
            ->assertSee('Diamond Kebaya & Jas', false);
    }

    public function test_staff_cannot_access_store_settings(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)
            ->get(route('settings.edit'))
            ->assertForbidden();

        $this->actingAs($staff)
            ->post(route('settings.update'), [])
            ->assertForbidden();
    }

    public function test_owner_can_update_store_settings_and_upload_logo(): void
    {
        Storage::fake('public');

        $owner = User::factory()->owner()->create();
        $logo = UploadedFile::fake()->image('logo.png', 320, 320);

        $this->actingAs($owner)
            ->post(route('settings.update'), [
                'store_name' => 'Diamond UAT',
                'store_address' => 'Jl. UAT No. 10',
                'store_whatsapp_number' => '081200001111',
                'invoice_footer_note' => 'Footer UAT invoice.',
                'primary_color' => '#7c3aed',
                'logo' => $logo,
            ])
            ->assertRedirect(route('settings.edit', absolute: false));

        $profile = Setting::storeProfile();

        $this->assertSame('Diamond UAT', $profile['store_name']);
        $this->assertSame('Jl. UAT No. 10', $profile['store_address']);
        $this->assertSame('081200001111', $profile['store_whatsapp_number']);
        $this->assertSame('Footer UAT invoice.', $profile['invoice_footer_note']);
        $this->assertSame('#7c3aed', $profile['primary_color']);
        $this->assertNotNull($profile['store_logo_path']);
        $this->assertNotNull($profile['store_favicon_path']);
        Storage::disk('public')->assertExists($profile['store_logo_path']);
        Storage::disk('public')->assertExists($profile['store_favicon_path']);
    }

    public function test_root_view_uses_store_favicon_when_configured(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('store-settings/favicon.png', 'favicon-content');

        Setting::updateStoreProfile([
            'store_name' => 'Diamond Favicon Store',
            'store_address' => 'Jl. Favicon No. 1',
            'store_whatsapp_number' => '081277770000',
            'invoice_footer_note' => 'Footer favicon.',
            'store_logo_path' => null,
            'store_favicon_path' => 'store-settings/favicon.png',
        ]);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Diamond Favicon Store')
            ->assertSee('favicon.png');
    }

    public function test_owner_must_use_valid_primary_color(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)
            ->from(route('settings.edit'))
            ->post(route('settings.update'), [
                'store_name' => 'Diamond UAT',
                'store_address' => 'Jl. UAT No. 10',
                'store_whatsapp_number' => '081200001111',
                'invoice_footer_note' => 'Footer UAT invoice.',
                'primary_color' => 'ungu',
            ])
            ->assertRedirect(route('settings.edit', absolute: false))
            ->assertSessionHasErrors('primary_color');
    }

    public function test_invoice_uses_store_settings_and_is_safe_without_logo(): void
    {
        $owner = User::factory()->owner()->create();

        Setting::updateStoreProfile([
            'store_name' => 'Diamond Invoice Store',
            'store_address' => 'Jl. Invoice No. 1',
            'store_whatsapp_number' => '081288880000',
            'invoice_footer_note' => 'Footer invoice dari setting.',
            'store_logo_path' => null,
        ]);

        $rental = Rental::factory()->create([
            'customer_id' => Customer::factory()->create([
                'name' => 'Customer Invoice',
                'whatsapp_number' => '081233331111',
            ])->id,
            'created_by' => $owner->id,
            'invoice_number' => 'INV-UAT-SETTING',
        ]);
        $rental->items()->create([
            'product_id' => Product::factory()->create(['name' => 'Kebaya Invoice'])->id,
            'item_name_snapshot' => 'Kebaya Invoice',
            'quantity' => 1,
            'unit_price' => 100000,
            'discount_amount' => 0,
            'final_price' => 100000,
        ]);

        $this->actingAs($owner)
            ->get(route('rentals.invoice', $rental))
            ->assertOk()
            ->assertSee('Diamond Invoice Store')
            ->assertSee('Jl. Invoice No. 1')
            ->assertSee('081288880000')
            ->assertSee('Footer invoice dari setting.')
            ->assertDontSee('Cannot read');
    }

    public function test_invoice_includes_store_logo_when_configured(): void
    {
        Storage::fake('public');

        $owner = User::factory()->owner()->create();
        Storage::disk('public')->put('store-settings/logo.png', 'logo-content');

        Setting::updateStoreProfile([
            'store_name' => 'Diamond Logo Store',
            'store_address' => 'Jl. Logo No. 1',
            'store_whatsapp_number' => '081299990000',
            'invoice_footer_note' => 'Footer logo.',
            'store_logo_path' => 'store-settings/logo.png',
        ]);

        $rental = Rental::factory()->create([
            'customer_id' => Customer::factory()->create()->id,
            'created_by' => $owner->id,
            'invoice_number' => 'INV-UAT-LOGO',
        ]);

        $this->actingAs($owner)
            ->get(route('rentals.invoice', $rental))
            ->assertOk()
            ->assertSee('Diamond Logo Store')
            ->assertSee('logo.png');
    }
}
