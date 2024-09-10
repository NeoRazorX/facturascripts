<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Dinamic\Model\AlbaranProveedor as DinAlbaranProveedor;

/**
 * Line of a supplier's delivery note.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class LineaAlbaranProveedor extends Base\PurchaseDocumentLine
{

    use Base\ModelTrait;

    /**
     * Delivery note ID of this line.
     *
     * @var int
     */
    public $idalbaran;

    public function documentColumn(): string
    {
        return 'idalbaran';
    }

    public function getDocument(): DinAlbaranProveedor
    {
        $albaran = new DinAlbaranProveedor();
        $albaran->loadFromCode($this->idalbaran);
        return $albaran;
    }

    public function install(): string
    {
        // needed dependency
        new AlbaranProveedor();

        return parent::install();
    }

    public static function tableName(): string
    {
        return 'lineasalbaranesprov';
    }

    public function url(string $type = 'auto', string $list = 'List'): string
    {
        if (null !== $this->idalbaran) {
            return 'EditAlbaranProveedor?code=' . $this->idalbaran;
        }

        return parent::url($type, $list);
    }
}
