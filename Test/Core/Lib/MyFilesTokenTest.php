<?php

use FacturaScripts\Core\Lib\MyFilesToken;
use FacturaScripts\Core\Model\AttachedFile;
use PHPUnit\Framework\TestCase;

class MyFilesTokenTest extends TestCase
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
    }

    public function testGetUrl()
    {
        $url = MyFilesToken::getUrl('MyFiles/test.jpg', false);
        $this->assertEquals('MyFiles/test.jpg?myft', explode('=', $url)[0]);

        $url = MyFilesToken::getUrl('/MyFiles/test.jpg', false);
        $this->assertEquals('MyFiles/test.jpg?myft', explode('=', $url)[0]);

        $url = MyFilesToken::getUrl('test.jpg', false);
        $this->assertEquals('MyFiles/test.jpg?myft', explode('=', $url)[0]);
    }
}
