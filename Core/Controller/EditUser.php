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
use FacturaScripts\Core\Base\DataBase;

/**
 * Description of EditUser
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class EditUser extends ExtendedController\PanelController
{

    /**
     * Devuelve un array de idiomas, donde la key es el nombre del archivo JSON y
     * el value es su correspondiente traducción.
     *
     * @return array
     */
    private function getLanguages()
    {
        $languages = [];
        $dir = __DIR__ . '/../Translation';
        foreach (scandir($dir, SCANDIR_SORT_ASCENDING) as $fileName) {
            if ($fileName !== '.' && $fileName !== '..' && !is_dir($fileName) && substr($fileName, -5) === '.json') {
                $languages[] = [
                    'value' => substr($fileName, 0, -5),
                    'title' => $this->i18n->trans('languages-' . substr($fileName, 0, -5))
                ];
            }
        }

        return $languages;
    }

    /**
     * Procedimiento para insertar vistas en el controlador
     */
    protected function createViews()
    {
        /// Add all views
        $this->addEditView('FacturaScripts\Core\Model\User', 'EditUser', 'user', 'fa-user');
        $this->addEditListView('FacturaScripts\Core\Model\RolUser', 'EditRolUser', 'rol-user', 'fa-address-card-o');
        $this->addListView('FacturaScripts\Core\Model\PageRule', 'ListPageRule', 'page-rule', 'fa fa-check-square');

        /// Load values option to Language select input
        $columnLangCode = $this->views['EditUser']->columnForName('lang-code');
        $langs = $this->getLanguages();
        $columnLangCode->widget->setValuesFromArray($langs);

        /// Disable columns
        $this->views['EditRolUser']->disableColumn('nick', TRUE);
        $this->views['ListPageRule']->disableColumn('nick', TRUE);
    }

    /**
     * Devuele el campo $fieldName del modelo User
     *
     * @param string $fieldName
     *
     * @return string|boolean
     */
    private function getUserFieldValue($fieldName)
    {
        $model = $this->views['EditUser']->getModel();
        return $model->{$fieldName};
    }

    /**
     * Procedimiento encargado de cargar los datos a visualizar
     *
     * @param string $keyView
     * @param ExtendedController\EditView $view
     */
    protected function loadData($keyView, $view)
    {
        switch ($keyView) {
            case 'EditUser':
                $value = $this->request->get('code');
                $view->loadData($value);
                break;

            case 'EditRolUser':
                $where = [new DataBase\DataBaseWhere('nick', $this->getUserFieldValue('nick'))];
                $view->loadData($where);
                break;

            case 'ListPageRule':
                $where = [new DataBase\DataBaseWhere('nick', $this->getUserFieldValue('nick'))];
                $view->loadData($where);
                break;
        }
    }

    /**
     * Devuelve los datos básicos de la página
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'user';
        $pagedata['icon'] = 'fa-user';
        $pagedata['menu'] = 'admin';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }
}
