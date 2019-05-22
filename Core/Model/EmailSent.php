<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Model EmailSent
 *
 * @author Raul Jimenez         <raljopa@gmail.com>
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class EmailSent extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Email addressee
     *
     * @var string
     */
    public $addressee;

    /**
     * Text of email
     *
     * @var string
     */
    public $body;

    /**
     * Date and time of send
     *
     * @var string
     */
    public $date;

    /**
     * Primary key.
     *
     * @var string
     */
    public $id;

    /**
     * User than sent email
     *
     * @var string
     */
    public $nick;

    /**
     * Subject of email
     *
     * @var string
     */
    public $subject;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->date = date('d-m-Y H:i:s');
    }

    /**
     * Returns the name of the column that is the primary key of the model.
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
        return 'emails_sent';
    }

    /**
     * 
     * @return bool
     */
    public function test()
    {
        $this->body = Utils::noHtml($this->body);
        $this->subject = Utils::noHtml($this->subject);
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
    public function url(string $type = 'auto', string $list = 'ListLogMessage?activetab=List')
    {
        return parent::url($type, $list);
    }
}
