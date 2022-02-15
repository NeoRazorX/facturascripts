<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\Base;

/**
 * Description of EmailNotification
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class EmailNotification extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     *
     * @var string
     */
    public $body;

    /**
     *
     * @var string
     */
    public $creationdate;

    /**
     *
     * @var bool
     */
    public $enabled;

    /**
     *
     * @var string
     */
    public $name;

    /**
     *
     * @var string
     */
    public $subject;

    public function clear()
    {
        parent::clear();
        $this->creationdate = \date(self::DATE_STYLE);
        $this->enabled = true;
    }

    /**
     * 
     * @return string
     */
    public static function primaryColumn(): string
    {
        return 'name';
    }

    /**
     * 
     * @return string
     */
    public static function tableName(): string
    {
        return 'emails_notifications';
    }

    /**
     * 
     * @return bool
     */
    public function test()
    {
        $this->name = $this->toolBox()->utils()->noHtml($this->name);
        $this->subject = $this->toolBox()->utils()->noHtml($this->subject);
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
    public function url(string $type = 'auto', string $list = 'ConfigEmail?activetab=List')
    {
        return parent::url($type, $list);
    }
}
