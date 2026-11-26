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

use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\DataSrc\Divisas;
use FacturaScripts\Core\DataSrc\Retenciones;
use FacturaScripts\Core\Lib\Accounting\AccountingClass;
use FacturaScripts\Core\Lib\Accounting\InvoiceToAccounting;
use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Asiento;
use FacturaScripts\Dinamic\Model\Divisa;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\FacturaProveedor;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Tests de la contabilización de facturas (InvoiceToAccounting), incluida la conversión
 * a la divisa de la empresa de las facturas emitidas o recibidas en otra divisa.
 */
final class InvoiceToAccountingTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    /** Neto de la línea de las facturas en divisa. */
    const FOREIGN_NET = 2442.86;

    /** Importe del suplido de las facturas en divisa. */
    const FOREIGN_SUPPLIED = 100.0;

    /** Tasa de conversión de la divisa de prueba en compras. */
    const PURCHASE_RATE = 0.8521;

    /** Tasa de conversión de la divisa de prueba en ventas. */
    const SALES_RATE = 0.8653;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
        self::loadCoreModels();
        self::installAccountingPlan();
        self::removeTaxRegularization();
    }

    public function testChangeRateRegeneratesEntry(): void
    {
        $currency = $this->createCurrency();

        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save(), 'cant-create-customer');

        $invoice = new FacturaCliente();
        $invoice->setSubject($customer);
        $invoice->setCurrency($currency->coddivisa);
        $this->assertTrue($invoice->save(), 'cant-create-invoice');
        $this->addForeignLines($invoice);
        $this->assertEqualsWithDelta(round($invoice->total / self::SALES_RATE, 2), $invoice->getAccountingEntry()->importe, 0.001, 'bad-entry-amount');

        // al cambiar la tasa de conversión se vuelve a generar el asiento con la nueva tasa
        $invoice->tasaconv = 0.5;
        $this->assertTrue($invoice->save(), 'cant-update-invoice');
        $entry = $invoice->getAccountingEntry();
        $this->assertTrue($entry->exists(), 'accounting-entry-not-found');
        $this->assertEqualsWithDelta(round($invoice->total / 0.5, 2), $entry->importe, 0.001, 'entry-not-regenerated');
        $this->assertTrue($entry->isBalanced(), 'accounting-entry-not-balanced');

        // eliminamos
        $this->assertTrue($invoice->delete(), 'cant-delete-invoice');
        $this->assertTrue($customer->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($customer->delete(), 'cant-delete-customer');
        $this->assertTrue($currency->delete(), 'cant-delete-currency');
    }

    public function testCustomerInvoiceInForeignCurrency(): void
    {
        $currency = $this->createCurrency();

        // cliente, con retención si hay alguna disponible
        $customer = $this->getRandomCustomer();
        foreach (Retenciones::all() as $retention) {
            $customer->codretencion = $retention->codretencion;
            break;
        }
        $this->assertTrue($customer->save(), 'cant-create-customer');

        // factura en la divisa de prueba
        $invoice = new FacturaCliente();
        $invoice->setSubject($customer);
        $invoice->setCurrency($currency->coddivisa);
        $this->assertTrue($invoice->save(), 'cant-create-invoice');
        $this->assertEquals(self::SALES_RATE, $invoice->tasaconv, 'bad-invoice-rate');
        $this->addForeignLines($invoice);
        if (count(Retenciones::all()) > 0) {
            $this->assertGreaterThan(0, $invoice->totalirpf, 'invoice-without-irpf');
        }

        // comprobamos el asiento de la factura
        $entry = $invoice->getAccountingEntry();
        $this->assertTrue($entry->exists(), 'accounting-entry-not-found');
        $this->checkForeignEntry($entry, $invoice, $currency, (string)$invoice->getSubject()->codsubcuenta, true);

        // cobramos el recibo: el asiento del cobro también va en la divisa de la empresa
        $receipts = $invoice->getReceipts();
        $this->assertCount(1, $receipts, 'bad-invoice-receipts-count');
        $receipts[0]->pagado = true;
        $this->assertTrue($receipts[0]->save(), 'cant-pay-receipt');
        $this->checkForeignPayment($receipts[0]->getPayments(), $entry, (string)$invoice->getSubject()->codsubcuenta, false);

        // eliminamos
        $this->assertTrue($invoice->delete(), 'cant-delete-invoice');
        $this->assertTrue($customer->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($customer->delete(), 'cant-delete-customer');
        $this->assertTrue($currency->delete(), 'cant-delete-currency');
    }

    public function testCustomerNotFoundShowsCode(): void
    {
        // factura de un cliente que no existe
        $invoice = new FacturaCliente();
        $invoice->codcliente = 'TESTMISS9';

        $accounting = new InvoiceToAccounting();
        $property = new ReflectionProperty(InvoiceToAccounting::class, 'document');
        $property->setValue($accounting, $invoice);

        MiniLog::clear();
        $method = new ReflectionMethod(InvoiceToAccounting::class, 'addCustomerLine');
        $this->assertFalse($method->invoke($accounting, new Asiento()), 'customer-line-added-without-customer');

        // el aviso indica el código del cliente
        $messages = array_column(MiniLog::read('', ['warning']), 'message');
        $this->assertNotEmpty(array_filter($messages, function (string $message) {
            return str_contains($message, 'TESTMISS9');
        }), 'customer-not-found-without-code');
        MiniLog::clear();
    }

    public function testExchangeRate(): void
    {
        $default = Divisas::default();
        $defaultRate = empty($default->tasaconv) || $default->tasaconv <= 0 ? 1.0 : (float)$default->tasaconv;

        // la divisa de la empresa no se convierte, aunque la tasa del documento sea otra
        $document = new FacturaCliente();
        $document->coddivisa = $default->coddivisa;
        $document->tasaconv = 2.5;
        $this->assertEquals(1.0, AccountingClass::exchangeRate($document), 'default-currency-converted');

        // una tasa vacía, 0 o negativa no convierte (evita la división por cero)
        $document->coddivisa = 'XTS';
        foreach ([null, 0, 0.0, -1.5] as $rate) {
            $document->tasaconv = $rate;
            $this->assertEquals(1.0, AccountingClass::exchangeRate($document), 'bad-rate-converted');
        }

        // sin divisa tampoco se convierte
        $document->coddivisa = null;
        $document->tasaconv = 0.8653;
        $this->assertEquals(1.0, AccountingClass::exchangeRate($document), 'empty-currency-converted');

        // otra divisa: tasa del documento respecto a la de la empresa
        $document->coddivisa = 'XTS';
        $document->tasaconv = 0.8653;
        $this->assertEqualsWithDelta(0.8653 / $defaultRate, AccountingClass::exchangeRate($document), 0.0000001, 'bad-rate');
    }

    public function testSupplierInvoiceInForeignCurrency(): void
    {
        $currency = $this->createCurrency();

        // proveedor, con retención si hay alguna disponible
        $supplier = $this->getRandomSupplier();
        foreach (Retenciones::all() as $retention) {
            $supplier->codretencion = $retention->codretencion;
            break;
        }
        $this->assertTrue($supplier->save(), 'cant-create-supplier');

        // factura en la divisa de prueba, con la tasa de compras
        $invoice = new FacturaProveedor();
        $invoice->setSubject($supplier);
        $invoice->setCurrency($currency->coddivisa, true);
        $this->assertTrue($invoice->save(), 'cant-create-invoice');
        $this->assertEquals(self::PURCHASE_RATE, $invoice->tasaconv, 'bad-invoice-rate');
        $this->addForeignLines($invoice);
        if (count(Retenciones::all()) > 0) {
            $this->assertGreaterThan(0, $invoice->totalirpf, 'invoice-without-irpf');
        }

        // comprobamos el asiento de la factura
        $entry = $invoice->getAccountingEntry();
        $this->assertTrue($entry->exists(), 'accounting-entry-not-found');
        $this->checkForeignEntry($entry, $invoice, $currency, (string)$invoice->getSubject()->codsubcuenta, false);

        // pagamos el recibo: el asiento del pago también va en la divisa de la empresa
        $receipts = $invoice->getReceipts();
        $this->assertCount(1, $receipts, 'bad-invoice-receipts-count');
        $receipts[0]->pagado = true;
        $this->assertTrue($receipts[0]->save(), 'cant-pay-receipt');
        $this->checkForeignPayment($receipts[0]->getPayments(), $entry, (string)$invoice->getSubject()->codsubcuenta, true);

        // eliminamos
        $this->assertTrue($invoice->delete(), 'cant-delete-invoice');
        $this->assertTrue($supplier->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($supplier->delete(), 'cant-delete-supplier');
        $this->assertTrue($currency->delete(), 'cant-delete-currency');
    }

    /**
     * Añade a la factura una línea de producto y otra de suplido y la recalcula, lo que genera el asiento.
     *
     * @param FacturaCliente|FacturaProveedor $invoice
     */
    private function addForeignLines($invoice): void
    {
        $line = $invoice->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = self::FOREIGN_NET;
        $this->assertTrue($line->save(), 'cant-save-line');

        $supplied = $invoice->getNewLine();
        $supplied->cantidad = 1;
        $supplied->pvpunitario = self::FOREIGN_SUPPLIED;
        $supplied->suplido = true;
        $this->assertTrue($supplied->save(), 'cant-save-supplied-line');

        $lines = $invoice->getLines();
        $this->assertTrue(Calculator::calculate($invoice, $lines, true), 'cant-update-invoice');
        $this->assertEqualsWithDelta(self::FOREIGN_NET, $invoice->neto, 0.001, 'bad-invoice-net');
    }

    /**
     * Comprueba que el asiento de una factura en divisa está en la divisa de la empresa:
     * importe del asiento y partida del cliente/proveedor = total / tasaconv, el resto de
     * partidas convertidas con la misma tasa y el asiento cuadrado.
     *
     * @param Asiento $entry
     * @param FacturaCliente|FacturaProveedor $invoice
     * @param Divisa $currency
     * @param string $subjectAccount subcuenta del cliente o del proveedor
     * @param bool $sales
     */
    private function checkForeignEntry(Asiento $entry, $invoice, Divisa $currency, string $subjectAccount, bool $sales): void
    {
        $rate = (float)$invoice->tasaconv;
        $convert = function (float $amount) use ($rate): float {
            return round($amount / $rate, 2);
        };

        // el importe del asiento es el total convertido
        $this->assertEqualsWithDelta($convert($invoice->total), $entry->importe, 0.001, 'bad-entry-amount');

        $debe = 0.0;
        $haber = 0.0;
        $subjectFound = false;
        $taxFound = false;
        $irpf = 0.0;
        foreach ($entry->getLines() as $line) {
            $debe += $line->debe;
            $haber += $line->haber;

            // las partidas indican la divisa y la tasa del documento
            $this->assertEquals($currency->coddivisa, $line->coddivisa, 'bad-line-currency');
            $this->assertEqualsWithDelta($rate, $line->tasaconv, 0.0000001, 'bad-line-rate');

            // ninguna partida lleva importes en la divisa del documento
            $this->assertNotEqualsWithDelta(self::FOREIGN_NET, $line->debe + $line->haber, 0.001, 'line-not-converted');

            // partida del cliente (debe) o del proveedor (haber): total convertido exacto
            if ($line->codsubcuenta === $subjectAccount) {
                $subjectFound = true;
                $amount = $sales ? $line->debe : $line->haber;
                $this->assertEqualsWithDelta($convert($invoice->total), $amount, 0.001, 'bad-subject-line');
                continue;
            }

            // partida de IVA: cuota y base convertidas
            if ($line->iva > 0) {
                $taxFound = true;
                $amount = $sales ? $line->haber : $line->debe;
                $this->assertEqualsWithDelta($convert($invoice->totaliva), $amount, 0.001, 'bad-tax-line');
                $this->assertEqualsWithDelta($convert($invoice->neto), $line->baseimponible, 0.001, 'bad-tax-base');
                continue;
            }

            // partidas de IRPF: en ventas van al debe, en compras al haber, y no son del cliente/proveedor
            if (substr($line->codsubcuenta, 0, 3) === '473' || substr($line->codsubcuenta, 0, 4) === '4751') {
                $irpf += $sales ? $line->debe : $line->haber;
            }
        }

        $this->assertTrue($subjectFound, 'subject-line-not-found');
        $this->assertTrue($taxFound, 'tax-line-not-found');
        $this->assertEqualsWithDelta($convert($invoice->totalirpf), $irpf, 0.001, 'bad-irpf-lines');

        // el asiento cuadra y sus sumas son las del documento convertido
        $this->assertEqualsWithDelta($debe, $haber, 0.001, 'unbalanced-entry');
        $this->assertTrue($entry->isBalanced(), 'accounting-entry-not-balanced');
        $expected = $convert($invoice->total) + $convert($invoice->totalirpf);
        $this->assertEqualsWithDelta($expected, $debe, 0.011, 'bad-entry-sum');
    }

    /**
     * Comprueba que el asiento del pago de un recibo en divisa está en la divisa de la empresa
     * y que salda la subcuenta del cliente/proveedor por el mismo importe que el asiento de la factura.
     *
     * @param array $payments pagos del recibo
     * @param Asiento $invoiceEntry asiento de la factura
     * @param string $subjectAccount subcuenta del cliente o del proveedor
     * @param bool $subjectDebit true si el pago carga la subcuenta del sujeto (proveedor)
     */
    private function checkForeignPayment(array $payments, Asiento $invoiceEntry, string $subjectAccount, bool $subjectDebit): void
    {
        $this->assertCount(1, $payments, 'should-have-one-payment');
        $this->assertNotEmpty($payments[0]->idasiento, 'payment-should-have-accounting-entry');

        $entry = new Asiento();
        $this->assertTrue($entry->load($payments[0]->idasiento), 'cant-load-payment-entry');
        $this->assertTrue($entry->isBalanced(), 'payment-entry-not-balanced');
        $this->assertEqualsWithDelta($invoiceEntry->importe, $entry->importe, 0.001, 'bad-payment-entry-amount');

        // la partida del sujeto en el pago salda la de la factura
        $invoiceAmount = $this->getSubjectNet($invoiceEntry, $subjectAccount);
        $paymentAmount = $this->getSubjectNet($entry, $subjectAccount);
        $this->assertEqualsWithDelta(0.0, $invoiceAmount + $paymentAmount, 0.001, 'subject-account-not-settled');
        $this->assertSame($subjectDebit, $paymentAmount > 0, 'bad-payment-side');
    }

    /**
     * Crea una divisa de prueba con tasas distintas para ventas y compras.
     *
     * @return Divisa
     */
    private function createCurrency(): Divisa
    {
        $currency = new Divisa();
        $currency->coddivisa = 'T' . mt_rand(10, 99);
        $currency->descripcion = 'Test currency';
        $currency->simbolo = 'T$';
        $currency->tasaconv = self::SALES_RATE;
        $currency->tasaconvcompra = self::PURCHASE_RATE;
        $this->assertTrue($currency->save(), 'cant-create-currency');
        return $currency;
    }

    /**
     * Devuelve el saldo (debe - haber) de una subcuenta en un asiento.
     *
     * @param Asiento $entry
     * @param string $codsubcuenta
     *
     * @return float
     */
    private function getSubjectNet(Asiento $entry, string $codsubcuenta): float
    {
        $net = 0.0;
        foreach ($entry->getLines() as $line) {
            if ($line->codsubcuenta === $codsubcuenta) {
                $net += $line->debe - $line->haber;
            }
        }

        return Tools::round($net);
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
