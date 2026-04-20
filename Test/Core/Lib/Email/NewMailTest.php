<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core\Lib\Email;

use FacturaScripts\Core\Lib\Email\BaseBlock;
use FacturaScripts\Core\Lib\Email\ButtonBlock;
use FacturaScripts\Core\Lib\Email\HtmlBlock;
use FacturaScripts\Core\Lib\Email\NewMail;
use FacturaScripts\Core\Lib\Email\SpaceBlock;
use FacturaScripts\Core\Lib\Email\TextBlock;
use FacturaScripts\Core\Lib\Email\TitleBlock;
use PHPUnit\Framework\TestCase;

final class NewMailTest extends TestCase
{
    public function testCreate(): void
    {
        $mailer = NewMail::create()
            ->to('test@facturascripts.com', 'test name')
            ->subject('test subject')
            ->body('test body');

        $this->assertInstanceOf(NewMail::class, $mailer);

        $this->assertEquals('test subject', $mailer->title);
        $this->assertEquals('test body', $mailer->text);

        $this->assertCount(1, $mailer->getToAddresses());
        $this->assertContains('test@facturascripts.com', $mailer->getToAddresses());

        $this->assertEmpty($mailer->getCcAddresses());
        $this->assertEmpty($mailer->getBccAddresses());
    }

    public function testCC(): void
    {
        $mailer = NewMail::create()
            ->cc('test-cc@facturascripts.com', 'test cc name')
            ->subject('cc subject')
            ->body('cc body');

        $this->assertEmpty($mailer->getToAddresses());
        $this->assertCount(1, $mailer->getCcAddresses());
        $this->assertContains('test-cc@facturascripts.com', $mailer->getCcAddresses());
        $this->assertEmpty($mailer->getBccAddresses());
    }

    public function testBCC(): void
    {
        $mailer = NewMail::create()
            ->bcc('test-bcc@facturascripts.com', 'test bcc name')
            ->subject('bcc subject')
            ->body('bcc body');

        $this->assertEmpty($mailer->getToAddresses());
        $this->assertEmpty($mailer->getCcAddresses());
        $this->assertCount(1, $mailer->getBccAddresses());
    }

    public function testBlocksFromShortcodes(): void
    {
        // 5 shortcodes válidos + 2 inválidos (deben ignorarse sin error)
        $text = '[blockText]Texto de prueba[/blockText]'
            . '[blockTitle type="h2"]Mi título[/blockTitle]'
            . '[blockHtml]<b>negrita</b>[/blockHtml]'
            . '[blockButton label="Reservar" href="https://example.com"]'
            . '[blockSpace height="20"]'
            . '[blockUnknown]este shortcode no existe[/blockUnknown]'
            . '[blockFake]';

        $blocks = NewMail::create()->body($text)->getMainBlocks();

        // solo los 5 bloques válidos deben estar presentes
        $this->assertCount(5, $blocks, 'No se han detectado exactamente 5 bloques cuando se esperaban 5 bloques válidos');
        $this->assertContainsOnlyInstancesOf(BaseBlock::class, $blocks, 'No todos los bloques son instancias de BaseBlock como se esperaba');

        $this->assertInstanceOf(TextBlock::class, $blocks[0], 'El primer bloque no es del tipo TextBlock como se esperaba');
        $this->assertInstanceOf(TitleBlock::class, $blocks[1], 'El segundo bloque no es del tipo TitleBlock como se esperaba');
        $this->assertInstanceOf(HtmlBlock::class, $blocks[2], 'El tercer bloque no es del tipo HtmlBlock como se esperaba');
        $this->assertInstanceOf(ButtonBlock::class, $blocks[3], 'El cuarto bloque no es del tipo ButtonBlock como se esperaba');
        $this->assertInstanceOf(SpaceBlock::class, $blocks[4], 'El quinto bloque no es del tipo SpaceBlock como se esperaba');
    }

    public function testHtmlBeforeBlock(): void
    {
        // html antes de un bloque → HtmlBlock + bloque
        $text = '<a href="https://example.com">mi enlace</a>'
            . '[blockTitle type="h3"]Mi título[/blockTitle]';

        $blocks = NewMail::create()->body($text)->getMainBlocks();

        $this->assertCount(2, $blocks, 'No se han detectado exactamente 2 bloques cuando se esperaba un bloque HTML seguido de un bloque de título');
        $this->assertInstanceOf(HtmlBlock::class, $blocks[0], 'El primer bloque no es del tipo HtmlBlock como se esperaba');
        $this->assertInstanceOf(TitleBlock::class, $blocks[1], 'El segundo bloque no es del tipo TitleBlock como se esperaba');
    }


    public function testPlainTextBlock(): void
    {
        // texto plano sin bloques ni html → un único TextBlock
        $blocks = NewMail::create()->body('Texto plano sin bloques')->getMainBlocks();

        $this->assertCount(1, $blocks, 'Hay más de un bloque cuando solo debería haber uno');
        $this->assertInstanceOf(TextBlock::class, $blocks[0], 'El bloque no es del tipo TextBlock como se esperaba');
    }

    public function testEmptyTextBlocks(): void
    {
        // sin texto → sin bloques
        $blocks = NewMail::create()->getMainBlocks();

        $this->assertEmpty($blocks, 'Hay bloques cuando no debería haber ninguno');
    }
}
