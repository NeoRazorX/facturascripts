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

use FacturaScripts\Core\Lib\ExtendedController;
use FacturaScripts\Core\Lib\EmailTools;

/**
 * Controller to edit main settings
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class EditSettings extends ExtendedController\PanelController
{
    const KEY_SETTINGS = 'Settings';

    /**
     * Load views
     */
    protected function createViews()
    {
        $modelName = '\FacturaScripts\Dinamic\Model\Settings';
        $icon = $this->getPageData()['icon'];
        foreach ($this->allSettingsXMLViews() as $name) {
            $title = strtolower(substr($name, 8));
            $this->addEditView($modelName, $name, $title, $icon);
        }

        $this->addHtmlView('Block/About.html', null, 'about', 'about');
        $this->testViews();
    }

    /**
     * Load view data
     *
     * @param string                      $keyView
     * @param ExtendedController\EditView $view
     */
    protected function loadData($keyView, $view)
    {
        if (empty($view->getModel())) {
            return;
        }

        $code = $this->getKeyFromViewName($keyView);
        $view->loadData($code);

        $model = $view->getModel();
        if ($model->name === null) {
            $model->name = strtolower(substr($keyView, 8));
            $model->save();
        }
    }

    /**
     * Run the controller after actions
     *
     * @param ExtendedController\EditView $view
     * @param string                      $action
     */
    protected function execAfterAction($view, $action)
    {
        switch ($action) {
            case 'export':
                $this->setTemplate(false);
                $this->exportAction();
                break;

            case 'testmail':
                $emailTools = new EmailTools();
                if ($emailTools->test()) {
                    $this->miniLog->info($this->i18n->trans('mail-test-ok'));
                } else {
                    $this->miniLog->error($this->i18n->trans('mail-test-error'));
                }
                break;
        }
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'app-preferences';
        $pagedata['icon'] = 'fa-cogs';
        $pagedata['menu'] = 'admin';
        $pagedata['submenu'] = 'control-panel';

        return $pagedata;
    }

    /**
     * Returns the url for a specified $type
     *
     * @param string $type
     *
     * @return string
     */
    public function getURL($type)
    {
        switch ($type) {
            case 'list':
                return 'AdminPlugins';

            case 'edit':
                return 'EditSettings';
        }

        return FS_ROUTE;
    }

    /**
     * Returns the configuration property value for a specified $field
     *
     * @param mixed  $model
     * @param string $field
     *
     * @return mixed
     */
    public function getFieldValue($model, $field)
    {
        $value = parent::getFieldValue($model, $field);
        if (isset($value)) {
            return $value;
        }

        if (is_array($model->properties) && array_key_exists($field, $model->properties)) {
            return $model->properties[$field];
        }

        return null;
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
     * Return a list of all XML view files on XMLView folder.
     *
     * @return array
     */
    private function allSettingsXMLViews()
    {
        $names = [];
        $files = array_diff(scandir(FS_FOLDER . '/Dinamic/XMLView', SCANDIR_SORT_ASCENDING), ['.', '..']);
        foreach ($files as $fileName) {
            if (0 === strpos($fileName, self::KEY_SETTINGS)) {
                $names[] = substr($fileName, 0, -4);
            }
        }

        return $names;
    }

    /**
     * Test all view to show usefull errors.
     */
    private function testViews()
    {
        foreach ($this->views as $viewName => $view) {
            if (!$view->getModel()) {
                continue;
            }

            $error = true;
            foreach ($view->getColumns() as $group) {
                if (!isset($group->columns)) {
                    break;
                }

                foreach ($group->columns as $col) {
                    if ($col->name === 'name') {
                        $error = false;
                        break;
                    }
                }

                break;
            }

            if ($error) {
                $this->miniLog->critical($this->i18n->trans('error-no-name-in-settings', ['%viewName%' => $viewName]));
            }
        }
    }

    /**
     * Exports data from views.
     */
    private function exportAction()
    {
        $this->exportManager->newDoc($this->request->get('option'));
        foreach ($this->views as $view) {
            $model = $view->getModel();
            if ($model === null || !isset($model->properties)) {
                continue;
            }

            $headers = ['key' => 'key', 'value' => 'value'];
            $rows = [];
            foreach ($model->properties as $key => $value) {
                $rows[] = ['key' => $key, 'value' => $value];
            }

            if (count($rows) > 0) {
                $this->exportManager->generateTablePage($headers, $rows);
            }
        }

        $this->exportManager->show($this->response);
    }
}
