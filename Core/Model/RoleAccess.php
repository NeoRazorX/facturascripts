<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2016       Joe Nilson          <joenilson at gmail.com>
 * Copyright (C) 2017-2019  Carlos García Gómez <carlos@facturascripts.com>
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

/**
 * Defines the individual permissions for each page within a user role.
 *
 * @author Joe Nilson           <joenilson at gmail.com>
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class RoleAccess extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Permission to delete.
     *
     * @var bool
     */
    public $allowdelete;

    /**
     * Permission to update.
     *
     * @var bool
     */
    public $allowupdate;

    /**
     * Role code.
     *
     * @var string
     */
    public $codrole;

    /**
     * Identifier.
     *
     * @var int
     */
    public $id;

    /**
     * Name of the page.
     *
     * @var string
     */
    public $pagename;

    /**
     * Add the indicated page list to the Role group
     *
     * @param string $codrole
     * @param Page[] $pages
     *
     * @return bool
     */
    public static function addPagesToRole($codrole, $pages)
    {
        $roleAccess = new static();
        foreach ($pages as $page) {
            $where = [
                new DataBaseWhere('codrole', $codrole),
                new DataBaseWhere('pagename', $page->name)
            ];

            if ($roleAccess->loadFromCode('', $where)) {
                continue;
            }

            $roleAccess->codrole = $codrole;
            $roleAccess->pagename = $page->name;
            $roleAccess->allowdelete = true;
            $roleAccess->allowupdate = true;
            if (!$roleAccess->save()) {
                return false;
            }
        }

        return true;
    }

    /**
     * 
     * @return Page
     */
    public function getPage()
    {
        $page = new Page();
        $page->loadFromCode($this->pagename);
        return $page;
    }

    /**
     * 
     * @return string
     */
    public function install()
    {
        /// needed dependencies
        new Role();
        new User();

        return parent::install();
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'id';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'roles_access';
    }
}
