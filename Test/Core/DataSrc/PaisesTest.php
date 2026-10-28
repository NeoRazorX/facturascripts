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

namespace FacturaScripts\Test\Core\DataSrc;

use FacturaScripts\Core\DataSrc\Paises;
use FacturaScripts\Core\Lib\Vies;
use PHPUnit\Framework\TestCase;

final class PaisesTest extends TestCase
{
    public function testMiembrosUE(): void
    {
        $this->assertCount(27, Paises::MIEMBROS_UE);
        $this->assertTrue(Paises::miembroUEbyIso('ES'));
        $this->assertTrue(Paises::miembroUEbyIso('GR'));

        // el Reino Unido salió de la UE el 31/01/2020
        $this->assertFalse(Paises::miembroUEbyIso('GB'));
        $this->assertFalse(Paises::miembroUEbyIso('US'));

        // todos los miembros tienen prefijo de NIF-IVA en VIES
        foreach (Paises::MIEMBROS_UE as $iso) {
            $this->assertContains(Vies::vatPrefix($iso), Vies::EU_COUNTRIES, 'vat-prefix-not-in-vies: ' . $iso);
        }
    }
}
