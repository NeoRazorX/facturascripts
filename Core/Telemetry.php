<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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
    }

    public function claimUrl(): string
    {
        $params = $this->collectData(true);
        $params['action'] = 'claim';

        $this->calculateHash($params);

        return self::TELEMETRY_URL . '?' . http_build_query($params);
    }

    public function getMetadata(): array
    {
        if ($this->id_install) {
            return [];
        }

        $params = $this->collectData();
        $params['action'] = 'get-metadata';

        $this->calculateHash($params);

        // hacemos una petición a la url de telemetría
        $request = Http::get(self::TELEMETRY_URL, $params)->setTimeout(10);
        if ($request->failed()) {
            return [];
        }

        // comprobamos que la petición ha devuelto un json
        $data = $request->json();
        if (empty($data) || empty($data['data'] ?? '')) {
            return [];
        }

        return $data['data'] ?? [];
    }

    public function id()
    {
        return $this->id_install;
    }

    public static function init(): self
    {
        return new self();
    }

    public function install(): bool
    {
        if ($this->id_install) {
            return true;
        }

        $params = $this->collectData();
        $params['action'] = 'install';

        // hacemos una petición a la url de telemetría
        $request = Http::get(self::TELEMETRY_URL, $params)->setTimeout(10);
        if ($request->failed()) {
            return false;
        }

        // comprobamos que la petición ha devuelto un json
        $data = $request->json();
        if (empty($data) || empty($data['idinstall'] ?? '')) {
            return false;
        }

        // guardamos los datos de la instalación
        $this->id_install = $data['idinstall'];
        $this->sign_key = $data['signkey'];
        return $this->save();
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

        // hacemos una petición a la url de telemetría
        $request = Http::get(self::TELEMETRY_URL, $params)->setTimeout(10);
        if ($request->failed()) {
            return false;
        }

        // comprobamos que la petición ha devuelto un json
        $data = $request->json();
        if (isset($data['error']) && $data['error']) {
            return false;
        }

        // eliminamos los datos de la instalación
        $this->id_install = null;
        Tools::settingsSet('default', 'telemetryinstall', null);
        Tools::settingsSet('default', 'telemetrylastu', null);
        Tools::settingsSet('default', 'telemetrykey', null);

        return Tools::settingsSave();
    }

    public function update(): bool
    {
        if (false === $this->ready() || time() - $this->last_update < self::UPDATE_INTERVAL) {
            return false;
        }

        $params = $this->collectData();
        $params['action'] = 'update';

        $this->calculateHash($params);

        // hacemos una petición a la url de telemetría
        $request = Http::get(self::TELEMETRY_URL, $params)->setTimeout(3);
        if ($request->failed()) {
            return false;
        }

        // comprobamos que la petición ha devuelto un json
        $data = $request->json();
        if (empty($data) || !isset($data['ok']) || !$data['ok']) {
            return false;
        }

        return $this->save();
    }

    private function calculateHash(array &$data): void
    {
        $data['hash'] = sha1($data['randomnum'] . $this->sign_key);
    }

    private function collectData(bool $minimum = false): array
    {
        $data = [
            'codpais' => Tools::settings('default', 'codpais'),
            'coreversion' => Kernel::version(),
            'idinstall' => $this->id_install,
            'langcode' => Tools::config('lang'),
            'phpversion' => (float)PHP_VERSION,
            'randomnum' => mt_rand()
        ];

        if (false === $minimum) {
            $data['pluginlist'] = implode(',', Plugins::enabled());
        }

        return $data;
    }

    private function save(): bool
    {
        $this->last_update = time();

        Tools::settingsSet('default', 'telemetryinstall', $this->id_install);
        Tools::settingsSet('default', 'telemetrykey', $this->sign_key);
        Tools::settingsSet('default', 'telemetrylastu', $this->last_update);

        return Tools::settingsSave();
    }
}
