<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2022-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\MyFilesToken;
use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Core\Model\AttachedFile;
use PHPUnit\Framework\TestCase;

final class AttachedFileTest extends TestCase
{
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
        $fileNameNoHtml = ToolBox::utils()::noHtml($name);
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

    public function testTokens(): void
    {
        $original = 'xss_img_src_onerror_alert(123).jpeg';
        $originalPath = FS_FOLDER . '/Test/__files/' . $original;
        $this->assertTrue(file_exists($originalPath), 'File not found: ' . $originalPath);

        // copiamos el archivo a MyFiles y renombramos
        $name = 'test.jpg';
        $this->assertTrue(copy($originalPath, FS_FOLDER . '/MyFiles/' . $name), 'File not copied');

        $model = new AttachedFile();
        $model->path = $name;
        $this->assertTrue($model->save(), 'can-not-save-file');

        // comprobamos que la fecha es hoy
        $this->assertEquals(MyFilesToken::getCurrentDate(), date('d-m-Y'), 'Bad current date');

        // generamos los tokens
        $tokenPermanent = MyFilesToken::get($model->path, true);
        $tokenTemporal = MyFilesToken::get($model->path, false);
        $tokenOneWeek = MyFilesToken::get($model->path, false, date('d-m-Y', strtotime('+1 week')));

        // validamos los tokens
        $this->assertTrue(MyFilesToken::validate($model->path, $tokenPermanent), 'Permanent Token not valid');
        $this->assertTrue(MyFilesToken::validate($model->path, $tokenTemporal), 'Temporal default Token not valid');
        $this->assertTrue(MyFilesToken::validate($model->path, $tokenOneWeek), 'Temporal one week Token not valid');

        // asignamos la fecha de mañana
        $tomorrow = date('d-m-Y', strtotime('+1 day'));
        MyFilesToken::setCurrentDate($tomorrow);

        // validamos los tokens de nuevo
        $this->assertTrue(MyFilesToken::validate($model->path, $tokenPermanent), 'Permanent Token not valid');
        $this->assertFalse(MyFilesToken::validate($model->path, $tokenTemporal), 'Temporal default Token still valid');
        $this->assertTrue(MyFilesToken::validate($model->path, $tokenOneWeek), 'Temporal one week Token not valid');

        // asignamos la fecha de dentro de 8 días
        $nextWeek = date('d-m-Y', strtotime('+8 days'));
        MyFilesToken::setCurrentDate($nextWeek);

        // validamos los tokens de nuevo
        $this->assertTrue(MyFilesToken::validate($model->path, $tokenPermanent), 'Permanent Token not valid');
        $this->assertFalse(MyFilesToken::validate($model->path, $tokenTemporal), 'Temporal default Token still valid');
        $this->assertFalse(MyFilesToken::validate($model->path, $tokenOneWeek), 'Temporal one week Token still valid');

        // eliminamos el archivo
        $this->assertTrue($model->delete(), 'can-not-delete-file');
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
}
