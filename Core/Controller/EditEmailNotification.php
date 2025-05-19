<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Lib\ExtendedController\EditController;

/**
 * Description of EditEmailNotification
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class EditEmailNotification extends EditController
{
    public function getPageData(): array
    {
        $pageData = parent::getPageData();
        $pageData['title'] = 'email-notification';
        $pageData['menu'] = 'admin';
        $pageData['icon'] = 'fa-solid fa-bell';
        return $pageData;
    }

    public function getModelClassName(): string
    {
        return 'EmailNotification';
    }

    /**
     * Create the view to display.
     */
    protected function createViews()
    {
        parent::createViews();

        // desactivamos los botones nuevo, opciones e imprimir
        $viewName = $this->getMainViewName();
        $this->setSettings($viewName, 'btnNew', false);
        $this->setSettings($viewName, 'btnOptions', false);
        $this->setSettings($viewName, 'btnPrint', false);
    }
}
