<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * This class allow sending telemetry data to the master server,
 * ONLY if the user has registered this installation.
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
final class TelemetryManager
{

    const TELEMETRY_URL = 'https://facturascripts.com/Telemetry';

    /**
     * Weekly update
     */
    const UPDATE_INTERVAL = 604800;

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
        $json = DownloadTools::getContents(self::TELEMETRY_URL . '?' . \http_build_query($params), 3);
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

        $json = DownloadTools::getContents(self::TELEMETRY_URL . '?' . \http_build_query($params), 3);
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

        if (false === $minimum) {
            $pluginManager = new PluginManager();
            $data['pluginlist'] = \implode(',', $pluginManager->enabledPlugins());
        }

        return $data;
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
