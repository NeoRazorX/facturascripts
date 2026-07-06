<?php
/**
 * Plugin PedidoToFactura para FacturaScripts
 *
 * API endpoint to convert PedidoCliente to FacturaCliente
 * with anticipos transfer.
 *
 * @author CDTCOM
 * @license MIT
 */

namespace FacturaScripts\Plugins\PedidoToFactura;

use FacturaScripts\Core\Template\InitClass;
use FacturaScripts\Core\Kernel;
use FacturaScripts\Core\Controller\ApiRoot;

final class Init extends InitClass
{
    public function init(): void
    {
        Kernel::addRoute('/api/3/pedidoToFactura', 'ApiPedidoToFactura', -1);
        ApiRoot::addCustomResource('pedidoToFactura');
    }

    public function update(): void
    {
    }

    public function uninstall(): void
    {
    }
}
