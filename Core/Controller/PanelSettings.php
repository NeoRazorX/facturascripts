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

    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'App Preferences';
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
                $result .= '?page=PanelSettings';
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
        return $properties[$field];
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
