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

namespace FacturaScripts\Test\Core\Lib\Widget;

use FacturaScripts\Core\Lib\Widget\WidgetSubcuenta;
use FacturaScripts\Core\Model\Ejercicio;
use FacturaScripts\Core\Model\Empresa;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class WidgetSubcuentaTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

    public function testExerciseFilterIsGroupedByCompany(): void
    {
        // creamos dos empresas con un ejercicio cada una
        $empresa1 = $this->getRandomCompany();
        $empresa1->nombrecorto = 'WS-Company-1';
        $this->assertTrue($empresa1->save());
        $ejercicio1 = $this->createExercise($empresa1, 'WS-1');

        $empresa2 = $this->getRandomCompany();
        $empresa2->nombrecorto = 'WS-Company-2';
        $this->assertTrue($empresa2->save());
        $ejercicio2 = $this->createExercise($empresa2, 'WS-2');

        $widget = new WidgetSubcuenta([
            'children' => [],
            'fieldname' => 'test',
            'type' => 'subcuenta',
        ]);

        // sin empresa en el modelo, los ejercicios se agrupan por empresa
        $html = $widget->edit((object)['test' => '']);
        $this->assertMatchesRegularExpression(
            '/<optgroup label="WS-Company-1"><option value="WS-1"[^>]*>exercise-WS-1<\/option><\/optgroup>/',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/<optgroup label="WS-Company-2"><option value="WS-2"[^>]*>exercise-WS-2<\/option><\/optgroup>/',
            $html
        );

        // con empresa en el modelo, solo se muestran sus ejercicios y sin agrupar
        $html = $widget->edit((object)['test' => '', 'idempresa' => $empresa2->idempresa]);
        $this->assertStringNotContainsString('<optgroup', $html);
        $this->assertStringContainsString('<option value="WS-2" selected>', $html);
        $this->assertStringNotContainsString('value="WS-1"', $html);

        // eliminamos
        $this->assertTrue($ejercicio1->delete());
        $this->assertTrue($ejercicio2->delete());
        $this->assertTrue($empresa1->delete());
        $this->assertTrue($empresa2->delete());
    }

    public function testModalListIsNotRenderedOnEdit(): void
    {
        $widget = new WidgetSubcuenta([
            'children' => [],
            'fieldname' => 'test',
            'type' => 'subcuenta',
        ]);

        $model = (object)['test' => '4720000000'];
        $html = $widget->edit($model);

        $this->assertStringContainsString('widget-subaccount-modal', $html);
        $this->assertStringContainsString('4720000000', $html);
        $this->assertStringNotContainsString('widget-subaccount-option', $html);
        $this->assertMatchesRegularExpression('/<tbody id="list_\d+"><\/tbody>/', $html);
    }

    private function createExercise(Empresa $empresa, string $code): Ejercicio
    {
        $ejercicio = new Ejercicio();
        $ejercicio->codejercicio = $code;
        $ejercicio->idempresa = $empresa->idempresa;
        $ejercicio->nombre = 'exercise-' . $code;
        $ejercicio->fechainicio = '2099-01-01';
        $ejercicio->fechafin = '2099-12-31';
        $this->assertTrue($ejercicio->save());

        return $ejercicio;
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
