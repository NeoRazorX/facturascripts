<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
 * Copyright (C) 2017  Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Core\Model;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of AccountingReports
 *
 * @author Francesc Pienda Segarra
 */
class DocumentReports extends Controller
{

    /**
     * Data for table.
     * 
     * @var array
     */
    public $dataTable;

    /**
     * Document 1 used by default or selected.
     * 
     * @var string
     */
    public $source1;

    /**
     * Document 2 used by default or selected.
     * 
     * @var string
     */
    public $source2;

    /**
     * Start date used by default or selected.
     * 
     * @var \DateTime
     */
    public $date1From;

    /**
     * End date used by default or selected.
     * 
     * @var \DateTime
     */
    public $date1To;

    /**
     * Start date used by default or selected.
     * 
     * @var \DateTime
     */
    public $date2From;

    /**
     * End date used by default or selected.
     * 
     * @var \DateTime
     */
    public $date2To;

    /**
     * Employee used by default or selected.
     * 
     * @var string
     */
    public $employee;

    /**
     * Employee list.
     * 
     * @var Model\Agente[]
     */
    public $employeeList;

    /**
     * Serie used by default or selected.
     * 
     * @var string
     */
    public $serie;

    /**
     * Serie list.
     * 
     * @var Model\Serie[]
     */
    public $serieList;

    /**
     * Currency used by default or selected.
     * 
     * @var string
     */
    public $currency;

    /**
     * Currency list.
     * 
     * @var Model\Divisa[]
     */
    public $currencyList;

    /**
     * Payment method used by default or selected.
     * 
     * @var string
     */
    public $paymentMethod;

    /**
     * Payment method List.
     * 
     * @var Model\FormaPago[]
     */
    public $paymentMethodList;

    /**
     * Contains daily, monthly or yearly.
     * 
     * @var string
     */
    public $grouped;
    
    /**
     * Runs the controller's private logic.
     *
     * @param Response $response
     * @param Model\User $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

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
        switch ($action) {
            case 'reload':
                $this->setValuesFromRequest();
                break;

            default:
                $this->setDefaults();
                break;
        }

        $this->setLists();
        $this->generateResults();
    }

    /**
     * Generate daily data to show to user.
     */
    private function generateResults()
    {
        $this->dataTable = [];
        $step = '+1 day';
        $format = 'd-m-Y';
        $this->getStepFormat($step, $format);

        $this->dataTable[] = $this->populateTable($this->date1From, $this->date1To, $this->source1, 1, $step, $format);
        $this->dataTable[] = $this->populateTable($this->date2From, $this->date2To, $this->source2, 2, $step, $format);
    }

    /**
     * Set the better result to use for step and format.
     *
     * @param $step
     * @param $format
     */
    private function getStepFormat(&$step, &$format)
    {
        $dateDiff1 = $this->date1To->diff($this->date1From);
        $dateDiff2 = $this->date2To->diff($this->date2From);

        $days = $dateDiff1->days < $dateDiff2->days ? $dateDiff2->days : $dateDiff1->days;

        switch (true) {
            case ($days >= 3*30 && $days <= 12*30):
                $step = '+1 month';
                $format = 'm-Y';
                $this->grouped = 'monthly';
                break;
            case ($days >= 12*30):
                $step = '+1 year';
                $format = 'Y';
                $this->grouped = 'yearly';
                break;
            default:
                $step = '+1 day';
                $format = 'd-m-Y';
                $this->grouped = 'daily';

                break;
        }
    }

    /**
     * Populate the result with the parameters.
     *
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param string $source
     * @param int $pos
     * @param string $step
     * @param string $format
     *
     * @return array
     */
    private function populateTable($dateFrom, $dateTo, $source, $pos, $step = '+1 day', $format = 'd-m-Y')
    {
        $result = [];

        foreach (Utils::dateRange($dateFrom->format('d-m-Y'), $dateTo->format('d-m-Y'), $step, $format) as $date) {
            $dateDaily = $this->getStamp($format, $date);
            $result[$source][$dateDaily] = [
                'date' => date($format, strtotime($this->fullDate($date))),
                'total' => 0
            ];
        }

        $dateSQL = $this->getDateSQL($format);

        $table = $this->getTableName($source);
        $sql = 'SELECT ' . $dateSQL . ' AS date, SUM(neto) AS total FROM ' . $table
            . ' WHERE ' . $this->getWhere($pos) . 'GROUP BY date ORDER BY date ASC;';
        $list = $this->dataBase->select($sql);
        foreach ($list as $item) {
            $dateDaily = $this->getStamp($format, $this->fullDate($item['date']));
            $result[$source][$dateDaily]['total'] = (float) $item['total'];
        }

        return $result;
    }

    /**
     * Return date field SQL format.
     *
     * @param string $format
     *
     * @return string
     */
    private function getDateSQL($format)
    {
        switch ($format) {
            case 'Y':
                $date = "DATE_FORMAT(fecha, '%Y')";
                if (strtolower(FS_DB_TYPE) === 'postgresql') {
                    $date = "to_char(fecha,'YYYY')";
                }
                break;

            case 'm-Y':
                $date = "DATE_FORMAT(fecha, '%m%Y')";
                if (strtolower(FS_DB_TYPE) === 'postgresql') {
                    $date = "to_char(fecha,'FMMMYYYY')";
                }
                break;

            default:
                $date = 'fecha';
                break;
        }

        return $date;
    }

