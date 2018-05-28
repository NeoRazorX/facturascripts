<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Lib\ExtendedController;

/**
 * Controller to list the items in the LogMessage model
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class ListLogMessage extends ExtendedController\ListController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'logs';
        $pagedata['icon'] = 'fa-file-text-o';
        $pagedata['menu'] = 'admin';
        $pagedata['submenu'] = 'control-panel';

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->addView('ListLogMessage', 'LogMessage');
        $this->addSearchFields('ListLogMessage', ['level', 'message']);

        $this->addOrderBy('ListLogMessage', 'time', 'date', 2);
        $this->addOrderBy('ListLogMessage', 'level', 'level');

        $values = $this->codeModel->all('logs', 'level', 'level');
        $this->addFilterSelect('ListLogMessage', 'level', 'level', 'level', $values);
        $this->addFilterAutocomplete('ListLogMessage', 'nick', 'user', 'nick', 'users');
        $this->addFilterAutocomplete('ListLogMessage', 'ip', 'ip', 'ip', 'logs');
        $this->addFilterDatePicker('ListLogMessage', 'time', 'date', 'time');
    }
}
