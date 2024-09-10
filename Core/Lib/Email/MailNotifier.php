<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2022-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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
            $text = str_replace('{' . $key . '}', $value, $text);
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
        if (false === $notification->loadFromCode($notificationName)) {
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
}
