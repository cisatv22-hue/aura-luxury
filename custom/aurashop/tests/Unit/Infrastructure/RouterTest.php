<?php

namespace AuraShop\Tests\Unit\Infrastructure;

use AuraShop\Infrastructure\Http\HttpError;
use AuraShop\Infrastructure\Http\Request;
use AuraShop\Infrastructure\Http\Response;
use AuraShop\Infrastructure\Http\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
	private Router $router;

	protected function setUp(): void
	{
		$this->router = new Router();
		$this->router->add('GET', '/v1/catalog/products/{ref}', static fn (Request $r, array $p) => Response::json($p));
	}

	public function testExtractsAndDecodesParams(): void
	{
		$response = $this->router->dispatch(new Request('GET', '/v1/catalog/products/CAD%20925'));

		$this->assertSame('{"ref":"CAD 925"}', $response->body);
	}

	public function testUnknownRouteIs404(): void
	{
		try {
			$this->router->dispatch(new Request('GET', '/v1/nothing'));
			$this->fail('Expected HttpError');
		} catch (HttpError $e) {
			$this->assertSame(404, $e->status);
		}
	}

	public function testWrongMethodIs405(): void
	{
		try {
			$this->router->dispatch(new Request('DELETE', '/v1/catalog/products/X'));
			$this->fail('Expected HttpError');
		} catch (HttpError $e) {
			$this->assertSame(405, $e->status);
		}
	}

	public function testHeadIsServedByGetRoutes(): void
	{
		$this->assertSame(200, $this->router->dispatch(new Request('HEAD', '/v1/catalog/products/X'))->status);
	}

	public function testParamsDoNotSpanSlashes(): void
	{
		$this->expectException(HttpError::class);
		$this->router->dispatch(new Request('GET', '/v1/catalog/products/a/b'));
	}
}
