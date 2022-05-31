<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\DataSrc;

use FacturaScripts\Dinamic\Model\CodeModel;
use FacturaScripts\Dinamic\Model\Ejercicio;

final class Ejercicios implements DataSrcInterface
{
    private static $list;

    /**
     * @return Ejercicio[]
     */
    public static function all(): array
    {
        if (null === self::$list) {
            $model = new Ejercicio();
            self::$list = $model->all([], [], 0, 0);
        }

        return self::$list;
    }

    public static function clear(): void
    {
        self::$list = null;
    }

    public static function codeModel(bool $addEmpty = true): array
    {
        $codes = [];
        foreach (self::all() as $ejercicio) {
            $codes[$ejercicio->codejercicio] = $ejercicio->nombre;
        }

        return CodeModel::array2codeModel($codes, $addEmpty);
    }

    /**
     * @param string $code
     *
     * @return Ejercicio
     */
    public static function get($code): Ejercicio
    {
        foreach (self::all() as $item) {
            if ($item->primaryColumnValue() === $code) {
                return $item;
            }
        }

        return new Ejercicio();
    }
}
