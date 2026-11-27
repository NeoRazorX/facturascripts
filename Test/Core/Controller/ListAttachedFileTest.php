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

use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Controller\ListAttachedFile;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\AttachedFile;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class ListAttachedFileTest extends TestCase
{
    use LogErrorsTrait;

    public function testDownloadRequiresExportPermission(): void
    {
        $file = $this->createFile();

        try {
            $controller = new TestableListAttachedFile('ListAttachedFile', '/ListAttachedFile');
            $controller->permissions = new ControllerPermissions();
            $controller->permissions->set(true, 1, false, false);
            $controller->permissions->allowExport = false;
            $controller->request = new Request(['request' => ['codes' => [$file->idfile]]]);

            // sin permiso de exportación no se descarga nada
            $this->assertTrue($controller->runDownload());
            $this->assertContains('no-print-permission', array_column(MiniLog::read(), 'original'));
        } finally {
            $file->delete();
        }
    }

    public function testZipContainsOnlySelectedFiles(): void
    {
        $file1 = $this->createFile();
        $file2 = $this->createFile();
        $controller = new TestableListAttachedFile('ListAttachedFile', '/ListAttachedFile');

        try {
            // dos descargas no comparten zip ni mezclan sus archivos
            $zip1 = $controller->runCreateZip([$file1->idfile]);
            $zip2 = $controller->runCreateZip([$file2->idfile]);
            $this->assertNotNull($zip1);
            $this->assertNotNull($zip2);
            $this->assertNotSame($zip1, $zip2);
            $this->assertSame([$file1->idfile . '_' . $file1->filename], $this->zipNames($zip1));
            $this->assertSame([$file2->idfile . '_' . $file2->filename], $this->zipNames($zip2));
            unlink($zip1);
            unlink($zip2);
        } finally {
            $file1->delete();
            $file2->delete();
        }
    }

    public function testZipWithoutValidFilesIsNotCreated(): void
    {
        // registro cuyo path apunta fuera de MyFiles
        $file = $this->createFile();
        $outsideName = 'attached_outside_' . Tools::randomString(6) . '.txt';
        $outsidePath = FS_FOLDER . '/' . $outsideName;
        $this->assertNotFalse(file_put_contents($outsidePath, 'secret'));
        AttachedFile::table()->whereEq('idfile', $file->idfile)->update(['path' => $outsideName]);

        try {
            $controller = new TestableListAttachedFile('ListAttachedFile', '/ListAttachedFile');
            $before = glob(Tools::folder('MyFiles', 'Tmp') . '/attached-files-*.zip');

            // ni el archivo externo ni un código inexistente generan un zip
            $this->assertNull($controller->runCreateZip([$file->idfile, 999999999]));
            $this->assertContains('no-data', array_column(MiniLog::read(), 'original'));
            $this->assertSame($before, glob(Tools::folder('MyFiles', 'Tmp') . '/attached-files-*.zip'));
        } finally {
            // el modelo conserva el path original, así que elimina su archivo
            $file->delete();
            unlink($outsidePath);
            MiniLog::clear();
        }
    }

    protected function setUp(): void
    {
        MiniLog::clear();
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }

    private function createFile(): AttachedFile
    {
        $name = 'attached_' . Tools::randomString(6) . '.jpg';
        $this->assertTrue(copy(FS_FOLDER . '/Test/__files/product_image.jpg', FS_FOLDER . '/MyFiles/' . $name));

        $model = new AttachedFile();
        $model->path = $name;
        $this->assertTrue($model->save(), 'can-not-save-file');
        return $model;
    }

    private function zipNames(string $path): array
    {
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path));

        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names[] = $zip->getNameIndex($i);
        }
        $zip->close();
        return $names;
    }
}

final class TestableListAttachedFile extends ListAttachedFile
{
    public function runCreateZip(array $codes): ?string
    {
        return $this->createZip(new AttachedFile(), $codes);
    }

    public function runDownload(): bool
    {
        return $this->downloadAction();
    }
}
