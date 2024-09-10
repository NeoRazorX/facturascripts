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

use FacturaScripts\Core\Tools;

/**
 * Description of IdentificadorFiscal
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class IdentificadorFiscal extends Base\ModelClass
{
    use Base\ModelTrait;

    /**
     * @var string
     */
    public $codeid;

    /**
     * @var string
     */
    public $tipoidfiscal;

    /**
     * @var bool
     */
    public $validar;

    public function clear()
    {
        parent::clear();
        $this->validar = false;
    }

    public static function primaryColumn(): string
    {
        return 'tipoidfiscal';
    }

    public static function tableName(): string
    {
        return 'idsfiscales';
    }

    public function test(): bool
    {
        // escapamos el html
        $this->codeid = Tools::noHtml($this->codeid);
        $this->tipoidfiscal = Tools::noHtml($this->tipoidfiscal);

        // comprobamos que el campo tenga un valor válido
        if (empty($this->tipoidfiscal) || 1 !== preg_match('/^[A-Z0-9_\+\.\-]{1,25}$/i', $this->tipoidfiscal)) {
            Tools::log()->error(
                'invalid-alphanumeric-code',
                ['%value%' => $this->tipoidfiscal, '%column%' => 'tipoidfiscal', '%min%' => '1', '%max%' => '25']
            );
            return false;
        }

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'EditSettings?activetab=List'): string
    {
        return parent::url($type, $list);
    }
}
