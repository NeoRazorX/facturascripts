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
use FacturaScripts\Core\Model;
use FacturaScripts\Core\Base\DataBase;

/**
 * Description of EditSettings
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class EditSettings extends ExtendedController\PanelController
{

    const KEYSETTINGS = 'Settings';

    /**
     * Devuelve los datos básicos de la página
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'app-preferences';
        $pagedata['icon'] = 'fa-cogs';
        $pagedata['menu'] = 'admin';
        $pagedata['orden'] = '999';

        return $pagedata;
    }

    /**
     * Devuelve la url para el tipo indicado
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
     * Devuelve el valor para la propiedad de configuración
     *
     * @param mixed $model
     * @param string $field
     * @return mixed
     */
    public function getFieldValue($model, $field)
    {
        $properties = parent::getFieldValue($model, 'properties');
        if (array_key_exists($field, $properties)) {
            return $properties[$field];
        }

        return $model->{$field};
    }

    /**
     * Devuelve el id de la vista con el valor de la constante KEYSSETTINGS
     * como prefijo
     *
     * @param string $key
     * @return string
     */
    private function getViewNameFromKey($key)
    {
        return self::KEYSETTINGS . ucfirst($key);
    }

    /**
     * Devuelve el id de la vista
     *
     * @param string $viewName
     * @return string
     */
    private function getKeyFromViewName($viewName)
    {
        return strtolower(substr($viewName, strlen(self::KEYSETTINGS)));
    }

    /**
     * Procedimiento para insertar vistas en el controlador
     */
    protected function createViews()
    {
        $modelName = 'FacturaScripts\Core\Model\Settings';
        $title = 'general';
        $icon = $this->getPageData()['icon'];
        $this->addEditView($modelName, $this->getViewNameFromKey('Default'), $title, $icon);

        $model = new Model\Settings();
        $where = [new DataBase\DataBaseWhere('name', 'default', '<>')];
        $rows = $model->all($where, ['name' => 'ASC'], 0, 0);
        foreach ($rows as $setting) {
            $title = $setting->name;
            $viewName = $this->getViewNameFromKey($setting->name);
            $this->addEditView($modelName, $viewName, $title, $setting->icon);
        }

        $title2 = 'about';
        $this->addHtmlView('Block/About.html', null, 'about', $title2);
    }

    /**
     * Procedimiento para cargar los datos de cada una de las vistas
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
    }
}
