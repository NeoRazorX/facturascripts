<?php
/**
 * Plugin MultiInstancia — Redirige a cabal.cdtcom.net
 *
 * @author   CDTCOM
 * @license  MIT
 */

namespace FacturaScripts\Plugins\MultiInstancia\Controller;

use FacturaScripts\Plugins\MultiInstancia\Lib\MultiInstanciaBase;

class MultiInstancia3 extends MultiInstanciaBase
{
    public function getPageData(): array
    {
        $data            = parent::getPageData();
        $data['menu']    = 'instalaciones';
        $data['submenu'] = '';
        $data['title']   = 'Cabal Corp';
        $data['icon']    = $this->installationData('cabal.cdtcom.net')['icon'] ?? 'fas fa-building';
        return $data;
    }

    protected function getInstallationKey(): string
    {
        return 'cabal.cdtcom.net';
    }
}
