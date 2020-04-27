<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Dinamic\Model\LogMessage;

/**
 * Class to read the miniLog.
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 */
class MiniLogSave
{

    /**
     * Read the data from MiniLog and storage in Log table.
     * 
     * @param string $ip
     * @param string $nick
     * @param string $uri
     */
    public function __construct(string $ip = '', string $nick = '', string $uri = '')
    {
        $miniLog = new MiniLog();
        foreach ($miniLog->readAll($this->getActiveSettingsLog()) as $value) {
            $logMessage = new LogMessage();
            $logMessage->channel = $value['channel'];
            $logMessage->time = date('d-m-Y H:i:s', $value['time']);
            $logMessage->level = $value['level'];
            $logMessage->message = $value['message'];
            $logMessage->ip = empty($ip) ? null : $ip;
            $logMessage->nick = empty($nick) ? null : $nick;
            $logMessage->uri = empty($uri) ? null : $uri;
            $logMessage->save();
        }
    }

    /**
     * Get types of settings log enable (true) 
     *
     * @return array
     */
    private function getActiveSettingsLog(): array
    {
        $types = [];
        foreach (MiniLog::ALL_LEVELS as $value) {
            if (AppSettings::get('log', $value, 'false') == 'true') {
                $types[] = $value;
            }
        }

        return $types;
    }
}
