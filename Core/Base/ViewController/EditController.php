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
namespace FacturaScripts\Core\Base\ViewController;

use FacturaScripts\Core\Base as Base;
use FacturaScripts\Core\Model as Model;

/**
 * Controlador para edición de datos
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class EditController extends Base\Controller
{
    /**
     * Modelo con los datos a mostrar
     * @var class
     */
    public $model;
    
    /**
     * Configuración de columnas y filtros
     * @var Model\PageOption
     */
    private $pageOption;
    
    public function __construct(&$cache, &$i18n, &$miniLog, $className)
    {
        parent::__construct($cache, $i18n, $miniLog, $className);

        $this->setTemplate("Master/EditController");                
        $this->pageOption = new Model\PageOption();
    }
    
    /**
     * Ejecuta la lógica privada del controlador.
     */
    public function privateCore(&$response, $user)
    {
        parent::privateCore($response, $user);

        // Cargamos configuración de columnas y filtros
        $className = $this->getClassName();
        $this->pageOption-> getForUser($className, $user->nick);

        // Cargamos datos del modelo
        $value = $this->request->get('code');
        $this->model->loadFromCode($value);
    }    
    
    public function getRow($key)
    {
        return empty($this->pageOption->rows) ? NULL : $this->pageOption->rows[$key];
    }    

    public function getGroupColumns()
    {
        return $this->pageOption->columns;
    }
    
}
