<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2022-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\AttachedFile;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class AttachedFileTest extends TestCase
{
    use LogErrorsTrait;

    public function testSaveFile(): void
    {
        $original = 'xss_img_src_onerror_alert(123).jpeg';
        $originalPath = FS_FOLDER . '/Test/__files/' . $original;
        $this->assertTrue(file_exists($originalPath), 'File not found: ' . $originalPath);

        // copiamos el archivo a MyFiles y renombramos
        $name = 'xss"\'><img src=x onerror=alert(123)>.jpeg';
        if (PHP_OS_FAMILY == 'Windows') {
            $name = 'file_upload_xss_attack_not_possible_on_windows_os.jpeg';
        }
        $this->assertTrue(copy($originalPath, FS_FOLDER . '/MyFiles/' . $name), 'File not copied');

        $model = new AttachedFile();
        $model->path = $name;
        $this->assertTrue($model->save(), 'can-not-save-file');

        // filename no puede contener html
        $fileNameNoHtml = Tools::noHtml($name);
        $this->assertEquals($fileNameNoHtml, $model->filename);

        // si forzamos el html en el filename, debe quitar el html
        $model->filename = $name;
        $this->assertTrue($model->save(), 'can-not-update-file');
        $this->assertEquals($fileNameNoHtml, $model->filename);

        // podemos eliminar
        $this->assertTrue($model->delete(), 'can-not-delete-file');

        // el archivo ya no está en el path
        $this->assertFalse(file_exists($model->path));
    }

    public function testDontReuseIds(): void
    {
        $file1 = 'product_image.jpg';
        $file1Path = FS_FOLDER . '/Test/__files/' . $file1;
        $this->assertTrue(file_exists($file1Path), 'File not found: ' . $file1Path);

        // copiamos el archivo a MyFiles, sin renombrar
        $this->assertTrue(copy($file1Path, FS_FOLDER . '/MyFiles/' . $file1), 'File not copied');

        $att1 = new AttachedFile();
        $att1->path = $file1;
        $this->assertTrue($att1->save(), 'can-not-save-file');

        // nos guardamos el identificador
        $id = $att1->idfile;

        // eliminamos el archivo
        $this->assertTrue($att1->delete(), 'can-not-delete-file');

        $file2 = 'product_image.gif';
        $file2Path = FS_FOLDER . '/Test/__files/' . $file2;
        $this->assertTrue(file_exists($file2Path), 'File not found: ' . $file2Path);

        // copiamos el archivo a MyFiles, sin renombrar
        $this->assertTrue(copy($file2Path, FS_FOLDER . '/MyFiles/' . $file2), 'File not copied');

        $att2 = new AttachedFile();
        $att2->path = $file2;
        $this->assertTrue($att2->save(), 'can-not-save-file');

        // comprobamos que el identificador es distinto
        $this->assertNotEquals($id, $att2->idfile);

        // eliminamos el archivo
        $this->assertTrue($att2->delete(), 'can-not-delete-file');
    }

    public function testStoredNameKeepsOriginal(): void
    {
        $source = FS_FOLDER . '/Test/__files/product_image.jpg';
        $this->assertTrue(file_exists($source), 'File not found: ' . $source);

        // copiamos el archivo a MyFiles con un nombre con espacios, acentos y mayúsculas
        $original = 'Cámara Fotos.JPG';
        $this->assertTrue(copy($source, FS_FOLDER . '/MyFiles/' . $original), 'File not copied');

        $model = new AttachedFile();
        $model->path = $original;
        $this->assertTrue($model->save(), 'can-not-save-file');

        // se guarda como {idfile}_{slug}.{ext}, conservando parte del nombre original
        $this->assertEquals(
            $model->idfile . '_camara_fotos.jpg',
            basename($model->path),
            'stored-name-without-slug'
        );

        // el archivo físico existe en esa ruta
        $this->assertTrue(file_exists($model->getFullPath()), 'stored-file-not-found');

        // podemos eliminar
        $this->assertTrue($model->delete(), 'can-not-delete-file');
        $this->assertFalse(file_exists($model->getFullPath()));
    }

    public function testStoredNameFallbackWhenSlugEmpty(): void
    {
        $source = FS_FOLDER . '/Test/__files/product_image.jpg';
        $this->assertTrue(file_exists($source), 'File not found: ' . $source);

        // un nombre sin caracteres alfanuméricos deja el slug vacío
        $original = '___.jpg';
        $this->assertTrue(copy($source, FS_FOLDER . '/MyFiles/' . $original), 'File not copied');

        $model = new AttachedFile();
        $model->path = $original;
        $this->assertTrue($model->save(), 'can-not-save-file');

        // sin slug, se usa solo el identificador: {idfile}.{ext}
        $this->assertEquals($model->idfile . '.jpg', basename($model->path), 'stored-name-fallback');
        $this->assertTrue(file_exists($model->getFullPath()), 'stored-file-not-found');

        $this->assertTrue($model->delete(), 'can-not-delete-file');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
