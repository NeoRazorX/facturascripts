<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Model;

/**
 * Description of Wizard
 *
 * @author Carlos García Gómez
 */
class Wizard extends Controller
{

    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['menu'] = 'admin';
        $pageData['showonmenu'] = false;

        return $pageData;
    }

    public function getPaises()
    {
        $paises = [];

        $paisModel = new Model\Pais();
        foreach ($paisModel->all([], ['nombre' => 'ASC'], 0, 500) as $pais) {
            $paises[$pais->codpais] = $pais->nombre;
        }

        return $paises;
    }
    
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        
        $codpais = $this->request->request->get('codpais','');
        if($codpais !== '') {
            $appSettings = new AppSettings();
            $appSettings->set('default', 'codpais', $codpais);
            $appSettings->save();
            $this->initModels();
            
            /// redir to EditSettings
            $this->response->headers->set('Refresh', '1; index.php?page=EditSettings');
        }
    }
    
    private function initModels()
    {
        new Model\Divisa();
        new Model\Empresa();
        new Model\Almacen();
        new Model\FormaPago();
        new Model\Impuesto();
    }
}
