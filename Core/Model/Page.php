<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Model;

/**
 * Element of the menu of InvoiceScripts, each corresponds to a controller.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Page extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Icon of the page.
     *
     * @var string
     */
    public $icon;

    /**
     * Title of the menu option where it is displayed.
     *
     * @var string
     */
    public $menu;

    /**
     * Primary key. Varchar (30).
     * Name of the page (controller).
     *
     * @var string
     */
    public $name;

    /**
     * Position where it is placed in the menu.
     *
     * @var int
     */
    public $ordernum;

    /**
     * Indicates if it is displayed in the menu.
     * False -> hide in the menu.
     *
     * @var bool
     */
    public $showonmenu;

    /**
     * Title of the menu sub-option where it is displayed (if it uses 2 levels).
     *
     * @var string
     */
    public $submenu;

    /**
     * Page title.
     *
     * @var string
     */
    public $title;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->ordernum = 100;
        $this->showonmenu = true;
    }

    /**
     * This function is called when creating the model table. Returns the SQL
     * that will be executed after the creation of the table. Useful to insert values
     * default.
     *
     * @return string
     */
    public function install()
    {
        return 'INSERT INTO ' . static::tableName() . " (name,title) VALUES ('Wizard','Wizard');";
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'name';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'pages';
    }

    /**
     * Returns the url where to see / modify the data.
     *
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'List')
    {
        return (string) $this->name;
    }
}
