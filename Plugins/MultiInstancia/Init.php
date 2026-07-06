<?php
/**
 * Plugin MultiInstancia para FacturaScripts
 *
 * @author   CDTCOM
 * @license  MIT
 */

namespace FacturaScripts\Plugins\MultiInstancia;

use FacturaScripts\Core\Template\InitClass;
use FacturaScripts\Dinamic\Lib\AssetManager;

final class Init extends InitClass
{
    /**
     * Se ejecuta en cada carga de página.
     * Define constantes PHP con los valores del navbar
     * para que la plantilla Twig los lea con constant().
     */
    public function init(): void
    {
        $installation = Config::current();

        // Valores por defecto si el host no está mapeado.
        $color  = $installation['nav_color']  ?? '#286090';
        $border = $installation['nav_border'] ?? '#204d74';
        $label  = $installation['nav_label']  ?? '';

        define('MULTI_NAV_COLOR',  $color);
        define('MULTI_NAV_BORDER', $border);
        define('MULTI_NAV_LABEL',  $label);

    }

    public function update(): void
    {
        ## vacio
    }

    public function uninstall(): void
    {
       ## vacio 
    }
}
