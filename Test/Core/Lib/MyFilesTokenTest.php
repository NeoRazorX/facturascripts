<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2024-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\AppKey;
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

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testHmacTokensWithAppKey(): void
    {
        if (false === defined('FS_APP_KEY')) {
            define('FS_APP_KEY', AppKey::generate());
        }
        $this->assertFalse(AppKey::isDerived(), 'app-key-not-used');

        // con FS_APP_KEY los tokens nuevos ya no son los antiguos
        $path = 'MyFiles' . DIRECTORY_SEPARATOR . 'test.jpg';
        $init = FS_DB_NAME . FS_DB_PASS;
        $nextWeek = date('d-m-Y', strtotime('+1 week'));
        $tokenPermanent = MyFilesToken::get($path, true);
        $tokenTemporal = MyFilesToken::get($path, false);
        $tokenOneWeek = MyFilesToken::get($path, false, $nextWeek);
        $this->assertNotEquals(sha1($init . $path), $tokenPermanent, 'permanent-token-is-legacy');
        $this->assertStringEndsWith('|' . $nextWeek, $tokenOneWeek, 'expiration-not-in-token');

        // los nuevos y los antiguos son válidos
        $this->assertTrue(MyFilesToken::validate($path, $tokenPermanent), 'permanent-token-not-valid');
        $this->assertTrue(MyFilesToken::validate($path, $tokenTemporal), 'temporal-token-not-valid');
        $this->assertTrue(MyFilesToken::validate($path, $tokenOneWeek), 'one-week-token-not-valid');
        $this->assertTrue(MyFilesToken::validate($path, sha1($init . $path)), 'legacy-permanent-token-not-valid');

        // pero no sirven para otro archivo
        $this->assertFalse(MyFilesToken::validate('test2.jpg', $tokenPermanent), 'token-valid-for-other-file');
    }

    public function testLegacyTokens(): void
    {
        // los tokens anteriores a FS_APP_KEY siguen siendo válidos, para no romper los enlaces compartidos
        $path = 'MyFiles' . DIRECTORY_SEPARATOR . 'test.jpg';
        $init = FS_DB_NAME . FS_DB_PASS;
        $nextWeek = date('d-m-Y', strtotime('+1 week'));
        $this->assertTrue(MyFilesToken::validate($path, sha1($init . $path)), 'legacy-permanent-token-not-valid');
        $this->assertTrue(MyFilesToken::validate($path, sha1($init . $path . MyFilesToken::getCurrentDate())), 'legacy-daily-token-not-valid');
        $this->assertTrue(MyFilesToken::validate($path, sha1($init . $path . $nextWeek) . '|' . $nextWeek), 'legacy-expiring-token-not-valid');

        // pero no los de otro archivo, ni los caducados
        $this->assertFalse(MyFilesToken::validate($path, sha1($init . $path . 'x')), 'legacy-other-file-token-valid');
        $lastWeek = date('d-m-Y', strtotime('-1 week'));
        $this->assertFalse(MyFilesToken::validate($path, sha1($init . $path . $lastWeek) . '|' . $lastWeek), 'legacy-expired-token-valid');
    }

    public function testTokenOfOtherFile(): void
    {
        $token = MyFilesToken::get('test.jpg', true);
        $this->assertFalse(MyFilesToken::validate('test2.jpg', $token), 'token-valid-for-other-file');
        $this->assertFalse(MyFilesToken::validate('test.jpg', ''), 'empty-token-valid');

        // un token con fecha de expiración manipulada no es válido
        $nextWeek = date('d-m-Y', strtotime('+1 week'));
        $nextYear = date('d-m-Y', strtotime('+1 year'));
        $tokenWeek = MyFilesToken::get('test.jpg', false, $nextWeek);
        $hash = explode('|', $tokenWeek)[0];
        $this->assertFalse(MyFilesToken::validate('test.jpg', $hash . '|' . $nextYear), 'tampered-expiration-valid');
    }

    public function testGetUrl(): void
    {
        $testPath = 'MyFiles' . DIRECTORY_SEPARATOR . 'test.jpg';

        $urlPermanent = MyFilesToken::getUrl($testPath, true);
        $this->assertStringStartsWith('MyFiles/test.jpg?myft=', $urlPermanent);
        $token = explode('=', $urlPermanent)[1];
        $this->assertTrue(MyFilesToken::validate($testPath, $token), 'Permanent Token not valid');

        $url2 = MyFilesToken::getUrl('/' . $testPath, false);
        $this->assertStringStartsWith('MyFiles/test.jpg?myft=', $url2);
        $token2 = explode('=', $url2)[1];
        $this->assertTrue(MyFilesToken::validate($testPath, $token2), 'Temporal Token not valid');

        $url3 = MyFilesToken::getUrl('test.jpg', false);
        $this->assertStringStartsWith('MyFiles/test.jpg?myft=', $url3);
        $token3 = explode('=', $url3)[1];
        $this->assertTrue(MyFilesToken::validate($testPath, $token3), 'Temporal Token not valid');
    }
}
