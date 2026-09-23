<?php

namespace AuraShop\Infrastructure\Http\Controller;

use AuraShop\Application\UseCase\Store\GetStoreConfig;
use AuraShop\Infrastructure\Http\Request;
use AuraShop\Infrastructure\Http\Response;

final class StoreController
{
	public function __construct(private readonly GetStoreConfig $getStoreConfig)
	{
	}

	public function config(Request $request): Response
	{
		return Response::json(($this->getStoreConfig)(), 200, array('Cache-Control' => 'public, max-age=300'));
	}
}
