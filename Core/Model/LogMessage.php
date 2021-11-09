<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\ToolBox;

/**
 * Model to persist data from logs.
 *
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 * @author Francesc Pineda Segarra  <francesc.pineda.segarra@gmail.com>
 */
class LogMessage extends Base\ModelClass
{

    use Base\ModelTrait;

    const MAX_MESSAGE_LEN = 3000;

    /**
     * @var string
     */
    public $channel;

    /**
     * @var string
     */
    public $context;

    /**
     * Primary key.
     *
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $idcontacto;

    /**
     * IP address.
     *
     * @var string
     */
    public $ip;

    /**
     * The type level of message.
     *
     * @var string
     */
    public $level;

    /**
     * The message.
     *
     * @var string
     */
    public $message;

    /**
     * @var string
     */
    public $model;

    /**
     * @var string
     */
    public $modelcode;

    /**
     * User nick.
     *
     * @var string
     */
    public $nick;

    /**
     * When was generated the message
     *
     * @var string
     */
    public $time;

    /**
     * @var string
     */
    public $uri;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->time = date(self::DATETIME_STYLE);
    }

    /**
     * Returns the saved context as array.
     *
     * @return array
     */
    public function context(): array
    {
        return json_decode(ToolBox::utils()::fixHtml($this->context), true);
    }

    /**
     * @return bool
     */
    public function delete()
    {
        if ($this->channel === self::AUDIT_CHANNEL) {
            self::toolBox()::i18nLog()->warning('cant-delete-audit-log');
            return false;
        }

        return parent::delete();
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
        return 'logs';
    }

    /**
     * Returns True if there are no errors on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $utils = $this->toolBox()->utils();
        $this->channel = $utils->noHtml($this->channel);
        $this->context = $utils->noHtml($this->context);
        $this->message = $utils->noHtml($this->message);
        if (strlen($this->message) > static::MAX_MESSAGE_LEN) {
            $this->message = substr($this->message, 0, static::MAX_MESSAGE_LEN);
        }

        $this->model = $utils->noHtml($this->model);
        $this->modelcode = $utils->noHtml($this->modelcode);
        $this->uri = $utils->noHtml($this->uri);
        return parent::test();
    }

    /**
     * @param array $values
     *
     * @return bool
     */
    protected function saveUpdate(array $values = [])
    {
        if ($this->channel === self::AUDIT_CHANNEL) {
            self::toolBox()::i18nLog()->warning('cant-update-audit-log');
            return false;
        }

        return parent::saveUpdate($values);
    }
}
