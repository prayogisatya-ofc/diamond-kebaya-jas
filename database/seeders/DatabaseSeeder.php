<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\Rental;
use App\Models\RentalPackage;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $users = $this->seedUsers();
            $categories = $this->seedCategories();
            $products = $this->seedProducts($categories);
            $packages = $this->seedPackages($products);
            $customers = $this->seedCustomers();

            $this->seedSettings();
            $this->seedRentals($users, $products, $packages, $customers);
        });
    }

    /**
     * @return array{owner: User, staff: User}
     */
    private function seedUsers(): array
    {
        $owner = User::query()->updateOrCreate([
            'email' => 'owner@diamond.test',
        ], [
            'name' => 'Owner Diamond',
            'username' => 'owner',
            'password' => 'password',
            'role' => UserRole::Owner,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $staff = User::query()->updateOrCreate([
            'email' => 'staff@diamond.test',
        ], [
            'name' => 'Staff Diamond',
            'username' => 'staff',
            'password' => 'password',
            'role' => UserRole::Staff,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        return [
            'owner' => $owner,
            'staff' => $staff,
        ];
    }

    private function seedSettings(): void
    {
        Setting::updateStoreProfile([
            'store_name' => 'Diamond Kebaya & Jas',
            'store_address' => 'Jl. Contoh Toko No. 12, Jakarta',
            'store_whatsapp_number' => '081234567890',
            'invoice_footer_note' => 'Terima kasih sudah menyewa di Diamond Kebaya & Jas. Simpan nota ini sebagai bukti transaksi.',
            'primary_color' => '#615cf9',
            'store_logo_path' => null,
            'store_favicon_path' => null,
        ]);
    }

    /**
     * @return array<string, ProductCategory>
     */
    private function seedCategories(): array
    {
        $categories = [];

        foreach ([
            'kebaya' => 'Kebaya',
            'jas' => 'Jas',
            'rok' => 'Rok',
            'celana' => 'Celana',
            'kemeja' => 'Kemeja',
            'dasi' => 'Dasi',
            'sepatu' => 'Sepatu',
            'aksesoris' => 'Aksesoris',
        ] as $slug => $name) {
            $categories[$slug] = ProductCategory::query()->updateOrCreate([
                'slug' => $slug,
            ], [
                'name' => $name,
                'is_active' => true,
            ]);
        }

        return $categories;
    }

    /**
     * @param  array<string, ProductCategory>  $categories
     * @return array<string, Product|ProductVariant>
     */
    private function seedProducts(array $categories): array
    {
        $kebaya = $this->product($categories['kebaya'], 'KB-MRH-001', 'Kebaya Merah Modern', 'Kebaya merah untuk akad dan wisuda.', 250000);
        $kebayaM = $this->variant($kebaya, 'KB-MRH-M', 'Size M Merah', 'M', 'Merah', 2, 275000);
        $kebayaL = $this->variant($kebaya, 'KB-MRH-L', 'Size L Merah', 'L', 'Merah', 1, 275000);

        $jas = $this->product($categories['jas'], 'JS-HTM-001', 'Jas Hitam Slim Fit', 'Jas hitam formal untuk pria.', 300000);
        $jasM = $this->variant($jas, 'JS-HTM-M', 'Size M Hitam', 'M', 'Hitam', 2, 325000);
        $jasL = $this->variant($jas, 'JS-HTM-L', 'Size L Hitam', 'L', 'Hitam', 2, 325000);

        $rok = $this->product($categories['rok'], 'RK-BTK-001', 'Rok Batik Coklat', 'Rok batik pasangan kebaya.', 100000);
        $rokAll = $this->variant($rok, 'RK-BTK-ALL', 'All Size Coklat', null, 'Coklat', 3, null);

        $kemeja = $this->product($categories['kemeja'], 'KM-PTH-001', 'Kemeja Putih Formal', 'Kemeja putih pendamping jas.', 80000);
        $kemejaM = $this->variant($kemeja, 'KM-PTH-M', 'Size M Putih', 'M', 'Putih', 3, null);

        $dasi = $this->product($categories['dasi'], 'DS-HTM-001', 'Dasi Hitam', 'Dasi hitam reguler.', 30000);
        $dasiRegular = $this->variant($dasi, 'DS-HTM-REG', 'Regular Hitam', null, 'Hitam', 5, null);

        $sepatu = $this->product($categories['sepatu'], 'SP-PTF-001', 'Sepatu Pantofel Hitam', 'Sepatu pantofel formal.', 100000);
        $sepatu42 = $this->variant($sepatu, 'SP-PTF-42', 'Size 42 Hitam', '42', 'Hitam', 2, 110000);

        return [
            'kebaya' => $kebaya,
            'kebaya_m' => $kebayaM,
            'kebaya_l' => $kebayaL,
            'jas' => $jas,
            'jas_m' => $jasM,
            'jas_l' => $jasL,
            'rok' => $rok,
            'rok_all' => $rokAll,
            'kemeja' => $kemeja,
            'kemeja_m' => $kemejaM,
            'dasi' => $dasi,
            'dasi_regular' => $dasiRegular,
            'sepatu' => $sepatu,
            'sepatu_42' => $sepatu42,
        ];
    }

    private function product(ProductCategory $category, string $code, string $name, string $description, int $price): Product
    {
        return Product::query()->updateOrCreate([
            'code' => $code,
        ], [
            'product_category_id' => $category->id,
            'name' => $name,
            'description' => $description,
            'base_rental_price' => $price,
            'is_active' => true,
        ]);
    }

    private function variant(Product $product, string $sku, string $name, ?string $size, ?string $color, int $stock, ?int $price): ProductVariant
    {
        return ProductVariant::query()->updateOrCreate([
            'sku' => $sku,
        ], [
            'product_id' => $product->id,
            'name' => $name,
            'size' => $size,
            'color' => $color,
            'stock_quantity' => $stock,
            'rental_price' => $price,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<string, Product|ProductVariant>  $products
     * @return array<string, RentalPackage>
     */
    private function seedPackages(array $products): array
    {
        $kebayaPackage = RentalPackage::query()->updateOrCreate([
            'name' => 'Paket Kebaya Lengkap',
        ], [
            'description' => 'Template paket kebaya dengan rok pendamping.',
            'package_price' => 350000,
            'is_active' => true,
        ]);

        $kebayaPackage->items()->delete();
        $kebayaPackage->items()->createMany([
            [
                'product_id' => $products['kebaya']->id,
                'product_variant_id' => $products['kebaya_m']->id,
                'quantity' => 1,
                'default_item_price' => 275000,
                'is_optional' => false,
                'notes' => 'Kebaya utama.',
            ],
            [
                'product_id' => $products['rok']->id,
                'product_variant_id' => $products['rok_all']->id,
                'quantity' => 1,
                'default_item_price' => 100000,
                'is_optional' => false,
                'notes' => 'Rok pasangan.',
            ],
        ]);

        $jasPackage = RentalPackage::query()->updateOrCreate([
            'name' => 'Paket Jas Full Set',
        ], [
            'description' => 'Template paket jas lengkap untuk acara formal.',
            'package_price' => 450000,
            'is_active' => true,
        ]);

        $jasPackage->items()->delete();
        $jasPackage->items()->createMany([
            [
                'product_id' => $products['jas']->id,
                'product_variant_id' => $products['jas_m']->id,
                'quantity' => 1,
                'default_item_price' => 325000,
                'is_optional' => false,
                'notes' => 'Jas utama.',
            ],
            [
                'product_id' => $products['kemeja']->id,
                'product_variant_id' => $products['kemeja_m']->id,
                'quantity' => 1,
                'default_item_price' => 80000,
                'is_optional' => false,
                'notes' => 'Kemeja pendamping.',
            ],
            [
                'product_id' => $products['dasi']->id,
                'product_variant_id' => $products['dasi_regular']->id,
                'quantity' => 1,
                'default_item_price' => 30000,
                'is_optional' => true,
                'notes' => 'Dasi opsional.',
            ],
            [
                'product_id' => $products['sepatu']->id,
                'product_variant_id' => $products['sepatu_42']->id,
                'quantity' => 1,
                'default_item_price' => 100000,
                'is_optional' => true,
                'notes' => 'Sepatu opsional.',
            ],
        ]);

        return [
            'kebaya' => $kebayaPackage,
            'jas' => $jasPackage,
        ];
    }

    /**
     * @return array<string, Customer>
     */
    private function seedCustomers(): array
    {
        return [
            'rina' => Customer::query()->updateOrCreate([
                'whatsapp_number' => '081234567890',
            ], [
                'name' => 'Rina Kartika',
                'notes' => 'Repeat customer paket kebaya.',
            ]),
            'andi' => Customer::query()->updateOrCreate([
                'whatsapp_number' => '081299988877',
            ], [
                'name' => 'Andi Wijaya',
                'notes' => 'Sewa jas untuk acara kantor.',
            ]),
            'sinta' => Customer::query()->updateOrCreate([
                'whatsapp_number' => '081277766655',
            ], [
                'name' => 'Sinta Lestari',
                'notes' => 'Customer demo transaksi selesai.',
            ]),
        ];
    }

    /**
     * @param  array{owner: User, staff: User}  $users
     * @param  array<string, Product|ProductVariant>  $products
     * @param  array<string, RentalPackage>  $packages
     * @param  array<string, Customer>  $customers
     */
    private function seedRentals(array $users, array $products, array $packages, array $customers): void
    {
        $today = Carbon::today();

        $this->syncRental([
            'invoice_number' => 'INV-DEMO-BOOKED',
            'customer_id' => $customers['rina']->id,
            'status' => 'booked',
            'payment_status' => 'dp',
            'guarantee_type' => 'ktp',
            'pickup_at' => $today->copy()->addDay()->setTime(10, 0),
            'return_due_at' => $today->copy()->addDays(3)->setTime(17, 0),
            'subtotal_amount' => 350000,
            'discount_amount' => 0,
            'custom_adjustment_amount' => 0,
            'penalty_days' => 0,
            'penalty_amount' => 0,
            'total_amount' => 350000,
            'paid_amount' => 100000,
            'remaining_amount' => 250000,
            'notes' => 'Booking demo dengan DP.',
            'created_by' => $users['staff']->id,
        ], [
            [
                'rental_package_id' => $packages['kebaya']->id,
                'product_id' => $products['kebaya']->id,
                'product_variant_id' => $products['kebaya_m']->id,
                'item_name_snapshot' => 'Kebaya Merah Modern',
                'variant_name_snapshot' => 'Size M Merah',
                'quantity' => 1,
                'unit_price' => 275000,
                'discount_amount' => 0,
                'final_price' => 275000,
                'notes' => 'Snapshot dari paket kebaya.',
            ],
            [
                'rental_package_id' => $packages['kebaya']->id,
                'product_id' => $products['rok']->id,
                'product_variant_id' => $products['rok_all']->id,
                'item_name_snapshot' => 'Rok Batik Coklat',
                'variant_name_snapshot' => 'All Size Coklat',
                'quantity' => 1,
                'unit_price' => 100000,
                'discount_amount' => 25000,
                'final_price' => 75000,
                'notes' => 'Diskon paket demo.',
            ],
        ], [
            [
                'payment_type' => 'dp',
                'payment_method' => 'cash',
                'amount' => 100000,
                'paid_at' => $today->copy()->setTime(9, 30),
                'notes' => 'DP tunai.',
                'created_by' => $users['staff']->id,
            ],
        ]);

        $this->syncRental([
            'invoice_number' => 'INV-DEMO-PICKED',
            'customer_id' => $customers['andi']->id,
            'status' => 'picked_up',
            'payment_status' => 'paid',
            'guarantee_type' => 'sim',
            'pickup_at' => $today->copy()->subDay()->setTime(11, 0),
            'return_due_at' => $today->copy()->addDay()->setTime(17, 0),
            'picked_up_at' => $today->copy()->subDay()->setTime(11, 15),
            'subtotal_amount' => 450000,
            'discount_amount' => 0,
            'custom_adjustment_amount' => 0,
            'penalty_days' => 0,
            'penalty_amount' => 0,
            'total_amount' => 450000,
            'paid_amount' => 450000,
            'remaining_amount' => 0,
            'notes' => 'Barang sudah diambil dan lunas.',
            'created_by' => $users['staff']->id,
            'picked_up_by' => $users['staff']->id,
        ], [
            [
                'rental_package_id' => $packages['jas']->id,
                'product_id' => $products['jas']->id,
                'product_variant_id' => $products['jas_m']->id,
                'item_name_snapshot' => 'Jas Hitam Slim Fit',
                'variant_name_snapshot' => 'Size M Hitam',
                'quantity' => 1,
                'unit_price' => 325000,
                'discount_amount' => 0,
                'final_price' => 325000,
                'notes' => 'Jas utama.',
            ],
            [
                'rental_package_id' => $packages['jas']->id,
                'product_id' => $products['kemeja']->id,
                'product_variant_id' => $products['kemeja_m']->id,
                'item_name_snapshot' => 'Kemeja Putih Formal',
                'variant_name_snapshot' => 'Size M Putih',
                'quantity' => 1,
                'unit_price' => 80000,
                'discount_amount' => 0,
                'final_price' => 80000,
                'notes' => null,
            ],
            [
                'rental_package_id' => $packages['jas']->id,
                'product_id' => $products['dasi']->id,
                'product_variant_id' => $products['dasi_regular']->id,
                'item_name_snapshot' => 'Dasi Hitam',
                'variant_name_snapshot' => 'Regular Hitam',
                'quantity' => 1,
                'unit_price' => 30000,
                'discount_amount' => 0,
                'final_price' => 30000,
                'notes' => null,
            ],
            [
                'rental_package_id' => $packages['jas']->id,
                'product_id' => $products['sepatu']->id,
                'product_variant_id' => $products['sepatu_42']->id,
                'item_name_snapshot' => 'Sepatu Pantofel Hitam',
                'variant_name_snapshot' => 'Size 42 Hitam',
                'quantity' => 1,
                'unit_price' => 100000,
                'discount_amount' => 85000,
                'final_price' => 15000,
                'notes' => 'Harga paket disesuaikan.',
            ],
        ], [
            [
                'payment_type' => 'dp',
                'payment_method' => 'transfer',
                'amount' => 150000,
                'paid_at' => $today->copy()->subDays(2)->setTime(14, 0),
                'notes' => 'DP transfer.',
                'created_by' => $users['staff']->id,
            ],
            [
                'payment_type' => 'pelunasan',
                'payment_method' => 'qris',
                'amount' => 300000,
                'paid_at' => $today->copy()->subDay()->setTime(11, 10),
                'notes' => 'Pelunasan saat ambil.',
                'created_by' => $users['staff']->id,
            ],
        ]);

        $this->syncRental([
            'invoice_number' => 'INV-DEMO-COMPLETED',
            'customer_id' => $customers['sinta']->id,
            'status' => 'completed',
            'payment_status' => 'paid',
            'guarantee_type' => 'ktp',
            'pickup_at' => $today->copy()->subDays(5)->setTime(10, 0),
            'return_due_at' => $today->copy()->subDays(3)->setTime(17, 0),
            'picked_up_at' => $today->copy()->subDays(5)->setTime(10, 20),
            'returned_at' => $today->copy()->subDays(2)->setTime(10, 0),
            'subtotal_amount' => 300000,
            'discount_amount' => 0,
            'custom_adjustment_amount' => 0,
            'penalty_days' => 1,
            'penalty_amount' => 25000,
            'total_amount' => 325000,
            'paid_amount' => 325000,
            'remaining_amount' => 0,
            'notes' => 'Demo transaksi selesai dengan denda manual.',
            'created_by' => $users['staff']->id,
            'picked_up_by' => $users['staff']->id,
            'returned_by' => $users['staff']->id,
        ], [
            [
                'rental_package_id' => null,
                'product_id' => $products['jas']->id,
                'product_variant_id' => $products['jas_l']->id,
                'item_name_snapshot' => 'Jas Hitam Slim Fit',
                'variant_name_snapshot' => 'Size L Hitam',
                'quantity' => 1,
                'unit_price' => 300000,
                'discount_amount' => 0,
                'final_price' => 300000,
                'notes' => 'Sewa satuan.',
            ],
        ], [
            [
                'payment_type' => 'dp',
                'payment_method' => 'cash',
                'amount' => 100000,
                'paid_at' => $today->copy()->subDays(5)->setTime(9, 45),
                'notes' => 'DP tunai.',
                'created_by' => $users['staff']->id,
            ],
            [
                'payment_type' => 'pelunasan',
                'payment_method' => 'transfer',
                'amount' => 200000,
                'paid_at' => $today->copy()->subDays(5)->setTime(10, 10),
                'notes' => 'Pelunasan saat ambil.',
                'created_by' => $users['staff']->id,
            ],
            [
                'payment_type' => 'denda',
                'payment_method' => 'cash',
                'amount' => 25000,
                'paid_at' => $today->copy()->subDays(2)->setTime(10, 5),
                'notes' => 'Denda manual keterlambatan.',
                'created_by' => $users['staff']->id,
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<int, array<string, mixed>>  $payments
     */
    private function syncRental(array $attributes, array $items, array $payments): Rental
    {
        $rental = Rental::query()->updateOrCreate([
            'invoice_number' => $attributes['invoice_number'],
        ], $attributes);

        $rental->items()->delete();
        $rental->payments()->delete();
        $rental->items()->createMany($items);
        $rental->payments()->createMany($payments);

        return $rental;
    }
}
