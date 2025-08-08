<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class CronJobTest extends TestCase
{
    use LogErrorsTrait;

    public function testCreate(): void
    {
        // creamos un trabajo
        $job = new CronJob();
        $job->jobname = 'TestName';
        $job->pluginname = 'TestPlugin';
        $this->assertNull($job->date);
        $this->assertTrue($job->enabled);
        $this->assertTrue($job->save());

        // comprobamos que existe
        $this->assertNotNull($job->id());
        $this->assertTrue($job->exists());

        // lo cargamos desde otro objeto
        $job2 = new CronJob();
        $job2->load($job->id());
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
        $job->pluginname = 'TestPlugin1';
        $this->assertFalse($job->save());
    }

    public function testCanCreateWithoutPlugin(): void
    {
        // creamos un trabajo sin plugin
        $job = new CronJob();
        $job->jobname = 'TestName2';
        $this->assertTrue($job->save());

        // eliminamos
        $this->assertTrue($job->delete());
    }

    public function testDisabled(): void
    {
        // creamos un trabajo que no se ejecuta
        $job = new CronJob();
        $job->jobname = 'TestName3';
        $job->pluginname = 'TestPlugin3';
        $job->enabled = false;
        $this->assertTrue($job->save());

        // comprobamos que no se puede ejecutar
        $this->assertFalse($job->every('1 day')->isReady());
        $this->assertFalse($job->every('1 hour')->isReady());

        // eliminamos
        $this->assertTrue($job->delete());
    }

    public function testEveryFunction(): void
    {
        // creamos un trabajo que se ejecuta cada día
        $job = new CronJob();
        $job->jobname = 'TestName4';
        $job->pluginname = 'TestPlugin4';
        $this->assertTrue($job->every('1 day')->isReady());
        $this->assertTrue($job->save());

        // comprobamos que se ha guardado y que no se puede volver a ejecutar hoy
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
        $job->enabled = false;
        $job->date = Tools::dateTime('-2 days');
        $this->assertFalse($job->every('1 day')->isReady());
        $this->assertFalse($job->every('2 hours')->isReady());
        $this->assertFalse($job->every('1 hour')->isReady());
        $this->assertFalse($job->every('30 minutes')->isReady());
        $this->assertFalse($job->done);

        // eliminamos
        $this->assertTrue($job->delete());
    }

    public function testEveryDayFunction(): void
    {
        $job = new CronJob();
        $job->jobname = 'TestName5';
        $job->pluginname = 'TestPlugin5';

        // establecemos la fecha de hoy a 2025-03-05 12:00:00
        $job->setMockDateTime('2025-03-05 12:00:00');

        // como nunca se ha ejecutado, si decimos de ejecutar hoy, se ejecutará
        $this->assertTrue($job->everyDay(5, 1)->isReady());
        $this->assertTrue($job->save());

        // como ya se ha ejecutado, si decimos de ejecutar hoy, no se ejecutará
        $this->assertFalse($job->everyDay(5, 1)->isReady());

        // como ya se ha ejecutado, si decimos de ejecutar mañana, no se ejecutará
        $this->assertFalse($job->everyDay(6, 1)->isReady());

        // eliminamos
        $this->assertTrue($job->delete());
    }

    public function testEveryDayStrictFunction(): void
    {
        $job = new CronJob();
        $job->jobname = 'TestName6';
        $job->pluginname = 'TestPlugin5';

        // establecemos la fecha de hoy a 2025-03-05 12:00:00
        $job->setMockDateTime('2025-03-05 12:00:00');

        // si decimos de ejecutar hoy estrictamente a la 1, no se ejecutará
        $this->assertFalse($job->everyDay(5, 1, true)->isReady());

        // si decimos de ejecutar hoy a las 12, se ejecutará
        $this->assertTrue($job->everyDay(5, 12, true)->isReady());
        $this->assertTrue($job->save());

        // si decimos de volver a ejecutar hoy a las 12, no se ejecutará
        $this->assertFalse($job->everyDay(5, 12, true)->isReady());

        // eliminamos
        $this->assertTrue($job->delete());
    }

    public function testEveryDayAtFunction(): void
    {
        $job = new CronJob();
        $job->jobname = 'TestName6';
        $job->pluginname = 'TestPlugin6';
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

    public function testEveryDayAtStrictFunction(): void
    {
        $job = new CronJob();
        $job->jobname = 'TestName7';
        $job->pluginname = 'TestPlugin7';

        // establecemos la fecha de hoy a 2025-03-05 12:00:00
        $job->setMockDateTime('2025-03-05 12:00:00');

        // si decimos de ejecutar hoy estrictamente a la 1, no se ejecutará
        $this->assertFalse($job->everyDayAt(1, true)->isReady());

        // si decimos de ejecutar hoy a las 12, se ejecutará
        $this->assertTrue($job->everyDayAt(12, true)->isReady());
        $this->assertTrue($job->save());

        // si decimos de volver a ejecutar hoy a las 12, no se ejecutará
        $this->assertFalse($job->everyDayAt(12, true)->isReady());

        // eliminamos
        $this->assertTrue($job->delete());
    }

    public function testRunFunction(): void
    {
        $job = new CronJob();
        $job->jobname = 'TestName7';
        $job->pluginname = 'TestPlugin7';
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
        $job->jobname = 'TestName8';
        $job->pluginname = 'TestPlugin8';
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
        $job1->jobname = 'TestName9';
        $job1->pluginname = 'TestPlugin9';
        $this->assertTrue($job1->save());

        // creamos un segundo trabajo que no se ejecutará
        $job2 = new CronJob();
        $job2->jobname = 'TestName10';
        $job2->pluginname = 'TestPlugin9';
        $job2->date = Tools::dateTime('-10 minutes');
        $job2->done = true;
        $this->assertTrue($job2->save());
        $this->assertFalse($job2->every('1 minute')->withoutOverlapping()->isReady());

        // creamos un tercer trabajo que si se ejecutará
        $job3 = new CronJob();
        $job3->jobname = 'TestName11';
        $job3->pluginname = 'TestPlugin9';
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

    public function testEveryMondayAtFunction(): void
    {
        $job = new CronJob();
        $job->jobname = 'TestName12';
        $job->pluginname = 'TestPlugin12';

        // probamos en domingo - NO se debe ejecutar
        $job->setMockDateTime('2025-03-02 14:00:00'); // domingo
        $this->assertFalse($job->everyMondayAt(14)->isReady());

        // probamos en lunes - SÍ se debe ejecutar
        $job->setMockDateTime('2025-03-03 14:00:00'); // lunes
        $this->assertTrue($job->everyMondayAt(14)->isReady());
        $this->assertTrue($job->save());

        // ya se ha ejecutado hoy, no se ejecutará de nuevo
        $this->assertFalse($job->everyMondayAt(14)->isReady());

        // probamos en martes - NO se debe ejecutar
        $job->setMockDateTime('2025-03-04 14:00:00'); // martes
        $this->assertFalse($job->everyMondayAt(14)->isReady());

        // probamos en miércoles - NO se debe ejecutar
        $job->setMockDateTime('2025-03-05 14:00:00'); // miércoles
        $this->assertFalse($job->everyMondayAt(14)->isReady());

        // avanzamos una semana al siguiente lunes - SÍ se debe ejecutar
        $job->date = '2025-03-03 14:00:00'; // resetear última ejecución al lunes anterior
        $job->setMockDateTime('2025-03-10 14:00:00'); // siguiente lunes
        $this->assertTrue($job->everyMondayAt(14)->isReady());

        // eliminamos
        $this->assertTrue($job->delete());
    }

    public function testEveryTuesdayAtFunction(): void
    {
        $job = new CronJob();
        $job->jobname = 'TestName13';
        $job->pluginname = 'TestPlugin13';

        // probamos en lunes - NO se debe ejecutar
        $job->setMockDateTime('2025-03-03 10:00:00'); // lunes
        $this->assertFalse($job->everyTuesdayAt(10)->isReady());

        // probamos en martes - SÍ se debe ejecutar
        $job->setMockDateTime('2025-03-04 10:00:00'); // martes
        $this->assertTrue($job->everyTuesdayAt(10)->isReady());
        $this->assertTrue($job->save());

        // ya se ha ejecutado hoy, no se ejecutará de nuevo
        $this->assertFalse($job->everyTuesdayAt(10)->isReady());

        // probamos en miércoles - NO se debe ejecutar
        $job->setMockDateTime('2025-03-05 10:00:00'); // miércoles
        $this->assertFalse($job->everyTuesdayAt(10)->isReady());

        // avanzamos una semana al siguiente martes - SÍ se debe ejecutar
        $job->date = '2025-03-04 10:00:00'; // resetear última ejecución al martes anterior
        $job->setMockDateTime('2025-03-11 10:00:00'); // siguiente martes
        $this->assertTrue($job->everyTuesdayAt(10)->isReady());

        // eliminamos
        $this->assertTrue($job->delete());
    }

    public function testEveryWednesdayAtFunction(): void
    {
        $job = new CronJob();
        $job->jobname = 'TestName14';
        $job->pluginname = 'TestPlugin14';

        // probamos en martes - NO se debe ejecutar
        $job->setMockDateTime('2025-03-04 16:00:00'); // martes
        $this->assertFalse($job->everyWednesdayAt(16)->isReady());

        // probamos en miércoles - SÍ se debe ejecutar
        $job->setMockDateTime('2025-03-05 16:00:00'); // miércoles
        $this->assertTrue($job->everyWednesdayAt(16)->isReady());
        $this->assertTrue($job->save());

        // ya se ha ejecutado hoy, no se ejecutará de nuevo
        $this->assertFalse($job->everyWednesdayAt(16)->isReady());

        // probamos en jueves - NO se debe ejecutar
        $job->setMockDateTime('2025-03-06 16:00:00'); // jueves
        $this->assertFalse($job->everyWednesdayAt(16)->isReady());

        // avanzamos una semana al siguiente miércoles - SÍ se debe ejecutar
        $job->date = '2025-03-05 16:00:00'; // resetear última ejecución al miércoles anterior
        $job->setMockDateTime('2025-03-12 16:00:00'); // siguiente miércoles
        $this->assertTrue($job->everyWednesdayAt(16)->isReady());

        // eliminamos
        $this->assertTrue($job->delete());
    }

    public function testEveryThursdayAtFunction(): void
    {
        $job = new CronJob();
        $job->jobname = 'TestName15';
        $job->pluginname = 'TestPlugin15';

        // probamos en miércoles - NO se debe ejecutar
        $job->setMockDateTime('2025-03-05 09:00:00'); // miércoles
        $this->assertFalse($job->everyThursdayAt(9)->isReady());

        // probamos en jueves - SÍ se debe ejecutar
        $job->setMockDateTime('2025-03-06 09:00:00'); // jueves
        $this->assertTrue($job->everyThursdayAt(9)->isReady());
        $this->assertTrue($job->save());

        // ya se ha ejecutado hoy, no se ejecutará de nuevo
        $this->assertFalse($job->everyThursdayAt(9)->isReady());

        // probamos en viernes - NO se debe ejecutar
        $job->setMockDateTime('2025-03-07 09:00:00'); // viernes
        $this->assertFalse($job->everyThursdayAt(9)->isReady());

        // avanzamos una semana al siguiente jueves - SÍ se debe ejecutar
        $job->date = '2025-03-06 09:00:00'; // resetear última ejecución al jueves anterior
        $job->setMockDateTime('2025-03-13 09:00:00'); // siguiente jueves
        $this->assertTrue($job->everyThursdayAt(9)->isReady());

        // eliminamos
        $this->assertTrue($job->delete());
    }

    public function testEveryFridayAtFunction(): void
    {
        $job = new CronJob();
        $job->jobname = 'TestName16';
        $job->pluginname = 'TestPlugin16';

        // probamos en jueves - NO se debe ejecutar
        $job->setMockDateTime('2025-03-06 18:00:00'); // jueves
        $this->assertFalse($job->everyFridayAt(18)->isReady());

        // probamos en viernes - SÍ se debe ejecutar
        $job->setMockDateTime('2025-03-07 18:00:00'); // viernes
        $this->assertTrue($job->everyFridayAt(18)->isReady());
        $this->assertTrue($job->save());

        // ya se ha ejecutado hoy, no se ejecutará de nuevo
        $this->assertFalse($job->everyFridayAt(18)->isReady());

        // probamos en sábado - NO se debe ejecutar
        $job->setMockDateTime('2025-03-08 18:00:00'); // sábado
        $this->assertFalse($job->everyFridayAt(18)->isReady());

        // avanzamos una semana al siguiente viernes - SÍ se debe ejecutar
        $job->date = '2025-03-07 18:00:00'; // resetear última ejecución al viernes anterior
        $job->setMockDateTime('2025-03-14 18:00:00'); // siguiente viernes
        $this->assertTrue($job->everyFridayAt(18)->isReady());

        // eliminamos
        $this->assertTrue($job->delete());
    }

    public function testEverySaturdayAtFunction(): void
    {
        $job = new CronJob();
        $job->jobname = 'TestName17';
        $job->pluginname = 'TestPlugin17';

        // probamos en viernes - NO se debe ejecutar
        $job->setMockDateTime('2025-03-07 08:00:00'); // viernes
        $this->assertFalse($job->everySaturdayAt(8)->isReady());

        // probamos en sábado - SÍ se debe ejecutar
        $job->setMockDateTime('2025-03-08 08:00:00'); // sábado
        $this->assertTrue($job->everySaturdayAt(8)->isReady());
        $this->assertTrue($job->save());

        // ya se ha ejecutado hoy, no se ejecutará de nuevo
        $this->assertFalse($job->everySaturdayAt(8)->isReady());

        // probamos en domingo - NO se debe ejecutar
        $job->setMockDateTime('2025-03-09 08:00:00'); // domingo
        $this->assertFalse($job->everySaturdayAt(8)->isReady());

        // avanzamos una semana al siguiente sábado - SÍ se debe ejecutar
        $job->date = '2025-03-08 08:00:00'; // resetear última ejecución al sábado anterior
        $job->setMockDateTime('2025-03-15 08:00:00'); // siguiente sábado
        $this->assertTrue($job->everySaturdayAt(8)->isReady());

        // eliminamos
        $this->assertTrue($job->delete());
    }

    public function testEverySundayAtFunction(): void
    {
        $job = new CronJob();
        $job->jobname = 'TestName18';
        $job->pluginname = 'TestPlugin18';

        // probamos en sábado - NO se debe ejecutar
        $job->setMockDateTime('2025-03-08 20:00:00'); // sábado
        $this->assertFalse($job->everySundayAt(20)->isReady());

        // probamos en domingo - SÍ se debe ejecutar
        $job->setMockDateTime('2025-03-09 20:00:00'); // domingo
        $this->assertTrue($job->everySundayAt(20)->isReady());
        $this->assertTrue($job->save());

        // ya se ha ejecutado hoy, no se ejecutará de nuevo
        $this->assertFalse($job->everySundayAt(20)->isReady());

        // probamos en lunes - NO se debe ejecutar
        $job->setMockDateTime('2025-03-10 20:00:00'); // lunes
        $this->assertFalse($job->everySundayAt(20)->isReady());

        // avanzamos una semana al siguiente domingo - SÍ se debe ejecutar
        $job->date = '2025-03-09 20:00:00'; // resetear última ejecución al domingo anterior
        $job->setMockDateTime('2025-03-16 20:00:00'); // siguiente domingo
        $this->assertTrue($job->everySundayAt(20)->isReady());

        // eliminamos
        $this->assertTrue($job->delete());
    }

    public function testEveryLastDayOfMonthAtFunction(): void
    {
        $job = new CronJob();
        $job->jobname = 'TestName19';
        $job->pluginname = 'TestPlugin19';

        // establecemos la fecha al último día del mes: 2025-03-31 15:00:00
        $job->setMockDateTime('2025-03-31 15:00:00');

        // como nunca se ha ejecutado, se ejecutará
        $this->assertTrue($job->everyLastDayOfMonthAt(15)->isReady());
        $this->assertTrue($job->save());

        // ya se ha ejecutado hoy, no se ejecutará de nuevo
        $this->assertFalse($job->everyLastDayOfMonthAt(15)->isReady());

        // probamos con modo estricto en hora incorrecta
        $job2 = new CronJob();
        $job2->jobname = 'TestName20';
        $job2->pluginname = 'TestPlugin20';
        $job2->setMockDateTime('2025-03-31 15:00:00');

        // no se ejecutará porque no es la hora exacta
        $this->assertFalse($job2->everyLastDayOfMonthAt(16, true)->isReady());

        // se ejecutará porque es la hora exacta
        $this->assertTrue($job2->everyLastDayOfMonthAt(15, true)->isReady());

        // eliminamos
        $this->assertTrue($job->delete());
        $this->assertTrue($job2->save());
        $this->assertTrue($job2->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
