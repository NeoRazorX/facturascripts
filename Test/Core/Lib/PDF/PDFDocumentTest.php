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

namespace FacturaScripts\Test\Core\Lib\PDF;

use FacturaScripts\Core\Lib\PDF\PDFDocument;
use FacturaScripts\Core\Model\CuentaBanco;
use FacturaScripts\Core\Model\FacturaCliente;
use FacturaScripts\Core\Model\FormaPago;
use FacturaScripts\Core\Model\ReciboCliente;
use FacturaScripts\Core\Response;
use PHPUnit\Framework\TestCase;

final class PDFDocumentTest extends TestCase
{
    public function testInvoiceBankDataUsesPaymentMethodBankAccount(): void
    {
        $bank = $this->getTestBankAccount('PDF invoice bank', 'ES9121000418450200051332');
        $payMethod = $this->getTestPaymentMethod('PDF invoice payment', $bank);

        $invoice = new FacturaCliente();
        $invoice->codcliente = '1';
        $invoice->codpago = $payMethod->codpago;

        $pdf = new TestPDFDocument();
        $this->assertStringContainsString($bank->getIban(true), $pdf->bankData($invoice));

        $this->assertTrue($payMethod->delete(), 'payment-method-cant-delete');
        $this->assertTrue($bank->delete(), 'bank-cant-delete');
    }

    public function testReceiptBankDataPrefersReceiptBankAccount(): void
    {
        $defaultBank = $this->getTestBankAccount('PDF default bank', 'ES7921000813610123456789');
        $receiptBank = $this->getTestBankAccount('PDF receipt bank', 'PT50002700000001234567833');
        $payMethod = $this->getTestPaymentMethod('PDF receipt payment', $defaultBank);

        $receipt = new ReciboCliente();
        $receipt->codcliente = '1';
        $receipt->codcuentabanco = $receiptBank->codcuenta;
        $receipt->codpago = $payMethod->codpago;

        $pdf = new TestPDFDocument();
        $bankData = $pdf->bankData($receipt);
        $this->assertStringContainsString($receiptBank->getIban(true), $bankData);
        $this->assertStringNotContainsString($defaultBank->getIban(true), $bankData);

        $this->assertTrue($payMethod->delete(), 'payment-method-cant-delete');
        $this->assertTrue($receiptBank->delete(), 'receipt-bank-cant-delete');
        $this->assertTrue($defaultBank->delete(), 'default-bank-cant-delete');
    }

    private function getTestBankAccount(string $description, string $iban): CuentaBanco
    {
        $bank = new CuentaBanco();
        $bank->codcuenta = (string)mt_rand(100000, 999999);
        $bank->descripcion = $description;
        $bank->iban = $iban;
        $this->assertTrue($bank->save(), 'bank-cant-save');

        return $bank;
    }

    private function getTestPaymentMethod(string $description, CuentaBanco $bank): FormaPago
    {
        $payMethod = new FormaPago();
        $payMethod->codcuentabanco = $bank->codcuenta;
        $payMethod->descripcion = $description;
        $payMethod->domiciliado = false;
        $payMethod->imprimir = true;
        $payMethod->pagado = false;
        $payMethod->plazovencimiento = 0;
        $payMethod->tipovencimiento = 'days';
        $this->assertTrue($payMethod->save(), 'payment-method-cant-save');

        return $payMethod;
    }
}

final class TestPDFDocument extends PDFDocument
{
    public function addBusinessDocPage($model): bool
    {
        return true;
    }

    public function addListModelPage($model, $where, $order, $offset, $columns, $title = ''): bool
    {
        return true;
    }

    public function addModelPage($model, $columns, $title = ''): bool
    {
        return true;
    }

    public function addTablePage($headers, $rows, $options = [], $title = ''): bool
    {
        return true;
    }

    public function bankData($receipt): string
    {
        return $this->getBankData($receipt);
    }

    public function getDoc()
    {
        return '';
    }

    public function newDoc(string $title, int $idformat, string $langcode)
    {
    }

    public function show(Response &$response)
    {
    }
}
