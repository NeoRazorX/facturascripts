<?php
/**
 * Plugin MultiInstancia — Redirige a conta.cdtcom.net
 *
 * @author   CDTCOM
 * @license  MIT
 */

namespace FacturaScripts\Plugins\MultiInstancia\Controller;

use FacturaScripts\Plugins\MultiInstancia\Lib\MultiInstanciaBase;

class MultiInstancia1 extends MultiInstanciaBase
{
    public function getPageData(): array
    {
        $data            = parent::getPageData();
        $data['menu']    = 'instalaciones';
        $data['submenu'] = '';
        $data['title']   = 'CableData Telecom';
        $data['icon']    = $this->installationData('conta.cdtcom.net')['icon'] ?? 'fas fa-server';
        return $data;
    }

    protected function getInstallationKey(): string
    {
        return 'conta.cdtcom.net';
    }
}
