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

namespace FacturaScripts\Test\Core;

use FacturaScripts\Core\Translator;
use PHPUnit\Framework\TestCase;

final class TranslatorTest extends TestCase
{
    public function testDefaultTrans(): void
    {
        $translator = new Translator();
        $this->assertEquals(FS_LANG, $translator->getLang());
    }

    public function testSpanishTranslations(): void
    {
        $translator = new Translator('es_ES');

        // comprobamos que el idioma está en la lista
        $this->assertArrayHasKey('es_ES', $translator->getAvailableLanguages());

        // comprobamos que el idioma está seleccionado
        $this->assertEquals('es_ES', $translator->getLang());

        // comprobamos algunas traducciones
        $this->assertEquals('Aceptar', $translator->trans('accept'));
        $this->assertEquals('Aceptado', $translator->trans('accepted'));
        $this->assertEquals(
            'La cuenta 999 tiene asociada una cuenta padre equivocada.',
            $translator->trans('account-bad-parent', ['%codcuenta%' => '999'])
        );
    }

    public function testEnglishTranslations(): void
    {
        $translator = new Translator('en_EN');

        // comprobamos que el idioma está en la lista
        $this->assertArrayHasKey('en_EN', $translator->getAvailableLanguages());

        // comprobamos que el idioma está seleccionado
        $this->assertEquals('en_EN', $translator->getLang());

        // comprobamos algunas traducciones
        $this->assertEquals('Accept', $translator->trans('accept'));
        $this->assertEquals('Accepted', $translator->trans('accepted'));
        $this->assertEquals(
            'The account 888 has the wrong parent account associated with it.',
            $translator->trans('account-bad-parent', ['%codcuenta%' => '888'])
        );
    }

    public function testMissingTranslations(): void
    {
        $translator = new Translator('es_ES');

        // al traducir una cadena que no existe, se devuelve la misma cadena
        $this->assertEquals('yolo-test-123', $translator->trans('yolo-test-123'));

        // y se añade a la lista de cadenas no traducidas
        $this->assertContains('yolo-test-123', $translator->getMissingStrings());
    }
}