<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

    protected function createViews()
    {
        $this->createViewsLedger();
        $this->createViewsAmount();
        $this->createViewsBalance();
    }

    protected function createViewsAmount($viewName = 'ListReportAmount')
    {
        $this->addView($viewName, 'ReportAmount', 'amounts', 'fas fa-calculator');
        $this->addOrderBy($viewName, ['name'], 'name');
        $this->addOrderBy($viewName, ['idcompany', 'name'], 'company');
        $this->addSearchFields($viewName, ['name']);
    }

    protected function createViewsBalance($viewName = 'ListReportBalance')
    {
        $this->addView($viewName, 'ReportBalance', 'balances', 'fas fa-book');
        $this->addOrderBy($viewName, ['name'], 'name');
        $this->addOrderBy($viewName, ['idcompany', 'name'], 'company');
        $this->addSearchFields($viewName, ['name']);

        $this->loadWidgetValues($viewName);
    }

    protected function createViewsLedger($viewName = 'ListReportLedger')
    {
        $this->addView($viewName, 'ReportLedger', 'ledger', 'fas fa-file-alt');
        $this->addOrderBy($viewName, ['name'], 'name');
        $this->addOrderBy($viewName, ['idcompany', 'name'], 'company');
        $this->addSearchFields($viewName, ['name']);
    }

    protected function loadWidgetValues($viewName)
    {
        $typeColumn = $this->views[$viewName]->columnForField('type');
        if ($typeColumn) {
            $typeColumn->widget->setValuesFromArray(ReportBalance::typeList());
        }

        $formatColumn = $this->views[$viewName]->columnForField('format');
        if ($formatColumn) {
            $formatColumn->widget->setValuesFromArray(ReportBalance::formatList());
        }
    }
}
