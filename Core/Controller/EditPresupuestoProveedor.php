<?php
/**
 * Copyright (C) 2021-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
 */

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\AjaxForms\PurchasesController;

/**
 * Description of EditPresupuestoProveedor
 *
 * @author Carlos Garcia Gomez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class EditPresupuestoProveedor extends PurchasesController
{

    /**
     * @return string
     */
    public function getModelClassName()
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
