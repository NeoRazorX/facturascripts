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

use FacturaScripts\Core\Model\WorkEvent;
use FacturaScripts\Core\Template\WorkerClass;
use FacturaScripts\Dinamic\Model\Atributo;

/**
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
class AtributoWorker extends WorkerClass
{
    public function run(WorkEvent $event): bool
    {
        // cargamos el atributo. Usamos el valor del evento, que siempre es la clave
        // primaria, porque update() solo manda en los params los campos modificados
        $atributo = new Atributo();
        if (false === $atributo->load($event->value ?? $event->param('codatributo'))) {
            return $this->done();
        }

        // AtributoValor::test() compone la descripción con el nombre del atributo,
        // así que al renombrarlo hay que regenerar la de todos sus valores
        foreach ($atributo->getValues() as $valor) {
            $descripcion = $atributo->nombre . ' ' . $valor->valor;
            if ($valor->descripcion !== $descripcion) {
                $valor->save();
            }
        }

        return $this->done();
    }
}
