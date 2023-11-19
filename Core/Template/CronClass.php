<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\CronJob;

abstract class CronClass
{
    /** @var string */
    public $pluginName;

    abstract public function run(): void;

    public function __construct(string $pluginName)
    {
        $this->pluginName = $pluginName;
    }

    protected function job(string $name): CronJob
    {
        $job = new CronJob();
        $where = [
            new DataBaseWhere('jobname', $name),
            new DataBaseWhere('pluginname', $this->pluginName)
        ];
        if (false === $job->loadFromCode('', $where)) {
            // no se había ejecutado nunca, lo creamos
            $job->jobname = $name;
            $job->pluginname = $this->pluginName;
        }

        return $job;
    }
}
