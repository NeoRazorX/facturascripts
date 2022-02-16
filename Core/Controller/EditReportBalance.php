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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditReportAccounting;
use FacturaScripts\Dinamic\Lib\Accounting\BalanceSheet;
use FacturaScripts\Dinamic\Lib\Accounting\IncomeAndExpenditure;
use FacturaScripts\Dinamic\Lib\Accounting\ProfitAndLoss;
use FacturaScripts\Dinamic\Model\ReportBalance;

/**
 * Description of EditReportBalance
 *
 * @author Carlos Garcia Gomez  <carlos@facturascripts.com>
 * @author Jose Antonio Cuello  <yopli2000@gmail.com>
 */
class EditReportBalance extends EditReportAccounting
{

    /**
     * Returns the class name of the model to use in the editView.
     *
     * @return string
     */
    public function getModelClassName()
    {
        return 'ReportBalance';
    }

    /**
     * Return the basic data for this page.
     *
     * @return array
     */
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'reports';
        $data['title'] = 'balances';
        $data['icon'] = 'fas fa-book';
        return $data;
    }

    protected function createViews()
    {
        parent::createViews();
        /// disable company column if there is only one company
        if ($this->empresa->count() < 2) {
            $this->views[$this->getMainViewName()]->disableColumn('company');
        }

        $this->createViewsBalances();
        $this->setTabsPosition('bottom');
    }

    /**
     *
     * @param string $viewName
     */
    protected function createViewsBalances(string $viewName = 'ListBalance')
    {
        $this->addListView($viewName, 'Balance', 'preferences');
        $this->views[$viewName]->addOrderBy(['codbalance'], 'code');
        $this->views[$viewName]->addOrderBy(['descripcion1'], 'description-1');
        $this->views[$viewName]->addOrderBy(['descripcion2'], 'description-2');
        $this->views[$viewName]->addOrderBy(['descripcion3'], 'description-3');
        $this->views[$viewName]->addOrderBy(['descripcion4'], 'description-4');
        $this->views[$viewName]->addOrderBy(['descripcion4ba'], 'description-4ba');
        $this->views[$viewName]->addSearchFields([
            'codbalance', 'naturaleza', 'descripcion1', 'descripcion2',
            'descripcion3', 'descripcion4', 'descripcion4ba'
        ]);

        /// disable column
        $this->views[$viewName]->disableColumn('nature');

        /// disable buttons
        $this->setSettings($viewName, 'btnDelete', false);
        $this->setSettings($viewName, 'btnNew', false);
        $this->setSettings($viewName, 'checkBoxes', false);
    }

    /**
     * Generate Balance Amounts data for report
     *
     * @param ReportBalance $model
     * @param string        $format
     *
     * @return array
     */
    protected function generateReport($model, $format)
    {
        $params = [
            'channel' => $model->channel,
            'format' => $format,
            'idcompany' => $model->idcompany,
            'subtype' => $model->subtype
        ];

        switch ($model->type) {
            case ReportBalance::TYPE_SHEET:
                $balanceAmount = new BalanceSheet();
                $balanceAmount->setExerciseFromDate($model->idcompany, $model->startdate);
                return $balanceAmount->generate($model->startdate, $model->enddate, $params);

            case ReportBalance::TYPE_PROFIT:
                $profitAndLoss = new ProfitAndLoss();
                $profitAndLoss->setExerciseFromDate($model->idcompany, $model->startdate);
                return $profitAndLoss->generate($model->startdate, $model->enddate, $params);

            case ReportBalance::TYPE_INCOME:
                $incomeAndExpenditure = new IncomeAndExpenditure();
                $incomeAndExpenditure->setExerciseFromDate($model->idcompany, $model->startdate);
                return $incomeAndExpenditure->generate($model->startdate, $model->enddate, $params);
        }

        return [];
    }

    /**
     *
     * @return array
     */
    protected function getPreferencesWhere()
    {
        switch ($this->getModel()->type) {
            case 'balance-sheet':
                return [
                    new DataBaseWhere('naturaleza', 'A'),
                    new DataBaseWhere('naturaleza', 'P', '=', 'OR')
                ];

            case 'profit-and-loss':
                return [new DataBaseWhere('naturaleza', 'PG')];

            case 'income-and-expenses':
                return [new DataBaseWhere('naturaleza', 'IG')];
        }

        return [];
    }

    /**
     * Loads the data to display.
     *
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $mainViewName = $this->getMainViewName();
        switch ($viewName) {
            case 'ListBalance':
                $where = $this->getPreferencesWhere();
                $view->loadData('', $where);
                break;

            case $mainViewName:
                parent::loadData($viewName, $view);
                $this->loadWidgetValues($view);
                break;
        }
    }

    /**
     * Load values into special widget columns
     *
     * @param BaseView $view
     */
    protected function loadWidgetValues($view)
    {
        $columnType = $view->columnForField('type');
        if ($columnType && $columnType->widget->getType() === 'select') {
            $columnType->widget->setValuesFromArray(ReportBalance::typeList());
        }

        $columnFormat = $view->columnForField('subtype');
        if ($columnFormat && $columnFormat->widget->getType() === 'select') {
            $columnFormat->widget->setValuesFromArray(ReportBalance::subtypeList());
        }
    }
}
