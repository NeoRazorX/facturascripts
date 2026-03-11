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

        $this->assertArrayHasKey(TaxExceptions::ES_TAX_EXCEPTION_E1, $all);
        $this->assertArrayHasKey(TaxExceptions::ES_TAX_EXCEPTION_E2, $all);
        $this->assertArrayHasKey(TaxExceptions::ES_TAX_EXCEPTION_E3, $all);
        $this->assertArrayHasKey(TaxExceptions::ES_TAX_EXCEPTION_E4, $all);
        $this->assertArrayHasKey(TaxExceptions::ES_TAX_EXCEPTION_E5, $all);
        $this->assertArrayHasKey(TaxExceptions::ES_TAX_EXCEPTION_E6, $all);
        $this->assertArrayHasKey(TaxExceptions::ES_TAX_EXCEPTION_N1, $all);
        $this->assertArrayHasKey(TaxExceptions::ES_TAX_EXCEPTION_N2, $all);
        $this->assertArrayHasKey(TaxExceptions::ES_TAX_EXCEPTION_PASSIVE_SUBJECT, $all);
        $this->assertCount(9, $all);
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
        $original = TaxExceptions::all()[TaxExceptions::ES_TAX_EXCEPTION_E1];

        TaxExceptions::add(TaxExceptions::ES_TAX_EXCEPTION_E1, 'new-label');
        $this->assertEquals('new-label', TaxExceptions::all()[TaxExceptions::ES_TAX_EXCEPTION_E1]);

        // restauramos
        TaxExceptions::add(TaxExceptions::ES_TAX_EXCEPTION_E1, $original);
    }

    public function testRemoveDefaultException(): void
    {
        TaxExceptions::remove(TaxExceptions::ES_TAX_EXCEPTION_E6);
        $this->assertArrayNotHasKey(TaxExceptions::ES_TAX_EXCEPTION_E6, TaxExceptions::all());

        // restauramos
        TaxExceptions::add(TaxExceptions::ES_TAX_EXCEPTION_E6, 'es-tax-exception-e6');
        $this->assertArrayHasKey(TaxExceptions::ES_TAX_EXCEPTION_E6, TaxExceptions::all());
    }

    public function testRemoveAndReAdd(): void
    {
        TaxExceptions::remove(TaxExceptions::ES_TAX_EXCEPTION_N1);
        $this->assertArrayNotHasKey(TaxExceptions::ES_TAX_EXCEPTION_N1, TaxExceptions::all());

        TaxExceptions::add(TaxExceptions::ES_TAX_EXCEPTION_N1, 'es-tax-exception-n1');
        $this->assertArrayHasKey(TaxExceptions::ES_TAX_EXCEPTION_N1, TaxExceptions::all());
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
        $this->assertTrue(TaxExceptions::isValidCombination(null, TaxExceptions::ES_TAX_EXCEPTION_E1, 'sales'));
        $this->assertTrue(TaxExceptions::isValidCombination(null, TaxExceptions::ES_TAX_EXCEPTION_E6, 'sales'));
        $this->assertTrue(TaxExceptions::isValidCombination(null, TaxExceptions::ES_TAX_EXCEPTION_N1, 'purchases'));
        $this->assertTrue(TaxExceptions::isValidCombination(null, TaxExceptions::ES_TAX_EXCEPTION_N2, 'purchases'));
        $this->assertTrue(TaxExceptions::isValidCombination(null, TaxExceptions::ES_TAX_EXCEPTION_PASSIVE_SUBJECT, 'sales'));
    }

    public function testNoOperationRejectsSpecificExceptions(): void
    {
        $this->assertFalse(TaxExceptions::isValidCombination(null, TaxExceptions::ES_TAX_EXCEPTION_E2, 'sales'));
        $this->assertFalse(TaxExceptions::isValidCombination(null, TaxExceptions::ES_TAX_EXCEPTION_E3, 'purchases'));
        $this->assertFalse(TaxExceptions::isValidCombination(null, TaxExceptions::ES_TAX_EXCEPTION_E4, 'sales'));
        $this->assertFalse(TaxExceptions::isValidCombination(null, TaxExceptions::ES_TAX_EXCEPTION_E5, 'purchases'));
    }

    // isValidCombination: intracomunitaria

    public function testIntraCommunityValidSales(): void
    {
        $op = InvoiceOperation::INTRA_COMMUNITY;
        $this->assertTrue(TaxExceptions::isValidCombination($op, TaxExceptions::ES_TAX_EXCEPTION_E3, 'sales'));
        $this->assertTrue(TaxExceptions::isValidCombination($op, TaxExceptions::ES_TAX_EXCEPTION_E4, 'sales'));
        $this->assertTrue(TaxExceptions::isValidCombination($op, TaxExceptions::ES_TAX_EXCEPTION_E5, 'sales'));
        $this->assertTrue(TaxExceptions::isValidCombination($op, TaxExceptions::ES_TAX_EXCEPTION_N2, 'sales'));
    }

    public function testIntraCommunityInvalidSales(): void
    {
        $op = InvoiceOperation::INTRA_COMMUNITY;
        $this->assertFalse(TaxExceptions::isValidCombination($op, TaxExceptions::ES_TAX_EXCEPTION_E1, 'sales'));
        $this->assertFalse(TaxExceptions::isValidCombination($op, TaxExceptions::ES_TAX_EXCEPTION_PASSIVE_SUBJECT, 'sales'));
        $this->assertFalse(TaxExceptions::isValidCombination($op, null, 'sales'));
    }

    public function testIntraCommunityValidPurchases(): void
    {
        $op = InvoiceOperation::INTRA_COMMUNITY;
        $this->assertTrue(TaxExceptions::isValidCombination($op, TaxExceptions::ES_TAX_EXCEPTION_PASSIVE_SUBJECT, 'purchases'));
        $this->assertTrue(TaxExceptions::isValidCombination($op, TaxExceptions::ES_TAX_EXCEPTION_N1, 'purchases'));
        $this->assertTrue(TaxExceptions::isValidCombination($op, TaxExceptions::ES_TAX_EXCEPTION_N2, 'purchases'));
    }

    public function testIntraCommunityInvalidPurchases(): void
    {
        $op = InvoiceOperation::INTRA_COMMUNITY;
        $this->assertFalse(TaxExceptions::isValidCombination($op, TaxExceptions::ES_TAX_EXCEPTION_E5, 'purchases'));
        $this->assertFalse(TaxExceptions::isValidCombination($op, null, 'purchases'));
    }

    // isValidCombination: intracomunitaria servicios

    public function testIntraCommunityServicesValid(): void
    {
        $op = InvoiceOperation::INTRA_COMMUNITY_SERVICES;
        $this->assertTrue(TaxExceptions::isValidCombination($op, TaxExceptions::ES_TAX_EXCEPTION_N2, 'sales'));
        $this->assertTrue(TaxExceptions::isValidCombination($op, TaxExceptions::ES_TAX_EXCEPTION_PASSIVE_SUBJECT, 'purchases'));
    }

    public function testIntraCommunityServicesInvalid(): void
    {
        $op = InvoiceOperation::INTRA_COMMUNITY_SERVICES;
        $this->assertFalse(TaxExceptions::isValidCombination($op, TaxExceptions::ES_TAX_EXCEPTION_E1, 'sales'));
        $this->assertFalse(TaxExceptions::isValidCombination($op, null, 'sales'));
        $this->assertFalse(TaxExceptions::isValidCombination($op, TaxExceptions::ES_TAX_EXCEPTION_N2, 'purchases'));
        $this->assertFalse(TaxExceptions::isValidCombination($op, null, 'purchases'));
    }

    // isValidCombination: inversión sujeto pasivo

    public function testReverseChargeValid(): void
    {
        $op = InvoiceOperation::REVERSE_CHARGE;
        $this->assertTrue(TaxExceptions::isValidCombination($op, TaxExceptions::ES_TAX_EXCEPTION_PASSIVE_SUBJECT, 'sales'));
        $this->assertTrue(TaxExceptions::isValidCombination($op, TaxExceptions::ES_TAX_EXCEPTION_PASSIVE_SUBJECT, 'purchases'));
    }

    public function testReverseChargeInvalid(): void
    {
        $op = InvoiceOperation::REVERSE_CHARGE;
        $this->assertFalse(TaxExceptions::isValidCombination($op, null, 'sales'));
        $this->assertFalse(TaxExceptions::isValidCombination($op, TaxExceptions::ES_TAX_EXCEPTION_E1, 'purchases'));
    }

    // isValidCombination: exportación

    public function testExportValidSales(): void
    {
        $this->assertTrue(TaxExceptions::isValidCombination(InvoiceOperation::EXPORT, TaxExceptions::ES_TAX_EXCEPTION_E2, 'sales'));
    }

    public function testExportInvalidSales(): void
    {
        $this->assertFalse(TaxExceptions::isValidCombination(InvoiceOperation::EXPORT, null, 'sales'));
        $this->assertFalse(TaxExceptions::isValidCombination(InvoiceOperation::EXPORT, TaxExceptions::ES_TAX_EXCEPTION_E1, 'sales'));
    }

    // isValidCombination: importación

    public function testImportValidPurchases(): void
    {
        $this->assertTrue(TaxExceptions::isValidCombination(InvoiceOperation::IMPORT, null, 'purchases'));
    }

    public function testImportInvalidPurchases(): void
    {
        $this->assertFalse(TaxExceptions::isValidCombination(InvoiceOperation::IMPORT, TaxExceptions::ES_TAX_EXCEPTION_E1, 'purchases'));
    }

    // isValidCombination: operación desconocida

    public function testUnknownOperationAllowsAnything(): void
    {
        $this->assertTrue(TaxExceptions::isValidCombination('unknown-op', null, 'sales'));
        $this->assertTrue(TaxExceptions::isValidCombination('unknown-op', TaxExceptions::ES_TAX_EXCEPTION_E1, 'purchases'));
        $this->assertTrue(TaxExceptions::isValidCombination('unknown-op', TaxExceptions::ES_TAX_EXCEPTION_PASSIVE_SUBJECT, 'sales'));
    }
}
