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

namespace FacturaScripts\Core\Worker;

use FacturaScripts\Core\Lib\CostPriceTools;
use FacturaScripts\Core\Model\WorkEvent;
use FacturaScripts\Core\Template\WorkerClass;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Variante;

/**
 * @author Esteban Sánchez Martínez <esteban@factura.city>
 */

class ProductoProveedorWorker extends WorkerClass
{
    public function run(WorkEvent $event): bool
    {
        $referencia = $event->param('referencia');
        if (empty($referencia)) {
            return $this->done();
        }

        $variant = new Variante();
        if (false === $variant->loadWhere([Where::eq('referencia', $referencia)])) {
            return $this->done();
        }

        CostPriceTools::update($variant);

        return $this->done();
    }
}
