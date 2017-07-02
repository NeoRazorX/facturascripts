<?php

/*
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

use FacturaScripts\Core\Base\Model;
use RuntimeException;
use Symfony\Component\Translation\Exception\InvalidArgumentException as TranslationInvalidArgumentException;

/**
 * Define la relación entre un usuario y un rol.
 *
 * @author Joe Nilson            <joenilson at gmail.com>
 * @author Carlos García Gómez   <neorazorx at gmail.com>
 */
class RolUser
{
    use Model;

    /**
     * TODO
     * @var
     */
    public $id;

    /**
     * TODO
     * @var
     */
    public $codrol;

    /**
     * TODO
     * @var
     */
    public $nick;

    /**
     * RolUser constructor.
     *
     * @param array $data
     *
     * @throws RuntimeException
     * @throws TranslationInvalidArgumentException
     */
    public function __construct(array $data = [])
    {
        $this->init(__CLASS__, 'fs_roles_users', 'id');
        if (!empty($data)) {
            $this->loadFromData($data);
        } else {
            $this->clear();
        }
    }
}
