<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2016 Joe Nilson             <joenilson at gmail.com>
 * Copyright (C) 2017 Carlos García Gómez    <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Define la relación entre un usuario y un rol.
 *
 * @author Joe Nilson            <joenilson at gmail.com>
 * @author Carlos García Gómez   <carlos@facturascripts.com>
 */
class RolUser
{

    use Base\ModelTrait;

    /**
     * Identificador
     *
     * @var int
     */
    public $id;

    /**
     * Código de rol
     *
     * @var string
     */
    public $codrol;

    /**
     * Nick
     *
     * @var string
     */
    public $nick;

    /**
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'fs_roles_users';
    }

    /**
     * Devuelve el nombre de la columna que es clave primaria del modelo.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'id';
    }

    public function test()
    {
        if (empty($this->nick)) {
            $this->miniLog->alert($this->i18n->trans('nick-is-empty'));
            return false;
        }

        if (empty($this->codrol)) {
            $this->miniLog->alert($this->i18n->trans('role-is-empty'));
            return false;
        }

        $where = [
            new DataBaseWhere('nick', $this->nick),
            new DataBaseWhere('codrol', $this->codrol)
        ];

        $rolUser = new RolUser();
        if ($rolUser->loadFromCode(NULL, $where) && $rolUser->id !== $this->id) {
            $this->miniLog->alert($this->i18n->trans('rol-user-exists'));
            return false;
        }

        return TRUE;
    }
}
