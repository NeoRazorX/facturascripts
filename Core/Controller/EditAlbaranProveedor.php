<?php
/**
 * Copyright (C) 2021-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
 */

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Dinamic\Lib\AjaxForms\PurchasesController;

/**
 * Controlador para editar un único elemento del modelo AlbaranProveedor
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
