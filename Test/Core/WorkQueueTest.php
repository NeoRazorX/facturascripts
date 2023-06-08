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

namespace FacturaScripts\Test\Core;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Cache;
use FacturaScripts\Core\Model\WorkEvent;
use FacturaScripts\Core\WorkQueue;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class WorkQueueTest extends TestCase
{
    use LogErrorsTrait;

    public static function setUpBeforeClass(): void
    {
        // eliminamos todos los eventos pendientes
        $model = new WorkEvent();
        foreach ($model->all([], [], 0, 0) as $event) {
            $event->delete();
        }

        // eliminamos todos los workers
        WorkQueue::removeAllWorkers();
    }

    public function testAddEvent(): void
    {
        // comprobamos que no podemos añadir el evento porque no existe el worker
        $this->assertFalse(WorkQueue::send('test-event', 'test-value'));

        // añadimos el worker
        WorkQueue::addWorker('TestWorker', 'test-event');

        // añadimos el evento
        $this->assertTrue(WorkQueue::send('test-event', 'test-value'));

        // comprobamos que se ha guardado el evento
        $model = new WorkEvent();
        $events = $model->all([], [], 0, 0);
        $this->assertCount(1, $events);
        $this->assertEquals('test-event', $events[0]->name);
        $this->assertEquals('test-value', $events[0]->value);
        $this->assertEmpty($events[0]->params());
        $this->assertFalse($events[0]->done);
        $this->assertNull($events[0]->done_date);

        // eliminamos el evento
        $this->assertTrue($events[0]->delete());

        // eliminamos el worker
        WorkQueue::removeAllWorkers();
    }

    public function testAddEventWithParams(): void
    {
        // añadimos el worker
        WorkQueue::addWorker('TestWorker', 'test-event');

        // añadimos el evento
        $params = ['param1' => 'value1', 'param2' => 'value2'];
        $this->assertTrue(WorkQueue::send('test-event', 'test-value', $params));

        // comprobamos que se ha guardado el evento
        $model = new WorkEvent();
        $events = $model->all([], [], 0, 0);
        $this->assertCount(1, $events);
        $this->assertEquals('test-event', $events[0]->name);
        $this->assertEquals('test-value', $events[0]->value);
        $this->assertEquals($params, $events[0]->params());
        $this->assertFalse($events[0]->done);
        $this->assertNull($events[0]->done_date);

        // eliminamos el evento
        $this->assertTrue($events[0]->delete());

        // eliminamos el worker
        WorkQueue::removeAllWorkers();
    }

    public function testRunEmptyQueue(): void
    {
        $this->assertFalse(WorkQueue::run());
    }

    public function testAddEventAndRun(): void
    {
        // establecemos el valor de cache a 0
        Cache::set('test-worker', 0);

        // añadimos el worker
        WorkQueue::addWorker('TestWorker', 'test-event-worker');

        // añadimos el evento
        $this->assertTrue(WorkQueue::send('test-event-worker', '123456'));

        // comprobamos que el valor de cache no ha cambiado
        $this->assertEquals(0, Cache::get('test-worker'));

        // ejecutamos la cola
        $this->assertTrue(WorkQueue::run());

        // comprobamos que el valor de cache ha cambiado
        $this->assertEquals('123456', Cache::get('test-worker'));

        // comprobamos que el evento se ha guardado como realizado
        $model = new WorkEvent();
        $where = [new DataBaseWhere('name', 'test-event-worker')];
        $events = $model->all($where, [], 0, 0);
        $this->assertCount(1, $events);
        $this->assertTrue($events[0]->done);
        $this->assertNotNull($events[0]->done_date);
        $this->assertEquals('123456', $events[0]->value);
        $this->assertEquals(1, $events[0]->workers);
        $this->assertEquals('TestWorker', $events[0]->worker_list);

        // eliminamos el evento
        $this->assertTrue($events[0]->delete());

        // eliminamos el worker
        WorkQueue::removeAllWorkers();
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
