<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use Error;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use Throwable;

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
    public $fails;

    /** @var int */
    public $id;

    /** @var string */
    public $jobname;

    /** @var float */
    public $last_duration;

    /** @var bool */
    private $overlapping = false;

    /** @var string */
    public $pluginname;

    /** @var bool */
    private $ready = false;

    /** @var int */
    public $running;

    /** @var float */
    private $start;

    public function clear(): void
    {
        parent::clear();
        $this->date = Tools::dateTime();
        $this->done = false;
        $this->duration = 0.0;
        $this->enabled = true;
        $this->failed = false;
        $this->fails = 0;
        $this->last_duration = 0.0;
        $this->running = 0;
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

    public function run(Closure $function): bool
    {
        if (false === $this->isReady()) {
            return false;
        }

        $this->start = microtime(true);
        $this->done = false;
        $this->failed = false;
        $this->running++;
        $this->last_duration = $this->duration;
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
        } catch (Throwable $e) {
            $logData = [
                'jobname' => $this->jobname,
                'pluginname' => $this->pluginname,
            ];

            if ($e instanceof Error) {
                $logData['type'] = 'fatal_error';
            }

            Tools::log('cron')->critical($e->getMessage(), $logData);

            $start = $this->start;
            $this->reload();
            $this->start = $start;

            $this->duration = round(microtime(true) - $this->start, 5);
            $this->done = true;
            $this->failed = true;
            $this->fails++;
            $this->running--;
            $this->save();

            return false;
        }

        $start = $this->start;
        $this->reload();
        $this->start = $start;

        $this->duration = round(microtime(true) - $this->start, 5);
        $this->done = true;
        $this->failed = false;
        $this->running--;
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

        if ($this->running < 0) {
            $this->running = 0;
        } elseif ($this->running > 0) {
            $this->done = false;
        }

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
            Where::eq('done', false),
            Where::eq('enabled', true),
        ];

        $whereRunning[] = count($jobs) > 0 ?
            Where::in('jobname', $jobs) :
            Where::notEq('jobname', $this->jobname);

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
