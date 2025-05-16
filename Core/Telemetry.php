<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core;

/**
 * This class allow sending telemetry data to the master server,
 * ONLY if the user has registered this installation.
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
final class Telemetry
{
    const TELEMETRY_URL = 'https://facturascripts.com/Telemetry';

    /** Weekly update*/
    const UPDATE_INTERVAL = 604800;

    /** @var int */
    private $id_install;

    /** @var int */
    private $last_update;

    /** @var string */
    private $sign_key;

    public function __construct()
    {
        $this->id_install = (int)Tools::settings('default', 'telemetryinstall');
        $this->last_update = (int)Tools::settings('default', 'telemetrylastu');
        $this->sign_key = Tools::settings('default', 'telemetrykey');

        /**
         * Is telemetry data defined in the config.php?
         * FS_TELEMETRY_TOKEN = IDINSTALL:SIGNKEY
         */
        if (empty($this->id_install) && defined('FS_TELEMETRY_TOKEN')) {
            $data = explode(':', Tools::config('telemetry_token', ''));
            if (count($data) === 2) {
                $this->id_install = (int)$data[0];
                $this->sign_key = $data[1];
                $this->update();
            }
        }
    }

    public function claimUrl(): string
    {
        $params = $this->collectData(true);
        $params['action'] = 'claim';
        $this->calculateHash($params);
        return self::TELEMETRY_URL . '?' . http_build_query($params);
    }

    public function id()
    {
        return $this->id_install;
    }

    public function install(): bool
    {
        if ($this->id_install) {
            return true;
        }

        $params = $this->collectData();
        $params['action'] = 'install';
        $data = Http::get(self::TELEMETRY_URL, $params)->setTimeout(10)->json();
        if ($data['idinstall']) {
            $this->id_install = $data['idinstall'];
            $this->sign_key = $data['signkey'];
            $this->save();
            return true;
        }

        return false;
    }

    public function ready(): bool
    {
        return !empty($this->id_install);
    }

    public function signUrl(string $url): string
    {
        if (empty($this->id_install)) {
            return $url;
        }

        $params = $this->collectData(true);
        $this->calculateHash($params);
        return $url . '?' . http_build_query($params);
    }

    public function unlink(): bool
    {
        if (empty($this->id_install)) {
            return true;
        }

        $params = $this->collectData();
        $params['action'] = 'unlink';
        $this->calculateHash($params);
        $data = Http::get(self::TELEMETRY_URL, $params)->setTimeout(10)->json();
        if (isset($data['error']) && $data['error']) {
            return false;
        }

        Tools::settingsSet('default', 'telemetryinstall', null);
        Tools::settingsSet('default', 'telemetrylastu', null);
        Tools::settingsSet('default', 'telemetrykey', null);
        Tools::settingsSave();

        $this->id_install = null;
        return true;
    }

    public function update(): bool
    {
        if (false === $this->ready() || time() - $this->last_update < self::UPDATE_INTERVAL) {
            return false;
        }

        $params = $this->collectData();
        $params['action'] = 'update';
        $this->calculateHash($params);

        $data = Http::get(self::TELEMETRY_URL, $params)->setTimeout(3)->json();

        $this->save();
        return isset($data['ok']) && $data['ok'];
    }

    private function calculateHash(array &$data): void
    {
        $data['hash'] = sha1($data['randomnum'] . $this->sign_key);
    }

    private function collectData(bool $minimum = false): array
    {
        $data = [
            'codpais' => FS_CODPAIS,
            'coreversion' => Kernel::version(),
            'idinstall' => $this->id_install,
            'langcode' => FS_LANG,
            'phpversion' => (float)PHP_VERSION,
            'randomnum' => mt_rand()
        ];

        if (false === $minimum) {
            $data['pluginlist'] = implode(',', Plugins::enabled());
        }

        return $data;
    }

    private function save(): void
    {
        $this->last_update = time();

        Tools::settingsSet('default', 'telemetryinstall', $this->id_install);
        Tools::settingsSet('default', 'telemetrykey', $this->sign_key);
        Tools::settingsSet('default', 'telemetrylastu', $this->last_update);
        Tools::settingsSave();
    }
}
