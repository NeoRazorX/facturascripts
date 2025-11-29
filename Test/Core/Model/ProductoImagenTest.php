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

    public function testGetThumbnail(): void
    {
        // saltamos el test si no tenemos la extensión GD
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('The GD extension is not available.');
        }

        // saltamos el test si GD no soporta JPEG
        $info = gd_info();
        if (!isset($info['JPEG Support']) || !$info['JPEG Support']) {
            $this->markTestSkipped('GD does not support JPEG.');
        }

        $producto = $this->getRandomProduct();
        $this->assertTrue($producto->save());

        // Como la imagen no existe, devuelve un string vacío
        $productoImagen = new ProductoImagen();
        $result = $productoImagen->getThumbnail();
        $this->assertEquals('', $result);

        // Relacionamos un archivo y un producto
        $attachedFile = $this->getFakeAttachedFile('product_image.jpg');
        $this->assertTrue($attachedFile->save());

        $productoImagen->idfile = $attachedFile->idfile;
        $productoImagen->idproducto = $producto->idproducto;
        $productoImagen->referencia = $producto->referencia;

        // Devuelve la ruta del archivo.
        $result = $productoImagen->getThumbnail();
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertFileExists(FS_FOLDER . $result);

        // eliminamos
        $attachedFile->delete();
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
        $this->assertTrue((new ProductoImagen())->load($productoImagen->id));

        // BORRAMOS
        $productoImagen->delete();

        // Comprobamos que no existen las entradas en la BBDD
        $this->assertFalse((new ProductoImagen())->load($productoImagen->id));

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
            throw new Exception('File ' . $source_path . ' not found');
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
