<?php

namespace AuraShop\Infrastructure\Http;

final class Router
{
	/** @var list<array{method: string, regex: string, params: list<string>, handler: callable}> */
	private array $routes = array();

	/**
	 * @param string   $pattern e.g. /v1/catalog/products/{ref}
	 * @param callable(Request, array<string, string>): Response $handler
	 */
	public function add(string $method, string $pattern, callable $handler): void
	{
		preg_match_all('/\{(\w+)\}/', $pattern, $matches);
		$regex = '#^'.preg_replace('/\\\\\{\w+\\\\\}/', '([^/]+)', preg_quote($pattern, '#')).'$#';
		$this->routes[] = array(
			'method' => strtoupper($method),
			'regex' => $regex,
			'params' => $matches[1],
			'handler' => $handler,
		);
	}

	public function dispatch(Request $request): Response
	{
		// HEAD is answered like GET; the web server drops the body.
		$method = $request->method === 'HEAD' ? 'GET' : $request->method;
		$pathMatched = false;
		foreach ($this->routes as $route) {
			if (!preg_match($route['regex'], $request->path, $values)) {
				continue;
			}
			$pathMatched = true;
			if ($route['method'] !== $method) {
				continue;
			}
			// PATH_INFO arrives already URL-decoded by the web server: decoding again would corrupt refs containing '%'.
			$params = array();
			foreach ($route['params'] as $i => $name) {
				$params[$name] = $values[$i + 1];
			}

			return ($route['handler'])($request, $params);
		}

		if ($pathMatched) {
			throw new HttpError(405, 'method_not_allowed', 'Método no permitido para esta ruta.');
		}
		throw new HttpError(404, 'route_not_found', 'La ruta solicitada no existe.');
	}
}
