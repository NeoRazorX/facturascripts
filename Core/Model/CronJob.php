<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\Utils;

/**
 * Class to store log information when a plugin is executed from cron.
 *
 * @author Francesc Pineda Segarra <francesc.pineda@x-netdigital.com>
 */
class CronJob extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     * Primary key.
     *
     * @var int
     */
    public $id;

    /**
     * Name of the plugin executed in cron.
     *
     * @var string
     */
    public $pluginname;

    /**
     * Date of execution of the plugin with cron.
     *
     * @var string
     */
    public $date;

    /**
     * Name of the cron job.
     *
     * @var null|string
     */
    public $jobname;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->pluginname = '';
        $this->date = date('d-m-Y H:i:s');
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
        $this->pluginname = Utils::noHtml($this->pluginname);
        $this->jobname = $this->jobname === null ? $this->jobname : Utils::noHtml($this->jobname);
        return parent::test();
    }
}
