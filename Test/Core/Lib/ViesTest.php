<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core\Lib;

use FacturaScripts\Core\Lib\Vies;
use PHPUnit\Framework\TestCase;

final class ViesTest extends TestCase
{
    protected function setUp(): void
    {
        Vies::simulateViesResponse(null);
        Vies::simulateFetchResponse(null);
    }

    protected function tearDown(): void
    {
        Vies::simulateViesResponse(null);
        Vies::simulateFetchResponse(null);
    }

    public function testSimulateResponse(): void
    {
        // sin simulación, los flags se ignoran y el método decide normalmente;
        // con simulación, check() debe devolver el valor simulado sin tocar SOAP.
        Vies::simulateViesResponse(Vies::RESULT_VALID);
        $this->assertSame(Vies::RESULT_VALID, Vies::check('75897326V', 'ES'));

        Vies::simulateViesResponse(Vies::RESULT_INVALID);
        $this->assertSame(Vies::RESULT_INVALID, Vies::check('75897326V', 'ES'));

        Vies::simulateViesResponse(Vies::RESULT_ERROR);
        $this->assertSame(Vies::RESULT_ERROR, Vies::check('75897326V', 'ES'));

        // la simulación cortocircuita incluso entradas que normalmente fallarían
        // en la validación local (codiso vacío, cifnif corto, país no UE).
        Vies::simulateViesResponse(Vies::RESULT_VALID);
        $this->assertSame(Vies::RESULT_VALID, Vies::check('', ''));
        $this->assertSame(Vies::RESULT_VALID, Vies::check('1', 'US'));

        // al desactivarla, vuelve el comportamiento normal: codiso inválido => ERROR.
        Vies::simulateViesResponse(null);
        $this->assertSame(Vies::RESULT_ERROR, Vies::check('75897326V', ''));
    }

    public function testSimulateFetchResponse(): void
    {
        // con simulación, fetch() devuelve el array fijado sin tocar SOAP.
        $fake = ['valid' => true, 'name' => 'ACME SA', 'address' => 'C/ Mayor 1'];
        Vies::simulateFetchResponse($fake);
        $this->assertSame($fake, Vies::fetch('75897326V', 'ES'));

        // un NIF inválido simulado devuelve array con valid=false, no null.
        $invalid = ['valid' => false, 'name' => '', 'address' => ''];
        Vies::simulateFetchResponse($invalid);
        $this->assertSame($invalid, Vies::fetch('75897326V', 'ES'));

        // cortocircuita incluso entradas que normalmente fallarían en la validación local.
        Vies::simulateFetchResponse($fake);
        $this->assertSame($fake, Vies::fetch('', ''));

        // al desactivarla, validación local: codiso inválido => null.
        Vies::simulateFetchResponse(null);
        $this->assertNull(Vies::fetch('75897326V', '', false));
    }

    public function testFetchValidation(): void
    {
        // mismas ramas locales que testCheckValidation pero para fetch():
        // todas deben devolver null antes de tocar SOAP.
        $this->assertNull(Vies::fetch('75897326V', '', false));
        $this->assertNull(Vies::fetch('75897326V', 'ESP', false));
        $this->assertNull(Vies::fetch('123456789', 'US', false));
        $this->assertNull(Vies::fetch('12', 'ES', false));
        $this->assertNull(Vies::fetch('ES12', 'ES', false));
    }

    public function testCheckValidation(): void
    {
        // sin simulación: estos casos deben cortar en la validación local
        // y devolver RESULT_ERROR sin llegar a SOAP.

        // codiso vacío
        $this->assertSame(Vies::RESULT_ERROR, Vies::check('75897326V', '', false));

        // codiso con longitud != 2
        $this->assertSame(Vies::RESULT_ERROR, Vies::check('75897326V', 'ESP', false));
        $this->assertSame(Vies::RESULT_ERROR, Vies::check('75897326V', 'E', false));

        // codiso no UE
        $this->assertSame(Vies::RESULT_ERROR, Vies::check('123456789', 'US', false));
        $this->assertSame(Vies::RESULT_ERROR, Vies::check('123456789', 'MX', false));

        // cifnif corto puro
        $this->assertSame(Vies::RESULT_ERROR, Vies::check('12', 'ES', false));

        // cifnif corto tras quitar el prefijo ISO (regresión: antes del reorden
        // de ifs esta entrada llegaba a VIES con '12' como vatNumber).
        $this->assertSame(Vies::RESULT_ERROR, Vies::check('ES12', 'ES', false));
    }

