<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Lib\ExportManager;
use FacturaScripts\Dinamic\Model\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of ReportTaxes
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ReportTaxes extends Controller
{

    /**
     * 
     * @var string
     */
    public $datefrom;

    /**
     * 
     * @var string
     */
    public $dateto;

    /**
     * 
     * @var string
     */
    public $format;

    /**
     * 
     * @var int
     */
    public $idempresa;

    /**
     * 
     * @var string
     */
    public $source;

    /**
     * 
     * @return array
     */
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['title'] = 'taxes';
        $data['menu'] = 'reports';
        $data['icon'] = 'fas fa-wallet';
        return $data;
    }

    /**
     * 
     * @param Response              $response
     * @param User                  $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        $this->initFilters();
        if ('export' === $this->request->request->get('action')) {
            $this->exportAction();
        }
    }

    protected function exportAction()
    {
        $data = $this->getReportData();
        if (empty($data)) {
            $this->toolBox()->i18nLog()->warning('no-data');
            return;
        }

        $this->setTemplate(false);
        $exportManager = new ExportManager();
        $exportManager->newDoc($this->format, $this->toolBox()->i18n()->trans('taxes'));

        /// add lines
        $lastcode = '';
        $lines = [];
        foreach ($data as $row) {
            $hide = $row['codigo'] === $lastcode && $this->format === 'pdf';
            $lines[] = [
                'serie' => $hide ? '' : $row['codserie'],
                'codigo' => $hide ? '' : $row['codigo'],
                'numero2' => $hide ? '' : $row['numero2'],
                'fecha' => $hide ? '' : $row['fecha'],
                'nombre' => $hide ? '' : $this->toolBox()->utils()->fixHtml($row['nombre']),
                'cifnif' => $hide ? '' : $row['cifnif'],
                'neto' => $this->toolBox()->numbers()->format($row['neto']),
                'iva' => $this->toolBox()->numbers()->format($row['iva']),
                'totaliva' => $this->toolBox()->numbers()->format($row['totaliva']),
                'recargo' => $this->toolBox()->numbers()->format($row['recargo']),
                'totalrecargo' => $this->toolBox()->numbers()->format($row['totalrecargo']),
                'irpf' => $this->toolBox()->numbers()->format($row['irpf']),
                'totalirpf' => $this->toolBox()->numbers()->format($row['totalirpf']),
                'suplidos' => $this->toolBox()->numbers()->format($row['suplidos'])
            ];

            $lastcode = $row['codigo'];
        }
        $headers = empty($lines) ? [] : \array_keys(\end($lines));
        $exportManager->addTablePage($headers, $lines);

        /// add totals
        $totals = [];
        foreach ($this->getTotals($data) as $row) {
            $totals[] = [
                'neto' => $this->toolBox()->coins()->format($row['neto']),
                'iva' => $this->toolBox()->numbers()->format($row['iva']) . ' %',
                'totaliva' => $this->toolBox()->coins()->format($row['totaliva']),
                'recargo' => $this->toolBox()->numbers()->format($row['recargo']) . ' %',
                'totalrecargo' => $this->toolBox()->coins()->format($row['totalrecargo']),
                'irpf' => $this->toolBox()->numbers()->format($row['irpf']) . ' %',
                'totalirpf' => $this->toolBox()->coins()->format($row['totalirpf']),
                'suplidos' => $this->toolBox()->coins()->format($row['suplidos'])
            ];
        }
        $headtotals = empty($totals) ? [] : \array_keys(\end($totals));
        $exportManager->addTablePage($headtotals, $totals);

        $exportManager->show($this->response);
    }

    /**
     * 
     * @return array
     */
    protected function getReportData(): array
    {
        $sql = '';
        switch ($this->source) {
            case 'purchases':
                $sql .= 'SELECT f.codserie, f.codigo, f.numproveedor, f.fecha, f.nombre, f.cifnif, l.pvptotal, l.iva, l.recargo, l.irpf, l.suplido'
                    . ' FROM lineasfacturasprov AS l'
                    . ' LEFT JOIN facturasprov AS f ON l.idfactura = f.idfactura '
                    . ' WHERE f.idempresa = ' . $this->dataBase->var2str($this->idempresa)
                    . ' AND f.fecha >= ' . $this->dataBase->var2str($this->datefrom)
                    . ' AND f.fecha <= ' . $this->dataBase->var2str($this->dateto)
                    . ' ORDER BY f.fecha, f.codigo ASC;';
                break;

            case 'sales':
                $sql .= 'SELECT f.codserie, f.codigo, f.numero2, f.fecha, f.nombrecliente, f.cifnif, l.pvptotal, l.iva, l.recargo, l.irpf, l.suplido'
                    . ' FROM lineasfacturascli AS l'
                    . ' LEFT JOIN facturascli AS f ON l.idfactura = f.idfactura '
                    . ' WHERE f.idempresa = ' . $this->dataBase->var2str($this->idempresa)
                    . ' AND f.fecha >= ' . $this->dataBase->var2str($this->datefrom)
                    . ' AND f.fecha <= ' . $this->dataBase->var2str($this->dateto)
                    . ' ORDER BY f.fecha, f.codigo ASC;';
                break;

            default:
                return [];
        }

        $data = [];
        foreach ($this->dataBase->select($sql) as $row) {
            $code = $row['codigo'] . '-' . $row['iva'] . '-' . $row['recargo'] . '-' . $row['irpf'] . '-' . $row['suplido'];
            if (isset($data[$code])) {
                $data[$code]['neto'] += (float) $row['pvptotal'];
                $data[$code]['totaliva'] += (float) $row['iva'] * $row['pvptotal'] / 100;
                $data[$code]['totalrecargo'] += (float) $row['recargo'] * $row['pvptotal'] / 100;
                $data[$code]['totalirpf'] += (float) $row['irpf'] * $row['pvptotal'] / 100;
                $data[$code]['suplidos'] += (float) $row['suplido'] * $row['pvptotal'];
                continue;
            }

            $data[$code] = [
                'codserie' => $row['codserie'],
                'codigo' => $row['codigo'],
                'numero2' => $row['numero2'] ?? $row['numproveedor'],
                'fecha' => $row['fecha'],
                'nombre' => $row['nombrecliente'] ?? $row['nombre'],
                'cifnif' => $row['cifnif'],
                'neto' => (float) $row['pvptotal'],
                'iva' => (float) $row['iva'],
                'totaliva' => (float) $row['iva'] * $row['pvptotal'] / 100,
                'recargo' => (float) $row['recargo'],
                'totalrecargo' => (float) $row['recargo'] * $row['pvptotal'] / 100,
                'irpf' => (float) $row['irpf'],
                'totalirpf' => (float) $row['irpf'] * $row['pvptotal'] / 100,
                'suplidos' => (float) $row['suplido'] * $row['pvptotal']
            ];
        }

        return $data;
    }

    /**
     * 
     * @param array $data
     *
     * @return array
     */
    protected function getTotals(array $data): array
    {
        $totals = [];
        foreach ($data as $row) {
            $code = $row['iva'] . '-' . $row['recargo'] . '-' . $row['irpf'];
            if (isset($totals[$code])) {
                $totals[$code]['neto'] += $row['neto'];
                $totals[$code]['totaliva'] += $row['totaliva'];
                $totals[$code]['totalrecargo'] += $row['totalrecargo'];
                $totals[$code]['totalirpf'] += $row['totalirpf'];
                $totals[$code]['suplidos'] += $row['suplidos'];
                continue;
            }

            $totals[$code] = [
                'neto' => $row['neto'],
                'iva' => $row['iva'],
                'totaliva' => $row['totaliva'],
                'recargo' => $row['recargo'],
                'totalrecargo' => $row['totalrecargo'],
                'irpf' => $row['irpf'],
                'totalirpf' => $row['totalirpf'],
                'suplidos' => $row['suplidos']
            ];
        }

        return $totals;
    }

    protected function initFilters()
    {
        $this->datefrom = $this->request->request->get('datefrom', \date('Y-m-01'));
        $this->dateto = $this->request->request->get('dateto', \date('Y-m-t'));
        $this->idempresa = (int) $this->request->request->get('idempresa');
        $this->format = $this->request->request->get('format');
        $this->source = $this->request->request->get('source');
    }
}
