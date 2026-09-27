<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\AppKey;
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
    const PURPOSE = 'multireqtoken';
    const RANDOM_STRING_LENGTH = 6;

    /** @var string */
    protected static $seed;

    public function __construct()
    {
        if (false === isset(self::$seed)) {
            $this->clearSeed();
        }
    }

    public function addSeed(string $seed): void
    {
        self::$seed .= $seed;
    }

    public function clearSeed(): void
    {
        // la firma con la clave de la instalación ya hace que el token sea único en cada instalación
        self::$seed = '';
    }

    /**
     * Generates a random token.
     *
     * @return string
     */
    public function newToken(): string
    {
        // firmamos la semilla junto con algo que cambia cada hora
        return AppKey::sign(self::PURPOSE, self::$seed . '|' . date('YmdH')) . '|' . $this->getRandomStr();
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

        // comprobamos la firma de la hora actual y de las MAX_TOKEN_AGE horas anteriores
        for ($hour = 0; $hour <= self::MAX_TOKEN_AGE; $hour++) {
            $date = date('YmdH', strtotime('-' . $hour . ' hours'));
            if (AppKey::verify(self::PURPOSE, self::$seed . '|' . $date, $tokenParts[0])) {
                return true;
            }
        }

        return false;
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