    /**
     * @dataProvider normalizeProvider
     */
    public function testNormalize(string $expected, string $cifnif, string $codiso): void
    {
        $this->assertSame($expected, Vies::normalize($cifnif, $codiso));
    }

    public function normalizeProvider(): array
    {
        return [
            'sin prefijo'                  => ['75897326V', '75897326V', 'ES'],
            'con prefijo ES'               => ['75897326V', 'ES75897326V', 'ES'],
            'con guiones'                  => ['75897326V', 'ES-75897326-V', 'ES'],
            'con puntos y espacios'        => ['75897326V', ' ES 758.97326 V ', 'ES'],
            'minúsculas con prefijo'       => ['75897326V', 'es75897326v', 'ES'],
            'caracteres mixtos'            => ['75897326V', 'es_758/97326\\v', 'ES'],
            'codiso distinto, no se quita' => ['ES75897326V', 'ES75897326V', 'FR'],
            'cadena vacía'                 => ['', '', 'ES'],
            'sólo prefijo'                 => ['', 'ES', 'ES'],
            'XI con prefijo'               => ['123456789', 'XI123456789', 'XI'],
        ];
    }

    public function testNorthernIrelandIsEu(): void
    {
        // XI (Irlanda del Norte) debe estar reconocido como código UE para VIES.
        $this->assertContains('XI', Vies::EU_COUNTRIES);
    }

    /**
     * @group integration
     * @dataProvider viesCasesProvider
     */
    public function testCheckIntegration(int $expected, string $number, string $iso): void
    {
        $check = Vies::check($number, $iso, false);

        // si esperábamos un resultado distinto de ERROR y VIES respondió ERROR,
        // saltamos sólo este caso (red caída, rate-limit, etc.).
        if ($check === Vies::RESULT_ERROR && $expected !== Vies::RESULT_ERROR) {
            $this->markTestSkipped('Vies service returns error: ' . Vies::getLastError());
        }

        $this->assertSame(
            $expected,
            $check,
            "Vies::check({$number}, {$iso}) returned {$check}, expected {$expected}"
        );

        // no saturamos el servicio
        usleep(500000);
    }

    public function viesCasesProvider(): array
    {
        return [
            'iso vacío'              => [Vies::RESULT_ERROR, '', ''],
            'iso vacío con número'   => [Vies::RESULT_ERROR, '123', ''],
            'ES inválido'            => [Vies::RESULT_INVALID, '123456789', 'ES'],
            'ES inválido con prefijo' => [Vies::RESULT_INVALID, 'ES74003828J', 'ES'],
            'ES válido con prefijo'  => [Vies::RESULT_VALID, 'ES75897326V', 'ES'],
            'FR válido con prefijo'  => [Vies::RESULT_VALID, 'FR38821737384', 'FR'],
            'FR inválido'            => [Vies::RESULT_INVALID, '81328757100011', 'FR'],
            'PT válido 1'            => [Vies::RESULT_VALID, '514356480', 'PT'],
            'PT válido 2'            => [Vies::RESULT_VALID, '513969144', 'PT'],
            'PT inválido'            => [Vies::RESULT_INVALID, '513967144', 'PT'],
        ];
    }

    /**
     * @group integration
     */
    public function testFetchIntegration(): void
    {
        $info = Vies::fetch('ES75897326V', 'ES', false);

        if ($info === null) {
            $this->markTestSkipped('Vies service returns error: ' . Vies::getLastError());
        }

        $this->assertArrayHasKey('valid', $info);
        $this->assertArrayHasKey('name', $info);
        $this->assertArrayHasKey('address', $info);
        $this->assertTrue($info['valid']);
        // España publica nombre y dirección, así que esperamos algo no vacío.
        $this->assertNotSame('', $info['name']);

        usleep(500000);
    }
}
