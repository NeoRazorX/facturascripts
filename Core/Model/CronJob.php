<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Kernel;
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

    /** @var int */
    const STALE_HOURS = 6;

    /** Fecha y hora de la última ejecución del trabajo. @var string */
    public $date;

    /** Número de ejecuciones completadas durante el día actual. @var int */
    public $daily_exec;

    /** Indica si la ejecución del trabajo ha finalizado. @var bool */
    public $done;

    /** Duración en segundos de la última ejecución. @var float */
    public $duration;

    /** Indica si el trabajo programado está habilitado. @var bool */
    public $enabled;

    /** Indica si la última ejecución terminó con un error. @var bool */
    public $failed;

    /** Número acumulado de ejecuciones fallidas. @var int */
    public $fails;

    /** Identificador único del trabajo programado. @var int */
    public $id;

    /** Nombre identificativo del trabajo programado. @var string */
    public $jobname;

    /** Duración en segundos de la ejecución anterior a la última. @var float */
    public $last_duration;

    /** Tiempo máximo permitido para el conjunto del cron, en segundos. @var int */
    private static $max_execution_time = 0;

    /** Indica si el exceso de tiempo máximo ya se registró en el log. @var bool */
    private static $max_execution_time_logged = false;

    /** Fecha y hora simuladas utilizadas durante los tests. @var string|null */
    private $mock_date_time;

    /** Marca de tiempo simulada utilizada durante los tests. @var float|null */
    private $mock_microtime;

    /** Indica si se ha detectado otra ejecución incompatible en curso. @var bool */
    private $overlapping = false;

    /** Nombre del plugin propietario del trabajo programado. @var string */
    public $pluginname;

    /** Indica si el trabajo debe ejecutarse según su programación. @var bool */
    private $ready = false;

    /** Número de ejecuciones simultáneas actualmente en curso. @var int */
    public $running;

    /** Marca de tiempo utilizada para medir el inicio de la ejecución. @var float */
    private $start;

    /**
     * Restablece los valores por defecto de todas las propiedades del modelo.
     */
    public function clear(): void
    {
        parent::clear();
        $this->daily_exec = 0;
        $this->done = false;
        $this->duration = 0.0;
        $this->enabled = true;
        $this->failed = false;
        $this->fails = 0;
        $this->last_duration = 0.0;
        $this->running = 0;
    }

    /**
     * Elimina el límite de tiempo máximo de ejecución del cron.
     */
    public static function clearMaxExecutionTime(): void
    {
        self::$max_execution_time = 0;
        self::$max_execution_time_logged = false;
    }

    /**
     * Elimina la fecha y el tiempo simulados que se usan en los tests,
     * volviendo al tiempo real.
     */
    public function clearMocks(): void
    {
        $this->mock_date_time = null;
        $this->mock_microtime = null;
    }

    /**
     * Marca el job como listo si ha pasado el periodo indicado desde su última ejecución.
     * Ejemplo: $job->every('6 hours')->run(...);
     *
     * @param string $period Periodo en formato strtotime: '1 hour', '30 minutes', '2 days', etc.
     *
     * @return static
     */
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

        $this->start = $this->getCurrentMicrotime();
        if (strtotime($this->date) <= strtotime('-' . $period)) {
            $this->ready = true;
            return $this;
        }

        $this->ready = false;
        return $this;
    }

    /**
     * Marca el job como listo el día indicado de cada mes a partir de la hora señalada.
     *
     * @param int $day Día del mes (1 a 31).
     * @param int $hour Hora del día (0 a 23).
     * @param bool $strict Si es true, solo se ejecuta dentro de la hora programada.
     *                     Si es false, recupera ejecuciones perdidas (estilo anacron).
     *
     * @return static
     */
    public function everyDay(int $day, int $hour, bool $strict = false): self
    {
        $date = date('Y-m-' . $day, $this->getCurrentTimestamp());
        return $this->everyDayAux($date, $hour, $strict, '1 month');
    }

    /**
     * Marca el job como listo cada día a partir de la hora indicada.
     *
     * @param int $hour Hora del día (0 a 23).
     * @param bool $strict Si es true, solo se ejecuta dentro de la hora programada.
     *                     Si es false, recupera ejecuciones perdidas (estilo anacron).
     *
     * @return static
     */
    public function everyDayAt(int $hour, bool $strict = false): self
    {
        $date = date('Y-m-d', $this->getCurrentTimestamp());
        return $this->everyDayAux($date, $hour, $strict, '1 day');
    }

    /**
     * Marca el job como listo cada viernes a partir de la hora indicada.
     *
     * @param int $hour Hora del día (0 a 23).
     * @param bool $strict Si es true, solo se ejecuta dentro de la hora programada.
     *                     Si es false, recupera ejecuciones perdidas (estilo anacron).
     *
     * @return static
     */
    public function everyFridayAt(int $hour, bool $strict = false): self
    {
        $date = date('Y-m-d', strtotime('friday', $this->getCurrentTimestamp()));
        return $this->everyDayAux($date, $hour, $strict, '7 days');
    }

    /**
     * Marca el job como listo el último día de cada mes a partir de la hora indicada.
     *
     * @param int $hour Hora del día (0 a 23).
     * @param bool $strict Si es true, solo se ejecuta dentro de la hora programada.
     *                     Si es false, recupera ejecuciones perdidas (estilo anacron).
     *
     * @return static
     */
    public function everyLastDayOfMonthAt(int $hour, bool $strict = false): self
    {
        $date = date('Y-m-d', strtotime('last day of this month', $this->getCurrentTimestamp()));

        // si todavía no toca la de este mes, usamos el último día del mes anterior,
        // porque restar un mes a una fecha de fin de mes da fechas incorrectas
        if (false === $strict && $this->getCurrentTimestamp() < strtotime($date . ' +' . $hour . ' hours')) {
            $date = date('Y-m-d', strtotime('last day of last month', $this->getCurrentTimestamp()));
        }

        return $this->everyDayAux($date, $hour, $strict, '1 month');
    }

    /**
     * Marca el job como listo cada lunes a partir de la hora indicada.
     *
     * @param int $hour Hora del día (0 a 23).
     * @param bool $strict Si es true, solo se ejecuta dentro de la hora programada.
     *                     Si es false, recupera ejecuciones perdidas (estilo anacron).
     *
     * @return static
     */
    public function everyMondayAt(int $hour, bool $strict = false): self
    {
        $date = date('Y-m-d', strtotime('monday', $this->getCurrentTimestamp()));
        return $this->everyDayAux($date, $hour, $strict, '7 days');
    }

    /**
     * Marca el job como listo cada sábado a partir de la hora indicada.
     *
     * @param int $hour Hora del día (0 a 23).
     * @param bool $strict Si es true, solo se ejecuta dentro de la hora programada.
     *                     Si es false, recupera ejecuciones perdidas (estilo anacron).
     *
     * @return static
     */
    public function everySaturdayAt(int $hour, bool $strict = false): self
    {
        $date = date('Y-m-d', strtotime('saturday', $this->getCurrentTimestamp()));
        return $this->everyDayAux($date, $hour, $strict, '7 days');
    }

    /**
     * Marca el job como listo cada domingo a partir de la hora indicada.
     *
     * @param int $hour Hora del día (0 a 23).
     * @param bool $strict Si es true, solo se ejecuta dentro de la hora programada.
     *                     Si es false, recupera ejecuciones perdidas (estilo anacron).
     *
     * @return static
     */
    public function everySundayAt(int $hour, bool $strict = false): self
    {
        $date = date('Y-m-d', strtotime('sunday', $this->getCurrentTimestamp()));
        return $this->everyDayAux($date, $hour, $strict, '7 days');
    }

    /**
     * Marca el job como listo cada jueves a partir de la hora indicada.
     *
     * @param int $hour Hora del día (0 a 23).
     * @param bool $strict Si es true, solo se ejecuta dentro de la hora programada.
     *                     Si es false, recupera ejecuciones perdidas (estilo anacron).
     *
     * @return static
     */
    public function everyThursdayAt(int $hour, bool $strict = false): self
    {
        $date = date('Y-m-d', strtotime('thursday', $this->getCurrentTimestamp()));
        return $this->everyDayAux($date, $hour, $strict, '7 days');
    }

    /**
     * Marca el job como listo cada martes a partir de la hora indicada.
     *
     * @param int $hour Hora del día (0 a 23).
     * @param bool $strict Si es true, solo se ejecuta dentro de la hora programada.
     *                     Si es false, recupera ejecuciones perdidas (estilo anacron).
     *
     * @return static
     */
    public function everyTuesdayAt(int $hour, bool $strict = false): self
    {
        $date = date('Y-m-d', strtotime('tuesday', $this->getCurrentTimestamp()));
        return $this->everyDayAux($date, $hour, $strict, '7 days');
    }

    /**
     * Marca el job como listo cada miércoles a partir de la hora indicada.
     *
     * @param int $hour Hora del día (0 a 23).
     * @param bool $strict Si es true, solo se ejecuta dentro de la hora programada.
     *                     Si es false, recupera ejecuciones perdidas (estilo anacron).
     *
     * @return static
     */
    public function everyWednesdayAt(int $hour, bool $strict = false): self
    {
        $date = date('Y-m-d', strtotime('wednesday', $this->getCurrentTimestamp()));
        return $this->everyDayAux($date, $hour, $strict, '7 days');
    }

    /**
     * Marca el job como listo una vez al año, el día y mes indicados, a partir de la hora señalada.
     *
     * @param int $month Mes del año (1 a 12).
     * @param int $day Día del mes (1 a 31).
     * @param int $hour Hora del día (0 a 23).
     * @param bool $strict Si es true, solo se ejecuta dentro de la hora programada.
     *                     Si es false, recupera ejecuciones perdidas (estilo anacron).
     *
     * @return static
     */
    public function everyYearAt(int $month, int $day, int $hour, bool $strict = false): self
    {
        $currentYear = date('Y', $this->getCurrentTimestamp());
        $date = sprintf('%s-%02d-%02d', $currentYear, $month, $day);
        return $this->everyDayAux($date, $hour, $strict, '1 year');
    }

    /**
     * Devuelve el tiempo máximo de ejecución del cron en segundos, 0 si no hay límite.
     *
     * @return int
     */
    public static function getMaxExecutionTime(): int
    {
        return self::$max_execution_time;
    }

    /**
     * Devuelve true si se ha superado el tiempo máximo de ejecución del cron,
     * en cuyo caso los jobs pendientes se rechazan hasta la siguiente ejecución.
     *
     * @return bool
     */
    public static function isMaxExecutionTimeReached(): bool
    {
        if (self::$max_execution_time <= 0) {
            return false;
        }

        if (Kernel::getExecutionTime() <= self::$max_execution_time) {
            return false;
        }

        // lo registramos en el log solamente una vez
        if (false === self::$max_execution_time_logged) {
            self::$max_execution_time_logged = true;
            Tools::log('cron')->notice('cron-max-execution-time-reached', [
                '%seconds%' => self::$max_execution_time,
            ]);
        }

        return true;
    }

    /**
     * Devuelve true si el job debe ejecutarse: le toca según su programación (every*),
     * no hay solapamiento (withoutOverlapping) y no se ha superado el tiempo
     * máximo de ejecución del cron.
     *
     * @return bool
     */
    public function isReady(): bool
    {
        if (self::isMaxExecutionTimeReached()) {
            return false;
        }

        return $this->ready && false === $this->overlapping;
    }

    /**
     * Si el job lleva más de STALE_HOURS horas en ejecución, se considera un
     * proceso zombie (murió sin liberar el contador) y se libera.
     *
     * @return bool True si el job estaba zombie y se ha liberado.
     */
    public function releaseIfStale(): bool
    {
        if ($this->running <= 0) {
            return false;
        }

        if (strtotime($this->date) >= $this->getCurrentTimestamp() - (self::STALE_HOURS * 3600)) {
            return false;
        }

        Tools::log('cron')->warning('cron-stale-job-released', [
            '%jobName%' => $this->jobname,
        ]);

        $this->running = 0;
        $this->done = true;
        $this->failed = true;
        $this->fails++;
        return $this->save();
    }

    /**
     * Ejecuta la función si el job está listo (isReady), guardando fecha, duración
     * y resultado. Captura cualquier excepción o error, lo registra en el log y
     * marca el job como fallido.
     *
     * @param Closure $function Función a ejecutar.
     *
     * @return bool True si se ha ejecutado sin errores.
     */
    public function run(Closure $function): bool
    {
        if (false === $this->isReady()) {
            return false;
        }

        if (date('Y-m-d', strtotime($this->date ?? '-1 day')) !== date('Y-m-d', $this->getCurrentTimestamp())) {
            $this->daily_exec = 0;
        }

        $this->start = $this->getCurrentMicrotime();
        $this->done = false;
        $this->failed = false;
        $this->running++;
        $this->last_duration = $this->duration;
        $this->duration = 0.0;
        $this->date = $this->getCurrentDateTime();
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
                'file' => defined('FS_FOLDER') ? str_replace(FS_FOLDER, '', $e->getFile()) : $e->getFile(),
                'line' => $e->getLine(),
            ];

            if ($e instanceof Error) {
                $logData['type'] = 'fatal_error';
            }

            Tools::log('cron')->critical($e->getMessage(), $logData);

            $start = $this->start;
            $this->reload();
            $this->start = $start;

            $this->duration = round($this->getCurrentMicrotime() - $this->start, 5);
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

        $this->duration = round($this->getCurrentMicrotime() - $this->start, 5);
        $this->done = true;
        $this->failed = false;
        $this->running--;
        $this->daily_exec++;
        $this->save();

        return true;
    }

    /**
     * Define el tiempo máximo de ejecución del cron en su conjunto. Una vez superado,
     * los jobs pendientes se rechazan y se ejecutarán en las siguientes pasadas del cron.
     * No interrumpe el job en ejecución. Se puede llamar desde el Init de un plugin.
     *
     * @param int $seconds Límite en segundos. Si varios plugins definen límites
     *                     distintos, se aplica el más restrictivo.
     */
    public static function setMaxExecutionTime(int $seconds): void
    {
        // si varios plugins definen límites distintos, gana el más restrictivo
        if ($seconds > 0 && (self::$max_execution_time === 0 || $seconds < self::$max_execution_time)) {
            self::$max_execution_time = $seconds;
        }
    }

    /**
     * Establece una fecha y hora simuladas para los tests.
     *
     * @param string|null $dateTime Fecha y hora simuladas, null para volver al tiempo real.
     * @param bool $update_microtime Si es true, también simula el microtime con esa fecha.
     */
    public function setMockDateTime(?string $dateTime, bool $update_microtime = true): void
    {
        $this->mock_date_time = $dateTime;

        if ($update_microtime) {
            $this->mock_microtime = strtotime($dateTime);
        }
    }

    /**
     * Establece un microtime simulado para los tests.
     *
     * @param float|null $microtime Timestamp con decimales, null para volver al tiempo real.
     */
    public function setMockMicrotime(?float $microtime): void
    {
        $this->mock_microtime = $microtime;
    }

    /**
     * Devuelve el nombre de la tabla en la base de datos.
     *
     * @return string
     */
    public static function tableName(): string
    {
        return 'cronjobs';
    }

    /**
     * Valida y sanea los datos del modelo antes de guardar.
     *
     * @return bool
     */
    public function test(): bool
    {
        $this->jobname = Tools::noHtml($this->jobname);

        // normalizamos el nombre del plugin a null si está vacío, ya que el cron
        // busca los jobs del core con pluginname IS NULL
        $this->pluginname = empty($this->pluginname) ? null : Tools::noHtml($this->pluginname);

        if (empty($this->date)) {
            $this->date = $this->getCurrentDateTime();
        }

        if ($this->running < 0) {
            $this->running = 0;
        } elseif ($this->running > 0) {
            $this->done = false;
        }

        return parent::test();
    }

    /**
     * Devuelve la url del registro o de su listado.
     *
     * @param string $type Tipo de url: 'auto', 'edit', 'list' o 'new'.
     * @param string $list Controlador del listado.
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'ListLogMessage?activetab=List'): string
    {
        return parent::url($type, $list);
    }

    /**
     * Impide que el job se ejecute mientras haya otros jobs en ejecución.
     * Ejemplo: $job->everyDayAt(2)->withoutOverlapping()->run(...);
     *
     * @param string ...$jobs Nombres de los jobs con los que no debe solaparse.
     *                        Si no se indica ninguno, se comprueban todos los demás.
     *
     * @return static
     */
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

    /**
     * Lógica común de los métodos every*: calcula si el job está listo comparando
     * su última ejecución con la fecha y hora programadas.
     *
     * @param string $date Fecha programada (Y-m-d).
     * @param int $hour Hora del día (0 a 23).
     * @param bool $strict Si es true, solo dentro de la hora programada; si es false,
     *                     recupera ejecuciones perdidas (estilo anacron).
     * @param string $period Periodicidad en formato strtotime: '1 day', '7 days', '1 month', etc.
     *
     * @return static
     */
    private function everyDayAux(string $date, int $hour, bool $strict, string $period = '1 day'): self
    {
        if (false === $this->enabled) {
            $this->ready = false;
            return $this;
        }

        $last = strtotime($this->date ?? '-99 years');
        $start = strtotime($date . ' +' . $hour . ' hours');
        $this->start = $this->getCurrentMicrotime();

        // en modo estricto solamente se ejecuta dentro de la hora programada
        if ($strict) {
            $end = strtotime($date . ' +' . $hour . ' hours +59 minutes');
            $this->ready = $last < $start && $this->start >= $start && $this->start <= $end;
            return $this;
        }

        // si todavía no ha llegado la hora programada, comprobamos la ocurrencia anterior,
        // para así recuperar ejecuciones perdidas (estilo anacron)
        if ($this->start < $start) {
            $start = strtotime($date . ' +' . $hour . ' hours -' . $period);
        }

        // se ejecuta si no se ha ejecutado desde la última hora programada
        $this->ready = $last < $start && $this->start >= $start;
        return $this;
    }

    /**
     * Devuelve la fecha y hora actuales, o las simuladas si se han establecido para los tests.
     *
     * @param string|null $date Fecha a formatear; null para la actual.
     *
     * @return string
     */
    protected function getCurrentDateTime(?string $date = null): string
    {
        if ($this->mock_date_time !== null && $date === null) {
            return $this->mock_date_time;
        }

        return Tools::dateTime($date);
    }

    /**
     * Devuelve el microtime actual, o el simulado si se ha establecido para los tests.
     *
     * @return float
     */
    protected function getCurrentMicrotime(): float
    {
        if ($this->mock_microtime !== null) {
            return $this->mock_microtime;
        }

        return microtime(true);
    }

    /**
     * Devuelve el timestamp actual, o el simulado si se ha establecido para los tests.
     *
     * @return int
     */
    protected function getCurrentTimestamp(): int
    {
        if ($this->mock_microtime !== null) {
            return (int)$this->mock_microtime;
        }

        return time();
    }
}
