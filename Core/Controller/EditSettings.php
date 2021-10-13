<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\DataSrc\Empresas;
use FacturaScripts\Core\DataSrc\Series;
use FacturaScripts\Core\Lib\ExtendedController\EditView;
use FacturaScripts\Core\Lib\ExtendedController\PanelController;
use FacturaScripts\Dinamic\Lib\Email\NewMail;
use FacturaScripts\Dinamic\Model\Impuesto;

/**
 * Controller to edit main settings
 *
 * @author Artex Trading sa     <jcuello@artextrading.com>
 * @author Carlos Garcia Gomez  <carlos@facturascripts.com>
 */
class EditSettings extends PanelController
{

    const KEY_SETTINGS = 'Settings';

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'control-panel';
        $data['icon'] = 'fas fa-tools';
        return $data;
    }

    /**
     * Return a list of all XML settings files on XMLView folder.
     *
     * @return array
     */
    private function allSettingsXMLViews()
    {
        $names = [];
        foreach ($this->toolBox()->files()->scanFolder(\FS_FOLDER . '/Dinamic/XMLView') as $fileName) {
            if (0 === \strpos($fileName, self::KEY_SETTINGS)) {
                $names[] = \substr($fileName, 0, -4);
            }
        }

        return $names;
    }

    /**
     * @return bool
     */
    protected function checkPaymentMethod()
    {
        $appSettings = $this->toolBox()->appSettings();

        $idempresa = $appSettings->get('default', 'idempresa');
        $where = [new DataBaseWhere('idempresa', $idempresa)];
        $values = $this->codeModel->all('formaspago', 'codpago', 'descripcion', false, $where);
        foreach ($values as $value) {
            if ($value->code == $appSettings->get('default', 'codpago')) {
                // perfect
                return true;
            }
        }

        // assign a new payment method
        foreach ($values as $value) {
            $appSettings->set('default', 'codpago', $value->code);
            $appSettings->save();
            return true;
        }

        // assign no payment method
        $appSettings->set('default', 'codpago', null);
        $appSettings->save();
        return false;
    }

    /**
     * @return bool
     */
    protected function checkWarehouse()
    {
        $appSettings = $this->toolBox()->appSettings();

        $idempresa = $appSettings->get('default', 'idempresa');
        $where = [new DataBaseWhere('idempresa', $idempresa)];
        $values = $this->codeModel->all('almacenes', 'codalmacen', 'nombre', false, $where);
        foreach ($values as $value) {
            if ($value->code == $appSettings->get('default', 'codalmacen')) {
                // perfect
                return true;
            }
        }

        // assign a new warehouse
        foreach ($values as $value) {
            $appSettings->set('default', 'codalmacen', $value->code);
            $appSettings->save();
            return true;
        }

        // assign no warehouse
        $appSettings->set('default', 'codalmacen', null);
        $appSettings->save();
        return false;
    }

    /**
     * @return bool
     */
    protected function checkTax()
    {
        $appSettings = $this->toolBox()->appSettings();

        // find current default tax
        $taxModel = new Impuesto();
        $codimpuesto = $appSettings->get('default', 'codimpuesto');
        if ($taxModel->loadFromCode($codimpuesto)) {
            return true;
        }

        foreach ($taxModel->all() as $tax) {
            $appSettings->set('default', 'codimpuesto', $tax->codimpuesto);
            $appSettings->save();
            break;
        }

        return false;
    }

    /**
     * @param string $viewName
     */
    protected function createDocTypeFilter(string $viewName)
    {
        $types = $this->codeModel->all('estados_documentos', 'tipodoc', 'tipodoc');

        // custom translation
        foreach ($types as $key => $value) {
            if (!empty($value->code)) {
                $types[$key]->description = $this->toolBox()->i18n()->trans($value->code);
            }
        }

        $this->views[$viewName]->addFilterSelect('tipodoc', 'doc-type', 'tipodoc', $types);
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->setTemplate('EditSettings');

        $modelName = 'Settings';
        $icon = $this->getPageData()['icon'];
        foreach ($this->allSettingsXMLViews() as $name) {
            $title = $this->getKeyFromViewName($name);
            $this->addEditView($name, $modelName, $title, $icon);

            // change icon
            $groups = $this->views[$name]->getColumns();
            foreach ($groups as $group) {
                if (!empty($group->icon)) {
                    $this->views[$name]->icon = $group->icon;
                    break;
                }
            }

            // disable buttons
            $this->setSettings($name, 'btnDelete', false);
            $this->setSettings($name, 'btnNew', false);
        }

        $this->createViewsApiKeys();
        $this->createViewsIdFiscal();
        $this->createViewSequences();
        $this->createViewStates();
        $this->createViewFormats();
    }

    /**
     * @param string $viewName
     */
    protected function createViewsApiKeys(string $viewName = 'ListApiKey')
    {
        $this->addListView($viewName, 'ApiKey', 'api-keys', 'fas fa-key');
        $this->views[$viewName]->addOrderBy(['id'], 'id');
        $this->views[$viewName]->addOrderBy(['descripcion'], 'description');
        $this->views[$viewName]->addOrderBy(['creationdate', 'id'], 'date', 2);
        $this->views[$viewName]->addSearchFields(['description', 'apikey', 'nick']);
    }

    /**
     * @param string $viewName
     */
    protected function createViewsIdFiscal(string $viewName = 'EditIdentificadorFiscal')
    {
        $this->addEditListView($viewName, 'IdentificadorFiscal', 'fiscal-id', 'far fa-id-card');
        $this->views[$viewName]->setInLine(true);
    }

    /**
     * @param string $viewName
     */
    protected function createViewFormats(string $viewName = 'ListFormatoDocumento')
    {
        $this->addListView($viewName, 'FormatoDocumento', 'printing-formats', 'fas fa-print');
        $this->views[$viewName]->addOrderBy(['nombre'], 'name');
        $this->views[$viewName]->addOrderBy(['titulo'], 'title');
        $this->views[$viewName]->addSearchFields(['nombre', 'titulo', 'texto']);

        // Filters
        $this->createDocTypeFilter($viewName);
        $this->views[$viewName]->addFilterSelect('idempresa', 'company', 'idempresa', Empresas::codeModel());
        $this->views[$viewName]->addFilterSelect('codserie', 'serie', 'codserie', Series::codeModel());
    }

    /**
     * @param string $viewName
     */
    protected function createViewSequences(string $viewName = 'ListSecuenciaDocumento')
    {
        $this->addListView($viewName, 'SecuenciaDocumento', 'sequences', 'fas fa-code');
        $this->views[$viewName]->addOrderBy(['codejercicio', 'codserie', 'tipodoc'], 'exercise', 2);
        $this->views[$viewName]->addOrderBy(['codserie'], 'serie');
        $this->views[$viewName]->addOrderBy(['numero'], 'number');
        $this->views[$viewName]->addSearchFields(['patron', 'tipodoc']);

        // Filters
        $this->createDocTypeFilter($viewName);

        $this->views[$viewName]->addFilterSelect('idempresa', 'company', 'idempresa', Empresas::codeModel());

        $exercises = $this->codeModel->all('ejercicios', 'codejercicio', 'nombre');
        $this->views[$viewName]->addFilterSelect('codejercicio', 'exercise', 'codejercicio', $exercises);

        $this->views[$viewName]->addFilterSelect('codserie', 'serie', 'codserie', Series::codeModel());
    }

    /**
     * @param string $viewName
     */
    protected function createViewStates(string $viewName = 'ListEstadoDocumento')
    {
        $this->addListView($viewName, 'EstadoDocumento', 'states', 'fas fa-tags');
        $this->views[$viewName]->addOrderBy(['idestado'], 'id');
        $this->views[$viewName]->addOrderBy(['nombre'], 'name');
        $this->views[$viewName]->addSearchFields(['nombre']);

        // Filters
        $this->createDocTypeFilter($viewName);

        $this->views[$viewName]->addFilterSelect('actualizastock', 'update-stock', 'actualizastock', [
            ['code' => null, 'description' => '------'],
            ['code' => -2, 'description' => $this->toolBox()->i18n()->trans('book')],
            ['code' => -1, 'description' => $this->toolBox()->i18n()->trans('subtract')],
            ['code' => 0, 'description' => $this->toolBox()->i18n()->trans('do-nothing')],
            ['code' => 1, 'description' => $this->toolBox()->i18n()->trans('add')],
            ['code' => 2, 'description' => $this->toolBox()->i18n()->trans('foresee')],
        ]);
        $this->views[$viewName]->addFilterCheckbox('predeterminado', 'default', 'predeterminado');
        $this->views[$viewName]->addFilterCheckbox('editable', 'editable', 'editable');
    }

    /**
     * @return bool
     */
    protected function editAction()
    {
        if (false === parent::editAction()) {
            return false;
        }

        $this->toolBox()->appSettings()->reload();

        // check relations
        $this->checkPaymentMethod();
        $this->checkWarehouse();
        $this->checkTax();
        return true;
    }

    /**
     * Run the controller after actions
     *
     * @param string $action
     */
    protected function execAfterAction($action)
    {
        switch ($action) {
            case 'export':
                break;

            case 'testmail':
                if (false === $this->editAction()) {
                    break;
                }
                $email = new NewMail();
                if ($email->test()) {
                    $this->toolBox()->i18nLog()->notice('mail-test-ok');
                    break;
                }
                $this->toolBox()->i18nLog()->warning('mail-test-error');
                break;
        }
    }

    /**
     * Returns the view id for a specified $viewName
     *
     * @param string $viewName
     *
     * @return string
     */
    private function getKeyFromViewName($viewName)
    {
        return \strtolower(\substr($viewName, \strlen(self::KEY_SETTINGS)));
    }

    /**
     * Load view data
     *
     * @param string $viewName
     * @param EditView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'ListApiKey':
                $view->loadData();
                if (false === (bool)$this->toolBox()->appSettings()->get('default', 'enable_api', '0')) {
                    $this->setSettings($viewName, 'active', false);
                }
                break;

            case 'EditIdentificadorFiscal':
                $view->loadData();
                break;

            case 'SettingsDefault':
                $code = $this->getKeyFromViewName($viewName);
                $view->loadData($code);
                if (empty($view->model->name)) {
                    $view->model->name = $code;
                }
                $this->loadPaymentMethodValues($viewName);
                $this->loadWarehouseValues($viewName);
                break;

            default:
                $code = $this->getKeyFromViewName($viewName);
                $view->loadData($code);
                if (empty($view->model->name)) {
                    $view->model->name = $code;
                }
                break;
        }
    }

    /**
     * @param string $viewName
     */
    protected function loadPaymentMethodValues($viewName)
    {
        $idempresa = $this->toolBox()->appSettings()->get('default', 'idempresa');
        $where = [new DataBaseWhere('idempresa', $idempresa)];
        $methods = $this->codeModel->all('formaspago', 'codpago', 'descripcion', false, $where);

        $columnPayment = $this->views[$viewName]->columnForName('payment-method');
        if ($columnPayment && $columnPayment->widget->getType() === 'select') {
            $columnPayment->widget->setValuesFromCodeModel($methods);
        }
    }

    /**
     * @param string $viewName
     */
    protected function loadWarehouseValues($viewName)
    {
        $idempresa = $this->toolBox()->appSettings()->get('default', 'idempresa');
        $where = [new DataBaseWhere('idempresa', $idempresa)];
        $almacenes = $this->codeModel->all('almacenes', 'codalmacen', 'nombre', false, $where);

        $columnWarehouse = $this->views[$viewName]->columnForName('warehouse');
        if ($columnWarehouse && $columnWarehouse->widget->getType() === 'select') {
            $columnWarehouse->widget->setValuesFromCodeModel($almacenes);
        }
    }
}
