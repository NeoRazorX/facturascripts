<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\Contract\MiniLogStorageInterface;
use FacturaScripts\Core\Session;
use FacturaScripts\Dinamic\Model\LogMessage;

final class MiniLogStorage implements MiniLogStorageInterface
{
    public function save(array $data): bool
    {
        $done = true;
        foreach ($data as $item) {
            // excluimos debug
            if ($item['level'] === 'debug') {
                continue;
            }

            // del canal master excluimos los que no sean error
            if ($item['channel'] === MiniLog::DEFAULT_CHANNEL && false === in_array($item['level'], ['critical', 'error'])) {
                continue;
            }

            // guardamos el resto
            $logItem = new LogMessage();
            $logItem->channel = $item['channel'];
            $logItem->context = json_encode($item['context']);
            $logItem->idcontacto = $item['context']['idcontacto'] ?? null;
            $logItem->ip = Session::getClientIp();
            $logItem->level = $item['level'];
            $logItem->message = $item['message'];
            $logItem->model = $item['context']['model-class'] ?? null;
            $logItem->modelcode = $item['context']['model-code'] ?? null;
            $logItem->nick = $item['context']['nick'] ?? Session::user()->nick;
            $logItem->time = date('d-m-Y H:i:s', (int)$item['time']);
            $logItem->uri = $item['context']['uri'] ?? Session::get('uri');
            if (false === $logItem->save()) {
                $done = false;
            }
        }

        return $done;
    }
}
