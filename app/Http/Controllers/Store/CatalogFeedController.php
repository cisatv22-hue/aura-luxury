<?php

namespace App\Http\Controllers\Store;

use App\Queries\Catalog\CatalogFeed;
use Illuminate\Http\Response;

class CatalogFeedController
{
    public function google(CatalogFeed $feed): Response
    {
        $xml = view('feeds.google', ['items' => $feed->rows()])->render();

        return response('<?xml version="1.0" encoding="UTF-8"?>'.$xml)->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }

    public function meta(CatalogFeed $feed): Response
    {
        $rows = $feed->rows();
        $stream = fopen('php://temp', 'r+');
        if (! $stream) {
            abort(500, 'No fue posible preparar el catálogo.');
        }
        fputcsv($stream, ['id', 'title', 'description', 'availability', 'condition', 'price', 'link', 'image_link', 'brand']);
        foreach ($rows as $row) {
            fputcsv($stream, [$row['id'], $row['title'], $row['description'], $row['availability'], 'new', $row['price'], $row['link'], $row['image_link'], $row['brand']]);
        }
        rewind($stream);
        $csv = stream_get_contents($stream) ?: '';
        fclose($stream);

        return response($csv, 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'inline; filename="aura-catalog.csv"']);
    }
}
