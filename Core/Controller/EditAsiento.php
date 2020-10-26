<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Dinamic\Lib\AccountingEntryTools;
use FacturaScripts\Dinamic\Model\Asiento;
use FacturaScripts\Dinamic\Model\Partida;

/**
 * Controller to edit a single item from the Asiento model
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class EditAsiento extends EditController
{

    /**
     * Returns the class name of the model to use in the editView.
     *
     * @return string
     */
    public function getModelClassName()
    {
        return 'Asiento';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'accounting';
        $data['title'] = 'accounting-entry';
        $data['icon'] = 'fas fa-balance-scale';
        return $data;
    }

    /**
     * Indicates whether the balance chart should be displayed
     *
     * @return bool
     */
    public function showBalanceGraphic()
    {
        return (bool) $this->toolBox()->appSettings()->get('default', 'balancegraphic');
    }

    /**
     * Overwrite autocomplete function to macro concepts in accounting concept.
     */
    protected function autocompleteAction(): array
    {
        if ($this->request->get('source', '') === 'conceptos_partidas') {
            return $this->replaceConcept(parent::autocompleteAction());
        }

        return parent::autocompleteAction();
    }

    /**
     * Lock/Unlock accounting entry
     */
    protected function changeLockStatus()
    {
        $code = $this->request->get('code');
        $accounting = new Asiento();
        if ($accounting->loadFromCode($code)) {
            $accounting->editable = !$accounting->editable;
            if ($accounting->save()) {
                $this->toolBox()->i18nLog()->notice('record-updated-correctly');
            }
        }
    }

    /**
     * Clone source document
     *
     * @return bool
     * @throws Exception
     */
    protected function cloneDocument()
    {
        $sourceCode = $this->request->get('code');

        // prepare source document structure
        $accounting = new Asiento();
        if (false === $accounting->loadFromCode($sourceCode)) {
            return true; // continue default view load
        }

        $entryModel = new Partida();
        $entries = $entryModel->all([new DataBaseWhere('idasiento', $accounting->idasiento)]);

        // init target document data
        $accounting->idasiento = null;
        $accounting->fecha = \date(Asiento::DATE_STYLE);
        $accounting->numero = $accounting->newCode('numero');

        // start transaction
        $this->dataBase->beginTransaction();

        // main save process
        $cloneOk = true;
        try {
            if (false === $accounting->save()) {
                throw new Exception($this->toolBox()->i18n()->trans('clone-document-error'));
            }

            foreach ($entries as $line) {
                $line->idpartida = null;
                $line->idasiento = $accounting->idasiento;
                if (false === $line->save()) {
                    throw new Exception($this->toolBox()->i18n()->trans('clone-line-document-error'));
                }
            }

            $this->dataBase->commit();
        } catch (Exception $exp) {
            $this->toolBox()->log()->error($exp->getMessage());
            $cloneOk = false;
        } finally {
            if ($this->dataBase->inTransaction()) {
                $this->dataBase->rollback();
            }
        }

        // if all ok then redirect to new record
        if ($cloneOk) {
            $this->setTemplate(false);
            $this->redirect($accounting->url('type') . '&action=save-ok');
            return false;
        }

        return true; // refresh view
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $master = ['name' => 'EditAsiento', 'model' => 'Asiento'];
        $detail = ['name' => 'EditPartida', 'model' => 'Partida'];
        $this->addGridView($master, $detail, 'accounting-entry', 'fas fa-balance-scale');
        $this->views['EditAsiento']->template = 'EditAsiento.html.twig';
        $this->setTabsPosition('bottom');
    }

    /**
     * 
     * @param string $action
     */
    protected function execAfterAction($action)
    {
        if ($action === 'save-ok' && false === $this->getModel()->isBalanced()) {
            $this->toolBox()->i18nLog()->warning('mismatched-accounting-entry');
            return;
        }

        parent::execAfterAction($action);
    }

    /**
     * Run the actions that alter data before reading it
     *
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        switch ($action) {
            case 'account-data':
                $this->getAccountData();
                return false;

            case 'clone':
                return $this->cloneDocument();

            case 'lock':
                $this->changeLockStatus();
                return true;

            case 'recalculate-document':
                $this->recalculateDocument();
                return false;
        }

        return parent::execPreviousAction($action);
    }

    /**
     * Get account data from request data
     */
    private function getAccountData()
    {
        $this->setTemplate(false);
        $subaccount = $this->request->get('codsubcuenta', '');
        $exercise = $this->request->get('codejercicio', '');
        $channel = $this->request->get('canal', 0);

        $tools = new AccountingEntryTools();
        $data = $tools->getAccountData($exercise, $subaccount, $channel);
        $this->response->setContent(\json_encode($data));
    }

    /**
     * Recalculate document amounts
     */
    private function recalculateDocument()
    {
        $this->setTemplate(false);
        $data = $this->request->request->all();

        $tools = new AccountingEntryTools();
        $this->response->setContent(
            \json_encode($tools->recalculate($this->views['EditAsiento'], $data))
        );
    }

    /**
     * Replace concept in concepts array with macro values
     *
     * @param array array
     *
     * @return array
     */
    protected function replaceConcept($results): array
    {
        $finalResults = [];
        $idasiento = $this->request->get('code');
        $accounting = new Asiento();
        $where = [new DataBaseWhere('idasiento', $idasiento)];

        if ($accounting->loadFromCode('', $where)) {
            $search = ['%document%', '%date%', '%date-entry%', '%month%'];
            $replace = [
                $accounting->documento,
                \date(Asiento::DATE_STYLE),
                $accounting->fecha,
                $this->toolBox()->i18n()->trans(\date('F', \strtotime($accounting->fecha)))
            ];
            foreach ($results as $result) {
                $finalValue = [
                    'key' => \str_replace($search, $replace, $result['key']),
                    'value' => $result['value']
                ];
                $finalResults[] = $finalValue;
            }
        }

        return $finalResults;
    }
}
