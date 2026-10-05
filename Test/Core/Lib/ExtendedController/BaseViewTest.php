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

namespace FacturaScripts\Test\Core\Lib\ExtendedController;

use FacturaScripts\Core\Lib\ExtendedController\EditView;
use FacturaScripts\Core\Lib\ExtendedController\ListView;
use FacturaScripts\Core\Lib\Widget\VisualItemLoadEngine;
use FacturaScripts\Core\Model\PageOption;
use FacturaScripts\Core\Model\Pais;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Core\Where;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

class BaseViewTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

    const VIEW_NAME = 'ListPais';

    /** @var User[] */
    private $users = [];

    public function testBucleInfinitoLimitCero(): void
    {
        $base_view = new EditView('test', 'test', 'test', 'test');

        $base_view->settings['itemLimit'] = 0;

        $this->assertEmpty($base_view->getPagination());
    }

    public function testLoadPageOptionsPrefiereLaDelUsuario(): void
    {
        $user = $this->createUser();
        $this->createPageOption(null, 'general-title');
        $this->createPageOption($user->nick, 'user-title');

        // la personalización del usuario gana a la general en todos los motores
        $view = new ListView(self::VIEW_NAME, 'countries', Pais::class, 'fa-solid fa-globe');
        $view->loadPageOptions($user);
        $this->assertTrue($view->settings['customized']);
        $this->assertSame('user-title', $view->columnForName('code')->title);

        // sin usuario se aplica la general
        $view = new ListView(self::VIEW_NAME, 'countries', Pais::class, 'fa-solid fa-globe');
        $view->loadPageOptions();
        $this->assertSame('general-title', $view->columnForName('code')->title);
    }

    public function testLoadPageOptionsUsaLaGeneralSiNoHayDelUsuario(): void
    {
        $user = $this->createUser();
        $other = $this->createUser();
        $this->createPageOption(null, 'general-title');
        $this->createPageOption($other->nick, 'other-title');

        // la personalización de otro usuario no se aplica
        $view = new ListView(self::VIEW_NAME, 'countries', Pais::class, 'fa-solid fa-globe');
        $view->loadPageOptions($user);
        $this->assertTrue($view->settings['customized']);
        $this->assertSame('general-title', $view->columnForName('code')->title);
    }

    protected function setUp(): void
    {
        $this->deletePageOptions();
    }

    protected function tearDown(): void
    {
        $this->deletePageOptions();
        foreach ($this->users as $user) {
            $user->delete();
        }
        $this->users = [];

        $this->logErrors();
    }

    private function createPageOption(?string $nick, string $title): void
    {
        $pageOption = new PageOption();
        VisualItemLoadEngine::installXML(self::VIEW_NAME, $pageOption);
        $pageOption->nick = $nick;
        foreach ($pageOption->columns as &$column) {
            if ($column['tag'] === 'column' && $column['name'] === 'code') {
                $column['title'] = $title;
            }
        }
        unset($column);

        $this->assertTrue($pageOption->save(), 'Error saving PageOption');
    }

    private function createUser(): User
    {
        $user = $this->getRandomUser();
        $this->assertTrue($user->save(), 'Error saving User');
        $this->users[] = $user;
        return $user;
    }

    private function deletePageOptions(): void
    {
        foreach (PageOption::all([Where::eq('name', self::VIEW_NAME)]) as $pageOption) {
            $pageOption->delete();
        }
    }
}
