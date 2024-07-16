<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core;

use FacturaScripts\Core\Model\User;
use FacturaScripts\Dinamic\Model\User as DinUser;

/**
 * Permite gestionar la sesión del usuario.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
final class Session
{
    const TOKEN_CACHE_KEY = 'session-tokens';
    const TOKEN_MAX_ITEMS = 500;

    private static $data = [];

    /** @var string */
    private static $seed = '';

    public static function get(string $key)
    {
        return self::$data[$key] ?? null;
    }

    public static function getClientIp(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $field) {
            if (isset($_SERVER[$field])) {
                return (string)$_SERVER[$field];
            }
        }

        return '::1';
    }

    public static function set(string $key, $value): void
    {
        self::$data[$key] = $value;
    }

    public static function token(bool $anonymous = false): string
    {
        // something unique in each installation
        $seed = PHP_VERSION . __FILE__ . FS_DB_NAME . FS_DB_PASS;
        $seed .= $anonymous ? 'anon' : self::$seed . self::getClientIp();

        // something that changes every hour
        $num = intval(date('YmdH'));

        // combine and generate the token
        $value = $seed . $num;
        return sha1($value) . '|' . Tools::randomString(4);
    }

    public static function tokenExists(string $token): bool
    {
        $tokens = self::tokenUsed();
        if (in_array($token, $tokens)) {
            return true;
        }

        self::tokenSave($token);
        return false;
    }

    public static function tokenValidate(string $token): bool
    {
        $tokenParts = explode('|', $token);
        if (count($tokenParts) != 2) {
            // invalid token format
            // the random part can be incremented in javascript so there is no fixed length
            return false;
        }

        $token_max_age = Tools::config('token_max_age', 72);

        // generate all possible valid tokens
        $seed1 = PHP_VERSION . __FILE__ . FS_DB_NAME . FS_DB_PASS . 'anon';
        $seed2 = PHP_VERSION . __FILE__ . FS_DB_NAME . FS_DB_PASS . self::$seed . self::getClientIp();
        $num = intval(date('YmdH'));
        $valid = [sha1($seed1 . $num), sha1($seed2 . $num)];
        for ($hour = 1; $hour <= $token_max_age; $hour++) {
            $time = strtotime('-' . $hour . ' hours');
            $alt_num = intval(date('YmdH', $time));
            $valid[] = sha1($seed1 . $alt_num);
            $valid[] = sha1($seed2 . $alt_num);
        }

        return in_array($tokenParts[0], $valid);
    }

    public static function tokenSetSeed(string $seed): void
    {
        self::$seed = $seed;
    }

    public static function user(): User
    {
        if (isset(self::$data['user']) && self::$data['user'] instanceof User) {
            return self::$data['user'];
        }

        // si la clase existe en Dinamic, la usamos
        return class_exists('\\FacturaScripts\\Dinamic\\Model\\User') ?
            new DinUser() :
            new User();
    }

    private static function tokenUsed(): array
    {
        $values = Cache::get(self::TOKEN_CACHE_KEY);
        $tokens = is_array($values) ? $values : [];
        if (count($tokens) < self::TOKEN_MAX_ITEMS) {
            return $tokens;
        }

        // reduce tokens
        return array_slice($tokens, -10);
    }

    private static function tokenSave(string $token): void
    {
        $tokens = self::tokenUsed();

        // save new token
        $tokens[] = $token;
        Cache::set(self::TOKEN_CACHE_KEY, $tokens);
    }
}
