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

namespace FacturaScripts\Test\Core\Lib;

use FacturaScripts\Core\Lib\OpenAi;
use FacturaScripts\Core\Tools;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class OpenAiTest extends TestCase
{
    public function testImageDefaults(): void
    {
        $method = new ReflectionMethod(OpenAi::class, 'image');
        $parameters = $method->getParameters();

        $this->assertSame('gpt-image-2', $parameters[4]->getDefaultValue());
        $this->assertSame(180, OpenAi::IMAGE_TIMEOUT);
    }

    public function testImageRequestData(): void
    {
        $openAi = new OpenAi('test-key');
        $method = $this->getPrivateMethod('getImageRequestData');
        $resize = false;
        $options = [
            'content_moderation' => 'low',
            'output_compression' => 50,
            'output_format' => 'WEBP',
            'stream' => true
        ];
        $args = ['prompt', 2048, 1152, 'gpt-image-2', $options, &$resize];
        $data = $method->invokeArgs($openAi, $args);

        $this->assertSame(1, $data['n']);
        $this->assertSame('2048x1152', $data['size']);
        $this->assertSame('webp', $data['output_format']);
        $this->assertSame(50, $data['output_compression']);
        $this->assertSame('low', $data['moderation']);
        $this->assertArrayNotHasKey('content_moderation', $data);
        $this->assertArrayNotHasKey('stream', $data);
        $this->assertFalse($resize);
    }

    public function testImageSizeFallback(): void
    {
        $openAi = new OpenAi('test-key');
        $method = $this->getPrivateMethod('getImageSize');
        $resize = false;
        $args = [&$resize, 800, 600, 'gpt-image-2'];

        $this->assertSame('1536x1024', $method->invokeArgs($openAi, $args));
        $this->assertTrue($resize);
    }

    public function testWebpResize(): void
    {
        if (!function_exists('imagewebp')) {
            $this->markTestSkipped('GD WebP support is not installed.');
        }

        $myFiles = Tools::folder('MyFiles');
        $folderCreated = !is_dir($myFiles);
        if ($folderCreated) {
            mkdir($myFiles);
        }

        $sourceName = 'openai_test_' . uniqid() . '.webp';
        $sourcePath = $myFiles . DIRECTORY_SEPARATOR . $sourceName;
        $source = imagecreatetruecolor(64, 64);
        imagewebp($source, $sourcePath);

        $resultPath = '';
        try {
            $method = $this->getPrivateMethod('imageResize');
            $resultPath = $method->invoke(new OpenAi('test-key'), $sourcePath, 32, 32);

            $this->assertNotEmpty($resultPath);
            $this->assertFileExists(FS_FOLDER . DIRECTORY_SEPARATOR . $resultPath);
        } finally {
            if (file_exists($sourcePath)) {
                unlink($sourcePath);
            }
            if ($resultPath && file_exists(FS_FOLDER . DIRECTORY_SEPARATOR . $resultPath)) {
                unlink(FS_FOLDER . DIRECTORY_SEPARATOR . $resultPath);
            }
            if ($folderCreated && is_dir($myFiles)) {
                rmdir($myFiles);
            }
        }
    }

    private function getPrivateMethod(string $name): ReflectionMethod
    {
        $method = new ReflectionMethod(OpenAi::class, $name);
        $method->setAccessible(true);
        return $method;
    }
}
