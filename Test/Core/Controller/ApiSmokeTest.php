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

namespace FacturaScripts\Test\Core\Controller;

use FacturaScripts\Core\Cache;
use FacturaScripts\Core\Http;
use FacturaScripts\Core\Model\ApiKey;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Template\ApiController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class ApiSmokeTest extends TestCase
{
    use LogErrorsTrait;

    public function testApiRootAndAuthenticationRespondThroughHttp(): void
    {
        $previousApiEnabled = Tools::settings('default', 'enable_api', false);
        $apiKey = new ApiKey();
        $pipes = [];
        $process = null;

        try {
            Tools::settingsSet('default', 'enable_api', true);
            Tools::settingsSave();
            Cache::deleteMulti(ApiController::IP_LIST);

            $apiKey->description = 'api-smoke-test';
            $apiKey->fullaccess = true;
            $this->assertTrue($apiKey->save(), 'can-not-create-api-key');

            $port = $this->getFreePort();
            $process = $this->startServer($port, $pipes);
            $url = 'http://127.0.0.1:' . $port . '/api/3';

            $status = 0;
            $error = '';
            $request = null;
            for ($attempt = 0; $attempt < 30; $attempt++) {
                $request = Http::get($url)
                    ->setToken($apiKey->apikey)
                    ->setTimeout(2)
                    ->setCurlOption(CURLOPT_CONNECTTIMEOUT_MS, 200);
                $status = $request->status();
                $error = $request->errorMessage();
                if ($status !== 0) {
                    break;
                }

                usleep(100000);
            }

            $serverOutput = $this->getServerOutput($pipes);
            $responseBody = $status === 0 ? '' : $request->body();
            $diagnostic = trim($error . PHP_EOL . $responseBody . PHP_EOL . $serverOutput);
            $this->assertSame(Response::HTTP_OK, $status, $diagnostic);
            $this->assertStringStartsWith('application/json', $request->header('Content-Type'));

            $data = json_decode($responseBody, true);
            $this->assertIsArray($data, 'api-response-is-not-json');
            $this->assertArrayHasKey('resources', $data);
            $this->assertContains('divisas', $data['resources']);

            $this->assertUnauthorized($url, 'invalid-' . $apiKey->apikey);
            $this->assertUnauthorized($url, null);
        } finally {
            $this->stopServer($process, $pipes);

            if ($apiKey->exists()) {
                $apiKey->delete();
            }

            Tools::settingsSet('default', 'enable_api', $previousApiEnabled);
            Tools::settingsSave();
            Cache::deleteMulti(ApiController::IP_LIST);
        }
    }

    private function assertUnauthorized(string $url, ?string $token): void
    {
        $request = Http::get($url)->setTimeout(2);
        if ($token !== null) {
            $request->setToken($token);
        }

        $status = $request->status();
        $body = $status === 0 ? '' : $request->body();
        $diagnostic = trim($request->errorMessage() . PHP_EOL . $body);
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $status, $diagnostic);
        $this->assertStringStartsWith('application/json', $request->header('Content-Type'));

        $data = json_decode($body, true);
        $this->assertIsArray($data, 'api-error-response-is-not-json');
        $this->assertSame('error', $data['status'] ?? null);
        $this->assertNotEmpty($data['message'] ?? '');
    }

    private function getFreePort(): int
    {
        $errorCode = 0;
        $errorMessage = '';
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);
        $this->assertIsResource($socket, $errorMessage);

        $address = stream_socket_get_name($socket, false);
        fclose($socket);

        return (int)substr($address, strrpos($address, ':') + 1);
    }

    private function getServerOutput(array $pipes): string
    {
        $output = '';
        foreach ([1, 2] as $index) {
            if (isset($pipes[$index]) && is_resource($pipes[$index])) {
                $output .= stream_get_contents($pipes[$index]);
            }
        }

        return $output;
    }

    private function startServer(int $port, array &$pipes)
    {
        $command = [
            PHP_BINARY,
            '-S',
            '127.0.0.1:' . $port,
            '-t',
            FS_FOLDER,
            FS_FOLDER . '/index.php'
        ];
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];

        $process = proc_open($command, $descriptors, $pipes, FS_FOLDER);
        $this->assertIsResource($process, 'can-not-start-api-server');

        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        return $process;
    }

    private function stopServer($process, array $pipes): void
    {
        if (is_resource($process)) {
            $status = proc_get_status($process);
            if ($status['running']) {
                proc_terminate($process);
            }
        }

        foreach ($pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }

        if (is_resource($process)) {
            proc_close($process);
        }
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
