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

use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Lib\Export\PDFExport;
use FacturaScripts\Core\Model\PresupuestoCliente;
use FacturaScripts\Core\Model\Retencion;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class PDFDocumentTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
    }

    /**
     * Comprueba que dos líneas con el mismo porcentaje de IRPF pero con retenciones
     * distintas (codretencion) generan dos filas de subtotal distintas en el PDF,
     * en lugar de mezclarse en una sola fila.
     */
    public function testTaxesRowsGroupByRetentionNotByPercentage(): void
    {
        // creamos dos retenciones con el mismo porcentaje pero distinto código
        $retentionA = new Retencion();
        $retentionA->codretencion = 'A' . Tools::randomString(4);
        $retentionA->descripcion = 'Retención A ' . Tools::randomString(4);
        $retentionA->porcentaje = 4;
        $this->assertTrue($retentionA->save(), 'retention-a-cant-save');

        $retentionB = new Retencion();
        $retentionB->codretencion = 'B' . Tools::randomString(4);
        $retentionB->descripcion = 'Retención B ' . Tools::randomString(4);
        $retentionB->porcentaje = 4;
        $this->assertTrue($retentionB->save(), 'retention-b-cant-save');

        // creamos el documento con dos líneas, una para cada retención
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'customer-cant-save');

        $doc = new PresupuestoCliente();
        $this->assertTrue($doc->setSubject($subject), 'document-cant-set-subject');
        $this->assertTrue($doc->save(), 'document-cant-save');

        $firstLine = $doc->getNewLine();
        $firstLine->cantidad = 1;
        $firstLine->descripcion = 'Linea con retención A';
        $firstLine->pvpunitario = 100;
        $firstLine->setRetention($retentionA->codretencion);
        $this->assertTrue($firstLine->save(), 'first-line-cant-save');

        $secondLine = $doc->getNewLine();
        $secondLine->cantidad = 1;
        $secondLine->descripcion = 'Linea con retención B';
        $secondLine->pvpunitario = 200;
        $secondLine->setRetention($retentionB->codretencion);
        $this->assertTrue($secondLine->save(), 'second-line-cant-save');

        $lines = $doc->getLines();
        $this->assertTrue(Calculator::calculate($doc, $lines, true), 'document-cant-calculate');

        // invocamos el método protegido getTaxesRows() mediante reflection
        $pdfExport = new PDFExport();
        $method = new ReflectionMethod($pdfExport, 'getTaxesRows');
        $rows = $method->invoke($pdfExport, $doc);

        // deben existir dos filas de irpf distintas, una por cada retención
        $irpfRows = [];
        foreach ($rows as $key => $row) {
            if (str_starts_with($key, 'irpf_')) {
                $irpfRows[$key] = $row;
            }
        }
        $this->assertCount(2, $irpfRows, 'retentions-with-same-percentage-were-merged');

        // las etiquetas deben corresponder a la descripción de cada retención,
        // no al texto genérico "irpf 4%"
        $labels = array_column($irpfRows, 'tax');
        $this->assertContains($retentionA->descripcion, $labels, 'missing-retention-a-label');
        $this->assertContains($retentionB->descripcion, $labels, 'missing-retention-b-label');
        foreach ($labels as $label) {
            $this->assertStringNotContainsString('%', $label, 'label-should-not-be-generic-percentage-text');
        }

        // limpiamos
        $this->assertTrue($doc->delete(), 'document-cant-delete');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'address-cant-delete');
        $this->assertTrue($subject->delete(), 'customer-cant-delete');
        $this->assertTrue($retentionA->delete(), 'retention-a-cant-delete');
        $this->assertTrue($retentionB->delete(), 'retention-b-cant-delete');
    }
}
