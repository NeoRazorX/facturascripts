<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2025-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Lib\Import\CSVImport;
use FacturaScripts\Core\Model\Ciudad;
use FacturaScripts\Core\Model\CodigoPostal;
use FacturaScripts\Core\Model\Pais;
use FacturaScripts\Core\Model\Provincia;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class CodigoPostalTest extends TestCase
{
    use LogErrorsTrait;

    private const SPAIN_DATA = FS_FOLDER . '/Core/Data/Codpais/ESP/';

    private static $ciudad;
    private static $pais;
    private static $provincia;

    public static function setUpBeforeClass(): void
    {
        $num = rand(1, 99);

        // Crear país para las pruebas
        self::$pais = new Pais();
        self::$pais->codpais = 'T' . $num;
        self::$pais->nombre = 'Test Country ' . $num;
        self::$pais->save();

        // Crear provincia para las pruebas
        self::$provincia = new Provincia();
        self::$provincia->provincia = 'Test Province ' . $num;
        self::$provincia->codpais = self::$pais->codpais;
        self::$provincia->save();

        // Crear ciudad para las pruebas
        self::$ciudad = new Ciudad();
        self::$ciudad->ciudad = 'Test City ' . $num;
        self::$ciudad->idprovincia = self::$provincia->idprovincia;
        self::$ciudad->save();
    }

    public static function tearDownAfterClass(): void
    {
        // Limpiar datos de prueba
        if (self::$ciudad) {
            self::$ciudad->delete();
        }
        if (self::$provincia) {
            self::$provincia->delete();
        }
        if (self::$pais) {
            self::$pais->delete();
        }
    }

    public function testCreate(): void
    {
        // Crear un código postal
        $codigoPostal = new CodigoPostal();
        $codigoPostal->number = 28001;
        $codigoPostal->idciudad = self::$ciudad->idciudad;
        $codigoPostal->idprovincia = self::$provincia->idprovincia;
        $codigoPostal->codpais = 'ESP';

        $this->assertTrue($codigoPostal->save(), 'codigo-postal-cant-save');
        $this->assertTrue($codigoPostal->exists(), 'codigo-postal-cant-persist');
        $this->assertNotEmpty($codigoPostal->id, 'codigo-postal-empty-id');

        // Verificar que se establecieron los campos automáticos
        $this->assertNotEmpty($codigoPostal->creation_date, 'codigo-postal-empty-creation-date');

        // Eliminar
        $this->assertTrue($codigoPostal->delete(), 'codigo-postal-cant-delete');
    }

    public function testDefaultValues(): void
    {
        $codigoPostal = new CodigoPostal();

        // Verificar valores por defecto
        $defaultCountry = Tools::settings('default', 'codpais', 'ESP');
        $this->assertEquals($defaultCountry, $codigoPostal->codpais, 'codigo-postal-wrong-default-country');
    }

    public function testUpdate(): void
    {
        // Crear un código postal
        $codigoPostal = new CodigoPostal();
        $codigoPostal->number = 28002;
        $codigoPostal->idciudad = self::$ciudad->idciudad;
        $codigoPostal->idprovincia = self::$provincia->idprovincia;
        $codigoPostal->codpais = self::$pais->codpais;
        $this->assertTrue($codigoPostal->save(), 'codigo-postal-cant-save');

        $originalDate = $codigoPostal->creation_date;
        $originalNick = $codigoPostal->nick;

        // Actualizar
        $codigoPostal->number = 28003;
        $this->assertTrue($codigoPostal->save(), 'codigo-postal-cant-update');

        // Verificar que se actualizaron los campos
        $this->assertEquals($originalDate, $codigoPostal->creation_date, 'codigo-postal-creation-date-changed');
        $this->assertEquals($originalNick, $codigoPostal->nick, 'codigo-postal-nick-changed');
        $this->assertNotEmpty($codigoPostal->last_update, 'codigo-postal-empty-last-update');

        // Eliminar
        $this->assertTrue($codigoPostal->delete(), 'codigo-postal-cant-delete');
    }

    public function testWithoutRequiredFields(): void
    {
        // Intentar crear sin campos requeridos
        $codigoPostal = new CodigoPostal();
        $this->assertFalse($codigoPostal->save(), 'codigo-postal-saved-without-required-fields');

        // Solo con número
        $codigoPostal->number = 28005;
        $this->assertFalse($codigoPostal->save(), 'codigo-postal-saved-without-city');

        // Con número y ciudad
        $codigoPostal->idciudad = self::$ciudad->idciudad;
        $this->assertFalse($codigoPostal->save(), 'codigo-postal-saved-without-province');
    }

    public function testMultiplePostalCodesForSameCity(): void
    {
        // Crear primer código postal
        $codigoPostal1 = new CodigoPostal();
        $codigoPostal1->number = 28006;
        $codigoPostal1->idciudad = self::$ciudad->idciudad;
        $codigoPostal1->idprovincia = self::$provincia->idprovincia;
        $codigoPostal1->codpais = self::$pais->codpais;
        $this->assertTrue($codigoPostal1->save(), 'first-codigo-postal-cant-save');

        // Crear segundo código postal para la misma ciudad
        $codigoPostal2 = new CodigoPostal();
        $codigoPostal2->number = 28007;
        $codigoPostal2->idciudad = self::$ciudad->idciudad;
        $codigoPostal2->idprovincia = self::$provincia->idprovincia;
        $codigoPostal2->codpais = self::$pais->codpais;
        $this->assertTrue($codigoPostal2->save(), 'second-codigo-postal-cant-save');

        // Verificar que ambos existen
        $this->assertTrue($codigoPostal1->exists(), 'first-codigo-postal-not-exists');
        $this->assertTrue($codigoPostal2->exists(), 'second-codigo-postal-not-exists');
        $this->assertNotEquals($codigoPostal1->id, $codigoPostal2->id, 'codigo-postales-same-id');

        // Eliminar
        $this->assertTrue($codigoPostal1->delete(), 'first-codigo-postal-cant-delete');
        $this->assertTrue($codigoPostal2->delete(), 'second-codigo-postal-cant-delete');
    }

    public function testKeepsLeadingZeros(): void
    {
        // creamos un código postal con ceros a la izquierda
        $codigoPostal = new CodigoPostal();
        $codigoPostal->number = '08001';
        $codigoPostal->idciudad = self::$ciudad->idciudad;
        $codigoPostal->idprovincia = self::$provincia->idprovincia;
        $codigoPostal->codpais = self::$pais->codpais;
        $this->assertTrue($codigoPostal->save(), 'codigo-postal-cant-save');

        // al volver a cargarlo conserva los ceros
        $loaded = new CodigoPostal();
        $this->assertTrue($loaded->load($codigoPostal->id), 'codigo-postal-cant-load');
        $this->assertSame('08001', $loaded->number, 'codigo-postal-lost-leading-zeros');

        // eliminamos
        $this->assertTrue($codigoPostal->delete(), 'codigo-postal-cant-delete');
    }

    public function testAlphanumeric(): void
    {
        // creamos un código postal con letras y espacios exteriores
        $codigoPostal = new CodigoPostal();
        $codigoPostal->number = '  SW1A 1AA ';
        $codigoPostal->idciudad = self::$ciudad->idciudad;
        $codigoPostal->idprovincia = self::$provincia->idprovincia;
        $codigoPostal->codpais = self::$pais->codpais;
        $this->assertTrue($codigoPostal->save(), 'codigo-postal-cant-save');
        $this->assertSame('SW1A 1AA', $codigoPostal->number, 'codigo-postal-not-trimmed');

        // al volver a cargarlo conserva el valor
        $loaded = new CodigoPostal();
        $this->assertTrue($loaded->load($codigoPostal->id), 'codigo-postal-cant-load');
        $this->assertSame('SW1A 1AA', $loaded->number, 'codigo-postal-alphanumeric-changed');

        // eliminamos
        $this->assertTrue($codigoPostal->delete(), 'codigo-postal-cant-delete');
    }

    public function testNumericValueIsStoredAsText(): void
    {
        // un entero, como los que puede enviar la API, se guarda como texto
        $codigoPostal = new CodigoPostal();
        $codigoPostal->number = 28010;
        $codigoPostal->idciudad = self::$ciudad->idciudad;
        $codigoPostal->idprovincia = self::$provincia->idprovincia;
        $codigoPostal->codpais = self::$pais->codpais;
        $this->assertTrue($codigoPostal->save(), 'codigo-postal-cant-save');
        $this->assertSame('28010', $codigoPostal->number, 'codigo-postal-not-text');

        // eliminamos
        $this->assertTrue($codigoPostal->delete(), 'codigo-postal-cant-delete');
    }

    public function testCantSaveEmptyOrTooLong(): void
    {
        $codigoPostal = new CodigoPostal();
        $codigoPostal->idciudad = self::$ciudad->idciudad;
        $codigoPostal->idprovincia = self::$provincia->idprovincia;
        $codigoPostal->codpais = self::$pais->codpais;

        // vacío
        $codigoPostal->number = '';
        $this->assertFalse($codigoPostal->save(), 'empty-codigo-postal-saved');

        // solo espacios
        $codigoPostal->number = '   ';
        $this->assertFalse($codigoPostal->save(), 'blank-codigo-postal-saved');

        // más de 10 caracteres
        $codigoPostal->number = '12345678901';
        $this->assertFalse($codigoPostal->save(), 'too-long-codigo-postal-saved');

        // el html escapado también puede superar los 10 caracteres
        $codigoPostal->number = '<b>28001';
        $this->assertFalse($codigoPostal->save(), 'html-codigo-postal-saved');
    }

    public function testFindPlaces(): void
    {
        // creamos una segunda ciudad, con html en el nombre, en la misma provincia
        $ciudad2 = new Ciudad();
        $ciudad2->ciudad = "A d'Test " . mt_rand(1, 9999);
        $ciudad2->idprovincia = self::$provincia->idprovincia;
        $this->assertTrue($ciudad2->save(), 'city-cant-save');

        // el mismo código postal para las dos ciudades y otro distinto solo para la primera
        $zipCodes = [];
        foreach ([[self::$ciudad, '09901'], [$ciudad2, '09901'], [self::$ciudad, '09902']] as [$ciudad, $number]) {
            $zipCode = new CodigoPostal();
            $zipCode->number = $number;
            $zipCode->idciudad = $ciudad->idciudad;
            $zipCode->idprovincia = self::$provincia->idprovincia;
            $zipCode->codpais = self::$pais->codpais;
            $this->assertTrue($zipCode->save(), 'codigo-postal-cant-save');
            $zipCodes[] = $zipCode;
        }

        // un código con una sola ciudad
        $places = CodigoPostal::findPlaces(' 09902 ', self::$pais->codpais);
        $this->assertCount(1, $places, 'zip-code-places-not-found');
        $this->assertEquals(self::$ciudad->ciudad, $places[0]['ciudad']);
        $this->assertEquals(self::$provincia->provincia, $places[0]['provincia']);

        // un código con dos ciudades: vienen ordenadas y sin html escapado
        $places = CodigoPostal::findPlaces('09901', self::$pais->codpais);
        $this->assertCount(2, $places, 'zip-code-places-bad-count');
        $this->assertStringStartsWith("A d'Test ", $places[0]['ciudad'], 'zip-code-places-html-not-fixed');
        $this->assertEquals(self::$ciudad->ciudad, $places[1]['ciudad']);

        // sin país busca en todos
        $this->assertCount(2, CodigoPostal::findPlaces('09901'), 'zip-code-places-without-country');

        // otro país, un código que no existe o vacío no devuelven nada
        $this->assertSame([], CodigoPostal::findPlaces('09901', 'XXX'));
        $this->assertSame([], CodigoPostal::findPlaces('09903', self::$pais->codpais));
        $this->assertSame([], CodigoPostal::findPlaces('   '));

        // eliminamos
        foreach ($zipCodes as $zipCode) {
            $this->assertTrue($zipCode->delete(), 'codigo-postal-cant-delete');
        }
        $this->assertTrue($ciudad2->delete(), 'city-cant-delete');
    }

    public function testSpainDataFile(): void
    {
        // cargamos las ciudades de España del archivo de datos
        $cities = [];
        foreach (CSVImport::readFile(self::SPAIN_DATA . 'ciudades.csv')->data as $row) {
            $cities[$row['idciudad']] = $row['idprovincia'];
        }
        $this->assertNotEmpty($cities, 'spain-cities-file-empty');

        // cada código postal debe apuntar a una ciudad del archivo y a su provincia
        $keys = [];
        $rows = CSVImport::readFile(self::SPAIN_DATA . 'codigos_postales.csv')->data;
        $this->assertNotEmpty($rows, 'spain-zip-codes-file-empty');
        foreach ($rows as $row) {
            $this->assertEquals('ESP', $row['codpais'], 'zip-code-bad-country');
            $this->assertMatchesRegularExpression('/^[0-9]{5}$/', $row['number'], 'zip-code-bad-number');
            $this->assertArrayHasKey($row['idciudad'], $cities, 'zip-code-city-not-found: ' . $row['number']);
            $this->assertEquals(
                $cities[$row['idciudad']],
                $row['idprovincia'],
                'zip-code-bad-province: ' . $row['number']
            );
            $keys[$row['idprovincia'] . '|' . $row['idciudad'] . '|' . $row['number']] = true;
        }

        // no puede haber filas repetidas, la tabla tiene una restricción única
        $this->assertCount(count($rows), $keys, 'zip-code-duplicated-rows');
    }

    public function testSpainDataFileCanBeImported(): void
    {
        // necesitamos las ciudades de España instaladas y la tabla de códigos postales vacía
        $db = new DataBase();
        $cities = Ciudad::table()
            ->whereEq('ciudad', 'Zurgena')
            ->whereEq('idciudad', 8140)
            ->count();
        if ($cities !== 1 || CodigoPostal::count() > 0) {
            $this->markTestSkipped('Spain cities are not installed or there are zip codes.');
        }

        // importamos el archivo dentro de una transacción para no dejar datos
        $sql = CSVImport::importFileSQL(CodigoPostal::tableName(), self::SPAIN_DATA . 'codigos_postales.csv');
        $this->assertNotEmpty($sql, 'spain-zip-codes-empty-sql');

        $db->beginTransaction();
        $imported = $db->exec($sql);
        $total = CodigoPostal::count();
        $barcelona = CodigoPostal::findWhere([Where::eq('number', '08001')]);
        $db->rollback();

        $this->assertTrue($imported, 'spain-zip-codes-cant-import');
        $this->assertGreaterThan(10000, $total, 'spain-zip-codes-not-imported');
        $this->assertNotNull($barcelona, 'zip-code-08001-not-found');
        $this->assertSame('08001', $barcelona->number, 'zip-code-lost-leading-zeros');
        $this->assertEquals(0, CodigoPostal::count(), 'spain-zip-codes-not-rolled-back');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
