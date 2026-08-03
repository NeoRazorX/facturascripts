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

namespace FacturaScripts\Test\Core\Lib\ExtendedController;

use FacturaScripts\Core\Lib\ExtendedController\OwnerDataTrait;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Clase anfitriona para probar OwnerDataTrait de forma aislada: expone
 * checkOwnerData y las propiedades $user / $permissions que el trait usa.
 */
class OwnerDataTraitHost
{
    use OwnerDataTrait;

    /** @var stdClass */
    public $permissions;

    /** @var stdClass */
    public $user;

    public function isOwner($model): bool
    {
        return $this->checkOwnerData($model);
    }
}

/**
 * Modelo falso con las columnas, id y campos de propiedad controlables.
 */
class OwnerDataFakeModel
{
    public $codagente;

    public $nick;

    /** @var array */
    private $columns;

    /** @var mixed */
    private $idValue;

    public function __construct($idValue, array $columns, $codagente = null, $nick = null)
    {
        $this->idValue = $idValue;
        $this->columns = $columns;
        $this->codagente = $codagente;
        $this->nick = $nick;
    }

    public function hasColumn(string $name): bool
    {
        return in_array($name, $this->columns, true);
    }

    public function id()
    {
        return $this->idValue;
    }
}

final class OwnerDataTraitTest extends TestCase
{
    private function host(bool $onlyOwnerData, ?string $codagente, string $nick): OwnerDataTraitHost
    {
        $host = new OwnerDataTraitHost();

        $host->permissions = new stdClass();
        $host->permissions->onlyOwnerData = $onlyOwnerData;

        $host->user = new stdClass();
        $host->user->codagente = $codagente;
        $host->user->nick = $nick;

        return $host;
    }

    public function testSinRestriccionSiemprePermite(): void
    {
        $host = $this->host(false, '1', 'pepe');
        // documento de otro agente y otro nick
        $model = new OwnerDataFakeModel(10, ['codagente', 'nick'], '2', 'ana');

        $this->assertTrue($host->isOwner($model));
    }

    public function testRegistroNuevoPermite(): void
    {
        $host = $this->host(true, '1', 'pepe');
        // id vacío => registro nuevo, aún no tiene propietario
        $model = new OwnerDataFakeModel(0, ['codagente', 'nick'], '2', 'ana');

        $this->assertTrue($host->isOwner($model));
    }

    public function testModeloSinColumnasDePropiedadPermite(): void
    {
        $host = $this->host(true, '1', 'pepe');
        $model = new OwnerDataFakeModel(10, ['otracolumna']);

        $this->assertTrue($host->isOwner($model));
    }

    public function testCoincideAgentePermite(): void
    {
        $host = $this->host(true, '5', 'pepe');
        $model = new OwnerDataFakeModel(10, ['codagente', 'nick'], '5', 'ana');

        $this->assertTrue($host->isOwner($model));
    }

    public function testCoincideNickAunQueNoElAgentePermite(): void
    {
        $host = $this->host(true, '5', 'pepe');
        // el agente no coincide, pero el nick sí (criterio por unión)
        $model = new OwnerDataFakeModel(10, ['codagente', 'nick'], '9', 'pepe');

        $this->assertTrue($host->isOwner($model));
    }

    public function testNiAgenteNiNickCoincidenDeniega(): void
    {
        $host = $this->host(true, '5', 'pepe');
        $model = new OwnerDataFakeModel(10, ['codagente', 'nick'], '9', 'ana');

        $this->assertFalse($host->isOwner($model));
    }

    public function testSoloAgenteSinCoincidenciaDeniega(): void
    {
        $host = $this->host(true, '5', 'pepe');
        $model = new OwnerDataFakeModel(10, ['codagente'], '9');

        $this->assertFalse($host->isOwner($model));
    }

    public function testSoloAgenteConCoincidenciaPermite(): void
    {
        $host = $this->host(true, '5', 'pepe');
        $model = new OwnerDataFakeModel(10, ['codagente'], '5');

        $this->assertTrue($host->isOwner($model));
    }

    public function testUsuarioSinAgenteEnModeloSoloAgenteDeniega(): void
    {
        // el usuario no tiene codagente, el modelo solo tiene esa columna de
        // propiedad: no puede cumplir ningún criterio => no posee nada
        $host = $this->host(true, null, 'pepe');
        $model = new OwnerDataFakeModel(10, ['codagente'], '5');

        $this->assertFalse($host->isOwner($model));
    }

    public function testSoloNickConCoincidenciaPermite(): void
    {
        $host = $this->host(true, '5', 'pepe');
        $model = new OwnerDataFakeModel(10, ['nick'], null, 'pepe');

        $this->assertTrue($host->isOwner($model));
    }

    public function testSoloNickSinCoincidenciaDeniega(): void
    {
        $host = $this->host(true, '5', 'pepe');
        $model = new OwnerDataFakeModel(10, ['nick'], null, 'ana');

        $this->assertFalse($host->isOwner($model));
    }
}
