<?php

/*
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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

namespace FacturaScripts\Core\Model;

/**
 * Esta clase almacena los principales datos de la empresa.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class Empresa extends \FacturaScripts\Core\Base\Model {

    /**
     * Clave primaria. Integer.
     * @var integer 
     */
    public $id;
    public $xid;

    /**
     * Todavía sin uso.
     * @var boolean
     */
    public $stockpedidos;

    /**
     * TRUE -> activa la contabilidad integrada. Se genera el asiento correspondiente
     * cada vez que se crea/modifica una factura.
     * @var boolean
     */
    public $contintegrada;

    /**
     * TRUE -> activa el uso de recargo de equivalencia en los albaranes y facturas de compra.
     * @var boolean
     */
    public $recequivalencia;

    /**
     * Código de la serie por defecto.
     * @var string
     */
    public $codserie;

    /**
     * Código del almacén predeterminado.
     * @var string
     */
    public $codalmacen;

    /**
     * Código de la forma de pago predeterminada.
     * @var string
     */
    public $codpago;

    /**
     * Código de la divisa predeterminada.
     * @var string
     */
    public $coddivisa;

    /**
     * Código del ejercicio predeterminado.
     * @var string
     */
    public $codejercicio;

    /**
     * URL de la web de la empresa.
     * @var string 
     */
    public $web;

    /**
     * Dirección de email de la empresa.
     * @var string 
     */
    public $email;

    /**
     * Número de fax de la empresa.
     * @var string 
     */
    public $fax;

    /**
     * Número de teléfono de la empresa.
     * @var string 
     */
    public $telefono;

    /**
     * Código del país predeterminado.
     * @var string
     */
    public $codpais;

    /**
     * Apartado de correos de la empresa.
     * @var string
     */
    public $apartado;

    /**
     * Provincia de la empresa.
     * @var string
     */
    public $provincia;

    /**
     * Ciudad de la empresa.
     * @var string
     */
    public $ciudad;

    /**
     * Código postal de la empresa.
     * @var string
     */
    public $codpostal;

    /**
     * Dirección de la empresa.
     * @var string
     */
    public $direccion;

    /**
     * Nombre del administrador de la empresa.
     * @var string
     */
    public $administrador;

    /**
     * Actualmente sin uso.
     * @var string
     */
    public $codedi;

    /**
     * Código de identificación fiscal dela empresa.
     * @var string
     */
    public $cifnif;

    /**
     * Nombre de la empresa.
     * @var string
     */
    public $nombre;

    /**
     * Nombre corto de la empresa, para mostrar en el menú
     * @var string Nombre a mostrar en el menú de facturaScripts.
     */
    public $nombrecorto;

    /**
     * Lema de la empresa
     * @var string
     */
    public $lema;

    /**
     * Horario de apertura
     * @var string
     */
    public $horario;

    /**
     * Texto al pié de las facturas de venta.
     * @var string
     */
    public $pie_factura;

    /**
     * Fecha de inicio de la actividad.
     * @var string
     */
    public $inicio_actividad;

    /**
     * Régimen de IVA de la empresa.
     * @var string
     */
    public $regimeniva;

    /**
     * Configuración de email de la empresa.
     * @var array de string
      ] */
    public $email_config;

    /**
     * Contructor por defecto
     */
    public function __construct() {
        parent::__construct('empresa', 'id');
        $data = $this->dataBase->select("SELECT * FROM " . $this->tableName . ";");
        if ($data) {
            $this->id = (int) $data[0]['id'];
            $this->xid = $data[0]['xid'];
            $this->stockpedidos = $this->str2bool($data[0]['stockpedidos']);
            $this->contintegrada = $this->str2bool($data[0]['contintegrada']);
            $this->recequivalencia = $this->str2bool($data[0]['recequivalencia']);
            $this->codserie = $data[0]['codserie'];
            $this->codalmacen = $data[0]['codalmacen'];
            $this->codpago = $data[0]['codpago'];
            $this->coddivisa = $data[0]['coddivisa'];
            $this->codejercicio = $data[0]['codejercicio'];
            $this->web = $data[0]['web'];
            $this->email = $data[0]['email'];
            $this->fax = $data[0]['fax'];
            $this->telefono = $data[0]['telefono'];
            $this->codpais = $data[0]['codpais'];
            $this->apartado = $data[0]['apartado'];
            $this->provincia = $data[0]['provincia'];
            $this->ciudad = $data[0]['ciudad'];
            $this->codpostal = $data[0]['codpostal'];
            $this->direccion = $data[0]['direccion'];
            $this->administrador = $data[0]['administrador'];
            $this->codedi = $data[0]['codedi'];
            $this->cifnif = $data[0]['cifnif'];
            $this->nombre = $data[0]['nombre'];
            $this->nombrecorto = $data[0]['nombrecorto'];
            $this->lema = $data[0]['lema'];
            $this->horario = $data[0]['horario'];
            $this->pie_factura = $data[0]['pie_factura'];
            $this->inicio_actividad = date('d-m-Y', strtotime($data[0]['inicioact']));
            $this->regimeniva = $data[0]['regimeniva'];

            /// cargamos las opciones de email por defecto
            $this->email_config = array(
                'mail_password' => '',
                'mail_bcc' => '',
                'mail_firma' => "\n---\nEnviado con FacturaScripts",
                'mail_mailer' => 'smtp',
                'mail_host' => 'smtp.gmail.com',
                'mail_port' => '465',
                'mail_enc' => 'ssl',
                'mail_user' => '',
                'mail_low_security' => FALSE,
            );

            if (is_null($this->xid)) {
                $this->xid = $this->randomString(30);
                $this->save();
            }
        }
    }

    public function clear() {
        
    }

    /**
     * Crea la consulta necesaria para dotar de datos a la empresa en la base de datos.
     * @return string
     */
    protected function install() {
        $num = mt_rand(1, 9999);
        return "INSERT INTO " . $this->tableName . " (stockpedidos,contintegrada,recequivalencia,codserie,"
                . "codalmacen,codpago,coddivisa,codejercicio,web,email,fax,telefono,codpais,apartado,provincia,"
                . "ciudad,codpostal,direccion,administrador,codedi,cifnif,nombre,nombrecorto,lema,horario)"
                . "VALUES (NULL,FALSE,NULL,'A','ALG','CONT','EUR','0001','https://www.facturascripts.com',"
                . "NULL,NULL,NULL,'ESP',NULL,NULL,NULL,NULL,'C/ Falsa, 123','',NULL,'00000014Z','Empresa " . $num . " S.L.',"
                . "'E-" . $num . "','','');";
    }

    /**
     * Devuelve la url donde ver/modificar los datos
     * @return string
     */
    public function url() {
        return 'index.php?page=admin_empresa';
    }

    /**
     * Comprueba los datos de la empresa, devuelve TRUE si está todo correcto
     * @return boolean
     */
    public function test() {
        $status = FALSE;

        $this->nombre = $this->noHtml($this->nombre);
        $this->nombrecorto = $this->noHtml($this->nombrecorto);
        $this->administrador = $this->noHtml($this->administrador);
        $this->apartado = $this->noHtml($this->apartado);
        $this->cifnif = $this->noHtml($this->cifnif);
        $this->ciudad = $this->noHtml($this->ciudad);
        $this->codpostal = $this->noHtml($this->codpostal);
        $this->direccion = $this->noHtml($this->direccion);
        $this->email = $this->noHtml($this->email);
        $this->fax = $this->noHtml($this->fax);
        $this->horario = $this->noHtml($this->horario);
        $this->lema = $this->noHtml($this->lema);
        $this->pie_factura = $this->noHtml($this->pie_factura);
        $this->provincia = $this->noHtml($this->provincia);
        $this->telefono = $this->noHtml($this->telefono);
        $this->web = $this->noHtml($this->web);

        if (strlen($this->nombre) < 1 || strlen($this->nombre) > 100) {
            $this->miniLog->alert($this->i18n->trans('company-name-invalid'));
        } else if (strlen($this->nombre) < strlen($this->nombrecorto)) {
            $this->miniLog->alert($this->i18n->trans('company-short-name-smaller-name'));
        } else {
            $status = TRUE;
        }

        return $status;
    }

    /**
     * Guarda los datos en la base de datos
     * @return boolean
     */
    public function save() {
        if ($this->test()) {
            if ($this->exists()) {
                $sql = "UPDATE " . $this->tableName . " SET nombre = " . $this->var2str($this->nombre)
                        . ", nombrecorto = " . $this->var2str($this->nombrecorto)
                        . ", cifnif = " . $this->var2str($this->cifnif)
                        . ", codedi = " . $this->var2str($this->codedi)
                        . ", administrador = " . $this->var2str($this->administrador)
                        . ", direccion = " . $this->var2str($this->direccion)
                        . ", codpostal = " . $this->var2str($this->codpostal)
                        . ", ciudad = " . $this->var2str($this->ciudad)
                        . ", provincia = " . $this->var2str($this->provincia)
                        . ", apartado = " . $this->var2str($this->apartado)
                        . ", codpais = " . $this->var2str($this->codpais)
                        . ", telefono = " . $this->var2str($this->telefono)
                        . ", fax = " . $this->var2str($this->fax)
                        . ", email = " . $this->var2str($this->email)
                        . ", web = " . $this->var2str($this->web)
                        . ", codejercicio = " . $this->var2str($this->codejercicio)
                        . ", coddivisa = " . $this->var2str($this->coddivisa)
                        . ", codpago = " . $this->var2str($this->codpago)
                        . ", codalmacen = " . $this->var2str($this->codalmacen)
                        . ", codserie = " . $this->var2str($this->codserie)
                        . ", recequivalencia = " . $this->var2str($this->recequivalencia)
                        . ", contintegrada = " . $this->var2str($this->contintegrada)
                        . ", stockpedidos = " . $this->var2str($this->stockpedidos)
                        . ", xid = " . $this->var2str($this->xid)
                        . ", lema = " . $this->var2str($this->lema)
                        . ", horario = " . $this->var2str($this->horario)
                        . ", pie_factura = " . $this->var2str($this->pie_factura)
                        . ", inicioact = " . $this->var2str($this->inicio_actividad)
                        . ", regimeniva = " . $this->var2str($this->regimeniva)
                        . "  WHERE id = " . $this->var2str($this->id) . ";";

                return $this->dataBase->exec($sql);
            }

            $sql = "INSERT INTO " . $this->tableName . " (stockpedidos,contintegrada,recequivalencia,codserie,"
                    . "codalmacen,codpago,coddivisa,codejercicio,web,email,fax,telefono,"
                    . "codpais,apartado,provincia,ciudad,codpostal,direccion,administrador,codedi,cifnif,nombre,"
                    . "nombrecorto,lema,horario,pie_factura,inicioact,regimeniva) VALUES "
                    . "(" . $this->var2str($this->stockpedidos)
                    . "," . $this->var2str($this->contintegrada)
                    . "," . $this->var2str($this->recequivalencia)
                    . "," . $this->var2str($this->codserie)
                    . "," . $this->var2str($this->codalmacen)
                    . "," . $this->var2str($this->codpago)
                    . "," . $this->var2str($this->coddivisa)
                    . "," . $this->var2str($this->codejercicio)
                    . "," . $this->var2str($this->web)
                    . "," . $this->var2str($this->email)
                    . "," . $this->var2str($this->fax)
                    . "," . $this->var2str($this->telefono)
                    . "," . $this->var2str($this->codpais)
                    . "," . $this->var2str($this->apartado)
                    . "," . $this->var2str($this->provincia)
                    . "," . $this->var2str($this->ciudad)
                    . "," . $this->var2str($this->codpostal)
                    . "," . $this->var2str($this->direccion)
                    . "," . $this->var2str($this->administrador)
                    . "," . $this->var2str($this->codedi)
                    . "," . $this->var2str($this->cifnif)
                    . "," . $this->var2str($this->nombre)
                    . "," . $this->var2str($this->nombrecorto)
                    . "," . $this->var2str($this->lema)
                    . "," . $this->var2str($this->horario)
                    . "," . $this->var2str($this->pie_factura)
                    . "," . $this->var2str($this->inicio_actividad)
                    . "," . $this->var2str($this->regimeniva) . ");";
            if ($this->dataBase->exec($sql)) {
                $this->id = $this->dataBase->lastval();
                return TRUE;
            }

            return FALSE;
        }

        return FALSE;
    }

}
