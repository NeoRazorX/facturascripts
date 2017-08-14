<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\ExtendedController;
use FacturaScripts\Core\Model;

/**
 * Controlador para la edición de un registro del modelo Pais
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class EditPais extends ExtendedController\EditController
{

    public function __construct(&$cache, &$i18n, &$miniLog, $className)
    {
        parent::__construct($cache, $i18n, $miniLog, $className);

        // Establecemos el modelo de datos
        $this->model = new Model\Pais();
    }

    public function privateCore(&$response, $user)
    {
        parent::privateCore($response, $user);
    }

    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'Países';
        $pagedata['icon'] = 'fa-globe';
        $pagedata['showonmenu'] = FALSE;
        return $pagedata;
    }
}
