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

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Lib\ExtendedController\EditController;

class EditWorkEvent extends EditController
{
    public function getModelClassName(): string
    {
        return "WorkEvent";
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data["menu"] = "admin";
        $data["title"] = "WorkEvent";
        $data["icon"] = "fa-solid fa-search";
        return $data;
    }

    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        // desactivamos el botón nuevo
        $mvn = $this->getMainViewName();
        $this->setSettings($mvn, 'btnNew', false);
    }
}
