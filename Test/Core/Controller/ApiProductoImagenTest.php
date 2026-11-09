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
use FacturaScripts\Core\Controller\ApiProductoImagen;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\AttachedFile;
use FacturaScripts\Dinamic\Model\AttachedFileRelation;
use FacturaScripts\Dinamic\Model\Producto;
use FacturaScripts\Dinamic\Model\ProductoImagen;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

/**
 * POST /api/3/productoimagenes: al enviar una imagen se crea el archivo adjunto
 * y queda vinculado al producto.
 */
final class ApiProductoImagenTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

    /** @var string[] */
    private $created = [];

    /** @var Producto */
    private $product;

    protected function setUp(): void
    {
        if (false === function_exists('imagepng')) {
            $this->markTestSkipped('gd-not-available');
        }

        $this->product = $this->getRandomProduct();
        $this->assertTrue($this->product->save(), 'can-not-save-product');
    }

    public function testExistingFileCanBeLinked(): void
    {
        // flujo en dos pasos: el archivo ya está subido y se envía su idfile
        $name = 'api-producto-imagen-' . uniqid() . '.png';
        copy($this->tempImage(), FS_FOLDER . '/MyFiles/' . $name);
        $file = new AttachedFile();
        $file->path = $name;
        $this->assertTrue($file->save(), 'can-not-save-file');

        $result = $this->callPost(['idproducto' => $this->product->idproducto, 'idfile' => $file->idfile], []);
        $this->assertEquals(Response::HTTP_OK, $result['code'], json_encode($result['body']));
        $this->assertEquals($file->idfile, $result['body']['data']['idfile'] ?? null);

        $this->assertTrue($file->delete());
    }

    public function testGetReturnsImageWithUrls(): void
    {
        $image = 'api-producto-imagen-' . uniqid() . '.png';
        $result = $this->callPost(
            ['idproducto' => $this->product->idproducto],
            $this->filesArray([$image], [$this->tempImage()])
        );
        $this->assertEquals(Response::HTTP_OK, $result['code'], json_encode($result['body']));
        $id = $result['body']['data']['id'] ?? 0;

        // antes cualquier petición respondía 405 method-not-allowed
        $result = $this->callApi('GET', '/api/3/productoimagenes/' . $id, [], []);
        $this->assertEquals(Response::HTTP_OK, $result['code'], json_encode($result['body']));
        $this->assertEquals($id, $result['body']['id'] ?? null);
        $this->assertNotEmpty($result['body']['download'] ?? null);
        $this->assertNotEmpty($result['body']['download-permanent'] ?? null);

        $file = new AttachedFile();
        $this->assertTrue($file->load($result['body']['idfile'] ?? 0));
        $this->assertTrue($file->delete());
    }

    public function testImageIsUploadedAndLinkedToProduct(): void
    {
        $text = 'api-producto-imagen-' . uniqid() . '.txt';
        $image = 'api-producto-imagen-' . uniqid() . '.png';
        $tmpText = $this->tempFile('no es una imagen');
        $this->created[] = FS_FOLDER . '/MyFiles/' . $text;

        // files[] con un archivo que no es imagen y una imagen: se usa la imagen
        $result = $this->callPost(
            ['idproducto' => $this->product->idproducto],
            $this->filesArray([$text, $image], [$tmpText, $this->tempImage()])
        );
        $this->assertEquals(Response::HTTP_OK, $result['code'], json_encode($result['body']));

        // se ha creado el archivo adjunto con la imagen
        $file = new AttachedFile();
        $this->assertTrue($file->load($result['body']['data']['idfile'] ?? 0), 'file-not-created');
        $this->assertEquals($image, $file->filename);
        $this->assertFileExists($file->getFullPath());

        // la imagen pertenece al producto
        $productImage = new ProductoImagen();
        $this->assertTrue($productImage->load($result['body']['data']['id'] ?? 0), 'image-not-created');
        $this->assertEquals($this->product->idproducto, $productImage->idproducto);
        $this->assertEquals($file->idfile, $productImage->idfile);

        // y aparece entre los adjuntos del producto
        $this->assertEquals(1, AttachedFileRelation::count([
            Where::eq('idfile', $file->idfile),
            Where::eq('model', 'Producto'),
            Where::eq('modelid', $this->product->idproducto),
        ]));

        // el archivo que no es imagen no se mueve a MyFiles
        $this->assertFileExists($tmpText);
        $this->assertFileDoesNotExist(FS_FOLDER . '/MyFiles/' . $text);

        $this->assertTrue($file->delete());
    }

    public function testNoValidImageIsRejected(): void
    {
        $text = 'api-producto-imagen-' . uniqid() . '.txt';
        $this->created[] = FS_FOLDER . '/MyFiles/' . $text;
        $filesBefore = AttachedFile::count();

        $result = $this->callPost(
            ['idproducto' => $this->product->idproducto],
            $this->filesArray([$text], [$this->tempFile('no es una imagen')])
        );
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $result['code'], json_encode($result['body']));
        $this->assertEquals($filesBefore, AttachedFile::count());
        $this->assertFileDoesNotExist(FS_FOLDER . '/MyFiles/' . $text);
    }

    public function testUploadIsRemovedWhenImageCanNotBeSaved(): void
    {
        $image = 'api-producto-imagen-' . uniqid() . '.png';
        $this->created[] = FS_FOLDER . '/MyFiles/' . $image;
        $filesBefore = AttachedFile::count();

        // sin producto la imagen no se puede guardar
        $result = $this->callPost(['orden' => 1], $this->filesArray([$image], [$this->tempImage()]));
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $result['code'], json_encode($result['body']));

        // no queda el archivo adjunto ni el archivo en disco
        $this->assertEquals($filesBefore, AttachedFile::count());
        $this->assertFileDoesNotExist(FS_FOLDER . '/MyFiles/' . $image);
        $this->assertEmpty(glob(FS_FOLDER . '/MyFiles/' . date('Y/m') . '/*_' . pathinfo($image, PATHINFO_FILENAME) . '*'));
    }

    protected function tearDown(): void
    {
        foreach ($this->created as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }

        if ($this->product && $this->product->exists()) {
            $this->product->delete();
        }

        $_FILES = [];
        $this->logErrors();
    }

    private function callApi(string $method, string $url, array $values, array $files): array
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        unset($_SERVER['CONTENT_TYPE']);
        $_POST = $values;
        $_GET = [];
        $_FILES = $files;

        // ejecutamos el recurso desde dentro del controlador, sin enviar la respuesta
        $controller = new ApiProductoImagen('ApiProductoImagen', $url);
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
        $result = Closure::bind($exec, $controller, ApiProductoImagen::class)();

        // limpiamos los globales
        $_POST = [];
        $_FILES = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';

        return $result;
    }

    private function callPost(array $values, array $files): array
    {
        return $this->callApi('POST', '/api/3/productoimagenes', $values, $files);
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
        $path = tempnam(sys_get_temp_dir(), 'api');
        file_put_contents($path, $content);
        $this->created[] = $path;
        return $path;
    }

    private function tempImage(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'api');
        $img = imagecreatetruecolor(2, 2);
        imagepng($img, $path);
        $this->created[] = $path;
        return $path;
    }
}
