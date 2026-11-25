<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * Script auxiliar de WorkQueueTest: ejecuta la cola de trabajos en un proceso PHP
 * aparte con un worker que nunca termina, para simular una petición que muere
 * mientras procesa un evento.
 *
 * Uso: php work-queue-dead-request.php <segundos>
 *  - Con segundos > 0, PHP corta la ejecución al superar ese tiempo (error fatal).
 *  - Con 0, no hay límite: el proceso padre debe matarlo.
 *
 * @author Esteban Sánchez Martínez <esteban@factura.city>
 */

namespace {

    // cargamos el autoload antes de declarar el worker, que extiende una clase del core
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
}

namespace FacturaScripts\Dinamic\Worker {

    use FacturaScripts\Core\Model\WorkEvent;
    use FacturaScripts\Core\Template\WorkerClass;

    /**
     * Worker que nunca termina: consume CPU hasta que la petición muere.
     */
    class TestDeadWorker extends WorkerClass
    {
        public function run(WorkEvent $event): bool
        {
            // avisamos al proceso padre de que el worker ha empezado
            echo 'worker-started' . PHP_EOL;
            flush();

            // bucle infinito con consumo de CPU, para que cuente también en el límite de Linux
            while (true) {
                md5((string)mt_rand());
            }
        }
    }
}

namespace {

    use FacturaScripts\Core\Base\DataBase;
    use FacturaScripts\Core\Kernel;
    use FacturaScripts\Core\Tools;
    use FacturaScripts\Core\WorkQueue;

    define('FS_FOLDER', dirname(__DIR__, 2));
    require_once FS_FOLDER . '/config.php';

    date_default_timezone_set(Tools::config('timezone', 'Europe/Madrid'));
    Kernel::init();

    $db = new DataBase();
    $db->connect();

    WorkQueue::addWorker('TestDeadWorker', 'test-dead-worker');

    // establecemos el tiempo máximo de ejecución pedido por el test
    set_time_limit((int)($argv[1] ?? 2));

    WorkQueue::run();

    // si llegamos aquí es que la petición no ha muerto
    echo 'queue-finished' . PHP_EOL;
}
