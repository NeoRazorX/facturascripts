<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  carlos@facturascripts.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
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
 * Define que un usuario tiene acceso a una página concreta
 * y si tiene permisos de eliminación en esa página.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class PageRule
{
    use Model;

    /**
     * TODO
     * @var
     */
    public $id;

    /**
     * Nick del usuario.
     * @var string
     */
    public $nick;

    /**
     * Nombre de la página (nombre del controlador).
     * @var string
     */
    public $pagename;

    /**
     * Otorga permisos al usuario a eliminar elementos en la página.
     * @var bool
     */
    public $allowdelete;

    /**
     * TODO
     * @var
     */
    public $allowupdate;

    /**
     * PageRule constructor.
     * @param array $data
     * @throws RuntimeException
     * @throws TranslationInvalidArgumentException
     */
    public function __construct(array $data = [])
    {
        $this->init(__CLASS__, 'fs_access', 'id');
        if (!empty($data)) {
            $this->loadFromData($data);
        } else {
            $this->clear();
        }
    }
}
