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

use FacturaScripts\Core\Lib\Widget\WidgetLibrary;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\AttachedFile;
use PHPUnit\Framework\TestCase;

final class WidgetLibraryTest extends TestCase
{
    public function testFileNameCanNotBreakOutOfOnclickHandler(): void
    {
        $names = ["',alert(1),'", 'a"b<c>&d.pdf'];
        foreach (['application/pdf', 'image/png'] as $mimetype) {
            foreach ($names as $name) {
                $file = $this->getFile($name, $mimetype);
                $html = $this->getWidget()->renderFileList([$file], null, "w1',alert(2),'");

                preg_match_all('/onclick="([^"]*)"/', $html, $matches);
                $this->assertNotEmpty($matches[1], $mimetype);

                foreach ($matches[1] as $attribute) {
                    // el navegador decodifica las entidades del atributo antes de ejecutar el JavaScript
                    $js = html_entity_decode($attribute, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $this->assertMatchesRegularExpression(
                        '/^widgetLibrarySelect\(("(?:[^"\\\\]|\\\\.)*"), ("(?:[^"\\\\]|\\\\.)*"), ("(?:[^"\\\\]|\\\\.)*")\);$/',
                        $js
                    );
                    $this->assertStringNotContainsString("'", $js);

                    preg_match('/^widgetLibrarySelect\((.*)\);$/', $js, $call);
                    $args = json_decode('[' . $call[1] . ']', true);
                    $shortName = html_entity_decode($file->shortFileName(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $this->assertSame(["w1',alert(2),'", '7', $shortName], $args);
                }
            }
        }
    }

    public function testShortFileNameIsDecodedForDisplay(): void
    {
        $file = $this->getFile("o'brien.pdf", 'application/pdf');
        $html = $this->getWidget()->renderFileList([$file], null, 'w1');

        preg_match('/onclick="([^"]*)"/', $html, $match);
        $js = html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        preg_match('/^widgetLibrarySelect\((.*)\);$/', $js, $call);

        $this->assertSame(['w1', '7', "o'brien.pdf"], json_decode('[' . $call[1] . ']', true));
    }

    private function getFile(string $name, string $mimetype): AttachedFile
    {
        $file = new AttachedFile();
        $file->idfile = 7;
        $file->filename = strtolower(Tools::noHtml($name));
        $file->mimetype = $mimetype;
        $file->path = 'MyFiles/2026/09/7_test.' . ($mimetype === 'image/png' ? 'png' : 'pdf');
        $file->size = 100;
        return $file;
    }

    private function getWidget(): WidgetLibrary
    {
        return new WidgetLibrary([
            'children' => [],
            'fieldname' => 'idfile',
            'type' => 'library',
        ]);
    }
}
