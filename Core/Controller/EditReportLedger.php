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

use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Core\Model\ReportLedger;
use FacturaScripts\Dinamic\Lib\Accounting\Ledger;

/**
 * Description of EditReportLedger
 *
 * @author Jose Antonio Cuello <jcuello@artextrading.com>
 */
class EditReportLedger extends EditController
{

    /**
     * Run the controller after actions.
     *
     * @param string $action
     */
    protected function execAfterAction($action)
    {
        switch ($action) {
            case 'export':
                $model = $this->getModel();
                $this->printReport($model);
                break;

            default:
                parent::execAfterAction($action);
                break;
        }
    }

    /**
     *
     * @return string
     */
    public function getModelClassName()
    {
        return 'ReportLedger';
    }

    /**
     *
     * @return array
     */
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'reports';
        $data['title'] = 'ledger';
        $data['icon'] = 'fas fa-file-alt';
        return $data;
    }

    /**
     *
     * @param ReportLedger $model
     */
    protected function printReport($model)
    {
        $params = [
            'subaccount-from' => $model->startcodsubaccount,
            'subaccount-to' => $model->endcodsubaccount,
            'entry-from' => $model->startentry,
            'entry-to' => $model->endentry,
            'channel' => $model->channel,
            'grouping' => $model->grouping
        ];

        $ledger = new Ledger();
        $pages = $ledger->generate($model->startdate, $model->enddate, $params);
        if (empty($pages)) {
            $this->toolBox()->i18nLog()->warning('no-data');
            return;
        }

        $format = $this->request->get('option', 'PDF');
        $title = $this->toolBox()->i18n()->trans('ledger') . ' - ' . $model->name;

        $this->setTemplate(false);
        $this->exportData($pages, $title, $format);
    }

    /**
     * Exports data to indicated format.
     *
     * @param array  $pages
     * @param string $format
     */
    protected function exportData(&$pages, $title, $format)
    {
        $this->exportManager->newDoc($format, $title);

        foreach ($pages as $data) {
            $headers = empty($data) ? [] : array_keys($data[0]);
            $this->exportManager->addTablePage($headers, $data);
        }

        $this->exportManager->show($this->response);
    }
}
