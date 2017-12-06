<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Model\Ejercicio;

/**
 * Description of AccountingReports
 *
 * @author Carlos García Gómez
 */
class AccountingReports extends Controller
{

    /**
     *
     * @var Ejercicio[] 
     */
    public $ejercicios;

    public function privateCore(&$response, $user)
    {
        parent::privateCore($response, $user);

        $ejercicioModel = new Ejercicio();
        $this->ejercicios = $ejercicioModel->all([], ['fechainicio' => 'DESC']);

        $action = $this->request->get('action', '');
        $this->execAction($action);
    }

    private function execAction($action)
    {
        switch ($action) {
            case 'libro-mayor':
                $this->setTemplate(false);
                /// TODO: generate libro mayor from data form
                break;
        }
    }

    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['menu'] = 'reports';
        $pageData['title'] = 'accounting-reports';
        $pageData['icon'] = 'fa-balance-scale';

        return $pageData;
    }
}
