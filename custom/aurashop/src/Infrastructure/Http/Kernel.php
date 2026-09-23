<?php

namespace AuraShop\Infrastructure\Http;

use AuraShop\Domain\Shared\InvalidValue;
use AuraShop\Domain\Shared\NotFound;

/**
 * Turns any exception into a JSON error so the API never leaks HTML or stack traces.
 */
final class Kernel
{
	public function __construct(private readonly Router $router)
	{
	}

	public function handle(Request $request): Response
	{
		try {
			return $this->router->dispatch($request);
		} catch (HttpError $e) {
			return Response::error($e->status, $e->errorCode, $e->getMessage());
		} catch (NotFound $e) {
			return Response::error(404, 'not_found', 'No encontramos lo que buscas.');
		} catch (InvalidValue $e) {
			return Response::error(422, 'invalid_value', $e->getMessage());
		} catch (\Throwable $e) {
			dol_syslog('AuraShop API '.$request->method.' '.$request->path.': '.get_class($e).': '.$e->getMessage(), LOG_ERR);

			return Response::error(500, 'internal_error', 'Ocurrió un error inesperado. Intenta de nuevo en unos minutos.');
		}
	}
}
