<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Model\Pais;

class Paises implements DataSrcInterface
{
    private static $list;

    /**
     * @return Pais[]
     */
    public static function all(): array
    {
        if (!isset(self::$list)) {
            $model = new Pais();
            self::$list = $model->all([], [], 0, 0);
        }

        return self::$list;
    }

    /**
     * @param bool $addEmpty
     *
     * @return array
     */
    public static function codeModel(bool $addEmpty = true): array
    {
        $codes = [];
        foreach (self::all() as $pais) {
            $codes[$pais->codpais] = $pais->nombre;
        }

        return CodeModel::array2codeModel($codes, $addEmpty);
    }

    /**
     * @param string $code
     *
     * @return Pais
     */
    public static function get($code): Pais
    {
        foreach (self::all() as $item) {
            if ($item->primaryColumnValue() === $code) {
                return $item;
            }
        }

        return new Pais();
    }
}
