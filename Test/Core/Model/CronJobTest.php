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

namespace FacturaScripts\Test\Core\Model;

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

    public function testCantCreateWithoutPlugin(): void
    {
        $job = new CronJob();
        $job->jobname = 'TestName';
        $this->assertFalse($job->save());
    }

    public function testEveryFunction(): void
    {
        $job = new CronJob();
        $job->jobname = 'TestName';
        $job->pluginname = 'TestPlugin';
        $this->assertTrue($job->every('1 day'));
        $this->assertTrue($job->save());
        $this->assertFalse($job->every('1 day'));

        $this->assertFalse($job->done);

        // ahora ponemos fecha de hace un día
        $job->date = Tools::dateTime('-1 day');
        $this->assertFalse($job->every('2 days'));
        $this->assertTrue($job->every('1 day'));
        $this->assertTrue($job->every('6 hours'));
        $this->assertTrue($job->every('1 hour'));
        $this->assertTrue($job->every('30 minutes'));

        $this->assertFalse($job->done);

        // ahora ponemos fecha de hace una hora
        $job->date = Tools::dateTime('-1 hour');
        $this->assertFalse($job->every('1 day'));
        $this->assertFalse($job->every('2 hours'));
        $this->assertTrue($job->every('1 hour'));
        $this->assertTrue($job->every('30 minutes'));

        $this->assertFalse($job->done);

        // desactivamos
        $job->enabled = true;
        $job->date = Tools::dateTime('-1 hour');
        $this->assertFalse($job->every('1 day'));
        $this->assertFalse($job->every('2 hours'));
        $this->assertTrue($job->every('1 hour'));
        $this->assertTrue($job->every('30 minutes'));

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
        $this->assertTrue($job->everyDay($currentDay, 1));
        $this->assertTrue($job->save());

        // como ya se ha ejecutado, si decimos de ejecutar hoy, no se ejecutará
        $this->assertFalse($job->everyDayAt($currentDay, 1));

        // como ya se ha ejecutado, si decimos de ejecutar mañana, no se ejecutará
        $this->assertFalse($job->everyDayAt($currentDay + 1, 1));

        // eliminamos
        $this->assertTrue($job->delete());
    }

    public function testEveryDayAtFunction(): void
    {
        $job = new CronJob();
        $job->jobname = 'TestName';
        $job->pluginname = 'TestPlugin';
        $this->assertTrue($job->everyDayAt(0));
        $this->assertTrue($job->save());
        $this->assertFalse($job->everyDayAt(0));

        $this->assertFalse($job->done);

        // ahora ponemos fecha de hace un día
        $job->date = Tools::dateTime('-1 day');
        $hour = (int)date('H');
        $this->assertTrue($job->everyDayAt($hour));
        $this->assertFalse($job->everyDayAt($hour + 1));

        $this->assertFalse($job->done);

        // eliminamos
        $this->assertTrue($job->delete());
    }

    public function testDoneFunction(): void
    {
        $job = new CronJob();
        $job->jobname = 'TestName';
        $job->pluginname = 'TestPlugin';
        $this->assertTrue($job->everyDayAt(0));

        // esperamos 1 segundo
        sleep(1);

        $this->assertTrue($job->done());
        $this->assertTrue($job->done);
        $this->assertGreaterThan(0.0, $job->duration);

        // eliminamos
        $this->assertTrue($job->delete());
    }
}
