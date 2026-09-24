<?php

namespace AuraShop\Infrastructure\Dolibarr;

/**
 * Turns a Dolibarr product description into HTML that is safe to inject in the storefront (v-html).
 *
 * dol_string_onlythesehtmltags() only filters tags: it keeps on* attributes and obfuscated javascript: URLs,
 * so its output is passed through dol_htmlwithnojs(), which parses the DOM and drops them.
 */
final class DolibarrHtmlSanitizer
{
	public function sanitize(string $description): string
	{
		if (trim($description) === '') {
			return '';
		}
		if (!dol_textishtml($description)) {
			// keepn=1: by default dol_escape_htmltag() turns newlines into a literal "\n" (meant for JS strings).
			return dol_nl2br(dol_escape_htmltag($description, 0, 1));
		}

		return dol_htmlwithnojs(dol_string_onlythesehtmltags($description, 1, 1, 1), 1, 'restricthtml');
	}
}
