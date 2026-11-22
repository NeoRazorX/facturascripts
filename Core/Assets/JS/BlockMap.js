/*!
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

/**
 * Mapas de los bloques MapBlock, con Leaflet. Cada mapa lee sus datos del atributo data-block-map.
 */
(function () {
    'use strict';

    var COLORS = ['#0d6efd', '#198754', '#dc3545', '#fd7e14', '#6f42c1', '#20c997'];

    function popup(marker) {
        var content = document.createElement(marker.url ? 'a' : 'span');
        content.textContent = marker.title;
        if (marker.url) {
            content.href = marker.url;
        }
        return content;
    }

    function build(element) {
        var data = JSON.parse(element.dataset.blockMap);
        var map = L.map(element, {preferCanvas: true, scrollWheelZoom: false});
        L.tileLayer(data.tiles, {attribution: data.attribution, maxZoom: 19}).addTo(map);

        // la rueda desplaza la página hasta que el usuario hace clic en el mapa
        map.on('click', function () {
            map.scrollWheelZoom.enable();
        });
        element.addEventListener('mouseleave', function () {
            map.scrollWheelZoom.disable();
        });

        // un grupo de marcadores por cada tipo, con su color
        var groups = {};
        var names = [];
        var bounds = [];
        data.markers.forEach(function (marker) {
            if (!groups[marker.group]) {
                groups[marker.group] = L.layerGroup().addTo(map);
                names.push(marker.group);
            }

            var color = marker.color || COLORS[names.indexOf(marker.group) % COLORS.length];
            var point = L.circleMarker([marker.lat, marker.lng], {
                radius: 6, weight: 2, color: '#fff', fillColor: color, fillOpacity: 0.9
            });
            if (marker.title) {
                point.bindTooltip(marker.title);
                point.bindPopup(popup(marker));
            }
            point.addTo(groups[marker.group]);
            bounds.push([marker.lat, marker.lng]);
        });

        if (names.length > 1) {
            var overlays = {};
            names.forEach(function (name, index) {
                var label = document.createElement('span');
                var dot = document.createElement('i');
                dot.className = 'fa-solid fa-circle fa-xs me-1';
                dot.style.color = COLORS[index % COLORS.length];
                label.appendChild(dot);
                label.appendChild(document.createTextNode(name));
                overlays[label.outerHTML] = groups[name];
            });
            L.control.layers(null, overlays, {collapsed: false}).addTo(map);
        }

        if (bounds.length > 0) {
            // con un solo marcador no hay nada que encuadrar: se usa el zoom del centro, si lo hay
            var single = bounds.length === 1 && data.center && data.center[2];
            map.fitBounds(bounds, {padding: [30, 30], maxZoom: single ? data.center[2] : 12});
        } else {
            map.setView([data.center[0], data.center[1]], data.center[2] || 5);
        }
    }

    // un mapa oculto, por ejemplo en una pestaña cerrada, no tiene tamaño: se monta al mostrarse
    function init(root) {
        if (typeof L === 'undefined') {
            return;
        }

        root.querySelectorAll('[data-block-map]').forEach(function (element) {
            if (!element.dataset.blockMapReady && element.offsetWidth > 0) {
                element.dataset.blockMapReady = '1';
                build(element);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        init(document);
    });
    document.addEventListener('block:loaded', function (event) {
        init(event.detail.root);
    });
    document.addEventListener('shown.bs.tab', function () {
        init(document);
    });
})();
