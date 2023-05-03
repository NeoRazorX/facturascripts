<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\RoleAccess;
use FacturaScripts\Core\Model\User;

/**
 * Description of ControllerPermissionsTest
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
final class ControllerPermissions
{
    /** @var int */
    public $accessMode = 1;

    /** @var bool */
    public $allowAccess = false;

    /** @var bool */
    public $allowDelete = false;

    /** @var bool */
    public $allowExport = false;

    /** @var bool */
    public $allowImport = false;

    /** @var bool */
    public $allowUpdate = false;

    /** @var bool */
    public $onlyOwnerData = false;

    public function __construct(?User $user = null, string $pageName = null)
    {
        if (empty($user) || empty($pageName)) {
            // do nothing
        } elseif ($user->admin) {
            // admin user
            $this->accessMode = 99;
            $this->allowAccess = true;
            $this->allowDelete = true;
            $this->allowExport = true;
            $this->allowImport = true;
            $this->allowUpdate = true;
            $this->onlyOwnerData = false;
        } else {
            // normal user
            foreach (RoleAccess::allFromUser($user->nick, $pageName) as $access) {
                $this->allowAccess = true;
                $this->allowDelete = $access->allowdelete ? true : $this->allowDelete;
                $this->allowExport = $access->allowexport ? true : $this->allowExport;
                $this->allowImport = $access->allowimport ? true : $this->allowImport;
                $this->allowUpdate = $access->allowupdate ? true : $this->allowUpdate;
                $this->onlyOwnerData = $access->onlyownerdata ? true : $this->onlyOwnerData;
            }
        }
    }

    public function set(bool $access, int $accessMode, bool $delete, bool $update, bool $onlyOwner = false): void
    {
        $this->accessMode = $accessMode;
        $this->allowAccess = $access;
        $this->allowDelete = $delete;
        $this->allowUpdate = $update;
        $this->onlyOwnerData = $onlyOwner;
    }

    public function setParams(array $params): void
    {
        foreach ($params as $key => $value) {
            if (false === property_exists($this, $key)) {
                continue;
            } elseif ($key === 'accessMode') {
                $this->{$key} = (int)$value;
                continue;
            }

            $this->{$key} = (bool)$value;
        }
    }
}
