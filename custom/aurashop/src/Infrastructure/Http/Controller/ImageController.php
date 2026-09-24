<?php

namespace AuraShop\Infrastructure\Http\Controller;

use AuraShop\Application\Port\ImageSize;
use AuraShop\Application\Port\ProductImages;
use AuraShop\Infrastructure\Http\HttpError;
use AuraShop\Infrastructure\Http\Request;
use AuraShop\Infrastructure\Http\Response;

final class ImageController
{
	private const MIME = array(
		'jpg' => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'png' => 'image/png',
		'webp' => 'image/webp',
		'gif' => 'image/gif',
	);

	public function __construct(private readonly ProductImages $images)
	{
	}

	/**
	 * @param array{ref: string, file: string} $params
	 */
	public function show(Request $request, array $params): Response
	{
		$path = $this->images->resolve($params['ref'], $params['file'], ImageSize::fromInput($request->queryString('size')));
		if ($path === null) {
			throw new HttpError(404, 'image_not_found', 'Imagen no encontrada.');
		}

		$etag = '"'.md5($path.'|'.filemtime($path).'|'.filesize($path)).'"';
		$headers = array('Cache-Control' => 'public, max-age=86400', 'ETag' => $etag);
		if ($request->header('if-none-match') === $etag) {
			return Response::empty(304, $headers);
		}

		$extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

		return Response::file($path, self::MIME[$extension] ?? 'application/octet-stream', $headers);
	}
}
