<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2025-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Plugins;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Template\ApiController;

class ApiPlugins extends ApiController
{
    protected function runResource(): void
    {
        // obtenemos el nombre del plugin desde la URI (api/3/plugins/{nombre})
        $pluginName = $this->getUriParam(3);

        // obtenemos la acción desde la URI (api/3/plugins/{nombre}/{accion})
        $action = $this->getUriParam(4);

        // si hay un plugin y una acción específica, procesamos la acción
        if (!empty($pluginName) && !empty($action)) {
            $this->handlePluginAction($pluginName, $action);
            return;
        }

        // si es un método GET
        if ($this->request->isMethod(Request::METHOD_GET)) {
            // si hay un plugin específico, mostramos su información
            if (!empty($pluginName)) {
                $this->getPlugin($pluginName);
                return;
            }

            // si no hay plugin, listamos todos
            $this->listPlugins();
            return;
        }

        // método no permitido
        $this->response
            ->setHttpCode(Response::HTTP_METHOD_NOT_ALLOWED)
            ->json([
                'status' => 'error',
                'message' => 'method-not-allowed',
            ]);
    }

    private function handlePluginAction(string $pluginName, string $action): void
    {
        // validamos que solo se permitan métodos POST
        if (false === $this->request->isMethod(Request::METHOD_POST)) {
            $this->response
                ->setHttpCode(Response::HTTP_METHOD_NOT_ALLOWED)
                ->json([
                    'status' => 'error',
                    'message' => 'method-not-allowed',
                ]);
            return;
        }

        // validamos que la API key tenga acceso completo
        if (false === $this->apiKey->fullaccess) {
            $this->response
                ->setHttpCode(Response::HTTP_FORBIDDEN)
                ->json([
                    'status' => 'error',
                    'message' => 'full-access-required',
                ]);
            return;
        }

        // procesamos la acción
        switch ($action) {
            case 'enable':
                $this->enablePlugin($pluginName);
                break;

            case 'disable':
                $this->disablePlugin($pluginName);
                break;

            default:
                $this->response
                    ->setHttpCode(Response::HTTP_BAD_REQUEST)
                    ->json([
                        'status' => 'error',
                        'message' => 'invalid-action',
                    ]);
        }
    }

    private function enablePlugin(string $pluginName): void
    {
        // validamos el plugin
        $plugin = $this->validatePluginAccess($pluginName);
        if (null === $plugin) {
            return;
        }

        if (Plugins::enable($pluginName)) {
            $this->response
                ->setHttpCode(Response::HTTP_OK)
                ->json([
                    'status' => 'success',
                    'message' => $this->getLogMessages('plugin-enabled'),
                ]);
            return;
        }

        $this->response
            ->setHttpCode(Response::HTTP_BAD_REQUEST)
            ->json([
                'status' => 'error',
                'message' => $this->getLogMessages('plugin-not-enabled'),
            ]);
    }

    private function disablePlugin(string $pluginName): void
    {
        // validamos el plugin
        $plugin = $this->validatePluginAccess($pluginName);
        if (null === $plugin) {
            return;
        }

        if (Plugins::disable($pluginName)) {
            $this->response
                ->setHttpCode(Response::HTTP_OK)
                ->json([
                    'status' => 'success',
                    'message' => $this->getLogMessages('plugin-disabled')
                ]);
            return;
        }

        $this->response
            ->setHttpCode(Response::HTTP_BAD_REQUEST)
            ->json([
                'status' => 'error',
                'message' => $this->getLogMessages('plugin-not-disabled')
            ]);
    }

    private function getLogMessages(string $default = ''): string
    {
        $messages = [];

        // capturamos solo los mensajes importantes del canal master
        $logs = MiniLog::read('master', ['info', 'notice', 'warning', 'error', 'critical']);
        foreach ($logs as $log) {
            $messages[] = $log['message'];
        }

        return empty($messages) ?
            $default :
            implode('. ', $messages);
    }

    private function validatePluginAccess(string $pluginName): ?object
    {
        // obtenemos el plugin
        $plugin = Plugins::get($pluginName);

        // verificamos que existe
        if (null === $plugin) {
            $this->response
                ->setHttpCode(Response::HTTP_NOT_FOUND)
                ->json([
                    'status' => 'error',
                    'message' => 'plugin-not-found: ' . $pluginName,
                ]);
            return null;
        }

        // verificamos que no esté oculto
        if ($plugin->hidden) {
            $this->response
                ->setHttpCode(Response::HTTP_FORBIDDEN)
                ->json([
                    'status' => 'error',
                    'message' => 'plugin-hidden: ' . $pluginName,
                ]);
            return null;
        }

        // verificamos que esté instalado
        if (false === $plugin->installed) {
            $this->response
                ->setHttpCode(Response::HTTP_NOT_FOUND)
                ->json([
                    'status' => 'error',
                    'message' => 'plugin-not-found: ' . $pluginName,
                ]);
            return null;
        }

        return $plugin;
    }

    private function formatPluginData(object $plugin): array
    {
        return [
            'compatible' => $plugin->compatible,
            'description' => $plugin->description,
            'enabled' => $plugin->enabled,
            'folder' => $plugin->folder,
            'min_version' => $plugin->min_version,
            'min_php' => $plugin->min_php,
            'name' => $plugin->name,
            'require' => $plugin->require,
            'require_php' => $plugin->require_php,
            'version' => $plugin->version,
        ];
    }

    private function getPlugin(string $pluginName): void
    {
        // validamos el plugin
        $plugin = $this->validatePluginAccess($pluginName);
        if (null === $plugin) {
            return;
        }

        // devolvemos la información del plugin
        $this->response->json($this->formatPluginData($plugin));
    }

    private function listPlugins(): void
    {
        // no incluimos plugins ocultos en la API
        $allPlugins = Plugins::list(false);

        // excluimos los plugins no instalados
        $plugins = array_filter($allPlugins, function ($plugin) {
            return $plugin->installed;
        });

        $filter = $this->request->getArray('filter');
        $plugins = $this->applyFilter($plugins, $filter);

        $order = $this->request->getArray('sort');
        $plugins = $this->applySort($plugins, $order);

        // filtramos los campos que se mostrarán
        $plugins = array_map(fn($plugin) => $this->formatPluginData($plugin), $plugins);

        $this->response->json(array_values($plugins));
    }

    private function applyFilter(array $plugins, array $filter): array
    {
        if (empty($filter)) {
            return $plugins;
        }

        return array_filter($plugins, function ($plugin) use ($filter) {
            foreach ($filter as $key => $value) {
                $operator = '=';
                $field = $key;
                if (str_ends_with($key, '_gt')) {
                    $field = substr($key, 0, -3);
                    $operator = '>';
                } elseif (str_ends_with($key, '_lt')) {
                    $field = substr($key, 0, -3);
                    $operator = '<';
                } elseif (str_ends_with($key, '_gte')) {
                    $field = substr($key, 0, -4);
                    $operator = '>=';
                } elseif (str_ends_with($key, '_lte')) {
                    $field = substr($key, 0, -4);
                    $operator = '<=';
                } elseif (str_ends_with($key, '_neq')) {
                    $field = substr($key, 0, -4);
                    $operator = '!=';
                } elseif (str_ends_with($key, '_like')) {
                    $field = substr($key, 0, -5);
                    $operator = 'LIKE';
                } elseif (str_ends_with($key, '_null')) {
                    $field = substr($key, 0, -5);
                    $operator = 'IS';
                    $value = null;
                } elseif (str_ends_with($key, '_notnull')) {
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

    private function applySort(array $plugins, array $sort): array
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
