<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\ImpuestoZona;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class ImpuestoZonaTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

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

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
