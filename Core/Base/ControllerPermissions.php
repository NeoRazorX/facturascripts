<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Dinamic\Model\RoleAccess;
use FacturaScripts\Dinamic\Model\User;

/**
 * Description of ControllerPermissions
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ControllerPermissions
{

    /**
     *
     * @var int
     */
    public $accessMode = 1;

    /**
     * Have permission to access data.
     *
     * @var bool
     */
    public $allowAccess = false;

    /**
     * Have permission to delete data.
     *
     * @var bool
     */
    public $allowDelete = false;

    /**
     * Have permission to update data.
     *
     * @var bool
     */
    public $allowUpdate = false;

    /**
     * Permission for show all or only owner data.
     *
     * @var bool
     */
    public $onlyOwnerData = false;

    /**
     * ControllerPermissions constructor.
     *
     * @param User|false  $user
     * @param string|null $pageName
     */
    public function __construct($user = false, $pageName = null)
    {
        if (empty($user) || empty($pageName)) {
            /// no dothing
        } elseif ($user->admin) {
            /// admin user
            $this->accessMode = 99;
            $this->allowAccess = true;
            $this->allowDelete = true;
            $this->allowUpdate = true;
            $this->onlyOwnerData = false;
        } else {
            /// normal user
            foreach (RoleAccess::allFromUser($user->nick, $pageName) as $access) {
                $this->allowAccess = true;
                $this->allowDelete = $access->allowdelete ? true : $this->allowDelete;
                $this->allowUpdate = $access->allowupdate ? true : $this->allowUpdate;
                $this->onlyOwnerData = $access->onlyownerdata ? true : $this->onlyOwnerData;
            }
        }
    }

    /**
     *
     * @param bool $access
     * @param int  $accessMode
     * @param bool $delete
     * @param bool $update
     * @param bool $onlyOwner
     */
    public function set(bool $access, int $accessMode, bool $delete, bool $update, bool $onlyOwner = false)
    {
        $this->accessMode = $accessMode;
        $this->allowAccess = $access;
        $this->allowDelete = $delete;
        $this->allowUpdate = $update;
        $this->onlyOwnerData = $onlyOwner;
    }
}
