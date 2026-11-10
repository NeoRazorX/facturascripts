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

namespace FacturaScripts\Core\Lib;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\DataSrc\EstadosDocumentos;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Lib\BusinessDocumentGenerator;
use FacturaScripts\Dinamic\Model\AlbaranCliente;
use FacturaScripts\Dinamic\Model\EstadoDocumento;
use FacturaScripts\Dinamic\Model\FacturaCliente;

/**
 * Lógica del Megafacturador: agrupa los albaranes de cliente pendientes de
 * facturar y genera una única factura con todos los albaranes de un grupo.
 *
 * Un grupo son los albaranes pendientes de un cliente que se pueden facturar
 * juntos, es decir, que comparten empresa, serie, divisa, almacén y descuentos
 * globales (los mismos criterios que exige el core para agrupar documentos).
 * Normalmente cada cliente tiene un único grupo.
 *
 * @author Esteban Sánchez Martínez <esteban@factura.city>
 */
class AlbaranFacturador
{
    /** @var string[] Columnas que identifican un grupo de albaranes facturables juntos. */
    const GROUP_FIELDS = ['codcliente', 'idempresa', 'codserie', 'coddivisa', 'codalmacen', 'dtopor1', 'dtopor2'];

    /** @var DataBase Conexión a la base de datos. */
    protected $dataBase;

    public function __construct()
    {
        $this->dataBase = new DataBase();
    }

    /**
     * Devuelve los grupos de albaranes de cliente pendientes de facturar, con
     * su número de albaranes y la suma de sus totales. Se calcula con una sola
     * consulta agregada, sin cargar los albaranes, para que la página siga
     * siendo rápida aunque haya cientos de albaranes por cliente.
     *
     * @param array $owner Restricción de propietario: ['nick' => ..., 'codagente' => ...], vacío para no filtrar.
     *
     * @return array Filas con las columnas de GROUP_FIELDS, nombre, albaranes y total.
     */
    public function getPendingGroups(array $owner = []): array
    {
        $groupFields = [];
        foreach (self::GROUP_FIELDS as $field) {
            $groupFields[] = 'a.' . $field;
        }

        $sql = 'SELECT ' . implode(', ', $groupFields) . ', c.nombre,'
            . ' COUNT(a.idalbaran) AS albaranes, SUM(a.total) AS total'
            . ' FROM albaranescli a'
            . ' LEFT JOIN clientes c ON c.codcliente = a.codcliente'
            . ' WHERE a.editable = ' . $this->dataBase->var2str(true)
            . $this->getOwnerSql($owner)
            . ' GROUP BY ' . implode(', ', $groupFields) . ', c.nombre'
            . ' ORDER BY c.nombre ASC, a.codcliente ASC';

        $groups = [];
        foreach ($this->dataBase->select($sql) as $row) {
            $groups[] = [
                'codcliente' => $row['codcliente'],
                'idempresa' => (int)$row['idempresa'],
                'codserie' => $row['codserie'],
                'coddivisa' => $row['coddivisa'],
                'codalmacen' => $row['codalmacen'],
                'dtopor1' => (float)$row['dtopor1'],
                'dtopor2' => (float)$row['dtopor2'],
                'nombre' => $row['nombre'] ?? $row['codcliente'],
                'albaranes' => (int)$row['albaranes'],
                'total' => (float)$row['total'],
            ];
        }

        return $groups;
    }

    /**
     * Devuelve los albaranes pendientes de facturar del grupo indicado,
     * ordenados por fecha y hora.
     *
     * @param array $group Valores de las columnas de GROUP_FIELDS.
     * @param array $owner Restricción de propietario: ['nick' => ..., 'codagente' => ...], vacío para no filtrar.
     *
     * @return AlbaranCliente[]
     */
    public function getPendingDeliveryNotes(array $group, array $owner = []): array
    {
        $where = [Where::eq('editable', true)];
        foreach (self::GROUP_FIELDS as $field) {
            $where[] = Where::eq($field, $group[$field] ?? null);
        }

        $orderBy = ['fecha' => 'ASC', 'hora' => 'ASC', 'idalbaran' => 'ASC'];
        $deliveryNotes = [];
        foreach (AlbaranCliente::all($where, $orderBy, 0, 0) as $albaran) {
            if ($this->isOwner($albaran, $owner)) {
                $deliveryNotes[] = $albaran;
            }
        }

        return $deliveryNotes;
    }

