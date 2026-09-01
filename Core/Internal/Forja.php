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

use FacturaScripts\Core\Cache;
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
            self::$builds = Cache::remember('forja_builds', function () {
                return Http::get(self::BUILDS_URL)->setTimeout(10)->json() ?? [];
            });
        }

        if (!is_array(self::$builds)) {
            return [];
        }

        $result = [];
        foreach (self::$builds as $project) {
            if (
                !is_array($project) ||
                !isset($project['project'], $project['name']) ||
                !is_int($project['project']) ||
                !is_string($project['name']) ||
                !isset($project['builds']) ||
                !is_array($project['builds'])
            ) {
                continue;
            }

            $project['builds'] = array_values(array_filter($project['builds'], static function ($build): bool {
                return is_array($build) &&
                    isset($build['version'], $build['stable'], $build['beta']) &&
                    is_numeric($build['version']) &&
                    is_bool($build['stable']) &&
                    is_bool($build['beta']) &&
                    array_key_exists('mincore', $build) &&
                    array_key_exists('maxcore', $build);
            }));
            $result[] = $project;
        }

        return $result;
    }

    public static function canUpdateCore(): bool
    {
        foreach (self::getBuilds(self::CORE_PROJECT_ID) as $build) {
            if ($build['stable'] && $build['version'] > Kernel::version()) {
                return true;
            }

            if (false === (bool)Tools::settings('default', 'enableupdatesbeta', 0)) {
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
            self::$pluginList = Cache::remember('forja_plugins', function () {
                return Http::get(self::PLUGIN_LIST_URL)->setTimeout(10)->json() ?? [];
            });
        }

        if (!is_array(self::$pluginList)) {
            return [];
        }

        return array_values(array_filter(self::$pluginList, static function ($item): bool {
            return is_array($item) && isset($item['name']) && is_string($item['name']);
        }));
    }
}
