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

use Closure;
use Exception;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
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

    /** @var bool */
    public $failed;

    /** @var int */
    public $id;

    /** @var string */
    public $jobname;

    /** @var bool */
    private $overlapping = false;

    /** @var string */
    public $pluginname;

    /** @var bool */
    private $ready = false;

    /** @var float */
    private $start;

    public function clear()
    {
        parent::clear();
        $this->date = Tools::dateTime();
        $this->done = false;
        $this->duration = 0.0;
        $this->enabled = true;
        $this->failed = false;
    }

    public function every(string $period): self
    {
        if (false === $this->enabled) {
            $this->ready = false;
            return $this;
        }

        if (false === $this->exists()) {
            $this->ready = true;
            return $this;
        }

        $this->start = microtime(true);
        if (strtotime($this->date) <= strtotime('-' . $period)) {
            $this->ready = true;
            return $this;
        }

        $this->ready = false;
        return $this;
    }

    public function everyDay(int $day, int $hour, bool $strict = false): self
    {
        $date = date('Y-m-' . $day);
        return $this->everyDayAux($date, $hour, $strict);
    }

    public function everyDayAt(int $hour, bool $strict = false): self
    {
        return $this->everyDayAux('today', $hour, $strict);
    }

    public function everyFridayAt(int $hour, bool $strict = false): self
    {
        return $this->everyDayAux('friday', $hour, $strict);
    }

    public function everyLastDayOfMonthAt(int $hour, bool $strict = false): self
    {
        return $this->everyDayAux('last day of this month', $hour, $strict);
    }

    public function everyMondayAt(int $hour, bool $strict = false): self
    {
        return $this->everyDayAux('monday', $hour, $strict);
    }

    public function everySaturdayAt(int $hour, bool $strict = false): self
    {
        return $this->everyDayAux('saturday', $hour, $strict);
    }

    public function everySundayAt(int $hour, bool $strict = false): self
    {
        return $this->everyDayAux('sunday', $hour, $strict);
    }

    public function everyThursdayAt(int $hour, bool $strict = false): self
    {
        return $this->everyDayAux('thursday', $hour, $strict);
    }

    public function everyTuesdayAt(int $hour, bool $strict = false): self
    {
        return $this->everyDayAux('tuesday', $hour, $strict);
    }

    public function everyWednesdayAt(int $hour, bool $strict = false): self
    {
        return $this->everyDayAux('wednesday', $hour, $strict);
    }

    public function isReady(): bool
    {
        return $this->ready && false === $this->overlapping;
    }

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public function run(Closure $function): bool
    {
        if (false === $this->isReady()) {
            return false;
        }

        $this->start = microtime(true);
        $this->done = false;
        $this->failed = false;
        $this->duration = 0.0;
        $this->date = Tools::dateTime();
        if (false === $this->save()) {
            Tools::log('cron')->error('Error saving cronjob', [
                'jobname' => $this->jobname,
                'pluginname' => $this->pluginname,
            ]);
            return false;
        }

        try {
            $function();
        } catch (Exception $e) {
            Tools::log('cron')->error($e->getMessage(), [
                'jobname' => $this->jobname,
                'pluginname' => $this->pluginname,
            ]);

            $this->duration = round(microtime(true) - $this->start, 5);
            $this->done = true;
            $this->failed = true;
            $this->save();
            return false;
        }

        $this->duration = round(microtime(true) - $this->start, 5);
        $this->done = true;
        $this->save();
        return true;
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

    public function withoutOverlapping(...$jobs): self
    {
        // comprobamos la lista de trabajos en ejecución
        $whereRunning = [
            new DataBaseWhere('done', false),
            new DataBaseWhere('enabled', true),
        ];

        if (count($jobs) > 0) {
            $whereRunning[] = new DataBaseWhere('jobname', implode(',', $jobs), 'IN');
        } else {
            $whereRunning[] = new DataBaseWhere('jobname', $this->jobname, '!=');
        }

        $this->overlapping = $this->count($whereRunning) > 0;

        return $this;
    }

    private function everyDayAux(string $day, int $hour, bool $strict): self
    {
        if (false === $this->enabled) {
            $this->ready = false;
            return $this;
        }

        if (false === $this->exists()) {
            $this->ready = true;
            return $this;
        }

        // si strict es true, solamente devolvemos true si es la hora exacta
        $end = $strict ?
            strtotime($day . ' +' . $hour . ' hours +59 minutes') :
            strtotime($day . ' +23 hours +59 minutes');

        // devolvemos true si la última ejecución es anterior a hoy a la hora indicada
        $last = strtotime($this->date);
        $start = strtotime($day . ' +' . $hour . ' hours');
        $this->start = microtime(true);
        if ($last <= $start && $this->start >= $start && $this->start <= $end) {
            $this->ready = true;
            return $this;
        }

        $this->ready = false;
        return $this;
    }
}
