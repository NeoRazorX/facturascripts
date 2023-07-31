<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Base\MyFilesToken;
use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Core\Model\AttachedFile;
use FacturaScripts\Core\Model\ProductoImagen;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ProductoImagenTest extends TestCase
{
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
        $attached_file = $this->getFakeAttachedFile('test.jpeg');

        $productoImagen->idfile = $attached_file->idfile;
        $productoImagen->idproducto = $producto->idproducto;
        $productoImagen->referencia = $producto->referencia;

        // Devuelve la ruta del archivo JPEG. Creamos una thumbnail JPEG sin parámetros
        $result = $productoImagen->getThumbnail();

        $expected_path = '/MyFiles/Tmp/Thumbnails/' . pathinfo($attached_file->filename, PATHINFO_FILENAME) . '_100x100.jpeg';
        $this->assertEquals($expected_path, $result);
        $this->assertFileExists(FS_FOLDER . $expected_path);

        unlink(FS_FOLDER . $expected_path);

        // Comprobamos las rutas con tokens
        $thumbnails_path = $expected_path;

        $result = $productoImagen->getThumbnail(100, 100, true, false);
        $expected_path = '/MyFiles/Tmp/Thumbnails/test_100x100.jpeg?myft=' . MyFilesToken::get($thumbnails_path, false);
        $this->assertEquals($expected_path, $result);

        $result = $productoImagen->getThumbnail(100, 100, true, true);
        $expected_path = '/MyFiles/Tmp/Thumbnails/test_100x100.jpeg?myft=' . MyFilesToken::get($thumbnails_path, true);
        $this->assertEquals($expected_path, $result);

        // Devuelve la ruta del archivo PNG
        $png_file = $this->getFakeAttachedFile('test.png');
        $productoImagen->idfile = $png_file->idfile;

        $result = $productoImagen->getThumbnail();

        $expected_path = '/MyFiles/Tmp/Thumbnails/' . pathinfo($attached_file->filename, PATHINFO_FILENAME) . '_100x100.png';

        $this->assertEquals($expected_path, $result);

        $png_file->delete();

        // Devuelve la ruta del archivo GIF
        $png_file = $this->getFakeAttachedFile('test.gif');
        $productoImagen->idfile = $png_file->idfile;

        $result = $productoImagen->getThumbnail();

        $expected_path = '/MyFiles/Tmp/Thumbnails/' . pathinfo($attached_file->filename, PATHINFO_FILENAME) . '_100x100.gif';
        $this->assertEquals($expected_path, $result);

        $png_file->delete();

        // Devuelve string vacío al pasarle un archivo con extensión no permitida
        $wrong_file = $this->getFakeAttachedFile('test.wrong_extension');
        $productoImagen->idfile = $wrong_file->idfile;

        $result = $productoImagen->getThumbnail();

        $this->assertEquals('', $result);

        $wrong_file->delete();

        // Creamos una thumbnail pasando dimensiones
        $png_file = $this->getFakeAttachedFile('test.jpeg');
        $productoImagen->idfile = $png_file->idfile;

        $result = $productoImagen->getThumbnail(100, 50);

        $expected_path = '/MyFiles/Tmp/Thumbnails/' . pathinfo($attached_file->filename, PATHINFO_FILENAME) . '_100x50.jpeg';
        $this->assertEquals($expected_path, $result);
        $this->assertFileExists(FS_FOLDER . $expected_path);
        $this->assertEquals(75, getimagesize(FS_FOLDER . $expected_path)[0]);
        $this->assertEquals(50, getimagesize(FS_FOLDER . $expected_path)[1]);

        unlink(FS_FOLDER . $expected_path);

        // Devuelve string vacío y genera log al pasarle un archivo erroneo
        file_put_contents(FS_FOLDER . '/MyFiles/wrong_file.jpeg', 'wrong_content');

        $attached_file = new AttachedFile();
        $attached_file->path = 'wrong_file.jpeg';
        $attached_file->save();

        $productoImagen->idfile = $attached_file->idfile;

        $result = $productoImagen->getThumbnail();

        $this->assertEquals('', $result);

        $logs = MiniLog::read();
        $this->assertEquals('imagecreatefromstring(): Data is not in a recognized format', end($logs)['message']);

        // Si no existe el directorio THUMBNAIL_PATH = '/MyFiles/Tmp/Thumbnails/', lo crea
        if (is_dir(FS_FOLDER . $productoImagen::THUMBNAIL_PATH)) {
            ToolBox::files()::delTree(FS_FOLDER . $productoImagen::THUMBNAIL_PATH);
        }

        $this->assertDirectoryNotExists(FS_FOLDER . $productoImagen::THUMBNAIL_PATH);
        $productoImagen->getThumbnail();
        $this->assertDirectoryExists(FS_FOLDER . $productoImagen::THUMBNAIL_PATH);

        // eliminamos
        $attached_file->delete();
        $png_file->delete();
        $producto->delete();
    }

    public function testDelete(): void
    {
        $producto = $this->getRandomProduct();
        $this->assertTrue($producto->save());

        $attached_file = $this->getFakeAttachedFile('test.jpeg');

        $productoImagen = new ProductoImagen();
        $productoImagen->idfile = $attached_file->idfile;
        $productoImagen->idproducto = $producto->idproducto;
        $productoImagen->referencia = $producto->referencia;
        $productoImagen->save();

        $productoImagen->getThumbnail();
        $productoImagen->getThumbnail(200, 200);
        $productoImagen->getThumbnail(300, 500);

        // Comprobamos antes de borrarlo que existen los archivos y entradas en la BBDD
        $expected_path = FS_FOLDER . '/MyFiles/Tmp/Thumbnails/' . pathinfo($attached_file->filename, PATHINFO_FILENAME);
        $this->assertFileExists($expected_path . '_100x100.jpeg');
        $this->assertFileExists($expected_path . '_200x200.jpeg');
        $this->assertFileExists($expected_path . '_300x500.jpeg');
        $this->assertTrue((new ProductoImagen())->loadFromCode($productoImagen->id));

        // BORRAMOS
        $productoImagen->delete();

        // Comprobamos que, una vez borrado, no existen los archivos ni las entradas en la BBDD
        $expected_path = FS_FOLDER . '/MyFiles/Tmp/Thumbnails/' . pathinfo($attached_file->filename, PATHINFO_FILENAME);
        $this->assertFileNotExists($expected_path . '_100x100.jpeg');
        $this->assertFileNotExists($expected_path . '_200x200.jpeg');
        $this->assertFileNotExists($expected_path . '_300x500.jpeg');
        $this->assertFalse((new ProductoImagen())->loadFromCode($productoImagen->id));

        // eliminamos
        $attached_file->delete();
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
        $attached_file = $this->getFakeAttachedFile('test.jpeg');

        $productoImagen->idfile = $attached_file->idfile;
        $productoImagen->idproducto = $producto->idproducto;
        $productoImagen->referencia = $producto->referencia;

        $result = $productoImagen->getProducto();

        $this->assertEquals($producto->idproducto, $result->idproducto);
        $this->assertEquals($producto->descripcion, $result->descripcion);

        // eliminamos
        $attached_file->delete();
        $producto->delete();
    }

    public function testGetFile(): void
    {
        $productoImagen = new ProductoImagen();

        $attached_file = $this->getFakeAttachedFile('test.jpeg');

        $productoImagen->idfile = $attached_file->idfile;

        $result = $productoImagen->getFile();

        $this->assertEquals($attached_file->idfile, $result->idfile);
        $this->assertEquals($attached_file->path, $result->path);
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
        $tests_file_name = 'xss_img_src_onerror_alert(123).jpeg';
        $source_path = FS_FOLDER . '/Test/__files/' . $tests_file_name;
        $dest_path = FS_FOLDER . '/MyFiles/' . $file_name;
        copy($source_path, $dest_path);

        $attached_file = new AttachedFile();
        $attached_file->path = $file_name;
        $attached_file->save();

        return $attached_file;
    }
}
