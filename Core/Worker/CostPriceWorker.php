<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2025 Pablo Aceituno <civernet@gmail.com>
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

use FacturaScripts\Core\Model\WorkEvent;
use FacturaScripts\Core\Template\WorkerClass;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Lib\CostPriceTools;
use FacturaScripts\Dinamic\Model\Variante;

class CostPriceWorker extends WorkerClass
{
    public function run(WorkEvent $event): bool
    {
        // obtenemos la referencia del producto
        $referencia = $event->param('referencia');
        if (empty($referencia)) {
            return $this->done();
        }

        // cargamos la variante
        $variante = new Variante();
        $where = [Where::eq('referencia', $referencia)];
        if (false === $variante->loadWhere($where)) {
            return $this->done();
        }

        // actualizamos el precio de coste
        CostPriceTools::update($variante);

        return $this->done();
    }
}
