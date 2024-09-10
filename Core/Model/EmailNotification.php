<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Tools;

/**
 * Description of EmailNotification
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class EmailNotification extends Base\ModelClass
{
    use Base\ModelTrait;

    /**
     * @var string
     */
    public $body;

    /**
     * @var string
     */
    public $creationdate;

    /**
     * @var bool
     */
    public $enabled;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $subject;

    public function clear()
    {
        parent::clear();
        $this->creationdate = Tools::date();
        $this->enabled = true;
    }

    public static function primaryColumn(): string
    {
        return 'name';
    }

    public static function tableName(): string
    {
        return 'emails_notifications';
    }

    public function test(): bool
    {
        $this->name = Tools::noHtml($this->name);
        $this->subject = Tools::noHtml($this->subject);

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'ConfigEmail?activetab=List'): string
    {
        return parent::url($type, $list);
    }
}
