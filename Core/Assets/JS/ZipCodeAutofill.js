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

// Rellena la ciudad y la provincia a partir del código postal, en los formularios
// que tienen los campos codpostal, ciudad y provincia. Necesita que el controlador
// responda a la acción find-zip-code.

let zipCodeListCounter = 0;

function zipCodeRestoreCityList(cityInput) {
    // vuelve a poner las sugerencias originales del campo ciudad
    if (cityInput.dataset.zipCodeList === undefined) {
        return;
    }

    if (cityInput.dataset.zipCodeList === '') {
        cityInput.removeAttribute('list');
    } else {
        cityInput.setAttribute('list', cityInput.dataset.zipCodeList);
    }
}

function zipCodeSetCityList(cityInput, places) {
    // sustituye las sugerencias del campo ciudad por las ciudades del código postal
    if (cityInput.dataset.zipCodeList === undefined) {
        cityInput.dataset.zipCodeList = cityInput.getAttribute('list') || '';
    }

    let datalist = document.getElementById(cityInput.dataset.zipCodeCities || '');
    if (datalist === null) {
        datalist = document.createElement('datalist');
        datalist.id = 'zip-code-cities-' + (++zipCodeListCounter);
        cityInput.dataset.zipCodeCities = datalist.id;
        cityInput.after(datalist);
    }

    datalist.innerHTML = '';
    places.forEach(function (place) {
        const option = document.createElement('option');
        option.value = place.ciudad;
        datalist.appendChild(option);
    });
    cityInput.setAttribute('list', datalist.id);
}

function zipCodeAutofill(form) {
    const zipInput = form.elements['codpostal'];
    const cityInput = form.elements['ciudad'];
    const provinceInput = form.elements['provincia'];
    const countryInput = form.elements['codpais'];

    const zipCode = zipInput.value.trim();
    if (zipCode === '') {
        zipCodeRestoreCityList(cityInput);
        return;
    }

    $.ajax({
        method: 'POST',
        url: window.location.href,
        data: {
            action: 'find-zip-code',
            codpostal: zipCode,
            codpais: countryInput ? countryInput.value : ''
        },
        dataType: 'json',
        success: function (places) {
            // si el código ha cambiado mientras tanto, no tocamos nada
            if (zipInput.value.trim() !== zipCode) {
                return;
            }

            // sin resultados dejamos los campos como están
            if (false === Array.isArray(places) || places.length === 0) {
                zipCodeRestoreCityList(cityInput);
                return;
            }

            zipCodeSetCityList(cityInput, places);

            // la provincia solo se rellena si es la misma para todas las ciudades
            const sameProvince = places.every(function (place) {
                return place.provincia === places[0].provincia;
            });
            if (sameProvince) {
                provinceInput.value = places[0].provincia;
            }

            // con una única ciudad la rellenamos; con varias, se elige entre las sugerencias
            const cityFound = places.some(function (place) {
                return place.ciudad === cityInput.value;
            });
            if (places.length === 1) {
                cityInput.value = places[0].ciudad;
            } else if (false === cityFound) {
                cityInput.value = '';
                cityInput.focus();
            }
        }
    });
}

$(document).ready(function () {
    $('form').each(function () {
        const form = this;
        if (!form.elements['codpostal'] || !form.elements['ciudad'] || !form.elements['provincia']) {
            return;
        }

        $(form.elements['codpostal']).on('change', function () {
            zipCodeAutofill(form);
        });

        if (form.elements['codpais']) {
            $(form.elements['codpais']).on('change', function () {
                zipCodeAutofill(form);
            });
        }
    });
});
