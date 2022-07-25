<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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
    public function testSaveFile()
    {
        $original = 'xss_img_src_onerror_alert(123).jpeg';
        $originalPath = FS_FOLDER . '/Test/__files/' . $original;
        $this->assertTrue(file_exists($originalPath), 'File not found: ' . $originalPath);

        // copiamos el archivo a MyFiles y renombramos
        $name = 'xss"\'><img src=x onerror=alert(123)>.jpeg';
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

    public function testGetTokenFile()
    {
        $original = 'xss_img_src_onerror_alert(123).jpeg';
        $originalPath = FS_FOLDER . '/Test/__files/' . $original;
        $this->assertTrue(file_exists($originalPath), 'File not found: ' . $originalPath);

        // copiamos el archivo a MyFiles y renombramos
        $name = 'xss"\'><img src=x onerror=alert(123)>.jpeg';
        $this->assertTrue(copy($originalPath, FS_FOLDER . '/MyFiles/' . $name), 'File not copied');

        $model = new AttachedFile();
        $model->path = $name;
        $this->assertTrue($model->save(), 'can-not-save-file');

        // validamos un token permanente
        $tokenPermanent = MyFilesToken::get($model->path, true);
        $this->assertTrue(MyFilesToken::validate($model->path, $tokenPermanent), 'Permanent Token not valid');

        // validamos un token temporal de 24 horas
        $tokenTemporal = MyFilesToken::get($model->path, false);
        $this->assertTrue(MyFilesToken::validate($model->path, $tokenTemporal), 'Temporal default Token not valid');

        // validamos un token pasado de fecha
        MyFilesToken::$datetime = '2099-01-01 00:00:00';
        $this->assertFalse(MyFilesToken::validate($model->path, $tokenTemporal), 'Temporal 2099 Token not valid');

        // validamos un token permanente viejo
        $init = FS_DB_NAME . FS_DB_PASS;
        $tokenOld1 = sha1($init . $model->path);
        $this->assertTrue(MyFilesToken::validate($model->path, $tokenOld1), 'Permanent old Token not valid');

        // validamos un token temporal viejo
        $date = date('d-m-Y');
        $tokenOld2 = sha1($init . $model->path . $date);
        $this->assertTrue(MyFilesToken::validate($model->path, $tokenOld2), 'Temporal default old Token not valid');

        // validamos un token temporal viejo pasado de fecha
        MyFilesToken::$date = '01-01-2099';
        $tokenOld3 = sha1($init . $model->path . $date);
        $this->assertFalse(MyFilesToken::validate($model->path, $tokenOld3), 'Temporal 2099 old Token not valid');

        // podemos eliminar
        $this->assertTrue($model->delete(), 'can-not-delete-file');

        // el archivo ya no está en el path
        $this->assertFalse(file_exists($model->path));
    }
}
