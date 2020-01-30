<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Lib;

/**
 * Prevents brute force attacks through a list of IP addresses and their counters failed attempts.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class IPFilter
{

    /**
     * The number of seconds the system blocks access.
     */
    const BAN_SECONDS = 600;

    /**
     * Maximum number of access attempts.
     */
    const MAX_ATTEMPTS = 5;

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
        $this->filePath = \FS_FOLDER . '/MyFiles/Cache/ip.list';
        $this->ipList = [];
        $this->readFile();
    }

    /**
     * Clean the list of IP addresses and save the data.
     */
    public function clear()
    {
        $this->ipList = [];
        $this->save();
    }

    /**
     * Returns true client IP address.
     * 
     * @return string
     */
    public static function getClientIp()
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $field) {
            if (isset($_SERVER[$field])) {
                return $_SERVER[$field];
            }
        }

        return '::1';
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
        foreach ($this->ipList as $line) {
            if ($line['ip'] === $ip && $line['count'] > self::MAX_ATTEMPTS) {
                return true;
            }
        }

        return false;
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
     * Reads file and load IP addressess.
     */
    private function readFile()
    {
        if (!file_exists($this->filePath)) {
            return;
        }

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

    /**
     * Load the IP addresses in the $ ipList array
     *
     * @param array $line
     */
    private function readIp($line)
    {
        /// if row is not expired
        if (count($line) === 3 && (int) $line[2] > time()) {
            $this->ipList[] = [
                'ip' => $line[0],
                'count' => (int) $line[1],
                'expire' => (int) $line[2],
            ];
        }
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
}
