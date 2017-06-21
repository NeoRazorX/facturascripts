<?php

/*
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2016  Carlos Garcia Gomez  neorazorx@gmail.com
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
 * Un país, por ejemplo España.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class pais extends \FacturaScripts\Core\Base\Model {

    /**
     * Clave primaria. Varchar(3).
     * @var string Código alfa-3 del país.
     * http://es.wikipedia.org/wiki/ISO_3166-1
     */
    public $codpais;

    /**
     * Código alfa-2 del país.
     * http://es.wikipedia.org/wiki/ISO_3166-1
     * @var string 
     */
    public $codiso;

    /**
     * Nombre del pais.
     * @var string 
     */
    public $nombre;
    
    /**
     * Constructor por defecto
     * @param array $p Array con los valores para crear un nuevo país
     */
    public function __construct($p = FALSE) {
        parent::__construct('paises');
        if ($p) {
            $this->codpais = $p['codpais'];

            $this->codiso = $p['codiso'];
            if ($p['codiso'] == '') {
                /// si no se ha rellenado codiso, intentamos usar esta lista
                $codigos = array(
                    'ESP' => 'ES',
                    'ARG' => 'AR',
                    'CHL' => 'CL',
                    'COL' => 'CO',
                    'ECU' => 'EC',
                    'MEX' => 'MX',
                    'PAN' => 'PA',
                    'PER' => 'PE',
                    'VEN' => 'VE',
                );

                if (isset($codigos[$this->codpais])) {
                    $this->codiso = $codigos[$this->codpais];
                }
            }

            $this->nombre = $p['nombre'];
        } else {
            $this->codpais = '';
            $this->codiso = NULL;
            $this->nombre = '';
        }
    }
    
    /**
     * Crea la consulta necesaria para crear los paises en la base de datos.
     * @return string
     */
    public function install() {
        $this->clean_cache();
        return "INSERT INTO " . $this->table_name . " (codpais,codiso,nombre)"
                . " VALUES ('ESP','ES','España'),"
                . " ('AFG','AF','Afganistán'),"
                . " ('ALB','AL','Albania'),"
                . " ('DEU','DE','Alemania'),"
                . " ('AND','AD','Andorra'),"
                . " ('AGO','AO','Angola'),"
                . " ('AIA','AI','Anguila'),"
                . " ('ATA','AQ','Antártida'),"
                . " ('ATG','AG','Antigua y Barbuda'),"
                . " ('ANT','AN','Antillas Holandesas'),"
                . " ('SAU','SA','Arabia Saudí'),"
                . " ('DZA','DZ','Argelia'),"
                . " ('ARG','AR','Argentina'),"
                . " ('ARM','AM','Armenia'),"
                . " ('ABW','AW','Aruba'),"
                . " ('AUS','AU','Australia'),"
                . " ('AUT','AT','Austria'),"
                . " ('AZE','AZ','Azerbaiyán'),"
                . " ('BHS','BS','Bahamas'),"
                . " ('BHR','BH','Bahréin'),"
                . " ('BGD','BD','Bangladesh'),"
                . " ('BRB','BB','Barbados'),"
                . " ('BEL','BE','Bélgica'),"
                . " ('BLZ','BZ','Belice'),"
                . " ('BEN','BJ','Benín'),"
                . " ('BMU','BM','Bermudas'),"
                . " ('BTN','BT','Bhután'),"
                . " ('BLR','BY','Bielorrusia'),"
                . " ('BOL','BO','Bolivia'),"
                . " ('BIH','BA','Bosnia y Herzegovina'),"
                . " ('BWA','BW','Botsuana'),"
                . " ('BRA','BR','Brasil'),"
                . " ('BRN','BN','Brunéi'),"
                . " ('BGR','BG','Bulgaria'),"
                . " ('BFA','BF','Burkina Faso'),"
                . " ('BDI','BI','Burundi'),"
                . " ('CPV','CV','Cabo Verde'),"
                . " ('KHM','KH','Camboya'),"
                . " ('CMR','CM','Camerún'),"
                . " ('CAN','CA','Canadá'),"
                . " ('TCD','TD','Chad'),"
                . " ('CHL','CL','Chile'),"
                . " ('CHN','CN','China'),"
                . " ('CYP','CY','Chipre'),"
                . " ('VAT','VA','Ciudad del Vaticano'),"
                . " ('COL','CO','Colombia'),"
                . " ('COM','KM','Comoras'),"
                . " ('COG','CG','Congo'),"
                . " ('PRK','KP','Corea del Norte'),"
                . " ('KOR','KR','Corea del Sur'),"
                . " ('CIV','CI','Costa de Marfil'),"
                . " ('CRI','CR','Costa Rica'),"
                . " ('HRV','HR','Croacia'),"
                . " ('CUB','CU','Cuba'),"
                . " ('DNK','DK','Dinamarca'),"
                . " ('DMA','DM','Dominica'),"
                . " ('ECU','EC','Ecuador'),"
                . " ('EGY','EG','Egipto'),"
                . " ('SLV','SV','El Salvador'),"
                . " ('ARE','AE','Emiratos Árabes Unidos'),"
                . " ('ERI','ER','Eritrea'),"
                . " ('SVK','SK','Eslovaquia'),"
                . " ('SVN','SI','Eslovenia'),"
                . " ('USA','US','Estados Unidos'),"
                . " ('EST','EE','Estonia'),"
                . " ('ETH','ET','Etiopía'),"
                . " ('PHL','PH','Filipinas'),"
                . " ('FIN','FI','Finlandia'),"
                . " ('FJI','FJ','Fiyi'),"
                . " ('FRA','FR','Francia'),"
                . " ('GAB','GA','Gabón'),"
                . " ('GMB','GM','Gambia'),"
                . " ('GEO','GE','Georgia'),"
                . " ('GHA','GH','Ghana'),"
                . " ('GIB','GI','Gibraltar'),"
                . " ('GRD','GD','Granada'),"
                . " ('GRC','GR','Grecia'),"
                . " ('GRL','GL','Groenlandia'),"
                . " ('GLP','GP','Guadalupe'),"
                . " ('GUM','GU','Guam'),"
                . " ('GTM','GT','Guatemala'),"
                . " ('GUF','GF','Guayana Francesa'),"
                . " ('GIN','GN','Guinea'),"
                . " ('GNQ','GQ','Guinea Ecuatorial'),"
                . " ('GNB','GW','Guinea-Bissau'),"
                . " ('GUY','GY','Guyana'),"
                . " ('HTI','HT','Haití'),"
                . " ('HND','HN','Honduras'),"
                . " ('HKG','HK','Hong Kong'),"
                . " ('HUN','HU','Hungría'),"
                . " ('IND','IN','India'),"
                . " ('IDN','ID','Indonesia'),"
                . " ('IRN','IR','Irán'),"
                . " ('IRQ','IQ','Iraq'),"
                . " ('IRL','IE','Irlanda'),"
                . " ('BVT','BV','Isla Bouvet'),"
                . " ('CXR','CX','Isla de Navidad'),"
                . " ('NFK','NF','Isla Norfolk'),"
                . " ('ISL','IS','Islandia'),"
                . " ('CYM','KY','Islas Caimán'),"
                . " ('CCK','CC','Islas Cocos'),"
                . " ('COK','CK','Islas Cook'),"
                . " ('FRO','FO','Islas Feroe'),"
                . " ('SGS','GS','Islas Georgias del Sur y Sandwich del Sur'),"
                . " ('ALA','AX','Islas Gland'),"
                . " ('HMD','HM','Islas Heard y McDonald'),"
                . " ('FLK','FK','Islas Malvinas'),"
                . " ('MNP','MP','Islas Marianas del Norte'),"
                . " ('MHL','MH','Islas Marshall'),"
                . " ('PCN','PN','Islas Pitcairn'),"
                . " ('SLB','SB','Islas Salomón'),"
                . " ('TCA','TC','Islas Turcas y Caicos'),"
                . " ('UMI','UM','Islas Ultramarinas de Estados Unidos'),"
                . " ('VGB','VG','Islas Vírgenes Británicas'),"
                . " ('VIR','VI','Islas Vírgenes de los Estados Unidos'),"
                . " ('ISR','IL','Israel'),"
                . " ('ITA','IT','Italia'),"
                . " ('JAM','JM','Jamaica'),"
                . " ('JPN','JP','Japón'),"
                . " ('JOR','JO','Jordania'),"
                . " ('KAZ','KZ','Kazajstán'),"
                . " ('KEN','KE','Kenia'),"
                . " ('KGZ','KG','Kirguistán'),"
                . " ('KIR','KI','Kiribati'),"
                . " ('KWT','KW','Kuwait'),"
                . " ('LAO','LA','Laos'),"
                . " ('LSO','LS','Lesotho'),"
                . " ('LVA','LV','Letonia'),"
                . " ('LBN','LB','Líbano'),"
                . " ('LBR','LR','Liberia'),"
                . " ('LBY','LY','Libia'),"
                . " ('LIE','LI','Liechtenstein'),"
                . " ('LTU','LT','Lituania'),"
                . " ('LUX','LU','Luxemburgo'),"
                . " ('MAC','MO','Macao'),"
                . " ('MKD','MK','Macedonia'),"
                . " ('MDG','MG','Madagascar'),"
                . " ('MYS','MY','Malasia'),"
                . " ('MWI','MW','Malaui'),"
                . " ('MDV','MV','Maldivas'),"
                . " ('MLI','ML','Malí'),"
                . " ('MLT','MT','Malta'),"
                . " ('MAR','MA','Marruecos'),"
                . " ('MTQ','MQ','Martinica'),"
                . " ('MUS','MU','Mauricio'),"
                . " ('MRT','MR','Mauritania'),"
                . " ('MYT','YT','Mayotte'),"
                . " ('MEX','MX','México'),"
                . " ('FSM','FM','Micronesia'),"
                . " ('MDA','MD','Moldavia'),"
                . " ('MCO','MC','Mónaco'),"
                . " ('MNG','MN','Mongolia'),"
                . " ('MNE','ME','Montenegro'),"
                . " ('MSR','MS','Montserrat'),"
                . " ('MOZ','MZ','Mozambique'),"
                . " ('MMR','MM','Myanmar'),"
                . " ('NAM','NA','Namibia'),"
                . " ('NRU','NR','Nauru'),"
                . " ('NPL','NP','Nepal'),"
                . " ('NIC','NI','Nicaragua'),"
                . " ('NER','NE','Níger'),"
                . " ('NGA','NG','Nigeria'),"
                . " ('NIU','NU','Niue'),"
                . " ('NOR','NO','Noruega'),"
                . " ('NCL','NC','Nueva Caledonia'),"
                . " ('NZL','NZ','Nueva Zelanda'),"
                . " ('OMN','OM','Omán'),"
                . " ('NLD','NL','Países Bajos'),"
                . " ('PAK','PK','Pakistán'),"
                . " ('PLW','PW','Palaos'),"
                . " ('PSE','PS','Palestina'),"
                . " ('PAN','PA','Panamá'),"
                . " ('PNG','PG','Papúa Nueva Guinea'),"
                . " ('PRY','PY','Paraguay'),"
                . " ('PER','PE','Perú'),"
                . " ('PYF','PF','Polinesia Francesa'),"
                . " ('POL','PL','Polonia'),"
                . " ('PRT','PT','Portugal'),"
                . " ('PRI','PR','Puerto Rico'),"
                . " ('QAT','QA','Qatar'),"
                . " ('GBR','GB','Reino Unido'),"
                . " ('CAF','CF','República Centroafricana'),"
                . " ('CZE','CZ','República Checa'),"
                . " ('COD','CD','República Democrática del Congo'),"
                . " ('DOM','DO','República Dominicana'),"
                . " ('REU','RE','Reunión'),"
                . " ('RWA','RW','Ruanda'),"
                . " ('ROU','RO','Rumania'),"
                . " ('RUS','RU','Rusia'),"
                . " ('ESH','EH','Sahara Occidental'),"
                . " ('WSM','WS','Samoa'),"
                . " ('ASM','AS','Samoa Americana'),"
                . " ('KNA','KN','San Cristóbal y Nieves'),"
                . " ('SMR','SM','San Marino'),"
                . " ('SPM','PM','San Pedro y Miquelón'),"
                . " ('VCT','VC','San Vicente y las Granadinas'),"
                . " ('SHN','SH','Santa Helena'),"
                . " ('LCA','LC','Santa Lucía'),"
                . " ('STP','ST','Santo Tomé y Príncipe'),"
                . " ('SEN','SN','Senegal'),"
                . " ('SRB','RS','Serbia'),"
                . " ('SYC','SC','Seychelles'),"
                . " ('SLE','SL','Sierra Leona'),"
                . " ('SGP','SG','Singapur'),"
                . " ('SYR','SY','Siria'),"
                . " ('SOM','SO','Somalia'),"
                . " ('LKA','LK','Sri Lanka'),"
                . " ('SWZ','SZ','Suazilandia'),"
                . " ('ZAF','ZA','Sudáfrica'),"
                . " ('SDN','SD','Sudán'),"
                . " ('SWE','SE','Suecia'),"
                . " ('CHE','CH','Suiza'),"
                . " ('SUR','SR','Surinam'),"
                . " ('SJM','SJ','Svalbard y Jan Mayen'),"
                . " ('THA','TH','Tailandia'),"
                . " ('TWN','TW','Taiwán'),"
                . " ('TZA','TZ','Tanzania'),"
                . " ('TJK','TJ','Tayikistán'),"
                . " ('IOT','IO','Territorio Británico del Océano Índico'),"
                . " ('ATF','TF','Territorios Australes Franceses'),"
                . " ('TLS','TL','Timor Oriental'),"
                . " ('TGO','TG','Togo'),"
                . " ('TKL','TK','Tokelau'),"
                . " ('TON','TO','Tonga'),"
                . " ('TTO','TT','Trinidad y Tobago'),"
                . " ('TUN','TN','Túnez'),"
                . " ('TKM','TM','Turkmenistán'),"
                . " ('TUR','TR','Turquía'),"
                . " ('TUV','TV','Tuvalu'),"
                . " ('UKR','UA','Ucrania'),"
                . " ('UGA','UG','Uganda'),"
                . " ('URY','UY','Uruguay'),"
                . " ('UZB','UZ','Uzbekistán'),"
                . " ('VUT','VU','Vanuatu'),"
                . " ('VEN','VE','Venezuela'),"
                . " ('VNM','VN','Vietnam'),"
                . " ('WLF','WF','Wallis y Futuna'),"
                . " ('YEM','YE','Yemen'),"
                . " ('DJI','DJ','Yibuti'),"
                . " ('ZMB','ZM','Zambia'),"
                . " ('ZWE','ZW','Zimbabue');";
    }

    /**
     * Devuelve la URL donde ver/modificar los datos
     * @return string
     */
    public function url() {
        if (is_null($this->codpais)) {
            return 'index.php?page=admin_paises';
        } else
            return 'index.php?page=admin_paises#' . $this->codpais;
    }

    /**
     * Devuelve TRUE si el pais es el predeterminado de la empresa
     * @return boolean
     */
    public function is_default() {
        return ( $this->codpais == $this->default_items->codpais() );
    }

    /**
     * Devuelve el pais con codpais = $cod
     * @param string $cod
     * @return boolean|\FacturaScripts\model\pais
     */
    public function get($cod) {
        $pais = $this->dataBase->select("SELECT * FROM " . $this->table_name . " WHERE codpais = " . $this->var2str($cod) . ";");
        if ($pais) {
            return new \pais($pais[0]);
        } else
            return FALSE;
    }

    /**
     * Devuelve el pais con codido = $cod
     * @param string $cod
     * @return \pais|boolean
     */
    public function get_by_iso($cod) {
        $pais = $this->dataBase->select("SELECT * FROM " . $this->table_name . " WHERE codiso = " . $this->var2str($cod) . ";");
        if ($pais) {
            return new \pais($pais[0]);
        } else
            return FALSE;
    }

    /**
     * Devuelve TRUE si el pais existe
     * @return boolean
     */
    public function exists() {
        if (is_null($this->codpais)) {
            return FALSE;
        } else
            return $this->dataBase->select("SELECT * FROM " . $this->table_name . " WHERE codpais = " . $this->var2str($this->codpais) . ";");
    }

    /**
     * Comprueba los datos del pais, devuelve TRUE si son correctos
     * @return boolean
     */
    public function test() {
        $status = FALSE;

        $this->codpais = trim($this->codpais);
        $this->nombre = $this->no_html($this->nombre);

        if (!preg_match("/^[A-Z0-9]{1,20}$/i", $this->codpais)) {
            $this->miniLog->alert("Código del país no válido: " . $this->codpais);
        } else if (strlen($this->nombre) < 1 || strlen($this->nombre) > 100) {
            $this->miniLog->alert("Nombre del país no válido.");
        } else
            $status = TRUE;

        return $status;
    }

    /**
     * Guarda los datos en la base de datos
     * @return boolean
     */
    public function save() {
        if ($this->test()) {
            $this->clean_cache();

            if ($this->exists()) {
                $sql = "UPDATE " . $this->table_name . " SET codiso = " . $this->var2str($this->codiso) .
                        ", nombre = " . $this->var2str($this->nombre) .
                        "  WHERE codpais = " . $this->var2str($this->codpais) . ";";
            } else {
                $sql = "INSERT INTO " . $this->table_name . " (codpais,codiso,nombre) VALUES
                     (" . $this->var2str($this->codpais) .
                        "," . $this->var2str($this->codiso) .
                        "," . $this->var2str($this->nombre) . ");";
            }

            return $this->dataBase->exec($sql);
        } else
            return FALSE;
    }

    /**
     * Elimina el pais (de la base de datos ... por ahora)
     * @return type
     */
    public function delete() {
        $this->clean_cache();
        return $this->dataBase->exec("DELETE FROM " . $this->table_name . " WHERE codpais = " . $this->var2str($this->codpais) . ";");
    }

    /**
     * Limpia la caché
     */
    private function clean_cache() {
        $this->cache->delete('m_pais_all');
    }

    /**
     * Devuelve un array con todos los paises
     * @return \pais
     */
    public function all() {
        /// Leemos la lista de la caché
        $listap = $this->cache->get_array('m_pais_all');
        if (!$listap) {
            /// si no encontramos los datos en caché, leemos de la base de datos
            $data = $this->dataBase->select("SELECT * FROM " . $this->table_name . " ORDER BY nombre ASC;");
            if ($data) {
                foreach ($data as $p) {
                    $listap[] = new \pais($p);
                }
            }

            /// guardamos la lista en caché
            $this->cache->set('m_pais_all', $listap);
        }

        return $listap;
    }

}
