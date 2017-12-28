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

use FacturaScripts\Core\Base;
use FacturaScripts\Core\Lib\RandomDataGenerator;
use FacturaScripts\Core\Model\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller to generate random data
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Randomizer extends Base\Controller
{

    /**
     *
     * @var string 
     */
    public $urlReload;

    /**
     * Runs the controller's private logic.
     *
     * @param Response $response
     * @param User $user
     * @param Base\ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        $option = $this->request->get('gen', '');
        if ($option !== '') {
            $this->execAction($option);
            $this->urlReload = $this->url() . '&gen=' . $option;
        }
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['menu'] = 'admin';
        $pageData['title'] = 'generate-test-data';
        $pageData['icon'] = 'fa-magic';

        return $pageData;
    }

    /**
     * Executes selected action.
     * 
     * @param string $option
     */
    private function execAction($option)
    {
        $accountingGenerator = new RandomDataGenerator\AccountingGenerator($this->empresa);
        $documentGenerator = new RandomDataGenerator\DocumentGenerator($this->empresa);
        $modelDataGenerator = new RandomDataGenerator\ModelDataGenerator($this->empresa);

        switch ($option) {
            case 'agentes':
                $num = $modelDataGenerator->agentes();
                $this->miniLog->info($this->i18n->trans('generated-agents', ['%quantity%' => $num]));
                break;

            case 'albaranescli':
                $num = $documentGenerator->albaranesCliente();
                $this->miniLog->info($this->i18n->trans('generated-customer-delivery-notes', ['%quantity%' => $num]));
                break;

            case 'albaranesprov':
                $num = $documentGenerator->albaranesProveedor();
                $this->miniLog->info($this->i18n->trans('generated-supplier-delivery-notes', ['%quantity%' => $num]));
                break;

            case 'articulos':
                $num = $modelDataGenerator->articulos();
                $this->miniLog->info($this->i18n->trans('generated-products', ['%quantity%' => $num]));
                break;

            case 'articulosprov':
                $num = $modelDataGenerator->articulosProveedor();
                $this->miniLog->info($this->i18n->trans('generated-products', ['%quantity%' => $num]));
                break;

            case 'asientos':
                $num = $accountingGenerator->asientos();
                $this->miniLog->info($this->i18n->trans('generated-accounting-entries', ['%quantity%' => $num]));
                break;

            case 'clientes':
                $num = $modelDataGenerator->clientes();
                $this->miniLog->info($this->i18n->trans('generated-customers', ['%quantity%' => $num]));
                break;

            case 'cuentas':
                $accountingGenerator->gruposEpigrafes(2);
                $accountingGenerator->epigrafes(4);
                $num = $accountingGenerator->cuentas(8);
                $this->miniLog->info($this->i18n->trans('generated-accounts', ['%quantity%' => $num]));
                break;

            case 'fabricantes':
                $num = $modelDataGenerator->fabricantes();
                $this->miniLog->info($this->i18n->trans('generated-manufacturers', ['%quantity%' => $num]));
                break;

            case 'familias':
                $num = $modelDataGenerator->familias();
                $this->miniLog->info($this->i18n->trans('generated-families', ['%quantity%' => $num]));
                break;

            case 'grupos':
                $num = $modelDataGenerator->gruposClientes();
                $this->miniLog->info($this->i18n->trans('generated-customer-groups', ['%quantity%' => $num]));
                break;

            case 'pedidoscli':
                $num = $documentGenerator->pedidosCliente();
                $this->miniLog->info($this->i18n->trans('generated-customer-orders', ['%quantity%' => $num]));
                break;

            case 'pedidosprov':
                $num = $documentGenerator->pedidosProveedor();
                $this->miniLog->info($this->i18n->trans('generated-supplier-orders', ['%quantity%' => $num]));
                break;

            case 'presupuestoscli':
                $num = $documentGenerator->presupuestosCliente();
                $this->miniLog->info($this->i18n->trans('generated-customer-estimations', ['%quantity%' => $num]));
                break;

            case 'proveedores':
                $num = $modelDataGenerator->proveedores();
                $this->miniLog->info($this->i18n->trans('generated-supplier', ['%quantity%' => $num]));
                break;

            case 'subcuentas':
                $num = $accountingGenerator->subcuentas();
                $this->miniLog->info($this->i18n->trans('generated-subaccounts', ['%quantity%' => $num]));
                break;
        }
    }
}
