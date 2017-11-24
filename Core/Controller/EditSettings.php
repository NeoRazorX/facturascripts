<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\ExtendedController;
use FacturaScripts\Core\Lib\EmailTools;

/**
 * Controller to edit main settings
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class EditSettings extends ExtendedController\PanelController
{

    const KEYSETTINGS = 'Settings';

    protected function execAfterAction($view, $action)
    {
        if ($action === 'testmail') {
            $emailTools = new EmailTools();
            if ($emailTools->test()) {
                $this->miniLog->info($this->i18n->trans('mail-test-ok'));
            } else {
                $this->miniLog->error($this->i18n->trans('mail-test-error'));
            }
        } else {
            parent::execAfterAction($view, $action);
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

        return $pagedata;
    }

    /**
     * Returns the url for a specified $type
     *
     * @param string $type
     * @return string
     */
    public function getURL($type)
    {
        $result = 'index.php';
        switch ($type) {
            case 'list':
                $result .= '?page=AdminHome';
                break;

            case 'edit':
                $result .= '?page=EditSettings';
                break;
        }

        return $result;
    }

    /**
     * Returns the configuration property value for a specified $field
     *
     * @param mixed $model
     * @param string $field
     * @return mixed
     */
    public function getFieldValue($model, $field)
    {
        $properties = parent::getFieldValue($model, 'properties');
        if (is_array($properties) && array_key_exists($field, $properties)) {
            return $properties[$field];
        }

        if (isset($model->{$field})) {
            return $model->{$field};
        }

        return null;
    }

    /**
     * Returns the view id for a specified $viewName
     *
     * @param string $viewName
     * @return string
     */
    private function getKeyFromViewName($viewName)
    {
        return strtolower(substr($viewName, strlen(self::KEYSETTINGS)));
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $modelName = 'FacturaScripts\Core\Model\Settings';
        $icon = $this->getPageData()['icon'];
        foreach ($this->allSettingsXMLViews() as $name) {
            $title = substr($name, 8);
            $this->addEditView($modelName, $name, $title, $icon);
        }

        $title2 = 'about';
        $this->addHtmlView('Block/About.html', null, 'about', $title2);
    }

    /**
     * Load view data
     *
     * @param string $keyView
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
            $model->name = substr(strtolower($keyView), 8);
            $model->save();
        }
    }

    private function allSettingsXMLViews()
    {
        $names = [];
        foreach (scandir(FS_FOLDER . '/Dinamic/XMLView', SCANDIR_SORT_ASCENDING) as $fileName) {
            if ($fileName != '.' && $fileName != '..' && substr($fileName, 0, 8) == self::KEYSETTINGS) {
                $names[] = substr($fileName, 0, -4);
            }
        }

        return $names;
    }
}
