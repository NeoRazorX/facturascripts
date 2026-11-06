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

namespace FacturaScripts\Test\Core\Lib\Widget;

use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Lib\Widget\WidgetFile;
use FacturaScripts\Core\Request;
use PHPUnit\Framework\TestCase;

final class WidgetFileTest extends TestCase
{
    /** @var string[] */
    private $created = [];

    protected function setUp(): void
    {
        MiniLog::clear();
    }

    protected function tearDown(): void
    {
        foreach ($this->created as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }
        MiniLog::clear();
    }

    public function testAcceptIsEscaped(): void
    {
        $widget = new WidgetFile(['children' => [], 'fieldname' => 'test', 'type' => 'file', 'accept' => '.pdf" onclick="alert(1)']);
        $html = $widget->edit((object)['test' => '']);

        $this->assertStringContainsString('accept=".pdf&quot; onclick=&quot;alert(1)"', $html);
        $this->assertStringNotContainsString('onclick="alert(1)"', $html);
    }

    public function testBlockedExtensionIsRejected(): void
    {
        $widget = new WidgetFile(['children' => [], 'fieldname' => 'test', 'type' => 'file']);
        $request = new Request(['files' => [
            'test' => ['name' => 'shell.php', 'type' => 'text/plain', 'tmp_name' => $this->tempFile(), 'error' => UPLOAD_ERR_OK, 'size' => 4],
        ]]);
        $request->file('test')->test = true;
        $this->created[] = FS_FOLDER . '/MyFiles/shell.php';

        $model = (object)['test' => 'old.pdf'];
        $widget->processFormData($model, $request);

        $this->assertSame('old.pdf', $model->test);
        $this->assertNotEmpty(MiniLog::read('', ['error']));
    }

    public function testMultipleFilesAreIgnored(): void
    {
        $widget = new WidgetFile(['children' => [], 'fieldname' => 'test', 'type' => 'file', 'multiple' => 'true']);
        $request = new Request(['files' => [
            'test' => [
                'name' => ['a.pdf', 'b.pdf'],
                'type' => ['application/pdf', 'application/pdf'],
                'tmp_name' => [$this->tempFile(), $this->tempFile()],
                'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
                'size' => [10, 10],
            ],
        ]]);

        // antes provocaba un error fatal al llamar a isValid() sobre un array
        $model = (object)['test' => 'old.pdf'];
        $widget->processFormData($model, $request);

        $this->assertSame('old.pdf', $model->test);
        $this->assertEmpty(MiniLog::read('', ['error']));
    }

    public function testMultipleWithoutFilesLogsNothing(): void
    {
        $widget = new WidgetFile(['children' => [], 'fieldname' => 'test', 'type' => 'file', 'multiple' => 'true']);
        $request = new Request(['files' => [
            'test' => ['name' => [''], 'type' => [''], 'tmp_name' => [''], 'error' => [UPLOAD_ERR_NO_FILE], 'size' => [0]],
        ]]);

        $model = (object)['test' => 'old.pdf'];
        $widget->processFormData($model, $request);

        $this->assertSame('old.pdf', $model->test);
        $this->assertEmpty(MiniLog::read('', ['error']));
    }

    public function testSingleFileIsMovedToMyFiles(): void
    {
        $name = 'widget-file-test-' . uniqid() . '.txt';
        $widget = new WidgetFile(['children' => [], 'fieldname' => 'test', 'type' => 'file']);
        $request = new Request(['files' => [
            'test' => ['name' => $name, 'type' => 'text/plain', 'tmp_name' => $this->tempFile(), 'error' => UPLOAD_ERR_OK, 'size' => 4],
        ]]);
        $request->file('test')->test = true;
        $this->created[] = FS_FOLDER . '/MyFiles/' . $name;

        $model = (object)['test' => ''];
        $widget->processFormData($model, $request);

        $this->assertSame($name, $model->test);
        $this->assertFileExists(FS_FOLDER . '/MyFiles/' . $name);
    }

    private function tempFile(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'wft');
        file_put_contents($path, 'test');
        $this->created[] = $path;
        return $path;
    }
}
