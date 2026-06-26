<?php

namespace App\Support;

use App\Models\Product;
use App\Models\RentalPackage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicSeoMeta
{
    /**
     * @param  array{store_name: string, store_address: string, store_whatsapp_number: string, invoice_footer_note: string, primary_color: string, whatsapp_notifications_enabled: bool, store_logo_path: string|null, store_logo_url: string|null, store_favicon_path: string|null, store_favicon_url: string|null}  $storeProfile
     * @return array{title: string, description: string, canonical: string, image: string, robots: string, type: string}
     */
    public static function forRequest(Request $request, array $storeProfile): array
    {
        $defaultImage = self::absoluteUrl($request, asset('/og-image.jpg'));
        $siteName = $storeProfile['store_name'] ?: 'Diamond Kebaya & Jas';

        if ($request->routeIs('dashboard', 'login', 'login.store', 'logout', 'profile.*', 'reports.*', 'products.*', 'product-categories.*', 'product-variants.*', 'rental-packages.*', 'customers.*', 'rentals.*', 'rental-availability', 'settings.*', 'users.*')) {
            return [
                'title' => "Panel {$siteName}",
                'description' => "Panel internal {$siteName}.",
                'canonical' => $request->fullUrl(),
                'image' => $defaultImage,
                'robots' => 'noindex,nofollow,noarchive',
                'type' => 'website',
            ];
        }

        if ($request->routeIs('public.catalog.show')) {
            $product = $request->route('product');

            if ($product instanceof Product) {
                $image = $product->imageUrl();

                if (! $image) {
                    $product->loadMissing('variants:id,product_id,image_path');
                    $image = $product->variants->first(fn ($variant) => filled($variant->imageUrl()))?->imageUrl();
                }

                return [
                    'title' => "{$product->name} | {$siteName}",
                    'description' => self::limitDescription(
                        $product->description
                            ?: "{$product->name} tersedia di {$siteName}. Lihat detail varian, referensi harga, lalu datang fitting ke toko sebelum transaksi rental difinalkan."
                    ),
                    'canonical' => route('public.catalog.show', $product),
                    'image' => self::absoluteUrl($request, $image ?: $defaultImage),
                    'robots' => 'index,follow',
                    'type' => 'product',
                ];
            }
        }

        if ($request->routeIs('public.catalog.packages.show')) {
            $rentalPackage = $request->route('rentalPackage');

            if ($rentalPackage instanceof RentalPackage) {
                $rentalPackage->loadMissing([
                    'items.product:id,name,image_path',
                    'items.productVariant:id,product_id,image_path',
                ]);

                $image = $rentalPackage->items
                    ->map(fn ($item): ?string => $item->productVariant?->imageUrl() ?: $item->product?->imageUrl())
                    ->first(fn (?string $imageUrl): bool => filled($imageUrl));

                return [
                    'title' => "{$rentalPackage->name} | {$siteName}",
                    'description' => self::limitDescription(
                        $rentalPackage->description
                            ?: "{$rentalPackage->name} tersedia di {$siteName}. Lihat item yang termasuk dalam paket lalu datang fitting ke toko sebelum transaksi rental difinalkan."
                    ),
                    'canonical' => route('public.catalog.packages.show', $rentalPackage),
                    'image' => self::absoluteUrl($request, $image ?: $defaultImage),
                    'robots' => 'index,follow',
                    'type' => 'product',
                ];
            }
        }

        if ($request->routeIs('public.how-to-rent')) {
            return [
                'title' => "Cara Sewa | {$siteName}",
                'description' => self::limitDescription("Panduan sewa {$siteName}: pilih referensi, datang fitting ke toko, finalisasi pesanan, lalu atur jadwal pengambilan."),
                'canonical' => route('public.how-to-rent'),
                'image' => $defaultImage,
                'robots' => 'index,follow',
                'type' => 'article',
            ];
        }

        if ($request->routeIs('public.faq')) {
            return [
                'title' => "FAQ | {$siteName}",
                'description' => self::limitDescription("FAQ {$siteName} tentang fitting sebelum deal, booking, pelunasan saat ambil, pengembalian, dan keterlambatan rental."),
                'canonical' => route('public.faq'),
                'image' => $defaultImage,
                'robots' => 'index,follow',
                'type' => 'article',
            ];
        }

        $search = trim((string) $request->query('search', ''));
        $description = $search !== ''
            ? "Temukan koleksi kebaya dan jas untuk {$search} di {$siteName}. Lihat referensi model, pilih favorit, lalu jadwalkan fitting di toko."
            : "Katalog public {$siteName} untuk referensi sewa kebaya, jas, dan item pendukung. Pilih model favorit lalu datang fitting ke toko sebelum transaksi difinalkan.";

        return [
            'title' => "Katalog Kebaya & Jas | {$siteName}",
            'description' => self::limitDescription($description),
            'canonical' => $request->fullUrl(),
            'image' => $defaultImage,
            'robots' => 'index,follow',
            'type' => 'website',
        ];
    }

    private static function absoluteUrl(Request $request, string $value): string
    {
        if (Str::startsWith($value, ['http://', 'https://'])) {
            return $value;
        }

        return url($value);
    }

    private static function limitDescription(string $value): string
    {
        return Str::of(trim($value))
            ->replaceMatches('/\s+/', ' ')
            ->limit(160, '')
            ->toString();
    }
}
