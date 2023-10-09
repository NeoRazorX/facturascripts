<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\CodeModel;
use FacturaScripts\Dinamic\Model\FormaPago;

final class FormasPago implements DataSrcInterface
{
    private static $list;

    /**
     * @return FormaPago[]
     */
    public static function all(array $where = []): array
    {
        if (!isset(self::$list)) {
            $model = new FormaPago();
            self::$list = $model->all($where, [], 0, 0);
        }

        return self::$list;
    }

    public static function clear(): void
    {
        self::$list = null;
    }

    /**
     * @param bool $addEmpty
     *
     * @return array
     */
    public static function codeModel(bool $addEmpty = true, ?int $idempresa = null): array
    {
        $where = [];
        if (false === is_null($idempresa)) {
            $where[] = new DataBaseWhere('idempresa', $idempresa);
        }

        $codes = [];
        foreach (self::all($where) as $formaPago) {
            $codes[$formaPago->codpago] = $formaPago->descripcion;
        }

        return CodeModel::array2codeModel($codes, $addEmpty);
    }

    /**
     * @param string $code
     *
     * @return FormaPago
     */
    public static function get($code): FormaPago
    {
        foreach (self::all() as $item) {
            if ($item->primaryColumnValue() === $code) {
                return $item;
            }
        }

        return new FormaPago();
    }
}
