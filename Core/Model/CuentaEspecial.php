<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

    /**
     * Return the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'codcuentaesp';
    }

    /**
     * Returns the name of the column that describes the model, such as name, description...
     *
     * @return string
     */
    public function primaryDescriptionColumn()
    {
        return 'codcuentaesp';
    }

    /**
     * Return the name of the tabel that this model uses.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'cuentasesp';
    }

    /**
     * 
     * @return bool
     */
    public function test()
    {
        $this->codcuentaesp = \trim($this->codcuentaesp);
        if (1 !== \preg_match('/^[A-Z0-9_\+\.\-]{1,6}$/i', $this->codcuentaesp)) {
            $this->toolBox()->i18nLog()->error(
                'invalid-alphanumeric-code',
                ['%value%' => $this->codcuentaesp, '%column%' => 'codcuentaesp', '%min%' => '1', '%max%' => '6']
            );
            return false;
        }

        $this->descripcion = $this->toolBox()->utils()->noHtml($this->descripcion);
        return parent::test();
    }

    /**
     * Returns the url where to see / modify the data.
     *
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'ListCuenta?activetab=List')
    {
        return parent::url($type, $list);
    }
}
