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

use FacturaScripts\Core\Lib\Widget\GroupItem;
use PHPUnit\Framework\TestCase;

/**
 * Comprueba que un grupo solo pinta su título si tiene alguna columna visible.
 */
final class GroupItemTest extends TestCase
{
    public function testDisabledColumnsHideTitle(): void
    {
        $group = $this->getGroup(['left']);
        $group->columns['column0']->display = 'none';

        $this->assertFalse($group->hasVisibleColumns());
        $this->assertStringNotContainsString('<legend', $group->edit((object)['field0' => 'a']));
    }

    public function testGroupWithoutVisibleColumnsHidesTitle(): void
    {
        $group = $this->getGroup(['none', 'none']);
        $this->assertFalse($group->hasVisibleColumns());

        $html = $group->edit((object)['field0' => 'a', 'field1' => 'b']);
        $this->assertStringNotContainsString('<legend', $html);
        $this->assertStringContainsString('d-none', $html);

        // los campos ocultos se siguen pintando para enviarlos con el formulario
        $this->assertStringContainsString('name="field0"', $html);
        $this->assertStringContainsString('name="field1"', $html);
    }

    public function testGroupWithVisibleColumnsShowsTitle(): void
    {
        $group = $this->getGroup(['left', 'none']);
        $this->assertTrue($group->hasVisibleColumns());

        $html = $group->edit((object)['field0' => 'a', 'field1' => 'b']);
        $this->assertStringContainsString('<legend', $html);
        $this->assertStringNotContainsString('d-none', $html);
    }

    /**
     * Crea un grupo con título y una columna de texto por cada valor de display indicado.
     */
    private function getGroup(array $displays): GroupItem
    {
        $children = [];
        foreach ($displays as $num => $display) {
            $children[] = [
                'tag' => 'column',
                'name' => 'column' . $num,
                'display' => $display,
                'children' => [
                    ['tag' => 'widget', 'type' => 'text', 'fieldname' => 'field' . $num, 'children' => []],
                ],
            ];
        }

        return new GroupItem(['name' => 'test-group', 'title' => 'test-group', 'children' => $children]);
    }
}
