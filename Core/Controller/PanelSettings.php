<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\ExtendedController;
use FacturaScripts\Core\Model;
use FacturaScripts\Core\Base\DataBase;

/**
 * Description of PanelSettings
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class PanelSettings extends ExtendedController\PanelController
{

    const KEYSETTINGS = 'Settings';

    public function __construct(&$cache, &$i18n, &$miniLog, $className)
    {
        parent::__construct($cache, $i18n, $miniLog, $className);

        // Establecemos el modelo de datos
        $this->model = new Model\Settings();
    }

    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'App Preferences';
        $pagedata['icon'] = 'fa-cogs';
        $pagedata['menu'] = 'admin';
        $pagedata['orden'] = '999';

        return $pagedata;
    }

    public function getPanelHeader()
    {
        return $this->i18n->trans('options');
    }

    public function getFieldValue($view, $field)
    {        
        $properties = parent::getFieldValue($view, 'properties');
        return $properties[$field];
    }
    
    private function getViewNameFromKey($key)
    {
        return self::KEYSETTINGS . ucfirst($key);
    }

    private function getKeyFromViewName($viewName)
    {
        return strtolower(substr($viewName, strlen(self::KEYSETTINGS)));
    }

    protected function createViews()
    {
        $modelName = 'FacturaScripts\Core\Model\Settings';
        $title = $this->i18n->trans('general');
        $icon = $this->getPageData()['icon'];
        $this->addEditView($modelName, $this->getViewNameFromKey('Default'), $title, $icon);

        $model = new Model\Settings();
        $where = [new DataBase\DataBaseWhere('name', 'default', '<>')];
        $rows = $model->all($where, ['name' => 'ASC'], 0, 0);
        foreach ($rows as $setting) {
            $title = $this->i18n->trans($setting->name);
            $viewName = $this->getViewNameFromKey($setting->name);
            $this->addEditView($modelName, $viewName, $title, $setting->icon);
        }

        $title2 = $this->i18n->trans('about');
        $this->addHtmlView('About.html', NULL, 'about', $title2);
    }

    protected function loadData($keyView, $view)
    {
        $model = $view->getModel();
        if (empty($model)) {
            return;
        }

        $code = $this->getKeyFromViewName($keyView);
        $view->loadData($code);
    }
}
