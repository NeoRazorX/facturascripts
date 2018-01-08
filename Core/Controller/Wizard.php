<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Model;

/**
 * Description of Wizard
 *
 * @author Carlos García Gómez
 */
class Wizard extends Controller
{

    public function getPageData()
    {
        $pageData = parent::getPageData();
        $pageData['menu'] = 'admin';
        $pageData['showonmenu'] = false;

        return $pageData;
    }

    public function getDivisas()
    {
        $divisas = [];

        $divisaModel = new Model\Divisa();
        foreach ($divisaModel->all([], ['descripcion' => 'ASC'], 0, 500) as $divisa) {
            $divisas[$divisa->coddivisa] = $divisa->descripcion;
        }

        return $divisas;
    }

    public function getPaises()
    {
        $paises = [];

        $paisModel = new Model\Pais();
        foreach ($paisModel->all([], ['nombre' => 'ASC'], 0, 500) as $pais) {
            $paises[$pais->codpais] = $pais->nombre;
        }

        return $paises;
    }

    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        $coddivisa = $this->request->request->get('coddivisa', '');
        $codpais = $this->request->request->get('codpais', '');
        if ($codpais !== '') {
            $appSettings = new AppSettings();
            $appSettings->set('default', 'coddivisa', $coddivisa);
            $appSettings->set('default', 'codpais', $codpais);
            $appSettings->set('default', 'homepage', 'AdminHome');
            $appSettings->save();
            $this->initModels();
            $this->saveAddress($appSettings, $codpais);

            /// change user homepage
            $this->user->homepage = 'AdminHome';
            $this->user->save();

            /// redir to EditSettings
            $this->response->headers->set('Refresh', '0; index.php?page=EditSettings');
        }
    }

    private function initModels()
    {
        new Model\FormaPago();
        new Model\Impuesto();
        new Model\Serie();
    }

    /**
     * 
     * @param AppSettings $appSettings
     * @param string $codpais
     */
    private function saveAddress(&$appSettings, $codpais)
    {
        $this->empresa->codpais = $codpais;
        $this->empresa->provincia = $this->request->request->get('provincia');
        $this->empresa->ciudad = $this->request->request->get('ciudad');
        $this->empresa->save();

        $almacenModel = new Model\Almacen();
        foreach ($almacenModel->all() as $almacen) {
            $almacen->codpais = $codpais;
            $almacen->provincia = $this->empresa->provincia;
            $almacen->ciudad = $this->empresa->ciudad;
            $almacen->save();

            $appSettings->set('default', 'codalmacen', $almacen->codalmacen);
            $appSettings->save();
            break;
        }
    }
}
