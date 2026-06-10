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
use PHPUnit\Framework\TestCase;

final class InvoiceOperationTest extends TestCase
{
    public function testAllReturnsDefaults(): void
    {
        $all = InvoiceOperation::all();

        $this->assertArrayHasKey(InvoiceOperation::EXPORT, $all);
        $this->assertArrayHasKey(InvoiceOperation::IMPORT, $all);
        $this->assertArrayHasKey(InvoiceOperation::INTRA_COMMUNITY, $all);
        $this->assertArrayHasKey(InvoiceOperation::INTRA_COMMUNITY_SERVICES, $all);
        $this->assertArrayHasKey(InvoiceOperation::REVERSE_CHARGE, $all);
        $this->assertArrayHasKey(InvoiceOperation::SUCCESSIVE_TRACT, $all);
        $this->assertArrayHasKey(InvoiceOperation::WORK_CERTIFICATION, $all);
    }

    public function testSalesExcludesImport(): void
    {
        $sales = InvoiceOperation::allForSales();

        $this->assertArrayHasKey(InvoiceOperation::EXPORT, $sales);
        $this->assertArrayHasKey(InvoiceOperation::INTRA_COMMUNITY, $sales);
        $this->assertArrayHasKey(InvoiceOperation::INTRA_COMMUNITY_SERVICES, $sales);
        $this->assertArrayHasKey(InvoiceOperation::REVERSE_CHARGE, $sales);
        $this->assertArrayHasKey(InvoiceOperation::SUCCESSIVE_TRACT, $sales);
        $this->assertArrayHasKey(InvoiceOperation::WORK_CERTIFICATION, $sales);

        $this->assertArrayNotHasKey(InvoiceOperation::IMPORT, $sales);
    }

    public function testPurchasesExcludesSalesOnly(): void
    {
        $purchases = InvoiceOperation::allForPurchases();

        $this->assertArrayHasKey(InvoiceOperation::IMPORT, $purchases);
        $this->assertArrayHasKey(InvoiceOperation::INTRA_COMMUNITY, $purchases);
        $this->assertArrayHasKey(InvoiceOperation::INTRA_COMMUNITY_SERVICES, $purchases);
        $this->assertArrayHasKey(InvoiceOperation::REVERSE_CHARGE, $purchases);

        $this->assertArrayNotHasKey(InvoiceOperation::EXPORT, $purchases);
        $this->assertArrayNotHasKey(InvoiceOperation::SUCCESSIVE_TRACT, $purchases);
        $this->assertArrayNotHasKey(InvoiceOperation::WORK_CERTIFICATION, $purchases);
    }

    public function testAddCustomOperationForBoth(): void
    {
        InvoiceOperation::add('custom-both', 'custom-both-label');

        $this->assertArrayHasKey('custom-both', InvoiceOperation::all());
        $this->assertArrayHasKey('custom-both', InvoiceOperation::allForSales());
        $this->assertArrayHasKey('custom-both', InvoiceOperation::allForPurchases());

        // limpiamos
        InvoiceOperation::remove('custom-both');
    }

    public function testAddCustomOperationForSalesOnly(): void
    {
        InvoiceOperation::add('custom-sale', 'custom-sale-label', InvoiceOperation::TYPE_SALE);

        $this->assertArrayHasKey('custom-sale', InvoiceOperation::all());
        $this->assertArrayHasKey('custom-sale', InvoiceOperation::allForSales());
        $this->assertArrayNotHasKey('custom-sale', InvoiceOperation::allForPurchases());

        // limpiamos
        InvoiceOperation::remove('custom-sale');
    }

    public function testAddCustomOperationForPurchasesOnly(): void
    {
        InvoiceOperation::add('custom-purchase', 'custom-purchase-label', InvoiceOperation::TYPE_PURCHASE);

        $this->assertArrayHasKey('custom-purchase', InvoiceOperation::all());
        $this->assertArrayNotHasKey('custom-purchase', InvoiceOperation::allForSales());
        $this->assertArrayHasKey('custom-purchase', InvoiceOperation::allForPurchases());

        // limpiamos
        InvoiceOperation::remove('custom-purchase');
    }

    public function testRemoveOperation(): void
    {
        InvoiceOperation::remove(InvoiceOperation::EXPORT);

        $this->assertArrayNotHasKey(InvoiceOperation::EXPORT, InvoiceOperation::all());
        $this->assertArrayNotHasKey(InvoiceOperation::EXPORT, InvoiceOperation::allForSales());

        // restauramos
        InvoiceOperation::add(InvoiceOperation::EXPORT, 'operation-export');
    }

    public function testAddKeyIsTruncatedTo20Chars(): void
    {
        $longKey = 'this-key-is-way-too-long-for-the-limit';
        InvoiceOperation::add($longKey, 'long-label');

        $truncated = substr($longKey, 0, 20);
        $this->assertArrayHasKey($truncated, InvoiceOperation::all());

        // limpiamos
        InvoiceOperation::remove($longKey);
    }
}
