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

/**
 * Allows to relate special accounts (SALES, for example)
 * with the real account or sub-account.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class CuentaEspecial extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Special account identifier.
     *
     * @var string
     */
    public $codcuentaesp;

    /**
     * Description of the special account.
     *
     * @var string
     */
    public $descripcion;

    public static function primaryColumn(): string
    {
        return 'codcuentaesp';
    }

    public function primaryDescriptionColumn(): string
    {
        return 'codcuentaesp';
    }

    public static function tableName(): string
    {
        return 'cuentasesp';
    }

    public function test(): bool
    {
        $this->codcuentaesp = self::toolBox()::utils()::noHtml($this->codcuentaesp);
        if ($this->codcuentaesp && 1 !== preg_match('/^[A-Z0-9_\+\.\-]{1,6}$/i', $this->codcuentaesp)) {
            $this->toolBox()->i18nLog()->error(
                'invalid-alphanumeric-code',
                ['%value%' => $this->codcuentaesp, '%column%' => 'codcuentaesp', '%min%' => '1', '%max%' => '6']
            );
            return false;
        }

        $this->descripcion = self::toolBox()::utils()::noHtml($this->descripcion);
        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'ListCuenta?activetab=List'): string
    {
        return parent::url($type, $list);
    }
}
