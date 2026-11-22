<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Template\EditBlockController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Divisa;

/**
 * Controlador para editar un único elemento del modelo Divisa
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Raúl Jiménez         <comercial@nazcanetworks.com>
 */
class EditDivisa extends EditBlockController
{
    public function getModelClassName(): string
    {
        return 'Divisa';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'currency';
        $data['icon'] = 'fa-solid fa-money-bill-alt';
        return $data;
    }

    protected function createBlocks(): void
    {
        parent::createBlocks();

        // solo hay un formulario pequeño: lo centramos en lugar de estirarlo a todo el ancho
        $this->page()->centered();

        // una divisa no cuelga de nada: la miga de pan es el listado y la propia divisa
        $this->header()
            ->breadcrumb('currencies', fn(Divisa $currency) => [])
            ->status(fn(Divisa $currency) => $currency->isDefault() ? [Tools::trans('default'), 'warning'] : null);
    }
}
