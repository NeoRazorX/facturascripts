<?php
/*
 * Copyright (C) 2017 Joe Nilson <joenilson at gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
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

/**
 * Description of EditPageOption
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class EditPageOption extends ExtendedController\EditController
{
    /**
     * Devuelve el nombre del modelo
     */
    public function getModelName()
    {
        return 'FacturaScripts\Core\Model\PageOption';
    }
    
    public function getPageData()
    {
        parent::getPageData();
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'page-option';
        $pagedata['icon'] = 'fa-cogs';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }
}
