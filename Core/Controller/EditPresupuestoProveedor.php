<?php
/**
 * Copyright (C) 2021-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
 */

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Lib\AjaxForms\PurchasesController;

/**
 * Description of EditPresupuestoProveedor
 *
 * @author Carlos Garcia Gomez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class EditPresupuestoProveedor extends PurchasesController
{
    public function getModelClassName(): string
    {
        return 'PresupuestoProveedor';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'purchases';
        $data['title'] = 'estimation';
        $data['icon'] = 'far fa-file-powerpoint';
        $data['showonmenu'] = false;
        return $data;
    }
}
