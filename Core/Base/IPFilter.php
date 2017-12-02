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
 * Prevents brute force attacks through a list of IP addresses and their counters failed attempts.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class IPFilter
{
    /**
     * Maximum number of access attempts.
     */
    const MAX_ATTEMPTS = 5;

    /**
     * The number of seconds the system blocks access.
     */
    const BAN_SECONDS = 600;

    /**
     * Path of the file with the list.
     *
     * @var string
     */
    private $filePath;

    /**
     * Contains IP addresses.
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

        // Check needed to pass the unit tests
        /**
        $basePath = FS_FOLDER . '/Cache';
        $this->filePath = $basePath . '/ip.list';
        if (!file_exists($basePath) && !@mkdir($basePath, 0775, true) && !is_dir($basePath)) {
            $minilog = new MiniLog();
            $i18n = new Translator();
            $minilog->critical($i18n->trans('cant-create-folder', [$basePath]));
        }
         */

        if (file_exists($this->filePath)) {
            /// We read the list of IP addresses in the file
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
     * Load the IP addresses in the $ ipList array
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
     * Returns true if attempts to access from the IP address exceed the MAX_ATTEMPTS limit.
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
     * Add or increase the attempt counter of the provided IP address.
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
     * Stores the list of IP addresses in the file.
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
     * Clean the list of IP addresses and save the data.
     */
    public function clear()
    {
        $this->ipList = [];
        $this->save();
    }
}