    /**
     * Genera una única factura con todos los albaranes pendientes del grupo
     * indicado y deja esos albaranes facturados. Todo se hace en una
     * transacción: si algo falla, no se modifica nada.
     *
     * @param array $group Valores de las columnas de GROUP_FIELDS.
     * @param string $fecha Fecha de la nueva factura.
     * @param array $owner Restricción de propietario: ['nick' => ..., 'codagente' => ...], vacío para no filtrar.
     *
     * @return FacturaCliente|null La factura creada, o null si no se ha podido crear.
     */
    public function invoice(array $group, string $fecha, array $owner = []): ?FacturaCliente
    {
        if (empty($fecha) || false === strtotime($fecha)) {
            Tools::log()->warning('invalid-date', ['%date%' => $fecha]);
            return null;
        }

        $deliveryNotes = $this->getPendingDeliveryNotes($group, $owner);
        if (empty($deliveryNotes)) {
            Tools::log()->warning('no-pending-delivery-notes');
            return null;
        }

        $status = $this->getInvoiceStatus();
        if (null === $status) {
            Tools::log()->warning('no-invoice-status-for-delivery-notes');
            return null;
        }

        // al cambiar el estado de los albaranes no queremos que cada uno genere
        // su propia factura; como el indicador es estático, lo restauramos
        // siempre al terminar para no afectar al resto de la petición
        $deliveryNotes[0]->setDocumentGeneration(false);
        try {
            return $this->generateInvoice($deliveryNotes, $status, $fecha);
        } finally {
            $deliveryNotes[0]->setDocumentGeneration(true);

            // si algo ha lanzado una excepción, deshacemos la transacción a medias
            if ($this->dataBase->inTransaction()) {
                $this->dataBase->rollback();
            }
        }
    }

    /**
     * Cambia los albaranes al estado de facturado y genera la factura con
     * todas sus líneas, dentro de una transacción.
     *
     * @param AlbaranCliente[] $deliveryNotes
     * @param EstadoDocumento $status Estado de albarán que genera la factura.
     * @param string $fecha Fecha de la nueva factura.
     *
     * @return FacturaCliente|null
     */
    protected function generateInvoice(array $deliveryNotes, EstadoDocumento $status, string $fecha): ?FacturaCliente
    {
        $this->dataBase->beginTransaction();

        $newLines = [];
        $quantities = [];
        foreach ($deliveryNotes as $albaran) {
            // línea informativa con el código y la fecha del albarán, para que
            // en la factura se sepa a qué albarán pertenece cada línea
            $newLines[] = $albaran->getNewLine([
                'cantidad' => 0,
                'descripcion' => Tools::trans('delivery-note') . ' ' . $albaran->codigo . ', ' . $albaran->fecha,
                'mostrar_cantidad' => false,
                'mostrar_precio' => false,
            ]);

            // marcamos el albarán como facturado (sin que genere su propia factura)
            $albaran->idestado = $status->idestado;
            if (false === $albaran->save()) {
                $this->dataBase->rollback();
                Tools::log()->error('record-save-error');
                return null;
            }

            // facturamos todo lo pendiente de cada línea; leemos las líneas después
            // del cambio de estado, porque ese cambio las actualiza (stock)
            foreach ($albaran->getLines() as $line) {
                $quantities[$line->id()] = $line->cantidad - $line->servido;
                $newLines[] = $line;
            }
        }

        $properties = ['fecha' => $fecha];
        if (strtotime($fecha) === strtotime(Tools::date())) {
            $properties['hora'] = Tools::hour();
        }

        $generator = new BusinessDocumentGenerator();
        if (false === $generator->generate(clone $deliveryNotes[0], 'FacturaCliente', $newLines, $quantities, $properties)) {
            $this->dataBase->rollback();
            Tools::log()->error('record-save-error');
            return null;
        }

        $this->dataBase->commit();

        foreach ($generator->getLastDocs() as $doc) {
            if ($doc instanceof FacturaCliente) {
                return $doc;
            }
        }

        return null;
    }

    /**
     * Devuelve el primer estado activo de los albaranes de cliente que genera
     * una factura de cliente, o null si no hay ninguno.
     *
     * @return EstadoDocumento|null
     */
    protected function getInvoiceStatus(): ?EstadoDocumento
    {
        foreach (EstadosDocumentos::byTipoDoc('AlbaranCliente') as $status) {
            if ($status->activo && $status->generadoc === 'FacturaCliente') {
                return $status;
            }
        }

        return null;
    }

    /**
     * Devuelve la condición SQL que limita los albaranes a los del usuario
     * (por nick o por agente) cuando solo puede ver sus propios datos.
     *
     * @param array $owner Restricción de propietario: ['nick' => ..., 'codagente' => ...], vacío para no filtrar.
     *
     * @return string
     */
    protected function getOwnerSql(array $owner): string
    {
        if (empty($owner)) {
            return '';
        }

        $conditions = ['a.nick = ' . $this->dataBase->var2str($owner['nick'] ?? '')];
        if (false === empty($owner['codagente'])) {
            $conditions[] = 'a.codagente = ' . $this->dataBase->var2str($owner['codagente']);
        }

        return ' AND (' . implode(' OR ', $conditions) . ')';
    }

    /**
     * Indica si el albarán pertenece al usuario de la restricción de propietario.
     *
     * @param AlbaranCliente $albaran
     * @param array $owner Restricción de propietario: ['nick' => ..., 'codagente' => ...], vacío para no filtrar.
     *
     * @return bool
     */
    protected function isOwner(AlbaranCliente $albaran, array $owner): bool
    {
        if (empty($owner)) {
            return true;
        }

        return $albaran->nick === ($owner['nick'] ?? '')
            || (false === empty($owner['codagente']) && $albaran->codagente === $owner['codagente']);
    }
}
