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
    const MAX_TOKENS = 500;

    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @var string
     */
    protected $controllerName;

    public function __construct(string $controllerName)
    {
        $this->cache = new Cache();
        $this->controllerName = $controllerName;
    }

    /**
     * Generates a random token for this code.
     *
     * @param string $code
     *
     * @return string
     */
    public function newToken(string $code = ''): string
    {
        return sha1($this->controllerName . $code) . '|' . $this->getRandomStr();
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
     * @param string $code
     *
     * @return bool
     */
    public function validate(string $token, string $code = ''): bool
    {
        $newTokenParts = explode('|', $this->newToken($code));
        $tokenParts = explode('|', $token);

        return count($tokenParts) === 2 && $tokenParts[0] === $newTokenParts[0];
    }

    /**
     * @return string
     */
    protected function getRandomStr(): string
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return substr(str_shuffle($chars), 0, 5);
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
    protected function saveToken(string $token): bool
    {
        $tokens = $this->getTokens();

        /// save new token
        $tokens[] = $token;
        return $this->cache->set(self::CACHE_KEY, $tokens);
    }
}
