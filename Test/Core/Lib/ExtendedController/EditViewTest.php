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

namespace FacturaScripts\Test\Core\Lib\ExtendedController;

use FacturaScripts\Core\Lib\ExtendedController\EditView;
use FacturaScripts\Core\Model\Cliente;
use FacturaScripts\Core\Request;
use PHPUnit\Framework\TestCase;

final class EditViewTest extends TestCase
{
    public function testLoadSanitizesQueryValues(): void
    {
        $view = new EditView('EditCliente', 'customer', Cliente::class, 'fa-solid fa-users');
        $request = new Request(['query' => [
            'code' => 'ignored',
            'nombre' => '"><img src=x onerror=alert(1)>',
            'telefono1' => ['<script>'],
        ]]);

        $view->processFormData($request, 'load');

        // el valor de la URL se limpia igual que al guardar
        $this->assertSame('&quot;&gt;&lt;img src=x onerror=alert(1)&gt;', $view->model->nombre);

        // los parámetros excluidos y los arrays no se copian al modelo
        $this->assertNull($view->model->codcliente);
        $this->assertNotSame(['<script>'], $view->model->telefono1);
    }
}
