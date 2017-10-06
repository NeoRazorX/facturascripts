<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017  Carlos Garcia Gomez  carlos@facturascripts.com
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
use FacturaScripts\Core\Lib\ModelDataGenerator;

/**
 * Description of Randomizer
 *
 * @author carlos
 */
class Randomizer extends Base\Controller
{
    public function privateCore(&$response, $user)
    {
        parent::privateCore($response, $user);
        
        $ModelDataGenerator = new ModelDataGenerator($this->empresa);
        
        $option = $this->request->get('gen', '');
        switch($option) {
            case 'agentes':
                $num = $ModelDataGenerator->agentes();
                $this->miniLog->info($num.' agentes generados.');
                break;
            
            case 'articulos':
                $num = $ModelDataGenerator->articulos();
                $this->miniLog->info($num.' artículos generados.');
                break;
            
            case 'clientes':
                $num = $ModelDataGenerator->clientes();
                $this->miniLog->info($num.' clientes generados.');
                break;
            
            case 'fabricantes':
                $num = $ModelDataGenerator->fabricantes();
                $this->miniLog->info($num.' fabricantes generados.');
                break;
            
            case 'familias':
                $num = $ModelDataGenerator->familias();
                $this->miniLog->info($num.' familias generadas.');
                break;
            
            case 'grupos':
                $num = $ModelDataGenerator->gruposClientes();
                $this->miniLog->info($num.' grupos de clientes generados.');
                break;
            
            case 'proveedores':
                $num = $ModelDataGenerator->proveedores();
                $this->miniLog->info($num.' proveedores generados.');
                break;
        }
    }
    
    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['menu'] = 'admin';
        $pageData['title'] = 'Generar datos de prueba';
        $pageData['icon'] = 'fa-magic';

        return $pageData;
    }
}
