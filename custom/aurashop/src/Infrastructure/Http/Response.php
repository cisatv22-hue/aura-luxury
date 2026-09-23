<?php

namespace AuraShop\Infrastructure\Http;

final class Response
{
	/**
	 * @param array<string, string> $headers
	 */
	private function __construct(
		public readonly int $status,
		public readonly array $headers,
		public readonly string $body,
		public readonly ?string $filePath = null
	) {
	}

	/**
	 * @param array<string, string> $headers
	 */
	public static function json(mixed $data, int $status = 200, array $headers = array()): self
	{
		// One product with broken UTF-8 (e.g. a Latin-1 CSV import) must not take the whole listing down.
		$body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);

		return new self($status, array('Content-Type' => 'application/json; charset=utf-8') + $headers, $body);
	}

	public static function error(int $status, string $code, string $message): self
	{
		return self::json(array('error' => array('code' => $code, 'message' => $message)), $status, array('Cache-Control' => 'no-store'));
	}

	/**
	 * @param array<string, string> $headers
	 */
	public static function file(string $path, string $contentType, array $headers = array()): self
	{
		return new self(200, array('Content-Type' => $contentType, 'Content-Length' => (string) filesize($path)) + $headers, '', $path);
	}

	/**
	 * @param array<string, string> $headers
	 */
	public static function empty(int $status, array $headers = array()): self
	{
		return new self($status, $headers, '');
	}

	public function send(): void
	{
		http_response_code($this->status);
		header('X-Content-Type-Options: nosniff');
		foreach ($this->headers as $name => $value) {
			header($name.': '.$value);
		}
		if ($this->filePath !== null) {
			readfile($this->filePath);

			return;
		}
		echo $this->body;
	}
}
