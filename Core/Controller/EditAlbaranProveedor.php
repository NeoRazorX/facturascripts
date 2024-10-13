<?php
/**
 * Copyright (C) 2021-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
 */

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Lib\AjaxForms\PurchasesController;

/**
 * Description of EditAlbaranProveedor
 *
 * @author Carlos Garcia Gomez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class EditAlbaranProveedor extends PurchasesController
{
    public function getModelClassName(): string
    {
        return 'AlbaranProveedor';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'purchases';
        $data['title'] = 'delivery-note';
        $data['icon'] = 'fa-solid fa-dolly-flatbed';
        $data['showonmenu'] = false;
        return $data;
    }
}
