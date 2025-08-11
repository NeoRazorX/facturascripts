<?php

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Plugins;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Template\ApiController;

class ApiPlugins extends ApiController
{
    protected function runResource(): void
    {
        // si el método no es GET, devolvemos un error
        if (false === $this->request->isMethod(Request::METHOD_GET)) {
            $this->response->setHttpCode(Response::HTTP_METHOD_NOT_ALLOWED);
            $this->response->setContent(json_encode([
                'status' => 'error',
                'message' => 'Method not allowed',
            ]));
            return;
        }

        $plugins = Plugins::list();
        $filter = $this->request->getArray('filter');
        $plugins = $this->applyFilter($plugins, $filter);
        $order = $this->request->getArray('sort');
        $plugins = $this->applyShort($plugins, $order);


        $this->response->setContent(json_encode(
            $plugins
        ));
    }

    private function applyFilter(array $plugins, $filter): array
    {
        if (empty($filter)) {
            return $plugins;
        }

        $plugins = array_filter($plugins, function ($plugin) use ($filter) {

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
                $pluginValue = $plugin->{$field} ?? null;
                if (!$this->compare($pluginValue, $value, $operator)) {
                    return false;
                }
            }
            return true;
        });
        return $plugins;
    }

    private function compare($a, $b, string $operator): bool
    {
        switch ($operator) {
            case '>':
                return $a > $b;
            case '<':
                return $a < $b;
            case '>=':
                return $a >= $b;
            case '<=':
                return $a <= $b;
            case '!=':
                return $a != $b;
            case 'LIKE':
                return stripos((string) $a, (string) $b) !== false;
            case 'IS':
                return $a === null;
            case 'IS NOT':
                return $a !== null;
            default:
                return $a == $b;
        }
    }

    private function applyShort($plugins, $filter): array
    {
        if (empty($filter)) {
            return $plugins;
        }

        usort($plugins, function ($a, $b) use ($filter) {
            foreach ($filter as $key => $value) {
                $plugin1 = $a->{$key};
                $plugin2 = $b->{$key};
                if ($plugin1 === $plugin2) {
                    return 0;
                }
                if ($value === 'DESC') {
                    return ($plugin1 < $plugin2) ? 1 : -1;
                }
                return ($plugin1 < $plugin2) ? -1 : 1;
            }
        });
        return $plugins;
    }
}