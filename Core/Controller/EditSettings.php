<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\FileManager;
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
     * Return a list of all XML settings files on XMLView folder.
     *
     * @return array
     */
    private function allSettingsXMLViews()
    {
        $names = [];
        foreach (FileManager::scanFolder(FS_FOLDER . '/Dinamic/XMLView') as $fileName) {
            if (0 === strpos($fileName, self::KEY_SETTINGS)) {
                $names[] = substr($fileName, 0, -4);
            }
        }

        return $names;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
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
        }
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
     * Exports data from views.
     */
    private function exportAction()
    {
        $this->exportManager->newDoc($this->request->get('option'));
        foreach ($this->views as $view) {
            if ($view->model === null || !isset($view->model->properties)) {
                continue;
            }

            $headers = ['key' => 'key', 'value' => 'value'];
            $rows = [];
            foreach ($view->model->properties as $key => $value) {
                $rows[] = ['key' => $key, 'value' => $value];
            }

            if (count($rows) > 0) {
                $this->exportManager->generateTablePage($headers, $rows);
            }
        }

        $this->exportManager->show($this->response);
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
        if (empty($view->model)) {
            return;
        }

        $code = $this->getKeyFromViewName($viewName);
        $view->loadData($code);

        if ($view->model->name === null) {
            $view->model->description = $view->model->name = strtolower(substr($viewName, 8));
            $view->model->save();
        }
    }
}
