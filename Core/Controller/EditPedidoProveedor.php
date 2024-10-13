<?php
/**
 * Copyright (C) 2021-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
 */

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Lib\AjaxForms\PurchasesController;

/**
 * Description of EditPedidoProveedor
 *
 * @author Carlos Garcia Gomez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class EditPedidoProveedor extends PurchasesController
{
    public function getModelClassName(): string
    {
        return 'PedidoProveedor';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'purchases';
        $data['title'] = 'order';
        $data['icon'] = 'fa-solid fa-file-powerpoint';
        $data['showonmenu'] = false;
        return $data;
    }
}
