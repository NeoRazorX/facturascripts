<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Contract\MailerInterface;
use FacturaScripts\Core\Model\Empresa;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Dinamic\Model\EmailNotification;
use PHPMailer\PHPMailer\PHPMailer;

final class Mailer
{
    /** @var array */
    public $attachments = [];

    /** @var array */
    public $bcc = [];

    /** @var string */
    public $body = '';

    /** @var array */
    public $cc = [];

    /** @var string */
    private $mailBox;

    /** @var array */
    private static $mailBoxes;

    /** @var MailerInterface[] */
    private static $mods = [];

    /** @var array */
    public $replyTo = [];

    /** @var string */
    public $signature;

    /** @var string */
    public $subject = '';

    /** @var array */
    public $to = [];

    /** @var string */
    public $verificode;

    public function __construct(string $mailBox = '')
    {
        self::loadMailBoxes();

        $this->mailBox = empty($mailBox) ?
            array_keys(self::$mailBoxes)[0] :
            $mailBox;

        $this->verificode = Tools::randomString(20);
    }

    public static function addMod(MailerInterface $mod): void
    {
        self::$mods[] = $mod;
    }

    public function attach(Archivo $archivo): self
    {
        $this->attachments[] = [
            'name' => $archivo->name,
            'path' => $archivo->path
        ];

        return $this;
    }

    public function bcc(Address $address): self
    {
        $this->bcc[$address->email] = $address->name;

        return $this;
    }

    public function body(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function bodyHtml(string $template, array $data = []): self
    {
        $this->body = Html::render($template, $data);

        return $this;
    }

    public function canSend(): bool
    {
        return true;
    }

    public function cc(Address $address): self
    {
        $this->cc[$address->email] = $address->name;

        return $this;
    }

    public static function from(string $mailBox = ''): self
    {
        return new self($mailBox);
    }

    public static function fromCompany(Empresa $empresa): self
    {
        $mailBox = '';
        foreach (self::$mods as $mod) {
            $mailBox = $mod->getMailBoxFromCompany($empresa);
            if (!empty($mailBox)) {
                break;
            }
        }

        return new self($mailBox);
    }

    public static function fromUser(User $user): self
    {
        $mailBox = '';
        foreach (self::$mods as $mod) {
            $mailBox = $mod->getMailBoxFromUser($user);
            if (!empty($mailBox)) {
                break;
            }
        }

        return new self($mailBox);
    }

    public static function mailBoxes(): array
    {
        self::loadMailBoxes();

        return self::$mailBoxes;
    }

    public function notify(EmailNotification $notification, Address $address, array $params = []): self
    {
        if(!$notification->isEnabled()){
            // TODO
        };

        // añadimos algunos campos más a los parámetros
        if (!isset($params['email'])) {
            $params['email'] = $address->email;
        }
        if (!isset($params['name'])) {
            $params['name'] = $address->name;
        }
        if (!isset($params['verificode'])) {
            $params['verificode'] = Tools::randomString(20);
        }

        // reemplazamos los campos
        $subject = $this->notificationReplace($notification->subject, $params);
        $body = $this->notificationReplace($notification->body, $params);

        return $this->subject($subject)->body($body);
    }

    public function queue(): bool
    {
        return true;
    }

    public function replyTo(Address $address): self
    {
        $this->replyTo[$address->email] = $address->name;

        return $this;
    }

    public function send(): bool
    {
        $ccMails = Tools::settings('email', 'emailcc', '');
        foreach ($this->splitEmails($ccMails) as $email) {
            $this->cc($email);
        }

        $bccMails = Tools::settings('email', 'emailbcc', '');
        foreach ($this->splitEmails($bccMails) as $email) {
            $this->bcc($email);
        }

        foreach (self::$mods as $mod) {
            if ($mod->canSend($this->mailBox)) {
                return $mod->send($this);
            }
        }

        $mail = new PHPMailer();
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->Mailer = Tools::settings('email', 'mailer');

        $mail->SMTPSecure = Tools::settings('email', 'enc', '');
        if ($mail->SMTPSecure) {
            $mail->SMTPAuth = true;
            $mail->AuthType = Tools::settings('email', 'authtype', '');
        }

        $mail->Host = Tools::settings('email', 'host');
        $mail->Port = Tools::settings('email', 'port');
        $mail->Username = Tools::settings('email', 'user') ?
            Tools::settings('email', 'user') :
            Tools::settings('email', 'email');
        $mail->Password = Tools::settings('email', 'password');

        $lowSecure = (bool)Tools::settings('email', 'lowsecure');
        $options = $lowSecure ? [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ] : [];


        return true;
    }

    public function subject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function test(): bool
    {
        foreach (self::$mods as $mod) {
            if ($mod->canSend($this->mailBox)) {
                return $mod->test($this);
            }
        }

        return true;
    }

    public function to(Address $address): self
    {
        $this->to[$address->email] = $address->name;

        return $this;
    }

    private static function loadMailBoxes(): void
    {
        if (self::$mailBoxes !== null) {
            return;
        }

        self::$mailBoxes = [
            'default' => Tools::settings('email', 'email')
        ];

        foreach (self::$mods as $mod) {
            $mod->addMailBoxes(self::$mailBoxes);
        }
    }

    private function notificationReplace(string $text, array $params): string
    {
        foreach ($params as $key => $value) {
            $text = str_replace('{' . $key . '}', $value, $text);
        }

        return $text;
    }

    public function splitEmails(string $emails): array
    {
        $return = [];
        foreach (explode(',', $emails) as $part) {
            $email = trim($part);
            if (!empty($part)) {
                $return[] = $email;
            }
        }

        return $return;
    }
}

class Archivo
{
    public $path;
    public $name;

    public function __construct(string $path, string $name = '')
    {
        $this->path = $this->validarPath($path);
        $this->name = $this->validarNombre($name);
    }
}

class Address
{
    public $email;
    public $name;

    public function __construct(string $email, string $name = '')
    {
        $this->email = $this->validarEmail($email);
        $this->name = $this->validarNombre($name);
    }
}