<?php
/**
 * Copyright (C) 2017   Joe Nilson          <joenilson at gmail.com>
 * Copyright (C) 2018   Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
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

/**
 * ApiKey model to manage the connection tokens through the api
 * that will be generated to synchronize different applications.
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class ApiKey extends Base\ModelClass
{
    use Base\ModelTrait;

    /**
     * Primary key. Id autoincremental
     *
     * @var int
     */
    public $id;

    /**
     * API key.
     *
     * @var string
     */
    public $apikey;

    /**
     * Description.
     *
     * @var string
     */
    public $descripcion;

    /**
     * Enabled/Disabled.
     *
     * @var bool
     */
    public $enabled;

    /**
     * Date of registration.
     *
     * @var string
     */
    public $creationdate;

    /**
     * Nick of the user.
     *
     * @var string
     */
    public $nick;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'api_keys';
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
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->apikey = '';
        $this->descripcion = '';
        $this->enabled = false;
        $this->creationdate = date('d-m-Y');
    }
}
