<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\Base\ModelTrait;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Tools;

class WorkEvent extends ModelClass
{
    use ModelTrait;

    /** @var string */
    public $creation_date;

    /** @var bool */
    public $done;

    /** @var string */
    public $done_date;

    /** @var int */
    public $id;

    /** @var string */
    public $name;

    /** @var string */
    public $nick;

    /** @var string */
    protected $params;

    /** @var string */
    public $value;

    /** @var int */
    public $workers;

    /** @var string */
    public $worker_list;

    public function clear()
    {
        parent::clear();
        $this->creation_date = Tools::dateTime();
        $this->done = false;
        $this->nick = Session::user()->nick;
        $this->workers = 0;
        $this->worker_list = '';
    }

    public function delete(): bool
    {
        // si existe un archivo con los parámetros, lo eliminamos
        $filePath = Tools::folder('MyFiles', 'Tmp', 'work-event-' . $this->id . '-params.json');
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        return parent::delete();
    }

    public function getHash(): string
    {
        return md5($this->name . $this->value . json_encode($this->params()));
    }

    public function param(string $key, $default = null)
    {
        $params = $this->params();
        return $params[$key] ?? $default;
    }

    public function params(): array
    {
        // si los parámetros están guardados en un archivo, los leemos
        if (strpos($this->params, 'file:') === 0) {
            $filePath = substr($this->params, 5);
            if (file_exists($filePath)) {
                $this->params = file_get_contents($filePath);
            } else {
                Tools::log()->error('Error reading params from file');
                return [];
            }
        }

        return empty($this->params) ? [] : json_decode($this->params, true);
    }

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public function save(): bool
    {
        // si los parámetros son muy grandes, los guardamos en un archivo
        if (strlen($this->params) > 5000 && !$this->saveParamsToFile()) {
            Tools::log()->error('Error saving params on file');
            return false;
        }

        return parent::save();
    }

    public function setParams(array $params): void
    {
        $this->params = json_encode($params);
    }

    public static function tableName(): string
    {
        return 'work_events';
    }

    public function test(): bool
    {
        $this->name = Tools::noHtml($this->name);
        $this->value = Tools::noHtml($this->value);
        $this->worker_list = Tools::noHtml($this->worker_list);

        // si los parámetros no son un string, devolvemos false
        if (!is_string($this->params)) {
            return false;
        }

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'ListLogMessage?activetab=List'): string
    {
        return parent::url($type, $list);
    }

    private function saveParamsToFile(): bool
    {
        $filePath = Tools::folder('MyFiles', 'Tmp', 'work-event-' . $this->id . '-params.json');
        if (file_put_contents($filePath, $this->params) === false) {
            return false;
        }

        $this->params = 'file:' . $filePath;
        return true;
    }
}
