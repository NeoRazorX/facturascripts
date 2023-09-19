<?php

declare(strict_types=1);

namespace FacturaScripts\Test\Core\Model;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Model\Ejercicio;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

class EjercicioTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;
    use DefaultSettingsTrait;

    protected function setUp(): void
    {
        $db = new DataBase();
        $db->connect();

        static::setDefaultSettings();
    }

    //Comprobar que se puede crear un ejercicio (y que se crea abierto) y borrarlo.
    public function testItCanCreateExercise()
    {
        // Lo creamos con fecha del próximo año porque al pasar los
        // test se instancia la clase 'Ejercicio' y se crea un ejercicio para este año automaticamente
        // por lo que da un error si se quiere crear otro ejercicio para este mismo año
        $nextYear = date('Y', strtotime(date('Y') . ' + 1 year'));

        $codejercicio = 'test';

        $ejercicio = new Ejercicio();
        $ejercicio->codejercicio = $codejercicio;
        $ejercicio->nombre = 'exercise-test';
        $ejercicio->fechainicio = $nextYear . '-01-01';
        $ejercicio->fechafin = $nextYear . '-12-31';
        self::assertTrue($ejercicio->save());

        // Obtenemos el ejercicio de la base de datos
        // y comprobamos que se crea abierto
        $ejercicio = new Ejercicio();
        $ejercicio->loadFromCode($codejercicio);
        self::assertEquals(Ejercicio::EXERCISE_STATUS_OPEN, $ejercicio->estado);

        // Comprobamos que se elimina correctamente
        self::assertTrue($ejercicio->delete());

        // Obtenemos el ejercicio de la base de datos
        // y comprobamos que ya no existe
        $ejercicio = new Ejercicio();
        self::assertFalse($ejercicio->loadFromCode($codejercicio));
    }

    //Comprobar que no se puede crear un ejercio con fecha de inicio posterior a la fecha de fin.
    public function testItCanNotCreateExerciseWrongDate()
    {
        MiniLog::clear();

        $ejercicio = new Ejercicio();
        $ejercicio->codejercicio = 'test';
        $ejercicio->nombre = 'exercise-test';
        $ejercicio->fechainicio = '2033-06-16';
        $ejercicio->fechafin = '2033-06-15';
        self::assertFalse($ejercicio->save());

        $miniLog = MiniLog::read();
        self::assertEquals('start-date-later-end-date', $miniLog[0]['message']);
    }

    //Crear un ejercicio para 2099 y comprobar que si solicitamos un ejercicio para esa fecha, lo devuelve.
    public function testItCanReturnExerciseFromDate()
    {
        $idempresa = 1;

        $ejercicio = new Ejercicio();
        $ejercicio->idempresa = $idempresa;
        $ejercicio->codejercicio = 'test';
        $ejercicio->nombre = 'exercise-test';
        $ejercicio->fechainicio = '2099-01-01';
        $ejercicio->fechafin = '2099-12-31';
        self::assertTrue($ejercicio->save());

        $ejercicio = new Ejercicio();
        $ejercicio->idempresa = $idempresa;
        self::assertTrue($ejercicio->loadFromDate('2099-06-15', true, false));

        // Eliminamos
        self::assertTrue($ejercicio->delete());
    }

    //Comprobar que se pueden crear dos ejercicios para la misma fecha pero distinta empresa.
    public function testItCanCreateExercisesFromDifferentCompanies()
    {
        $fechainicio = '2099-01-01';
        $fechafin = '2099-12-31';

        $empresa1 = $this->getRandomCompany();
        self::assertTrue($empresa1->save());

        $empresa2 = $this->getRandomCompany();
        self::assertTrue($empresa2->save());

        $ejercicioEmpresa1 = new Ejercicio();
        $ejercicioEmpresa1->idempresa = $empresa1->idempresa;
        $ejercicioEmpresa1->codejercicio = 'E-1';
        $ejercicioEmpresa1->nombre = 'exercise-test-1';
        $ejercicioEmpresa1->fechainicio = $fechainicio;
        $ejercicioEmpresa1->fechafin = $fechafin;
        self::assertTrue($ejercicioEmpresa1->save());

        $ejercicioEmpresa2 = new Ejercicio();
        $ejercicioEmpresa2->idempresa = $empresa2->idempresa;
        $ejercicioEmpresa2->codejercicio = 'E-2';
        $ejercicioEmpresa2->nombre = 'exercise-test-2';
        $ejercicioEmpresa2->fechainicio = $fechainicio;
        $ejercicioEmpresa2->fechafin = $fechafin;
        self::assertTrue($ejercicioEmpresa2->save());

        $ejercicio = new Ejercicio();
        self::assertTrue($ejercicio->loadFromCode($ejercicioEmpresa1->codejercicio));

        $ejercicio = new Ejercicio();
        self::assertTrue($ejercicio->loadFromCode($ejercicioEmpresa2->codejercicio));

        // Eliminamos
        self::assertTrue($ejercicioEmpresa1->delete());
        self::assertTrue($ejercicioEmpresa2->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
