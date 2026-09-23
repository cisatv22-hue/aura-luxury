<?php

namespace AuraShop\Tests\Unit\Domain;

use AuraShop\Domain\Shared\InvalidValue;
use AuraShop\Domain\Shared\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
	public function testFromDecimalStoresCents(): void
	{
		$this->assertSame(145000, Money::fromDecimal('1450.00000000', 'MXN')->amount);
		$this->assertSame(1999, Money::fromDecimal(19.99, 'MXN')->amount);
	}

	public function testFromDecimalRoundsDolibarrPrecision(): void
	{
		$this->assertSame(116, Money::fromDecimal('1.155', 'MXN')->amount);
	}

	public function testArithmetic(): void
	{
		$total = Money::fromDecimal('1450', 'MXN')->multiply(2)->add(Money::fromDecimal('0.50', 'MXN'));

		$this->assertSame('2900.50', $total->toDecimalString());
	}

	public function testRejectsCurrencyMismatch(): void
	{
		$this->expectException(InvalidValue::class);
		Money::zero('MXN')->add(Money::zero('USD'));
	}

	public function testRejectsNonNumericAmount(): void
	{
		$this->expectException(InvalidValue::class);
		Money::fromDecimal('abc', 'MXN');
	}

	public function testNegativeDecimalString(): void
	{
		$this->assertSame('-0.05', (new Money(-5, 'MXN'))->toDecimalString());
	}
}
