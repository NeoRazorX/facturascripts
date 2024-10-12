<?php
/**
 * Copyright (C) 2021-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
 */

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Lib\AjaxForms\SalesController;

/**
 * Description of EditPresupuestoCliente
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class EditPresupuestoCliente extends SalesController
{
    public function getModelClassName(): string
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
