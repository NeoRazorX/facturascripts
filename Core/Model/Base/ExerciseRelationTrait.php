<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
     * All exercises.
     *
     * @var Ejercicio[]
     */
    private static $ejercicios;

    public function clearExerciseCache()
    {
        $exerciseModel = new Ejercicio();
        self::$ejercicios = $exerciseModel->all();
    }

    /**
     * Returns the current exercise or the default one.
     * 
     * @return Ejercicio
     */
    public function getExercise()
    {
        /// loads all exercise to improve performance
        if (empty(self::$ejercicios)) {
            $exerciseModel = new Ejercicio();
            self::$ejercicios = $exerciseModel->all();
        }

        /// find exercise
        foreach (self::$ejercicios as $exe) {
            if ($exe->codejercicio == $this->codejercicio) {
                return $exe;
            } elseif (empty($this->codejercicio) && $exe->isOpened()) {
                /// return default exercise
                return $exe;
            }
        }

        /// exercise not found? try to get from database
        $exercise = new Ejercicio();
        if ($exercise->loadFromCode($this->codejercicio)) {
            /// add new exercise to cache
            self::$ejercicios[] = $exercise;
        }
        return $exercise;
    }
}
