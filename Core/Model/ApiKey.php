<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * ApiKey model to manage the connection tokens through the api
 * that will be generated to synchronize different applications.
 *
 * @author Joe Nilson           <joenilson at gmail.com>
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class ApiKey extends Base\ModelClass
{
    use Base\ModelTrait;

    /** @var string */
    public $apikey;

    /** @var string */
    public $creationdate;

    /** @var string */
    public $description;

    /** @var bool */
    public $enabled;

    /** @var bool */
    public $fullaccess;

    /** @var int */
    public $id;

    /** @var string */
    public $nick;

    public function clear()
    {
        parent::clear();
        $this->apikey = Tools::randomString(20);
        $this->creationdate = Tools::date();
        $this->enabled = true;
        $this->fullaccess = false;
    }

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public function primaryDescriptionColumn(): string
    {
        return 'description';
    }

    public static function tableName(): string
    {
        return 'api_keys';
    }

    public function test(): bool
    {
        // escapamos el html
        $this->apikey = Tools::noHtml($this->apikey);
        $this->description = Tools::noHtml($this->description);
        $this->nick = Tools::noHtml($this->nick);

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'EditSettings?activetab=List'): string
    {
        return parent::url($type, $list);
    }
}
