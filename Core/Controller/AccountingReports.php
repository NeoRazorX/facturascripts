<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
use FacturaScripts\Core\Lib\Accounting;
use FacturaScripts\Core\Lib\Export\PDFExport;
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
     * Runs the controller's private logic.
     *
     * @param Response $response
     * @param User|null $user
     */
    public function privateCore(&$response, $user)
    {
        parent::privateCore($response, $user);

        $ejercicioModel = new Ejercicio();
        $this->ejercicios = $ejercicioModel->all([], ['fechainicio' => 'DESC']);

        $action = $this->request->get('action', '');
        $this->execAction($action);
    }

    /**
     * Execute main actions.
     *
     * @param $action
     */
    private function execAction($action)
    {
        $data = [];
        $dateFrom = $this->request->get('date-from');
        $dateTo = $this->request->get('date-to');

        switch ($action) {
            case 'libro-mayor':
                $this->setTemplate(false);
                $ledger = new Accounting\Ledger();
                $data = $ledger->generate($dateFrom, $dateTo);
                break;

            case 'sumas-saldos':
                $balanceAmmount = new Accounting\BalanceAmmounts();
                $data = $balanceAmmount->generate($dateFrom, $dateTo);
                break;

            case 'situacion':
                $balanceSheet = new Accounting\BalanceSheet();
                $data = $balanceAmmount->generate($dateFrom, $dateTo);
                break;

            case 'pyg':
                $proffitAndLoss = new Accounting\ProffitAndLoss();
                $data = $proffitAndLoss->generate($dateFrom, $dateTo);
                break;
        }

        if (!empty($data)) {
            $this->setTemplate(false);
            $this->exportData($data);
        }
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
     * @param array $data
     */
    private function exportData(&$data)
    {
        $headers = array_keys($data[0]);

        $pdfExport = new PDFExport();
        $pdfExport->newDoc($this->response);
        $pdfExport->generateTablePage($headers, $data);
        $this->response->setContent($pdfExport->getDoc());
    }
}
