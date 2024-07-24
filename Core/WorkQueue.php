<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use Exception;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\WorkEvent;
use Throwable;

/**
 * La cola de trabajos en segundo plano. Permite añadir workers y lanzar eventos.
 */
final class WorkQueue
{
    /** @var array */
    private static $new_events = [];

    /** @var array */
    private static $prevent_new_events = [];

    /** @var array */
    private static $workers_list = [];

    public static function addWorker(string $worker_name, string $event = '*', int $position = 0): void
    {
        self::$workers_list[] = [
            'class' => self::getWorkerClass($worker_name),
            'event' => $event,
            'name' => $worker_name,
            'position' => $position,
        ];
    }

    public static function clear(): void
    {
        self::$new_events = [];
        self::$prevent_new_events = [];
        self::$workers_list = [];
    }

    public static function getWorkersList(): array
    {
        return self::$workers_list;
    }

    public static function matchEvent(string $pattern, string $event): bool
    {
        if ($pattern === $event || $pattern === '*') {
            return true;
        }

        // si contiene un *, comparamos hasta el *
        $pos = strpos($pattern, '*');
        if ($pos !== false) {
            return substr($pattern, 0, $pos) === substr($event, 0, $pos);
        }

        return false;
    }

    public static function preventNewEvents(array $event_names): void
    {
        self::$prevent_new_events = $event_names;
    }

    public static function run(): bool
    {
        // leemos la lista de trabajos pendientes
        $workEventModel = new WorkEvent();
        $where = [new DataBaseWhere('done', false)];
        $orderBy = ['id' => 'ASC'];
        foreach ($workEventModel->all($where, $orderBy, 0, 1) as $event) {
            self::preventDuplicated($event);

            return self::runEvent($event);
        }

        return false;
    }

    public static function send(string $event, string $value, array $params = []): bool
    {
        // comprobamos si el evento está bloqueado
        foreach (self::$prevent_new_events as $prevent_event) {
            if (self::matchEvent($prevent_event, $event)) {
                return false;
            }
        }

        // excluimos también Model.WorkEvent.* para evitar bucles infinitos
        if (self::matchEvent('Model.WorkEvent.*', $event)) {
            return false;
        }

        // recorremos los workers
        foreach (self::$workers_list as $info) {
            // si no es el evento que buscamos, pasamos al siguiente
            if (!self::matchEvent($info['event'], $event)) {
                continue;
            }

            // worker encontrado, guardamos el evento
            $work_event = new WorkEvent();
            $work_event->name = $event;
            $work_event->value = $value;
            $work_event->setParams($params);

            if (self::isDuplicated($work_event)) {
                // devolvemos true porque ya se había enviado el evento
                return true;
            }

            if (false === $work_event->save()) {
                return false;
            }

            self::preventDuplicated($work_event);
            return true;
        }

        return false;
    }

    private static function getWorkerClass(string $worker_name): string
    {
        $workerClass = '\\FacturaScripts\\Dinamic\\Worker\\' . $worker_name;
        if (class_exists($workerClass)) {
            return $workerClass;
        }

        // buscamos en los plugins
        foreach (Plugins::enabled() as $name) {
            $pluginWorker = '\\FacturaScripts\\Plugins\\' . $name . '\\Worker\\' . $worker_name;
            if (class_exists($pluginWorker)) {
                return $pluginWorker;
            }
        }

        // buscamos en el core
        $coreWorker = '\\FacturaScripts\\Core\\Worker\\' . $worker_name;
        if (class_exists($coreWorker)) {
            return $coreWorker;
        }

        throw new Exception('Worker not found: ' . $worker_name);
    }

    private static function isDuplicated(WorkEvent $event): bool
    {
        $hash = $event->getHash();
        return isset(self::$new_events[$hash]);
    }

    private static function preventDuplicated(WorkEvent $event): void
    {
        $hash = $event->getHash();
        self::$new_events[$hash] = true;
    }

    private static function runEvent(WorkEvent &$event): bool
    {
        // creamos un bloqueo para evitar que se ejecute el mismo evento varias veces
        if (false === Kernel::lock('work-queue-' . $event->name)) {
            return false;
        }

        // ordenamos los workers por posición
        usort(self::$workers_list, function ($a, $b) {
            return $a['position'] <=> $b['position'];
        });

        // recorremos los workers
        $worker_list = [];
        foreach (self::$workers_list as $info) {
            // si no es el evento que buscamos, pasamos al siguiente
            if (!self::matchEvent($info['event'], $event->name)) {
                continue;
            }

            // si la clase no existe, pasamos al siguiente
            if (!class_exists($info['class'])) {
                continue;
            }

            // ejecutamos el worker
            $continue = true;
            try {
                $worker = new $info['class']();
                $continue = $worker->run($event);
            } catch (Throwable $th) {
                Tools::log('work-queue')->error($th->getMessage(), [
                    '%class%' => $info['class'],
                    '%event%' => $event->name,
                    '%value%' => $event->value,
                ]);
            }

            // guardamos el worker
            $worker_list[] = $info['name'];

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
        $return = $event->save();

        // liberamos el bloqueo
        Kernel::unlock('work-queue-' . $event->name);

        return $return;
    }
}
