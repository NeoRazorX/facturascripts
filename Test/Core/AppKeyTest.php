<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core;

use FacturaScripts\Core\AppKey;
use FacturaScripts\Core\CrashReport;
use FacturaScripts\Core\Tools;
use PHPUnit\Framework\TestCase;

final class AppKeyTest extends TestCase
{
    public function testCanEncrypt(): void
    {
        // sin la extensión openssl o sin FS_APP_KEY no se cifra: la clave derivada cambia con los datos de conexión
        $expected = extension_loaded('openssl') && false === AppKey::isDerived();
        $this->assertEquals($expected, AppKey::canEncrypt(), 'bad-can-encrypt');
        if (false === $expected) {
            $this->assertNull(AppKey::encrypt('test', 'secret'), 'encrypted-without-app-key');
            $this->assertNull(AppKey::decrypt('test', 'v1:' . base64_encode(str_repeat('a', 40))), 'decrypted-without-app-key');
        }
    }

    public function testCrashReportToken(): void
    {
        $token = CrashReport::newToken();
        $this->assertTrue(CrashReport::validateToken($token), 'crash-report-token-not-valid');
        $this->assertFalse(CrashReport::validateToken(''), 'empty-crash-report-token-valid');
        $this->assertFalse(CrashReport::validateToken(strrev($token)), 'bad-crash-report-token-valid');

        // el token antiguo solo dependía del nombre y el usuario de la base de datos
        $oldToken = md5(Tools::config('db_name') . Tools::config('db_user') . date('Y-m-d H'));
        $this->assertFalse(CrashReport::validateToken($oldToken), 'old-crash-report-token-valid');

        // el token de otro tipo no sirve como token de CrashReport
        $otherToken = AppKey::sign('other', date('Y-m-d H'));
        $this->assertFalse(CrashReport::validateToken($otherToken), 'other-purpose-token-valid');
    }

    public function testEncryptAndDecrypt(): void
    {
        self::defineAppKey();

        // ida y vuelta, también con un secreto vacío o con caracteres especiales
        foreach (['secret', '', 'ñ€ "<>&\' con espacios '] as $plain) {
            $value = AppKey::encrypt('test', $plain, '1|abc');
            $this->assertNotNull($value, 'not-encrypted');
            $this->assertStringStartsWith(AppKey::CIPHER_PREFIX, $value, 'bad-encrypted-prefix');
            if ($plain !== '') {
                $this->assertStringNotContainsString($plain, $value, 'plain-in-encrypted-value');
            }
            $this->assertSame($plain, AppKey::decrypt('test', $value, '1|abc'), 'not-decrypted');
        }

        // el vector de inicialización es aleatorio: cifrar dos veces lo mismo da valores distintos
        $value = AppKey::encrypt('test', 'secret', '1|abc');
        $this->assertNotEquals($value, AppKey::encrypt('test', 'secret', '1|abc'), 'repeated-encrypted-value');

        // con otro propósito u otro contexto no se descifra
        $this->assertNull(AppKey::decrypt('other', $value, '1|abc'), 'decrypted-with-other-purpose');
        $this->assertNull(AppKey::decrypt('test', $value, '2|abc'), 'decrypted-with-other-context');
        $this->assertNull(AppKey::decrypt('test', $value), 'decrypted-without-context');

        // los valores modificados, sin prefijo, cortos o vacíos no se descifran
        $data = base64_decode(substr($value, strlen(AppKey::CIPHER_PREFIX)));
        $data[strlen($data) - 1] = chr(ord($data[strlen($data) - 1]) ^ 1);
        $this->assertNull(AppKey::decrypt('test', AppKey::CIPHER_PREFIX . base64_encode($data), '1|abc'), 'modified-value-decrypted');
        $this->assertNull(AppKey::decrypt('test', substr($value, strlen(AppKey::CIPHER_PREFIX)), '1|abc'), 'value-without-prefix-decrypted');
        $this->assertNull(AppKey::decrypt('test', AppKey::CIPHER_PREFIX . 'short', '1|abc'), 'short-value-decrypted');
        $this->assertNull(AppKey::decrypt('test', '', '1|abc'), 'empty-value-decrypted');
        $this->assertNull(AppKey::decrypt('test', null, '1|abc'), 'null-value-decrypted');
    }

