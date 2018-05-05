<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2014-2018 Carlos Garcia Gomez  <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\Utils;

/**
 * A group of customers, which may be associated with a rate.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class GrupoClientes extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Primary key.
     *
     * @var string
     */
    public $codgrupo;

    /**
     * Code of the associated rate, if any.
     *
     * @var string
     */
    public $codtarifa;

    /**
     * Group name.
     *
     * @var string
     */
    public $nombre;

    /**
     * Parent group.
     *
     * @var string
     */
    public $parent;

    /**
     * This function is called when creating the model table. Returns the SQL
     * that will be executed after the creation of the table. Useful to insert values
     * default.
     *
     * @return string
     */
    public function install()
    {
        /// As there is a key outside of tariffs, we have to check that table before
        new Tarifa();

        return '';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'codgrupo';
    }

    /**
     * Returns the description of the column that is the model's primary key.
     *
     * @return string
     */
    public function primaryDescriptionColumn()
    {
        return 'nombre';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'gruposclientes';
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->nombre = Utils::noHtml($this->nombre);

        if ($this->checkCircularRelation()) {
            return false;
        }

        return parent::test();
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
        return parent::url($type, 'ListCliente?active=List');
    }

    /**
     * Check if exists a circular relation between groups.
     *
     * @return bool
     */
    private function checkCircularRelation()
    {
        if ($this->parent === null) {
            return false;
        }

        if ($this->codgrupo === $this->parent) {
            self::$miniLog->alert(self::$i18n->trans('parent-group-cant-be-the-same-group'));
            return true;
        }

        $subgroups = [$this->codgrupo];
        $group = $this->get($this->parent);
        while ($group->parent !== null) {
            if (in_array($group->parent, $subgroups)) {
                self::$miniLog->alert(self::$i18n->trans('parent-group-loop', ['%parentGroup%' => $group->codgrupo]));
                return true;
            }

            $subgroups[] = $group->parent;
            if (!$group->loadFromCode($group->parent)) {
                break;
            }
        }

        return false;
    }
}
