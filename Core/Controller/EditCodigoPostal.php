<?php declare(strict_types=1);

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Lib\ExtendedController\EditController;

class EditCodigoPostal extends EditController
{
    public function getModelClassName(): string
    {
        return "CodigoPostal";
    }

    public function getPageData(): array
    {
        $pageData = parent::getPageData();
        $pageData["title"] = "zip-code";
        $pageData["icon"] = "fas fa-map-pin";
        return $pageData;
    }
}
