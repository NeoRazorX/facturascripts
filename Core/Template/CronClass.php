<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Template;

use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\CronJob;

/**
 * Clase base para el cron de los plugins. Cada plugin puede tener una clase Cron
 * en su raíz que extienda de esta, y su método run() se ejecutará en cada
 * pasada del cron.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class CronClass
{
    /** @var string */
    public $pluginName;

    /**
     * Punto de entrada del cron del plugin. Aquí se definen y ejecutan los jobs.
     * Ejemplo: $this->job('mi-job')->everyDayAt(3)->run(function() { ... });
     */
    abstract public function run(): void;

    /**
     * @param string $pluginName Nombre del plugin al que pertenece este cron.
     */
    public function __construct(string $pluginName)
    {
        $this->pluginName = $pluginName;
    }

    /**
     * Devuelve el job del plugin con el nombre indicado, creándolo si no existe,
     * para programarlo y ejecutarlo. Si el job estaba en ejecución desde hace
     * demasiado tiempo (proceso zombie), lo libera.
     *
     * @param string $name Nombre identificativo del job dentro del plugin.
     *
     * @return CronJob
     */
    protected function job(string $name): CronJob
    {
        $job = new CronJob();
        $where = [
            Where::eq('jobname', $name),
            Where::eq('pluginname', $this->pluginName)
        ];
        if (false === $job->loadWhere($where)) {
            // no se había ejecutado nunca, lo creamos
            $job->jobname = $name;
            $job->pluginname = $this->pluginName;
        }

        // si es un proceso zombie, lo liberamos
        $job->releaseIfStale();

        return $job;
    }
}
