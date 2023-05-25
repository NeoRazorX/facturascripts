<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Internal;

use FacturaScripts\Core\Http;
use FacturaScripts\Core\Kernel;
use FacturaScripts\Core\Tools;

final class Forja
{
    const BUILDS_URL = 'https://facturascripts.com/DownloadBuild';
    const CORE_PROJECT_ID = 1;
    const PLUGIN_LIST_URL = 'https://facturascripts.com/PluginInfoList';

    /** @var array */
    public static $builds;

    /** @var array */
    private static $pluginList;

    public static function builds(): array
    {
        if (!isset(self::$builds)) {
            self::$builds = Http::get(self::BUILDS_URL)->setTimeout(10)->json();
        }

        return self::$builds ?? [];
    }

    public static function canUpdateCore(): bool
    {
        foreach (self::getBuilds(self::CORE_PROJECT_ID) as $build) {
            if ($build['stable'] && $build['version'] > Kernel::version()) {
                return true;
            }

            if (false === Tools::settings('default', 'enableupdatesbeta', false)) {
                continue;
            }

            if ($build['beta'] && $build['version'] > Kernel::version()) {
                return true;
            }
        }

        return false;
    }

    public static function getBuilds(int $id): array
    {
        foreach (self::builds() as $project) {
            if ($project['project'] == $id) {
                return $project['builds'];
            }
        }

        return [];
    }

    public static function getBuildsByName(string $pluginName): array
    {
        foreach (self::builds() as $project) {
            if ($project['name'] == $pluginName) {
                return $project['builds'];
            }
        }

        return [];
    }

    public static function plugins(): array
    {
        if (!isset(self::$pluginList)) {
            self::$pluginList = Http::get(self::PLUGIN_LIST_URL)->setTimeout(10)->json();
        }

        return self::$pluginList ?? [];
    }
}
