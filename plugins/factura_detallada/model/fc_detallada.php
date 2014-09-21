<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014  Valentín González    valengon@gmail.com
 * Copyright (C) 2014  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class fc_detallada
{
    public $ruta_html = 'view/ventas_factura.html';
    public $ruta_html_plugin = 'plugins/factura_detallada/view/ventas_factura.html';
    public $ruta_html_default = 'plugins/factura_detallada/view/ventas_factura_default.html';

    public function __construct() { }

    public function MadeHtml()
    {
        $error = FALSE;
        if (!file_exists ($this->ruta_html) )
        {
            $error = TRUE;
            $fichero = '';
        }
        else
        {
            $fichero = file_get_contents($this->ruta_html);
        }

        $buscar = '           <a class="btn btn-block btn-default" target="_blank" href="{$fsc->url()}&imprimir=firma">
               <span class="glyphicon glyphicon-print"></span>
               &nbsp; Factura con firma
            </a>';
        $reemplazar = '            <a class="btn btn-block btn-default" target="_blank" href="{$fsc->url()}&imprimir=firma">
               <span class="glyphicon glyphicon-print"></span>
               &nbsp; Factura con firma
            </a>

            <a class="btn btn-block btn-default" target="_blank" href="index.php?page=factura_detallada&id={$fsc->factura->idfactura}">
               <span class="glyphicon glyphicon-print"></span>
               &nbsp; Factura Detallada
            </a>';
        $pos = strpos($fichero, $buscar);
        if ($pos !== false)
        {
            $fichero = str_replace( $buscar, $reemplazar, $fichero );
        } else {
            $error = TRUE;
        }

        $buscar = '<form class="form" role="form" name="enviar_email" action ="{$fsc->url()}" method="post">';
        $reemplazar = '<form class="form" role="form" name="enviar_email" action ="index.php?page=factura_detallada&id={$fsc->factura->idfactura}" method="post">';
        $pos = strpos($fichero, $buscar);
        if ($pos !== false)
        {
            $fichero = str_replace( $buscar, $reemplazar, $fichero );
        } else {
            $error = TRUE;
        }

        $buscar = '<h4 class="modal-title">Enviar factura</h4>';
        $reemplazar = '<h4 class="modal-title">Enviar factura Detallada</h4>';
        $pos = strpos($fichero, $buscar);
        if ($pos !== false)
        {
            $fichero = str_replace( $buscar, $reemplazar, $fichero );
        } else {
            $error = TRUE;
        }

        if ($error == TRUE)
        {
            if (file_exists($this->ruta_html_plugin))
            {
                unlink ($this->ruta_html_plugin);
            }

            $fichero = file_get_contents($this->ruta_html_default);
        }

        // Creamos el archivo ventas_factura.html
        if (!file_exists($this->ruta_html_plugin))
        {
            $fp = fopen($this->ruta_html_plugin,"w");
            fwrite($fp, $fichero);
            fclose($fp);
        }
    }
}
?>