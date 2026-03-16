<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Test\Core\Lib;

use FacturaScripts\Core\Lib\InvoiceOperation;
use FacturaScripts\Core\Lib\TaxExceptions;
use PHPUnit\Framework\TestCase;

final class TaxExceptionsTest extends TestCase
{
    public function testAllReturnsDefaults(): void
    {
        $all = TaxExceptions::all();

        $this->assertArrayHasKey(TaxExceptions::ES_TAX_EXCEPTION_20, $all);
        $this->assertArrayHasKey(TaxExceptions::ES_TAX_EXCEPTION_21, $all);
        $this->assertArrayHasKey(TaxExceptions::ES_TAX_EXCEPTION_22, $all);
        $this->assertArrayHasKey(TaxExceptions::ES_TAX_EXCEPTION_23_24, $all);
        $this->assertArrayHasKey(TaxExceptions::ES_TAX_EXCEPTION_25, $all);
        $this->assertArrayHasKey(TaxExceptions::ES_TAX_EXCEPTION_OTHER, $all);
        $this->assertArrayHasKey(TaxExceptions::ES_TAX_EXCEPTION_7, $all);
        $this->assertArrayHasKey(TaxExceptions::ES_TAX_EXCEPTION_68_70, $all);
        $this->assertArrayHasKey(TaxExceptions::ES_TAX_EXCEPTION_84, $all);
        $this->assertCount(11, $all);
    }

    public function testAddCustomException(): void
    {
        TaxExceptions::add('custom-exc', 'custom-exc-label');

        $all = TaxExceptions::all();
        $this->assertArrayHasKey('custom-exc', $all);
        $this->assertEquals('custom-exc-label', $all['custom-exc']);

        // limpiamos
        TaxExceptions::remove('custom-exc');
        $this->assertArrayNotHasKey('custom-exc', TaxExceptions::all());
    }

    public function testAddOverridesExistingLabel(): void
    {
        $original = TaxExceptions::all()[TaxExceptions::ES_TAX_EXCEPTION_20];

        TaxExceptions::add(TaxExceptions::ES_TAX_EXCEPTION_20, 'new-label');
        $this->assertEquals('new-label', TaxExceptions::all()[TaxExceptions::ES_TAX_EXCEPTION_20]);

        // restauramos
        TaxExceptions::add(TaxExceptions::ES_TAX_EXCEPTION_20, $original);
    }

    public function testRemoveDefaultException(): void
    {
        TaxExceptions::remove(TaxExceptions::ES_TAX_EXCEPTION_OTHER);
        $this->assertArrayNotHasKey(TaxExceptions::ES_TAX_EXCEPTION_OTHER, TaxExceptions::all());

        // restauramos
        TaxExceptions::add(TaxExceptions::ES_TAX_EXCEPTION_OTHER, 'es-tax-exception-other');
        $this->assertArrayHasKey(TaxExceptions::ES_TAX_EXCEPTION_OTHER, TaxExceptions::all());
    }

    public function testRemoveAndReAdd(): void
    {
        TaxExceptions::remove(TaxExceptions::ES_TAX_EXCEPTION_7);
        $this->assertArrayNotHasKey(TaxExceptions::ES_TAX_EXCEPTION_7, TaxExceptions::all());

        TaxExceptions::add(TaxExceptions::ES_TAX_EXCEPTION_7, 'es-tax-exception-7');
        $this->assertArrayHasKey(TaxExceptions::ES_TAX_EXCEPTION_7, TaxExceptions::all());
    }

    public function testAddKeyIsTruncatedTo20Chars(): void
    {
        $longKey = 'this-key-is-way-too-long-for-limit';
        TaxExceptions::add($longKey, 'long-label');

        $truncated = substr($longKey, 0, 20);
        $all = TaxExceptions::all();
        $this->assertArrayHasKey($truncated, $all);
        $this->assertArrayNotHasKey($longKey, $all);

        // limpiamos
        TaxExceptions::remove($longKey);
    }

