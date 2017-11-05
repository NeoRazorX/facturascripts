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

namespace FacturaScripts\Core\Base\ExtendedController;

use DOMDocument;
use FacturaScripts\Core\Base;
use Symfony\Component\HttpFoundation\Response;

/**
 * Definición de la vista para uso en ExtendedControllers
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class HtmlView extends BaseView
{
    /**
     * Nombre de archivo
     *
     * @var string
     */
    public $fileName;

    /**
     * Constructor e inicializador de la clase
     *
     * @param string $title
     * @param string $modelName
     * @param string $fileName
     */
    public function __construct($title, $modelName, $fileName)
    {
        parent::__construct($title, $modelName);
        $this->fileName = $fileName;
    }

    /**
     * Método para la exportación de los datos de la vista
     *
     * @param Base\ExportManager $exportManager
     * @param Response $response
     * @param string $action
     *
     * @return null
     */
    public function export(&$exportManager, &$response, $action)
    {
        return null;
    }

    public function disableColumn($columnName, $disabled)
    {
        
    }
}
