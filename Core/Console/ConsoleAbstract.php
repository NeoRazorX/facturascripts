<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Console;

/**
 * Class ConsoleAbstract
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
abstract class ConsoleAbstract
{
    /**
     * Arguments received from command execution.
     *
     * @var array
     */
    protected $argv;

    /**
     * Start point to run the command.
     *
     * @param array $params
     *
     * @return int
     */
    abstract public function run(...$params): int;

    /**
     * Return description about this class.
     *
     * @return string
     */
    abstract public function getDescription(): string;

    /**
     * Print help information to the user.
     */
    abstract public function showHelp();

    /**
     * Returns an associative array of available methods for the user.
     * Add more options if you want to add support for custom methods.
     *      [
     *          '-h'        => 'showHelp',
     *          '--help'    => 'showHelp',
     *      ]
     *
     * @return array
     */
    public function getUserMethods(): array
    {
        return [
            '-h' => 'showHelp',
            '--help' => 'showHelp',
        ];
    }

    /**
     * Ask user to continue and return a boolean.
     *
     * @return bool
     */
    public function areYouSure()
    {
        echo \PHP_EOL . 'Are you sure? [y/n] ';
        $stdin = fgets(STDIN);
        switch (trim($stdin)) {
            case 'y':
            case 'Y':
            case 'yes':
            case 'Yes':
                return true;
            default:
                return false;
        }
    }
}
