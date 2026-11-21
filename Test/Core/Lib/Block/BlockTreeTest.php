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

namespace FacturaScripts\Test\Core\Lib\Block;

use FacturaScripts\Core\Lib\Block\Block;
use FacturaScripts\Core\Lib\Block\Columns;
use FacturaScripts\Core\Lib\Block\Page;
use FacturaScripts\Core\Lib\Block\Section;
use FacturaScripts\Core\Lib\Block\Tabs;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class BlockTreeTest extends TestCase
{
    public function testAddAfterBeforeAndInto(): void
    {
        $page = $this->page();

        $this->assertTrue($page->addAfter('main', $this->leaf('after-main')));
        $this->assertTrue($page->addBefore('second', $this->leaf('before-second')));
        $this->assertTrue($page->addInto('tabs', $this->leaf('third')));
        $this->assertSame(
            ['page', 'main', 'after-main', 'columns', 'left', 'right', 'tabs', 'first', 'before-second', 'second', 'third'],
            $page->names()
        );

        // si el bloque de referencia no existe, o no es un contenedor, no se añade nada
        $this->assertFalse($page->addAfter('unknown', $this->leaf('lost')));
        $this->assertFalse($page->addBefore('unknown', $this->leaf('lost')));
        $this->assertFalse($page->addInto('main', $this->leaf('lost')));
        $this->assertNull($page->find('lost'));
    }

    public function testColumnsWidth(): void
    {
        $columns = Columns::make('columns')
            ->col(8, $this->leaf('wide'))
            ->col(40, $this->leaf('too-wide'))
            ->add($this->leaf('auto'));

        $this->assertSame(8, $columns->width('wide'));
        $this->assertSame(12, $columns->width('too-wide'));
        $this->assertSame(0, $columns->width('auto'));
    }

    public function testDuplicatedNamesAreRejected(): void
    {
        $page = $this->page();
        $page->assertUniqueNames();

        $page->addInto('tabs', $this->leaf('left'));
        $this->expectException(InvalidArgumentException::class);
        $page->assertUniqueNames();
    }

    public function testFindAndRemove(): void
    {
        $page = $this->page();

        $this->assertSame('second', $page->find('second')->name());
        $this->assertTrue($page->find('tabs')->contains('second'));
        $this->assertFalse($page->find('columns')->contains('second'));

        $this->assertTrue($page->remove('second'));
        $this->assertNull($page->find('second'));
        $this->assertFalse($page->remove('second'));
    }

    public function testInvalidNameIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->leaf('not valid"');
    }

    public function testLazyBlockIsDeferredUnlessTargeted(): void
    {
        $page = $this->page();
        $page->find('right')->setLazy();

        $page->plan('main');
        $this->assertTrue($page->find('right')->isDeferred());
        $this->assertFalse($page->find('left')->isDeferred());

        $page->plan('right');
        $this->assertFalse($page->find('right')->isDeferred());
    }

    public function testLazyTabsDeferAllButTheActiveOne(): void
    {
        $page = $this->page();
        $page->find('tabs')->setLazy();

        // sin un bloque de destino dentro, la pestaña activa es la primera
        $page->plan('main');
        $this->assertFalse($page->find('tabs')->isDeferred());
        $this->assertFalse($page->find('first')->isDeferred());
        $this->assertTrue($page->find('second')->isDeferred());

        // si la petición va dirigida a otra pestaña, esa es la activa
        $page->plan('second');
        $this->assertTrue($page->find('first')->isDeferred());
        $this->assertFalse($page->find('second')->isDeferred());

        // al pedir una pestaña por separado, deja de estar aplazada
        $page->plan('main');
        $page->plan('main', 'second');
        $this->assertFalse($page->find('second')->isDeferred());
    }

    public function testNotLazyTabsLoadEveryTab(): void
    {
        $page = $this->page();

        $page->plan('main');
        $this->assertFalse($page->find('first')->isDeferred());
        $this->assertFalse($page->find('second')->isDeferred());
    }

    public function testVisibilityDependsOnTheRecord(): void
    {
        $page = $this->page();
        $page->find('main')->showWithoutRecord();

        // sin registro solo se muestra el bloque que lo permite, y los contenedores quedan vacíos
        $this->assertTrue($page->find('main')->isVisible(false));
        $this->assertFalse($page->find('left')->isVisible(false));
        $this->assertFalse($page->find('columns')->isVisible(false));
        $this->assertTrue($page->isVisible(false));

        $this->assertTrue($page->find('left')->isVisible(true));
        $this->assertTrue($page->find('columns')->isVisible(true));
    }

    private function leaf(string $name): Block
    {
        return new class ($name) extends Block {
            public function template(): string
            {
                return '';
            }
        };
    }

    private function page(): Page
    {
        $page = new Page();
        $page->add($this->leaf('main'))
            ->add(Columns::make('columns')->col(6, $this->leaf('left'))->col(6, $this->leaf('right')))
            ->add(Tabs::make('tabs')->tab($this->leaf('first'))->tab($this->leaf('second')));

        return $page;
    }
}
