<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Serve an XML sitemap for search engines.
     *
     * Only public, indexable pages are listed. Authenticated routes are
     * deliberately omitted — they are also blocked in public/robots.txt.
     */
    public function index(): Response
    {
        $urls = $this->getPublicUrls();

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= "    <url>\n";
            $xml .= "        <loc>" . htmlspecialchars($url['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc>\n";

            if (! empty($url['lastmod'])) {
                $xml .= "        <lastmod>" . $url['lastmod'] . "</lastmod>\n";
            }
            if (! empty($url['changefreq'])) {
                $xml .= "        <changefreq>" . $url['changefreq'] . "</changefreq>\n";
            }
            if (! empty($url['priority'])) {
                $xml .= "        <priority>" . $url['priority'] . "</priority>\n";
            }

            $xml .= "    </url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * Public URLs to include in the sitemap.
     *
     * Only list URLs that are:
     *   - reachable without authentication
     *   - safe to be indexed by Google (no PII, no farm data)
     *   - return HTTP 200
     *
     * Add new entries here as you build public-facing pages
     * (e.g. About, Pricing, Contact, Blog). Do NOT list any route
     * that sits behind auth middleware.
     *
     * @return array<int, array{loc: string, lastmod?: string, changefreq?: string, priority?: string}>
     */
    private function getPublicUrls(): array
    {
        return [
            [
                'loc'        => route('home'),
                'lastmod'    => now()->toDateString(),
                'changefreq' => 'weekly',
                'priority'   => '1.0',
            ],
        ];
    }
}