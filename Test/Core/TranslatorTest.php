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

use FacturaScripts\Core\Tools;
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

        // obtenemos algunas traducciones
        $accept = $translator->trans('accept');
        $accepted = $translator->trans('accepted');
        $accountBadParent999 = $translator->trans('account-bad-parent', ['%codcuenta%' => '999']);

        // leemos las traducciones del archivo de idioma
        $file = Tools::folder('Core', 'Translation', 'es_ES.json');
        $data = file_get_contents($file);
        $json = json_decode($data, true);

        // comprobamos que las traducciones son correctas
        $this->assertEquals($json['accept'], $accept);
        $this->assertEquals($json['accepted'], $accepted);
        $this->assertEquals(
            str_replace('%codcuenta%', '999', $json['account-bad-parent']),
            $accountBadParent999
        );
    }

    public function testEnglishTranslations(): void
    {
        $translator = new Translator('en_EN');

        // comprobamos que el idioma está en la lista
        $this->assertArrayHasKey('en_EN', $translator->getAvailableLanguages());

        // comprobamos que el idioma está seleccionado
        $this->assertEquals('en_EN', $translator->getLang());

        // obtenemos algunas traducciones
        $accept = $translator->trans('accept');
        $cancel = $translator->trans('cancel');
        $accountBadParent777 = $translator->trans('account-bad-parent', ['%codcuenta%' => '777']);

        // leemos las traducciones del archivo de idioma
        $file = Tools::folder('Core', 'Translation', 'en_EN.json');
        $data = file_get_contents($file);
        $json = json_decode($data, true);

        // comprobamos que las traducciones son correctas
        $this->assertEquals($json['accept'], $accept);
        $this->assertEquals($json['cancel'], $cancel);
        $this->assertEquals(
            str_replace('%codcuenta%', '777', $json['account-bad-parent']),
            $accountBadParent777
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

    public function testFolderDinamic(): void
    {
        // obtenemos la traducción de accounting
        $translator = new Translator('es_ES');
        $accounting = $translator->trans('accounting');

        // reconstruimos las traducciones de dinamic
        Translator::deploy();

        // Cargamos el archivo de traducciones
        $file = Tools::folder('Dinamic', 'Translation', 'es_ES.json');
        $data = file_get_contents($file);
        $json = json_decode($data, true);

        // comprobamos que la traducción de accounting es la misma
        $this->assertEquals($accounting, $json['accounting']);

        // cambiamos la traducción de accounting
        $json['accounting'] = 'Contabilidad - test dinamic';
        file_put_contents($file, json_encode($json));

        // recargamos las traducciones
        Translator::reload();

        // comprobamos que la traducción ha cambiado
        $this->assertEquals('Contabilidad - test dinamic', $translator->trans('accounting'));

        // reconstruimos las traducciones de dinamic
        Translator::deploy();
    }

    public function testFolderMyFiles(): void
    {
        // creamos la carpeta MyFiles/Translation
        Tools::folderCheckOrCreate('MyFiles/Translation');

        // creamos el archivo de traducciones
        $file = Tools::folder('MyFiles', 'Translation', 'es_ES.json');
        $json = ['accounting' => 'Contabilidad - test myfiles'];
        file_put_contents($file, json_encode($json));

        // recargamos las traducciones
        Translator::reload();

        // comprobamos que la traducción ha cambiado
        $translator = new Translator('es_ES');
        $this->assertEquals('Contabilidad - test myfiles', $translator->trans('accounting'));

        // eliminamos el archivo de traducciones
        unlink($file);
    }
}
