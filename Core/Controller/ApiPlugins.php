<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Plugins;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Template\ApiController;

class ApiPlugins extends ApiController
{
    protected function runResource(): void
    {
        if (false === $this->request->isMethod(Request::METHOD_GET)) {
            $this->response
                ->setHttpCode(Response::HTTP_METHOD_NOT_ALLOWED)
                ->json([
                    'status' => 'error',
                    'message' => 'method-not-allowed',
                ]);
            return;
        }

        $plugins = Plugins::list();

        $filter = $this->request->getArray('filter');
        $plugins = $this->applyFilter($plugins, $filter);

        $order = $this->request->getArray('sort');
        $plugins = $this->applySort($plugins, $order);

        $this->response->json($plugins);
    }

    private function applyFilter(array $plugins, $filter): array
    {
        if (empty($filter)) {
            return $plugins;
        }

        return array_filter($plugins, function ($plugin) use ($filter) {
            foreach ($filter as $key => $value) {
                $operator = '=';
                $field = $key;
                if (substr($key, -3) === '_gt') {
                    $field = substr($key, 0, -3);
                    $operator = '>';
                } elseif (substr($key, -3) === '_lt') {
                    $field = substr($key, 0, -3);
                    $operator = '<';
                } elseif (substr($key, -4) === '_gte') {
                    $field = substr($key, 0, -4);
                    $operator = '>=';
                } elseif (substr($key, -4) === '_lte') {
                    $field = substr($key, 0, -4);
                    $operator = '<=';
                } elseif (substr($key, -4) === '_neq') {
                    $field = substr($key, 0, -4);
                    $operator = '!=';
                } elseif (substr($key, -5) === '_like') {
                    $field = substr($key, 0, -5);
                    $operator = 'LIKE';
                } elseif (substr($key, -5) === '_null') {
                    $field = substr($key, 0, -5);
                    $operator = 'IS';
                    $value = null;
                } elseif (substr($key, -8) === '_notnull') {
                    $field = substr($key, 0, -8);
                    $operator = 'IS NOT';
                    $value = null;
                }
                if (!property_exists($plugin, $field)) {
                    return false;
                }
                $pluginValue = $plugin->{$field};
                if (!$this->compare($pluginValue, $value, $operator)) {
                    return false;
                }
            }
            return true;
        });
    }

    private function compare($a, $b, string $operator): bool
    {
        return match ($operator) {
            '>' => $a > $b,
            '<' => $a < $b,
            '>=' => $a >= $b,
            '<=' => $a <= $b,
            '!=' => $a != $b,
            'LIKE' => stripos((string)$a, (string)$b) !== false,
            'IS' => $a === null,
            'IS NOT' => $a !== null,
            default => $a == $b,
        };
    }

    private function applySort($plugins, $sort): array
    {
        if (empty($sort)) {
            return $plugins;
        }

        usort($plugins, function ($a, $b) use ($sort) {
            foreach ($sort as $key => $value) {
                if (!property_exists($a, $key) || !property_exists($b, $key)) {
                    continue;
                }

                $plugin1 = $a->{$key};
                $plugin2 = $b->{$key};

                if ($plugin1 === $plugin2) {
                    continue;
                }

                if ($value === 'DESC') {
                    return ($plugin1 < $plugin2) ? 1 : -1;
                }
                return ($plugin1 < $plugin2) ? -1 : 1;
            }
            return 0;
        });

        return $plugins;
    }
}
