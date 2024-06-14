<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Http;
use FacturaScripts\Core\Plugins;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Tools;

/**
 * This class allow sending subscription data to the master server,
 *
 * @author Daniel Fernández Giménez <hola@danielfg.es>
 */
final class SubscriptionManager
{
    //const SUBSCRIPTION_URL = 'https://facturascripts.com/SubscriptionActivity';
    const SUBSCRIPTION_URL = 'https://forja.danielfg.es/SubscriptionActivity';

    /** Weekly update*/
    const UPDATE_INTERVAL = 604800;

    /** @var string */
    private $uuid_install;

    /** @var int */
    private $last_update;

    public function __construct()
    {
        $this->uuid_install = Tools::settings('default', 'uuid_install');
        $this->last_update = (int)Tools::settings('default', 'subscription_last_update');
    }

    public function ready(): bool
    {
        if (empty(Tools::settings('default', 'uuid_install'))) {
            $this->uuid_install = Tools::randomString(20);
            Tools::settingsSet('default', 'uuid_install', $this->uuid_install);
            return Tools::settingsSave();
        }

        return !empty($this->uuid_install);
    }

    public function update(string $pluginName = ''): bool
    {
        if (false === $this->ready()
            || empty($pluginName) && time() - $this->last_update < self::UPDATE_INTERVAL) {
            return false;
        }

        $params = $this->collectData($pluginName);
        if (empty($params['subscriptions'])) {
            return false;
        }

        $params['action'] = 'update';
        $data = Http::get(self::SUBSCRIPTION_URL, $params)->setTimeout(3)->json();

        $this->save();
        return isset($data['ok']) && $data['ok'];
    }

    private function collectData(string $pluginName = ''): array
    {
        $subscriptions = [];

        foreach (Plugins::list() as $plugin) {
            if (empty($plugin->subscription)
                || !empty($pluginName) && $plugin->name !== $pluginName) {
                continue;
            }

            $subscriptions[] = $plugin->subscription;
        }

        return [
            'telemetry_install' => Tools::settings('default', 'telemetryinstall'),
            'ip' => Session::getClientIp(),
            'subscriptions' => implode(',', $subscriptions),
            'uuid_install' => $this->uuid_install,
        ];
    }

    private function save(): void
    {
        Tools::settingsSet('default', 'subscription_last_update', time());
        Tools::settingsSave();
    }
}
