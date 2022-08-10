<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2020-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Model\Base;

use FacturaScripts\Core\DataSrc\Ejercicios;
use FacturaScripts\Dinamic\Model\Ejercicio;

/**
 * Description of InvoiceTrait
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
trait ExerciseRelationTrait
{

    /**
     * Exercise code.
     *
     * @var string
     */
    public $codejercicio;

    /**
     * @return void
     * @deprecated since 2022.1. Use Ejercicios::clear() instead.
     */
    public function clearExerciseCache()
    {
        Ejercicios::clear();
    }

    public function getExercise(string $codejercicio = ''): Ejercicio
    {
        $code = empty($codejercicio) ? $this->codejercicio : $codejercicio;
        return Ejercicios::get($code);
    }
}
