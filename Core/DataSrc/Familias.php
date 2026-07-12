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
use FacturaScripts\Dinamic\Model\Familia;

final class Familias implements DataSrcInterface
{
    /** @var Familia[] */
    private static $list;

    /** @return Familia[] */
    public static function all(): array
    {
        if (!isset(self::$list)) {
            self::$list = Cache::remember('model-Familia-list', function () {
                return Familia::all([], ['descripcion' => 'ASC'], 0, 0);
            });
        }

        return self::$list;
    }

    /**
     * Devuelve las subfamilias directas de la familia indicada, ordenadas
     * por descripción. Pasa null (o cadena vacía) para obtener las familias raíz.
     *
     * @param string|null $codmadre
     *
     * @return Familia[]
     */
    public static function children($codmadre = null): array
    {
        $children = [];
        foreach (self::all() as $familia) {
            if ((empty($codmadre) && empty($familia->madre)) || $familia->madre === $codmadre) {
                $children[] = $familia;
            }
        }

        return $children;
    }

    public static function clear(): void
    {
        self::$list = null;
    }

    public static function count(): int
    {
        return count(self::all());
    }

    public static function codeModel(bool $addEmpty = true): array
    {
        $codes = [];
        foreach (self::all() as $familia) {
            $codes[$familia->codfamilia] = $familia->descripcion;
        }

        return CodeModel::array2codeModel($codes, $addEmpty);
    }

    /**
     * @param string $code
     *
     * @return Familia
     */
    public static function get($code): Familia
    {
        foreach (self::all() as $item) {
            if ($item->id() === $code) {
                return $item;
            }
        }

        return Familia::find($code) ?? new Familia();
    }
}
