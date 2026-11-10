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

namespace FacturaScripts\Test\Core;

use FacturaScripts\Core\Maintenance;
use FacturaScripts\Core\Model\CronJob;
use FacturaScripts\Core\Model\WorkEvent;
use FacturaScripts\Core\Plugins;
use FacturaScripts\Core\WorkQueue;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class MaintenanceTest extends TestCase
{
    use LogErrorsTrait;

    protected function setUp(): void
    {
        Maintenance::disable();
    }

    protected function tearDown(): void
    {
        Maintenance::disable();
        $this->logErrors();
    }

    public function testEnableAndDisable(): void
    {
        // por defecto no está activo
        $this->assertFalse(Maintenance::isActive());
        $this->assertSame('', Maintenance::token());

        // lo activamos
        $this->assertTrue(Maintenance::enable());
        $this->assertTrue(Maintenance::isActive());
        $this->assertFileExists(Maintenance::filePath());

        // el token es el que se guarda en el archivo
        $token = Maintenance::token();
        $this->assertSame(32, strlen($token));
        $data = json_decode(file_get_contents(Maintenance::filePath()), true);
        $this->assertSame($token, $data['token']);
        $this->assertGreaterThan(time(), $data['expires']);

        // lo desactivamos
        $this->assertTrue(Maintenance::disable());
        $this->assertFalse(Maintenance::isActive());
        $this->assertFileDoesNotExist(Maintenance::filePath());

        // desactivarlo de nuevo no da error
        $this->assertTrue(Maintenance::disable());
    }

    public function testEnableTwice(): void
    {
        $this->assertTrue(Maintenance::enable());
        $token = Maintenance::token();

        // la segunda llamada no lo activa ni cambia el token: solo quien lo activó debe desactivarlo
        $this->assertFalse(Maintenance::enable());
        $this->assertSame($token, Maintenance::token());
    }

    public function testExpired(): void
    {
        // un mantenimiento caducado ya no está activo
        file_put_contents(Maintenance::filePath(), json_encode(['expires' => time() - 10, 'token' => 'old']));
        $this->assertFalse(Maintenance::isActive());
        $this->assertSame('', Maintenance::token());

        // y se puede activar de nuevo
        $this->assertTrue(Maintenance::enable());
        $this->assertTrue(Maintenance::isActive());
        $this->assertNotSame('old', Maintenance::token());
    }

    public function testWithoutExpiration(): void
    {
        $this->assertTrue(Maintenance::enable(0));

        $data = json_decode(file_get_contents(Maintenance::filePath()), true);
        $this->assertSame(0, $data['expires']);
        $this->assertTrue(Maintenance::isActive());
    }

    public function testManualFile(): void
    {
        // un archivo vacío, creado a mano, activa el mantenimiento sin caducidad ni token
        touch(Maintenance::filePath());
        $this->assertTrue(Maintenance::isActive());
        $this->assertSame('', Maintenance::token());
        $this->assertFalse(Maintenance::enable());

        $this->assertTrue(Maintenance::disable());
        $this->assertFalse(Maintenance::isActive());
    }

    public function testCronJobsDoNotRun(): void
    {
        $job = new CronJob();
        $job->jobname = 'TestMaintenance';
        $job->pluginname = 'TestPluginMaintenance';
        $job->setMockDateTime('2025-03-05 10:00:00');
        $this->assertTrue($job->save());
        $this->assertTrue($job->every('1 minute')->isReady());

        // en mantenimiento, el job se rechaza
        $this->assertTrue(Maintenance::enable());
        $executed = false;
        $this->assertFalse($job->every('1 minute')->isReady());
        $this->assertFalse($job->run(function () use (&$executed) {
            $executed = true;
        }));
        $this->assertFalse($executed);

        // al terminar el mantenimiento, se ejecuta
        $this->assertTrue(Maintenance::disable());
        $this->assertTrue($job->every('1 minute')->run(function () use (&$executed) {
            $executed = true;
        }));
        $this->assertTrue($executed);

        $this->assertTrue($job->delete());
    }

    public function testWorkQueueDoesNotRun(): void
    {
        // eliminamos los eventos pendientes y añadimos uno
        $model = new WorkEvent();
        foreach ($model->all([], [], 0, 0) as $event) {
            $event->delete();
        }
        WorkQueue::clear();
        WorkQueue::addWorker('TestWorker', 'test-maintenance');
        $this->assertTrue(WorkQueue::send('test-maintenance', 'test-value'));

        // en mantenimiento, el evento se queda pendiente
        $this->assertTrue(Maintenance::enable());
        $this->assertFalse(WorkQueue::run());
        $events = $model->all([], [], 0, 0);
        $this->assertCount(1, $events);
        $this->assertFalse($events[0]->done);

        $this->assertTrue($events[0]->delete());
        WorkQueue::clear();
    }

    public function testDeployReleasesItsOwnMaintenance(): void
    {
        Plugins::deploy(false);
        $this->assertFalse(Maintenance::isActive());
    }

    public function testDeployKeepsPreviousMaintenance(): void
    {
        // si el mantenimiento lo activó otro (el actualizador), el deploy no lo desactiva
        $this->assertTrue(Maintenance::enable());
        $token = Maintenance::token();

        Plugins::deploy(false);
        $this->assertTrue(Maintenance::isActive());
        $this->assertSame($token, Maintenance::token());
    }

    public function testIndexRejectsCronFromCli(): void
    {
        // con el mantenimiento activo, index.php no llega a ejecutar el cron
        touch(Maintenance::filePath());

        $output = [];
        $code = 1;
        exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(FS_FOLDER . '/index.php') . ' -cron 2>&1', $output, $code);

        $this->assertSame(0, $code);
        $this->assertSame(['An update is being installed. Please try again in a few seconds.'], $output);
    }
}