    // isValidCombination: sin operación

    public function testNoOperationAllowsNull(): void
    {
        $this->assertTrue(TaxExceptions::isValidCombination(null, null, 'sales'));
        $this->assertTrue(TaxExceptions::isValidCombination('', null, 'purchases'));
    }

    public function testNoOperationAllowsGenericExceptions(): void
    {
        $this->assertTrue(TaxExceptions::isValidCombination(null, TaxExceptions::ES_TAX_EXCEPTION_20, 'sales'));
        $this->assertTrue(TaxExceptions::isValidCombination(null, TaxExceptions::ES_TAX_EXCEPTION_OTHER, 'sales'));
        $this->assertTrue(TaxExceptions::isValidCombination(null, TaxExceptions::ES_TAX_EXCEPTION_7, 'purchases'));
        $this->assertTrue(TaxExceptions::isValidCombination(null, TaxExceptions::ES_TAX_EXCEPTION_68_70, 'purchases'));
        $this->assertTrue(TaxExceptions::isValidCombination(null, TaxExceptions::ES_TAX_EXCEPTION_84, 'sales'));
    }

    public function testNoOperationRejectsSpecificExceptions(): void
    {
        $this->assertFalse(TaxExceptions::isValidCombination(null, TaxExceptions::ES_TAX_EXCEPTION_21, 'sales'));
        $this->assertFalse(TaxExceptions::isValidCombination(null, TaxExceptions::ES_TAX_EXCEPTION_22, 'purchases'));
        $this->assertFalse(TaxExceptions::isValidCombination(null, TaxExceptions::ES_TAX_EXCEPTION_23_24, 'sales'));
        $this->assertFalse(TaxExceptions::isValidCombination(null, TaxExceptions::ES_TAX_EXCEPTION_25, 'purchases'));
    }

    // isValidCombination: intracomunitaria

    public function testIntraCommunityValidSales(): void
    {
        $op = InvoiceOperation::INTRA_COMMUNITY;
        $this->assertTrue(TaxExceptions::isValidCombination($op, TaxExceptions::ES_TAX_EXCEPTION_22, 'sales'));
        $this->assertTrue(TaxExceptions::isValidCombination($op, TaxExceptions::ES_TAX_EXCEPTION_23_24, 'sales'));
        $this->assertTrue(TaxExceptions::isValidCombination($op, TaxExceptions::ES_TAX_EXCEPTION_25, 'sales'));
        $this->assertTrue(TaxExceptions::isValidCombination($op, TaxExceptions::ES_TAX_EXCEPTION_68_70, 'sales'));
    }

    public function testIntraCommunityInvalidSales(): void
    {
        $op = InvoiceOperation::INTRA_COMMUNITY;
        $this->assertFalse(TaxExceptions::isValidCombination($op, TaxExceptions::ES_TAX_EXCEPTION_20, 'sales'));
        $this->assertFalse(TaxExceptions::isValidCombination($op, TaxExceptions::ES_TAX_EXCEPTION_84, 'sales'));
        $this->assertFalse(TaxExceptions::isValidCombination($op, null, 'sales'));
    }

    public function testIntraCommunityValidPurchases(): void
    {
        $op = InvoiceOperation::INTRA_COMMUNITY;
        $this->assertTrue(TaxExceptions::isValidCombination($op, TaxExceptions::ES_TAX_EXCEPTION_84, 'purchases'));
        $this->assertTrue(TaxExceptions::isValidCombination($op, TaxExceptions::ES_TAX_EXCEPTION_7, 'purchases'));
        $this->assertTrue(TaxExceptions::isValidCombination($op, TaxExceptions::ES_TAX_EXCEPTION_68_70, 'purchases'));
    }

