<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Base;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\RoleUser;
use FacturaScripts\Dinamic\Model\User;

/**
 * Description of ControllerPermissions
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ControllerPermissions
{

    /**
     * Have permissitions to access data.
     *
     * @var bool
     */
    public $allowAccess;

    /**
     * Have permissitions to delete data.
     *
     * @var bool
     */
    public $allowDelete;

    /**
     * Have permissions to update data.
     *
     * @var bool
     */
    public $allowUpdate;

    /**
     * ControllerPermissions constructor.
     *
     * @param User|false  $user
     * @param string|null $pageName
     */
    public function __construct($user = false, $pageName = null)
    {
        if ($user !== false && $pageName !== null) {
            $this->loadFromUser($user, $pageName);
        } else {
            $this->clear();
        }
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        $this->allowAccess = false;
        $this->allowDelete = false;
        $this->allowUpdate = false;
    }

    /**
     * Load permissions from $user
     *
     * @param User   $user
     * @param string $pageName
     */
    public function loadFromUser($user, $pageName)
    {
        $this->clear();
        if ($user->admin) {
            $this->allowAccess = true;
            $this->allowDelete = true;
            $this->allowUpdate = true;
        }

        $roleUserModel = new RoleUser();
        $filter = [new DataBaseWhere('nick', $user->nick)];
        foreach ($roleUserModel->all($filter) as $roleUser) {
            foreach ($roleUser->getRoleAccess($pageName) as $roleAccess) {
                $this->allowAccess = true;
                $this->allowDelete = $roleAccess->allowdelete ? true : $this->allowDelete;
                $this->allowUpdate = $roleAccess->allowupdate ? true : $this->allowUpdate;
            }
        }
    }
}
