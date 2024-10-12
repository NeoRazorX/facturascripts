<?php
/**
 * Copyright (C) 2021-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
 */

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Lib\AjaxForms\SalesController;

/**
 * Description of EditPedidoCliente
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class EditPedidoCliente extends SalesController
{
    public function getModelClassName(): string
    {
        return 'PedidoCliente';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'order';
        $data['icon'] = 'fa-solid fa-file-powerpoint';
        $data['showonmenu'] = false;
        return $data;
    }
}
