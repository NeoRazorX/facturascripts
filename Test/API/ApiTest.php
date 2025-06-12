<?php

use PHPUnit\Framework\TestCase;

class ApiTest extends TestCase
{

    public function testApiConnection()
    {
        $url = "http://127.0.0.2:8000/api/3/proveedores";
        $token = "prueba";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Token: $token"
        ]);
        $respuesta = curl_exec($ch);
        echo 'Respuesta: ' . $respuesta;
        $this->assertEquals($respuesta, '[{"cifnif": "","email": "","fax": "","fechaalta": "03-06-2025","langcode": "es_ES","nombre": "pepe","observaciones": "","personafisica": true,"telefono1": "","telefono2": "","tipoidfiscal": "NIF","codcliente": null,"codpago": null,"codproveedor": "1","codretencion": null,"codserie": null,"codsubcuenta": "","debaja": false,"fechabaja": null,"razonsocial": "pepe","regimeniva": "General","web": "","codimpuestoportes": "IVA21","idcontacto": 1}]');
    }
}
/*
$url = "http://127.0.0.2:8000/api/3/divisas?filter[descripcion_like]=PESO";
$token = "prueba";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_ setopt($ch, CURLOPT_HTTPHEADER, [
    "Token: $token"
]);

$respuesta = curl_exec($ch;

if (curl_errno($ch)) {
    echo 'Error en la solicitud: ' . curl_error($ch);
} else {
    echo 'Respuesta: ' . $respuesta;
}

curl_close($ch);
*/