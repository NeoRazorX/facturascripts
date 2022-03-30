<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * Class to store log information when a plugin is executed from cron.
 *
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 * @author Francesc Pineda Segarra  <francesc.pineda@x-netdigital.com>
 */
class CronJob extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Date of execution of the plugin with cron.
     *
     * @var string
     */
    public $date;

    /**
     * @var bool
     */
    public $done;

    /**
     * @var float
     */
    public $duration;

    /**
     * @var bool
     */
    public $enabled;

    /**
     * Primary key.
     *
     * @var int
     */
    public $id;

    /**
     * Name of the cron job.
     *
     * @var string
     */
    public $jobname;

    /**
     * Name of the plugin executed in cron.
     *
     * @var string
     */
    public $pluginname;

    public function clear()
    {
        parent::clear();
        $this->date = date(self::DATETIME_STYLE);
        $this->done = false;
        $this->duration = 0.0;
        $this->enabled = true;
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
        $utils = $this->toolBox()->utils();
        $this->jobname = $utils->noHtml($this->jobname);
        $this->pluginname = $utils->noHtml($this->pluginname);
        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'ListLogMessage?activetab=List'): string
    {
        return parent::url($type, $list);
    }
}
