<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Model\EstadoDocumento;

final class EstadosDocumentos implements DataSrcInterface
{
    /** @var EstadoDocumento[] */
    private static $list;

    /** @return EstadoDocumento[] */
    public static function all(): array
    {
        if (!isset(self::$list)) {
            self::$list = Cache::remember('model-EstadoDocumento-list', function () {
                return EstadoDocumento::all([], ['idestado' => 'ASC'], 0, 0);
            });
        }

        return self::$list;
    }

    /**
     * Devuelve los estados de un tipo de documento concreto.
     *
     * @return EstadoDocumento[]
     */
    public static function byTipoDoc(string $tipodoc): array
    {
        $result = [];
        foreach (self::all() as $item) {
            if ($item->tipodoc === $tipodoc) {
                $result[] = $item;
            }
        }

        return $result;
    }

    public static function clear(): void
    {
        self::$list = null;
    }

    public static function codeModel(bool $addEmpty = true): array
    {
        $codes = [];
        foreach (self::all() as $item) {
            $codes[$item->idestado] = $item->nombre;
        }

        return CodeModel::array2codeModel($codes, $addEmpty);
    }

    /**
     * codeModel de los estados de un tipo de documento concreto.
     */
    public static function codeModelByTipoDoc(string $tipodoc, bool $addEmpty = true): array
    {
        $codes = [];
        foreach (self::byTipoDoc($tipodoc) as $item) {
            $codes[$item->idestado] = $item->nombre;
        }

        return CodeModel::array2codeModel($codes, $addEmpty);
    }

    /**
     * Devuelve el estado predeterminado para el tipo de documento indicado.
     */
    public static function default(string $tipodoc): EstadoDocumento
    {
        foreach (self::byTipoDoc($tipodoc) as $item) {
            if ($item->predeterminado) {
                return $item;
            }
        }

        return new EstadoDocumento();
    }

    /**
     * @param int|string $code
     */
    public static function get($code): EstadoDocumento
    {
        foreach (self::all() as $item) {
            if ($item->id() == $code) {
                return $item;
            }
        }

        return EstadoDocumento::find($code) ?? new EstadoDocumento();
    }
}
