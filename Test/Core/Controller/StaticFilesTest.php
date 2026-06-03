<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core\Controller;

use FacturaScripts\Core\Controller\Files;
use FacturaScripts\Core\Controller\Myfiles;
use FacturaScripts\Core\KernelException;
use FacturaScripts\Core\Model\AttachedFile;
use FacturaScripts\Core\Tools;
use PHPUnit\Framework\TestCase;

final class StaticFilesTest extends TestCase
{
    /** @var string */
    private static $testFolder = 'StaticFilesTest';

    public static function tearDownAfterClass(): void
    {
        Tools::folderDelete('MyFiles' . DIRECTORY_SEPARATOR . 'Private' . DIRECTORY_SEPARATOR . self::$testFolder);
        Tools::folderDelete('MyFiles' . DIRECTORY_SEPARATOR . 'Public' . DIRECTORY_SEPARATOR . self::$testFolder);
        Tools::folderDelete('Plugins' . DIRECTORY_SEPARATOR . self::$testFolder);
    }

    public function testFilesControllerRejectsTraversalFromSafeFolder(): void
    {
        $this->createFile('MyFiles', 'Private', self::$testFolder, 'invoice.pdf');

        try {
            new Files('Files', '/Plugins/../MyFiles/Private/' . self::$testFolder . '/invoice.pdf');
            $this->fail('La ruta con traversal debe rechazarse.');
        } catch (KernelException $exception) {
            $this->assertEquals('UnsafeFolder', $exception->handler);
        }
    }

    public function testFilesControllerAcceptsSafePluginFile(): void
    {
        $this->createFile('Plugins', self::$testFolder, 'Assets', 'style.css');

        $this->expectNotToPerformAssertions();
        new Files('Files', '/Plugins/' . self::$testFolder . '/Assets/style.css');
    }

    public function testMyfilesControllerRejectsPublicTraversal(): void
    {
        $this->createFile('MyFiles', 'Public', self::$testFolder, 'public.pdf');
        $this->createFile('MyFiles', 'Private', self::$testFolder, 'invoice.pdf');

        try {
            new Myfiles('Myfiles', '/MyFiles/Public/' . self::$testFolder . '/../../Private/'
                . self::$testFolder . '/invoice.pdf');
            $this->fail('La ruta con traversal debe rechazarse.');
        } catch (KernelException $exception) {
            $this->assertEquals('MyfilesTokenError', $exception->handler);
        }
    }

    public function testMyfilesControllerAcceptsPublicFile(): void
    {
        $this->createFile('MyFiles', 'Public', self::$testFolder, 'public.pdf');

        $this->expectNotToPerformAssertions();
        new Myfiles('Myfiles', '/MyFiles/Public/' . self::$testFolder . '/public.pdf');
    }

    public function testMyfilesControllerCountsRealDownloads(): void
    {
        $file = $this->createAttachedFile('download.pdf');
        $_GET['myft'] = $this->getTokenFromUrl($file->url('download-permanent'));

        try {
            new Myfiles('Myfiles', '/' . $file->path);
            $reloaded = new AttachedFile();
            $this->assertTrue($reloaded->loadFromCode($file->idfile), 'attached-file-not-found');
            $this->assertEquals(1, $reloaded->downloads, 'download-counter-not-incremented');
        } finally {
            unset($_GET['myft'], $_GET['embed']);
            $this->assertTrue($file->delete(), 'can-not-delete-attached-file');
        }
    }

    public function testMyfilesControllerIgnoresEmbeddedPreviewDownloads(): void
    {
        $file = $this->createAttachedFile('preview.pdf');
        $_GET['myft'] = $this->getTokenFromUrl($file->url('download-permanent'));
        $_GET['embed'] = 'true';

        try {
            new Myfiles('Myfiles', '/' . $file->path);
            $reloaded = new AttachedFile();
            $this->assertTrue($reloaded->loadFromCode($file->idfile), 'attached-file-not-found');
            $this->assertEquals(0, $reloaded->downloads, 'embed-preview-counted-as-download');
        } finally {
            unset($_GET['myft'], $_GET['embed']);
            $this->assertTrue($file->delete(), 'can-not-delete-attached-file');
        }
    }

    private function createFile(string ...$path): void
    {
        $filePath = Tools::folder(...$path);
        Tools::folderCheckOrCreate(dirname($filePath));
        file_put_contents($filePath, 'test');
    }

    private function createAttachedFile(string $name): AttachedFile
    {
        $name = uniqid('', true) . '_' . $name;
        $this->createFile('MyFiles', $name);

        $file = new AttachedFile();
        $file->path = $name;
        $this->assertTrue($file->save(), 'can-not-save-attached-file');
        return $file;
    }

    private function getTokenFromUrl(string $url): string
    {
        parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $query);
        return $query['myft'] ?? '';
    }
}
