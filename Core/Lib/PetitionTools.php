<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\Cache;
/**
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class PetitionTools
{
    /**
     * Generate a random token.
     *
     * @return string
     */
    public static function newToken()
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $token = substr(str_shuffle($chars), 0, 20);

        return $token;
    }

    /**
     * Validate if petition token exist, otherwise save it.
     *
     * @param string $token
     *
     * @return bool
     */
    public static function tokenExist(string $token)
    {
        $cacheObject = new Cache();
        $storedToken = $cacheObject->get('token');

        if (isset($storedToken) && $storedToken == $token) {
            return true;
        }

        self::saveToken($token);
        return false;
    }

    /**
     * Save new token to cache.
     * 
     * @param string $token
     *
     * @return bool
     */
    protected static function saveToken(string $token)
    {
        $cacheObject = new Cache();

        return $cacheObject->set('token', $token);
    }
}
