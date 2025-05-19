<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Tools;
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
        $data['icon'] = 'fa-solid fa-tools';
        return $data;
    }

    protected function checkPaymentMethod(): bool
    {
        $idempresa = Tools::settings('default', 'idempresa');
        $where = [new DataBaseWhere('idempresa', $idempresa)];
        $values = $this->codeModel->all('formaspago', 'codpago', 'descripcion', false, $where);
        foreach ($values as $value) {
            if ($value->code == Tools::settings('default', 'codpago')) {
                // perfect
                return true;
            }
        }

        // assign a new payment method
        foreach ($values as $value) {
            Tools::settingsSet('default', 'codpago', $value->code);
            Tools::settingsSave();
            return true;
        }

        // assign no payment method
        Tools::settingsSet('default', 'codpago', null);
        Tools::settingsSave();
        return false;
    }

    protected function checkWarehouse(): bool
    {
        $idempresa = Tools::settings('default', 'idempresa');
        $where = [new DataBaseWhere('idempresa', $idempresa)];
        $values = $this->codeModel->all('almacenes', 'codalmacen', 'nombre', false, $where);
        foreach ($values as $value) {
            if ($value->code == Tools::settings('default', 'codalmacen')) {
                // perfect
                return true;
            }
        }

        // assign a new warehouse
        foreach ($values as $value) {
            Tools::settingsSet('default', 'codalmacen', $value->code);
            Tools::settingsSave();
            return true;
        }

        // assign no warehouse
        Tools::settingsSet('default', 'codalmacen', null);
        Tools::settingsSave();
        return false;
    }

    protected function checkTax(): bool
    {
        // find current default tax
        $taxModel = new Impuesto();
        $codimpuesto = Tools::settings('default', 'codimpuesto');
        if ($taxModel->loadFromCode($codimpuesto)) {
            return true;
        }

        // assign no tax
        Tools::settingsSet('default', 'codimpuesto', null);
        Tools::settingsSave();
        return false;
    }

    protected function createDocTypeFilter(string $viewName): void
    {
        $types = $this->codeModel->all('estados_documentos', 'tipodoc', 'tipodoc');

        // custom translation
        foreach ($types as $key => $value) {
            if (!empty($value->code)) {
                $value->description = Tools::lang()->trans($value->code);
            }
        }

        $this->listView($viewName)->addFilterSelect('tipodoc', 'doc-type', 'tipodoc', $types);
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

    protected function createViewsApiKeys(string $viewName = 'ListApiKey'): void
    {
        $this->addListView($viewName, 'ApiKey', 'api-keys', 'fa-solid fa-key')
            ->addOrderBy(['id'], 'id')
            ->addOrderBy(['descripcion'], 'description')
            ->addOrderBy(['creationdate', 'id'], 'date', 2)
            ->addSearchFields(['description', 'apikey', 'nick']);
    }

    protected function createViewsIdFiscal(string $viewName = 'EditIdentificadorFiscal'): void
    {
        $this->addEditListView($viewName, 'IdentificadorFiscal', 'fiscal-id', 'far fa-id-card')
            ->setInLine(true);
    }

    protected function createViewFormats(string $viewName = 'ListFormatoDocumento'): void
    {
        $this->addListView($viewName, 'FormatoDocumento', 'printing-formats', 'fa-solid fa-print')
            ->addOrderBy(['nombre'], 'name')
            ->addOrderBy(['titulo'], 'title')
            ->addSearchFields(['nombre', 'titulo', 'texto']);

        // Filters
        $this->createDocTypeFilter($viewName);
        $this->listView($viewName)
            ->addFilterSelect('idempresa', 'company', 'idempresa', Empresas::codeModel())
            ->addFilterSelect('codserie', 'serie', 'codserie', Series::codeModel());
    }

    /**
     * Add a view settings.
     *
     * @param string $name
     * @param string $model
     * @param string $icon
     */
    protected function createViewsSettings(string $name, string $model, string $icon): void
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

    protected function createViewSequences(string $viewName = 'ListSecuenciaDocumento'): void
    {
        $this->addListView($viewName, 'SecuenciaDocumento', 'sequences', 'fa-solid fa-code')
            ->addOrderBy(['codejercicio', 'codserie', 'tipodoc'], 'exercise')
            ->addOrderBy(['codserie'], 'serie')
            ->addOrderBy(['numero'], 'number')
            ->addOrderBy(['tipodoc', 'codejercicio', 'codserie'], 'doc-type', 1)
            ->addSearchFields(['patron', 'tipodoc']);

        // disable company column if there is only one company
        if ($this->empresa->count() < 2) {
            $this->views[$viewName]->disableColumn('company');
        } else {
            // Filters with various companies
            $this->listView($viewName)->addFilterSelect('idempresa', 'company', 'idempresa', Empresas::codeModel());
        }

        // Filters
        $this->listView($viewName)
            ->addFilterSelect('codejercicio', 'exercise', 'codejercicio', Ejercicios::codeModel())
            ->addFilterSelect('codserie', 'serie', 'codserie', Series::codeModel());
        $this->createDocTypeFilter($viewName);
    }

    protected function createViewStates(string $viewName = 'ListEstadoDocumento'): void
    {
        $this->addListView($viewName, 'EstadoDocumento', 'states', 'fa-solid fa-tags')
            ->addOrderBy(['idestado'], 'id')
            ->addOrderBy(['nombre'], 'name')
            ->addSearchFields(['nombre']);

        // Filters
        $this->createDocTypeFilter($viewName);
        $this->listView($viewName)
            ->addFilterSelect('actualizastock', 'update-stock', 'actualizastock', [
                ['code' => null, 'description' => '------'],
                ['code' => -2, 'description' => Tools::lang()->trans('book')],
                ['code' => -1, 'description' => Tools::lang()->trans('subtract')],
                ['code' => 0, 'description' => Tools::lang()->trans('do-nothing')],
                ['code' => 1, 'description' => Tools::lang()->trans('add')],
                ['code' => 2, 'description' => Tools::lang()->trans('foresee')],
            ])
            ->addFilterCheckbox('predeterminado', 'default', 'predeterminado')
            ->addFilterCheckbox('editable', 'editable', 'editable');
    }

    protected function editAction(): bool
    {
        if (false === parent::editAction()) {
            return false;
        }

        Tools::settingsClear();

        // check relations
        $this->checkPaymentMethod();
        $this->checkWarehouse();
        $this->checkTax();

        // check site_url
        $siteUrl = Tools::settings('default', 'site_url');
        if (empty($siteUrl)) {
            Tools::settingsSet('default', 'site_url', Tools::siteUrl());
            Tools::settingsSave();
        }

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
                if (false === (bool)Tools::settings('default', 'enable_api', '0')) {
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
                $this->loadSerie($viewName);
                $this->loadSerieRectifying($viewName);
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

    protected function loadLogoImageValues($viewName): void
    {
        $columnLogo = $this->views[$viewName]->columnForName('login-image');
        if ($columnLogo && $columnLogo->widget->getType() === 'select') {
            $images = $this->codeModel->all('attached_files', 'idfile', 'filename', true, [
                new DataBaseWhere('mimetype', 'image/gif,image/jpeg,image/png', 'IN')
            ]);
            $columnLogo->widget->setValuesFromCodeModel($images);
        }
    }

    protected function loadPaymentMethodValues(string $viewName): void
    {
        $idempresa = Tools::settings('default', 'idempresa');
        $where = [new DataBaseWhere('idempresa', $idempresa)];
        $methods = $this->codeModel->all('formaspago', 'codpago', 'descripcion', false, $where);

        $columnPayment = $this->views[$viewName]->columnForName('payment-method');
        if ($columnPayment && $columnPayment->widget->getType() === 'select') {
            $columnPayment->widget->setValuesFromCodeModel($methods);
        }
    }

    protected function loadSerie(string $viewName): void
    {
        $columnSerie = $this->views[$viewName]->columnForName('serie');
        if ($columnSerie && $columnSerie->widget->getType() === 'select') {
            $series = $this->codeModel->all('series', 'codserie', 'descripcion', false, [
                new DataBaseWhere('tipo', 'R', '!='),
                new DataBaseWhere('tipo', null, '=', 'OR')
            ]);
            $columnSerie->widget->setValuesFromCodeModel($series);
        }
    }

    protected function loadSerieRectifying(string $viewName): void
    {
        $columnSerie = $this->views[$viewName]->columnForName('rectifying-serie');
        if ($columnSerie && $columnSerie->widget->getType() === 'select') {
            $series = $this->codeModel->all('series', 'codserie', 'descripcion', false, [
                new DataBaseWhere('tipo', 'R')
            ]);
            $columnSerie->widget->setValuesFromCodeModel($series);
        }
    }

    protected function loadWarehouseValues(string $viewName): void
    {
        $idempresa = Tools::settings('default', 'idempresa');
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
        foreach (Tools::folderScan(FS_FOLDER . '/Dinamic/XMLView') as $fileName) {
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
