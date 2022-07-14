<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

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
     * @var bool
     */
    public $opened;

    /**
     * Subject of email
     *
     * @var string
     */
    public $subject;

    /**
     * @var string
     */
    public $verificode;

    public function clear()
    {
        parent::clear();
        $this->date = date(self::DATETIME_STYLE);
        $this->opened = false;
    }

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'emails_sent';
    }

    public function test(): bool
    {
        $utils = $this->toolBox()->utils();
        $body = $utils->noHtml($this->body);
        $this->body = strlen($body) > 5000 ? substr($body, 0, 4997) . '...' : $body;
        $this->subject = $utils->noHtml($this->subject);
        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'ConfigEmail?activetab=List'): string
    {
        return parent::url($type, $list);
    }

    public static function verify(string $verificode, string $addressee = ''): bool
    {
        $found = false;

        $model = new static();
        $where = [new DataBaseWhere('verificode', $verificode)];
        if (!empty($addressee)) {
            $where[] = new DataBaseWhere('addressee', $addressee);
        }

        foreach ($model->all($where) as $item) {
            $item->opened = true;
            $item->save();

            $found = true;
        }

        return $found;
    }
}
