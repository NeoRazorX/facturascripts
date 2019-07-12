<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Base;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\CronJob;

/**
 * Defines global attributes and methos for all classes.
 *
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 * @author Rafael San José Tovar
 */
abstract class CronClass
{

    /**
     * Cache object.
     *
     * @var Cache
     */
    protected static $cache;

    /**
     * Database object.
     *
     * @var DataBase
     */
    protected static $dataBase;

    /**
     * Translator object.
     *
     * @var Translator
     */
    protected static $i18n;

    /**
     * MiniLog object.
     *
     * @var MiniLog
     */
    protected static $miniLog;

    /**
     *
     * @var string
     */
    private $pluginName;

    /**
     * Select and execute the relevant controller for the cron.
     *
     * @return mixed
     */
    abstract public function run();

    /**
     * CronClass constructor.
     * 
     * @param string $pluginName
     */
    public function __construct(string $pluginName)
    {
        $this->pluginName = $pluginName;
        if (!isset(self::$cache)) {
            self::$cache = new Cache();
            self::$dataBase = new DataBase();
            self::$i18n = new Translator();
            self::$miniLog = new MiniLog();
        }
    }

    /**
     * Returns true if this cron job can be executed (never executed or more than period),
     * false otherwise.
     *
     * @param string $jobName
     * @param string $period
     *
     * @return bool
     */
    public function isTimeForJob(string $jobName, string $period = '1 day')
    {
        $cronJob = new CronJob();
        $where = [
            new DataBaseWhere('pluginname', $this->pluginName),
            new DataBaseWhere('jobname', $jobName)
        ];

        /// if we can't find it, then is the first time
        if (!$cronJob->loadFromCode('', $where)) {
            return true;
        }

        /// last time was before period?
        if ($cronJob->enabled && strtotime($cronJob->date) < strtotime('-' . $period)) {
            /// updates date and return true (if no error)
            $cronJob->date = date('d-m-Y H:i:s');
            $cronJob->done = false;
            return $cronJob->save();
        }

        return false;
    }

    /**
     * Updates when this job is executed.
     *
     * @param string $jobName
     */
    public function jobDone(string $jobName)
    {
        $cronJob = new CronJob();
        $where = [
            new DataBaseWhere('pluginname', $this->pluginName),
            new DataBaseWhere('jobname', $jobName)
        ];

        if (!$cronJob->loadFromCode('', $where)) {
            $cronJob->pluginname = $this->pluginName;
            $cronJob->jobname = $jobName;
        }

        $cronJob->date = date('d-m-Y H:i:s');
        $cronJob->done = true;
        if (!$cronJob->save()) {
            self::$miniLog->error(self::$i18n->trans('record-save-error'));
        }
    }
}
