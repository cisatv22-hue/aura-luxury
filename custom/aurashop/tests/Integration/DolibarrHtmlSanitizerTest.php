<?php

namespace AuraShop\Tests\Integration;

use AuraShop\Infrastructure\Dolibarr\DolibarrHtmlSanitizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Runs against the real Dolibarr functions (container only): AURASHOP_DOLIBARR=1 php vendor/bin/phpunit
 */
final class DolibarrHtmlSanitizerTest extends TestCase
{
	protected function setUp(): void
	{
		if (!defined('DOL_VERSION')) {
			$this->markTestSkipped('Requires Dolibarr: run inside the container with AURASHOP_DOLIBARR=1.');
		}
	}

	/**
	 * @return array<string, array{string}>
	 */
	public static function xssPayloads(): array
	{
		return array(
			'img onerror' => array('<p>Plata</p><img src=x onerror=alert(1)>'),
			'onclick' => array('<p onclick="alert(1)">Cadena</p>'),
			'javascript href' => array('<a href="javascript:alert(1)">ver</a>'),
			'tab obfuscated href' => array('<a href="jav&#x09;ascript:alert(1)">ver</a>'),
			'svg onload' => array('<svg onload=alert(1)></svg>'),
			'script tag' => array('<script>alert(1)</script><p>ok</p>'),
			'iframe' => array('<iframe src="https://evil.example"></iframe>'),
			'details ontoggle' => array('<details open ontoggle=alert(1)>x</details>'),
		);
	}

	#[DataProvider('xssPayloads')]
	public function testRemovesExecutableContent(string $payload): void
	{
		$html = (new DolibarrHtmlSanitizer())->sanitize($payload);

		// Text that Dolibarr does not detect as HTML comes back escaped (&lt;svg ...), which is safe: only real tags matter.
		$this->assertDoesNotMatchRegularExpression('/<[^>]*\son[a-z]+\s*=/i', $html);
		$this->assertDoesNotMatchRegularExpression('/<(script|iframe|svg)\b/i', $html);
		$this->assertDoesNotMatchRegularExpression('/<a[^>]+href\s*=\s*["\']?\s*javascript:/i', html_entity_decode($html));
	}

	public function testKeepsFormatting(): void
	{
		$html = (new DolibarrHtmlSanitizer())->sanitize('<p>Algodón <strong>480 g</strong></p><ul><li>Oversized</li></ul>');

		$this->assertStringContainsString('<strong>480 g</strong>', $html);
		$this->assertStringContainsString('<li>Oversized</li>', $html);
	}

	public function testEscapesPlainTextAndKeepsLineBreaks(): void
	{
		$html = (new DolibarrHtmlSanitizer())->sanitize("Talla <M>\nNegra");

		$this->assertStringStartsWith('Talla &lt;M&gt;<br', $html);
		$this->assertStringEndsWith('Negra', $html);
		$this->assertStringNotContainsString('\\n', $html);
	}
}
