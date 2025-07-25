<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\Ciudad;
use FacturaScripts\Core\Model\CodigoPostal;
use FacturaScripts\Core\Model\Provincia;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class CodigoPostalTest extends TestCase
{
    use LogErrorsTrait;

    private static $ciudad;
    private static $provincia;

    public static function setUpBeforeClass(): void
    {
        // Crear provincia para las pruebas
        self::$provincia = new Provincia();
        self::$provincia->provincia = 'Test Province';
        self::$provincia->codpais = 'ESP';
        self::$provincia->save();

        // Crear ciudad para las pruebas
        self::$ciudad = new Ciudad();
        self::$ciudad->ciudad = 'Test City';
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
        $codigoPostal->codpais = 'ESP';
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
        $codigoPostal1->codpais = 'ESP';
        $this->assertTrue($codigoPostal1->save(), 'first-codigo-postal-cant-save');

        // Crear segundo código postal para la misma ciudad
        $codigoPostal2 = new CodigoPostal();
        $codigoPostal2->number = 28007;
        $codigoPostal2->idciudad = self::$ciudad->idciudad;
        $codigoPostal2->idprovincia = self::$provincia->idprovincia;
        $codigoPostal2->codpais = 'ESP';
        $this->assertTrue($codigoPostal2->save(), 'second-codigo-postal-cant-save');

        // Verificar que ambos existen
        $this->assertTrue($codigoPostal1->exists(), 'first-codigo-postal-not-exists');
        $this->assertTrue($codigoPostal2->exists(), 'second-codigo-postal-not-exists');
        $this->assertNotEquals($codigoPostal1->id, $codigoPostal2->id, 'codigo-postales-same-id');

        // Eliminar
        $this->assertTrue($codigoPostal1->delete(), 'first-codigo-postal-cant-delete');
        $this->assertTrue($codigoPostal2->delete(), 'second-codigo-postal-cant-delete');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
