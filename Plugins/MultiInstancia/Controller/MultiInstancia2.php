<?php
/**
 * Plugin MultiInstancia — Redirige a waterman.cdtcom.net
 *
 * @author   CDTCOM
 * @license  MIT
 */

namespace FacturaScripts\Plugins\MultiInstancia\Controller;

use FacturaScripts\Plugins\MultiInstancia\Lib\MultiInstanciaBase;

class MultiInstancia2 extends MultiInstanciaBase
{
    public function getPageData(): array
    {
        $data            = parent::getPageData();
        $data['menu']    = 'instalaciones';
        $data['submenu'] = '';
        $data['title']   = 'Waterman Store';
        $data['icon']    = $this->installationData('waterman.cdtcom.net')['icon'] ?? 'fas fa-store';
        return $data;
    }

    protected function getInstallationKey(): string
    {
        return 'waterman.cdtcom.net';
    }
}
