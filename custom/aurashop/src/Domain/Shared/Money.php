<?php

namespace AuraShop\Domain\Shared;

/**
 * Amount in minor units (cents) to avoid floating point errors.
 */
final class Money
{
	public function __construct(
		public readonly int $amount,
		public readonly string $currency
	) {
		if (!preg_match('/^[A-Z]{3}$/', $currency)) {
			throw new InvalidValue('Invalid currency code: '.$currency);
		}
	}

	public static function fromDecimal(string|float|int $value, string $currency): self
	{
		$normalized = is_string($value) ? trim($value) : (string) $value;
		if ($normalized === '' || !is_numeric($normalized)) {
			throw new InvalidValue('Invalid money amount: '.$normalized);
		}

		// Round to 2 decimals first: 1.155 * 100 is 115.4999... in floating point.
		return new self((int) round(round((float) $normalized, 2) * 100), $currency);
	}

	public static function zero(string $currency): self
	{
		return new self(0, $currency);
	}

	public function add(self $other): self
	{
		$this->assertSameCurrency($other);

		return new self($this->amount + $other->amount, $this->currency);
	}

	public function multiply(int $quantity): self
	{
		return new self($this->amount * $quantity, $this->currency);
	}

	public function equals(self $other): bool
	{
		return $this->currency === $other->currency && $this->amount === $other->amount;
	}

	public function toDecimalString(): string
	{
		$sign = $this->amount < 0 ? '-' : '';
		$abs = abs($this->amount);

		return $sign.intdiv($abs, 100).'.'.str_pad((string) ($abs % 100), 2, '0', STR_PAD_LEFT);
	}

	private function assertSameCurrency(self $other): void
	{
		if ($this->currency !== $other->currency) {
			throw new InvalidValue('Currency mismatch: '.$this->currency.' vs '.$other->currency);
		}
	}
}
