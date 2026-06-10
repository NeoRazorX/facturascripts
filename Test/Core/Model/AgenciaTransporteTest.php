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

use FacturaScripts\Core\Model\AgenciaTransporte;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class AgenciaTransporteTest extends TestCase
{
    use LogErrorsTrait;

    public function testDataInstalled(): void
    {
        // llamamos de forma estática
        $this->assertNotEmpty(AgenciaTransporte::all(), 'agency-data-not-installed-from-csv');

        // llamamos de forma dinámica
        $agency = new AgenciaTransporte();
        $this->assertNotEmpty($agency->all(), 'agency-data-not-installed-from-csv');
    }

    public function testClear(): void
    {
        $agency = new AgenciaTransporte();
        $this->assertTrue($agency->activo);
        $this->assertNull($agency->codtrans);
        $this->assertNull($agency->nombre);
        $this->assertNull($agency->telefono);
        $this->assertNull($agency->web);
    }

    public function testCreate(): void
    {
        $agency = new AgenciaTransporte();
        $agency->codtrans = 'Test';
        $agency->nombre = 'Test Agency';
        $this->assertTrue($agency->save(), 'agency-cant-save');
        $this->assertNotNull($agency->id(), 'agency-not-stored');
        $this->assertTrue($agency->exists(), 'agency-cant-persist');
        $this->assertTrue($agency->delete(), 'agency-cant-delete');
    }

    public function testCreateWithNewCode(): void
    {
        $agency = new AgenciaTransporte();
        $agency->nombre = 'Test Agency with new code';
        $this->assertTrue($agency->save(), 'agency-cant-save');
        $this->assertTrue($agency->delete(), 'agency-cant-delete');
    }

    public function testBadWeb(): void
    {
        $agency = new AgenciaTransporte();
        $agency->codtrans = 'Test';
        $agency->nombre = 'Test Agency';
        $agency->web = 'javascript:alert(origin)';
        $this->assertFalse($agency->save(), 'agency-can-save-bad-web');

        // javascript con forma de url
        $agency->web = 'javascript://example.com//%0aalert(document.domain);//';
        $this->assertFalse($agency->save(), 'agency-can-save-bad-web-2');

        // javascript con mayúsculas
        $agency->web = 'jAvAsCriPt://sadas.com/%0aalert(11);//';
        $this->assertFalse($agency->save(), 'agency-can-save-bad-web-3');
    }

    public function testGoodWeb(): void
    {
        $agency = new AgenciaTransporte();
        $agency->codtrans = 'Test';
        $agency->nombre = 'Test Agency';
        $agency->web = 'https://www.facturascripts.com';
        $this->assertTrue($agency->save(), 'agency-cant-save-good-web');
        $this->assertTrue($agency->delete(), 'agency-cant-delete');
    }

    public function testLoadFromData(): void
    {
        $agency = new AgenciaTransporte();
        $agency->loadFromData([
            'activo' => true,
            'codtrans' => 'Test',
            'nombre' => 'Test Agency',
            'telefono' => '+34 922 000 000',
            'web' => 'https://www.facturascripts.com'
        ]);

        $this->assertTrue($agency->activo, 'agency-cant-load-activo');
        $this->assertEquals('Test', $agency->codtrans, 'agency-cant-load-codtrans');
        $this->assertEquals('Test Agency', $agency->nombre, 'agency-cant-load-nombre');
        $this->assertEquals('+34 922 000 000', $agency->telefono, 'agency-cant-load-telefono');
        $this->assertEquals('https://www.facturascripts.com', $agency->web, 'agency-cant-load-web');

        // ahora probamos a cambiar datos
        $agency->loadFromData([
            'activo' => false,
            'codtrans' => 'Test2',
            'nombre' => 'Test Agency 2',
            'telefono' => '+34 922 000 001',
            'web' => 'https://www.facturascripts.com/test'
        ]);

        $this->assertFalse($agency->activo, 'agency-cant-load-activo-2');
        $this->assertEquals('Test2', $agency->codtrans, 'agency-cant-load-codtrans-2');
        $this->assertEquals('Test Agency 2', $agency->nombre, 'agency-cant-load-nombre-2');
        $this->assertEquals('+34 922 000 001', $agency->telefono, 'agency-cant-load-telefono-2');
        $this->assertEquals('https://www.facturascripts.com/test', $agency->web, 'agency-cant-load-web-2');
    }

    public function testLoadWhereEq(): void
    {
        // creamos una agencia de transporte para buscarla
        $agency = new AgenciaTransporte();
        $agency->codtrans = 'Test';
        $agency->nombre = 'Test-Agency-W';
        $this->assertTrue($agency->save(), 'agency-cant-save-for-loadwhereeq');

        // ahora la buscamos
        $agency2 = new AgenciaTransporte();
        $this->assertTrue($agency2->loadWhereEq('nombre', 'Test-Agency-W'), 'agency-cant-loadwhereeq');
        $this->assertEquals('Test', $agency2->codtrans, 'agency-loadwhereeq-wrong-codtrans');

        // borramos la agencia de transporte
        $this->assertTrue($agency2->delete(), 'agency-cant-delete-after-loadwhereeq');
    }

    public function testGetOriginal(): void
    {
        // creamos una agencia de transporte
        $agency = new AgenciaTransporte();
        $agency->codtrans = 'Test';
        $agency->nombre = 'Original Name';
        $agency->telefono = '+34 922 000 000';
        $agency->web = 'https://www.facturascripts.com';
        $this->assertTrue($agency->save(), 'agency-cant-save-for-getoriginal');

        // cargamos la agencia de transporte
        $agency2 = new AgenciaTransporte();
        $this->assertTrue($agency2->loadWhereEq('codtrans', 'Test'), 'agency-cant-load-for-getoriginal');

        // verificamos que getOriginal devuelve los valores originales antes de modificar
        $this->assertEquals('Original Name', $agency2->getOriginal('nombre'), 'agency-getoriginal-wrong-nombre');
        $this->assertEquals('+34 922 000 000', $agency2->getOriginal('telefono'), 'agency-getoriginal-wrong-telefono');
        $this->assertEquals('https://www.facturascripts.com', $agency2->getOriginal('web'), 'agency-getoriginal-wrong-web');

        // modificamos los datos
        $agency2->nombre = 'Modified Name';
        $agency2->telefono = '+34 922 111 111';
        $agency2->web = 'https://www.example.com';

        // verificamos que getOriginal sigue devolviendo los valores originales
        $this->assertEquals('Original Name', $agency2->getOriginal('nombre'), 'agency-getoriginal-after-modify-nombre');
        $this->assertEquals('+34 922 000 000', $agency2->getOriginal('telefono'), 'agency-getoriginal-after-modify-telefono');
        $this->assertEquals('https://www.facturascripts.com', $agency2->getOriginal('web'), 'agency-getoriginal-after-modify-web');

        // verificamos que las propiedades sí han cambiado
        $this->assertEquals('Modified Name', $agency2->nombre, 'agency-nombre-not-modified');
        $this->assertEquals('+34 922 111 111', $agency2->telefono, 'agency-telefono-not-modified');
        $this->assertEquals('https://www.example.com', $agency2->web, 'agency-web-not-modified');

        // borramos la agencia de transporte
        $this->assertTrue($agency2->delete(), 'agency-cant-delete-after-getoriginal');
    }

    public function testIsDirty(): void
    {
        // creamos una agencia de transporte
        $agency = new AgenciaTransporte();
        $agency->codtrans = 'Test';
        $agency->nombre = 'Test Agency';
        $agency->telefono = '+34 922 000 000';
        $agency->web = 'https://www.facturascripts.com';
        $this->assertTrue($agency->save(), 'agency-cant-save-for-isdirty');

        // cargamos la agencia de transporte
        $agency2 = new AgenciaTransporte();
        $this->assertTrue($agency2->loadWhereEq('codtrans', 'Test'), 'agency-cant-load-for-isdirty');

        // verificamos que no está modificado después de cargar
        $this->assertFalse($agency2->isDirty(), 'agency-is-dirty-after-load');
        $this->assertFalse($agency2->isDirty('nombre'), 'agency-nombre-is-dirty-after-load');
        $this->assertFalse($agency2->isDirty('telefono'), 'agency-telefono-is-dirty-after-load');
        $this->assertFalse($agency2->isDirty('web'), 'agency-web-is-dirty-after-load');

        // modificamos el nombre
        $agency2->nombre = 'Modified Name';

        // verificamos que está modificado
        $this->assertTrue($agency2->isDirty(), 'agency-is-not-dirty-after-modify');
        $this->assertTrue($agency2->isDirty('nombre'), 'agency-nombre-is-not-dirty-after-modify');
        $this->assertFalse($agency2->isDirty('telefono'), 'agency-telefono-is-dirty-without-modify');
        $this->assertFalse($agency2->isDirty('web'), 'agency-web-is-dirty-without-modify');

        // modificamos el teléfono también
        $agency2->telefono = '+34 922 111 111';

        // verificamos que ambos están modificados
        $this->assertTrue($agency2->isDirty(), 'agency-is-not-dirty-after-modify-2');
        $this->assertTrue($agency2->isDirty('nombre'), 'agency-nombre-is-not-dirty-after-modify-2');
        $this->assertTrue($agency2->isDirty('telefono'), 'agency-telefono-is-not-dirty-after-modify-2');
        $this->assertFalse($agency2->isDirty('web'), 'agency-web-is-dirty-without-modify-2');

        // restauramos el nombre al valor original
        $agency2->nombre = 'Test Agency';

        // verificamos que nombre ya no está modificado, pero teléfono sí
        $this->assertTrue($agency2->isDirty(), 'agency-is-not-dirty-after-restore-nombre');
        $this->assertFalse($agency2->isDirty('nombre'), 'agency-nombre-is-dirty-after-restore');
        $this->assertTrue($agency2->isDirty('telefono'), 'agency-telefono-is-not-dirty-after-restore-nombre');

        // borramos la agencia de transporte
        $this->assertTrue($agency2->delete(), 'agency-cant-delete-after-isdirty');
    }

    public function testGetDirty(): void
    {
        // creamos una agencia de transporte
        $agency = new AgenciaTransporte();
        $agency->codtrans = 'Test';
        $agency->nombre = 'Test Agency';
        $agency->telefono = '+34 922 000 000';
        $agency->web = 'https://www.facturascripts.com';
        $this->assertTrue($agency->save(), 'agency-cant-save-for-getdirty');

        // cargamos la agencia de transporte
        $agency2 = new AgenciaTransporte();
        $this->assertTrue($agency2->loadWhereEq('codtrans', 'Test'), 'agency-cant-load-for-getdirty');

        // verificamos que no hay campos modificados después de cargar
        $dirty = $agency2->getDirty();
        $this->assertIsArray($dirty, 'agency-getdirty-not-array');
        $this->assertEmpty($dirty, 'agency-getdirty-not-empty-after-load');

        // modificamos el nombre
        $agency2->nombre = 'Modified Name';

        // verificamos que getDirty devuelve solo el campo nombre
        $dirty = $agency2->getDirty();
        $this->assertIsArray($dirty, 'agency-getdirty-not-array-after-modify');
        $this->assertCount(1, $dirty, 'agency-getdirty-wrong-count-after-modify-nombre');
        $this->assertArrayHasKey('nombre', $dirty, 'agency-getdirty-missing-nombre');
        $this->assertEquals('Modified Name', $dirty['nombre'], 'agency-getdirty-wrong-value-nombre');

        // modificamos el teléfono y la web
        $agency2->telefono = '+34 922 111 111';
        $agency2->web = 'https://www.example.com';

        // verificamos que getDirty devuelve los tres campos modificados
        $dirty = $agency2->getDirty();
        $this->assertIsArray($dirty, 'agency-getdirty-not-array-after-modify-2');
        $this->assertCount(3, $dirty, 'agency-getdirty-wrong-count-after-modify-3');
        $this->assertArrayHasKey('nombre', $dirty, 'agency-getdirty-missing-nombre-2');
        $this->assertArrayHasKey('telefono', $dirty, 'agency-getdirty-missing-telefono');
        $this->assertArrayHasKey('web', $dirty, 'agency-getdirty-missing-web');
        $this->assertEquals('Modified Name', $dirty['nombre'], 'agency-getdirty-wrong-value-nombre-2');
        $this->assertEquals('+34 922 111 111', $dirty['telefono'], 'agency-getdirty-wrong-value-telefono');
        $this->assertEquals('https://www.example.com', $dirty['web'], 'agency-getdirty-wrong-value-web');

        // restauramos el nombre al valor original
        $agency2->nombre = 'Test Agency';

        // verificamos que getDirty ahora solo devuelve teléfono y web
        $dirty = $agency2->getDirty();
        $this->assertIsArray($dirty, 'agency-getdirty-not-array-after-restore');
        $this->assertCount(2, $dirty, 'agency-getdirty-wrong-count-after-restore');
        $this->assertArrayNotHasKey('nombre', $dirty, 'agency-getdirty-has-nombre-after-restore');
        $this->assertArrayHasKey('telefono', $dirty, 'agency-getdirty-missing-telefono-after-restore');
        $this->assertArrayHasKey('web', $dirty, 'agency-getdirty-missing-web-after-restore');

        // borramos la agencia de transporte
        $this->assertTrue($agency2->delete(), 'agency-cant-delete-after-getdirty');
    }

    public function testDirtyAfterSave(): void
    {
        // creamos una agencia de transporte
        $agency = new AgenciaTransporte();
        $agency->codtrans = 'Test';
        $agency->nombre = 'Test Agency';
        $agency->telefono = '+34 922 000 000';
        $this->assertTrue($agency->save(), 'agency-cant-save-for-dirty-after-save');

        // cargamos la agencia de transporte
        $agency2 = new AgenciaTransporte();
        $this->assertTrue($agency2->loadWhereEq('codtrans', 'Test'), 'agency-cant-load-for-dirty-after-save');

        // modificamos varios campos
        $agency2->nombre = 'Modified Name';
        $agency2->telefono = '+34 922 111 111';
        $agency2->web = 'https://www.example.com';

        // verificamos que está dirty antes de guardar
        $this->assertTrue($agency2->isDirty(), 'agency-is-not-dirty-before-save');
        $this->assertTrue($agency2->isDirty('nombre'), 'agency-nombre-is-not-dirty-before-save');
        $this->assertTrue($agency2->isDirty('telefono'), 'agency-telefono-is-not-dirty-before-save');
        $this->assertTrue($agency2->isDirty('web'), 'agency-web-is-not-dirty-before-save');

        $dirty = $agency2->getDirty();
        $this->assertCount(3, $dirty, 'agency-getdirty-wrong-count-before-save');

        // guardamos los cambios
        $this->assertTrue($agency2->save(), 'agency-cant-save-after-modify');

        // verificamos que ya no está dirty después de guardar
        $this->assertFalse($agency2->isDirty(), 'agency-is-dirty-after-save');
        $this->assertFalse($agency2->isDirty('nombre'), 'agency-nombre-is-dirty-after-save');
        $this->assertFalse($agency2->isDirty('telefono'), 'agency-telefono-is-dirty-after-save');
        $this->assertFalse($agency2->isDirty('web'), 'agency-web-is-dirty-after-save');

        $dirty = $agency2->getDirty();
        $this->assertEmpty($dirty, 'agency-getdirty-not-empty-after-save');

        // verificamos que getOriginal devuelve los nuevos valores guardados
        $this->assertEquals('Modified Name', $agency2->getOriginal('nombre'), 'agency-getoriginal-wrong-after-save');
        $this->assertEquals('+34 922 111 111', $agency2->getOriginal('telefono'), 'agency-getoriginal-telefono-wrong-after-save');
        $this->assertEquals('https://www.example.com', $agency2->getOriginal('web'), 'agency-getoriginal-web-wrong-after-save');

        // borramos la agencia de transporte
        $this->assertTrue($agency2->delete(), 'agency-cant-delete-after-dirty-after-save');
    }

    public function testChangeId(): void
    {
        // creamos una agencia de transporte con un codtrans específico
        $agency = new AgenciaTransporte();
        $agency->codtrans = 'OLD';
        $agency->nombre = 'Test Agency for ChangeId';
        $agency->telefono = '+34 922 000 000';
        $this->assertTrue($agency->save(), 'agency-cant-save-for-changeid');

        // verificamos que existe con el codtrans original
        $this->assertEquals('OLD', $agency->codtrans, 'agency-wrong-codtrans-before-change');
        $this->assertEquals('OLD', $agency->id(), 'agency-wrong-id-before-change');
        $this->assertTrue($agency->exists(), 'agency-not-exists-before-change');

        // cambiamos el codtrans a un nuevo valor
        $this->assertTrue($agency->changeId('NEW'), 'agency-cant-changeid');

        // verificamos que el codtrans ha cambiado en el objeto
        $this->assertEquals('NEW', $agency->codtrans, 'agency-codtrans-not-changed');
        $this->assertEquals('NEW', $agency->id(), 'agency-id-not-changed');

        // verificamos que el antiguo codtrans ya no existe en la base de datos
        $oldAgency = new AgenciaTransporte();
        $this->assertFalse($oldAgency->loadWhereEq('codtrans', 'OLD'), 'agency-old-codtrans-still-exists');

        // verificamos que el nuevo codtrans existe en la base de datos
        $newAgency = new AgenciaTransporte();
        $this->assertTrue($newAgency->loadWhereEq('codtrans', 'NEW'), 'agency-new-codtrans-not-exists');
        $this->assertEquals('Test Agency for ChangeId', $newAgency->nombre, 'agency-nombre-wrong-after-changeid');
        $this->assertEquals('+34 922 000 000', $newAgency->telefono, 'agency-telefono-wrong-after-changeid');

        // verificamos que getOriginal devuelve el nuevo valor después del cambio
        $this->assertEquals('NEW', $agency->getOriginal('codtrans'), 'agency-getoriginal-codtrans-wrong-after-changeid');

        // borramos la agencia de transporte
        $this->assertTrue($newAgency->delete(), 'agency-cant-delete-after-changeid');
    }

    public function testChangeIdWithEmptyValue(): void
    {
        // creamos una agencia de transporte
        $agency = new AgenciaTransporte();
        $agency->codtrans = 'TEST1';
        $agency->nombre = 'Test Agency';
        $this->assertTrue($agency->save(), 'agency-cant-save-for-changeid-empty');

        // intentamos cambiar a un valor vacío (debe fallar)
        $this->assertFalse($agency->changeId(''), 'agency-changeid-empty-should-fail');
        $this->assertFalse($agency->changeId(null), 'agency-changeid-null-should-fail');

        // verificamos que el codtrans no ha cambiado
        $this->assertEquals('TEST1', $agency->codtrans, 'agency-codtrans-changed-with-empty');

        // borramos la agencia de transporte
        $this->assertTrue($agency->delete(), 'agency-cant-delete-after-changeid-empty');
    }

    public function testChangeIdWithSameValue(): void
    {
        // creamos una agencia de transporte
        $agency = new AgenciaTransporte();
        $agency->codtrans = 'TEST2';
        $agency->nombre = 'Test Agency';
        $this->assertTrue($agency->save(), 'agency-cant-save-for-changeid-same');

        // intentamos cambiar al mismo valor (debe fallar)
        $this->assertFalse($agency->changeId('TEST2'), 'agency-changeid-same-should-fail');

        // verificamos que sigue existiendo con el mismo codtrans
        $this->assertEquals('TEST2', $agency->codtrans, 'agency-codtrans-changed-incorrectly');
        $this->assertTrue($agency->exists(), 'agency-not-exists-after-changeid-same');

        // borramos la agencia de transporte
        $this->assertTrue($agency->delete(), 'agency-cant-delete-after-changeid-same');
    }

    public function testChangeIdKeepsDirtyFields(): void
    {
        // creamos una agencia de transporte
        $agency = new AgenciaTransporte();
        $agency->codtrans = 'OLDCODE';
        $agency->nombre = 'Original Name';
        $agency->telefono = '+34 922 000 000';
        $agency->web = 'https://www.original.com';
        $this->assertTrue($agency->save(), 'agency-cant-save-for-changeid-dirty');

        // cargamos la agencia de transporte
        $agency2 = new AgenciaTransporte();
        $this->assertTrue($agency2->loadWhereEq('codtrans', 'OLDCODE'), 'agency-cant-load-for-changeid-dirty');

        // verificamos que no está dirty después de cargar
        $this->assertFalse($agency2->isDirty(), 'agency-is-dirty-after-load');

        // modificamos varios campos
        $agency2->nombre = 'Modified Name';
        $agency2->telefono = '+34 922 111 111';
        $agency2->web = 'https://www.modified.com';

        // verificamos que está dirty antes de cambiar el id
        $this->assertTrue($agency2->isDirty(), 'agency-is-not-dirty-before-changeid');
        $this->assertTrue($agency2->isDirty('nombre'), 'agency-nombre-is-not-dirty-before-changeid');
        $this->assertTrue($agency2->isDirty('telefono'), 'agency-telefono-is-not-dirty-before-changeid');
        $this->assertTrue($agency2->isDirty('web'), 'agency-web-is-not-dirty-before-changeid');
        $this->assertFalse($agency2->isDirty('codtrans'), 'agency-codtrans-is-dirty-before-changeid');

        $dirtyBefore = $agency2->getDirty();
        $this->assertCount(3, $dirtyBefore, 'agency-getdirty-wrong-count-before-changeid');

        // cambiamos el id
        $this->assertTrue($agency2->changeId('NEWCODE'), 'agency-cant-changeid-with-dirty');

        // verificamos que el id ha cambiado
        $this->assertEquals('NEWCODE', $agency2->codtrans, 'agency-codtrans-not-changed-after-changeid');
        $this->assertEquals('NEWCODE', $agency2->id(), 'agency-id-not-changed-after-changeid');

        // IMPORTANTE: verificamos que los otros campos modificados siguen siendo dirty
        $this->assertTrue($agency2->isDirty(), 'agency-is-not-dirty-after-changeid');
        $this->assertTrue($agency2->isDirty('nombre'), 'agency-nombre-is-not-dirty-after-changeid');
        $this->assertTrue($agency2->isDirty('telefono'), 'agency-telefono-is-not-dirty-after-changeid');
        $this->assertTrue($agency2->isDirty('web'), 'agency-web-is-not-dirty-after-changeid');
        $this->assertFalse($agency2->isDirty('codtrans'), 'agency-codtrans-is-dirty-after-changeid');

        $dirtyAfter = $agency2->getDirty();
        $this->assertCount(3, $dirtyAfter, 'agency-getdirty-wrong-count-after-changeid');
        $this->assertArrayHasKey('nombre', $dirtyAfter, 'agency-getdirty-missing-nombre-after-changeid');
        $this->assertArrayHasKey('telefono', $dirtyAfter, 'agency-getdirty-missing-telefono-after-changeid');
        $this->assertArrayHasKey('web', $dirtyAfter, 'agency-getdirty-missing-web-after-changeid');
        $this->assertArrayNotHasKey('codtrans', $dirtyAfter, 'agency-getdirty-has-codtrans-after-changeid');

        // verificamos que en la base de datos el registro tiene los valores originales (no los modificados)
        $agency3 = new AgenciaTransporte();
        $this->assertTrue($agency3->loadWhereEq('codtrans', 'NEWCODE'), 'agency-cant-load-newcode-for-verification');
        $this->assertEquals('Original Name', $agency3->nombre, 'agency-nombre-saved-incorrectly');
        $this->assertEquals('+34 922 000 000', $agency3->telefono, 'agency-telefono-saved-incorrectly');
        $this->assertEquals('https://www.original.com', $agency3->web, 'agency-web-saved-incorrectly');

        // ahora guardamos los cambios pendientes
        $this->assertTrue($agency2->save(), 'agency-cant-save-after-changeid');

        // verificamos que ya no está dirty después de guardar
        $this->assertFalse($agency2->isDirty(), 'agency-is-dirty-after-save-changeid');

        // verificamos que en la base de datos ahora sí tiene los valores modificados
        $agency4 = new AgenciaTransporte();
        $this->assertTrue($agency4->loadWhereEq('codtrans', 'NEWCODE'), 'agency-cant-load-newcode-after-save');
        $this->assertEquals('Modified Name', $agency4->nombre, 'agency-nombre-not-saved-after-changeid');
        $this->assertEquals('+34 922 111 111', $agency4->telefono, 'agency-telefono-not-saved-after-changeid');
        $this->assertEquals('https://www.modified.com', $agency4->web, 'agency-web-not-saved-after-changeid');

        // borramos la agencia de transporte
        $this->assertTrue($agency4->delete(), 'agency-cant-delete-after-changeid-dirty');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
