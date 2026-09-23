<?php

namespace AuraShop\Infrastructure\Http;

final class Request
{
	/**
	 * @param array<string, mixed>  $query
	 * @param array<string, string> $headers lower-cased names
	 */
	public function __construct(
		public readonly string $method,
		public readonly string $path,
		public readonly array $query = array(),
		public readonly array $headers = array(),
		public readonly string $body = ''
	) {
	}

	public static function fromGlobals(): self
	{
		$headers = array();
		foreach ($_SERVER as $key => $value) {
			if (strncmp($key, 'HTTP_', 5) === 0) {
				$headers[strtolower(str_replace('_', '-', substr($key, 5)))] = (string) $value;
			}
		}
		if (isset($_SERVER['CONTENT_TYPE'])) {
			$headers['content-type'] = (string) $_SERVER['CONTENT_TYPE'];
		}

		$path = (string) ($_SERVER['PATH_INFO'] ?? '/');

		return new self(
			strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')),
			'/'.trim($path, '/'),
			$_GET,
			$headers,
			(string) file_get_contents('php://input')
		);
	}

	public function queryString(string $name): ?string
	{
		$value = $this->query[$name] ?? null;

		return is_scalar($value) ? (string) $value : null;
	}

	public function queryInt(string $name): ?int
	{
		$value = $this->queryString($name);

		return ($value !== null && preg_match('/^-?\d{1,9}$/', $value)) ? (int) $value : null;
	}

	public function header(string $name): ?string
	{
		return $this->headers[strtolower($name)] ?? null;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function json(): array
	{
		if ($this->body === '') {
			return array();
		}
		try {
			$data = json_decode($this->body, true, 32, JSON_THROW_ON_ERROR);
		} catch (\JsonException) {
			throw new HttpError(400, 'invalid_json', 'El cuerpo de la petición no es JSON válido.');
		}
		if (!is_array($data)) {
			throw new HttpError(400, 'invalid_json', 'Se esperaba un objeto JSON.');
		}

		return $data;
	}
}
