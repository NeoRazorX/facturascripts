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

use Symfony\Component\Finder\Finder;

/**
 * This class is a start point for php-cli commands.
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class ConsoleManager extends ConsoleAbstract
{
    /**
     * ConsoleManager constructor.
     *
     * @param int   $argc
     * @param array $argv
     */
    public function __construct($argc, $argv)
    {
        $this->argv = $argv;

        // Check that at least there are 2 params (console.php & command)
        if ($argc >= 2) {
            // Check if first param is an option or a command
            switch ($this->argv[1]) {
                case '-l':
                case '--list':
                    $this->showAvailableCommands();
                    break;

                case '-h':
                case '--help':
                    break;

                case 0 === \strpos($this->argv[1], '-'):
                case 0 === \strpos($this->argv[1], '--'):
                    $this->optionNotAvailable($this->argv[0], $this->argv[1]);
                    break;
                default:
                    $this->run();
            }
        }

        $this->showHelp();
    }

    /**
     * Start point to run the command.
     *
     * @param array $params
     *
     * @return mixed
     */
    public function run(...$params): int
    {
        $cmd = $this->argv[1];

        if (class_exists(__NAMESPACE__ . '\\' . $cmd)) {
            exit($this->execute());
        }

        echo \PHP_EOL . 'ERROR: Command "' . $cmd . '" not found.' . \PHP_EOL . \PHP_EOL;

        $this->showHelp();
        die();
    }

    /**
     * Return description about this class.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'The Console Manager';
    }

    /**
     * Print help information to the user.
     */
    public function showHelp()
    {
        echo 'Use as: php console.php [COMMAND] [OPTIONS]' . \PHP_EOL;
        echo 'Available options:' . \PHP_EOL;
        echo '   -h, --help        Show this help.' . \PHP_EOL;
        echo '   -l, --list        Show a list of available commands.' . \PHP_EOL;
        echo \PHP_EOL;
    }

    /**
     * Returns an associative array of available methods for the user.
     * Add more options if you want to add support for custom methods.
     *      [
     *          '-h'        => 'showHelp',
     *          '--help'    => 'showHelp',
     *          '-l'        => 'showAvailableCommands',
     *          '--list'    => 'showAvailableCommands',
     *      ]
     *
     * @return array
     */
    public function getUserMethods(): array
    {
        // Adding extra method
        $methods = parent::getUserMethods();
        $methods['-l'] = 'showAvailableCommands';
        $methods['--list'] = 'showAvailableCommands';

        return $methods;
    }

    /**
     * Exec the command with the given options
     *
     * @return mixed
     */
    public function execute()
    {
        $status = -1;
        $params = $this->argv;
        \array_shift($params); // Extract console.php
        // command class
        $cmd = \array_shift($params); // Extract command
        // $params contains adicional parameters if are received

        switch ($cmd) {
            case '-h':
            case '--help':
                $this->getAvailableOptions($cmd);
                break;
            default:
                $className = __NAMESPACE__ . '\\' . $cmd;
                $methods = \call_user_func([new $className(), 'getUserMethods']);
                // Forced in ConsoleAbstract, but we don't want to show it to users
                $methods['run'] = 'run';

                // If not alias, we want to directly run
                $alias = $params[0] ?? 'run';
                // If not method match, show how it works
                $method = $methods[$alias[0]] ?? 'showHelp';

                if (\array_key_exists($alias, $methods)) {
                    // Check if method is in class or parent class
                    if (\in_array($method, \get_class_methods($className), false) ||
                        \in_array($method, \get_class_methods(\get_parent_class($className)), false)
                    ) {
                        $status = \call_user_func_array([new $className(), 'run'], $params);
                        break;
                    }
                    // Can be deleted, but starting with this can be helpful
                    if (FS_DEBUG) {
                        $msg = '#######################################################################################'
                            . \PHP_EOL . '# ERROR: "' . $method . '" not defined in "' . $className . '"' . \PHP_EOL
                            . '#    Maybe you have a misspelling on the method name or is a missing declaration?'
                            . \PHP_EOL
                            . '#######################################################################################'
                            . \PHP_EOL;
                        echo $msg;
                    }
                    break;
                }

                // Can be deleted, but starting with this can be helpful
                if (FS_DEBUG) {
                    $msg = '#######################################################################################'
                        . \PHP_EOL . '# ERROR: "' . $alias . '" not in "getUserMethods" for "' . $className . '"'
                        . \PHP_EOL . '#    Maybe you are missing to put it in to getUserMethods?' . \PHP_EOL
                        . '#######################################################################################'
                        . \PHP_EOL;
                    echo $msg;
                }

                $this->optionNotAvailable($cmd, $alias);
                $this->getAvailableOptions($cmd);
        }
        return $status;
    }

    /**
     * Returns a list of available methods for this command.
     *
     * @param string $cmd
     */
    public function getAvailableOptions($cmd)
    {
        echo 'Available options for "' . $cmd . '"' . \PHP_EOL . \PHP_EOL;

        $className = __NAMESPACE__ . '\\' . $cmd;
        $options = \call_user_func([new $className(), 'getUserMethods']);

        foreach ((array) $options as $option => $methods) {
            echo '   ' . $option . \PHP_EOL;
        }

        echo \PHP_EOL . 'Use as: php console.php ' . $cmd . ' [OPTIONS]' . \PHP_EOL . \PHP_EOL;

        die();
    }

    /**
     * Print help information to the user.
     */
    public function showAvailableCommands()
    {
        echo 'Available commands:' . \PHP_EOL;

        foreach ($this->getAvailableCommands() as $cmd) {
            $className = __NAMESPACE__ . '\\' . $cmd;
            echo '   - ' . $cmd . ' : ' . \call_user_func([new $className(), 'getDescription']) . \PHP_EOL;
        }
        echo \PHP_EOL;
    }

    /**
     * Return a list of available commands
     *
     * @return array
     */
    public function getAvailableCommands(): array
    {
        $available = [];
        $allClasses = $this->getAllFcqns(__DIR__);
        $exclude = [__NAMESPACE__ . '\\' . 'ConsoleManager', __NAMESPACE__ . '\\' . 'ConsoleAbstract'];
        foreach ($allClasses as $class) {
            if (0 === \strpos($class, __NAMESPACE__ . '\\') && !\in_array($class, $exclude, false)) {
                $available[] = \str_replace(__NAMESPACE__ . '\\', '', $class);
            }
        }
        return $available;
    }

    /**
     * Show that this option is not available.
     *
     * @param string $cmd
     * @param string $option
     */
    private function optionNotAvailable($cmd, $option)
    {
        echo 'Option "' . $option . '" not available for "' . $cmd . '".' . \PHP_EOL . \PHP_EOL;
    }

    /**
     * TODO: All this following methods can be replaced when we have an alternative way to manage services.
     *
     * @example https://symfony.com/doc/current/console.html#getting-services-from-the-service-container
     * @example https://symfony.com/doc/current/service_container.html#handling-multiple-services
     *
     * This way, we can register new services, and get it when needed.
     *
     * This isn't the best way to do that, but at this time, is a first rapprochement that works.
     */

    /**
     * @param string $projectRoot
     *
     * @return array
     */
    private function getAllFcqns($projectRoot): array
    {
        $fileNames = $this->getFileNames($projectRoot);
        $fcqns = [];
        foreach ($fileNames as $fileName) {
            $fcqns[] = $this->getFullNameSpace($fileName) . '\\' . $this->getClassName($fileName);
        }

        return $fcqns;
    }

    /**
     * @param string $path
     *
     * @return array
     */
    private function getFileNames($path): array
    {
        $finder = new Finder();
        $finder->files()->in($path)->name('*.php');
        $fileNames = [];
        foreach ($finder as $finderFile) {
            $fileNames[] = $finderFile->getRealPath();
        }

        return $fileNames;
    }

    /**
     * @param string $fileName
     *
     * @return mixed
     */
    private function getFullNameSpace($fileName)
    {
        $lines = file($fileName);
        $array = preg_grep('/^namespace /', $lines);
        $namespaceLine = array_shift($array);
        $matches = [];
        preg_match('/^namespace (.*);$/', $namespaceLine, $matches);
        return array_pop($matches);
    }

    /**
     * @param string $fileName
     *
     * @return mixed
     */
    private function getClassName($fileName)
    {
        $dirsAndFile = explode(DIRECTORY_SEPARATOR, $fileName);
        $fileName = array_pop($dirsAndFile);
        $nameAndExt = explode('.', $fileName);
        return array_shift($nameAndExt);
    }
}
