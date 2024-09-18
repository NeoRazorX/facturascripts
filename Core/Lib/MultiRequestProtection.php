<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Cache;

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

    /** @var string */
    protected static $seed;

    public function __construct()
    {
        // something unique in each installation
        if (false === isset(self::$seed)) {
            $this->clearSeed();
        }
    }

    public function addSeed(string $seed)
    {
        self::$seed .= $seed;
    }

    public function clearSeed(): void
    {
        self::$seed = PHP_VERSION . __FILE__ . FS_DB_NAME . FS_DB_PASS;
    }

    /**
     * Generates a random token.
     *
     * @return string
     */
    public function newToken(): string
    {
        // something that changes every hour
        $num = intval(date('YmdH')) + strlen(self::$seed);

        // combine and generate the token
        $value = self::$seed . $num;
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

    public function validate(string $token): bool
    {
        $tokenParts = explode('|', $token);
        if (count($tokenParts) != 2) {
            // invalid token format
            // the random part can be incremented in javascript so there is no fixed length
            return false;
        }

        // check all valid tokens roots
        $num = intval(date('YmdH')) + strlen(self::$seed);
        $valid = [sha1(self::$seed . $num)];
        for ($hour = 1; $hour <= self::MAX_TOKEN_AGE; $hour++) {
            $time = strtotime('-' . $hour . ' hours');
            $altNum = intval(date('YmdH', $time)) + strlen(self::$seed);
            $valid[] = sha1(self::$seed . $altNum);
        }

        return in_array($tokenParts[0], $valid);
    }

    protected function getRandomStr(): string
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return substr(str_shuffle($chars), 0, self::RANDOM_STRING_LENGTH);
    }

    protected function getTokens(): array
    {
        $values = Cache::get(self::CACHE_KEY);
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
        Cache::set(self::CACHE_KEY, $tokens);
        return true;
    }
}
