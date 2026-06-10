<?php
/**
 * Copyright (C) 2021-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
 */

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Dinamic\Lib\AjaxForms\SalesController;

/**
 * Description of EditAlbaranCliente
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class EditAlbaranCliente extends SalesController
{
    public function getModelClassName(): string
    {
        return 'AlbaranCliente';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'delivery-note';
        $data['icon'] = 'fa-solid fa-dolly-flatbed';
        $data['showonmenu'] = false;
        return $data;
    }
}
