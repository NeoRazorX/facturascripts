<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  carlos@facturascripts.com
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
namespace FacturaScripts\Core\App;

use Symfony\Component\HttpFoundation\Response;

/**
 * Description of App
 *
 * @author Carlos García Gómez
 */
class AppAPI extends App
{

    /**
     * Ejecuta la API.
     *
     * @return boolean
     */
    public function run()
    {
        $this->response->headers->set('Content-Type', 'text/plain');
        if (!$this->dataBase->connected()) {
            $this->response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            $this->response->setContent('DB-ERROR');
            return false;
        } elseif ($this->isIPBanned()) {
            $this->response->setStatusCode(Response::HTTP_FORBIDDEN);
            $this->response->setContent('IP-BANNED');
            return false;
        }

        return $this->selectVersion();
    }

    private function selectVersion()
    {
        $version = $this->request->get('v', '');
        if ($version == '3') {
            return $this->selectMap();
        }

        $this->response->setStatusCode(Response::HTTP_NOT_FOUND);
        $this->response->setContent('API-VERSION-NOT-FOUND');
        return true;
    }

    private function selectMap()
    {
        $mapName = $this->request->get('map', '');
        if ($mapName == '') {
            return $this->getAPIOptions();
        }

        $map = $this->getAPIMap($mapName);
        if (!isset($map->model) || !isset($map->function)) {
            $this->response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            $this->response->setContent('API-MAP-ERROR');
            return false;
        }

        $modelName = "FacturaScripts\\Dinamic\\Model\\" . $map->model;
        $modelFunction = $map->function;

        try {
            $model = new $modelName();
            $param1 = $this->request->get('param1', '');

            if ($modelFunction == 'get' && $param1 != '') {
                $data = $model->{$modelFunction}($param1);
            } else if ($modelFunction == 'all' && $param1 != '') {
                $data = $model->{$modelFunction}([], [], $param1);
            } else if ($modelFunction == 'all') {
                $data = $model->{$modelFunction}();
            } else {
                $data = false;
            }

            $this->response->setContent(json_encode($data));
            return true;
        } catch (Exception $ex) {
            $this->response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            $this->response->setContent('API-ERROR');
            return false;
        }
    }

    private function getAPIMap($mapName)
    {
        $path = $this->folder . '/Dinamic/API/' . $mapName . '.json';
        if (!file_exists($path)) {
            $path = $this->folder . '/Core/API/' . $mapName . '.json';
        }

        if (file_exists($path)) {
            return json_decode(file_get_contents($path));
        }

        return json_decode("{}");
    }

    private function getAPIOptions()
    {
        $options = ['version' => '3', 'routes' => []];
        $path = $this->folder . '/Dinamic/API';
        if (!file_exists($this->folder . '/Dinamic/API')) {
            $path = $this->folder . '/Core/API';
        }

        foreach (scandir($this->folder . '/Core/API') as $fName) {
            if (substr($fName, -5) == '.json') {
                $options['routes'][] = substr($fName, 0, -5);
            }
        }

        $this->response->setContent(json_encode($options));
        return TRUE;
    }
}
