<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2024-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Cache;
use FacturaScripts\Dinamic\Model\CodeModel;
use FacturaScripts\Dinamic\Model\GrupoClientes;

final class GruposClientes implements DataSrcInterface
{
    /** @var GrupoClientes[] */
    private static $list;

    /**
     * @return GrupoClientes[]
     */
    public static function all(): array
    {
        if (!isset(self::$list)) {
            self::$list = Cache::remember('model-GrupoClientes-list', function () {
                return GrupoClientes::all([], ['nombre' => 'ASC'], 0, 0);
            });
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
        foreach (self::all() as $grupo) {
            $codes[$grupo->codgrupo] = $grupo->nombre;
        }

        return CodeModel::array2codeModel($codes, $addEmpty);
    }

    /**
     * @param string $code
     *
     * @return GrupoClientes
     */
    public static function get($code): GrupoClientes
    {
        foreach (self::all() as $item) {
            if ($item->id() === $code) {
                return $item;
            }
        }

        return GrupoClientes::find($code) ?? new GrupoClientes();
    }
}
