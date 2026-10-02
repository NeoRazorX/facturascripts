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

namespace FacturaScripts\Test\Core\Controller;

use FacturaScripts\Core\Controller\EditProducto;
use FacturaScripts\Core\Model\CodeModel;
use FacturaScripts\Dinamic\Model\User;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class EditProductoTest extends TestCase
{
    public function testStockTabsAreGroupedInWarehouse(): void
    {
        // createViews() cambia el límite estático de CodeModel, lo restauramos al terminar
        $limit = CodeModel::getLimit();

        try {
            $controller = new EditProducto('EditProducto', '/EditProducto');
            $controller->user = new User();
            $controller->user->admin = true;
            $method = new ReflectionMethod(EditProducto::class, 'createViews');
            $method->invoke($controller);

            $groups = $controller->getTabGroups();
        } finally {
            CodeModel::setLimit($limit);
        }

        // la definición del producto queda sin grupo y el stock, las reservas y lo pendiente de recibir, en almacén
        $this->assertSame(['', 'warehouse'], array_keys($groups));
        $this->assertSame(
            ['EditProducto', 'EditVariante', 'EditProductoImagen', 'docfiles', 'EditProductoProveedor'],
            array_keys($groups[''])
        );
        $this->assertSame(
            ['EditStock', 'ListLineaPedidoCliente', 'ListLineaPedidoProveedor'],
            array_keys($groups['warehouse'])
        );
    }
}
