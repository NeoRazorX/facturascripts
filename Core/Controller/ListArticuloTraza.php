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

use FacturaScripts\Core\Base\ExtendedController;
use FacturaScripts\Core\Model\ArticuloTraza;

/**
 * Description of ListArticuloTraza
 *
 * @author PC REDNET S.L. <luismi@pcrednet.com>
 */
class ListArticuloTraza extends ExtendedController\ListController
{

    /**
     * Load views
     */
    protected function createViews()
    {
        /* Artículos */
        $this->addView(ArticuloTraza::class, 'ListArticuloTraza', 'traceability');
        $this->addSearchFields('ListArticuloTraza', ['referencia', 'numserie']);

        $this->addOrderBy('ListArticuloTraza', 'referencia', 'reference');
        $this->addOrderBy('ListArticuloTraza', 'numserie', 'serial-number');

    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'traceability';
        $pagedata['icon'] = 'fa-barcode';
        $pagedata['menu'] = 'warehouse';

        return $pagedata;
    }
}
