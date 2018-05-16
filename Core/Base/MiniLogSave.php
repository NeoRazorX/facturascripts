<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\LogMessage;
use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\MiniLog;

/**
 * Class to read the miniLog
 *
 * @package FacturaScripts\Core\Base
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Cristo M. Estévez Hernández <cristom.estevez@gmail.com>
 */
class MiniLogSave
{

    /**
     * Type of logs 
     *
     * @var array
     */
    const TYPESLOGS = ['info', 'notice', 'warning', 'error', 
                       'critical', 'alert', 'emergency'
                      ];

    /**
     * Store the datas into db
     *
     * @return void
     */
    public static function saveLog() : void
    {
        foreach (MiniLog::getDataLog() as $content) {
            $logMessage = new LogMessage();
            $logMessage->time = $content["time"];
            $logMessage->level = $content["level"];
            $logMessage->message = $content["message"];
            $logMessage->save();
        }

        foreach (self::TYPESLOGS as $value) {
            $logMessage = new LogMessage();
            $logMessage->level = $value;
            $logMessage->message = AppSettings::get('log', $value, 'false');
            $logMessage->save();
        }
    }
}
