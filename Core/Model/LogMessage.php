<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

    public function clear()
    {
        parent::clear();
        $this->time = Tools::dateTime();
    }

    /**
     * Returns the saved context as array.
     *
     * @return array
     */
    public function context(): array
    {
        return json_decode(Tools::fixHtml($this->context), true);
    }

    public function delete(): bool
    {
        if ($this->channel === self::AUDIT_CHANNEL) {
            Tools::log()->warning('cant-delete-audit-log');
            return false;
        }

        return parent::delete();
    }

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'logs';
    }

    public function test(): bool
    {
        $this->channel = Tools::noHtml($this->channel);
        $this->context = Tools::noHtml($this->context);
        $this->message = Tools::noHtml($this->message);
        if (strlen($this->message) > static::MAX_MESSAGE_LEN) {
            $this->message = substr($this->message, 0, static::MAX_MESSAGE_LEN);
        }

        $this->model = Tools::noHtml($this->model);
        $this->modelcode = Tools::noHtml($this->modelcode);
        $this->uri = Tools::noHtml($this->uri);

        return parent::test();
    }

    protected function saveUpdate(array $values = []): bool
    {
        if ($this->channel === self::AUDIT_CHANNEL) {
            Tools::log()->warning('cant-update-audit-log');
            return false;
        }

        return parent::saveUpdate($values);
    }
}
