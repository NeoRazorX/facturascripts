<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core\Lib;

use FacturaScripts\Core\Lib\MyFilesToken;
use FacturaScripts\Core\Model\AttachedFile;
use PHPUnit\Framework\TestCase;

final class MyFilesTokenTest extends TestCase
{
    public function testGet(): void
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

        // volvemos a la fecha de hoy
        MyFilesToken::setCurrentDate(date('d-m-Y'));
    }

    public function testGetUrl(): void
    {
        $urlPermanent = MyFilesToken::getUrl('MyFiles/test.jpg', true);
        $this->assertStringStartsWith('MyFiles/test.jpg?myft=', $urlPermanent);
        $token = explode('=', $urlPermanent)[1];
        $this->assertTrue(MyFilesToken::validate('MyFiles/test.jpg', $token), 'Permanent Token not valid');

        $url2 = MyFilesToken::getUrl('/MyFiles/test.jpg', false);
        $this->assertStringStartsWith('MyFiles/test.jpg?myft=', $url2);
        $token2 = explode('=', $url2)[1];
        $this->assertTrue(MyFilesToken::validate('MyFiles/test.jpg', $token2), 'Temporal Token not valid');

        $url3 = MyFilesToken::getUrl('test.jpg', false);
        $this->assertStringStartsWith('MyFiles/test.jpg?myft=', $url3);
        $token3 = explode('=', $url3)[1];
        $this->assertTrue(MyFilesToken::validate('MyFiles/test.jpg', $token3), 'Temporal Token not valid');
    }
}
