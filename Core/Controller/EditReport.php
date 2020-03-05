<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Dinamic\Lib\ReportChart\AreaChart;
use FacturaScripts\Dinamic\Model\Report;

/**
 * Description of EditReport
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class EditReport extends EditController
{

    /**
     * 
     * @return AreaChart
     */
    public function getChart()
    {
        foreach ($this->views as $view) {
            return new AreaChart($view->model);
        }

        return new AreaChart(new Report());
    }

    /**
     * 
     * @return string
     */
    public function getModelClassName()
    {
        return 'Report';
    }

    /**
     * 
     * @return array
     */
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'reports';
        $data['title'] = 'report';
        $data['icon'] = 'fas fa-chart-pie';
        return $data;
    }

    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');
        $this->addHtmlView('chart', 'Master/htmlChart', 'Report', 'chart');

        /// disable print button
        $this->setSettings($this->getMainViewName(), 'btnPrint', false);
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
                $this->loadWidgetValues($viewName);
                break;
        }
    }

    /**
     * 
     * @param string $viewName
     */
    protected function loadWidgetValues($viewName)
    {
        $tableColumn = $this->views[$viewName]->columnForField('table');
        if ($tableColumn) {
            $tableColumn->widget->setValuesFromArray($this->dataBase->getTables());
        }

        $tableName = $this->views[$viewName]->model->table;
        $columns = empty($tableName) || !$this->dataBase->tableExists($tableName) ? [] : array_keys($this->dataBase->getColumns($tableName));
        sort($columns);

        $xcolColumn = $this->views[$viewName]->columnForField('xcolumn');
        if ($xcolColumn && count($columns) > 0) {
            $xcolColumn->widget->setValuesFromArray($columns);
        }

        $ycolColumn = $this->views[$viewName]->columnForField('ycolumn');
        if ($ycolColumn && count($columns) > 0) {
            $ycolColumn->widget->setValuesFromArray($columns, false, true);
        }
    }
}
