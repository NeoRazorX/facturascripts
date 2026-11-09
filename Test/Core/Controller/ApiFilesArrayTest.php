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

use Closure;
use FacturaScripts\Core\Controller\ApiAttachedFiles;
use FacturaScripts\Core\Response;
use FacturaScripts\Dinamic\Model\AttachedFile;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

/**
 * La API de archivos adjuntos recibe un campo con varios archivos (files[]) como array
 * y debe usar el primero válido en lugar de provocar un error fatal.
 */
final class ApiFilesArrayTest extends TestCase
{
    use LogErrorsTrait;

    /** @var string[] */
    private $created = [];

    public function testAttachedFilesUsesFirstFileOfArray(): void
    {
        $first = 'api-files-array-' . uniqid() . '.txt';
        $second = 'api-files-array-' . uniqid() . '.txt';
        $tmpFirst = $this->tempFile('primero');
        $tmpSecond = $this->tempFile('segundo');
        $this->created[] = FS_FOLDER . '/MyFiles/' . $second;

        $result = $this->callApi(ApiAttachedFiles::class, 'attachedfiles', $this->filesArray(
            [$first, $second],
            [$tmpFirst, $tmpSecond]
        ));
        $this->assertEquals(Response::HTTP_OK, $result['code'], json_encode($result['body']));
        $this->assertEquals($first, $result['body']['data']['filename'] ?? null);

        // el segundo archivo no se mueve a MyFiles
        $this->assertFileExists($tmpSecond);
        $this->assertFileDoesNotExist(FS_FOLDER . '/MyFiles/' . $second);

        // eliminamos el adjunto creado
        $file = new AttachedFile();
        $this->assertTrue($file->load($result['body']['data']['idfile'] ?? 0));
        $this->assertTrue($file->delete());
    }

    protected function tearDown(): void
    {
        foreach ($this->created as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }

        $_FILES = [];
        $this->logErrors();
    }

    private function callApi(string $className, string $resource, array $files): array
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        unset($_SERVER['CONTENT_TYPE']);
        $_POST = [];
        $_GET = [];
        $_FILES = $files;

        // ejecutamos el recurso desde dentro del controlador, sin enviar la respuesta
        $parts = explode('\\', $className);
        $controller = new $className(end($parts), '/api/3/' . $resource);
        $exec = function (): array {
            // los archivos no llegan por HTTP: los marcamos como subidas de prueba
            foreach ($this->request->files->all() as $item) {
                foreach (is_array($item) ? $item : [$item] as $file) {
                    $file->test = true;
                }
            }

            $this->response->disableSend(true);
            $this->runResource();
            $decoded = json_decode($this->response->getContent(), true);

            return [
                'code' => $this->response->getHttpCode(),
                'body' => is_array($decoded) ? $decoded : [],
            ];
        };
        $result = Closure::bind($exec, $controller, $className)();

        // limpiamos los globales
        $_FILES = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';

        return $result;
    }

    private function filesArray(array $names, array $tmpNames): array
    {
        return ['files' => [
            'name' => $names,
            'type' => array_fill(0, count($names), 'application/octet-stream'),
            'tmp_name' => $tmpNames,
            'error' => array_fill(0, count($names), UPLOAD_ERR_OK),
            'size' => array_map('filesize', $tmpNames),
        ]];
    }

    private function tempFile(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'aft');
        file_put_contents($path, $content);
        $this->created[] = $path;
        return $path;
    }
}
