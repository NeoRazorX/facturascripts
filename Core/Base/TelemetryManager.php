<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Base;

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\DownloadTools;
use FacturaScripts\Core\Base\PluginManager;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Dinamic\Model\Variante;

/**
 * This class allow sending telemetry data to the master server,
 * ONLY if the user has registered this installation.
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class TelemetryManager
{

    const TELEMETRY_URL = 'https://www.facturascripts.com/Telemetry';

    /**
     *
     * @var AppSettings
     */
    private $appSettings;

    /**
     *
     * @var int
     */
    private $idinstall;

    /**
     *
     * @var string
     */
    private $signkey;

    public function __construct()
    {
        $this->appSettings = new AppSettings();
        $this->idinstall = $this->appSettings->get('default', 'telemetryinstall');
        $this->signkey = $this->appSettings->get('default', 'telemetrykey');
    }

    /**
     * 
     * @return bool
     */
    public function install(): bool
    {
        $downloader = new DownloadTools();
        $params = $this->collectData();
        $params['action'] = 'install';
        $json = $downloader->getContents(self::TELEMETRY_URL . '?' . http_build_query($params));
        $data = json_decode($json, true);
        if ($data['idinstall']) {
            $this->idinstall = $data['idinstall'];
            $this->signkey = $data['signkey'];
            $this->save();
            return true;
        }

        return false;
    }

    /**
     * 
     * @return bool
     */
    public function ready(): bool
    {
        return empty($this->idinstall) ? false : true;
    }

    /**
     * 
     * @return array
     */
    private function collectData(): array
    {
        $customer = new Cliente();
        $invoice = new FacturaCliente();
        $pluginManager = new PluginManager();
        $variant = new Variante();
        $user = new User();

        return [
            'codpais' => FS_CODPAIS,
            'coreversion' => PluginManager::CORE_VERSION,
            'langcode' => FS_LANG,
            'numcustomers' => $customer->count(),
            'numinvoices' => $invoice->count(),
            'numusers' => $user->count(),
            'numvariants' => $variant->count(),
            'phpversion' => (float) PHP_VERSION,
            'pluginlist' => implode(',', $pluginManager->enabledPlugins()),
        ];
    }

    private function save()
    {
        $this->appSettings->set('default', 'telemetryinstall', $this->idinstall);
        $this->appSettings->set('default', 'telemetrykey', $this->signkey);
        $this->appSettings->save();
    }
}
