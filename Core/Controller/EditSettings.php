<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\DataSrc\Ejercicios;
use FacturaScripts\Core\DataSrc\Empresas;
use FacturaScripts\Core\DataSrc\Series;
use FacturaScripts\Core\Lib\ExtendedController\EditView;
use FacturaScripts\Core\Lib\ExtendedController\PanelController;
use FacturaScripts\Core\Model\Settings;
use FacturaScripts\Dinamic\Model\Impuesto;

/**
 * Controller to edit main settings
 *
 * @author Jose Antonio Cuello  <yopli2000@gmail.com>
 * @author Carlos Garcia Gomez  <carlos@facturascripts.com>
 */
class EditSettings extends PanelController
{
    const KEY_SETTINGS = 'Settings';

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'control-panel';
        $data['icon'] = 'fas fa-tools';
        return $data;
    }

    protected function checkPaymentMethod(): bool
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

    protected function checkWarehouse(): bool
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

    protected function checkTax(): bool
    {
        $appSettings = $this->toolBox()->appSettings();

        // find current default tax
        $taxModel = new Impuesto();
        $codimpuesto = $appSettings->get('default', 'codimpuesto');
        if ($taxModel->loadFromCode($codimpuesto)) {
            return true;
        }

        // assign no tax
        $appSettings->set('default', 'codimpuesto', null);
        $appSettings->save();
        return false;
    }

    protected function createDocTypeFilter(string $viewName)
    {
        $types = $this->codeModel->all('estados_documentos', 'tipodoc', 'tipodoc');

        // custom translation
        foreach ($types as $key => $value) {
            if (!empty($value->code)) {
                $value->description = $this->toolBox()->i18n()->trans($value->code);
            }
        }

        $this->views[$viewName]->addFilterSelect('tipodoc', 'doc-type', 'tipodoc', $types);
    }

    /**
     * Load views
     *
     * Put the Default first in the list.
     * Then we process all the views that start with Settings.
     */
    protected function createViews()
    {
        $this->setTemplate('EditSettings');

        // añadimos una pestaña para cada archivo SettingsXXX
        $modelName = 'Settings';
        $icon = $this->getPageData()['icon'];
        $this->createViewsSettings('SettingsDefault', $modelName, $icon);
        foreach ($this->allSettingsXMLViews() as $name) {
            if ($name != 'SettingsDefault') {
                $this->createViewsSettings($name, $modelName, $icon);
            }
        }

        // Añadimos el resto de pestañas
        $this->createViewsApiKeys();
        $this->createViewsIdFiscal();
        $this->createViewSequences();
        $this->createViewStates();
        $this->createViewFormats();
    }

    protected function createViewsApiKeys(string $viewName = 'ListApiKey')
    {
        $this->addListView($viewName, 'ApiKey', 'api-keys', 'fas fa-key');
        $this->views[$viewName]->addOrderBy(['id'], 'id');
        $this->views[$viewName]->addOrderBy(['descripcion'], 'description');
        $this->views[$viewName]->addOrderBy(['creationdate', 'id'], 'date', 2);
        $this->views[$viewName]->addSearchFields(['description', 'apikey', 'nick']);
    }

    protected function createViewsIdFiscal(string $viewName = 'EditIdentificadorFiscal')
    {
        $this->addEditListView($viewName, 'IdentificadorFiscal', 'fiscal-id', 'far fa-id-card');
        $this->views[$viewName]->setInLine(true);
    }

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
     * Add a view settings.
     *
     * @param string $name
     * @param string $model
     * @param string $icon
     */
    protected function createViewsSettings(string $name, string $model, string $icon)
    {
        $title = $this->getKeyFromViewName($name);
        $this->addEditView($name, $model, $title, $icon);

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

    protected function createViewSequences(string $viewName = 'ListSecuenciaDocumento')
    {
        $this->addListView($viewName, 'SecuenciaDocumento', 'sequences', 'fas fa-code');
        $this->views[$viewName]->addOrderBy(['codejercicio', 'codserie', 'tipodoc'], 'exercise', 2);
        $this->views[$viewName]->addOrderBy(['codserie'], 'serie');
        $this->views[$viewName]->addOrderBy(['numero'], 'number');
        $this->views[$viewName]->addSearchFields(['patron', 'tipodoc']);

        // disable company column if there is only one company
        if ($this->empresa->count() < 2) {
            $this->views[$viewName]->disableColumn('company');
        } else {
            // Filters with various companies
            $this->views[$viewName]->addFilterSelect('idempresa', 'company', 'idempresa', Empresas::codeModel());
        }

        // Filters
        $this->views[$viewName]->addFilterSelect('codejercicio', 'exercise', 'codejercicio', Ejercicios::codeModel());
        $this->views[$viewName]->addFilterSelect('codserie', 'serie', 'codserie', Series::codeModel());
        $this->createDocTypeFilter($viewName);
    }

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

    protected function editAction(): bool
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

    protected function exportAction()
    {
        // do nothing
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
                if ($view->model instanceof Settings && empty($view->model->name)) {
                    $view->model->name = $code;
                }
                $this->loadPaymentMethodValues($viewName);
                $this->loadWarehouseValues($viewName);
                $this->loadLogoImageValues($viewName);
                break;

            default:
                $code = $this->getKeyFromViewName($viewName);
                $view->loadData($code);
                if ($view->model instanceof Settings && empty($view->model->name)) {
                    $view->model->name = $code;
                }
                break;
        }
    }

    protected function loadLogoImageValues($viewName)
    {
        $columnLogo = $this->views[$viewName]->columnForName('login-image');
        if ($columnLogo && $columnLogo->widget->getType() === 'select') {
            $images = $this->codeModel->all('attached_files', 'idfile', 'filename', true, [
                new DataBaseWhere('mimetype', 'image/gif,image/jpeg,image/png', 'IN')
            ]);
            $columnLogo->widget->setValuesFromCodeModel($images);
        }
    }

    protected function loadPaymentMethodValues(string $viewName)
    {
        $idempresa = $this->toolBox()->appSettings()->get('default', 'idempresa');
        $where = [new DataBaseWhere('idempresa', $idempresa)];
        $methods = $this->codeModel->all('formaspago', 'codpago', 'descripcion', false, $where);

        $columnPayment = $this->views[$viewName]->columnForName('payment-method');
        if ($columnPayment && $columnPayment->widget->getType() === 'select') {
            $columnPayment->widget->setValuesFromCodeModel($methods);
        }
    }

    protected function loadWarehouseValues(string $viewName)
    {
        $idempresa = $this->toolBox()->appSettings()->get('default', 'idempresa');
        $where = [new DataBaseWhere('idempresa', $idempresa)];
        $almacenes = $this->codeModel->all('almacenes', 'codalmacen', 'nombre', false, $where);

        $columnWarehouse = $this->views[$viewName]->columnForName('warehouse');
        if ($columnWarehouse && $columnWarehouse->widget->getType() === 'select') {
            $columnWarehouse->widget->setValuesFromCodeModel($almacenes);
        }
    }

    /**
     * Return a list of all XML settings files on XMLView folder.
     *
     * @return array
     */
    private function allSettingsXMLViews(): array
    {
        $names = [];
        foreach ($this->toolBox()->files()->scanFolder(FS_FOLDER . '/Dinamic/XMLView') as $fileName) {
            if (0 === strpos($fileName, self::KEY_SETTINGS)) {
                $names[] = substr($fileName, 0, -4);
            }
        }

        return $names;
    }

    /**
     * Returns the view id for a specified $viewName
     *
     * @param string $viewName
     *
     * @return string
     */
    private function getKeyFromViewName(string $viewName): string
    {
        return strtolower(substr($viewName, strlen(self::KEY_SETTINGS)));
    }
}
