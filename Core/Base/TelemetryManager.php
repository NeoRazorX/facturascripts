<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
final class TelemetryManager
{

    const TELEMETRY_URL = 'https://facturascripts.com/Telemetry';
    const UPDATE_INTERVAL = 86400;

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
     * @var int
     */
    private $lastupdate;

    /**
     *
     * @var string
     */
    private $signkey;

    public function __construct()
    {
        $this->appSettings = new AppSettings();
        $this->idinstall = (int) $this->appSettings->get('default', 'telemetryinstall');
        $this->lastupdate = (int) $this->appSettings->get('default', 'telemetrylastu');
        $this->signkey = $this->appSettings->get('default', 'telemetrykey');

        /**
         * Is telemetry data defined in the config.php?
         * FS_TELEMETRY_TOKEN = IDINSTALL:SIGNKEY
         */
        if (empty($this->idinstall) && \defined('FS_TELEMETRY_TOKEN')) {
            $data = \explode(':', \FS_TELEMETRY_TOKEN);
            if (\count($data) === 2) {
                $this->idinstall = (int) $data[0];
                $this->signkey = $data[1];
                $this->update();
            }
        }
    }

    /**
     * 
     * @return string
     */
    public function claimUrl(): string
    {
        $params = $this->collectData(true);
        $params['action'] = 'claim';
        $this->calculateHash($params);
        return self::TELEMETRY_URL . '?' . \http_build_query($params);
    }

    /**
     * 
     * @return string
     */
    public function id()
    {
        return $this->idinstall;
    }

    /**
     * 
     * @return bool
     */
    public function install(): bool
    {
        $params = $this->collectData();
        $params['action'] = 'install';
        $json = $this->getDownloader()->getContents(self::TELEMETRY_URL . '?' . \http_build_query($params), 3);
        $data = \json_decode($json, true);
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
     * @param string $url
     *
     * @return string
     */
    public function signUrl(string $url): string
    {
        if (empty($this->idinstall)) {
            return $url;
        }

        $params = $this->collectData(true);
        $this->calculateHash($params);
        return $url . '?' . \http_build_query($params);
    }

    /**
     * 
     * @return bool
     */
    public function update(): bool
    {
        if (false === $this->ready() || \time() - $this->lastupdate < self::UPDATE_INTERVAL) {
            return false;
        }

        $params = $this->collectData();
        $params['action'] = 'update';
        $this->calculateHash($params);

        $json = $this->getDownloader()->getContents(self::TELEMETRY_URL . '?' . \http_build_query($params), 3);
        $data = \json_decode($json, true);

        $this->save();
        return isset($data['ok']) ? (bool) $data['ok'] : false;
    }

    /**
     * 
     * @param array $data
     */
    private function calculateHash(array &$data)
    {
        $data['hash'] = \sha1($data['randomnum'] . $this->signkey);
    }

    /**
     * 
     * @param bool $minimum
     *
     * @return array
     */
    private function collectData(bool $minimum = false): array
    {
        $data = [
            'codpais' => \FS_CODPAIS,
            'coreversion' => PluginManager::CORE_VERSION,
            'idinstall' => $this->idinstall,
            'langcode' => \FS_LANG,
            'phpversion' => (float) \PHP_VERSION,
            'randomnum' => \mt_rand()
        ];

        if ($minimum) {
            return $data;
        }

        $customer = new Cliente();
        $invoice = new FacturaCliente();
        $pluginManager = new PluginManager();
        $variant = new Variante();
        $user = new User();
        $more = [
            'numcustomers' => $customer->count(),
            'numinvoices' => $invoice->count(),
            'numusers' => $user->count(),
            'numvariants' => $variant->count(),
            'pluginlist' => \implode(',', $pluginManager->enabledPlugins())
        ];

        return \array_merge($data, $more);
    }

    /**
     * 
     * @return DownloadTools
     */
    private function getDownloader()
    {
        return new DownloadTools();
    }

    private function save()
    {
        $this->lastupdate = \time();
        $this->appSettings->set('default', 'telemetryinstall', $this->idinstall);
        $this->appSettings->set('default', 'telemetrykey', $this->signkey);
        $this->appSettings->set('default', 'telemetrylastu', $this->lastupdate);
        $this->appSettings->save();
    }
}
