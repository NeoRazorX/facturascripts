<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Dinamic\Lib\Accounting;
use FacturaScripts\Dinamic\Lib\ExportManager;
use FacturaScripts\Dinamic\Model\Ejercicio;
use FacturaScripts\Dinamic\Model\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of AccountingReports
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class AccountingReports extends Controller
{

    /**
     * List of exercices.
     *
     * @var Ejercicio[]
     */
    public $ejercicios;

    /**
     * Object to manager data export.
     *
     * @var ExportManager
     */
    public $exportManager;

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
     * Return list of accounting documents
     *
     * @return array
     */
    public function getReports()
    {
        return [
            'ledger' => ['description' => 'ledger', 'grouping' => true],
            'balance-amounts' => ['description' => 'balance-amounts', 'grouping' => false],
            'balance-sheet' => ['description' => 'balance-sheet', 'grouping' => false],
            'profit' => ['description' => 'profit-and-loss-balance', 'grouping' => false],
        ];
    }

    /**
     * Runs the controller's private logic.
     *
     * @param Response              $response
     * @param User                  $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        $ejercicioModel = new Ejercicio();
        $this->ejercicios = $ejercicioModel->all([], ['fechainicio' => 'DESC']);
        $this->exportManager = new ExportManager();

        $action = $this->request->get('action', '');
        if ($action !== '') {
            $this->execAction($action);
        }
    }

    /**
     * Execute main actions.
     * Filter bi date-from date-to format and grouping
     * 
     * @param $action
     */
    protected function execAction($action)
    {
        $pages = [];
        $dateFrom = $this->request->get('date-from', '');
        $dateTo = $this->request->get('date-to', '');
        $format = $this->request->get('format', '');
        $params = ['grouping' => ('YES' == $this->request->get('grouping', 'YES'))];

        switch ($action) {
            case 'ledger':
                $ledger = new Accounting\Ledger();
                $pages = $ledger->generate($dateFrom, $dateTo, $params);
                break;

            case 'balance-amounts':
                $balanceAmount = new Accounting\BalanceAmounts();
                $pages = $balanceAmount->generate($dateFrom, $dateTo, $params);
                break;

            case 'balance-sheet':
                $balanceSheet = new Accounting\BalanceSheet();
                $pages = $balanceSheet->generate($dateFrom, $dateTo, $params);
                break;

            case 'profit':
                $profitAndLoss = new Accounting\ProfitAndLoss();
                $pages = $profitAndLoss->generate($dateFrom, $dateTo, $params);
                break;
        }

        if (empty($pages)) {
            $this->toolBox()->i18nLog()->warning('no-data');
            return;
        }

        $this->setTemplate(false);
        $this->exportData($pages, $format);
    }

    /**
     * Exports data to PDF.
     *
     * @param array  $pages
     * @param string $format
     */
    protected function exportData(&$pages, $format)
    {
        $this->exportManager->newDoc($format);

        foreach ($pages as $data) {
            $headers = empty($data) ? [] : array_keys($data[0]);
            $this->exportManager->addTablePage($headers, $data);
        }

        $this->exportManager->show($this->response);
    }
}
