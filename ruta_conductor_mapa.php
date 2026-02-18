<?php
require('conecta.php');
$fechaHoy = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ruta conductor</title>
    <?php includeAssets(); ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        .rc-page { padding: 0.95rem 0.55rem 1rem; background: #fff; }
        .rc-shell { max-width: 1620px; margin: 0 auto; }
        .rc-card { border: 1px solid #d8e6f2; border-radius: 14px; overflow: hidden; background: #fff; box-shadow: 0 8px 20px rgba(21, 58, 87, 0.08); }
        .rc-head { padding: 0.9rem 1rem; border-bottom: 1px solid #dce8f3; background: linear-gradient(180deg, #f7fbff 0%, #edf5fd 100%); }
        .rc-title { margin: 0; color: #123f60; font-size: 1.16rem; font-weight: 800; display: inline-flex; align-items: center; gap: 0.45rem; }
        .rc-sub { margin-top: 0.2rem; color: #4d6a80; font-size: 0.82rem; }
        .rc-filters { padding: 0.85rem 1rem 0.35rem; }
        .rc-filters .form-label { margin-bottom: 0.2rem; font-size: 0.78rem; font-weight: 700; color: #35566f; }
        .rc-kpis { padding: 0 1rem 0.55rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 0.45rem; }
        .rc-kpi { border: 1px solid #d7e6f2; border-radius: 10px; background: #f7fbff; padding: 0.45rem 0.6rem; }
        .rc-kpi span { display: block; color: #597488; font-size: 0.72rem; text-transform: uppercase; }
        .rc-kpi strong { display: block; color: #123f60; font-size: 1.02rem; font-weight: 800; }
        .rc-map-wrap { padding: 0 1rem 0.7rem; }
        #rcMapa { width: 100%; height: 520px; border: 1px solid #d7e6f2; border-radius: 12px; overflow: hidden; }
        .rc-table-wrap { padding: 0 1rem 1rem; }
        .rc-table-box { border: 1px solid #d7e6f2; border-radius: 10px; overflow: auto; max-height: 330px; }
        #rcTabla { margin: 0; min-width: 1020px; font-size: 0.83rem; }
        #rcTabla thead th { position: sticky; top: 0; z-index: 1; background: #edf5fd; text-transform: uppercase; font-size: 0.73rem; }
        .rc-empty { text-align: center; color: #5c7386; padding: 0.9rem; }
        .rc-chip { display: inline-block; border-radius: 999px; padding: 0.18rem 0.5rem; font-size: 0.72rem; font-weight: 700; background: #e8f1fa; color: #1c4f73; }
        @media (max-width: 992px) {
            #rcMapa { height: 420px; }
        }
    </style>
</head>
<body class="bodyc rc-page">
<section class="rc-shell">
    <div class="rc-card">
        <header class="rc-head">
            <h2 class="rc-title"><i class="fas fa-route"></i> RUTA CONDUCTOR (Mapa)</h2>
            <div class="rc-sub">Seguimiento de puntos de entrega por fecha y conductor con detalle de remision.</div>
        </header>

        <section class="rc-filters">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label" for="rc_fecha">Fecha</label>
                    <input type="date" id="rc_fecha" class="form-control" value="<?php echo $fechaHoy; ?>">
                </div>
                <div class="col-12 col-md-5">
                    <label class="form-label" for="rc_conductor">Conductor</label>
                    <select id="rc_conductor" class="form-select">
                        <option value="">Todos</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <div class="d-grid d-md-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary flex-fill" id="rc_btn_hoy"><i class="fas fa-calendar-day"></i> Hoy</button>
                        <button type="button" class="btn btn-primary flex-fill" id="rc_btn_cargar"><i class="fas fa-magnifying-glass-location"></i> Ver ruta</button>
                        <button type="button" class="btn btn-outline-danger" id="rc_btn_pdf" title="Exportar PDF"><i class="fas fa-file-pdf"></i></button>
                    </div>
                </div>
            </div>
        </section>

        <section class="rc-kpis">
            <article class="rc-kpi"><span>Puntos en mapa</span><strong id="rc_kpi_puntos">0</strong></article>
            <article class="rc-kpi"><span>Remisiones</span><strong id="rc_kpi_remisiones">0</strong></article>
            <article class="rc-kpi"><span>Conductores</span><strong id="rc_kpi_conductores">0</strong></article>
            <article class="rc-kpi"><span>Distancia aprox (km)</span><strong id="rc_kpi_km">0.0</strong></article>
        </section>

        <section class="rc-map-wrap">
            <div id="rcMapa"></div>
            <div id="rcMsg" class="rc-empty">Selecciona filtros y consulta la ruta.</div>
        </section>

        <section class="rc-table-wrap">
            <div class="rc-table-box">
                <table class="table table-sm table-hover align-middle" id="rcTabla">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Conductor</th>
                        <th>Guia</th>
                        <th>Remision</th>
                        <th>Cliente</th>
                        <th>Direccion</th>
                        <th>Estado</th>
                        <th>Fecha evento</th>
                        <th>Ubicacion</th>
                    </tr>
                    </thead>
                    <tbody id="rcBody">
                    <tr><td colspan="9" class="rc-empty">Sin datos</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</section>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function() {
    var mapa = null;
    var capaPuntos = null;
    var capaLineas = null;
    var colores = ['#1d5e88', '#2f9460', '#d08b24', '#9b4269', '#5d6e7b', '#4a5cb5', '#a26824'];

    function notificar(icon, title, text) {
        if (typeof Swal !== 'undefined' && Swal.fire) {
            Swal.fire({ icon: icon, title: title, text: text });
            return;
        }
        alert(title + ': ' + text);
    }

    function esc(v) {
        return String(v == null ? '' : v)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function n(v, dec) {
        var num = Number(v);
        if (!isFinite(num)) {
            num = 0;
        }
        if (typeof dec === 'number') {
            return num.toLocaleString('es-CO', { minimumFractionDigits: dec, maximumFractionDigits: dec });
        }
        return Math.round(num).toLocaleString('es-CO');
    }

    function fmtFecha(v) {
        if (!v) return '';
        var txt = String(v).replace(' ', 'T');
        var d = new Date(txt);
        if (isNaN(d.getTime())) return String(v);
        return d.toLocaleString('es-CO', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' });
    }

    function kmEntre(lat1, lng1, lat2, lng2) {
        var R = 6371;
        var toRad = function(x) { return x * Math.PI / 180; };
        var dLat = toRad(lat2 - lat1);
        var dLng = toRad(lng2 - lng1);
        var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
            Math.sin(dLng / 2) * Math.sin(dLng / 2);
        var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
    }

    function asegurarMapa() {
        if (mapa) {
            return;
        }
        mapa = L.map('rcMapa', { preferCanvas: true }).setView([7.1193, -73.1227], 7);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(mapa);
        capaLineas = L.layerGroup().addTo(mapa);
        capaPuntos = L.layerGroup().addTo(mapa);
    }

    function limpiarMapa() {
        if (!mapa) return;
        capaLineas.clearLayers();
        capaPuntos.clearLayers();
    }

    function renderTabla(rows) {
        var $body = $('#rcBody');
        if (!rows || !rows.length) {
            $body.html('<tr><td colspan="9" class="rc-empty">No hay puntos con geolocalizacion para el filtro.</td></tr>');
            return;
        }
        var html = '';
        for (var i = 0; i < rows.length; i++) {
            var r = rows[i];
            html += '<tr>' +
                '<td>' + (i + 1) + '</td>' +
                '<td>' + esc(r.conductor || '') + '</td>' +
                '<td>' + esc(r.guia || '') + '</td>' +
                '<td><span class="rc-chip">' + esc(r.remision || '') + '</span></td>' +
                '<td>' + esc(r.cliente || '') + '</td>' +
                '<td>' + esc(r.direccion || '') + '</td>' +
                '<td>' + esc(r.estado || '') + '</td>' +
                '<td>' + esc(fmtFecha(r.fecha_evento)) + '</td>' +
                '<td>' + esc((r.longitud || '') + ';' + (r.latitud || '')) + '</td>' +
                '</tr>';
        }
        $body.html(html);
    }

    function renderMapa(rows) {
        asegurarMapa();
        limpiarMapa();

        if (!rows || !rows.length) {
            $('#rcMsg').text('No hay puntos con geolocalizacion para los filtros seleccionados.');
            mapa.setView([7.1193, -73.1227], 7);
            return;
        }

        $('#rcMsg').text('');

        var bounds = [];
        var grupos = {};
        for (var i = 0; i < rows.length; i++) {
            var r = rows[i];
            var key = String(r.id_conductor || '0');
            if (!grupos[key]) {
                grupos[key] = { conductor: r.conductor || 'SIN CONDUCTOR', puntos: [] };
            }
            grupos[key].puntos.push(r);
        }

        var keys = Object.keys(grupos);
        var totalKm = 0;
        var totalConductores = keys.length;

        for (var g = 0; g < keys.length; g++) {
            var keyCon = keys[g];
            var data = grupos[keyCon];
            var color = colores[g % colores.length];
            var poly = [];

            for (var p = 0; p < data.puntos.length; p++) {
                var item = data.puntos[p];
                var lat = Number(item.latitud);
                var lng = Number(item.longitud);
                if (!isFinite(lat) || !isFinite(lng)) {
                    continue;
                }
                poly.push([lat, lng]);
                bounds.push([lat, lng]);

                if (poly.length > 1) {
                    var ant = poly[poly.length - 2];
                    totalKm += kmEntre(ant[0], ant[1], lat, lng);
                }

                var popup = '' +
                    '<div style="font-size:12px;min-width:230px">' +
                    '<strong>' + esc(item.remision || '') + '</strong><br>' +
                    'Guia: ' + esc(item.guia || '') + '<br>' +
                    'Conductor: ' + esc(item.conductor || '') + '<br>' +
                    'Cliente: ' + esc(item.cliente || '') + '<br>' +
                    'Direccion: ' + esc(item.direccion || '') + '<br>' +
                    'Estado: ' + esc(item.estado || '') + '<br>' +
                    'Fecha: ' + esc(fmtFecha(item.fecha_evento)) + '<br>' +
                    '<a target="_blank" href="https://www.google.com/maps?q=' + encodeURIComponent(lat + ',' + lng) + '">Abrir en mapa</a>' +
                    '</div>';

                L.circleMarker([lat, lng], {
                    radius: 7,
                    color: '#fff',
                    weight: 1,
                    fillColor: color,
                    fillOpacity: 0.95
                }).bindPopup(popup).addTo(capaPuntos);
            }

            if (poly.length >= 2) {
                L.polyline(poly, { color: color, weight: 4, opacity: 0.75 }).addTo(capaLineas);
            }
        }

        if (bounds.length) {
            mapa.fitBounds(bounds, { padding: [30, 30] });
        }
        setTimeout(function() {
            try { mapa.invalidateSize(); } catch (e) {}
        }, 120);

        $('#rc_kpi_puntos').text(n(rows.length));
        $('#rc_kpi_remisiones').text(n(rows.length));
        $('#rc_kpi_conductores').text(n(totalConductores));
        $('#rc_kpi_km').text(n(totalKm, 1));
    }

    function cargarConductores() {
        $.ajax({
            url: 'ruta_conductor_mapa_ajax.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'listar_conductores'
            },
            success: function(resp) {
                if (!resp || !resp.ok) {
                    return;
                }
                var data = resp.data || [];
                var html = '<option value="">Todos</option>';
                for (var i = 0; i < data.length; i++) {
                    var c = data[i];
                    html += '<option value="' + esc(c.terid) + '">' + esc(c.nombre) + '</option>';
                }
                $('#rc_conductor').html(html);
            }
        });
    }

    function cargarRuta() {
        var fecha = $('#rc_fecha').val() || '';
        if (!fecha) {
            notificar('warning', 'Fecha requerida', 'Selecciona una fecha para consultar la ruta.');
            return;
        }

        $('#rcMsg').text('Consultando puntos de ruta...');
        $('#rcBody').html('<tr><td colspan="9" class="rc-empty">Consultando...</td></tr>');
        $('#rc_kpi_puntos').text('0');
        $('#rc_kpi_remisiones').text('0');
        $('#rc_kpi_conductores').text('0');
        $('#rc_kpi_km').text('0.0');

        $.ajax({
            url: 'ruta_conductor_mapa_ajax.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'listar_ruta',
                fecha: fecha,
                id_conductor: $('#rc_conductor').val() || ''
            },
            success: function(resp) {
                if (!resp || !resp.ok) {
                    renderMapa([]);
                    renderTabla([]);
                    notificar('error', 'No se pudo consultar', (resp && resp.message) ? resp.message : 'Error consultando ruta.');
                    return;
                }
                var rows = resp.data || [];
                renderMapa(rows);
                renderTabla(rows);
            },
            error: function(xhr) {
                renderMapa([]);
                renderTabla([]);
                notificar('error', 'Error', 'Error de comunicacion. ' + ((xhr && xhr.responseText) ? xhr.responseText : ''));
            }
        });
    }

    function exportarPdfRuta() {
        var fecha = $('#rc_fecha').val() || '';
        if (!fecha) {
            notificar('warning', 'Fecha requerida', 'Selecciona una fecha para exportar.');
            return;
        }
        var idConductor = $('#rc_conductor').val() || '';
        var url = 'ruta_conductor_mapa_pdf.php?fecha=' + encodeURIComponent(fecha) + '&id_conductor=' + encodeURIComponent(idConductor);
        window.open(url, '_blank');
    }

    $(document).ready(function() {
        cargarConductores();
        $('#rc_btn_hoy').on('click', function() {
            var d = new Date();
            var y = d.getFullYear();
            var m = String(d.getMonth() + 1).padStart(2, '0');
            var day = String(d.getDate()).padStart(2, '0');
            $('#rc_fecha').val(y + '-' + m + '-' + day);
            cargarRuta();
        });
        $('#rc_btn_cargar').on('click', cargarRuta);
        $('#rc_btn_pdf').on('click', exportarPdfRuta);
        $('#rc_fecha').on('change', cargarRuta);
        cargarRuta();
    });
})();
</script>
</body>
</html>
