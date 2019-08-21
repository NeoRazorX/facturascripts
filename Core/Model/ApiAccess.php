<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018  Carlos García Gómez <carlos@facturascripts.com>
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
 * Defines the individual permissions for each resrouce within an api key.
 *
 * @author Francesc Pineda Segarra <francesc.pineda@x-netdigital.com>
 */
class ApiAccess extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Permission to delete.
     *
     * @var bool
     */
    public $allowdelete;

    /**
     * Permission to get.
     *
     * @var bool
     */
    public $allowget;

    /**
     * Permission to post.
     *
     * @var bool
     */
    public $allowpost;

    /**
     * Permission to put.
     *
     * @var bool
     */
    public $allowput;

    /**
     * Identifier of API key.
     *
     * @var int
     */
    public $idapikey;

    /**
     * Identifier.
     *
     * @var int
     */
    public $id;

    /**
     * Name of the resource.
     *
     * @var string
     */
    public $resource;

    /**
     * Add the indicated resource list to the Role group
     *
     * @param int   $idApiKey
     * @param array $resources
     * @param bool  $state
     *
     * @return bool
     */
    public static function addResourcesToApiKey($idApiKey, $resources, $state = false)
    {
        $apiAccess = new static();

        foreach ($resources as $resource) {
            $where = [new DataBaseWhere('idapikey', $idApiKey)];
            $where[] = new DataBaseWhere('resource', $resource);
            if (!$apiAccess->loadFromCode('', $where)) {
                $apiAccess->idapikey = $idApiKey;
                $apiAccess->resource = $resource;
                $apiAccess->allowdelete = $state;
                $apiAccess->allowget = $state;
                $apiAccess->allowpost = $state;
                $apiAccess->allowput = $state;
                if (!$apiAccess->save()) {
                    return false;
                }
            }
            unset($where[1]);
        }

        return true;
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
        new ApiKey();

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
        return 'api_access';
    }
}
