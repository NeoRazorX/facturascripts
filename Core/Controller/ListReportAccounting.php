<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2020-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Lib\ExtendedController\ListController;
use FacturaScripts\Core\Model\ReportBalance;

/**
 * Description of ListReportAccounting
 *
 * @author Jose Antonio Cuello <jcuello@artextrading.com>
 */
class ListReportAccounting extends ListController
{

    /**
     *
     * @var array
     */
    private $companyList;

    /**
     * Return the basic data for this page.
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'reports';
        $data['title'] = 'accounting-reports';
        $data['icon'] = 'fas fa-balance-scale';
        return $data;
    }

    /**
     * Inserts the views or tabs to display.
     */
    protected function createViews()
    {
        $this->createViewsLedger();
        $this->createViewsAmount();
        $this->createViewsBalance();
        $this->createViewsPreferences();
    }

    /**
     * Inserts the view for amount balances.
     *
     * @param string $viewName
     */
    protected function createViewsAmount(string $viewName = 'ListReportAmount')
    {
        $this->addView($viewName, 'ReportAmount', 'balance-amounts', 'fas fa-calculator');
        $this->addOrderBy($viewName, ['name'], 'name');
        $this->addOrderBy($viewName, ['idcompany', 'name'], 'company');
        $this->addSearchFields($viewName, ['name']);
        $this->addCommonFilter($viewName);
    }

    /**
     * Inserts the view for sheet and Profit & Loss balances.
     *
     * @param string $viewName
     */
    protected function createViewsBalance(string $viewName = 'ListReportBalance')
    {
        $this->addView($viewName, 'ReportBalance', 'balances', 'fas fa-book');
        $this->addOrderBy($viewName, ['name'], 'name');
        $this->addOrderBy($viewName, ['idcompany', 'name'], 'company');
        $this->addSearchFields($viewName, ['name']);
        $this->addCommonFilter($viewName);
        $this->loadWidgetValues($viewName);
    }

    /**
     * Inserts the view for ledger report.
     *
     * @param string $viewName
     */
    protected function createViewsLedger(string $viewName = 'ListReportLedger')
    {
        $this->addView($viewName, 'ReportLedger', 'ledger', 'fas fa-file-alt');
        $this->addOrderBy($viewName, ['name'], 'name');
        $this->addOrderBy($viewName, ['idcompany', 'name'], 'company');
        $this->addSearchFields($viewName, ['name']);
        $this->addCommonFilter($viewName);
    }

    /**
     * Inserts the view for setting balances report.
     *
     * @param string $viewName
     */
    protected function createViewsPreferences(string $viewName = 'ListBalance')
    {
        $this->addView($viewName, 'Balance', 'preferences', 'fas fa-cogs');
        $this->addOrderBy($viewName, ['codbalance'], 'code');
        $this->addOrderBy($viewName, ['descripcion1'], 'description-1');
        $this->addOrderBy($viewName, ['descripcion2'], 'description-2');
        $this->addOrderBy($viewName, ['descripcion3'], 'description-3');
        $this->addOrderBy($viewName, ['descripcion4'], 'description-4');
        $this->addOrderBy($viewName, ['descripcion4ba'], 'description-4ba');

        $this->addSearchFields($viewName, [
            'codbalance', 'naturaleza', 'descripcion1', 'descripcion2',
            'descripcion3', 'descripcion4', 'descripcion4ba'
        ]);

        $i18n = $this->toolBox()->i18n();
        $this->addFilterSelect($viewName, 'type', 'type', 'naturaleza', [
            ['code' => '', 'description' => '------'],
            ['code' => 'A', 'description' => $i18n->trans('asset')],
            ['code' => 'P', 'description' => $i18n->trans('liabilities')],
            ['code' => 'PG', 'description' => $i18n->trans('profit-and-loss')],
            ['code' => 'IG', 'description' => $i18n->trans('income-and-expenses')]
        ]);
    }

    /**
     * Load values into special widget columns
     *
     * @param string $viewName
     */
    protected function loadWidgetValues($viewName)
    {
        $typeColumn = $this->views[$viewName]->columnForField('type');
        if ($typeColumn) {
            $typeColumn->widget->setValuesFromArray(ReportBalance::typeList());
        }

        $formatColumn = $this->views[$viewName]->columnForField('subtype');
        if ($formatColumn) {
            $formatColumn->widget->setValuesFromArray(ReportBalance::subtypeList());
        }
    }

    /**
     * Add to indicated view a filter select with company list
     *
     * @param string $viewName
     */
    private function addCommonFilter($viewName)
    {
        if (empty($this->companyList)) {
            $this->companyList = $this->codeModel->all('empresas', 'idempresa', 'nombrecorto');
        }

        $this->addFilterSelect($viewName, 'idcompany', 'company', 'idcompany', $this->companyList);
        $this->addFilterNumber($viewName, 'channel', 'channel', 'channel', '=');
    }
}