    public function testIntraCommunityInvalidPurchases(): void
    {
        $op = InvoiceOperation::INTRA_COMMUNITY;
        $this->assertFalse(TaxExceptions::isValidCombination($op, TaxExceptions::ES_TAX_EXCEPTION_25, 'purchases'));
        $this->assertFalse(TaxExceptions::isValidCombination($op, null, 'purchases'));
    }

    // isValidCombination: intracomunitaria servicios

    public function testIntraCommunityServicesValid(): void
    {
        $op = InvoiceOperation::INTRA_COMMUNITY_SERVICES;
        $this->assertTrue(TaxExceptions::isValidCombination($op, TaxExceptions::ES_TAX_EXCEPTION_68_70, 'sales'));
        $this->assertTrue(TaxExceptions::isValidCombination($op, TaxExceptions::ES_TAX_EXCEPTION_84, 'purchases'));
    }

    public function testIntraCommunityServicesInvalid(): void
    {
        $op = InvoiceOperation::INTRA_COMMUNITY_SERVICES;
        $this->assertFalse(TaxExceptions::isValidCombination($op, TaxExceptions::ES_TAX_EXCEPTION_20, 'sales'));
        $this->assertFalse(TaxExceptions::isValidCombination($op, null, 'sales'));
        $this->assertFalse(TaxExceptions::isValidCombination($op, TaxExceptions::ES_TAX_EXCEPTION_68_70, 'purchases'));
        $this->assertFalse(TaxExceptions::isValidCombination($op, null, 'purchases'));
    }

    // isValidCombination: inversión sujeto pasivo

    public function testReverseChargeValid(): void
    {
        $op = InvoiceOperation::REVERSE_CHARGE;
        $this->assertTrue(TaxExceptions::isValidCombination($op, TaxExceptions::ES_TAX_EXCEPTION_84, 'sales'));
        $this->assertTrue(TaxExceptions::isValidCombination($op, TaxExceptions::ES_TAX_EXCEPTION_84, 'purchases'));
    }

    public function testReverseChargeInvalid(): void
    {
        $op = InvoiceOperation::REVERSE_CHARGE;
        $this->assertFalse(TaxExceptions::isValidCombination($op, null, 'sales'));
        $this->assertFalse(TaxExceptions::isValidCombination($op, TaxExceptions::ES_TAX_EXCEPTION_20, 'purchases'));
    }

    // isValidCombination: exportación

    public function testExportValidSales(): void
    {
        $this->assertTrue(TaxExceptions::isValidCombination(InvoiceOperation::EXPORT, TaxExceptions::ES_TAX_EXCEPTION_21, 'sales'));
    }

    public function testExportInvalidSales(): void
    {
        $this->assertFalse(TaxExceptions::isValidCombination(InvoiceOperation::EXPORT, null, 'sales'));
        $this->assertFalse(TaxExceptions::isValidCombination(InvoiceOperation::EXPORT, TaxExceptions::ES_TAX_EXCEPTION_20, 'sales'));
    }

    // isValidCombination: importación

    public function testImportValidPurchases(): void
    {
        $this->assertTrue(TaxExceptions::isValidCombination(InvoiceOperation::IMPORT, null, 'purchases'));
    }

    public function testImportInvalidPurchases(): void
    {
        $this->assertFalse(TaxExceptions::isValidCombination(InvoiceOperation::IMPORT, TaxExceptions::ES_TAX_EXCEPTION_20, 'purchases'));
    }

    // isValidCombination: operación desconocida

    public function testUnknownOperationAllowsAnything(): void
    {
        $this->assertTrue(TaxExceptions::isValidCombination('unknown-op', null, 'sales'));
        $this->assertTrue(TaxExceptions::isValidCombination('unknown-op', TaxExceptions::ES_TAX_EXCEPTION_20, 'purchases'));
        $this->assertTrue(TaxExceptions::isValidCombination('unknown-op', TaxExceptions::ES_TAX_EXCEPTION_84, 'sales'));
    }
}
