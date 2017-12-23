<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Defines that a user has access to a specific page
 * and if you have removal permissions on that page.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class PageRule
{

    use Base\ModelTrait;

    /**
     * Identifier.
     *
     * @var int
     */
    public $id;

    /**
     * Nick of the user.
     *
     * @var string
     */
    public $nick;

    /**
     * Name of the page (name of the controller).
     *
     * @var string
     */
    public $pagename;

    /**
     * Grants permissions to the user to delete elements on the page.
     *
     * @var bool
     */
    public $allowdelete;

    /**
     * Grant the user permission to update elements on the page.
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
        return 'fs_page_rules';
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
     * Add the indicated page list to user
     *
     * @param string $nick
     * @param Page[] $pages
     * @return bool
     */
    public static function addPagesToUser($nick, $pages): bool
    {
        $where = [new DataBaseWhere('nick', $nick)];
        $pageRule = new PageRule();

        foreach ($pages as $record) {
            $where[] = new DataBaseWhere('pagename', $record->name);

            if (!$pageRule->loadFromCode('', $where)) {
                $pageRule->nick = $nick;
                $pageRule->pagename = $record->name;
                $pageRule->allowdelete = true;
                $pageRule->allowupdate = true;
                if (!$pageRule->save()) {
                    return false;
                }
            }
            unset($where[1]);
        }
        return true;
    }
}
