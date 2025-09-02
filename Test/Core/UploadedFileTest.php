<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core;

use FacturaScripts\Core\UploadedFile;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class UploadedFileTest extends TestCase
{
    private $tempDir;
    private $tempFile;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/uploadedfile_test_' . uniqid();
        mkdir($this->tempDir);

        $this->tempFile = $this->tempDir . '/test_file.txt';
        file_put_contents($this->tempFile, 'test content');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }

        $files = glob($this->tempDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }
    }

    public function testConstructorWithArrayData(): void
    {
        $data = [
            'name' => 'test.pdf',
            'type' => 'application/pdf',
            'tmp_name' => '/tmp/phpXXXXXX',
            'error' => UPLOAD_ERR_OK,
            'size' => 1024
        ];

        $file = new UploadedFile($data);

        $this->assertEquals('test.pdf', $file->name);
        $this->assertEquals('application/pdf', $file->type);
        $this->assertEquals('/tmp/phpXXXXXX', $file->tmp_name);
        $this->assertEquals(UPLOAD_ERR_OK, $file->error);
        $this->assertEquals(1024, $file->size);
        $this->assertFalse($file->test);
    }

    public function testConstructorWithNestedArrayData(): void
    {
        $data = [
            'name' => ['test.pdf'],
            'type' => ['application/pdf'],
            'tmp_name' => ['/tmp/phpXXXXXX'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [1024]
        ];

        $file = new UploadedFile($data);

        $this->assertEquals('test.pdf', $file->name);
        $this->assertEquals('application/pdf', $file->type);
        $this->assertEquals('/tmp/phpXXXXXX', $file->tmp_name);
        $this->assertEquals(UPLOAD_ERR_OK, $file->error);
        $this->assertEquals(1024, $file->size);
    }

    public function testConstructorIgnoresInvalidProperties(): void
    {
        $data = [
            'name' => 'test.pdf',
            'invalid_property' => 'should be ignored',
            'another_invalid' => 123
        ];

        $file = new UploadedFile($data);

        $this->assertEquals('test.pdf', $file->name);
        $this->assertFalse(property_exists($file, 'invalid_property'));
        $this->assertFalse(property_exists($file, 'another_invalid'));
    }

    public function testExtensionMethod(): void
    {
        $testCases = [
            'document.pdf' => 'pdf',
            'image.jpeg' => 'jpeg',
            'archive.tar.gz' => 'gz',
            'noextension' => '',
            '.hidden' => 'hidden'
        ];

        foreach ($testCases as $filename => $expectedExtension) {
            $file = new UploadedFile(['name' => $filename]);
            $this->assertEquals($expectedExtension, $file->extension());
        }
    }

    public function testGetClientOriginalName(): void
    {
        $file = new UploadedFile(['name' => 'original_document.pdf']);
        $this->assertEquals('original_document.pdf', $file->getClientOriginalName());
    }

    public function testGetErrorMessage(): void
    {
        $errorCases = [
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
            999 => 'Unknown upload error.'
        ];

        foreach ($errorCases as $errorCode => $expectedMessage) {
            $file = new UploadedFile(['error' => $errorCode]);
            $this->assertEquals($expectedMessage, $file->getErrorMessage());
        }
    }

    public function testGetMaxFilesize(): void
    {
        $maxFilesize = UploadedFile::getMaxFilesize();

        $this->assertIsInt($maxFilesize);
        $this->assertGreaterThan(0, $maxFilesize);
    }

    public function testGetClientMimeType(): void
    {
        $file = new UploadedFile(['tmp_name' => $this->tempFile]);
        $mimeType = $file->getClientMimeType();

        $this->assertStringContainsString('text/', $mimeType);
    }

    public function testGetMimeType(): void
    {
        $file = new UploadedFile(['tmp_name' => $this->tempFile]);
        $mimeType = $file->getMimeType();

        $this->assertStringContainsString('text/', $mimeType);
    }

    public function testGetPathname(): void
    {
        $file = new UploadedFile(['tmp_name' => '/tmp/test.txt']);
        $this->assertEquals('/tmp/test.txt', $file->getPathname());
    }

    public function testGetRealPath(): void
    {
        $file = new UploadedFile(['tmp_name' => '/tmp/test.txt']);
        $this->assertEquals('/tmp/test.txt', $file->getRealPath());
    }

    public function testGetSize(): void
    {
        $file = new UploadedFile(['size' => 2048]);
        $this->assertEquals(2048, $file->getSize());
    }

    public function testIsUploadedWithoutTestMode(): void
    {
        $file = new UploadedFile([
            'tmp_name' => '/tmp/non_uploaded_file.txt',
            'test' => false
        ]);

        $this->assertFalse($file->isUploaded());
    }

    public function testIsUploadedWithTestMode(): void
    {
        $file = new UploadedFile([
            'tmp_name' => $this->tempFile,
            'test' => true
        ]);

        $this->assertTrue($file->isUploaded());
    }

    public function testIsValidWithValidFile(): void
    {
        $file = new UploadedFile([
            'error' => UPLOAD_ERR_OK,
            'tmp_name' => $this->tempFile,
            'test' => true
        ]);

        $this->assertTrue($file->isValid());
    }

    public function testIsValidWithError(): void
    {
        $file = new UploadedFile([
            'error' => UPLOAD_ERR_NO_FILE,
            'tmp_name' => $this->tempFile,
            'test' => true
        ]);

        $this->assertFalse($file->isValid());
    }

    public function testIsValidWithNonUploadedFile(): void
    {
        $file = new UploadedFile([
            'error' => UPLOAD_ERR_OK,
            'tmp_name' => '/tmp/non_uploaded.txt',
            'test' => false
        ]);

        $this->assertFalse($file->isValid());
    }

    public function testMoveWithValidFileInTestMode(): void
    {
        $file = new UploadedFile([
            'error' => UPLOAD_ERR_OK,
            'tmp_name' => $this->tempFile,
            'test' => true
        ]);

        $destName = 'moved_file.txt';
        $result = $file->move($this->tempDir, $destName);

        $this->assertTrue($result);
        $this->assertFileExists($this->tempDir . '/' . $destName);
        $this->assertFileDoesNotExist($this->tempFile);
    }

    public function testMoveAddsDirectorySeparator(): void
    {
        file_put_contents($this->tempFile, 'test content');

        $file = new UploadedFile([
            'error' => UPLOAD_ERR_OK,
            'tmp_name' => $this->tempFile,
            'test' => true
        ]);

        $destName = 'moved_file.txt';
        $destinyWithoutSeparator = rtrim($this->tempDir, DIRECTORY_SEPARATOR);
        $result = $file->move($destinyWithoutSeparator, $destName);

        $this->assertTrue($result);
        $this->assertFileExists($this->tempDir . '/' . $destName);
    }

    public function testMoveWithInvalidFile(): void
    {
        $file = new UploadedFile([
            'error' => UPLOAD_ERR_NO_FILE,
            'tmp_name' => $this->tempFile,
            'test' => true
        ]);

        $result = $file->move($this->tempDir, 'moved_file.txt');

        $this->assertFalse($result);
    }

    public function testMoveToWithValidFileInTestMode(): void
    {
        $file = new UploadedFile([
            'error' => UPLOAD_ERR_OK,
            'tmp_name' => $this->tempFile,
            'test' => true
        ]);

        $targetPath = $this->tempDir . '/moved_file.txt';
        $result = $file->moveTo($targetPath);

        $this->assertTrue($result);
        $this->assertFileExists($targetPath);
        $this->assertFileDoesNotExist($this->tempFile);
    }

    public function testMoveToWithInvalidFile(): void
    {
        $file = new UploadedFile([
            'error' => UPLOAD_ERR_NO_FILE,
            'tmp_name' => $this->tempFile,
            'test' => true
        ]);

        $targetPath = $this->tempDir . '/moved_file.txt';
        $result = $file->moveTo($targetPath);

        $this->assertFalse($result);
        $this->assertFileDoesNotExist($targetPath);
    }

    public function testParseFilesizeDataProvider(): array
    {
        return [
            ['1024', 1024],
            ['2k', 2048],
            ['2K', 2048],
            ['3m', 3145728],
            ['3M', 3145728],
            ['1g', 1073741824],
            ['1G', 1073741824],
            ['1t', 1099511627776],
            ['1T', 1099511627776],
            ['0x100', 256],
            ['0100', 64],
            ['+500', 500],
            ['', 0]
        ];
    }

    /**
     * @dataProvider testParseFilesizeDataProvider
     */
    public function testParseFilesize(string $input, int $expected): void
    {
        $reflection = new ReflectionClass(UploadedFile::class);
        $method = $reflection->getMethod('parseFilesize');
        $method->setAccessible(true);

        $result = $method->invoke(null, $input);
        $this->assertEquals($expected, $result);
    }
}
