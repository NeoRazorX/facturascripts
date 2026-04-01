<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Model\ImpuestoZona;
use FacturaScripts\Core\Model\PresupuestoCliente;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class ImpuestoZonaTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
    }

    public function testCreate(): void
    {
        // creamos un país
        $country = $this->getRandomCountry();
        $this->assertTrue($country->save());

        // creamos una provincia
        $province = $this->getRandomProvince($country->codpais);
        $this->assertTrue($province->save());

        // creamos dos impuestos
        $tax1 = $this->getRandomTax();
        $this->assertTrue($tax1->save());
        $tax2 = $this->getRandomTax();
        $this->assertTrue($tax2->save());

        // creamos un impuesto por zona
        $impuestoZona = new ImpuestoZona();
        $impuestoZona->codimpuesto = $tax1->codimpuesto;
        $impuestoZona->codimpuestosel = $tax2->codimpuesto;
        $impuestoZona->codpais = $country->codpais;
        $impuestoZona->prioridad = 5;
        $this->assertTrue($impuestoZona->save());

        // comprobamos que existe en la base de datos
        $this->assertTrue($impuestoZona->exists());

        // comprobamos que se ha asignado un id
        $this->assertNotNull($impuestoZona->id);

        // eliminamos
        $this->assertTrue($impuestoZona->delete());
        $this->assertTrue($tax1->delete());
        $this->assertTrue($tax2->delete());
        $this->assertTrue($province->delete());
        $this->assertTrue($country->delete());
    }

    public function testCantCreateWithoutTax(): void
    {
        $impuestoZona = new ImpuestoZona();
        $impuestoZona->codimpuesto = null;
        $impuestoZona->codimpuestosel = null;
        $this->assertFalse($impuestoZona->save());
    }

    public function testMatchProvinciaWithNullCodisopro(): void
    {
        // sin provincia definida, debe coincidir con cualquier valor
        $impuestoZona = new ImpuestoZona();
        $impuestoZona->codisopro = null;

        $this->assertTrue($impuestoZona->matchProvincia('Madrid'));
        $this->assertTrue($impuestoZona->matchProvincia('MADRID'));
        $this->assertTrue($impuestoZona->matchProvincia(null));
    }

    public function testMatchProvinciaWithProvince(): void
    {
        // creamos un país y una provincia
        $country = $this->getRandomCountry();
        $this->assertTrue($country->save());

        $province = $this->getRandomProvince($country->codpais);
        $this->assertTrue($province->save());

        $impuestoZona = new ImpuestoZona();
        $impuestoZona->codisopro = $province->idprovincia;

        // coincidencia exacta
        $this->assertTrue($impuestoZona->matchProvincia($province->provincia));

        // coincidencia en mayúsculas
        $this->assertTrue($impuestoZona->matchProvincia(strtoupper($province->provincia)));

        // coincidencia en minúsculas
        $this->assertTrue($impuestoZona->matchProvincia(strtolower($province->provincia)));

        // no coincide con otra provincia
        $this->assertFalse($impuestoZona->matchProvincia('OtraProvincia'));

        // no coincide con null
        $this->assertFalse($impuestoZona->matchProvincia(null));

        // eliminamos
        $this->assertTrue($province->delete());
        $this->assertTrue($country->delete());
    }

    public function testMatchPaisWithNullCodpais(): void
    {
        // sin país definido, debe coincidir con cualquier país y provincia
        $impuestoZona = new ImpuestoZona();
        $impuestoZona->codpais = null;
        $impuestoZona->codisopro = null;

        $this->assertTrue($impuestoZona->matchPais('ESP', 'Madrid'));
        $this->assertTrue($impuestoZona->matchPais('FRA', null));
        $this->assertTrue($impuestoZona->matchPais(null, null));
    }

    public function testMatchPaisWithCountryAndNoProvince(): void
    {
        // país definido pero sin provincia, debe coincidir con cualquier provincia del país
        $country = $this->getRandomCountry();
        $this->assertTrue($country->save());

        $impuestoZona = new ImpuestoZona();
        $impuestoZona->codpais = $country->codpais;
        $impuestoZona->codisopro = null;

        $this->assertTrue($impuestoZona->matchPais($country->codpais, 'Madrid'));
        $this->assertTrue($impuestoZona->matchPais($country->codpais, null));
        $this->assertFalse($impuestoZona->matchPais('OTRO', 'Madrid'));

        $this->assertTrue($country->delete());
    }

    public function testMatchPaisWithCountryAndProvince(): void
    {
        // país y provincia definidos
        $country = $this->getRandomCountry();
        $this->assertTrue($country->save());

        $province = $this->getRandomProvince($country->codpais);
        $this->assertTrue($province->save());

        $impuestoZona = new ImpuestoZona();
        $impuestoZona->codpais = $country->codpais;
        $impuestoZona->codisopro = $province->idprovincia;

        // coincidencia exacta
        $this->assertTrue($impuestoZona->matchPais($country->codpais, $province->provincia));

        // coincidencia en mayúsculas
        $this->assertTrue($impuestoZona->matchPais($country->codpais, strtoupper($province->provincia)));

        // país correcto pero provincia incorrecta
        $this->assertFalse($impuestoZona->matchPais($country->codpais, 'OtraProvincia'));

        // país incorrecto
        $this->assertFalse($impuestoZona->matchPais('OTRO', $province->provincia));

        $this->assertTrue($province->delete());
        $this->assertTrue($country->delete());
    }

    public function testTaxZoneAppliedOnDocumentWithUppercaseProvince(): void
    {
        // creamos un país y una provincia
        $country = $this->getRandomCountry();
        $this->assertTrue($country->save());

        $province = $this->getRandomProvince($country->codpais);
        $this->assertTrue($province->save());

        // creamos dos impuestos
        $tax1 = $this->getRandomTax();
        $this->assertTrue($tax1->save());
        $tax2 = $this->getRandomTax();
        $this->assertTrue($tax2->save());

        // creamos la zona: tax1 → tax2 para ese país y provincia
        $zone = new ImpuestoZona();
        $zone->codimpuesto = $tax1->codimpuesto;
        $zone->codimpuestosel = $tax2->codimpuesto;
        $zone->codpais = $country->codpais;
        $zone->codisopro = $province->idprovincia;
        $zone->prioridad = 10;
        $this->assertTrue($zone->save());

        // creamos un cliente
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save());

        // creamos el presupuesto con el país y la provincia en MAYÚSCULAS
        $doc = new PresupuestoCliente();
        $this->assertTrue($doc->setSubject($subject));
        $doc->codpais = $country->codpais;
        $doc->provincia = strtoupper($province->provincia);
        $this->assertTrue($doc->save());

        // añadimos una línea con tax1
        $line = $doc->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 100;
        $line->codimpuesto = $tax1->codimpuesto;
        $line->iva = $tax1->iva;
        $this->assertTrue($line->save());

        // calculamos
        $lines = $doc->getLines();
        $this->assertTrue(Calculator::calculate($doc, $lines, true));

        // la zona debe haber cambiado el impuesto a tax2
        $lines = $doc->getLines();
        $this->assertEquals($tax2->codimpuesto, $lines[0]->codimpuesto, 'tax-zone-not-applied-uppercase-province');
        $this->assertEquals($tax2->iva, $lines[0]->iva, 'tax-zone-bad-iva');

        // eliminamos
        $this->assertTrue($doc->delete());
        $this->assertTrue($subject->getDefaultAddress()->delete());
        $this->assertTrue($subject->delete());
        $this->assertTrue($zone->delete());
        $this->assertTrue($tax1->delete());
        $this->assertTrue($tax2->delete());
        $this->assertTrue($province->delete());
        $this->assertTrue($country->delete());
    }

    public function testTaxZoneNotAppliedOnDocumentWithDifferentProvince(): void
    {
        // creamos un país y dos provincias
        $country = $this->getRandomCountry();
        $this->assertTrue($country->save());

        $province1 = $this->getRandomProvince($country->codpais);
        $this->assertTrue($province1->save());

        $province2 = $this->getRandomProvince($country->codpais);
        $this->assertTrue($province2->save());

        // creamos dos impuestos
        $tax1 = $this->getRandomTax();
        $this->assertTrue($tax1->save());
        $tax2 = $this->getRandomTax();
        $this->assertTrue($tax2->save());

        // la zona aplica solo para province1
        $zone = new ImpuestoZona();
        $zone->codimpuesto = $tax1->codimpuesto;
        $zone->codimpuestosel = $tax2->codimpuesto;
        $zone->codpais = $country->codpais;
        $zone->codisopro = $province1->idprovincia;
        $zone->prioridad = 10;
        $this->assertTrue($zone->save());

        // creamos un cliente
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save());

        // creamos el presupuesto con province2 (no debe aplicarse la zona)
        $doc = new PresupuestoCliente();
        $this->assertTrue($doc->setSubject($subject));
        $doc->codpais = $country->codpais;
        $doc->provincia = $province2->provincia;
        $this->assertTrue($doc->save());

        // añadimos una línea con tax1
        $line = $doc->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 100;
        $line->codimpuesto = $tax1->codimpuesto;
        $line->iva = $tax1->iva;
        $this->assertTrue($line->save());

        // calculamos
        $lines = $doc->getLines();
        $this->assertTrue(Calculator::calculate($doc, $lines, true));

        // la zona NO debe haberse aplicado: el impuesto sigue siendo tax1
        $lines = $doc->getLines();
        $this->assertEquals($tax1->codimpuesto, $lines[0]->codimpuesto, 'tax-zone-applied-wrong-province');

        // eliminamos
        $this->assertTrue($doc->delete());
        $this->assertTrue($subject->getDefaultAddress()->delete());
        $this->assertTrue($subject->delete());
        $this->assertTrue($zone->delete());
        $this->assertTrue($tax1->delete());
        $this->assertTrue($tax2->delete());
        $this->assertTrue($province1->delete());
        $this->assertTrue($province2->delete());
        $this->assertTrue($country->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