    /**
     * Return a full format date string from partial string.
     * Example:    2017 => 01-01-2017
     *              12017 =>  01-01-2017
     *              102017 =>  01-10-2017
     *              10-2017 =>  01-10-2017
     *
     * @param string $date
     *
     * @return string
     */
    private function fullDate($date)
    {
        switch (strlen($date)) {
            case 4:
                return '01-01-' . $date;

            case 5:
                return '01-0' . $date[0] . '-' . substr($date, 1);

            case 6:
                return '01-' . substr($date, 0, 2) . '-' . substr($date, 2);

            case 7:
                return '01-' . $date;

            case 8:
                return substr($date, 0, 2) . '-' . substr($date, 2, 2) . '-' .substr($date, 4, 4);

            default:
                return $date;
        }
    }

    /**
     * Return the time stamp used to identify this date.
     *
     * @param string $format
     * @param string $date
     *
     * @return false|int
     */
    private function getStamp($format, $date)
    {
        switch ($format) {
            case 'Y':
                return date('Y', strtotime($this->fullDate($date)));

            case 'm-Y':
                return date('Ym', strtotime($this->fullDate($date)));

            default:
                return date('Ymd', strtotime($date));
        }
    }

    /**
     * Return the table name.
     *
     * @param $source
     *
     * @return string
     */
    private function getTableName($source)
    {
        switch ($source) {
            case 'customer-estimations':
                return 'presupuestoscli';

            case 'customer-orders':
                return 'pedidoscli';

            case 'customer-delivery-notes':
                return 'albaranescli';

            case 'customer-invoices':
                return 'facturascli';

            case 'supplier-orders':
                return 'pedidosprov';

            case 'supplier-delivery-notes':
                return 'albaranesprov';

            case 'supplier-invoices':
                return 'facturasprov';

            default:
                return '';
        }
    }

    /**
     * Establishes the WHERE clause according to the defined filters.
     *
     * @param int $pos
     *
     * @return string
     */
    private function getWhere($pos)
    {
        $where = '';
        switch ($pos) {
            case 1:
                $where .= ' fecha >= ' . $this->dataBase->var2str($this->date1From->format('d-m-Y'))
                    . ' AND fecha <= ' . $this->dataBase->var2str($this->date1To->format('d-m-Y')) . ' ';
                return $where . $this->getCommonWhere();

            case 2:
                $where .= ' fecha >= ' . $this->dataBase->var2str($this->date2From->format('d-m-Y'))
                    . ' AND fecha <= ' . $this->dataBase->var2str($this->date2To->format('d-m-Y')) . ' ';
                return $where . $this->getCommonWhere();

            default:
                return '';
        }
    }

    /**
     * Return the common WHERE clause according to the defined filters.
     * @return string
     */
    private function getCommonWhere()
    {
        $where = '';
        if (!empty($this->employee)) {
            $where .= ' AND codagente = ' . $this->dataBase->var2str($this->employee) . ' ';
        }
        if (!empty($this->serie)) {
            $where .= ' AND codserie = ' . $this->dataBase->var2str($this->serie) . ' ';
        }
        if (!empty($this->currency)) {
            $where .= ' AND coddivisa = ' . $this->dataBase->var2str($this->currency) . ' ';
        }
        if (!empty($this->paymentMethod)) {
            $where .= ' AND codpago = ' . $this->dataBase->var2str($this->paymentMethod) . ' ';
        }

        return $where;
    }

    /**
     * Set basic list of default data for input selects.
     */
    private function setLists()
    {
        $age = new Model\Agente();
        $this->employeeList = $age->all();

        $ser = new Model\Serie();
        $this->serieList = $ser->all();

        $div = new Model\Divisa();
        $this->currencyList = $div->all();

        $fpag = new Model\FormaPago();
        $this->paymentMethodList = $fpag->all();
    }

    /**
     * Set values selected by the user.
     */
    private function setValuesFromRequest()
    {
        $this->source1 = $this->request->get('source1', 'customer-invoices');
        $this->source2 = $this->request->get('source2', 'supplier-invoices');
        $this->date1From = new \DateTime($this->request->get('date1-from', date('01-m-Y')));
        $this->date1To = new \DateTime($this->request->get('date1-to', date('t-m-Y')));
        $this->date2From = new \DateTime($this->request->get('date2-from', date('01-m-Y')));
        $this->date2To = new \DateTime($this->request->get('date2-to', date('t-m-Y')));
        $this->employee = $this->request->get('employee', '');
        $this->serie = $this->request->get('serie', '');
        $this->currency = $this->request->get('currency', AppSettings::get('default', 'coddivisa'));
        $this->paymentMethod = $this->request->get('payment-method', '');
    }

    /**
     * Set default values.
     */
    private function setDefaults()
    {
        $this->source1 = 'customer-invoices';
        $this->source2 = 'supplier-invoices';
        $this->date1From = new \DateTime(date('01-m-Y'));
        $this->date1To = new \DateTime(date('t-m-Y'));
        $this->date2From = new \DateTime(date('01-m-Y'));
        $this->date2To = new \DateTime(date('t-m-Y'));
        $this->employee = '';
        $this->serie = AppSettings::get('default', 'codserie');
        $this->currency = AppSettings::get('default', 'coddivisa');
        $this->paymentMethod = AppSettings::get('default', 'codpago');
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
        $pageData['title'] = 'document-reports';
        $pageData['icon'] = ' fa-area-chart';

        return $pageData;
    }

    /**
     * Return available document types.
     *
     * @return array
     */
    public function getDocumentTypes()
    {
        return [
            'customer-estimations',
            'customer-orders',
            'customer-delivery-notes',
            'customer-invoices',
            'supplier-orders',
            'supplier-delivery-notes',
            'supplier-invoices'
        ];
    }
}
