<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use Exception;
use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Lib\MyFilesToken;
use FacturaScripts\Core\Model\AttachedFile;
use FacturaScripts\Core\Model\ProductoImagen;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\UploadedFile;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class ProductoImagenTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

    public function fileNameProvider(): array
    {
        return [
            ['product_image.jpg', 'JPEG Support', 'GD does not support JPEG'],
            ['product_image.png', 'PNG Support', 'GD does not support PNG'],
            ['product_image.gif', 'GIF Create Support', 'GD does not support GIF'],
        ];
    }

    /**
     * @dataProvider fileNameProvider
     * @param string $fileName
     * @param string $supportExtensionKey
     * @param string $notSupportText
     * @throws Exception
     */
    public function testGetThumbnail(string $fileName, string $supportExtensionKey, string $notSupportText): void
    {
        // saltamos el test si no tenemos la extensión GD
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('The GD extension is not available.');
        }

        // saltamos el test si GD no soporta el tipo de archivo testeado
        $info = gd_info();
        if (!isset($info[$supportExtensionKey]) || !$info[$supportExtensionKey]) {
            $this->markTestSkipped($notSupportText);
        }

        $producto = $this->getRandomProduct();
        $this->assertTrue($producto->save());

        // Como la imagen no existe, devuelve un string vacío
        $productoImagen = new ProductoImagen();
        $result = $productoImagen->getThumbnail();
        $this->assertEquals('', $result);

        // Relacionamos un archivo y un producto
        $attachedFile = $this->getFakeAttachedFile($fileName);
        $this->assertTrue($attachedFile->save());

        // Obtenemos la extensión del archivo
        $extension = pathinfo(FS_FOLDER . '/MyFiles/' . $fileName, PATHINFO_EXTENSION);

        $productoImagen->idfile = $attachedFile->idfile;
        $productoImagen->idproducto = $producto->idproducto;
        $productoImagen->referencia = $producto->referencia;

        // Devuelve la ruta del archivo. Creamos una thumbnail sin parámetros
        $result = $productoImagen->getThumbnail();

        $expectedPath = '/MyFiles/Tmp/Thumbnails/' . pathinfo($attachedFile->filename, PATHINFO_FILENAME) . '_100x100.' . $extension;
        $this->assertEquals($expectedPath, $result);
        $this->assertFileExists(FS_FOLDER . $expectedPath);

        // Comprobamos las rutas con tokens
        $thumbnailsPath = $expectedPath;
        $result = $productoImagen->getThumbnail(100, 100, true, false);
        $expectedPath = '/MyFiles/Tmp/Thumbnails/' . pathinfo($attachedFile->filename, PATHINFO_FILENAME)
            . '_100x100.' . $extension . '?myft=' . MyFilesToken::get($thumbnailsPath, false);
        $this->assertEquals($expectedPath, $result);

        $result = $productoImagen->getThumbnail(100, 100, true, true);
        $expectedPath = '/MyFiles/Tmp/Thumbnails/' . pathinfo($attachedFile->filename, PATHINFO_FILENAME)
            . '_100x100.' . $extension . '?myft=' . MyFilesToken::get($thumbnailsPath, true);
        $this->assertEquals($expectedPath, $result);

        // Devuelve string vacío al pasarle un archivo con extensión no permitida
        $wrongFile = $this->getFakeAttachedFile('testTemplate.html.twig');
        $this->assertTrue($wrongFile->save());
        $productoImagen->idfile = $wrongFile->idfile;
        $result = $productoImagen->getThumbnail();
        $this->assertEquals('', $result);

        // Creamos una thumbnail pasando dimensiones
        $productoImagen->idfile = $attachedFile->idfile;
        $result = $productoImagen->getThumbnail(100, 50);
        $expectedPath = '/MyFiles/Tmp/Thumbnails/' . pathinfo($attachedFile->filename, PATHINFO_FILENAME) . '_100x50.' . $extension;
        $this->assertEquals($expectedPath, $result);
        $this->assertFileExists(FS_FOLDER . $expectedPath);
        $this->assertEquals(50, getimagesize(FS_FOLDER . $expectedPath)[0]);
        $this->assertEquals(50, getimagesize(FS_FOLDER . $expectedPath)[1]);
        unlink(FS_FOLDER . $expectedPath);

        // Devuelve string vacío y genera log al pasarle un archivo erróneo
        file_put_contents(FS_FOLDER . '/MyFiles/wrong_file.jpeg', 'wrong_content');
        $attachedFile = new AttachedFile();
        $attachedFile->path = 'wrong_file.jpeg';
        $attachedFile->save();
        $productoImagen->idfile = $attachedFile->idfile;
        $result = $productoImagen->getThumbnail();
        $this->assertEquals('', $result);

        $logs = MiniLog::read();
        $this->assertEquals('imagecreatefromstring(): Data is not in a recognized format', end($logs)['message']);

        // Si existe el directorio THUMBNAIL_PATH, lo eliminamos
        if (is_dir(FS_FOLDER . $productoImagen::THUMBNAIL_PATH)) {
            Tools::folderDelete(FS_FOLDER . $productoImagen::THUMBNAIL_PATH);
        }

        $this->assertDirectoryDoesNotExist(FS_FOLDER . $productoImagen::THUMBNAIL_PATH);
        $productoImagen->getThumbnail();
        $this->assertDirectoryExists(FS_FOLDER . $productoImagen::THUMBNAIL_PATH);

        // eliminamos
        $attachedFile->delete();
        $producto->delete();
    }

    public function testDelete(): void
    {
        // saltamos el test si la extensión GD no está instalada
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('La extensión GD no está instalada.');
        }

        // saltamos el test si GD no soporta JPEG
        $info = gd_info();
        if (!isset($info['JPEG Support']) || !$info['JPEG Support']) {
            $this->markTestSkipped('GD does not support JPEG.');
        }

        $producto = $this->getRandomProduct();
        $this->assertTrue($producto->save());

        $attachedFile = $this->getFakeAttachedFile('product_image.jpg');
        $this->assertTrue($attachedFile->save());

        $productoImagen = new ProductoImagen();
        $productoImagen->idfile = $attachedFile->idfile;
        $productoImagen->idproducto = $producto->idproducto;
        $productoImagen->referencia = $producto->referencia;
        $productoImagen->save();

        $productoImagen->getThumbnail();
        $productoImagen->getThumbnail(200, 200);
        $productoImagen->getThumbnail(300, 500);

        // Comprobamos antes de borrarlo que existen los archivos y entradas en la BBDD
        $expectedPath = FS_FOLDER . '/MyFiles/Tmp/Thumbnails/' . pathinfo($attachedFile->filename, PATHINFO_FILENAME);
        $this->assertFileExists($expectedPath . '_100x100.jpg');
        $this->assertFileExists($expectedPath . '_200x200.jpg');
        $this->assertFileExists($expectedPath . '_300x500.jpg');
        $this->assertTrue((new ProductoImagen())->loadFromCode($productoImagen->id));

        // BORRAMOS
        $productoImagen->delete();

        // Comprobamos que, una vez borrado, no existen los archivos ni las entradas en la BBDD
        $expectedPath = FS_FOLDER . '/MyFiles/Tmp/Thumbnails/' . pathinfo($attachedFile->filename, PATHINFO_FILENAME);
        $this->assertFileDoesNotExist($expectedPath . '_100x100.jpg');
        $this->assertFileDoesNotExist($expectedPath . '_200x200.jpg');
        $this->assertFileDoesNotExist($expectedPath . '_300x500.jpg');
        $this->assertFalse((new ProductoImagen())->loadFromCode($productoImagen->id));

        // eliminamos
        $attachedFile->delete();
        $producto->delete();
    }

    public function testInstall(): void
    {
        $productoImagen = new ProductoImagen();

        $result = $productoImagen->install();

        $this->assertEquals('', $result);
    }

    public function testGetProducto(): void
    {
        $producto = $this->getRandomProduct();
        $this->assertTrue($producto->save());

        $productoImagen = new ProductoImagen();

        // Relacionamos un archivo y un producto
        $attachedFile = $this->getFakeAttachedFile('product_image.jpg');
        $this->assertTrue($attachedFile->save());

        $productoImagen->idfile = $attachedFile->idfile;
        $productoImagen->idproducto = $producto->idproducto;
        $productoImagen->referencia = $producto->referencia;

        $result = $productoImagen->getProducto();

        $this->assertEquals($producto->idproducto, $result->idproducto);
        $this->assertEquals($producto->descripcion, $result->descripcion);

        // eliminamos
        $attachedFile->delete();
        $producto->delete();
    }

    public function testGetFile(): void
    {
        $productoImagen = new ProductoImagen();

        $attachedFile = $this->getFakeAttachedFile('product_image.jpg');
        $this->assertTrue($attachedFile->save());

        $productoImagen->idfile = $attachedFile->idfile;

        $result = $productoImagen->getFile();

        $this->assertEquals($attachedFile->idfile, $result->idfile);
        $this->assertEquals($attachedFile->path, $result->path);
    }

    public function testPrimaryColumn(): void
    {
        $productoImagen = new ProductoImagen();

        $result = $productoImagen::primaryColumn();

        $this->assertEquals('id', $result);
    }

    public function testGetMaxFileUpload(): void
    {
        $productoImagen = new ProductoImagen();

        $result = $productoImagen->getMaxFileUpload();

        $this->assertEquals((UploadedFile::getMaxFilesize() / 1024 / 1024), $result);
    }

    public function testTableName(): void
    {
        $productoImagen = new ProductoImagen();

        $result = $productoImagen::tableName();

        $this->assertEquals('productos_imagenes', $result);
    }

    public function testUrl(): void
    {
        $productoImagen = new ProductoImagen();

        $result = $productoImagen->url();
        $this->assertEquals('ListProductoImagen', $result);

        $result = $productoImagen->url('download');
        $this->assertEquals('?myft=' . MyFilesToken::get('', false), $result);

        $result = $productoImagen->url('download-permanent');
        $this->assertEquals('?myft=' . MyFilesToken::get('', true), $result);
    }

    private function getFakeAttachedFile(string $file_name): AttachedFile
    {
        $source_path = FS_FOLDER . '/Test/__files/' . $file_name;
        if (false === file_exists($source_path)) {
            throw new Exception("File $source_path not found");
        }

        $dest_path = FS_FOLDER . '/MyFiles/' . $file_name;
        copy($source_path, $dest_path);

        $attachedFile = new AttachedFile();
        $attachedFile->path = $file_name;
        return $attachedFile;
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
