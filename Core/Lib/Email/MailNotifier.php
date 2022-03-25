<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Dinamic\Lib\Email\NewMail as DinNewMail;
use FacturaScripts\Dinamic\Model\EmailNotification;

/**
 * Description of MailNotifier
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class MailNotifier
{

    public static function send(string $notificationName, string $email, string $name = '', array $params = [])
    {
        $notification = new EmailNotification();
        if (false === $notification->loadFromCode($notificationName)) {
            ToolBox::i18nLog()->warning('email-notification-not-exists', ['%name%' => $notificationName]);
            return;
        }
        if (false === $notification->enabled) {
            return;
        }

        $newMail = new DinNewMail();
        $newMail->addAddress($email, $name);

        /**
         * Add email and name to params
         */
        if (!isset($params['email'])) {
            $params['email'] = $email;
        }
        if (!isset($params['name'])) {
            $params['name'] = $name;
        }

        $newMail->title = static::getText($notification->subject, $params);
        $newMail->text = static::getText($notification->body, $params);
        $newMail->send();
    }

    protected static function getText(string $text, array $params): string
    {
        foreach ($params as $key => $value) {
            $text = str_replace('{' . $key . '}', $value, $text);
        }

        return $text;
    }
}
