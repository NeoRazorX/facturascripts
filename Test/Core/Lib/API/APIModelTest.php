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

namespace FacturaScripts\Test\Core\Lib\API;

use FacturaScripts\Core\Lib\API\APIModel;
use FacturaScripts\Core\Model\ApiKey;
use FacturaScripts\Core\Model\Divisa;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class APIModelTest extends TestCase
{
    use LogErrorsTrait;

    public function testUserHidesSensitiveFieldsOnGet(): void
    {
        $user = $this->createUser();

        $body = $this->callApi('User', 'GET', [$user->nick]);

        $this->assertArrayHasKey('nick', $body);
        $this->assertEquals($user->nick, $body['nick']);
        $this->assertArrayNotHasKey('password', $body);
        $this->assertArrayNotHasKey('logkey', $body);
        $this->assertArrayNotHasKey('two_factor_secret_key', $body);

        $this->assertTrue($user->delete());
    }

    public function testUserHidesSensitiveFieldsOnList(): void
    {
        $user = $this->createUser();

        $body = $this->callApi('User', 'GET', []);

        $this->assertNotEmpty($body);
        foreach ($body as $row) {
            $this->assertArrayNotHasKey('password', $row);
            $this->assertArrayNotHasKey('logkey', $row);
            $this->assertArrayNotHasKey('two_factor_secret_key', $row);
        }

        $this->assertTrue($user->delete());
    }

    public function testUserHidesSensitiveFieldsOnSchema(): void
    {
        $body = $this->callApi('User', 'GET', ['schema']);

        $this->assertNotEmpty($body);
        $this->assertArrayHasKey('nick', $body);
        $this->assertArrayNotHasKey('password', $body);
        $this->assertArrayNotHasKey('logkey', $body);
        $this->assertArrayNotHasKey('two_factor_secret_key', $body);
    }

    public function testApiKeyHidesApikeyField(): void
    {
        $key = new ApiKey();
        $key->description = 'test-apimodel';
        $this->assertTrue($key->save());

        $body = $this->callApi('ApiKey', 'GET', [(string)$key->id]);
        $this->assertArrayHasKey('description', $body);
        $this->assertArrayNotHasKey('apikey', $body);

        $listBody = $this->callApi('ApiKey', 'GET', []);
        foreach ($listBody as $row) {
            $this->assertArrayNotHasKey('apikey', $row);
        }

        $schemaBody = $this->callApi('ApiKey', 'GET', ['schema']);
        $this->assertArrayNotHasKey('apikey', $schemaBody);
        $this->assertArrayHasKey('description', $schemaBody);

        $this->assertTrue($key->delete());
    }

    public function testModelWithoutHiddenFieldsExposesAll(): void
    {
        // Divisa no oculta nada: comprobamos que su schema sigue devolviendo todos los campos.
        $body = $this->callApi('Divisa', 'GET', ['schema']);
        $this->assertNotEmpty($body);
        $this->assertArrayHasKey('coddivisa', $body);
    }

    public function testGetApiFieldsToHideDefaults(): void
    {
        $this->assertSame([], (new Divisa())->getApiFieldsToHide());
        $this->assertSame(['password', 'logkey', 'two_factor_secret_key'], (new User())->getApiFieldsToHide());
        $this->assertSame(['apikey'], (new ApiKey())->getApiFieldsToHide());
    }

    private function callApi(string $resource, string $method, array $params): array
    {
        $_SERVER['REQUEST_METHOD'] = $method;

        $request = new Request();
        $response = new Response();
        $response->disableSend(true);

        $api = new APIModel($response, $request, $params);
        $api->processResource($resource);

        $decoded = json_decode($response->getContent(), true);
        return is_array($decoded) ? $decoded : [];
    }

    private function createUser(): User
    {
        $user = new User();
        $user->nick = 'apimodel_' . Tools::randomString(6);
        $user->email = $user->nick . '@test.local';
        $user->setPassword('TestPassword123!');
        $this->assertTrue($user->save());

        return $user;
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
