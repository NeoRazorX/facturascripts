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

namespace FacturaScripts\Test\Core\Model;

use Exception;
use FacturaScripts\Core\Model\CronJob;
use FacturaScripts\Core\Tools;
use PHPUnit\Framework\TestCase;

final class CronJobTest extends TestCase
{
    public function testCreate(): void
    {
        $job = new CronJob();
        $job->jobname = 'TestName';
        $job->pluginname = 'TestPlugin';
        $this->assertTrue($job->save());

        // comprobamos que existe
        $this->assertNotNull($job->primaryColumnValue());
        $this->assertTrue($job->exists());

        // lo cargamos desde otro objeto
        $job2 = new CronJob();
        $job2->loadFromCode($job->primaryColumnValue());
        $this->assertEquals('TestName', $job2->jobname);
        $this->assertEquals('TestPlugin', $job2->pluginname);
        $this->assertTrue($job2->enabled);
        $this->assertFalse($job2->done);
        $this->assertFalse($job2->failed);
        $this->assertEquals(0.0, $job2->duration);

        // eliminamos
        $this->assertTrue($job->delete());

        // comprobamos que no existe
        $this->assertFalse($job->exists());
    }

    public function testCantCreateWithoutName(): void
    {
        $job = new CronJob();
        $job->pluginname = 'TestPlugin';
        $this->assertFalse($job->save());
    }

    public function testCanCreateWithoutPlugin(): void
    {
        // creamos un trabajo sin plugin
        $job = new CronJob();
        $job->jobname = 'TestName';
        $this->assertTrue($job->save());

        // eliminamos
        $this->assertTrue($job->delete());
    }

    public function testEveryFunction(): void
    {
        $job = new CronJob();
        $job->jobname = 'TestName';
        $job->pluginname = 'TestPlugin';
        $this->assertTrue($job->every('1 day')->isReady());
        $this->assertTrue($job->save());
        $this->assertFalse($job->every('1 day')->isReady());

        $this->assertFalse($job->done);

        // ahora ponemos fecha de hace un día
        $job->date = Tools::dateTime('-1 day');
        $this->assertFalse($job->every('2 days')->isReady());
        $this->assertTrue($job->every('1 day')->isReady());
        $this->assertTrue($job->every('6 hours')->isReady());
        $this->assertTrue($job->every('1 hour')->isReady());
        $this->assertTrue($job->every('30 minutes')->isReady());

        $this->assertFalse($job->done);

        // ahora ponemos fecha de hace una hora
        $job->date = Tools::dateTime('-1 hour');
        $this->assertFalse($job->every('1 day')->isReady());
        $this->assertFalse($job->every('2 hours')->isReady());
        $this->assertTrue($job->every('1 hour')->isReady());
        $this->assertTrue($job->every('30 minutes')->isReady());

        $this->assertFalse($job->done);

        // desactivamos
        $job->enabled = true;
        $job->date = Tools::dateTime('-1 hour');
        $this->assertFalse($job->every('1 day')->isReady());
        $this->assertFalse($job->every('2 hours')->isReady());
        $this->assertTrue($job->every('1 hour')->isReady());
        $this->assertTrue($job->every('30 minutes')->isReady());

        $this->assertFalse($job->done);

        // eliminamos
        $this->assertTrue($job->delete());
    }

    public function testEveryDayFunction(): void
    {
        $currentDay = date('d') < 28 ? (int)date('d') : 10;

        $job = new CronJob();
        $job->jobname = 'TestName';
        $job->pluginname = 'TestPlugin';

        // como nunca se ha ejecutado, si decimos de ejecutar hoy, se ejecutará
        $this->assertTrue($job->everyDay($currentDay, 1)->isReady());
        $this->assertTrue($job->save());

        // como ya se ha ejecutado, si decimos de ejecutar hoy, no se ejecutará
        $this->assertFalse($job->everyDayAt($currentDay, 1)->isReady());

        // como ya se ha ejecutado, si decimos de ejecutar mañana, no se ejecutará
        $this->assertFalse($job->everyDayAt($currentDay + 1, 1)->isReady());

        // eliminamos
        $this->assertTrue($job->delete());
    }

    public function testEveryDayAtFunction(): void
    {
        $job = new CronJob();
        $job->jobname = 'TestName';
        $job->pluginname = 'TestPlugin';
        $this->assertTrue($job->everyDayAt(0)->isReady());
        $this->assertTrue($job->save());
        $this->assertFalse($job->everyDayAt(0)->isReady());

        $this->assertFalse($job->done);

        // ahora ponemos fecha de hace un día
        $job->date = Tools::dateTime('-1 day');
        $hour = (int)date('H');
        $this->assertTrue($job->everyDayAt($hour)->isReady());
        $this->assertFalse($job->everyDayAt($hour + 1)->isReady());

        $this->assertFalse($job->done);

        // eliminamos
        $this->assertTrue($job->delete());
    }

    public function testRunFunction(): void
    {
        $job = new CronJob();
        $job->jobname = 'TestName';
        $job->pluginname = 'TestPlugin';
        $this->assertTrue($job->everyDayAt(0)->isReady());

        $this->assertTrue(
            $job->run(function () {
                // esperamos 1 segundo
                sleep(1);

                return true;
            })
        );
        $this->assertTrue($job->done);
        $this->assertFalse($job->failed);
        $this->assertGreaterThan(0.99, $job->duration);

        // eliminamos
        $this->assertTrue($job->delete());
    }

    public function testRunFailFunction(): void
    {
        $job = new CronJob();
        $job->jobname = 'TestName';
        $job->pluginname = 'TestPlugin';
        $this->assertTrue($job->everyDayAt(0)->isReady());

        $this->assertFalse(
            $job->run(function () {
                // esperamos 1 segundo
                sleep(1);

                throw new Exception('Test');
            })
        );
        $this->assertTrue($job->done);
        $this->assertTrue($job->failed);
        $this->assertGreaterThan(0.99, $job->duration);

        // eliminamos
        $this->assertTrue($job->delete());
    }

    public function testWithoutOverlapping(): void
    {
        // creamos un trabajo en ejecución
        $job1 = new CronJob();
        $job1->jobname = 'TestName';
        $job1->pluginname = 'TestPlugin';
        $this->assertTrue($job1->save());

        // creamos un segundo trabajo que no se ejecutará
        $job2 = new CronJob();
        $job2->jobname = 'TestName2';
        $job2->pluginname = 'TestPlugin';
        $job2->date = Tools::dateTime('-10 minutes');
        $job2->done = true;
        $this->assertTrue($job2->save());
        $this->assertFalse($job2->every('1 minute')->withoutOverlapping()->isReady());

        // creamos un tercer trabajo que si se ejecutará
        $job3 = new CronJob();
        $job3->jobname = 'TestName3';
        $job3->pluginname = 'TestPlugin';
        $job3->date = Tools::dateTime('-1 hour');
        $this->assertTrue($job3->withoutOverlapping('TestName2')->every('1 minute')->isReady());

        // marcamos el primer trabajo como terminado
        $job1->done = true;
        $this->assertTrue($job1->save());

        // comprobamos que el segundo trabajo se ejecutará
        $this->assertTrue($job2->every('1 minute')->withoutOverlapping()->isReady());

        // eliminamos
        $this->assertTrue($job1->delete());
        $this->assertTrue($job2->delete());
    }
}
