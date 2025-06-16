<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Traits;

trait ApiTrait
{
    private string $host = '127.0.0.2';
    private string $port = '8000';
    private string $document_root;
    private string $router;

    private string $url;
    private string $token = "prueba";
    private string $pid;
    private string $command;

    protected function startAPIServer(): void
    {
        $this->document_root = __DIR__ . '/../../';
        $this->router = __DIR__ . '/../../index.php';

        $this->url = "http://{$this->host}:{$this->port}/api/3/";
        $this->command = "php -S {$this->host}:{$this->port} -t {$this->document_root} {$this->router} > /dev/null 2>&1 & echo $!";
        $this->pid = shell_exec($this->command);
        sleep(1);
    }

    protected function stopAPIServer(): void
    {
        shell_exec("kill $this->pid");
    }

    protected function setApiUrl(string $url): void
    {
        $this->url = $url;
    }

    protected function setApiToken(string $token): void
    {
        $this->token = $token;
    }

    protected function makeGETCurl(string $params = ''): array
    {
        $ch = curl_init($this->url . $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Token: " . $this->token
        ]);
        $respuesta = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($respuesta, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $data;
        } else {
            throw new \Exception('Error al decodificar la respuesta JSON: ' . json_last_error_msg());
        }
    }

    protected function makePOSTCurl(string $params = '', array $data = []): array
    {
        $ch = curl_init($this->url . $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); // <-- Aquí el cambio
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Token: " . $this->token,
            "Content-Type: application/x-www-form-urlencoded" // <-- Aquí el cambio
        ]);

        $respuesta = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($respuesta, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $data;
        } else {
            echo $respuesta;
            throw new \Exception('Error al decodificar la respuesta JSON: ' . json_last_error_msg());
        }
    }

    protected function makePUTCurl(string $params = '', array $data = []): array
    {
        $ch = curl_init($this->url . $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); // <-- Cambiado aquí
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Token: " . $this->token,
            "Content-Type: application/x-www-form-urlencoded" // <-- Cambiado aquí
        ]);

        $respuesta = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($respuesta, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $data;
        } else {
            throw new \Exception('Error al decodificar la respuesta JSON: ' . json_last_error_msg());
        }
    }

    protected function makeDELETECurl(string $params = '', array $data = []): array
    {
        $ch = curl_init($this->url . $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); // <-- Cambiado aquí
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Token: " . $this->token,
            "Content-Type: application/x-www-form-urlencoded" // <-- Cambiado aquí
        ]);

        $respuesta = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($respuesta, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $data;
        } else {
            throw new \Exception('Error al decodificar la respuesta JSON: ' . json_last_error_msg());
        }
    }


    protected function getResourcesList(): array
    {
        return [
            "agenciatransportes",
            "agentes",
            "albaranclientes",
            "albaranproveedores",
            "almacenes",
            "apiaccess",
            "apikeyes",
            "asientos",
            "atributos",
            "atributovalores",
            "attachedfilerelations",
            "attachedfiles",
            "ciudades",
            "clientes",
            "codemodeles",
            "codigopostales",
            "conceptopartidas",
            "contactos",
            "crearFacturaCliente",
            "crearFacturaRectificativaCliente",
            "cronjobes",
            "cuentabancoclientes",
            "cuentabancoproveedores",
            "cuentabancos",
            "cuentaespeciales",
            "cuentas",
            "diarios",
            "divisas",
            "doctransformations",
            "ejercicios",
            "emailnotifications",
            "emailsentes",
            "empresas",
            "estadodocumentos",
            "exportarFacturaCliente",
            "fabricantes",
            "facturaclientes",
            "facturaproveedores",
            "familias",
            "formapagos",
            "formatodocumentos",
            "grupoclientes",
            "identificadorfiscales",
            "impuestos",
            "impuestozonas",
            "lineaalbaranclientes",
            "lineaalbaranproveedores",
            "lineafacturaclientes",
            "lineafacturaproveedores",
            "lineapedidoclientes",
            "lineapedidoproveedores",
            "lineapresupuestoclientes",
            "lineapresupuestoproveedores",
            "logmessages",
            "pagefilteres",
            "pageoptions",
            "pages",
            "pagoclientes",
            "pagoproveedores",
            "pais",
            "partidas",
            "pedidoclientes",
            "pedidoproveedores",
            "presupuestoclientes",
            "presupuestoproveedores",
            "productoimagenes",
            "productoproveedores",
            "productos",
            "proveedores",
            "provincias",
            "puntointeresciudades",
            "reciboclientes",
            "reciboproveedores",
            "regularizacionimpuestos",
            "retenciones",
            "roleaccess",
            "roles",
            "roleusers",
            "secuenciadocumentos",
            "series",
            "settings",
            "stocks",
            "subcuentas",
            "tarifas",
            "totalmodeles",
            "uploadFiles",
            "users",
            "variantes",
            "workeventes"
        ];
    }
}