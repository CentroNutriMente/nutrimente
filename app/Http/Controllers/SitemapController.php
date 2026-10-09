<?php

namespace App\Http\Controllers;

use App\Models\ProfessionalProfile;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    // Sitemap XML con le sole pagine pubbliche indicizzabili.
    // Registrata in Search Console e referenziata da robots.txt.
    public function index(): Response
    {
        $urls = [
            ['loc' => route('home'), 'priority' => '1.0'],
            ['loc' => route('booking.index'), 'priority' => '0.8'],
            ['loc' => route('groups.public.create'), 'priority' => '0.6'],
        ];

        ProfessionalProfile::where('is_bookable', true)
            ->whereNotNull('slug')
            ->pluck('slug')
            ->each(function (string $slug) use (&$urls) {
                $urls[] = ['loc' => route('contact-requests.create', $slug), 'priority' => '0.8'];
            });

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $url) {
            $xml .= '    <url>'."\n";
            $xml .= '        <loc>'.e($url['loc']).'</loc>'."\n";
            $xml .= '        <priority>'.$url['priority'].'</priority>'."\n";
            $xml .= '    </url>'."\n";
        }
        $xml .= '</urlset>'."\n";

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
