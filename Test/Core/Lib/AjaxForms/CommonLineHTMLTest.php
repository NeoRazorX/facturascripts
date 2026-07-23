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

namespace FacturaScripts\Test\Core\Lib\AjaxForms;

use FacturaScripts\Core\Lib\AjaxForms\PurchasesLineHTML;
use FacturaScripts\Core\Lib\AjaxForms\SalesLineHTML;
use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Core\Model\PresupuestoCliente;
use FacturaScripts\Core\Model\PresupuestoProveedor;
use PHPUnit\Framework\TestCase;

final class CommonLineHTMLTest extends TestCase
{
    public function testSalesSubtotalIncludesGlobalDiscount(): void
    {
        $document = new PresupuestoCliente();

        $this->assertSubtotalIncludesGlobalDiscount(
            $document,
            SalesLineHTML::class
        );
    }

    public function testPurchasesSubtotalIncludesGlobalDiscount(): void
    {
        $document = new PresupuestoProveedor();

        $this->assertSubtotalIncludesGlobalDiscount(
            $document,
            PurchasesLineHTML::class
        );
    }

    private function assertSubtotalIncludesGlobalDiscount(
        BusinessDocument $document,
        string $lineHtmlClass
    ): void {
        $document->dtopor1 = 10;
        $document->dtopor2 = 20;

        $line = $document->getNewLine();
        $line->idlinea = 1;
        $line->cantidad = 1;
        $line->pvpunitario = 100;
        $line->iva = 21;

        $lines = [$line];
        $this->assertTrue(Calculator::calculate($document, $lines, false));

        $map = $lineHtmlClass::map($lines, $document);

        // 100 × 0,90 × 0,80 × 1,21 = 87,12
        $this->assertEquals(87.12, $map['linetotal_1']);
        $this->assertEquals($document->total, $map['linetotal_1']);
    }
}
