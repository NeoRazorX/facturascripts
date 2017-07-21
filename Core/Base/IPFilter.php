<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  carlos@facturascripts.com
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
 * Previene los ataques de fuerza bruta
 *
 * @author Carlos García Gómez
 */
class IPFilter
{

    /**
     * Número máximo de intentos de acceso
     */
    const MAX_ATTEMPTS = 5;

    /**
     * Número de segundos que el sistema bloquea el acceso
     */
    const BAN_SECONDS = 600;

    /**
     * Ruta del archivo de la cache
     * @var string
     */
    private $filePath;

    /**
     * Contiene las direcciones IP
     * @var array
     */
    private $ipList;

    /**
     * IPFilter constructor.
     * @param string $folder
     */
    public function __construct($folder = '')
    {
        $this->filePath = $folder . '/Cache/ip.list';
        $this->ipList = [];

        if (file_exists($this->filePath)) {
            /// Read IP list file
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
     * @param  array $line
     */
    private function readIp($line)
    {
        if (count($line) === 3 && (int) $line[2] > time()) { /// if not expired
            $this->ipList[] = [
                'ip' => $line[0],
                'count' => (int) $line[1],
                'expire' => (int) $line[2]
            ];
        }
    }

    /**
     * Devuelve true si los intentos de acceso desde una IP sobrepasa el límite MAX_ATTEMPTS
     * @param $ip
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
     * Cuenta las veces que un usuario intenta acceder desde una dirección IP
     * @param $ip
     */
    public function setAttempt($ip)
    {
        $found = false;
        foreach ($this->ipList as $key => $line) {
            if ($line['ip'] === $ip) {
                $this->ipList[$key]['count']++;
                $this->ipList[$key]['expire'] = time() + self::BAN_SECONDS;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $this->ipList[] = [
                'ip' => $ip,
                'count' => 1,
                'expire' => time() + self::BAN_SECONDS
            ];
        }

        $this->save();
    }

    /**
     * Almacena las direcciones IP en la cache ip.list
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
}
