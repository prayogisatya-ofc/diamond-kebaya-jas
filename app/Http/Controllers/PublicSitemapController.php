<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PublicSitemapController extends Controller
{
    public function sitemap(): Response
    {
        $entries = collect([
            [
                'loc' => route('public.catalog'),
                'lastmod' => now(),
                'changefreq' => 'daily',
                'priority' => '1.0',
            ],
            [
                'loc' => route('public.how-to-rent'),
                'lastmod' => now(),
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ],
            [
                'loc' => route('public.faq'),
                'lastmod' => now(),
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ],
        ])->merge($this->productEntries());

        return response()
            ->view('sitemap', [
                'entries' => $entries,
            ])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        $content = implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /panel',
            'Sitemap: '.route('public.sitemap'),
        ]);

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    /**
     * @return Collection<int, array{loc: string, lastmod: Carbon, changefreq: string, priority: string}>
     */
    private function productEntries(): Collection
    {
        return Product::query()
            ->select(['id', 'updated_at'])
            ->where('is_active', true)
            ->whereHas('category', fn ($query) => $query->where('is_active', true))
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (Product $product): array => [
                'loc' => route('public.catalog.show', $product),
                'lastmod' => $product->updated_at ?? now(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ]);
    }
}
