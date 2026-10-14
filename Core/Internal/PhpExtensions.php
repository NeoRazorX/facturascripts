<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Tools;

/**
 * Comprueba las extensiones de PHP que necesita FacturaScripts. El instalador las comprueba
 * al instalar, pero el servidor puede perderlas después (al cambiar de versión de PHP o de hosting).
 */
final class PhpExtensions
{
    const REQUIRED = ['bcmath', 'curl', 'fileinfo', 'gd', 'mbstring', 'openssl', 'simplexml', 'zip'];

    /** @var array extensiones que se tratan como no cargadas, para simular su ausencia en los tests */
    public static $simulateMissing = [];

    /**
     * Devuelve las extensiones de la lista que no están cargadas.
     */
    public static function missing(array $extensions = self::REQUIRED): array
    {
        $missing = [];
        foreach ($extensions as $extension) {
            if (in_array($extension, self::$simulateMissing, true) || false === extension_loaded($extension)) {
                $missing[] = $extension;
            }
        }

        return $missing;
    }

    /**
     * Añade un aviso por cada extensión de la lista que no está cargada.
     * Devuelve true si están todas cargadas.
     */
    public static function warnMissing(array $extensions = self::REQUIRED): bool
    {
        $missing = self::missing($extensions);
        foreach ($missing as $extension) {
            Tools::log()->warning('php-extension-not-found', ['%extension%' => $extension]);
        }

        return empty($missing);
    }
}
