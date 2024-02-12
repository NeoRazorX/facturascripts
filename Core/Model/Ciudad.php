<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Cache;
use FacturaScripts\Core\Tools;

/**
 * Ciudad
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Frank Aguirre        <faguirre@soenac.com>
 */
class Ciudad extends Base\ModelClass
{
    use Base\ModelTrait;

    /**
     * @var string
     */
    public $ciudad;

    /**
     * @var string
     */
    public $codeid;

    /**
     * @var int
     */
    public $idciudad;

    /**
     * @var int
     */
    public $idprovincia;

    public function install(): string
    {
        // needed dependency
        new Provincia();

        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'idciudad';
    }

    public static function tableName(): string
    {
        return 'ciudades';
    }

    public function test(): bool
    {
        Cache::delete('DataModel.' . $this->modelClassName());

        $this->ciudad = Tools::noHtml($this->ciudad);

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'ListPais?activetab=List'): string
    {
        return parent::url($type, $list);
    }

    public function all(array $where = [], array $order = [], int $offset = 0, int $limit = 50): array
    {
        if ($where === []) {
            return Cache::remember(
                'DataModel.' . $this->modelClassName(),
                function () use ($where, $order, $offset, $limit) {
                    return parent::all($where, $order, $offset, $limit);
                }
            );
        }

        return parent::all($where, $order, $offset, $limit);
    }
}
