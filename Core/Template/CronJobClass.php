<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2024-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Template;

use Exception;
use FacturaScripts\Core\DataSrc\Users;
use FacturaScripts\Dinamic\Lib\Email\NewMail;
use FacturaScripts\Dinamic\Model\LogMessage;

/**
 * Clase base para trabajos del cron. Permite separar la lógica de cada job en su
 * propia clase, con utilidades para acumular la salida, guardarla en el log y
 * avisar por email a los administradores.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class CronJobClass
{
    /** Modo de salida: imprime el texto al momento, además de acumularlo. */
    const ECHO_MODE_FULL = 'full';

    /** Modo de salida: solamente acumula el texto, sin imprimirlo. */
    const ECHO_MODE_LOG = 'log';

    /** Nombre del job. Se usa como canal al guardar la salida en el log. */
    const JOB_NAME = 'cron';

    /**
     * Punto de entrada del job. Aquí va la lógica a ejecutar.
     */
    abstract public static function run(): void;

    /** @var string */
    private static $echo = '';

    /** @var string */
    private static $echo_mode = self::ECHO_MODE_FULL;

    /**
     * Acumula el texto en la salida del job, y si el modo es ECHO_MODE_FULL,
     * también lo imprime al momento.
     *
     * @param string $text Texto a mostrar.
     */
    protected static function echo(string $text): void
    {
        if (self::$echo_mode === self::ECHO_MODE_FULL) {
            echo $text;
            ob_flush();
        }

        self::$echo .= $text;
    }

    /**
     * Establece el modo de salida del job.
     *
     * @param string $mode ECHO_MODE_FULL para imprimir al momento, ECHO_MODE_LOG para solo acumular.
     */
    public static function echoMode(string $mode): void
    {
        self::$echo_mode = $mode;
    }

    /**
     * Devuelve toda la salida acumulada del job.
     *
     * @return string
     */
    protected static function getEcho(): string
    {
        return self::$echo;
    }

    /**
     * Acumula el texto en la salida del job sin imprimirlo, sea cual sea el modo.
     *
     * @param string $text Texto a acumular.
     */
    protected static function text(string $text): void
    {
        self::$echo .= $text;
    }

    /**
     * Guarda la salida acumulada del job en el log, con nivel info y JOB_NAME como
     * canal, troceándola en registros de 3000 caracteres. Después la vacía.
     */
    protected static function saveEcho(): void
    {
        if (empty(self::$echo)) {
            return;
        }

        // el texto está limitado a 3000 caracteres, así que debemos guardar un registro por cada 3000
        $max = 3000;
        while (mb_strlen(self::$echo, 'UTF-8') > $max) {
            $log = new LogMessage();
            $log->channel = static::JOB_NAME;
            $log->level = 'info';
            $log->message = mb_substr(self::$echo, 0, $max, 'UTF-8');
            $log->save();

            self::$echo = mb_substr(self::$echo, $max, null, 'UTF-8');
        }

        // guardamos el resto
        $log = new LogMessage();
        $log->channel = static::JOB_NAME;
        $log->level = 'info';
        $log->message = self::$echo;
        $log->save();

        self::$echo = '';
    }

    /**
     * Envía un email a todos los usuarios administradores. Si el envío falla,
     * añade el cuerpo a la salida del job.
     *
     * @param string $subject Asunto del email.
     * @param string $body Cuerpo del email. Los saltos de línea se convierten en <br>.
     */
    protected static function sendToAdmins(string $subject, string $body): void
    {
        foreach (Users::all() as $user) {
            if (false === $user->admin) {
                continue;
            }

            try {
                $mail = NewMail::create()
                    ->to($user->email, $user->nick)
                    ->subject($subject)
                    ->body(
                        '<p>Hola ' . $user->nick . ",<br><br>" . nl2br($body)
                        . '</p><br/><br/><p>Atentamente,<br/>el cron de FacturaScripts</p>'
                    );
                if (!$mail->send()) {
                    self::echo($body);
                }
            } catch (Exception $ex) {
                self::echo($ex->getCode() . ' - ' . $ex->getMessage());
            }
        }
    }
}
