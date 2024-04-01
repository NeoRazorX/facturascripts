<?php declare(strict_types=1);

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Lib\ExtendedController\EditController;

class EditPuntoInteresCiudad extends EditController
{
    public function getModelClassName(): string
    {
        return "PuntoInteresCiudad";
    }

    public function getPageData(): array
    {
        $pageData = parent::getPageData();
        $pageData["title"] = "PuntoInteresCiudad";
        $pageData["icon"] = "fas fa-search";
        return $pageData;
    }
}
