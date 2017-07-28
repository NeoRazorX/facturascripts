<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2016 Joe Nilson             <joenilson at gmail.com>
 * Copyright (C) 2017 Carlos García Gómez    <neorazorx at gmail.com>
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
 * @author Carlos García Gómez   <neorazorx at gmail.com>
 */
class RolAccess
{

    use Base\ModelTrait;

    /**
     * TODO
     * @var int
     */
    public $id;

    /**
     * TODO
     * @var string
     */
    public $codrol;

    /**
     * TODO
     * @var string
     */
    public $pagename;

    /**
     * TODO
     * @var bool
     */
    public $allowdelete;

    /**
     * TODO
     * @var bool
     */
    public $allowupdate;

    /**
     * RolAccess constructor.
     *
     * @param array $data
     */
    public function __construct($data = [])
    {
        $this->init(__CLASS__, 'fs_roles_access', 'id');
        if (is_null($data) || empty($data)) {
            $this->clear();
        } else {
            $this->loadFromData($data);
        }
    }
}
