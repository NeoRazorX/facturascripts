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

/**
 * Define los permisos individuales para cada página dentro de un rol de usuarios.
 *
 * @author Joe Nilson            <joenilson at gmail.com>
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class RolAccess
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
     * Nombre de la página
     *
     * @var string
     */
    public $pagename;

    /**
     * Permiso para eliminar
     *
     * @var bool
     */
    public $allowdelete;

    /**
     * Permiso para actualizar
     *
     * @var bool
     */
    public $allowupdate;

    /**
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'fs_roles_access';
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
}
