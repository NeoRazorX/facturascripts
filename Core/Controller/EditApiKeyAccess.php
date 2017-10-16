<?php

/*
 * Copyright (C) 2017 Joe Nilson <joenilson at gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
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
use FacturaScripts\Core\Model\ApiKey;
use FacturaScripts\Core\Model\ApiKeyAccess;
/**
 * Description of EditApiKey
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class EditApiKeyAccess extends ExtendedController\EditController
{
    public $resources;
    public $model2;
    public function __construct(&$cache, &$i18n, &$miniLog, $className)
    {
        parent::__construct($cache, $i18n, $miniLog, $className);
        $this->modelName = 'FacturaScripts\Core\Model\ApiKeyAccess';
        $this->setTemplate('EditApiKeyAccess');
    }

    public function privateCore(&$response, $user)
    {
        parent::privateCore($response, $user);
        $apiKey = new ApiKey();
        $this->model2 = $apiKey->get($this->request->get('code'));
        // Si es la primera vez que se carga el model lo llenamos con data vacia
        // y le agregamos los modelos adicionales
        $this->verifyResources();
        $apiKeyAccess = new ApiKeyAccess();
        $this->resources = $apiKeyAccess->getByApiKey($this->request->get('code'));
        if(\filter_input(INPUT_POST, 'accion')){
            $funcion = \filter_input(INPUT_POST, 'accion');
            $this->$funcion($response);
        }
    }

    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'Accesos del ApiKey';
        $pagedata['icon'] = 'fa-list';
        $pagedata['showonmenu'] = FALSE;
        return $pagedata;
    }

    public function getResources()
    {
        $resources = [];
        foreach (scandir(dirname(dirname(__DIR__)) . '/Dinamic/Model') as $fName) {
            if (substr($fName, -4) == '.php') {
                $modelName = substr($fName, 0, -4);
                /// convertimos en plural
                if (substr($modelName, -1) == 's') {
                    $plural = strtolower($modelName);
                } else if (substr($modelName, -3) == 'ser' || substr($modelName, -4) == 'tion') {
                    $plural = strtolower($modelName) . 's';
                } else if (in_array(substr($modelName, -1), ['a', 'e', 'i', 'o', 'u', 'k', 'y'])) {
                    $plural = strtolower($modelName) . 's';
                } else if (substr(strtolower($modelName), -5) == 'model') {
                    $plural = strtolower($modelName) . 'os';
                } else {
                    $plural = strtolower($modelName) . 'es';
                }
                $resources[] = $modelName;
            }
        }
        return $resources;
    }

    /**
     * Verifica la estructura y carga en el modelo los datos informados en un array
     *
     * @param array $data
     */
    public function loadFromData(&$data)
    {

        if ($data['primarykey'] != $this->model->primaryColumnValue()) {
            $this->model->loadFromCode(FALSE,['idapikey'=>$this->request->get('code')]);
        }

        $this->model->checkArrayData($data);
        $this->model->loadFromData($data, ['action', 'active', 'primarykey']);
    }

    public function verifyResources()
    {
        $idApiKey = $this->request->get('code');
        $apiKeyAccess = new ApiKeyAccess();
        $actualResources = $this->getResources();
        foreach ($actualResources as $res) {
            $res_exists = $apiKeyAccess->getByApiKeyResource($idApiKey, $res);
            if (!$res_exists and $idApiKey) {
                $res_exists = new ApiKeyAccess();
                $res_exists->resource = $res;
                $res_exists->idapikey = $idApiKey;
                $res_exists->save();
            }
        }
    }

}
