<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\FileManager;
use FacturaScripts\Core\Lib\ExtendedController;
use FacturaScripts\Dinamic\Lib\EmailTools;
use FacturaScripts\Dinamic\Model\CodeModel;

/**
 * Controller to edit main settings
 *
 * @author Artex Trading sa     <jcuello@artextrading.com>
 * @author Carlos Garcia Gomez  <carlos@facturascripts.com>
 */
class EditSettings extends ExtendedController\PanelController
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
        $data['submenu'] = 'control-panel';
        $data['title'] = 'app-preferences';
        $data['icon'] = 'fas fa-cogs';
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
        foreach (FileManager::scanFolder(\FS_FOLDER . '/Dinamic/XMLView') as $fileName) {
            if (0 === strpos($fileName, self::KEY_SETTINGS)) {
                $names[] = substr($fileName, 0, -4);
            }
        }

        return $names;
    }

    /**
     * 
     * @return bool
     */
    protected function checkPaymentMethod()
    {
        $appSettings = new AppSettings();
        $appSettings->reload();

        $idempresa = $appSettings->get('default', 'idempresa');
        $where = [new DataBaseWhere('idempresa', $idempresa)];
        $values = CodeModel::all('formaspago', 'codpago', 'descripcion', false, $where);
        foreach ($values as $value) {
            if ($value->code == $appSettings->get('default', 'codpago')) {
                /// perfect
                return true;
            }
        }

        /// assign a new payment method
        foreach ($values as $value) {
            $appSettings->set('default', 'codpago', $value->code);
            $appSettings->save();
            return true;
        }

        /// assign no payment method
        $appSettings->set('default', 'codpago', null);
        $appSettings->save();
        return false;
    }

    /**
     * 
     * @return bool
     */
    protected function checkWarehouse()
    {
        $appSettings = new AppSettings();
        $appSettings->reload();

        $idempresa = $appSettings->get('default', 'idempresa');
        $where = [new DataBaseWhere('idempresa', $idempresa)];
        $values = CodeModel::all('almacenes', 'codalmacen', 'nombre', false, $where);
        foreach ($values as $value) {
            if ($value->code == $appSettings->get('default', 'codalmacen')) {
                /// perfect
                return true;
            }
        }

        /// assign a new warehouse
        foreach ($values as $value) {
            $appSettings->set('default', 'codalmacen', $value->code);
            $appSettings->save();
            return true;
        }

        /// assign no warehouse
        $appSettings->set('default', 'codalmacen', null);
        $appSettings->save();
        return false;
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

            /// change icon
            $groups = $this->views[$name]->getColumns();
            foreach ($groups as $group) {
                if (!empty($group->icon)) {
                    $this->views[$name]->icon = $group->icon;
                    break;
                }
            }

            /// disable delete
            $this->setSettings($name, 'btnDelete', false);
        }
    }

    /**
     * 
     * @return bool
     */
    protected function editAction()
    {
        if (!parent::editAction()) {
            return false;
        }

        /// check warehouse-company and payment-method-company relations
        $this->checkPaymentMethod();
        $this->checkWarehouse();
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
                $emailTools = new EmailTools();
                if ($this->editAction() && $emailTools->test()) {
                    $this->miniLog->info($this->i18n->trans('mail-test-ok'));
                } else {
                    $this->miniLog->error($this->i18n->trans('mail-test-error'));
                }
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
        return strtolower(substr($viewName, strlen(self::KEY_SETTINGS)));
    }

    /**
     * Load view data
     *
     * @param string                      $viewName
     * @param ExtendedController\EditView $view
     */
    protected function loadData($viewName, $view)
    {
        $code = $this->getKeyFromViewName($viewName);
        $view->loadData($code);
        if (empty($view->model->name)) {
            $view->model->name = $code;
        }

        switch ($viewName) {
            case 'SettingsDefault':
                $this->loadPaymentMethodValues($viewName);
                $this->loadWarehouseValues($viewName);
                break;
        }
    }

    /**
     * 
     * @param string $viewName
     */
    protected function loadPaymentMethodValues($viewName)
    {
        $idempresa = AppSettings::get('default', 'idempresa');
        $where = [new DataBaseWhere('idempresa', $idempresa)];
        $methods = CodeModel::all('formaspago', 'codpago', 'descripcion', false, $where);

        $columnPayment = $this->views[$viewName]->columnForName('payment-method');
        if ($columnPayment) {
            $columnPayment->widget->setValuesFromCodeModel($methods);
        }
    }

    /**
     * 
     * @param string $viewName
     */
    protected function loadWarehouseValues($viewName)
    {
        $idempresa = AppSettings::get('default', 'idempresa');
        $where = [new DataBaseWhere('idempresa', $idempresa)];
        $almacenes = CodeModel::all('almacenes', 'codalmacen', 'nombre', false, $where);

        $columnWarehouse = $this->views[$viewName]->columnForName('warehouse');
        if ($columnWarehouse) {
            $columnWarehouse->widget->setValuesFromCodeModel($almacenes);
        }
    }
}
