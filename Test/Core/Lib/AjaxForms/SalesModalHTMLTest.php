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

use FacturaScripts\Core\Lib\AjaxForms\SalesModalHTML;
use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Model\Cliente;
use FacturaScripts\Core\Model\FacturaCliente;
use FacturaScripts\Core\Model\PresupuestoCliente;
use FacturaScripts\Core\Model\Producto;
use FacturaScripts\Core\Model\Stock;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class SalesModalHTMLTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    /** @var Cliente */
    private $customer;

    /** @var FacturaCliente[] */
    private $invoices = [];

    /** @var Producto */
    private $product;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
        self::installAccountingPlan();
        self::removeTaxRegularization();

        // la consulta del modal necesita la tabla de stocks
        new Stock();
    }

    protected function setUp(): void
    {
        $this->customer = $this->getRandomCustomer();
        $this->assertTrue($this->customer->save(), 'customer-cant-save');

        $this->product = $this->getRandomProduct();
        $this->assertTrue($this->product->save(), 'product-cant-save');

        $this->invoices = [];
    }

    public function testEmptyListSpansAllColumns(): void
    {
        $html = $this->findProducts('no-existe-' . mt_rand(1, 99999), false);
        $this->assertStringContainsString('<td colspan="3">', $html);

        // con «vendido anteriormente» se añade la columna del último precio
        $html = $this->findProducts('no-existe-' . mt_rand(1, 99999), true);
        $this->assertStringContainsString('<td colspan="4">', $html);
    }

    public function testLastPriceUsesLatestInvoiceOfTheDay(): void
    {
        $date = Tools::date();
        $this->createInvoice($date, '09:00:00', 10);
        $this->createInvoice($date, '18:00:00', 20);

        $html = $this->findProducts($this->product->referencia, true);
        $lastPrice = '<td class="text-end">' . str_replace(' ', '&nbsp;', Tools::money(20)) . '</td>';
        $this->assertStringContainsString($lastPrice, $html);
    }

    protected function tearDown(): void
    {
        foreach (array_reverse($this->invoices) as $invoice) {
            $invoice->delete();
        }

        $this->product->delete();
        $this->customer->getDefaultAddress()->delete();
        $this->customer->delete();
        $this->logErrors();
    }

    private function createInvoice(string $date, string $hour, float $price): void
    {
        $invoice = new FacturaCliente();
        $invoice->setSubject($this->customer);
        $this->assertTrue($invoice->setDate($date, $hour), 'invoice-cant-set-date');
        $this->assertTrue($invoice->save(), 'invoice-cant-save');
        $this->invoices[] = $invoice;

        $line = $invoice->getNewProductLine($this->product->referencia);
        $line->cantidad = 1;
        $line->pvpunitario = $price;
        $this->assertTrue($line->save(), 'invoice-line-cant-save');

        $lines = $invoice->getLines();
        $this->assertTrue(Calculator::calculate($invoice, $lines, true), 'invoice-cant-calculate');
    }

    private function findProducts(string $query, bool $vendido): string
    {
        $doc = new PresupuestoCliente();
        $doc->setSubject($this->customer);

        SalesModalHTML::apply($doc, ['fp_query' => $query, 'fp_vendido' => $vendido]);
        return SalesModalHTML::renderProductList();
    }
}
