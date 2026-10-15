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

use FacturaScripts\Core\Lib\Widget\RowActions;
use PHPUnit\Framework\TestCase;

final class RowActionsTest extends TestCase
{
    public function testHasButtonBuscaEntreSueltosYGrupos(): void
    {
        $row = new RowActions(['children' => []]);
        $this->assertFalse($row->hasButton('test'));

        $row->addButton(['action' => 'loose']);
        $row->addButton(['action' => 'grouped', 'group' => 'doc-actions']);

        $this->assertTrue($row->hasButton('loose'));
        $this->assertTrue($row->hasButton('grouped'));
        $this->assertFalse($row->hasButton('missing'));
    }

    public function testHasButtonConGrupoSoloBuscaEnEseGrupo(): void
    {
        $row = new RowActions(['children' => []]);
        $row->addButton(['action' => 'loose']);
        $row->addButton(['action' => 'one', 'group' => 'group-a']);
        $row->addButton(['action' => 'two', 'group' => 'group-b']);

        $this->assertTrue($row->hasButton('one', 'group-a'));
        $this->assertFalse($row->hasButton('one', 'group-b'));
        $this->assertTrue($row->hasButton('two', 'group-b'));

        // un botón suelto no pertenece a ningún grupo
        $this->assertFalse($row->hasButton('loose', 'group-a'));

        // un grupo que no existe no contiene nada
        $this->assertFalse($row->hasButton('one', 'missing'));
    }

    public function testHasButtonGroup(): void
    {
        $row = new RowActions(['children' => []]);
        $this->assertFalse($row->hasButtonGroup('doc-actions'));

        // un botón suelto con el mismo nombre no cuenta como grupo
        $row->addButton(['action' => 'doc-actions']);
        $this->assertFalse($row->hasButtonGroup('doc-actions'));

        $row->addButtonGroup(['name' => 'doc-actions', 'label' => 'documents']);
        $this->assertTrue($row->hasButtonGroup('doc-actions'));
        $this->assertFalse($row->hasButtonGroup('other'));
    }

    public function testHasButtonGroupDetectaGrupoCreadoPorAddButton(): void
    {
        $row = new RowActions(['children' => []]);
        $row->addButton(['action' => 'test', 'group' => 'auto-group']);

        $this->assertTrue($row->hasButtonGroup('auto-group'));
    }
}
