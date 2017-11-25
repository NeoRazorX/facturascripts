<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017  Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\ExtendedController;
use FacturaScripts\Core\Model\DashboardCard;

/**
 * Controller to edit a single item from the DashboardCard model
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class EditDashboardCard extends ExtendedController\EditController
{

    /**
     * Returns the model name
     */
    public function getModelName()
    {
        return DashboardCard::class;
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'dashboard-card';
        $pagedata['menu'] = 'reports';
        $pagedata['icon'] = 'fa-dashboard';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }
}
