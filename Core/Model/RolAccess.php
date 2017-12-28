<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2016 Joe Nilson             <joenilson at gmail.com>
 * Copyright (C) 2017 Carlos García Gómez    <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Defines the individual permissions for each page within a user role.
 *
 * @author Joe Nilson            <joenilson at gmail.com>
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class RolAccess
{

    use Base\ModelTrait;

    /**
     * Identifier.
     *
     * @var int
     */
    public $id;

    /**
     * Role code.
     *
     * @var string
     */
    public $codrol;

    /**
     * Name of the page.
     *
     * @var string
     */
    public $pagename;

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
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'fs_roles_access';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'id';
    }

    /**
     * Add the indicated page list to the Role group
     *
     * @param string $codRol
     * @param Page[] $pages
     * @return bool
     */
    public static function addPagesToRol($codRol, $pages)
    {
        $where = [new DataBaseWhere('codrol', $codRol)];
        $rolAccess = new RolAccess();

        foreach ($pages as $record) {
            $where[] = new DataBaseWhere('pagename', $record->name);

            if (!$rolAccess->loadFromCode('', $where)) {
                $rolAccess->codrol = $codRol;
                $rolAccess->pagename = $record->name;
                $rolAccess->allowdelete = true;
                $rolAccess->allowupdate = true;
                if (!$rolAccess->save()) {
                    return false;
                }
            }
            unset($where[1]);
        }
        return true;
    }
}
