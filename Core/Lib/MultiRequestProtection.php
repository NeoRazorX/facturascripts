<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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
    const MAX_TOKEN_AGE = 4;
    const MAX_TOKENS = 500;
    const RANDOM_STRING_LENGTH = 6;

    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @var string
     */
    protected $seed;

    public function __construct()
    {
        $this->cache = new Cache();

        // something unique in each installation
        $this->seed = PHP_VERSION . __FILE__ . FS_DB_NAME . FS_DB_PASS . FS_CACHE_PREFIX;
    }

    /**
     * @param string $seed
     */
    public function addSeed(string $seed)
    {
        $this->seed .= $seed;
    }

    /**
     * Generates a random token.
     *
     * @return string
     */
    public function newToken(): string
    {
        // something that changes every hour
        $num = intval(date('YmdH')) + strlen($this->seed);

        // combine and generate the token
        $value = $this->seed . $num;
        return sha1($value) . '|' . $this->getRandomStr();
    }

    /**
     * Validates if a petition token exist, otherwise save it.
     *
     * @param string $token
     *
     * @return bool
     */
    public function tokenExist(string $token): bool
    {
        $tokens = $this->getTokens();
        if (in_array($token, $tokens)) {
            return true;
        }

        $this->saveToken($token);
        return false;
    }

    /**
     * @param string $token
     *
     * @return bool
     */
    public function validate(string $token): bool
    {
        $tokenParts = explode('|', $token);
        if (count($tokenParts) != 2) {
            // invalid token format
            // the random part can be incremented in javascript so there is no fixed length
            return false;
        }

        // check all valid tokens roots
        $num = intval(date('YmdH')) + strlen($this->seed);
        $valid = [sha1($this->seed . $num)];
        for ($hour = 1; $hour <= self::MAX_TOKEN_AGE; $hour++) {
            $time = strtotime('-' . $hour . ' hours');
            $altNum = intval(date('YmdH', $time)) + strlen($this->seed);
            $valid[] = sha1($this->seed . $altNum);
        }

        return in_array($tokenParts[0], $valid);
    }

    /**
     * @return string
     */
    protected function getRandomStr(): string
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return substr(str_shuffle($chars), 0, self::RANDOM_STRING_LENGTH);
    }

    /**
     * @return array
     */
    protected function getTokens(): array
    {
        $values = $this->cache->get(self::CACHE_KEY);
        $tokens = is_array($values) ? $values : [];
        if (count($tokens) < self::MAX_TOKENS) {
            return $tokens;
        }

        // reduce tokens
        return array_slice($tokens, -10);
    }

    /**
     * Saves the new token to cache.
     *
     * @param string $token
     *
     * @return bool
     */
    protected function saveToken(string $token): bool
    {
        $tokens = $this->getTokens();

        // save new token
        $tokens[] = $token;
        return $this->cache->set(self::CACHE_KEY, $tokens);
    }
}
