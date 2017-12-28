<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Base;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Core\Model\RolAccess;
use FacturaScripts\Core\Model\RolUser;

/**
 * Description of ControllerPermissions
 *
 * @author carlos
 */
class ControllerPermissions
{

    /**
     *
     * @var bool 
     */
    public $allowAccess;

    /**
     *
     * @var bool 
     */
    public $allowDelete;

    /**
     *
     * @var bool 
     */
    public $allowUpdate;

    /**
     * 
     * @param User|false $user
     * @param string $pageName
     */
    public function __construct($user = false, $pageName = null)
    {
        if ($user !== false && $pageName !== null) {
            $this->loadFromUser($user, $pageName);
        } else {
            $this->clear();
        }
    }

    public function clear()
    {
        $this->allowAccess = false;
        $this->allowDelete = false;
        $this->allowUpdate = false;
    }

    /**
     * 
     * @param User $user
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

        $rolUserModel = new RolUser();
        $rolAccessModel = new RolAccess();

        $filter1 = [new DataBaseWhere('nick', $user->nick)];
        foreach ($rolUserModel->all($filter1) as $rolUser) {
            $filter2 = [
                new DataBaseWhere('codrol', $rolUser->codrol),
                new DataBaseWhere('pagename', $pageName)
            ];
            foreach ($rolAccessModel->all($filter2) as $rolAccess) {
                $this->allowAccess = true;
                $this->allowDelete = $rolAccess->allowdelete ? true : $this->allowDelete;
                $this->allowUpdate = $rolAccess->allowupdate ? true : $this->allowUpdate;
            }
        }
    }
}
