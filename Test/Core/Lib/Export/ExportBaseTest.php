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

namespace FacturaScripts\Test\Core\Lib\Export;

use FacturaScripts\Core\Lib\Export\ExportBase;
use FacturaScripts\Core\Model\FormatoDocumento;
use FacturaScripts\Core\Response;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class ExportBaseTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

    public function testReturnsMostSpecificFormat(): void
    {
        // creamos una factura (trae idempresa y codserie)
        $invoice = $this->getRandomCustomerInvoice();
        $this->assertTrue($invoice->exists());

        // formato totalmente genérico (tipodoc y codserie nulos)
        $generic = $this->createFormat($invoice->idempresa, null, null);

        // formato por tipo de documento (sin serie)
        $byType = $this->createFormat($invoice->idempresa, 'FacturaCliente', null);

        // formato específico para este tipo y serie
        $specific = $this->createFormat($invoice->idempresa, 'FacturaCliente', $invoice->codserie);

        // debe devolver el más específico, sea cual sea el orden de los NULL en la BD
        $found = $this->exporter()->getDocumentFormat($invoice);
        $this->assertEquals($specific->id, $found->id);

        // limpieza
        $this->assertTrue($specific->delete());
        $this->assertTrue($byType->delete());
        $this->assertTrue($generic->delete());
        $this->deleteInvoice($invoice);
    }

    public function testSpecificWinsRegardlessOfInsertionOrder(): void
    {
        $invoice = $this->getRandomCustomerInvoice();
        $this->assertTrue($invoice->exists());

        // insertamos primero el específico y después los genéricos, para dejar
        // explícito que el resultado no depende del orden de inserción ni del
        // orden de los NULL en la BD (MySQL vs PostgreSQL)
        $specific = $this->createFormat($invoice->idempresa, 'FacturaCliente', $invoice->codserie);
        $byType = $this->createFormat($invoice->idempresa, 'FacturaCliente', null);
        $generic = $this->createFormat($invoice->idempresa, null, null);

        $found = $this->exporter()->getDocumentFormat($invoice);
        $this->assertEquals($specific->id, $found->id);

        $this->assertTrue($specific->delete());
        $this->assertTrue($byType->delete());
        $this->assertTrue($generic->delete());
        $this->deleteInvoice($invoice);
    }

    public function testFallsBackToTypeWhenNoSerieMatch(): void
    {
        $invoice = $this->getRandomCustomerInvoice();
        $this->assertTrue($invoice->exists());

        // solo hay genérico y por tipo: debe ganar el de tipo (más específico)
        $generic = $this->createFormat($invoice->idempresa, null, null);
        $byType = $this->createFormat($invoice->idempresa, 'FacturaCliente', null);

        $found = $this->exporter()->getDocumentFormat($invoice);
        $this->assertEquals($byType->id, $found->id);

        $this->assertTrue($byType->delete());
        $this->assertTrue($generic->delete());
        $this->deleteInvoice($invoice);
    }

    public function testReturnsEmptyFormatWhenNoneMatch(): void
    {
        $invoice = $this->getRandomCustomerInvoice();
        $this->assertTrue($invoice->exists());

        // sin formatos aplicables devuelve un FormatoDocumento vacío (sin id)
        $found = $this->exporter()->getDocumentFormat($invoice);
        $this->assertNull($found->id);

        $this->deleteInvoice($invoice);
    }

    private function createFormat(int $idempresa, ?string $tipodoc, ?string $codserie): FormatoDocumento
    {
        $format = new FormatoDocumento();
        $format->nombre = 'Test ' . ($tipodoc ?? 'null') . '-' . ($codserie ?? 'null');
        $format->titulo = 'Test';
        $format->texto = 'Test';
        $format->idempresa = $idempresa;
        $format->tipodoc = $tipodoc;
        $format->codserie = $codserie;
        $format->autoaplicar = true;
        $this->assertTrue($format->save());

        return $format;
    }

    private function deleteInvoice($invoice): void
    {
        $customer = $invoice->getSubject();
        foreach ($invoice->getLines() as $line) {
            $line->delete();
        }
        $invoice->delete();
        $customer->delete();
    }

    private function exporter(): ExportBase
    {
        return new class extends ExportBase {
            public function getDocumentFormat($model)
            {
                return parent::getDocumentFormat($model);
            }

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

            public function getDoc()
            {
                return '';
            }

            public function newDoc(string $title, int $idformat, string $langcode)
            {
            }

            public function setOrientation(string $orientation)
            {
            }

            public function show(Response &$response)
            {
            }
        };
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
