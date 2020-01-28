<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Class to prevent duplicated petitions.
 *
 * @author Juan José Prieto Dzul    <juanjoseprieto88@gmail.com>
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 */
class MultiRequestProtection
{

    const CACHE_KEY = 'MultiRequestProtection';
    const MAX_TOKENS = 100;
    const TOKEN_LENGTH = 20;

    /**
     *
     * @var Cache
     */
    protected $cache;

    public function __construct()
    {
        $this->cache = new Cache();
    }

    /**
     * Generates a random token.
     *
     * @return string
     */
    public function newToken()
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return substr(str_shuffle($chars), 0, self::TOKEN_LENGTH);
    }

    /**
     * Validates if a petition token exist, otherwise save it.
     *
     * @param string $token
     *
     * @return bool
     */
    public function tokenExist(string $token)
    {
        $tokens = $this->getTokens();
        if (in_array($token, $tokens)) {
            return true;
        }

        $this->saveToken($token);
        return false;
    }

    /**
     * 
     * @return array
     */
    protected function getTokens()
    {
        $values = $this->cache->get(self::CACHE_KEY);
        $tokens = is_array($values) ? $values : [];
        if (count($tokens) < self::MAX_TOKENS) {
            return $tokens;
        }

        /// reduce tokens
        return array_slice($tokens, -10);
    }

    /**
     * Saves the new token to cache.
     * 
     * @param string $token
     *
     * @return bool
     */
    protected function saveToken(string $token)
    {
        $tokens = $this->getTokens();

        /// save new token
        $tokens[] = $token;
        return $this->cache->set(self::CACHE_KEY, $tokens);
    }
}
