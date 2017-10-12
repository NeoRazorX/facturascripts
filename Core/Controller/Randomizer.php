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
        $i18n = new Base\Translator();
        $ModelDataGenerator = new ModelDataGenerator($this->empresa);
        
        $option = $this->request->get('gen', '');
        switch($option) {
            case 'agentes':
                $num = $ModelDataGenerator->agentes();
                $this->miniLog->info($num . $i18n->trans('generated-agents'));
                break;
            
            case 'albaranescli':
                $num = $ModelDataGenerator->albaranesCliente();
                $this->miniLog->info($num . $i18n->trans('generated-customer-delivery-notes'));
                break;
            
            case 'albaranesprov':
                $num = $ModelDataGenerator->albaranesProveedor();
                $this->miniLog->info($num . $i18n->trans('generated-supplier-delivery-notes'));
                break;
            
            case 'articulos':
                $num = $ModelDataGenerator->articulos();
                $this->miniLog->info($num . $i18n->trans('generated-products'));
                break;
            
            case 'clientes':
                $num = $ModelDataGenerator->clientes();
                $this->miniLog->info($num . $i18n->trans('generated-customers'));
                break;
            
            case 'fabricantes':
                $num = $ModelDataGenerator->fabricantes();
                $this->miniLog->info($num . $i18n->trans('generated-manufacturers'));
                break;
            
            case 'familias':
                $num = $ModelDataGenerator->familias();
                $this->miniLog->info($num . $i18n->trans('generated-families'));
                break;
            
            case 'grupos':
                $num = $ModelDataGenerator->gruposClientes();
                $this->miniLog->info($num . $i18n->trans('generated-customer-groups'));
                break;
            
            case 'pedidoscli':
                $num = $ModelDataGenerator->pedidosCliente();
                $this->miniLog->info($num . $i18n->trans('generated-customer-orders'));
                break;
            
            case 'pedidosprov':
                $num = $ModelDataGenerator->pedidosProveedor();
                $this->miniLog->info($num . $i18n->trans('generated-supplier-orders'));
                break;
            
            case 'presupuestoscli':
                $num = $ModelDataGenerator->presupuestosCliente();
                $this->miniLog->info($num . $i18n->trans('generated-customer-estimations'));
                break;
            
            case 'proveedores':
                $num = $ModelDataGenerator->proveedores();
                $this->miniLog->info($num . $i18n->trans('generated-supplier'));
                break;
        }
    }
    
    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['menu'] = 'admin';
        $pageData['title'] = 'generate-test-data';
        $pageData['icon'] = 'fa-magic';

        return $pageData;
    }
}
