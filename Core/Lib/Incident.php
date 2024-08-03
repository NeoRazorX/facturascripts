<?php declare(strict_types=1);

namespace FacturaScripts\Core\Lib;

use FacturaScripts\Core\Cache;

class Incident
{
    public const INCIDENT_EXPIRATION_TIME = 600;
    public const IP_LIST = 'login-ip-list';
    public const USER_LIST = 'login-user-list';
    public const MAX_INCIDENT_COUNT = 6;

    public function userHasManyIncidents(string $ip, string $username = '')
    {
        // get ip count on the list
        $ipCount = 0;
        foreach (self::getIpList() as $item) {
            if ($item['ip'] === $ip) {
                $ipCount++;
            }
        }
        if ($ipCount >= self::MAX_INCIDENT_COUNT) {
            return true;
        }

        // get user count on the list
        $userCount = 0;
        foreach ($this->getUserList() as $item) {
            if ($item['user'] === $username) {
                $userCount++;
            }
        }
        return $userCount >= self::MAX_INCIDENT_COUNT;
    }

    private function getIpList():array
    {
        $ipList = Cache::get(self::IP_LIST);
        if (false === is_array($ipList)) {
            return [];
        }

        // remove expired items
        $newList = [];
        foreach ($ipList as $item) {
            if (time() - $item['time'] < self::INCIDENT_EXPIRATION_TIME) {
                $newList[] = $item;
            }
        }
        return $newList;
    }

    private function getUserList():array
    {
        $userList = Cache::get(self::USER_LIST);
        if (false === is_array($userList)) {
            return [];
        }

        // remove expired items
        $newList = [];
        foreach ($userList as $item) {
            if (time() - $item['time'] < self::INCIDENT_EXPIRATION_TIME) {
                $newList[] = $item;
            }
        }
        return $newList;
    }

    public function clearIncidents():void
    {
        Cache::delete(self::IP_LIST);
        Cache::delete(self::USER_LIST);
    }

    public function saveIncident(string $ip, string $user = '', ?int $time = null):void
    {
        // add the current IP to the list
        $ipList = self::getIpList();
        $ipList[] = [
            'ip' => $ip,
            'time' => ($time ?? time()),
        ];

        // save the list in cache
        Cache::set(self::IP_LIST, $ipList);

        // if the user is not empty, save the incident
        if (empty($user)) {
            return;
        }

        // add the current user to the list
        $userList = $this->getUserList();
        $userList[] = [
            'user' => $user,
            'time' => ($time ?? time()),
        ];

        // save the list in cache
        Cache::set(self::USER_LIST, $userList);
    }
}
