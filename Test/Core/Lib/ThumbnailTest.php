<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use Exception;
use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Lib\MyFilesToken;
use FacturaScripts\Core\Lib\Thumbnail;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class ThumbnailTest extends TestCase
{
    use LogErrorsTrait;

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
    public function testGenerate(string $fileName, string $supportExtensionKey, string $notSupportText): void
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

        // Como la imagen no existe, devuelve un string vacío
        $result = Thumbnail::generate('non_existent_file.jpg');
        $this->assertEquals('', $result);

        $filePath = $this->getFakeImage($fileName);

        // Obtenemos la extensión del archivo
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        // Devuelve la ruta del archivo. Creamos una thumbnail sin parámetros
        $result = Thumbnail::generate($filePath);

        $expectedPath = '/MyFiles/Tmp/Thumbnails/' . pathinfo($filePath, PATHINFO_FILENAME) . '_100x100.' . $extension;
        $this->assertEquals($expectedPath, $result);
        $this->assertFileExists(FS_FOLDER . $expectedPath);

        // Comprobamos las rutas con tokens
        $thumbnailsPath = $expectedPath;
        $result = Thumbnail::generate($filePath, 100, 100, true, false);
        $expectedPath = '/MyFiles/Tmp/Thumbnails/' . pathinfo($filePath, PATHINFO_FILENAME)
            . '_100x100.' . $extension . '?myft=' . MyFilesToken::get($thumbnailsPath, false);
        $this->assertEquals($expectedPath, $result);

        $result = Thumbnail::generate($filePath, 100, 100, true, true);
        $expectedPath = '/MyFiles/Tmp/Thumbnails/' . pathinfo($filePath, PATHINFO_FILENAME)
            . '_100x100.' . $extension . '?myft=' . MyFilesToken::get($thumbnailsPath, true);
        $this->assertEquals($expectedPath, $result);

        // Devuelve string vacío al pasarle un archivo con extensión no permitida
        $wrongFilePath = $this->getFakeImage('testTemplate.html.twig');
        $result = Thumbnail::generate($wrongFilePath);
        $this->assertEquals('', $result);

        // Creamos una thumbnail pasando dimensiones
        $result = Thumbnail::generate($filePath, 100, 50);
        $expectedPath = '/MyFiles/Tmp/Thumbnails/' . pathinfo($filePath, PATHINFO_FILENAME) . '_100x50.' . $extension;
        $this->assertEquals($expectedPath, $result);
        $this->assertFileExists(FS_FOLDER . $expectedPath);
        $this->assertEquals(50, getimagesize(FS_FOLDER . $expectedPath)[0]);
        $this->assertEquals(50, getimagesize(FS_FOLDER . $expectedPath)[1]);
        unlink(FS_FOLDER . $expectedPath);

        // Devuelve string vacío y genera log al pasarle un archivo erróneo
        $wrongFilePath = FS_FOLDER . '/MyFiles/wrong_file.jpeg';
        file_put_contents($wrongFilePath, 'wrong_content');
        $result = Thumbnail::generate($wrongFilePath);
        $this->assertEquals('', $result);

        $logs = MiniLog::read();
        $this->assertEquals('imagecreatefromstring(): Data is not in a recognized format', end($logs)['message']);

        // Si existe el directorio THUMBNAIL_PATH, lo eliminamos
        if (is_dir(FS_FOLDER . Thumbnail::THUMBNAIL_PATH)) {
            Tools::folderDelete(FS_FOLDER . Thumbnail::THUMBNAIL_PATH);
        }

        $this->assertDirectoryDoesNotExist(FS_FOLDER . Thumbnail::THUMBNAIL_PATH);
        Thumbnail::generate($filePath);
        $this->assertDirectoryExists(FS_FOLDER . Thumbnail::THUMBNAIL_PATH);
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

        $filePath = $this->getFakeImage('product_image.jpg');

        Thumbnail::generate($filePath);
        Thumbnail::generate($filePath, 200, 200);
        Thumbnail::generate($filePath, 300, 500);

        // Comprobamos antes de borrarlo que existen los archivos
        $expectedPath = FS_FOLDER . '/MyFiles/Tmp/Thumbnails/' . pathinfo($filePath, PATHINFO_FILENAME);
        $this->assertFileExists($expectedPath . '_100x100.jpg');
        $this->assertFileExists($expectedPath . '_200x200.jpg');
        $this->assertFileExists($expectedPath . '_300x500.jpg');

        // BORRAMOS
        Thumbnail::delete($filePath);

        // Comprobamos que, una vez borrado, no existen los archivos
        $this->assertFileDoesNotExist($expectedPath . '_100x100.jpg');
        $this->assertFileDoesNotExist($expectedPath . '_200x200.jpg');
        $this->assertFileDoesNotExist($expectedPath . '_300x500.jpg');
    }

    /**
     * Probar si la ruta esperada de la thumbnail es correcta
     */
    public function testGetExpectedThumbnailPath(): void
    {
        // la ruta es válida, debe crear la thumbnail
        $filePath = '/MyFiles/product_image.jpg';
        $result = Thumbnail::getExpectedThumbnailPath($filePath, 100, 100);
        $this->assertEquals('/MyFiles/Tmp/Thumbnails/product_image_100x100.jpg', $result);

        // la ruta es incorrecta, debe no crear thumbnail
        $filePath = '/MyFiles/product_image.txt';
        $result = Thumbnail::getExpectedThumbnailPath($filePath, 100, 100);
        $this->assertEquals('', $result);
    }

    private function getFakeImage(string $fileName): string
    {
        $sourcePath = FS_FOLDER . '/Test/__files/' . $fileName;
        if (false === file_exists($sourcePath)) {
            throw new Exception('File ' . $sourcePath . ' not found');
        }

        $destPath = FS_FOLDER . '/MyFiles/' . $fileName;
        copy($sourcePath, $destPath);

        return $destPath;
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
