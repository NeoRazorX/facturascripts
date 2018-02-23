<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Lib;

use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Base\Translator as i18n;
use FacturaScripts\Core\Model\Settings;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Tools for send emails.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class EmailTools
{

    /**
     * Settings properties for email
     *
     * @var array
     */
    private static $settings;

    /**
     * EmailTools constructor.
     */
    public function __construct()
    {
        if (!isset(self::$settings)) {
            $this->reloadConfig();
        }
    }

    /**
     * Reload all email settings properties.
     */
    public function reloadConfig()
    {
        $settingsModel = new Settings();
        $emailSettings = $settingsModel->get('email');
        if ($emailSettings) {
            self::$settings = $emailSettings->properties;
        }
    }

    /**
     * Create new PHPMailer connection with stored settings.
     *
     * @return PHPMailer
     */
    public function newMail()
    {
        $mail = new PHPMailer();
        $mail->CharSet = 'UTF-8';
        $mail->WordWrap = 50;
        $mail->Mailer = self::$settings['mailer'];
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = self::$settings['enc'];
        $mail->Host = self::$settings['host'];
        $mail->Port = self::$settings['port'];

        $mail->Username = self::$settings['email'];
        if (self::$settings['user']) {
            $mail->Username = self::$settings['user'];
        }

        $mail->Password = self::$settings['password'];

        return $mail;
    }

    /**
     * Send an email, returns True on success, False on failure.
     *
     * @param PHPMailer $mail
     *
     * @return bool
     */
    public function send($mail)
    {
        if ($mail->smtpConnect($this->smtpOptions()) && $mail->send()) {
            return true;
        }

        $i18n = new i18n();
        $miniLog = new MiniLog();
        $miniLog->alert($i18n->trans('email-error', ['%errorInfo%' => $mail->ErrorInfo]));

        return false;
    }

    /**
     * Test the PHPMailer connection. Return the result of the connection.
     *
     * @return bool
     */
    public function test()
    {
        if (self::$settings['mailer'] === 'smtp') {
            $mail = $this->newMail();

            return $mail->smtpConnect($this->smtpOptions());
        }

        return true;
    }

    /**
     * Returns the HTML code for the email.
     *
     * @param string $companyName
     * @param string $title
     * @param string $txt
     * @param string $sign
     *
     * @return mixed
     */
    public function getHtml($companyName, $title, $txt, $sign)
    {
        $html = file_get_contents(FS_FOLDER . '/Dinamic/Assets/Email/BasicTemplate.html.twig');
        $search = [
            '[[titulo]]',
            '[[empresa]]',
            '[[texto]]',
            '[[pie]]',
        ];
        $replace = [
            $title,
            $companyName,
            nl2br($txt),
            $sign,
        ];

        return str_replace($search, $replace, $html);
    }

    /**
     * Returns the SMTP Options.
     *
     * @return array
     */
    private function smtpOptions()
    {
        $SMTPOptions = [];
        if (isset(self::$settings['lowsecure']) && self::$settings['lowsecure']) {
            $SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ];
        }

        return $SMTPOptions;
    }
}
