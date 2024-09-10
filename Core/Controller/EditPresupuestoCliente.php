<?php
/**
 * Copyright (C) 2021-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
 */

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\AjaxForms\SalesController;

/**
 * Description of EditPresupuestoCliente
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class EditPresupuestoCliente extends SalesController
{

    /**
     * @return string
     */
    public function getModelClassName()
    {
        return 'PresupuestoCliente';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'estimation';
        $data['icon'] = 'far fa-file-powerpoint';
        $data['showonmenu'] = false;
        return $data;
    }
}
