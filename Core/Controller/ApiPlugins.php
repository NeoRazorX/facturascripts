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
        $orden = $this->request->getArray('sort');
        $plugins = $this->applyShort($plugins, $orden);


        $this->response->setContent(json_encode(
            $plugins
        ));
    }

    private function applyFilter(array $plugins, $filtro): array
    {
        if (empty($filtro)) {
            return $plugins;
        }

        $plugins = array_filter($plugins, function ($plugin) use ($filtro) {

            foreach ($filtro as $filter => $value) {
                $operator = '=';
                $field = $filter;
                if (substr($filter, -3) === '_gt') {
                    $field = substr($filter, 0, -3);
                    $operator = '>';
                } elseif (substr($filter, -3) === '_lt') {
                    $field = substr($filter, 0, -3);
                    $operator = '<';
                } elseif (substr($filter, -4) === '_gte') {
                    $field = substr($filter, 0, -4);
                    $operator = '>=';
                } elseif (substr($filter, -4) === '_lte') {
                    $field = substr($filter, 0, -4);
                    $operator = '<=';
                } elseif (substr($filter, -4) === '_neq') {
                    $field = substr($filter, 0, -4);
                    $operator = '!=';
                } elseif (substr($filter, -5) === '_like') {
                    $field = substr($filter, 0, -5);
                    $operator = 'LIKE';
                } elseif (substr($filter, -5) === '_null') {
                    $field = substr($filter, 0, -5);
                    $operator = 'IS';
                    $value = null;
                } elseif (substr($filter, -8) === '_notnull') {
                    $field = substr($filter, 0, -8);
                    $operator = 'IS NOT';
                    $value = null;
                }
                $pluginValue = $plugin->{$field} ?? null;
                if (!$this->comparar($pluginValue, $value, $operator)) {
                    return false;
                }
            }
            return true;
        });
        return $plugins;
    }

    private function comparar($a, $b, string $operator): bool
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

    private function applyShort($plugins, $filtro): array
    {
        if (empty($filtro)) {
            return $plugins;
        }

        usort($plugins, function ($a, $b) use ($filtro) {
            foreach ($filtro as $filter => $value) {
                $plugin1 = $a->{$filter};
                $plugin2 = $b->{$filter};
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