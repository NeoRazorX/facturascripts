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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Controller;

use Exception;
use FacturaScripts\Core\Base\DivisaTools;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController;
use FacturaScripts\Core\Model;

/**
 * Controller to edit a single item from the Asiento model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 * @author Fco Antonio Moreno Pérez <famphuelva@gmail.com>
 * @author PC REDNET S.L. <luismi@pcrednet.com>
 */
class EditAsiento extends ExtendedController\PanelController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData(): array
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'accounting-entries';
        $pagedata['menu'] = 'accounting';
        $pagedata['icon'] = 'fa-balance-scale';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->addEditView('EditAsiento', 'Asiento', 'accounting-entry', 'fa-balance-scale');
        $this->addGridView('EditPartida', 'EditAsiento', 'Partida', 'accounting-items');
        $this->setTemplate('EditAsiento');
    }

    /**
     * Run the actions that alter data before reading it
     *
     * @param string   $action
     *
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        switch ($action) {
            case 'save-ok':
                $this->miniLog->notice($this->i18n->trans('record-updated-correctly'));
                return true;

            case 'recalculate-document':
                $this->setTemplate(false);
                $data = $this->request->request->all();
                $result = $this->recalculateDocument($data);
                $this->response->setContent(json_encode($result));
                return false;

            case 'account-data':
                $this->setTemplate(false);
                $subaccount = $this->request->get('codsubcuenta', '');
                $exercise = $this->request->get('codejercicio', '');
                $result = $this->getAccountData($exercise, $subaccount);
                $this->response->setContent(json_encode($result));
                return false;

            case 'clone':
                $data = $this->request->request->all();
                $result = $this->cloneDocument($data);
                if (!empty($result)) {
                    $this->setTemplate(false);
                    $this->response->headers->set('Refresh', '0;' . $result);
                    return false;
                }
                return true;

            case 'lock':
                return true; // TODO: Uncomplete

            default:
                return parent::execPreviousAction($action);
        }
    }

    /**
     * Load data and balances from subaccount
     *
     * @param string $exercise
     * @param string $codeSubAccount
     * @return array
     */
    private function getAccountData(string $exercise, string $codeSubAccount): array
    {
        $result = [
            'subaccount' => $codeSubAccount,
            'description' => '',
            'codevat' => '',
            'balance' => 0.00,
            'detail' => [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00]
        ];

        if (empty($exercise) || empty($codeSubAccount)) {
            return $result;
        }

        $where = [
            new DataBaseWhere('codsubcuenta', $codeSubAccount),
            new DataBaseWhere('codejercicio', $exercise)
        ];

        $subAccount = new Model\Subcuenta();
        if ($subAccount->loadFromCode(null, $where)) {
            $balance = new Model\SubcuentaSaldo();
            $result['description'] = $subAccount->descripcion;
            $result['codevat'] = ($subAccount->codimpuesto === null) ? '' : $subAccount->codimpuesto;
            $result['balance'] = $balance->setSubAccountBalance($subAccount->idsubcuenta, $result['detail']);
            $result['balance'] = DivisaTools::format($result['balance']);
        }

        // Return account data
        return $result;
    }

    private function calculateAmounts(array &$data, float $credit, float $debit)
    {
        $unbalance = round(($credit - $debit), (int) FS_NF0);
        $index = count($data['lines']) - 1;
        $line = &$data['lines'][$index];

        if (($line['debe'] + $line['haber']) === 0.00 && $index > 0) {
            // if the sub-account is the same as the previous offsetting
            if ($line['codsubcuenta'] === $data['lines'][$index - 1]['codcontrapartida']) {
                $field = 'haber';
                if ($unbalance < 0) {
                    $field = 'debe';
                }
                $line[$field] = abs($unbalance);
                $unbalance = 0.00;
            }
        }
        $data['unbalance'] = $unbalance;
        $data['total'] = ($credit > $debit) ? round($credit, (int) FS_NF0) : round($debit, (int) FS_NF0);
    }

    private function checkEmptyValues(array &$line, array $previousLine)
    {
        if (empty($line['concepto'])) {
            $line['concepto'] = $previousLine['concepto'];
        }

        if (empty($line['codcontrapartida']) && !empty($line['codsubcuenta'])) {
            // TODO: [Fix] Go through previous lines in search of the offsetting. The sub-account that uses the offsetting for the first time is needed
            $line['codcontrapartida'] = ($line['codsubcuenta'] === $previousLine['codcontrapartida']) ? $previousLine['codsubcuenta'] : $previousLine['codcontrapartida'];
        }
    }

    private function recalculateLines(array $lines, float &$totalCredit, float &$totalDebit): array
    {
        // Work variables
        $result = [];
        $previous = null;
        $totalCredit = 0.00;
        $totalDebit = 0.00;

        // Check lines data
        foreach ($lines as $item) {
            // check empty imports
            if (empty($item['debe'])) {
                $item['debe'] = 0.00;
            }
            if (empty($item['haber'])) {
                $item['haber'] = 0.00;
            }

            // copy previous values to empty fields
            if (!empty($previous)) {
                $this->checkEmptyValues($item, $previous);
            }
            $previous = $item;

            // Acumulate imports
            $totalCredit += $item['debe'];
            $totalDebit += $item['haber'];

            $result[] = $item;
        }
        return $result;
    }

    /**
     * Search for VAT Data into Client model
     *
     * @param string $codeSubAccount
     * @param array $values
     */
    private function searchVatDataFromClient($codeSubAccount, &$values)
    {
        $where = [new DataBaseWhere('codsubcuenta', $codeSubAccount)];
        $client = new Model\Cliente();
        if ($client->loadFromCode(null, $where)) {
            $values['code'] = $client->codcliente;
            $values['description'] = $client->nombre;
            $values['id'] = $client->cifnif;
            $values['surcharge'] = $client->recargo;
        }
    }

    /**
     * Search for VAT Data into Supplier model
     *
     * @param string $codeSubAccount
     * @param array $values
     */
    private function searchVatDataFromSupplier($codeSubAccount, &$values)
    {
        $where = [new DataBaseWhere('codsubcuenta', $codeSubAccount)];
        $supplier = new Model\Proveedor();
        if ($supplier->loadFromCode(null, $where)) {
            $values['code'] = $supplier->codproveedor;
            $values['description'] = $supplier->nombre;
            $values['id'] = $supplier->cifnif;
            $values['surcharge'] = $this->empresa->recequivalencia;
        }
    }

    /**
     * Get VAT information for a sub-account
     *
     * @param string $exercise
     * @param string $codeSubAccount
     * @return array
     */
    private function getAccountVatID($exercise, $codeSubAccount): array
    {
        $result = ['group' => '', 'code' => '', 'description' => '', 'id' => '', 'surcharge' => false];
        if (empty($exercise) || empty($codeSubAccount)) {
            return $result;
        }

        $where = [
            new DataBaseWhere('codsubcuenta', $codeSubAccount),
            new DataBaseWhere('codejercicio', $exercise)
        ];

        $subAccount = new Model\Subcuenta();
        if ($subAccount->loadFromCode(null, $where)) {
            $result['group'] = $subAccount->getSpecialAccountCode();
            switch ($result['group']) {
                case 'CLIENT':
                    $this->searchVatDataFromClient($codeSubAccount, $result);
                    break;

                case 'ACREED':
                case 'PROVEE':
                    $this->searchVatDataFromSupplier($codeSubAccount, $result);
                    break;
            }
        }
        return $result;
    }

    private function recalculateVatRegister(array &$line, array $document, string $codevat, float $base): array
    {
        $result = [];
        if (empty($codevat)) {
            $line['cifnif'] = null;
            $line['documento'] = null;
            $line['baseimponible'] = null;
            $line['iva'] = null;
            $line['recargo'] = null;
            return $result;
        }


        $vat = new Model\Impuesto();
        if ($vat->loadFromCode($codevat)) {
            $result = $this->getAccountVatID($document['codejercicio'], $line['codcontrapartida']);
            $line['documento'] = $document['documento'];
            $line['cifnif'] = $result['id'];
            $line['iva'] = $vat->iva;
            $line['recargo'] = $result['surcharge'] ? $vat->recargo : 0.00;
            $line['baseimponible'] = ($result['group'] === 'CLIENT') ? ($base * -1) : $base;
        }
        return $result;
    }

    protected function recalculateDocument(&$data): array
    {
        $result = [
            'total' => 0.00,
            'unbalance' => 0.00,
            'lines' => [],
            'subaccount' => [],
            'vat' => []
        ];

        if (isset($data['lines'])) {
            // Prepare lines data
            $lines = $this->views['EditPartida']->processFormLines($data['lines']);

            // Recalculate lines data and amounts
            $totalCredit = $totalDebit = 0.00;
            $result['lines'] = $this->recalculateLines($lines, $totalCredit, $totalDebit);
            $this->calculateAmounts($result, $totalCredit, $totalDebit);

            // If only change subaccount, search for subaccount data
            if (count($data['changes']) === 1 && $data['changes'][0][1] === 'codsubcuenta') {
                $index = $data['changes'][0][0];
                $line = &$result['lines'][$index];
                $result['subaccount'] = $this->getAccountData($data['document']['codejercicio'], $line['codsubcuenta']);

                $result['vat'] = $this->recalculateVatRegister(
                    $line, $data['document'], $result['subaccount']['codevat'], $result['unbalance']
                );
            }
        }

        return $result;
    }

    private function cloneDocument(&$data): string
    {
        // init document
        $accounting = new Model\Asiento();
        $accounting->loadFromData($data, ['action', 'active']);

        // init line document
        $entryModel = new Model\Partida();
        $entries = $entryModel->all([new DataBaseWhere('idasiento', $data['idasiento'])]);

        // start transaction
        $dataBase = new DataBase();
        $dataBase->beginTransaction();

        // main save process
        try {
            $accounting->idasiento = null;
            $accounting->fecha = date('d-m-Y');
            $accounting->numero = $accounting->newCode('numero');
            if (!$accounting->save()) {
                throw new Exception(self::$i18n->trans('clone-document-error'));
            }

            foreach ($entries as $line) {
                $line->idpartida = null;
                $line->idasiento = $accounting->idasiento;
                if (!$line->save()) {
                    throw new Exception(self::$i18n->trans('clone-line-document-error'));
                }
            }
            // confirm data
            $dataBase->commit();
            $result = $accounting->url('type') . '&action=save-ok';
        } catch (Exception $e) {
            self::$miniLog->alert($e->getMessage());
            $result = '';
        } finally {
            if ($dataBase->inTransaction()) {
                $dataBase->rollback();
            }
        }

        return $result;
    }

    /**
     * Load data view procedure
     *
     * @param string                      $viewName
     * @param ExtendedController\BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'EditAsiento':
                $code = $this->request->get('code');
                $view->loadData($code);
                break;

            case 'EditPartida':
                $idasiento = $this->getViewModelValue('EditAsiento', 'idasiento');
                if (!empty($idasiento)) {
                    $where = [new DataBaseWhere('idasiento', $idasiento)];
                    $orderby = ['idpartida' => 'ASC'];
                    $view->loadData($where, $orderby);
                }
                break;
        }
    }
}
