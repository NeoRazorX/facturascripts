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

use FacturaScripts\Core\Lib\DocumentReportsBase;
use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\Cache;
use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Core\Model;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of AccountingReports
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
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
     * List of filters.
     *
     * @var DocumentReportsBase\DocumentReportsFilterList[]
     */
    public $filters;

    /**
     * Contains daily, monthly or yearly.
     *
     * @var string
     */
    public $grouped;

    /**
     * List of sources.
     *
     * @var DocumentReportsBase\DocumentReportsSource[]
     */
    public $sources;

    /**
     * List of index labels for data
     *
     * @var Array
     */
    private $labels;

    /**
     * Initializes all the objects and properties
     *
     * @param Cache      $cache
     * @param Translator $i18n
     * @param MiniLog    $miniLog
     * @param string     $className
     */
    public function __construct(&$cache, &$i18n, &$miniLog, $className)
    {
        parent::__construct($cache, $i18n, $miniLog, $className);

        $this->dataTable = [];
        $this->labels = [];

        $this->sources = [
            new DocumentReportsBase\DocumentReportsSource('customer-invoices', '181,225,174'),
            new DocumentReportsBase\DocumentReportsSource('supplier-invoices', '154,206,223'),
        ];

        $this->filters = [
            'employee' => new DocumentReportsBase\DocumentReportsFilterList('\FacturaScripts\Dinamic\Model\Agente', '', 'fa-users'),
            'serie' => new DocumentReportsBase\DocumentReportsFilterList('\FacturaScripts\Dinamic\Model\Serie', AppSettings::get('default', 'codserie')),
            'currency' => new DocumentReportsBase\DocumentReportsFilterList('\FacturaScripts\Dinamic\Model\Divisa', AppSettings::get('default', 'coddivisa')),
            'payment-method' => new DocumentReportsBase\DocumentReportsFilterList('\FacturaScripts\Dinamic\Model\FormaPago'),
        ];
    }

    /**
     * Runs the controller's private logic.
     *
     * @param Response              $response
     * @param Model\User            $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        // actions to be executed before the main process
        $action = $this->request->get('action', '');
        $this->execAction($action);

        // main process
        $this->generateResults();
    }

    /**
     * Set values selected by the user to source.
     *
     * @param int                                   $index
     * @param DocumentReportsBase\DocumentReportsSource $source
     */
    private function setDefaultToSource($index, &$source)
    {
        $source->source = $this->request->get('source' . $index, $source->source);
        $source->dateFrom = new \DateTime($this->request->get('date-from' . $index, date('01-m-Y')));
        $source->dateTo = new \DateTime($this->request->get('date-to' . $index, date('t-m-Y')));
    }

    /**
     * Execute main actions.
     *
     * @param $action
     */
    protected function execAction($action)
    {
        if ($action === 'reload') {
            // Load sources data from params
            foreach ($this->sources as $index => $source) {
                $this->setDefaultToSource($index, $source);
            }

            // Load filters data from params
            foreach ($this->filters as $key => $filter) {
                $filter->selectedValue = $this->request->get($key, $filter->selectedValue);
            }
        }
    }

    /**
     * Set the better result to use for step and format.
     *
     * @param $step
     * @param $format
     */
    private function getStepFormat(&$step, &$format)
    {
        $dateDiff1 = $this->sources[0]->dateTo->diff($this->sources[0]->dateFrom);
        $dateDiff2 = $this->sources[1]->dateTo->diff($this->sources[1]->dateFrom);
        $days = ($dateDiff1->days < $dateDiff2->days) ? $dateDiff2->days : $dateDiff1->days;

        if ($days >= 15 * 30) {
            $step = '+1 year';
            $format = 'Y';
            $this->grouped = 'yearly';

            return;
        }

        if ($days >= 3 * 30) {
            $step = '+1 month';
            $format = 'm-Y';
            $this->grouped = 'monthly';

            return;
        }
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
        $concat = [];
        $options = explode('-', $format);

        switch (true) {
            case in_array('d', $options):
                $concat[] = 'LPAD(CAST(EXTRACT(DAY FROM fecha) AS CHAR(10)), 2, \'0\')';
                $concat[] = ' \'-\' ';
            /// no break

            case in_array('m', $options):
                $concat[] = 'LPAD(CAST(EXTRACT(MONTH FROM fecha) AS CHAR(10)), 2, \'0\')';
                $concat[] = ' \'-\' ';
            /// no break

            case in_array('Y', $options):
                $concat[] = 'CAST(EXTRACT(YEAR FROM fecha) AS CHAR(10))';
        }

        if (strtolower(FS_DB_TYPE) === 'mysql') {
            return 'CONCAT(' . implode(', ', $concat) . ')';
        }

        /// PostgreSQL
        return implode(' || ', $concat);
    }

    /**
     * Establishes the WHERE clause according to the defined filters.
     *
     * @param DocumentReportsBase\DocumentReportsSource $source
     *
     * @return DataBase\DataBaseWhere[]
     */
    private function getWhere($source)
    {
        $where = [
            new DataBase\DataBaseWhere('fecha', $source->dateFrom->format('d-m-Y'), '>='),
            new DataBase\DataBaseWhere('fecha', $source->dateTo->format('d-m-Y'), '<='),
        ];

        foreach ($this->filters as $filter) {
            $where[] = $filter->getWhere();
        }

        return $where;
    }

    /**
     * Populate the result with the parameters.
     *
     * @param DocumentReportsBase\DocumentReportsSource $source
     * @param string                                    $step
     * @param string                                    $format
     *
     * @return array
     */
    private function populateTable(&$source, $step, $format)
    {
        // Init data
        $result = [];
        foreach (Utils::dateRange($source->dateFrom->format('d-m-Y'), $source->dateTo->format('d-m-Y'), $step, $format) as $index) {
            $result[$index] = 0;
        }

        // Get data
        $tableName = $source->getTableName();
        $dateSQL = $this->getDateSQL($format);
        $where = $this->getWhere($source);
        $data = Model\TotalModel::all($tableName, $where, ['total' => 'SUM(neto)'], $dateSQL);

        foreach ($data as $item) {
            if (empty($item->code)) {
                continue;
            }

            $result[$item->code] = (float) $item->totals['total'];
        }

        return $result;
    }

    /**
     * Generate daily data to show to user.
     */
    protected function generateResults()
    {
        $step = '+1 day';
        $format = 'd-m-Y';
        $this->grouped = 'daily';
        $this->getStepFormat($step, $format);

        $this->dataTable = [];
        foreach ($this->sources as $source) {
            $data = $this->populateTable($source, $step, $format);
            $this->dataTable[$source->source] = $data;
            $this->labels += array_keys($data);
            unset($data);
        }
        sort($this->labels);
    }

    /**
     * Return a comma separated list of labels.
     *
     * @return string
     */
    public function getLabels()
    {
        return '"' . implode('","', $this->labels) . '"';
    }

    /**
     * Return a comma separated list of keys.
     *
     * @param $sourceKey
     *
     * @return string
     */
    public function getDataTable($sourceKey)
    {
        return implode(',', $this->dataTable[$sourceKey]);
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
        $pageData['icon'] = 'fa-area-chart';

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
            'customer-estimations' => $this->i18n->trans('customer-estimations'),
            'customer-orders' => $this->i18n->trans('customer-orders'),
            'customer-delivery-notes' => $this->i18n->trans('customer-delivery-notes'),
            'customer-invoices' => $this->i18n->trans('customer-invoices'),
            'supplier-orders' => $this->i18n->trans('supplier-orders'),
            'supplier-delivery-notes' => $this->i18n->trans('supplier-delivery-notes'),
            'supplier-invoices' => $this->i18n->trans('supplier-invoices'),
        ];
    }
}
