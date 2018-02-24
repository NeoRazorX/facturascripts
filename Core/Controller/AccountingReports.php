<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Lib\Accounting;
use FacturaScripts\Core\Lib\ExportManager;
use FacturaScripts\Core\Model\Ejercicio;
use FacturaScripts\Core\Model\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of AccountingReports
 *
 * @author Carlos García Gómez
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
     *
     * @param $action
     */
    private function execAction($action)
    {
        $pages = [];
        $dateFrom = $this->request->get('date-from');
        $dateTo = $this->request->get('date-to');
        $format = $this->request->get('format');

        switch ($action) {
            case 'libro-mayor':
                $this->setTemplate(false);
                $ledger = new Accounting\Ledger();
                $pages = $ledger->generate($dateFrom, $dateTo);
                break;

            case 'sumas-saldos':
                $balanceAmmount = new Accounting\BalanceAmmounts();
                $pages = $balanceAmmount->generate($dateFrom, $dateTo);
                break;

            case 'situacion':
                $balanceSheet = new Accounting\BalanceSheet();
                $pages = $balanceSheet->generate($dateFrom, $dateTo);
                break;

            case 'pyg':
                $proffitAndLoss = new Accounting\ProffitAndLoss();
                $pages = $proffitAndLoss->generate($dateFrom, $dateTo);
                break;
        }

        if (empty($pages)) {
            $this->miniLog->info($this->i18n->trans('no-data'));

            return;
        }

        $this->setTemplate(false);
        $this->exportData($pages, $format);
    }

    /**
     * Return list of accounting documents
     *
     * @return array
     */
    public function getReports()
    {
        return [
            'libro-mayor' => 'ledger',
            'sumas-saldos' => 'balance-ammounts',
            'situacion' => 'balance-sheet',
            'pyg' => 'profit-and-loss-balance',
        ];
    }

    /**
     * Return the basic data for this page.
     *
     * @return array
     */
    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['menu'] = 'reports';
        $pageData['title'] = 'accounting-reports';
        $pageData['icon'] = 'fa-balance-scale';

        return $pageData;
    }

    /**
     * Exports data to PDF.
     *
     * @param array  $pages
     * @param string $format
     */
    private function exportData(&$pages, $format)
    {
        $this->exportManager->newDoc($format);

        foreach ($pages as $data) {
            $headers = empty($data) ? [] : array_keys($data[0]);
            $this->exportManager->generateTablePage($headers, $data);
        }

        $this->exportManager->show($this->response);
    }
}
