<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Contract\ExportPDFInterface;
use FacturaScripts\Core\Lib\ExportPDF;
use FacturaScripts\Test\Core\Lib\Helper\CollectingExportPDFMod;
use FacturaScripts\Test\Core\Lib\Helper\TestableExportPDF;
use FacturaScripts\Test\Core\Lib\Helper\UnsupportedExportModel;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ExportPDFTest extends TestCase
{
    private string $tempDir;
    private string $cacheDir;

    protected function setUp(): void
    {
        $this->tempDir = FS_FOLDER . '/MyFiles/Tests/ExportPDF';
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0777, true);
        }

        $this->cacheDir = FS_FOLDER . '/MyFiles/Cache';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }

        ExportPDF::clearMods();
        ExportPDF::clearModelExtensions();
    }

    protected function tearDown(): void
    {
        ExportPDF::clearMods();
        ExportPDF::clearModelExtensions();

        if (is_dir($this->tempDir)) {
            foreach (glob($this->tempDir . '/*') as $item) {
                if (is_dir($item)) {
                    $this->removeDir($item);
                    continue;
                }

                unlink($item);
            }
        }
    }

    public function testCreateReturnsNewInstance(): void
    {
        $first = ExportPDF::create();
        $second = ExportPDF::create();

        $this->assertInstanceOf(ExportPDF::class, $first);
        $this->assertInstanceOf(ExportPDF::class, $second);
        $this->assertNotSame($first, $second);
    }

    public function testSettersAffectPdf(): void
    {
        $default = TestableExportPDF::create();
        $default->newPage();
        $defaultWidth = $default->getPdfSetting('pageWidth');

        $export = TestableExportPDF::create();
        $export->setSize('a5');
        $export->newPage();
        $this->assertSame($export, $export->newPage());
        $this->assertNotSame($defaultWidth, $export->getPdfSetting('pageWidth'));

        $export->setOrientation('landscape');
        $this->assertSame('landscape', $export->getDefaultOrientation());

        $export->setLang('en_EN');
        $this->assertSame('en_EN', $export->getCurrentLang());
    }

    public function testDisableHeaderFooter(): void
    {
        $export = TestableExportPDF::create();
        $export->disableHeader();
        $export->disableFooter();
        $this->assertFalse($export->isHeaderActive());
        $this->assertFalse($export->isFooterActive());

        $export->addText('content');
        $this->assertFalse($export->isHeaderActive());
        $this->assertFalse($export->isFooterActive());
    }

    public function testSetComanyStoresIdentifier(): void
    {
        $export = TestableExportPDF::create();
        $export->setComany(7);
        $this->assertSame(7, $export->getCompanyIdValue());
    }

    public function testAddTextAndOutputProducesContent(): void
    {
        $export = TestableExportPDF::create();
        $export->addText('Hello world');

        $this->assertGreaterThan(0, strlen($export->output()));
    }

    public function testAddTableGeneratesContent(): void
    {
        $export = TestableExportPDF::create();
        $export->addTable([
            ['first' => 'a', 'second' => 'b'],
        ]);

        $this->assertGreaterThan(0, strlen($export->output()));
    }

    public function testAddImageRequiresExistingFile(): void
    {
        $this->expectException(RuntimeException::class);
        TestableExportPDF::create()->addImage($this->tempDir . '/missing.png');
    }

    public function testAddImageLoadsFile(): void
    {
        $export = TestableExportPDF::create();
        $export->addImage(FS_FOLDER . '/Test/__files/product_image.jpg');

        $this->assertGreaterThan(0, strlen($export->output()));
    }

    public function testSaveWritesFile(): void
    {
        $export = TestableExportPDF::create();
        $export->addText('Save me');

        $path = $export->save('sample', $this->tempDir);
        $this->assertFileExists($path);
        $this->assertGreaterThan(0, filesize($path));
    }

    public function testAddModelWithExtensionAndExtraSections(): void
    {
        $model = new \stdClass();
        $handled = null;
        $sectionRan = false;

        ExportPDF::addModelExtension(\stdClass::class, function (ExportPDFInterface $export, $model, array &$context) use (&$handled) {
            $handled = $context['data']['custom'] ?? null;
            $export->addText('extension');
            return true;
        });

        $export = TestableExportPDF::create();
        $export->setData('custom', 'value');
        $export->addSection(ExportPDFInterface::SECTION_EXTRA_PAGES, function (ExportPDFInterface $export, array $context) use (&$sectionRan) {
            $sectionRan = true;
            $export->newPage();
            $export->addText('extra');
        });

        $export->addModel($model);

        $this->assertSame('value', $handled);
        $this->assertTrue($sectionRan);
        $this->assertGreaterThan(0, strlen($export->output()));
    }

    public function testAddModelWithUnsupportedTypeThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        TestableExportPDF::create()->addModel(new UnsupportedExportModel());
    }

    public function testModsCustomizeExport(): void
    {
        $calls = [];
        ExportPDF::addMod(new CollectingExportPDFMod($calls));

        ExportPDF::addModelExtension(\stdClass::class, function (ExportPDFInterface $export, $model, array &$context) {
            $export->addText('mod-extension');
            return true;
        });

        $export = TestableExportPDF::create();
        $export->addModel(new \stdClass());

        $this->assertSame(['before', 'after', 'extra'], $calls);
        $this->assertSame('en_GB', $export->getCurrentLang());
        $this->assertSame('landscape', $export->getDefaultOrientation());
    }

    private function removeDir(string $directory): void
    {
        foreach (scandir($directory) as $item) {
            if (in_array($item, ['.', '..'], true)) {
                continue;
            }

            $path = $directory . '/' . $item;
            if (is_dir($path)) {
                $this->removeDir($path);
                continue;
            }

            unlink($path);
        }

        rmdir($directory);
    }
}
