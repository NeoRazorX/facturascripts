<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
     *
     * @var bool
     */
    public $done;

    /**
     *
     * @var float
     */
    public $duration;

    /**
     *
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

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->date = \date(self::DATETIME_STYLE);
        $this->done = false;
        $this->duration = 0.0;
        $this->enabled = true;
    }

    /**
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'id';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'cronjobs';
    }

    /**
     * Returns True if there is no errors on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $utils = $this->toolBox()->utils();
        $this->jobname = $utils->noHtml($this->jobname);
        $this->pluginname = $utils->noHtml($this->pluginname);
        return parent::test();
    }

    /**
     * Returns the url where to see / modify the data.
     *
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'ListLogMessage?activetab=List')
    {
        return parent::url($type, $list);
    }
}
