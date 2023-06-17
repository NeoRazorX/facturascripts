<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Tools;

/**
 * Class to store log information when a plugin is executed from cron.
 *
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 * @author Francesc Pineda Segarra  <francesc.pineda@x-netdigital.com>
 */
class CronJob extends ModelClass
{
    use ModelTrait;

    /** @var string */
    public $date;

    /** @var bool */
    public $done;

    /** @var float */
    public $duration;

    /** @var bool */
    public $enabled;

    /** @var int */
    public $id;

    /** @var string */
    public $jobname;

    /** @var string */
    public $pluginname;

    /** @var int */
    private $start;

    public function clear()
    {
        parent::clear();
        $this->date = Tools::dateTime();
        $this->done = false;
        $this->duration = 0.0;
        $this->enabled = true;
    }

    public function done(): bool
    {
        $this->done = true;
        $this->duration = round(microtime(true) - $this->start, 5);
        $this->date = Tools::dateTime($this->start);
        return $this->save();
    }

    public function every(string $period): bool
    {
        if (false === $this->enabled) {
            return false;
        }

        if (false === $this->exists()) {
            return true;
        }

        $this->start = microtime(true);
        return strtotime($this->date) <= strtotime('-' . $period);
    }

    public function everyDay(int $day, int $hour, bool $strict = false): bool
    {
        $date = date('Y-m-' . $day);
        return $this->everyDayAux($date, $hour, $strict);
    }

    public function everyDayAt(int $hour, bool $strict = false): bool
    {
        return $this->everyDayAux('today', $hour, $strict);
    }

    public function everyFridayAt(int $hour, bool $strict): bool
    {
        return $this->everyDayAux('friday', $hour, $strict);
    }

    public function everyLastDayOfMonthAt(int $hour, bool $strict): bool
    {
        return $this->everyDayAux('last day of this month', $hour, $strict);
    }

    public function everyMondayAt(int $hour, bool $strict): bool
    {
        return $this->everyDayAux('monday', $hour, $strict);
    }

    public function everySaturdayAt(int $hour, bool $strict): bool
    {
        return $this->everyDayAux('saturday', $hour, $strict);
    }

    public function everySundayAt(int $hour, bool $strict): bool
    {
        return $this->everyDayAux('sunday', $hour, $strict);
    }

    public function everyThursdayAt(int $hour, bool $strict): bool
    {
        return $this->everyDayAux('thursday', $hour, $strict);
    }

    public function everyTuesdayAt(int $hour, bool $strict): bool
    {
        return $this->everyDayAux('tuesday', $hour, $strict);
    }

    public function everyWednesdayAt(int $hour, bool $strict): bool
    {
        return $this->everyDayAux('wednesday', $hour, $strict);
    }

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'cronjobs';
    }

    public function test(): bool
    {
        $this->jobname = Tools::noHtml($this->jobname);
        $this->pluginname = Tools::noHtml($this->pluginname);

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'ListLogMessage?activetab=List'): string
    {
        return parent::url($type, $list);
    }

    private function everyDayAux(string $day, int $hour, bool $strict): bool
    {
        if (false === $this->enabled) {
            return false;
        }

        if (false === $this->exists()) {
            return true;
        }

        // si strict es true, solamente devolvemos true si es la hora exacta
        $end = $strict ?
            strtotime($day . ' +' . $hour . ' hours +59 minutes') :
            strtotime($day . ' +23 hours +59 minutes');

        // devolvemos true si la última ejecución es anterior a hoy a la hora indicada
        $last = strtotime($this->date);
        $start = strtotime($day . ' +' . $hour . ' hours');
        $this->start = microtime(true);
        return $last <= $start && $this->start >= $start && $this->start <= $end;
    }
}
