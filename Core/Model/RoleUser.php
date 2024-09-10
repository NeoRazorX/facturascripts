<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2023  Carlos García Gómez <carlos@facturascripts.com>
 * Copyright (C) 2016       Joe Nilson          <joenilson at gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Role as DinRole;
use FacturaScripts\Dinamic\Model\RoleAccess as DinRoleAccess;

/**
 * Defines the relationship between a user and a role.
 *
 * @author Joe Nilson            <joenilson at gmail.com>
 * @author Carlos García Gómez   <carlos@facturascripts.com>
 */
class RoleUser extends Base\ModelClass
{
    use Base\ModelTrait;

    /** @var string */
    public $codrole;

    /** @var int */
    public $id;

    /** @var string */
    public $nick;

    public function getRole(): Role
    {
        $role = new DinRole();
        $role->loadFromCode($this->codrole);
        return $role;
    }

    /**
     * Devuelve la lista de permisos de acceso para el usuario.
     * Si se proporciona un $pageName, devuelve el permiso para esa página.
     *
     * @param string $pageName
     *
     * @return DinRoleAccess[]
     */
    public function getRoleAccess(string $pageName = ''): array
    {
        if (empty($this->nick)) {
            return [];
        }

        $filter = [new DataBaseWhere('codrole', $this->codrole)];
        if (!empty($pageName)) {
            $filter[] = new DataBaseWhere('pagename', $pageName);
        }

        $accesses = [];
        $roleAccessModel = new DinRoleAccess();
        foreach ($roleAccessModel->all($filter, ['pagename' => 'ASC'], 0, 0) as $roleAccess) {
            $accesses[] = $roleAccess;
        }

        return $accesses;
    }

    public function install(): string
    {
        // needed dependencies
        new Role();
        new User();

        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'roles_users';
    }

    public function test(): bool
    {
        if (empty($this->nick)) {
            Tools::log()->warning('nick-is-empty');
            return false;
        }

        if (empty($this->codrole)) {
            Tools::log()->warning('role-is-empty');
            return false;
        }

        return parent::test();
    }
}
