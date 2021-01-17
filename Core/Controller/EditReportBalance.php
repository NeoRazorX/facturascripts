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

use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditReportAccounting;
use FacturaScripts\Core\Model\ReportBalance;
use FacturaScripts\Dinamic\Lib\Accounting\BalanceSheet;
use FacturaScripts\Dinamic\Lib\Accounting\IncomeAndExpenditure;
use FacturaScripts\Dinamic\Lib\Accounting\ProfitAndLoss;

/**
 * Description of EditReportBalance
 *
 * @author Carlos Garcia Gomez  <carlos@facturascripts.com>
 * @author Jose Antonio Cuello  <jcuello@artextrading.com>
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
    }

    /**
     * Generate Balance Amounts data for report
     *
     * @param ReportBalance $model
     *
     * @return array
     */
    protected function generateReport($model)
    {
        $params = [
            'idcompany' => $model->idcompany,
            'channel' => $model->channel,
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
     * Get Title for report
     *
     * @return string
     */
    protected function getTitle(): string
    {
        $model = $this->getModel();
        return $this->toolBox()->i18n()->trans($model->type);
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
        $typeColumn = $view->columnForField('type');
        if ($typeColumn) {
            $typeColumn->widget->setValuesFromArray(ReportBalance::typeList());
        }

        $formatColumn = $view->columnForField('subtype');
        if ($formatColumn) {
            $formatColumn->widget->setValuesFromArray(ReportBalance::subtypeList());
        }
    }
}
