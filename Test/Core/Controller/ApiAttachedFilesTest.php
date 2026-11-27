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
 * La API de archivos adjuntos no debe registrar rutas elegidas por el cliente
 * ni sobrescribir los archivos que ya existen en MyFiles.
 */
final class ApiAttachedFilesTest extends TestCase
{
    use LogErrorsTrait;

    /** @var string[] */
    private $created = [];

    /** @var int[] */
    private $ids = [];

    public function testPostWithCustomIdDoesNotRegisterServerFiles(): void
    {
        // archivo del servidor, fuera de MyFiles
        $outsideName = 'api-attached-outside-' . uniqid() . '.txt';
        $outsidePath = FS_FOLDER . '/' . $outsideName;
        $this->assertNotFalse(file_put_contents($outsidePath, 'secret'));
        $this->created[] = $outsidePath;

        $id = 900000 + mt_rand(1, 99999);
        $result = $this->callApi(['idfile' => $id, 'path' => $outsideName, 'filename' => 'invoice.pdf']);
        $this->assertNotEquals(Response::HTTP_OK, $result['code'], json_encode($result['body']));

        $file = new AttachedFile();
        $this->assertFalse($file->load($id), 'server-file-registered-as-attachment');
        $this->assertFileExists($outsidePath);
    }

    public function testPostDoesNotTakeFilesAlreadyInMyFiles(): void
    {
        // archivo que ya existe en MyFiles, como routes.json o plugins.json
        $name = 'api-attached-existing-' . uniqid() . '.json';
        $existing = FS_FOLDER . '/MyFiles/' . $name;
        $this->assertNotFalse(file_put_contents($existing, 'original'));
        $this->created[] = $existing;

        // el path recibido se ignora: el archivo no se mueve ni se registra
        foreach ([[], ['idfile' => 900000 + mt_rand(1, 99999)]] as $extra) {
            $result = $this->callApi($extra + ['path' => $name, 'filename' => 'x.json']);
            $this->assertNotEquals(Response::HTTP_OK, $result['code'], json_encode($result['body']));
            $this->assertFileExists($existing);
        }
    }

    public function testPutDoesNotChangePath(): void
    {
        $name = 'api-attached-' . uniqid() . '.txt';
        $result = $this->callApi([], $this->fileField($name, $this->tempFile('hola')));
        $this->assertEquals(Response::HTTP_OK, $result['code'], json_encode($result['body']));
        $id = $result['body']['data']['idfile'];
        $path = $result['body']['data']['path'];

        $existing = FS_FOLDER . '/MyFiles/api-attached-existing-' . uniqid() . '.json';
        $this->assertNotFalse(file_put_contents($existing, 'original'));
        $this->created[] = $existing;

        $result = $this->callApi(['idfile' => $id, 'path' => basename($existing)], [], 'PUT');
        $this->assertEquals(Response::HTTP_OK, $result['code'], json_encode($result['body']));
        $this->assertSame($path, $result['body']['data']['path']);
        $this->assertFileExists($existing);
        $this->assertFileExists(FS_FOLDER . '/' . $path);
        $this->deleteAttachedFile($result);
    }

    public function testUploadDoesNotOverwriteFilesInMyFiles(): void
    {
        // archivo que ya existe en MyFiles, como routes.json o plugins.json
        $name = 'api-attached-existing-' . uniqid() . '.json';
        $existing = FS_FOLDER . '/MyFiles/' . $name;
        $this->assertNotFalse(file_put_contents($existing, 'original'));
        $this->created[] = $existing;

        // con idfile elegido por el cliente
        $id = 900000 + mt_rand(1, 99999);
        $result = $this->callApi(['idfile' => $id], $this->fileField($name, $this->tempFile('{"evil":1}')));
        $this->assertSame('original', file_get_contents($existing), json_encode($result['body']));
        $this->deleteAttachedFile($result);

        // sin idfile
        $result = $this->callApi([], $this->fileField($name, $this->tempFile('{"evil":2}')));
        $this->assertEquals(Response::HTTP_OK, $result['code'], json_encode($result['body']));
        $this->assertFileExists($existing);
        $this->assertSame('original', file_get_contents($existing));
        $this->deleteAttachedFile($result);
    }

    protected function tearDown(): void
    {
        foreach ($this->created as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }

        // eliminamos cualquier registro de prueba que haya quedado
        foreach ($this->ids as $id) {
            $file = new AttachedFile();
            if ($file->load($id)) {
                $file->delete();
            }
        }

        $_FILES = [];
        $_POST = [];
        $this->logErrors();
    }

    private function callApi(array $post, array $files = [], string $method = 'POST'): array
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        unset($_SERVER['CONTENT_TYPE']);
        $_POST = $post;
        $_GET = [];
        $_FILES = $files;

        // ejecutamos el recurso desde dentro del controlador, sin enviar la respuesta
        $controller = new ApiAttachedFiles('ApiAttachedFiles', '/api/3/attachedfiles');
        $exec = function (): array {
            // los archivos no llegan por HTTP: los marcamos como subidas de prueba
            foreach ($this->request->files->all() as $file) {
                $file->test = true;
            }

            $this->response->disableSend(true);
            $this->runResource();
            $decoded = json_decode($this->response->getContent(), true);

            return [
                'code' => $this->response->getHttpCode(),
                'body' => is_array($decoded) ? $decoded : [],
            ];
        };
        $result = Closure::bind($exec, $controller, ApiAttachedFiles::class)();
        foreach ([$post['idfile'] ?? null, $result['body']['data']['idfile'] ?? null] as $id) {
            if (!empty($id)) {
                $this->ids[] = (int)$id;
            }
        }

        // limpiamos los globales
        $_FILES = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';

        return $result;
    }

    private function deleteAttachedFile(array $result): void
    {
        $file = new AttachedFile();
        if ($file->load($result['body']['data']['idfile'] ?? 0)) {
            $this->assertTrue($file->delete());
        }
    }

    private function fileField(string $name, string $tmpName): array
    {
        return ['file' => [
            'name' => $name,
            'type' => 'application/json',
            'tmp_name' => $tmpName,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($tmpName),
        ]];
    }

    private function tempFile(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'aaf');
        file_put_contents($path, $content);
        $this->created[] = $path;
        return $path;
    }
}
