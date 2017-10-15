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
use FacturaScripts\Core\Base\Security\Tools;

/**
 * Description of EditApiKeys
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class EditApiKeys extends ExtendedController\EditController
{
    public function __construct(&$cache, &$i18n, &$miniLog, $className)
    {
        parent::__construct($cache, $i18n, $miniLog, $className);
        $this->modelName = 'FacturaScripts\Core\Model\ApiKeys';
        $this->setTemplate('EditApiKeys');
    }

    public function privateCore(&$response, $user)
    {
        parent::privateCore($response, $user);
        $model = $this->getModel();
        $model->apikey = (!$model->apikey)?Tools::Token(16):$model->apikey;
    }

    public function getPanelFooter()
    {
        $model = $this->getModel();
        return $this->i18n->trans('Fecha de alta: ') . $model->f_alta;
    }

    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'apikey';
        $pagedata['icon'] = 'fa-exchange';
        $pagedata['showonmenu'] = FALSE;

        return $pagedata;
    }
}
