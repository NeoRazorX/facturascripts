<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core;

use Closure;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Cache;
use FacturaScripts\Core\Model\Producto;
use FacturaScripts\Core\Model\WorkEvent;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Core\WorkQueue;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class WorkQueueTest extends TestCase
{
    use LogErrorsTrait;

    /** @var int|null */
    public static $blockedId;

    public static function setUpBeforeClass(): void
    {
        // eliminamos todos los eventos pendientes
        $model = new WorkEvent();
        foreach ($model->all([], [], 0, 0) as $event) {
            $event->delete();
        }

        // eliminamos todos los workers
        WorkQueue::clear();
    }

    public function testMatchEvent(): void
    {
        $this->assertTrue(WorkQueue::matchEvent('Model.Producto.Save', 'Model.Producto.Save'));
        $this->assertTrue(WorkQueue::matchEvent('Model.Producto.*', 'Model.Producto.Save'));
        $this->assertTrue(WorkQueue::matchEvent('Model.*', 'Model.Producto.Save'));
        $this->assertTrue(WorkQueue::matchEvent('*', 'Model.Producto.Save'));

        $this->assertFalse(WorkQueue::matchEvent('Model.Producto.Save', 'Model.Producto.Delete'));
        $this->assertFalse(WorkQueue::matchEvent('Model.Producto.*', 'Model.Asiento.Insert'));
        $this->assertFalse(WorkQueue::matchEvent('Model.*', 'test-event'));
    }

    public function testSend(): void
    {
        // comprobamos que no podemos añadir el evento porque no existe el worker
        $this->assertFalse(WorkQueue::send('test-event', 'test-value'));

        // añadimos el worker
        WorkQueue::addWorker('TestWorker', 'test-event');

        // añadimos el evento
        $this->assertTrue(WorkQueue::send('test-event', 'test-value'));

        // comprobamos que se ha guardado el evento
        $model = new WorkEvent();
        $events = $model->all([], ['id' => 'ASC'], 0, 0);
        $this->assertCount(1, $events);
        $this->assertEquals('test-event', $events[0]->name);
        $this->assertEquals('test-value', $events[0]->value);
        $this->assertEmpty($events[0]->params());
        $this->assertFalse($events[0]->done);
        $this->assertNull($events[0]->done_date);

        // eliminamos el evento
        $this->assertTrue($events[0]->delete());

        // eliminamos el worker
        WorkQueue::clear();
    }

    public function testSendWithParams(): void
    {
        // añadimos el worker
        WorkQueue::addWorker('TestWorker', 'test-event');

        // añadimos el evento
        $params = ['param1' => 'value1', 'param2' => 'value2'];
        $this->assertTrue(WorkQueue::send('test-event', 'test-value', $params));

        // comprobamos que se ha guardado el evento
        $model = new WorkEvent();
        $events = $model->all([], ['id' => 'ASC'], 0, 0);
        $this->assertCount(1, $events);
        $this->assertEquals('test-event', $events[0]->name);
        $this->assertEquals('test-value', $events[0]->value);
        $this->assertEquals($params, $events[0]->params());
        $this->assertFalse($events[0]->done);
        $this->assertNull($events[0]->done_date);

        // eliminamos el evento
        $this->assertTrue($events[0]->delete());

        // eliminamos el worker
        WorkQueue::clear();
    }

    public function testSendDuplicated(): void
    {
        // añadimos el worker
        WorkQueue::addWorker('TestWorker', 'test-event');

        // añadimos el evento
        $this->assertTrue(WorkQueue::send('test-event', 'test-value'));

        // lo volvemos a añadir
        $this->assertTrue(WorkQueue::send('test-event', 'test-value'));

        // comprobamos que se ha guardado el evento
        $model = new WorkEvent();
        $events = $model->all([], ['id' => 'ASC'], 0, 0);
        $this->assertCount(1, $events);
        $this->assertEquals('test-event', $events[0]->name);
        $this->assertEquals('test-value', $events[0]->value);
        $this->assertFalse($events[0]->done);
        $this->assertNull($events[0]->done_date);

        // eliminamos el evento
        $this->assertTrue($events[0]->delete());

        // eliminamos el worker
        WorkQueue::clear();
    }

    public function testRemoveAllWorkers(): void
    {
        // contamos el número de workers
        $count = count(WorkQueue::getWorkersList());

        // añadimos un worker
        WorkQueue::addWorker('TestWorker', 'test-event');

        // comprobamos que se ha añadido el worker
        $this->assertCount($count + 1, WorkQueue::getWorkersList());

        // eliminamos todos los workers
        WorkQueue::clear();

        // comprobamos que se han eliminado todos los workers
        $this->assertEmpty(WorkQueue::getWorkersList());
    }

    public function testMasterWorker(): void
    {
        // añadimos el worker
        WorkQueue::addWorker('TestWorker');

        // añadimos 2 eventos
        $this->assertTrue(WorkQueue::send('test-event', 'test-value'));
        $this->assertTrue(WorkQueue::send('test-event-2', 'test-value-2'));

        // comprobamos que se han guardado los eventos
        $model = new WorkEvent();
        $events = $model->all([], ['id' => 'ASC'], 0, 0);
        $this->assertCount(2, $events);
        $this->assertEquals('test-event', $events[0]->name);
        $this->assertEquals('test-event-2', $events[1]->name);

        // eliminamos el worker
        WorkQueue::clear();

        // eliminamos los eventos
        $this->assertTrue($events[0]->delete());
        $this->assertTrue($events[1]->delete());
    }

    public function testModelAsientoAsterisk(): void
    {
        // añadimos el worker
        WorkQueue::addWorker('TestWorker', 'Model.Asiento.*');

        // añadimos 2 eventos de modelos
        $this->assertTrue(WorkQueue::send('Model.Asiento.Save', 'test-value'));
        $this->assertTrue(WorkQueue::send('Model.Asiento.Delete', 'test-value-2'));

        // añadimos un evento no relacionado
        $this->assertFalse(WorkQueue::send('Yolo.Save', 'test-value-3'));

        // comprobamos que se han guardado los 2 eventos
        $model = new WorkEvent();
        $events = $model->all([], ['id' => 'ASC'], 0, 0);
        $this->assertCount(2, $events);
        $this->assertEquals('Model.Asiento.Save', $events[0]->name);
        $this->assertEquals('Model.Asiento.Delete', $events[1]->name);

        // eliminamos el worker
        WorkQueue::clear();

        // eliminamos los eventos
        $this->assertTrue($events[0]->delete());
        $this->assertTrue($events[1]->delete());
    }

    public function testModelAsterisk(): void
    {
        // añadimos el worker
        WorkQueue::addWorker('TestWorker', 'Model.*');

        // añadimos 2 eventos de modelos
        $this->assertTrue(WorkQueue::send('Model.Asiento.Save', 'test-value'));
        $this->assertTrue(WorkQueue::send('Model.Producto.Save', 'test-value-2'));

        // añadimos un evento no relacionado
        $this->assertFalse(WorkQueue::send('Otro.Save', 'test-value-3'));

        // comprobamos que se han guardado los 2 eventos
        $model = new WorkEvent();
        $events = $model->all([], ['id' => 'ASC'], 0, 0);
        $this->assertCount(2, $events);
        $this->assertEquals('Model.Asiento.Save', $events[0]->name);
        $this->assertEquals('Model.Producto.Save', $events[1]->name);

        // eliminamos el worker
        WorkQueue::clear();

        // eliminamos los eventos
        $this->assertTrue($events[0]->delete());
        $this->assertTrue($events[1]->delete());
    }

    public function testRunEmptyQueue(): void
    {
        $this->assertFalse(WorkQueue::run());
    }

    public function testSendEventAndRun(): void
    {
        // establecemos el valor inicial de caché
        Cache::set('test-worker-name', '-');
        Cache::set('test-worker-value', -1);

        // añadimos el worker
        WorkQueue::addWorker('TestWorker', 'test-event-worker');

        // añadimos el evento
        $this->assertTrue(WorkQueue::send('test-event-worker', '123456'));

        // comprobamos que el valor de cache no ha cambiado
        $this->assertEquals('-', Cache::get('test-worker-name'));
        $this->assertEquals(-1, Cache::get('test-worker-value'));

        // ejecutamos la cola
        $this->assertTrue(WorkQueue::run());

        // comprobamos que el valor de cache ha cambiado
        $this->assertEquals('test-event-worker', Cache::get('test-worker-name'));
        $this->assertEquals('123456', Cache::get('test-worker-value'));

        // comprobamos que el evento se ha guardado como realizado
        $model = new WorkEvent();
        $where = [Where::eq('name', 'test-event-worker')];
        $events = $model->all($where, ['id' => 'ASC'], 0, 0);
        $this->assertCount(1, $events);
        $this->assertTrue($events[0]->done);
        $this->assertNotNull($events[0]->done_date);
        $this->assertEquals('test-event-worker', $events[0]->name);
        $this->assertEquals('123456', $events[0]->value);
        $this->assertEquals(1, $events[0]->workers);
        $this->assertEquals('TestWorker', $events[0]->worker_list);

        // eliminamos el evento
        $this->assertTrue($events[0]->delete());

        // eliminamos el worker
        WorkQueue::clear();
    }

    public function testModelEvent(): void
    {
        // eliminamos todos los eventos de la tabla
        $model = new WorkEvent();
        foreach ($model->all([], [], 0, 0) as $event) {
            $event->delete();
        }

        // añadimos un worker que escuche el evento Model.Producto.Save y Model.Producto.Delete
        WorkQueue::addWorker('TestWorker', 'Model.Producto.Save');
        WorkQueue::addWorker('TestWorker', 'Model.Producto.Delete');

        // creamos un producto
        $producto = new Producto();
        $producto->referencia = 'test-producto';
        $producto->descripcion = 'test-producto-description';
        $this->assertTrue($producto->save());

        // comprobamos que se ha guardado el evento
        $event = new WorkEvent();
        $this->assertTrue($event->loadWhereEq('name', 'Model.Producto.Save'));

        // ejecutamos la cola
        $this->assertTrue(WorkQueue::run());

        // comprobamos que el evento se ha guardado como realizado
        $this->assertTrue($event->load($event->primaryColumnValue()));
        $this->assertTrue($event->done);

        // comprobamos que se ha actualizado la caché
        $this->assertEquals('Model.Producto.Save', Cache::get('test-worker-name'));
        $this->assertEquals($producto->primaryColumnValue(), Cache::get('test-worker-value'));

        // eliminamos el producto
        $this->assertTrue($producto->delete());

        // comprobamos que se ha guardado el evento
        $this->assertTrue($event->loadWhereEq('name', 'Model.Producto.Delete'));

        // ejecutamos la cola
        $this->assertTrue(WorkQueue::run());

        // comprobamos que el evento se ha guardado como realizado
        $this->assertTrue($event->load($event->primaryColumnValue()));
        $this->assertTrue($event->done);

        // comprobamos que se ha actualizado la caché
        $this->assertEquals('Model.Producto.Delete', Cache::get('test-worker-name'));
        $this->assertEquals($producto->primaryColumnValue(), Cache::get('test-worker-value'));
    }

    public function testModelEventWithLongParams(): void
    {
        // creamos un evento con miles de parámetros
        $params = [];
        for ($i = 0; $i < 1000; ++$i) {
            $params['param' . $i] = 'value' . $i;
        }

        $event = new WorkEvent();
        $event->name = 'Model.Producto.Save';
        $event->value = 'test-value';
        $event->setParams($params);
        $this->assertTrue($event->save());

        // recargamos el evento
        $this->assertTrue($event->load($event->primaryColumnValue()));

        // comprobamos que los parámetros son correctos
        $this->assertEquals($params, $event->params());

        // eliminamos el evento
        $this->assertTrue($event->delete());
    }

    public function testDeleteBeforeExtensionKeepsParamsFile(): void
    {
        // creamos un evento y le asignamos parámetros grandes, que se guardan en un archivo
        $event = new WorkEvent();
        $event->name = 'test-event';
        $event->value = 'test-value';
        $event->setParams([]);
        $this->assertTrue($event->save());
        $this->assertTrue(Tools::folderCheckOrCreate(Tools::folder('MyFiles', 'Tmp')));
        $event->setParams(['data' => str_repeat('a', 6000)]);
        $this->assertTrue($event->save());
        $filePath = Tools::folder('MyFiles', 'Tmp', 'work-event-' . $event->id . '-params.json');
        $this->assertTrue(file_exists($filePath));

        // añadimos una extensión que impide eliminar este evento
        self::$blockedId = $event->id;
        WorkEvent::addExtension(new class {
            public function deleteBefore(): Closure
            {
                return function () {
                    if ($this instanceof WorkEvent && $this->id == WorkQueueTest::$blockedId) {
                        return false;
                    }
                };
            }
        });

        // el evento no se puede eliminar y conserva el archivo de parámetros
        $this->assertFalse($event->delete());
        $this->assertTrue($event->exists());
        $this->assertTrue(file_exists($filePath));

        // retiramos el bloqueo
        self::$blockedId = null;

        // eliminamos: también se elimina el archivo
        $this->assertTrue($event->delete());
        $this->assertFalse(file_exists($filePath));
    }

    public function testRunCountsAttempts(): void
    {
        // añadimos el worker y el evento
        WorkQueue::addWorker('TestWorker', 'test-event-attempts');
        $this->assertTrue(WorkQueue::send('test-event-attempts', 'test-value'));

        // el evento nuevo no tiene intentos
        $event = new WorkEvent();
        $this->assertTrue($event->loadWhereEq('name', 'test-event-attempts'));
        $this->assertEquals(0, $event->attempts);

        // ejecutamos la cola: el evento termina con un intento
        $this->assertTrue(WorkQueue::run());
        $this->assertTrue($event->reload());
        $this->assertTrue($event->done);
        $this->assertEquals(1, $event->attempts);

        // la cola ha liberado el bloqueo
        $this->assertFileDoesNotExist($this->lockFile());

        // limpieza
        $this->assertTrue($event->delete());
        WorkQueue::clear();
    }

    public function testEventWithMaxAttemptsIsDiscarded(): void
    {
        Cache::set('test-worker-name', '-');
        WorkQueue::addWorker('TestWorker', 'test-event-discard');
        WorkQueue::addWorker('TestWorker', 'test-event-next');

        // evento que ya ha agotado sus intentos
        $stuck = new WorkEvent();
        $stuck->name = 'test-event-discard';
        $stuck->value = 'stuck';
        $stuck->setParams([]);
        $stuck->attempts = WorkQueue::MAX_ATTEMPTS;
        $this->assertTrue($stuck->save());

        // evento normal que va detrás
        $this->assertTrue(WorkQueue::send('test-event-next', 'next'));

        // primera ejecución: aparta el evento sin ejecutar su worker
        $this->assertTrue(WorkQueue::run());
        $this->assertTrue($stuck->reload());
        $this->assertTrue($stuck->done);
        $this->assertNotNull($stuck->done_date);
        $this->assertEquals(0, $stuck->workers);
        $this->assertEquals(WorkQueue::MAX_ATTEMPTS, $stuck->attempts);
        $this->assertEquals('-', Cache::get('test-worker-name'));

        // segunda ejecución: procesa el siguiente evento
        $this->assertTrue(WorkQueue::run());
        $this->assertEquals('test-event-next', Cache::get('test-worker-name'));

        // limpieza
        $this->deleteEvents(['test-event-discard', 'test-event-next']);
        WorkQueue::clear();
    }

    /**
     * Prueba real: lanza 3 procesos PHP que mueren por tiempo máximo de ejecución
     * mientras procesan el mismo evento. Cada uno debe dejar la cola desbloqueada
     * y sumar un intento; después, la cola aparta ese evento y sigue con el siguiente.
     */
    public function testDeadRequestReleasesLockAndEventIsDiscarded(): void
    {
        Cache::set('test-worker-name', '-');
        WorkQueue::addWorker('TestWorker', 'test-event-after-dead');

        // evento que hará morir la petición
        $dead = new WorkEvent();
        $dead->name = 'test-dead-worker';
        $dead->value = 'dead';
        $dead->setParams([]);
        $this->assertTrue($dead->save());

        // evento normal que queda detrás
        $this->assertTrue(WorkQueue::send('test-event-after-dead', 'after'));

        for ($attempt = 1; $attempt <= WorkQueue::MAX_ATTEMPTS; $attempt++) {
            $output = $this->runChildProcess(1);

            // el worker empezó y la petición murió sin terminar la cola
            $this->assertStringContainsString('worker-started', $output);
            $this->assertStringContainsString('Maximum execution time', $output);
            $this->assertStringNotContainsString('queue-finished', $output);

            // el bloqueo se ha liberado al apagarse PHP
            $this->assertFileDoesNotExist($this->lockFile(), 'El bloqueo sigue puesto tras el intento ' . $attempt);

            // el evento sigue pendiente, con el intento anotado
            $this->assertTrue($this->reloadFromDb($dead));
            $this->assertFalse($dead->done);
            $this->assertEquals($attempt, $dead->attempts);
        }

        // la cola ya no está bloqueada: aparta el evento que siempre muere
        $this->assertTrue(WorkQueue::run());
        $this->assertTrue($dead->reload());
        $this->assertTrue($dead->done);
        $this->assertEquals('-', Cache::get('test-worker-name'));

        // y procesa el siguiente sin esperas
        $this->assertTrue(WorkQueue::run());
        $this->assertEquals('test-event-after-dead', Cache::get('test-worker-name'));

        // limpieza
        $this->deleteEvents(['test-dead-worker', 'test-event-after-dead']);
        WorkQueue::clear();
    }

    protected function tearDown(): void
    {
        // por si algún test falla a mitad, no dejamos la cola bloqueada
        if (file_exists($this->lockFile())) {
            unlink($this->lockFile());
        }

        $this->logErrors();
    }

    /**
     * Elimina los eventos de prueba con los nombres indicados.
     *
     * @param array $names Nombres de los eventos.
     */
    private function deleteEvents(array $names): void
    {
        $model = new WorkEvent();
        foreach ($names as $name) {
            foreach ($model->all([Where::eq('name', $name)], [], 0, 0) as $event) {
                $event->delete();
            }
        }
    }

    /**
     * Vuelve a leer el evento de la base de datos. Antes cerramos la transacción de
     * lectura: la conexión MySQL trabaja sin autocommit y, sin cerrarla, no veríamos
     * los cambios que ha guardado el proceso hijo.
     *
     * @param WorkEvent $event Evento a recargar.
     *
     * @return bool
     */
    private function reloadFromDb(WorkEvent $event): bool
    {
        (new DataBase())->commit();
        return $event->reload();
    }

    /**
     * Devuelve la ruta del archivo de bloqueo de la cola.
     *
     * @return string
     */
    private function lockFile(): string
    {
        return Tools::folder('MyFiles', 'lock_' . md5(WorkQueue::LOCK_NAME) . '.lock');
    }

    /**
     * Ejecuta la cola en un proceso PHP aparte con un worker que nunca termina
     * y devuelve su salida cuando el proceso acaba.
     *
     * @param int $seconds Tiempo máximo de ejecución del proceso hijo.
     *
     * @return string
     */
    private function runChildProcess(int $seconds): string
    {
        $command = [PHP_BINARY];
        $ini = php_ini_loaded_file();
        if ($ini) {
            $command[] = '-c';
            $command[] = $ini;
        }
        $command[] = Tools::folder('Test', '__files', 'work-queue-dead-request.php');
        $command[] = (string)$seconds;

        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, Tools::folder());
        $this->assertIsResource($process, 'No se pudo lanzar el proceso hijo');

        $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        return $output;
    }
}
