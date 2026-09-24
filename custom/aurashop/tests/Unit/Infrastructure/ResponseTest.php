<?php

namespace AuraShop\Tests\Unit\Infrastructure;

use AuraShop\Infrastructure\Http\Response;
use PHPUnit\Framework\TestCase;

final class ResponseTest extends TestCase
{
	public function testInvalidUtf8IsReplacedInsteadOfFailing(): void
	{
		$response = Response::json(array('label' => "Cadena \xE9 Plata"));

		$this->assertSame(200, $response->status);
		$this->assertSame('{"label":"Cadena '."\u{FFFD}".' Plata"}', $response->body);
	}

	public function testErrorsAreNotCached(): void
	{
		$response = Response::error(404, 'not_found', 'No existe');

		$this->assertSame('no-store', $response->headers['Cache-Control']);
		$this->assertSame('{"error":{"code":"not_found","message":"No existe"}}', $response->body);
	}
}
