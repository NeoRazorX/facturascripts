<?php
/**
 * Plugin MultiInstancia para FacturaScripts
 *
 * Clase base abstracta para los controladores de redirección.
 * Ubicada en Lib/ para que PluginsDeploy no intente instanciarla.
 *
 * @author   CDTCOM
 * @license  MIT
 */

namespace FacturaScripts\Plugins\MultiInstancia\Lib;

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Plugins\MultiInstancia\Config;

abstract class MultiInstanciaBase extends Controller
{
    /**
     * Hostname del array Config::INSTALLATIONS al que redirige
     * este controlador. Debe sobrescribir cada clase hija.
     */
    abstract protected function getInstallationKey(): string;

    /**
     * Redirige al dominio configurado para esta instalación.
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        $key = $this->getInstallationKey();

        if (!isset(Config::INSTALLATIONS[$key])) {
            return;
        }

        header('Location: ' . Config::INSTALLATIONS[$key]['url'], true, 302);
        exit;
    }

    /**
     * Helper para acceder a los datos de una instalación por clave.
     */
    protected function installationData(string $key): array
    {
        return Config::INSTALLATIONS[$key] ?? [];
    }
}
