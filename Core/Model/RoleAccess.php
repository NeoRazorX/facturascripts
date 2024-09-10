<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2023  Carlos García Gómez <carlos@facturascripts.com>
 * Copyright (C) 2016       Joe Nilson          <joenilson@gmail.com>
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
use FacturaScripts\Dinamic\Model\Page as DinPage;
use FacturaScripts\Dinamic\Model\Role as DinRole;
use FacturaScripts\Dinamic\Model\User as DinUser;

/**
 * Defines the individual permissions for each page within a user role.
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Joe Nilson           <joenilson@gmail.com>
 */
class RoleAccess extends Base\ModelClass
{
    use Base\ModelTrait;

    /** @var bool */
    public $allowdelete;

    /** @var bool */
    public $allowexport;

    /** @var bool */
    public $allowimport;

    /** @var bool */
    public $allowupdate;

    /** @var string */
    public $codrole;

    /** @var int */
    public $id;

    /** @var bool */
    public $onlyownerdata;

    /** @var string */
    public $pagename;

    /**
     * Add the indicated page list to the Role group
     *
     * @param string $codrole
     * @param DinPage[] $pages
     *
     * @return bool
     */
    public static function addPagesToRole(string $codrole, array $pages): bool
    {
        foreach ($pages as $page) {
            $roleAccess = new static();
            $where = [
                new DataBaseWhere('codrole', $codrole),
                new DataBaseWhere('pagename', $page->name)
            ];
            if ($roleAccess->loadFromCode('', $where)) {
                continue;
            }

            $roleAccess->codrole = $codrole;
            $roleAccess->pagename = $page->name;
            if (false === $roleAccess->save()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $nick
     * @param string $pageName
     * @return RoleAccess[]
     */
    public static function allFromUser(string $nick, string $pageName): array
    {
        $sqlIn = 'SELECT codrole FROM ' . RoleUser::tableName() . ' WHERE nick = ' . self::$dataBase->var2str($nick);
        $where = [
            new DataBaseWhere('codrole', $sqlIn, 'IN'),
            new DataBaseWhere('pagename', $pageName)
        ];
        $roleAccess = new static();
        return $roleAccess->all($where, [], 0, 0);
    }

    public function can(string $permission): bool
    {
        switch ($permission) {
            case 'access':
                return true;

            case 'delete':
                return $this->allowdelete;

            case 'export':
                return $this->allowexport;

            case 'import':
                return $this->allowimport;

            case 'update':
                return $this->allowupdate;

            case 'only-owner-data':
                return $this->onlyownerdata;

            default:
                Tools::log()->error('invalid-user-can-permission', ['%permission%' => $permission]);
                return false;
        }
    }

    public function clear()
    {
        parent::clear();
        $this->allowdelete = true;
        $this->allowexport = true;
        $this->allowimport = true;
        $this->allowupdate = true;
        $this->onlyownerdata = false;
    }

    public function getPage(): DinPage
    {
        $page = new DinPage();
        $page->loadFromCode($this->pagename);
        return $page;
    }

    public function install(): string
    {
        // needed dependencies
        new DinRole();
        new DinUser();

        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'roles_access';
    }
}
