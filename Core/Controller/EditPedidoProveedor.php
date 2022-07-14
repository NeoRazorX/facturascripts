<?php
/**
 * Copyright (C) 2021-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
 */

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\AjaxForms\PurchasesController;

/**
 * Description of EditPedidoProveedor
 *
 * @author Carlos Garcia Gomez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class EditPedidoProveedor extends PurchasesController
{

    /**
     * @return string
     */
    public function getModelClassName()
    {
        return 'PedidoProveedor';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'purchases';
        $data['title'] = 'order';
        $data['icon'] = 'fas fa-file-powerpoint';
        $data['showonmenu'] = false;
        return $data;
    }
}
