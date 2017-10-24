<?php
/**
 * File EditSerie.php | EditSerie.php
 *
 * @package     facturascripts
 * @subpackage  facturascripts
 * @autor       Francesc Pineda Segarra francesc.pineda.segarra@gmail.com
 * @copyright   Copyright (c) 2017
 */

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base;
use FacturaScripts\Core\Base\ExtendedController;

/**
 * Controlador para la edición de un registro del modelo Familia
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class EditSerie extends ExtendedController\EditController
{
    /**
     * EditFamilia constructor.
     *
     * @param Base\Cache $cache
     * @param Base\Translator $i18n
     * @param Base\MiniLog $miniLog
     * @param string $className
     */
    public function __construct(&$cache, &$i18n, &$miniLog, $className)
    {
        parent::__construct($cache, $i18n, $miniLog, $className);

        // Establecemos el modelo de datos
        $this->modelName = 'FacturaScripts\Core\Model\Serie';
    }

    /**
     * Devuelve los datos básicos de la página
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'series';
        $pagedata['icon'] = 'fa-file-text';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }
}
