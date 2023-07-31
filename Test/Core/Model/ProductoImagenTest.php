<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Base\MyFilesToken;
use FacturaScripts\Core\Model\AttachedFile;
use FacturaScripts\Core\Model\ProductoImagen;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ProductoImagenTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

    public function testGetThumbnail(): void
    {
        $producto = $this->getRandomProduct();
        $this->assertTrue($producto->save());

        $productoImagen = new ProductoImagen();

        // Como la imagen no existe, devuelve en string vacío
        $result = $productoImagen->getThumbnail();
        $this->assertEquals('', $result);

        // Relacionamos un archivo y un producto
        $attachedFile = $this->getFakeAttachedFile('product_image.jpg');
        $this->assertTrue($attachedFile->save());

        $productoImagen->idfile = $attachedFile->idfile;
        $productoImagen->idproducto = $producto->idproducto;
        $productoImagen->referencia = $producto->referencia;

        // Devuelve la ruta del archivo JPEG. Creamos una thumbnail JPEG sin parámetros
        $result = $productoImagen->getThumbnail();

        $expectedPath = '/MyFiles/Tmp/Thumbnails/' . pathinfo($attachedFile->filename, PATHINFO_FILENAME) . '_100x100.jpg';
        $this->assertEquals($expectedPath, $result);
        $this->assertFileExists(FS_FOLDER . $expectedPath);

        // Comprobamos las rutas con tokens
        $thumbnailsPath = $expectedPath;
        $result = $productoImagen->getThumbnail(100, 100, true, false);
        $expectedPath = '/MyFiles/Tmp/Thumbnails/' . pathinfo($attachedFile->filename, PATHINFO_FILENAME)
            . '_100x100.jpg?myft=' . MyFilesToken::get($thumbnailsPath, false);
        $this->assertEquals($expectedPath, $result);

        $result = $productoImagen->getThumbnail(100, 100, true, true);
        $expectedPath = '/MyFiles/Tmp/Thumbnails/' . pathinfo($attachedFile->filename, PATHINFO_FILENAME)
            . '_100x100.jpg?myft=' . MyFilesToken::get($thumbnailsPath, true);
        $this->assertEquals($expectedPath, $result);

        // Devuelve la ruta del archivo PNG
        $pngFile = $this->getFakeAttachedFile('product_image.png');
        $this->assertTrue($pngFile->save());
        $productoImagen->idfile = $pngFile->idfile;
        $result = $productoImagen->getThumbnail();
        $expectedPath = '/MyFiles/Tmp/Thumbnails/' . pathinfo($pngFile->filename, PATHINFO_FILENAME) . '_100x100.png';
        $this->assertEquals($expectedPath, $result);

        // Devuelve la ruta del archivo GIF
        $gifFile = $this->getFakeAttachedFile('product_image.gif');
        $this->assertTrue($gifFile->save());
        $productoImagen->idfile = $gifFile->idfile;
        $result = $productoImagen->getThumbnail();
        $expectedPath = '/MyFiles/Tmp/Thumbnails/' . pathinfo($gifFile->filename, PATHINFO_FILENAME) . '_100x100.gif';
        $this->assertEquals($expectedPath, $result);

        // Devuelve string vacío al pasarle un archivo con extensión no permitida
        $wrongFile = $this->getFakeAttachedFile('testTemplate.html.twig');
        $this->assertTrue($wrongFile->save());
        $productoImagen->idfile = $wrongFile->idfile;
        $result = $productoImagen->getThumbnail();
        $this->assertEquals('', $result);

        // Creamos una thumbnail pasando dimensiones
        $productoImagen->idfile = $pngFile->idfile;
        $result = $productoImagen->getThumbnail(100, 50);
        $expectedPath = '/MyFiles/Tmp/Thumbnails/' . pathinfo($pngFile->filename, PATHINFO_FILENAME) . '_100x50.png';
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

        $this->assertDirectoryNotExists(FS_FOLDER . $productoImagen::THUMBNAIL_PATH);
        $productoImagen->getThumbnail();
        $this->assertDirectoryExists(FS_FOLDER . $productoImagen::THUMBNAIL_PATH);

        // eliminamos
        $attachedFile->delete();
        $pngFile->delete();
        $gifFile->delete();
        $producto->delete();
    }

    public function testDelete(): void
    {
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
        $this->assertFileNotExists($expectedPath . '_100x100.jpg');
        $this->assertFileNotExists($expectedPath . '_200x200.jpg');
        $this->assertFileNotExists($expectedPath . '_300x500.jpg');
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
