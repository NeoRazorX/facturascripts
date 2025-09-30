<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2022-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Lib\Email;

use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\Email\NewMail as DinNewMail;
use FacturaScripts\Dinamic\Model\EmailNotification;
use PHPMailer\PHPMailer\Exception;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Description of MailNotifier
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class MailNotifier
{
    public static function getText(string $text, array $params): string
    {
        foreach ($params as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $text = str_replace('{' . $key . '}', $value, $text);
            }
        }

        return $text;
    }

    /**
     * @throws Exception
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public static function send(string $notificationName, string $email, string $name = '', array $params = [], array $attach = [], array $mainBlocks = [], array $footerBlocks = []): bool
    {
        // ¿La notificación existe?
        $notification = new EmailNotification();
        if (false === $notification->load($notificationName)) {
            Tools::log()->warning('email-notification-not-exists', ['%name%' => $notificationName]);
            return false;
        }

        // ¿Está desactivada?
        if (false === $notification->enabled) {
            Tools::log()->warning('email-notification-disabled', ['%name%' => $notificationName]);
            return false;
        }

        // cargamos la clase NewMail
        $newMail = new DinNewMail();

        // añadimos algunos campos más a los parámetros
        if (!isset($params['email'])) {
            $params['email'] = $email;
        }
        if (!isset($params['name'])) {
            $params['name'] = $name;
        }
        if (!isset($params['verificode'])) {
            $params['verificode'] = $newMail->verificode;
        }

        $newMail->to($email, $name);
        $newMail->title = static::getText($notification->subject, $params);
        $newMail->text = static::getText($notification->body, $params);
        static::replaceTextToBlock($newMail, $params);

        foreach ($mainBlocks as $block) {
            $newMail->addMainBlock($block);
        }

        foreach ($footerBlocks as $block) {
            $newMail->addFooterBlock($block);
        }

        foreach ($attach as $adjunto) {
            $newMail->addAttachment($adjunto, basename($adjunto));
        }

        return $newMail->send();
    }

    /**
     * Los bloques se añaden al campo de params como 'block1', 'block2', ...
     * Cada bloque se compone de un string o de un objeto que herede de BaseBlock.
     * El texto del email puede contener {block1}, {block2}, ... Para indicar
     * dónde se debe insertar cada bloque. Si no se encuentra la etiqueta, el bloque
     * se añade al final del email.
     */
    protected static function replaceTextToBlock(DinNewMail &$newMail, array $params): void
    {
        // si no hay parámetros o texto, no hacemos nada
        if (empty($params) || empty($newMail->text)) {
            return;
        }

        // Obtenemos las coincidencias de {block1}, {block2}, ... sobre el texto
        preg_match_all('/{block(\d+)}/', $newMail->text, $matches);

        // si no hay coincidencias, no hacemos nada
        if (empty($matches[1])) {
            return;
        }

        // obtenemos el texto hasta el primer bloque, y entre los bloques
        // para añadir un TextBlock con el texto encontrado
        $text = $newMail->text;
        $newMail->text = '';
        $lastPos = 0;

        // recorremos los bloques encontrados
        foreach ($matches[1] as $blockIndex) {
            $substr = substr($text, $lastPos, strpos($text, '{block' . $blockIndex . '}') - $lastPos);
            $lastPos = strpos($text, '{block' . $blockIndex . '}') + strlen('{block' . $blockIndex . '}');
            if (empty($substr) && isset($params['block' . $blockIndex]) && $params['block' . $blockIndex] instanceof BaseBlock) {
                $newMail->addMainBlock($params['block' . $blockIndex]);
                continue;
            }

            $newMail->addMainBlock(new TextBlock($substr));
            if (isset($params['block' . $blockIndex]) && $params['block' . $blockIndex] instanceof BaseBlock) {
                $newMail->addMainBlock($params['block' . $blockIndex]);
            }
        }

        $substr = substr($text, $lastPos);
        if (false === empty($substr)) {
            $newMail->addMainBlock(new TextBlock($substr));
        }
    }
}
