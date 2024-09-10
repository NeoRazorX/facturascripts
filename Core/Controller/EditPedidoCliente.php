<?php
/**
 * Copyright (C) 2021-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
 */

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\AjaxForms\SalesController;

/**
 * Description of EditPedidoCliente
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class EditPedidoCliente extends SalesController
{

    /**
     * @return string
     */
    public function getModelClassName()
    {
        return 'PedidoCliente';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'order';
        $data['icon'] = 'fas fa-file-powerpoint';
        $data['showonmenu'] = false;
        return $data;
    }
}
