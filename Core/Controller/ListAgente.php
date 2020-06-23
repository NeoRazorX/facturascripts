<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\ListController;
use FacturaScripts\Dinamic\Lib\CommissionTools;
use FacturaScripts\Dinamic\Model\Agente;
use FacturaScripts\Dinamic\Model\Empresa;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\LiquidacionComision;

/**
 * Controller to list the items in the Agentes model
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class ListAgente extends ListController
{

    /**
     * Company list used by filters
     *
     * @var array
     */
    protected $companyList;

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'agents';
        $data['icon'] = 'fas fa-user-tie';
        return $data;
    }

    /**
     * Add Agent View
     *
     * @param string $viewName
     */
    protected function createAgentView(string $viewName = 'ListAgente')
    {
        $this->addView($viewName, 'Agente', 'agents', 'fas fa-user-tie');
        $this->addOrderBy($viewName, ['codagente'], 'code');
        $this->addOrderBy($viewName, ['nombre'], 'name', 1);
        $this->addSearchFields($viewName, ['nombre', 'codagente', 'email', 'telefono1', 'telefono2', 'observaciones']);

        /// Filters
        $this->addFilterSelectWhere($viewName, 'status', [
            ['label' => $this->toolBox()->i18n()->trans('only-active'), 'where' => [new DataBaseWhere('debaja', false)]],
            ['label' => $this->toolBox()->i18n()->trans('only-suspended'), 'where' => [new DataBaseWhere('debaja', true)]],
            ['label' => $this->toolBox()->i18n()->trans('all'), 'where' => []]
        ]);

        $selectValues = $this->codeModel->all('agentes', 'cargo', 'cargo');
        $this->addFilterSelect($viewName, 'cargo', 'position', 'cargo', $selectValues);
    }

    /**
     * Add Commission View
     *
     * @param string $viewName
     */
    protected function createCommissionView(string $viewName = 'ListComision')
    {
        $this->addView($viewName, 'Comision', 'commissions', 'fas fa-percentage');
        $this->addOrderBy($viewName, ['idcomision'], 'id');
        $this->addOrderBy($viewName, ['prioridad'], 'priority', 2);
        $this->addOrderBy($viewName, ['idempresa', 'codagente', 'porcentaje'], 'company');
        $this->addOrderBy($viewName, ['codagente', 'codcliente', 'codfamilia', 'idproducto', 'porcentaje'], 'agent');
        $this->addOrderBy($viewName, ['codcliente', 'codfamilia', 'idproducto', 'porcentaje'], 'customer');
        $this->addOrderBy($viewName, ['codfamilia', 'idproducto', 'porcentaje'], 'family');
        $this->addSearchFields($viewName, ['codagente', 'codcliente']);

        /// Filters
        $this->addFilterSelect($viewName, 'idempresa', 'company', 'idempresa', $this->companyList);
        $this->addFilterAutocomplete($viewName, 'agent', 'agent', 'codagente', 'agentes', 'codagente', 'nombre');
        $this->addFilterAutocomplete($viewName, 'customer', 'customer', 'codcliente', 'Cliente', 'codcliente');
        $this->addFilterAutocomplete($viewName, 'family', 'family', 'codfamilia', 'Familia', 'codfamilia');
        $this->addFilterAutocomplete($viewName, 'product', 'product', 'referencia', 'Producto', 'referencia', 'descripcion');
    }

    /**
     * Add Settled Commission View
     *
     * @param string $viewName
     */
    protected function createSettlementView(string $viewName = 'ListLiquidacionComision')
    {
        $this->addView($viewName, 'LiquidacionComision', 'settlements', 'fas fa-chalkboard-teacher');
        $this->addOrderBy($viewName, ['fecha', 'idliquidacion'], 'date', 2);
        $this->addOrderBy($viewName, ['codagente', 'fecha'], 'agent');
        $this->addOrderBy($viewName, ['total', 'fecha'], 'amount');
        $this->addSearchFields($viewName, ['observaciones']);

        /// Filters
        $this->addFilterPeriod($viewName, 'fecha', 'date', 'fecha');
        $this->addFilterSelect($viewName, 'idempresa', 'company', 'idempresa', $this->companyList);

        $series = $this->codeModel->all('series', 'codserie', 'descripcion');
        $this->addFilterSelect($viewName, 'codserie', 'serie', 'codserie', $series);

        $agents = $this->codeModel->all('agentes', 'codagente', 'nombre');
        $this->addFilterSelect($viewName, 'codagente', 'agent', 'codagente', $agents);

        $this->addButton($viewName, [
            'action' => 'gen-settlements',
            'icon' => 'fas fa-magic',
            'label' => 'generate',
            'type' => 'modal'
        ]);
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->companyList = $this->codeModel->all(Empresa::tableName(), Empresa::primaryColumn(), 'nombre');

        $this->createAgentView();
        $this->createCommissionView();
        $this->createSettlementView();
    }

    /**
     * 
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        if ($action === 'gen-settlements') {
            $this->generateSettlementsAction();
        }

        return parent::execPreviousAction($action);
    }

    protected function generateSettlementsAction()
    {
        $codserie = $this->request->request->get('codserie', '');
        $dateFrom = $this->request->request->get('datefrom', '');
        $dateTo = $this->request->request->get('dateto', '');
        $idempresa = $this->request->request->get('idempresa');

        $generated = 0;
        $agenteModel = new Agente();
        foreach ($agenteModel->all([], [], 0, 0) as $agente) {
            $invoiceModel = new FacturaCliente();
            $where = [
                new DataBaseWhere('idliquidacion', null, 'IS'),
                new DataBaseWhere('idempresa', $idempresa),
                new DataBaseWhere('codserie', $codserie),
                new DataBaseWhere('codagente', $agente->codagente)
            ];

            if (!empty($dateFrom)) {
                $where[] = new DataBaseWhere('fecha', $dateFrom, '>=');
            }

            if (!empty($dateTo)) {
                $where[] = new DataBaseWhere('fecha', $dateTo, '<=');
            }

            $invoices = $invoiceModel->all($where, [], 0, 0);
            if (\count($invoices)) {
                $this->newSettlement($agente->codagente, $idempresa, $codserie, $invoices);
                $generated++;
            }
        }

        $this->toolBox()->i18nLog()->notice('items-added-correctly', ['%num%' => $generated]);
    }

    /**
     * 
     * @param string           $codagente
     * @param int              $idempresa
     * @param string           $codserie
     * @param FacturaCliente[] $invoices
     */
    protected function newSettlement($codagente, $idempresa, $codserie, $invoices)
    {
        $newSettlement = new LiquidacionComision();
        $newSettlement->codagente = $codagente;
        $newSettlement->codserie = $codserie;
        $newSettlement->idempresa = $idempresa;
        if ($newSettlement->save()) {
            $commissionTools = new CommissionTools();
            foreach ($invoices as $invoice) {
                /// recalculate commissions
                $lines = $invoice->getLines();
                $commissionTools->recalculate($invoice, $lines);

                $invoice->idliquidacion = $newSettlement->idliquidacion;
                $invoice->save();
            }

            $newSettlement->calculateTotalCommission($newSettlement->idliquidacion);
        }
    }
}
