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

use FacturaScripts\Core\Controller\Base\EditReportAccounting;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Model\ReportBalance;
use FacturaScripts\Dinamic\Lib\Accounting\BalanceSheet;
use FacturaScripts\Dinamic\Lib\Accounting\ProfitAndLoss;

/**
 * Description of EditReportBalance
 *
 * @author Jose Antonio Cuello <jcuello@artextrading.com>
 */
class EditReportBalance extends EditReportAccounting
{

    /**
     *
     * @return string
     */
    public function getModelClassName()
    {
        return 'ReportBalance';
    }

    /**
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

    /**
     * Generate Balance Amounts data for report
     *
     * @param ReportBalance $model
     * @return array
     */
    protected function generateReport($model)
    {
        $params = [
            'idcompany' => $model->idcompany,
            'channel' => $model->channel,
            'format' => $model->format
        ];

        switch ($model->type) {
            case ReportBalance::TYPE_SHEET:
                $balanceAmount = new BalanceSheet();
                return $balanceAmount->generate($model->startdate, $model->enddate, $params);

            case ReportBalance::TYPE_PROFIT:
                $profitAndLoss = new ProfitAndLoss();
                return $profitAndLoss->generate($model->startdate, $model->enddate, $params);
        }

        return [];
    }

    /**
     *
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            default:
                parent::loadData($viewName, $view);
                $this->loadWidgetValues($view);
                break;
        }
    }

    /**
     *
     * @param BaseView $view
     */
    protected function loadWidgetValues($view)
    {
        $typeColumn = $view->columnForField('type');
        if ($typeColumn) {
            $typeColumn->widget->setValuesFromArray(ReportBalance::typeList());
        }

        $formatColumn = $view->columnForField('format');
        if ($formatColumn) {
            $formatColumn->widget->setValuesFromArray(ReportBalance::formatList());
        }
    }
}
