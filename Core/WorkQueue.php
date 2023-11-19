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

namespace FacturaScripts\Core;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\WorkEvent;
use Throwable;

final class WorkQueue
{
    private static $workers = [];

    public static function addWorker(string $worker, string $event = '*', int $position = 0): void
    {
        self::$workers[] = [
            'class' => self::getWorkerClass($worker),
            'event' => $event,
            'name' => $worker,
            'position' => $position,
        ];
    }

    public static function removeAllWorkers(): void
    {
        self::$workers = [];
    }

    public static function run(): bool
    {
        // leemos la lista de trabajos pendientes
        $workEventModel = new WorkEvent();
        $where = [new DataBaseWhere('done', false)];
        $orderBy = ['id' => 'ASC'];
        foreach ($workEventModel->all($where, $orderBy, 0, 1) as $event) {
            return self::runEvent($event);
        }

        return false;
    }

    public static function send(string $event, string $value, array $params = []): bool
    {
        // recorremos los workers
        foreach (self::$workers as $worker) {
            // si no es el evento que buscamos, pasamos al siguiente
            if ($worker['event'] !== $event) {
                continue;
            }

            // worker encontrado, guardamos el evento
            $workEvent = new WorkEvent();
            $workEvent->name = $event;
            $workEvent->params = json_encode($params, JSON_PRETTY_PRINT);
            $workEvent->value = $value;
            return $workEvent->save();
        }

        return false;
    }

    private static function getWorkerClass(string $worker): string
    {
        $workerClass = '\\FacturaScripts\\Dinamic\\Worker\\' . $worker;
        if (!class_exists($workerClass)) {
            $workerClass = '\\FacturaScripts\\Core\\Worker\\' . $worker;
        }

        return $workerClass;
    }

    private static function runEvent(WorkEvent &$event): bool
    {
        // ordenamos los workers por posición
        usort(self::$workers, function ($a, $b) {
            return $a['position'] <=> $b['position'];
        });

        // recorremos los workers
        $worker_list = [];
        foreach (self::$workers as $worker) {
            // si no es el evento que buscamos, pasamos al siguiente
            if ($worker['event'] !== $event->name && $worker['event'] != '*') {
                continue;
            }

            // si la clase no existe, pasamos al siguiente
            if (!class_exists($worker['class'])) {
                continue;
            }

            // ejecutamos el worker
            $continue = true;
            try {
                $workerClass = new $worker['class']();
                $continue = $workerClass->run($event);
            } catch (Throwable $th) {
                Tools::log('work-queue')->error($th->getMessage(), [
                    '%class%' => $worker['class'],
                    '%event%' => $event->name,
                    '%value%' => $event->value,
                ]);
            }

            // guardamos el worker
            $worker_list[] = $worker['name'];

            // si ha devuelto false, detenemos la ejecución
            if (!$continue) {
                break;
            }

            // actualizamos el evento
            $event->workers = count($worker_list);
            $event->worker_list = implode(',', $worker_list);
        }

        // marcamos el evento como realizado
        $event->done = true;
        $event->done_date = Tools::dateTime();
        $event->workers = count($worker_list);
        $event->worker_list = implode(',', $worker_list);
        return $event->save();
    }
}
