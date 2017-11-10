<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Base;

/**
 * Previene los ataques de fuerza bruta mediante una lista de direcciones IP
 * y sus contadores de intentos fallidos.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class IPFilter
{
    /**
     * Número máximo de intentos de acceso.
     */
    const MAX_ATTEMPTS = 5;

    /**
     * Número de segundos que el sistema bloquea el acceso.
     */
    const BAN_SECONDS = 600;

    /**
     * Ruta del archivo con la lista.
     *
     * @var string
     */
    private $filePath;

    /**
     * Contiene las direcciones IP.
     *
     * @var array
     */
    private $ipList;

    /**
     * IPFilter constructor.
     */
    public function __construct()
    {
        $this->filePath = FS_FOLDER . '/Cache/ip.list';
        $this->ipList = [];

        if (file_exists($this->filePath)) {
            /// leemos la lista de direcciones de IP del archivo
            $file = fopen($this->filePath, 'rb');
            if ($file) {
                while (!feof($file)) {
                    $line = explode(';', trim(fgets($file)));
                    $this->readIp($line);
                }

                fclose($file);
            }
        }
    }

    /**
     * Carga las direcciones IP en el array $ipList
     *
     * @param array $line
     */
    private function readIp($line)
    {
        /// si no ha expirado
        if (count($line) === 3 && (int) $line[2] > time()) {
            $this->ipList[] = [
                'ip' => $line[0],
                'count' => (int) $line[1],
                'expire' => (int) $line[2],
            ];
        }
    }

    /**
     * Devuelve true si los intentos de acceso desde la dirección IP sobrepasa el límite MAX_ATTEMPTS.
     *
     * @param string $ip
     *
     * @return bool
     */
    public function isBanned($ip)
    {
        $banned = false;

        foreach ($this->ipList as $line) {
            if ($line['ip'] === $ip && $line['count'] > self::MAX_ATTEMPTS) {
                $banned = true;
                break;
            }
        }

        return $banned;
    }

    /**
     * Añade o incrementa el contador de intentos de la dirección IP proporcionada.
     *
     * @param string $ip
     */
    public function setAttempt($ip)
    {
        $found = false;
        foreach ($this->ipList as $key => $line) {
            if ($line['ip'] === $ip) {
                ++$this->ipList[$key]['count'];
                $this->ipList[$key]['expire'] = time() + self::BAN_SECONDS;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $this->ipList[] = [
                'ip' => $ip,
                'count' => 1,
                'expire' => time() + self::BAN_SECONDS,
            ];
        }

        $this->save();
    }

    /**
     * Almacena la lista de direcciones IP en el archivo.
     */
    private function save()
    {
        $file = fopen($this->filePath, 'wb');
        if ($file) {
            foreach ($this->ipList as $line) {
                fwrite($file, $line['ip'] . ';' . $line['count'] . ';' . $line['expire'] . "\n");
            }

            fclose($file);
        }
    }

    /**
     * Limpia la lista de direcciones IP y guarda los datos.
     */
    public function clear()
    {
        $this->ipList = [];
        $this->save();
    }
}
