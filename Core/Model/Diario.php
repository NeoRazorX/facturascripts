<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;

/**
 * A division of accounting entries in different journals
 *
 * @author Carlos Garcia Gomez  <carlos@facturascripts.com>
 * @author Raul Jimenez         <raul.jimenez@nazcanetworks.com>
 */
class Diario extends ModelClass
{
    use ModelTrait;

    /** Descripción del diario contable. @var string */
    public $descripcion;

    /** Identificador único del diario contable. @var integer */
    public $iddiario;

    public static function primaryColumn(): string
    {
        return 'iddiario';
    }

    public static function tableName(): string
    {
        return 'diarios';
    }

    public function test(): bool
    {
        $this->descripcion = Tools::noHtml($this->descripcion);
        if (strlen($this->descripcion ?? '') < 1) {
            Tools::log()->warning('field-required', ['%field%' => 'descripcion']);
            return false;
        }

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'ListAsiento?activetab=List'): string
    {
        return parent::url($type, $list);
    }

    protected function saveInsert(): bool
    {
        if (empty($this->iddiario)) {
            $this->iddiario = $this->newCode();
        }

        return parent::saveInsert();
    }
}