    public function testEncryptFormat(): void
    {
        self::defineAppKey();

        // el formato no puede cambiar sin cambiar el prefijo: lo guardado tiene que seguir descifrándose
        $iv = random_bytes(AppKey::CIPHER_IV_BYTES);
        $tag = '';
        $key = hash_hkdf('sha256', AppKey::get(), 32, 'test');
        $cipher = openssl_encrypt('secret', 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '1|abc', 16);
        $value = 'v1:' . base64_encode($iv . $tag . $cipher);
        $this->assertSame('secret', AppKey::decrypt('test', $value, '1|abc'), 'format-changed');
    }

    public function testGenerate(): void
    {
        $key1 = AppKey::generate();
        $key2 = AppKey::generate();
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $key1, 'bad-key-format');
        $this->assertNotEquals($key1, $key2, 'repeated-key');
    }

    public function testGet(): void
    {
        $key = AppKey::get();
        $this->assertGreaterThanOrEqual(AppKey::KEY_BYTES, strlen($key), 'key-too-short');
        $this->assertEquals($key, AppKey::get(), 'key-not-stable');

        if (AppKey::isDerived()) {
            $this->assertNotEquals(Tools::config('db_pass'), $key, 'derived-key-is-db-pass');
        } else {
            $this->assertEquals(Tools::config('app_key'), $key, 'key-not-from-config');
        }
    }

    public function testSaveToConfig(): void
    {
        $file = Tools::folder('MyFiles', 'Tmp', 'config-' . mt_rand() . '.php');
        Tools::folderCheckOrCreate(dirname($file));
        $original = "<?php\ndefine('FS_TEST_CONST', 'test');\n?>\n";
        file_put_contents($file, $original);

        // añade la clave tras la etiqueta de apertura y conserva el resto
        $key = AppKey::saveToConfig($file);
        $this->assertNotNull($key, 'key-not-saved');
        $this->assertGreaterThanOrEqual(AppKey::KEY_BYTES, strlen($key), 'saved-key-too-short');
        $expected = "<?php\ndefine('FS_APP_KEY', '" . $key . "');\ndefine('FS_TEST_CONST', 'test');\n?>\n";
        $this->assertEquals($expected, file_get_contents($file), 'bad-config-content');
        $this->assertEmpty(glob($file . '.*.tmp'), 'tmp-file-not-removed');

        // si ya menciona la clave, no lo modifica
        $this->assertNull(AppKey::saveToConfig($file), 'key-saved-twice');
        $this->assertEquals($expected, file_get_contents($file), 'config-modified-twice');

        // ni si no empieza por la etiqueta de apertura
        file_put_contents($file, "<html><?php define('FS_TEST_CONST', 'test');");
        $this->assertNull(AppKey::saveToConfig($file), 'key-saved-in-bad-config');
        unlink($file);

        // ni si no existe
        $this->assertNull(AppKey::saveToConfig($file), 'key-saved-in-missing-config');
        $this->assertFileDoesNotExist($file, 'missing-config-created');

        // ni si es un enlace simbólico, porque lo sustituiría por un archivo normal
        $target = $file . '.target';
        file_put_contents($target, $original);
        if (@symlink($target, $file)) {
            $this->assertNull(AppKey::saveToConfig($file), 'key-saved-in-symlink');
            $this->assertTrue(is_link($file), 'symlink-replaced');
            $this->assertEquals($original, file_get_contents($target), 'symlink-target-modified');
            unlink($file);
        }
        unlink($target);
    }

    public function testSignAndVerify(): void
    {
        $signature = AppKey::sign('test', 'data');
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $signature, 'bad-signature-format');
        $this->assertEquals($signature, AppKey::sign('test', 'data'), 'signature-not-stable');
        $this->assertTrue(AppKey::verify('test', 'data', $signature), 'signature-not-valid');

        // cambiar los datos, el propósito o la firma invalida la comprobación
        $this->assertFalse(AppKey::verify('test', 'other-data', $signature), 'signature-valid-with-other-data');
        $this->assertFalse(AppKey::verify('other', 'data', $signature), 'signature-valid-with-other-purpose');
        $this->assertFalse(AppKey::verify('test', 'data', ''), 'empty-signature-valid');
        $this->assertFalse(AppKey::verify('test', 'data', substr($signature, 0, -1)), 'short-signature-valid');
    }

    /**
     * El config.php de los tests no trae FS_APP_KEY y sin ella no se cifra.
     */
    private static function defineAppKey(): void
    {
        if (false === defined('FS_APP_KEY')) {
            define('FS_APP_KEY', AppKey::generate());
        }
    }
}
