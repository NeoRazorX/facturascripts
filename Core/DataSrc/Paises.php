<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

final class Paises implements DataSrcInterface
{
    const MIEMBROS_UE = [
        'DE', 'AT', 'BE', 'BG', 'CZ', 'CY', 'HR', 'DK', 'SK', 'SI', 'EE', 'FI', 'FR', 'GR', 'HU', 'IE', 'IT', 'LV',
        'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'GB', 'ES'
    ];

    private static $list;

    /**
     * @return Pais[]
     */
    public static function all(): array
    {
        if (!isset(self::$list)) {
            $model = new Pais();
            self::$list = $model->all([], ['nombre' => 'ASC'], 0, 0);
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

    public static function miembroUE($codpais): bool
    {
        $iso = self::get($codpais)->codiso;
        return self::miembroUEbyIso($iso);
    }

    public static function miembroUEbyIso($iso): bool
    {
        return in_array($iso, self::MIEMBROS_UE);
    }
}
