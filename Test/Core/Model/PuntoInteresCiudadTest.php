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
use FacturaScripts\Core\Model\Provincia;
use FacturaScripts\Core\Model\PuntoInteresCiudad;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class PuntoInteresCiudadTest extends TestCase
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
        // Crear un punto de interés
        $puntoInteres = new PuntoInteresCiudad();
        $puntoInteres->name = 'Plaza Mayor';
        $puntoInteres->alias = 'plaza-mayor';
        $puntoInteres->idciudad = self::$ciudad->idciudad;
        $puntoInteres->latitude = 40.4168;
        $puntoInteres->longitude = -3.7038;

        $this->assertTrue($puntoInteres->save(), 'punto-interes-cant-save');
        $this->assertTrue($puntoInteres->exists(), 'punto-interes-cant-persist');
        $this->assertNotEmpty($puntoInteres->id, 'punto-interes-empty-id');

        // Verificar que se establecieron los campos automáticos
        $this->assertNotEmpty($puntoInteres->creation_date, 'punto-interes-empty-creation-date');

        // Eliminar
        $this->assertTrue($puntoInteres->delete(), 'punto-interes-cant-delete');
    }

    public function testHtmlEscaping(): void
    {
        // Crear un punto de interés con HTML en el nombre y alias
        $puntoInteres = new PuntoInteresCiudad();
        $puntoInteres->name = '<b>Museo del Prado</b>';
        $puntoInteres->alias = '<script>alert("test")</script>';
        $puntoInteres->idciudad = self::$ciudad->idciudad;
        $puntoInteres->latitude = 40.4138;
        $puntoInteres->longitude = -3.6921;

        $this->assertTrue($puntoInteres->save(), 'punto-interes-cant-save');

        // Verificar que el HTML ha sido escapado
        $noHtmlName = Tools::noHtml('<b>Museo del Prado</b>');
        $noHtmlAlias = Tools::noHtml('<script>alert("test")</script>');
        $this->assertEquals($noHtmlName, $puntoInteres->name, 'punto-interes-wrong-html-name');
        $this->assertEquals($noHtmlAlias, $puntoInteres->alias, 'punto-interes-wrong-html-alias');

        // Eliminar
        $this->assertTrue($puntoInteres->delete(), 'punto-interes-cant-delete');
    }

    public function testUpdate(): void
    {
        // Crear un punto de interés
        $puntoInteres = new PuntoInteresCiudad();
        $puntoInteres->name = 'Parque del Retiro';
        $puntoInteres->alias = 'retiro';
        $puntoInteres->idciudad = self::$ciudad->idciudad;
        $puntoInteres->latitude = 40.4152;
        $puntoInteres->longitude = -3.6844;
        $this->assertTrue($puntoInteres->save(), 'punto-interes-cant-save');

        $originalDate = $puntoInteres->creation_date;

        // Actualizar
        $puntoInteres->name = 'Parque del Buen Retiro';
        $this->assertTrue($puntoInteres->save(), 'punto-interes-cant-update');

        // Verificar que se actualizaron los campos
        $this->assertEquals($originalDate, $puntoInteres->creation_date, 'punto-interes-creation-date-changed');
        $this->assertNotEmpty($puntoInteres->last_update, 'punto-interes-empty-last-update');

        // Eliminar
        $this->assertTrue($puntoInteres->delete(), 'punto-interes-cant-delete');
    }

    public function testGetCity(): void
    {
        // Crear un punto de interés
        $puntoInteres = new PuntoInteresCiudad();
        $puntoInteres->name = 'Catedral';
        $puntoInteres->alias = 'catedral';
        $puntoInteres->idciudad = self::$ciudad->idciudad;
        $puntoInteres->latitude = 40.4165;
        $puntoInteres->longitude = -3.7026;
        $this->assertTrue($puntoInteres->save(), 'punto-interes-cant-save');

        // Obtener la ciudad asociada
        $city = $puntoInteres->getCity();
        $this->assertNotNull($city, 'punto-interes-city-null');
        $this->assertEquals(self::$ciudad->idciudad, $city->idciudad, 'punto-interes-wrong-city');
        $this->assertEquals('Test City', $city->ciudad, 'punto-interes-wrong-city-name');

        // Eliminar
        $this->assertTrue($puntoInteres->delete(), 'punto-interes-cant-delete');
    }

    public function testCoordinates(): void
    {
        // Crear un punto de interés con coordenadas específicas
        $puntoInteres = new PuntoInteresCiudad();
        $puntoInteres->name = 'Puerta del Sol';
        $puntoInteres->alias = 'puerta-sol';
        $puntoInteres->idciudad = self::$ciudad->idciudad;
        $puntoInteres->latitude = 40.416775;
        $puntoInteres->longitude = -3.703790;
        $this->assertTrue($puntoInteres->save(), 'punto-interes-cant-save');

        // Verificar que las coordenadas se guardaron correctamente
        $this->assertEquals(40.416775, $puntoInteres->latitude, 'punto-interes-wrong-latitude');
        $this->assertEquals(-3.703790, $puntoInteres->longitude, 'punto-interes-wrong-longitude');

        // Eliminar
        $this->assertTrue($puntoInteres->delete(), 'punto-interes-cant-delete');
    }

    public function testWithoutRequiredFields(): void
    {
        // Intentar crear sin campos requeridos
        $puntoInteres = new PuntoInteresCiudad();
        $this->assertFalse($puntoInteres->save(), 'punto-interes-saved-without-required-fields');

        // Solo con nombre
        $puntoInteres->name = 'Test Point';
        $this->assertFalse($puntoInteres->save(), 'punto-interes-saved-without-city');

        // Con nombre y ciudad pero sin coordenadas
        $puntoInteres->idciudad = self::$ciudad->idciudad;
        $this->assertTrue($puntoInteres->save(), 'punto-interes-cant-save-without-coordinates');

        // Eliminar
        $this->assertTrue($puntoInteres->delete(), 'punto-interes-cant-delete');
    }

    public function testWithoutCoordinates(): void
    {
        // Crear un punto de interés sin coordenadas
        $puntoInteres = new PuntoInteresCiudad();
        $puntoInteres->name = 'Punto sin coordenadas';
        $puntoInteres->alias = 'punto-sin-coordenadas';
        $puntoInteres->idciudad = self::$ciudad->idciudad;

        $this->assertTrue($puntoInteres->save(), 'punto-interes-cant-save-without-coordinates');
        $this->assertNull($puntoInteres->latitude, 'punto-interes-latitude-not-null');
        $this->assertNull($puntoInteres->longitude, 'punto-interes-longitude-not-null');

        // Eliminar
        $this->assertTrue($puntoInteres->delete(), 'punto-interes-cant-delete');
    }

    public function testMultiplePointsForSameCity(): void
    {
        // Crear múltiples puntos de interés para la misma ciudad
        $punto1 = new PuntoInteresCiudad();
        $punto1->name = 'Museo Reina Sofía';
        $punto1->alias = 'reina-sofia';
        $punto1->idciudad = self::$ciudad->idciudad;
        $punto1->latitude = 40.4080;
        $punto1->longitude = -3.6944;
        $this->assertTrue($punto1->save(), 'first-punto-interes-cant-save');

        $punto2 = new PuntoInteresCiudad();
        $punto2->name = 'Museo Thyssen';
        $punto2->alias = 'thyssen';
        $punto2->idciudad = self::$ciudad->idciudad;
        $punto2->latitude = 40.4159;
        $punto2->longitude = -3.6944;
        $this->assertTrue($punto2->save(), 'second-punto-interes-cant-save');

        // Verificar que ambos existen
        $this->assertTrue($punto1->exists(), 'first-punto-interes-not-exists');
        $this->assertTrue($punto2->exists(), 'second-punto-interes-not-exists');
        $this->assertNotEquals($punto1->id, $punto2->id, 'puntos-interes-same-id');

        // Eliminar
        $this->assertTrue($punto1->delete(), 'first-punto-interes-cant-delete');
        $this->assertTrue($punto2->delete(), 'second-punto-interes-cant-delete');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
