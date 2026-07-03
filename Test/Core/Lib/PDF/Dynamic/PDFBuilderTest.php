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

namespace FacturaScripts\Test\Core\Lib\PDF\Dynamic;

use FacturaScripts\Core\Lib\PDF\Dynamic\Blocks\DualColumnTableBlock;
use FacturaScripts\Core\Lib\PDF\Dynamic\Blocks\RawHtmlBlock;
use FacturaScripts\Core\Lib\PDF\Dynamic\Blocks\SpacerBlock;
use FacturaScripts\Core\Lib\PDF\Dynamic\Blocks\TableBlock;
use FacturaScripts\Core\Lib\PDF\Dynamic\Blocks\TextBlock;
use FacturaScripts\Core\Lib\PDF\Dynamic\Blocks\TitleBlock;
use FacturaScripts\Core\Lib\PDF\Dynamic\PDFBuilder;
use PHPUnit\Framework\TestCase;

final class PDFBuilderTest extends TestCase
{
    public function testTitleBlockEscapesAndSetsLevel(): void
    {
        $html = (new TitleBlock('Hola <b>mundo</b>', 2, 'center'))->render();
        $this->assertSame('<h2 class="title text-center">Hola &lt;b&gt;mundo&lt;/b&gt;</h2>', $html);

        // el nivel se limita a 1..3
        $this->assertStringStartsWith('<h3', (new TitleBlock('x', 9))->render());
        $this->assertStringStartsWith('<h1', (new TitleBlock('x', 0))->render());
    }

    public function testTextBlockEscapesAndKeepsNewLines(): void
    {
        $html = (new TextBlock("line1\nline2", 'font-bold'))->render();
        $this->assertStringContainsString('class="text font-bold"', $html);
        $this->assertStringContainsString("line1<br />\nline2", $html);
    }

    public function testSpacerBlock(): void
    {
        $this->assertSame('<div style="height: 8mm;"></div>', (new SpacerBlock(8))->render());
        $this->assertSame('<div style="height: 0mm;"></div>', (new SpacerBlock(-3))->render());
    }

    public function testTableBlockRendersTitlesRowsAndAlignments(): void
    {
        $html = (new TableBlock(
            [['a', 'b'], ['c', 'd']],
            ['col1', 'col2'],
            ['left', 'right']
        ))->render();

        $this->assertStringContainsString('<table class="table-list">', $html);
        $this->assertStringContainsString('<th class="text-left">col1</th>', $html);
        $this->assertStringContainsString('<th class="text-right">col2</th>', $html);
        $this->assertStringContainsString('<td class="text-left">a</td>', $html);
        $this->assertStringContainsString('<td class="text-right">d</td>', $html);
        $this->assertSame(2, substr_count($html, '<tr>') - 1); // 2 filas de datos + 1 de títulos
    }

    public function testTableBlockWithoutTitlesHasNoHeader(): void
    {
        $html = (new TableBlock([['a']]))->render();
        $this->assertStringNotContainsString('<thead>', $html);
    }

    public function testDualColumnTableBlock(): void
    {
        $html = (new DualColumnTableBlock(['Fecha' => '03-07-2026']))->render();
        $this->assertStringContainsString('<td>Fecha</td>', $html);
        $this->assertStringContainsString('<td>03-07-2026</td>', $html);
    }

    public function testRawHtmlBlockSanitizes(): void
    {
        $html = (new RawHtmlBlock(
            '<p onclick="evil()">ok</p><script>alert(1)</script><a href="javascript:evil()">x</a>'
        ))->render();

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('alert(1)', $html);
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringContainsString('<p>ok</p>', $html);
    }

    public function testBuilderBodySplitsPages(): void
    {
        $body = PDFBuilder::create()
            ->addTitle('page one')
            ->addPageBreak()
            ->addText('page two')
            ->getBodyHtml();

        $this->assertSame(2, substr_count($body, '<div class="page">'));
        $this->assertStringContainsString('page one', $body);
        $this->assertStringContainsString('page two', $body);
    }

    public function testBuilderHtmlDocument(): void
    {
        $html = PDFBuilder::create()
            ->setTitle('my "doc"')
            ->setOrientation(PDFBuilder::ORIENTATION_LANDSCAPE)
            ->addText('hello')
            ->getHtml();

        $this->assertStringStartsWith('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('<title>my &quot;doc&quot;</title>', $html);
        $this->assertStringContainsString('html2pdf.bundle.min.js', $html);
        $this->assertStringContainsString('id="FORPRINT"', $html);
        $this->assertStringContainsString('orientation:"landscape"', str_replace(' ', '', $html));
        $this->assertStringContainsString('function fsPrint()', $html);
        $this->assertStringContainsString('function fsDownloadPdf(', $html);
        // en horizontal, la página mide 257mm de ancho (297 - 2 x 20 de margen)
        $this->assertStringContainsString('width: 257mm', $html);
    }

    public function testCustomBlockRegistry(): void
    {
        PDFBuilder::addCustomBlock('MyBlock', TextBlock::class);
        $body = PDFBuilder::create()
            ->add('MyBlock', 'custom content')
            ->getBodyHtml();

        $this->assertStringContainsString('custom content', $body);
    }
}
