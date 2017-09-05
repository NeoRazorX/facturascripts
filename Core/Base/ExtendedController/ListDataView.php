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
namespace FacturaScripts\Core\Base\ExtendedController;

/**
 * Description of ListDataView
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class ListDataView
{
    /**
     * Lista de vistas de datos (type DataView)
     * 
     * @var Array
     */
    private $views;
    
    public function __construct()        
    {
        $this->views = [];        
    }
    
    public function add($modelName, $viewName, $userNick)
    {
        $this->views[] = new DataView($modelName, $viewName, $userNick);
    }
    
    public function getModel($index = 0)
    {
        return $this->views[$index]->getModel();
    }    
    
    public function getCursor($index = 0)
    {
        return $this->views[$index]->getCursor();
    }
    
    public function getCount($index = 0)       
    {
        return $this->views[$index]->getCount();
    }
}
