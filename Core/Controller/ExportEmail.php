<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base;

/**
 * Controller to export and send via email a model/page
 *
 * @author Ángel Guzmán Maeso <angel@guzmanmaeso.com>
 */
class ExportEmail extends Base\Controller
{
    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'send-via-email';
        $pagedata['icon'] = 'fa-envelope-o';
        $pagedata['menu'] = 'admin';

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $className = $this->getClassName();
        $this->addView('\FacturaScripts\Dinamic\Model\ExportEmail', $className);
        $this->addSearchFields($className, ['email', 'BCC', 'subject', 'text' , 'files', 'send']);

        $this->addOrderBy($className, 'codagente', 'code');
        $this->addOrderBy($className, 'nombre||apellidos', 'name');
        $this->addOrderBy($className, 'provincia', 'province');

        $this->addFilterCheckbox($className, 'f_baja', 'suspended', 'f_baja', true, null);
    }
}
