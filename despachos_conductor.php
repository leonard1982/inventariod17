<?php
require('conecta.php');

$usuarioActual = isset($_SESSION['user']) ? strtoupper(trim((string)$_SESSION['user'])) : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Despachos conductor</title>
    <?php includeAssets(); ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <style>
        .dc-page { padding: 0.9rem 0.55rem 1rem; background: #fff; font-size: 0.93rem; }
        .dc-shell { max-width: 1450px; margin: 0 auto; }
        .dc-card {
            border: 1px solid #d8e6f1;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(14, 48, 76, 0.08);
            background: #fff;
        }
        .dc-head {
            padding: 0.9rem 1rem;
            border-bottom: 1px solid #dce8f3;
            background: linear-gradient(180deg, #f7fbff 0%, #edf5fd 100%);
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 0.35rem;
        }
        .dc-title-wrap {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        .dc-title {
            margin: 0;
            color: #133e5e;
            font-size: 1.15rem;
            font-weight: 800;
        }
        .dc-refresh-icon {
            width: 30px;
            height: 30px;
            padding: 0;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }
        .dc-sub { color: #547087; font-size: 0.83rem; }
        .dc-body { padding: 0.85rem 1rem 1rem; }
        .dc-filtros {
            border: 1px solid #d8e7f3;
            border-radius: 10px;
            background: #f8fbff;
            padding: 0.7rem;
            margin-bottom: 0.85rem;
        }
        .dc-filtros .form-label {
            font-size: 0.78rem;
            margin-bottom: 0.2rem !important;
            color: #36556c;
            font-weight: 600;
        }
        .dc-filtros .form-select,
        .dc-filtros .badge {
            font-size: 0.82rem;
        }
        .dc-filtros .form-select {
            min-height: 31px;
            padding-top: 0.2rem;
            padding-bottom: 0.2rem;
        }
        .dc-selects-line {
            display: flex;
            align-items: flex-end;
            gap: 0.5rem;
            flex-wrap: nowrap;
        }
        .dc-select-col {
            min-width: 0;
            flex: 1 1 0;
        }
        .dc-select-col.guia {
            flex: 1.3 1 0;
        }
        .dc-select-col.estado {
            flex: 1 1 0;
        }
        .dc-meta-line {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            margin-top: 0.4rem;
            flex-wrap: wrap;
        }
        .dc-tabla-wrap {
            border: 1px solid #d7e6f2;
            border-radius: 12px;
            overflow: auto;
            max-height: calc(100vh - 295px);
        }
        #dcTablaRemisiones { min-width: 1320px; margin: 0; font-size: 0.84rem; }
        #dcTablaRemisiones thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: #1d4f74;
            color: #fff;
            border-bottom: 0;
            text-transform: uppercase;
            font-size: 0.74rem;
            letter-spacing: 0.02em;
        }
        .dc-empty { text-align: center; color: #5b7286; padding: 1.2rem; }
        .dc-badge {
            display: inline-block;
            font-size: 0.72rem;
            font-weight: 700;
            border-radius: 999px;
            padding: 0.2rem 0.52rem;
        }
        .dc-badge.pendiente { background: #fff2cc; color: #6a4a00; }
        .dc-badge.entregado { background: #d7f4e3; color: #0d6537; }
        .dc-badge.no_entregado { background: #ffe0dd; color: #8a1f1f; }
        .dc-badge.parcial { background: #d7ebff; color: #1e4f82; }
        .dc-actions { display: inline-flex; gap: 0.28rem; flex-wrap: wrap; }
        .dc-actions .btn { padding: 0.16rem 0.42rem; }
        .dc-resumen {
            border: 1px solid #d8e7f3;
            border-radius: 9px;
            background: #f6fbff;
            padding: 0.55rem 0.7rem;
            margin-bottom: 0.8rem;
            color: #2f4e67;
            font-size: 0.84rem;
        }
        .dc-chat-list {
            border: 1px solid #d8e7f3;
            border-radius: 8px;
            min-height: 170px;
            max-height: 330px;
            overflow: auto;
            padding: 0.5rem;
            background: #fbfdff;
        }
        .dc-chat-msg {
            border: 1px solid #dbe7f2;
            border-radius: 8px;
            padding: 0.38rem 0.52rem;
            margin-bottom: 0.4rem;
            background: #fff;
        }
        .dc-chat-meta { font-size: 0.72rem; color: #60798e; margin-bottom: 0.15rem; }
        .dc-pendiente-fijo {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.76rem;
            font-weight: 600;
            color: #2e536b;
            border: 1px solid #d4e4f1;
            background: #eff6fd;
            border-radius: 999px;
            padding: 0.24rem 0.52rem;
        }
        .dc-mobile-dashboard {
            display: none !important;
            border: 1px solid #d8e7f3;
            border-radius: 10px;
            background: #ffffff;
            padding: 0.52rem;
            margin-bottom: 0.72rem;
        }
        .dc-mobile-kpi-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.42rem;
            margin-bottom: 0.5rem;
        }
        .dc-mobile-kpi {
            border: 1px solid #d6e6f2;
            border-radius: 9px;
            background: #f8fbff;
            padding: 0.32rem 0.44rem;
        }
        .dc-mobile-kpi span {
            display: block;
            color: #5b7489;
            font-size: 0.67rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .dc-mobile-kpi strong {
            color: #123d5e;
            font-size: 0.94rem;
            font-weight: 800;
        }
        .dc-mobile-chart-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.45rem;
        }
        .dc-mobile-chart-card {
            border: 1px solid #d7e6f2;
            border-radius: 9px;
            padding: 0.35rem;
            background: #fff;
        }
        .dc-mobile-chart-title {
            color: #1b4767;
            font-size: 0.7rem;
            font-weight: 700;
            margin: 0 0 0.2rem;
        }
        .dc-mobile-chart-card canvas {
            width: 100% !important;
            height: 120px !important;
        }
        @media (max-width: 992px) {
            .dc-tabla-wrap { max-height: calc(100vh - 240px); }
            .dc-body { padding: 0.7rem; }
        }
        @media (max-width: 767px) {
            .dc-selects-line {
                gap: 0.38rem;
            }
            .dc-filtros .form-label {
                font-size: 0.72rem;
            }
            .dc-filtros .form-select {
                font-size: 0.77rem;
                min-height: 30px;
            }
            .dc-mobile-dashboard { display: none !important; }
        }
    </style>
</head>
<body class="bodyc dc-page">
<section class="dc-shell">
    <div class="dc-card">
        <header class="dc-head">
            <div class="dc-title-wrap">
                <h2 class="dc-title"><i class="fas fa-truck-ramp-box"></i> Guias conductor</h2>
                <button type="button" class="btn btn-outline-primary btn-sm dc-refresh-icon" id="dcBtnActualizar" title="Actualizar">
                    <i class="fas fa-rotate"></i>
                </button>
            </div>
            <div class="dc-sub">Usuario: <?php echo htmlspecialchars($usuarioActual); ?> | Modulo operativo de entrega</div>
        </header>

        <div class="dc-body">
            <div class="dc-filtros">
                <div class="dc-selects-line">
                    <div class="dc-select-col guia">
                        <label class="form-label mb-1" for="dcGuiaSelect">Guia</label>
                        <select id="dcGuiaSelect" class="form-select">
                            <option value="">TODAS</option>
                        </select>
                    </div>
                    <div class="dc-select-col estado">
                        <label class="form-label mb-1" for="dcFiltroEstadoRemision">Estado remision</label>
                        <select id="dcFiltroEstadoRemision" class="form-select">
                            <option value="PENDIENTE" selected>PENDIENTE</option>
                            <option value="TODOS">TODOS</option>
                            <option value="ENTREGADO">ENTREGADO</option>
                            <option value="NO_ENTREGADO">NO ENTREGADO</option>
                            <option value="ENTREGA_PARCIAL">ENTREGA PARCIAL</option>
                        </select>
                    </div>
                </div>
                <div class="dc-meta-line">
                    <span class="dc-pendiente-fijo"><i class="fas fa-filter"></i> Solo guias pendientes</span>
                    <span class="badge text-bg-light border" id="dcResumenGuias">Sin datos</span>
                </div>
            </div>

            <section class="dc-mobile-dashboard" id="dcMobileDashboard">
                <div class="dc-mobile-kpi-grid">
                    <article class="dc-mobile-kpi"><span>Remisiones</span><strong id="dcMkTotal">0</strong></article>
                    <article class="dc-mobile-kpi"><span>Pendientes</span><strong id="dcMkPend">0</strong></article>
                    <article class="dc-mobile-kpi"><span>Entregadas</span><strong id="dcMkEnt">0</strong></article>
                    <article class="dc-mobile-kpi"><span>No/Parcial</span><strong id="dcMkNoPar">0</strong></article>
                </div>
                <div class="dc-mobile-chart-grid">
                    <article class="dc-mobile-chart-card">
                        <h6 class="dc-mobile-chart-title">Estados</h6>
                        <canvas id="dcMobileChartEstados"></canvas>
                    </article>
                    <article class="dc-mobile-chart-card">
                        <h6 class="dc-mobile-chart-title">Carga por guia</h6>
                        <canvas id="dcMobileChartGuias"></canvas>
                    </article>
                </div>
            </section>

            <div class="dc-resumen" id="dcResumenSeleccion">Selecciona una guia o deja TODAS para ver pendientes.</div>

            <div class="dc-tabla-wrap">
                <table class="table table-hover align-middle" id="dcTablaRemisiones">
                    <thead>
                        <tr>
                            <th>Guia</th>
                            <th>Remision</th>
                            <th>Cliente</th>
                            <th>Direccion</th>
                            <th>Telefono</th>
                            <th>Estado</th>
                            <th>Obs.</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="dcCuerpoRemisiones">
                        <tr><td colspan="8" class="dc-empty">Cargando informacion...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="dcModalJustificar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-comment-dots"></i> Justificacion de entrega</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="dcJIdGuia" value="0">
                <input type="hidden" id="dcJKardexId" value="0">
                <input type="hidden" id="dcJAccion" value="justificar_no_entregado">
                <div class="mb-2"><strong id="dcJRemision">-</strong></div>
                <div class="small text-muted mb-1" id="dcJTipoEstado">Justificacion de no entregado</div>
                <textarea id="dcJTexto" class="form-control" rows="4" maxlength="300" placeholder="Escribe aqui la justificacion..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-danger" id="dcBtnGuardarJustificacion"><i class="fas fa-save"></i> Guardar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="dcModalChat" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-comments"></i> Chat de entrega</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="dcCIdGuia" value="0">
                <input type="hidden" id="dcCKardexId" value="0">
                <div class="mb-2"><strong id="dcCRemision">-</strong></div>
                <div id="dcChatLista" class="dc-chat-list"></div>
                <div class="input-group mt-2">
                    <input type="text" id="dcChatTexto" class="form-control" maxlength="500" placeholder="Escribe mensaje de entrega...">
                    <button type="button" class="btn btn-primary" id="dcBtnEnviarChat"><i class="fas fa-paper-plane"></i> Enviar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var modalJustificacion = null;
    var modalChat = null;
    var guiasCache = [];
    var remisionesCache = [];
    var remisionesVisibles = [];
    var dcMobileChartEstados = null;
    var dcMobileChartGuias = null;

    function notificar(icon, title, text) {
        if (typeof Swal !== 'undefined' && Swal.fire) {
            Swal.fire({ icon: icon, title: title, text: text });
            return;
        }
        alert(title + ': ' + text);
    }

    function fmtFecha(v) {
        if (!v) return '';
        var txt = String(v).replace(' ', 'T');
        var d = new Date(txt);
        if (isNaN(d.getTime())) return String(v);
        return d.toLocaleString('es-CO', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' });
    }

    function escapeHtml(valor) {
        return String(valor == null ? '' : valor)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function badgeEstado(estado) {
        var e = String(estado || 'PENDIENTE').toUpperCase();
        if (e === 'ENTREGADO') return '<span class="dc-badge entregado">ENTREGADO</span>';
        if (e === 'NO_ENTREGADO') return '<span class="dc-badge no_entregado">NO ENTREGADO</span>';
        if (e === 'ENTREGA_PARCIAL') return '<span class="dc-badge parcial">ENTREGA PARCIAL</span>';
        return '<span class="dc-badge pendiente">PENDIENTE</span>';
    }

    function telefonoSoloDigitos(tel) {
        return String(tel || '').replace(/[^0-9]/g, '');
    }

    function urlWhatsapp(telefono, remision) {
        var tel = telefonoSoloDigitos(telefono);
        if (tel === '') {
            return '';
        }
        if (tel.length === 10) {
            tel = '57' + tel;
        }
        var txt = 'Seguimiento entrega remision ' + (remision || '');
        return 'https://wa.me/' + tel + '?text=' + encodeURIComponent(txt);
    }

    function urlMapa(direccion) {
        var dir = String(direccion || '').trim();
        if (dir === '') {
            return '';
        }
        return 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(dir);
    }

    function esVistaMovilConductor() {
        return false;
    }

    function calcularMetricasConductor(items) {
        var m = {
            total: 0,
            pendiente: 0,
            entregado: 0,
            noEntregado: 0,
            parcial: 0,
            porGuia: {}
        };
        var lista = Array.isArray(items) ? items : [];

        for (var i = 0; i < lista.length; i++) {
            var r = lista[i];
            var e = String(r.estado_entrega || 'PENDIENTE').toUpperCase();
            var g = String(r.num_guia || 'SIN_GUIA');

            m.total++;
            if (!Object.prototype.hasOwnProperty.call(m.porGuia, g)) {
                m.porGuia[g] = 0;
            }
            m.porGuia[g]++;

            if (e === 'ENTREGADO') {
                m.entregado++;
            } else if (e === 'NO_ENTREGADO') {
                m.noEntregado++;
            } else if (e === 'ENTREGA_PARCIAL') {
                m.parcial++;
            } else {
                m.pendiente++;
            }
        }

        return m;
    }

    function actualizarDashboardMovilConductor(items) {
        if (!esVistaMovilConductor()) {
            return;
        }

        var m = calcularMetricasConductor(items);
        $('#dcMkTotal').text(m.total);
        $('#dcMkPend').text(m.pendiente);
        $('#dcMkEnt').text(m.entregado);
        $('#dcMkNoPar').text(m.noEntregado + m.parcial);

        if (typeof Chart === 'undefined') {
            return;
        }

        var ctxE = document.getElementById('dcMobileChartEstados');
        var ctxG = document.getElementById('dcMobileChartGuias');
        if (!ctxE || !ctxG) {
            return;
        }

        var dataEstados = [m.pendiente, m.entregado, m.noEntregado, m.parcial];
        if (dcMobileChartEstados) {
            dcMobileChartEstados.data.datasets[0].data = dataEstados;
            dcMobileChartEstados.update();
        } else {
            dcMobileChartEstados = new Chart(ctxE, {
                type: 'doughnut',
                data: {
                    labels: ['Pendiente', 'Entregado', 'No entregado', 'Parcial'],
                    datasets: [{
                        data: dataEstados,
                        backgroundColor: ['#e8c05d', '#2f9460', '#cb4b4b', '#3a79b7'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } }
                }
            });
        }

        var labelsGuias = Object.keys(m.porGuia);
        var valuesGuias = [];
        for (var i = 0; i < labelsGuias.length; i++) {
            valuesGuias.push(m.porGuia[labelsGuias[i]]);
        }

        if (!labelsGuias.length) {
            labelsGuias = ['Sin datos'];
            valuesGuias = [0];
        }

        if (dcMobileChartGuias) {
            dcMobileChartGuias.data.labels = labelsGuias;
            dcMobileChartGuias.data.datasets[0].data = valuesGuias;
            dcMobileChartGuias.update();
        } else {
            dcMobileChartGuias = new Chart(ctxG, {
                type: 'bar',
                data: {
                    labels: labelsGuias,
                    datasets: [{
                        label: 'Remisiones',
                        data: valuesGuias,
                        backgroundColor: '#2b709f'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 } },
                        x: { ticks: { maxRotation: 0, minRotation: 0, autoSkip: true } }
                    }
                }
            });
        }
    }

    function cargarGuias() {
        var solo = 'S';

        $.ajax({
            url: 'despachos_conductor_ajax.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'listar_guias', solo_pendientes: solo },
            success: function(resp) {
                if (!resp || !resp.ok) {
                    $('#dcResumenGuias').text('Error al cargar guias');
                    notificar('error', 'Error', (resp && resp.message) ? resp.message : 'No se pudo cargar guias.');
                    return;
                }

                guiasCache = resp.data || [];
                renderSelectGuias();
                var total = guiasCache.length;
                $('#dcResumenGuias').text('Guias visibles: ' + total);

                if (resp.warning) {
                    $('#dcResumenSeleccion').text(resp.warning);
                }

                var actual = $('#dcGuiaSelect').val() || '';
                if (actual === '' || !guiasCache.some(function(g){ return String(g.id) === String(actual); })) {
                    $('#dcGuiaSelect').val('');
                }

                cargarRemisiones();
            },
            error: function(xhr) {
                notificar('error', 'Error', 'Error de comunicacion al listar guias. ' + (xhr && xhr.responseText ? xhr.responseText : ''));
            }
        });
    }

    function renderSelectGuias() {
        var html = '<option value="">TODAS</option>';
        for (var i = 0; i < guiasCache.length; i++) {
            var g = guiasCache[i];
            html += '<option value="' + g.id + '">' + g.num_guia + ' | Pendientes: ' + g.total_pendientes + '</option>';
        }
        $('#dcGuiaSelect').html(html);
    }

    function cargarRemisiones() {
        var idGuia = $('#dcGuiaSelect').val() || '';

        if (idGuia === '') {
            var pendientes = guiasCache.filter(function(g){ return Number(g.total_pendientes || 0) > 0; });
            if (!pendientes.length) {
                $('#dcResumenSeleccion').text('No hay guias pendientes para mostrar.');
                $('#dcCuerpoRemisiones').html('<tr><td colspan="8" class="dc-empty">No hay remisiones pendientes.</td></tr>');
                return;
            }

            var tareas = pendientes.map(function(g){ return cargarRemisionesDeGuia(g.id, g.num_guia); });
            Promise.all(tareas).then(function(grupos) {
                var rows = [];
                for (var i = 0; i < grupos.length; i++) {
                    rows = rows.concat(grupos[i]);
                }
                remisionesCache = rows;
                $('#dcResumenSeleccion').text('Mostrando remisiones pendientes de todas las guias visibles.');
                aplicarFiltroRemisiones();
            }).catch(function(err){
                $('#dcCuerpoRemisiones').html('<tr><td colspan="8" class="dc-empty">Error cargando remisiones.</td></tr>');
                notificar('error', 'Error', err && err.message ? err.message : 'Error cargando remisiones.');
            });
            return;
        }

        var guia = guiasCache.find(function(g){ return String(g.id) === String(idGuia); });
        $('#dcResumenSeleccion').text(guia ? ('Guia seleccionada: ' + guia.num_guia + ' | Pendientes: ' + guia.total_pendientes) : 'Guia seleccionada');

        cargarRemisionesDeGuia(idGuia, guia ? guia.num_guia : '').then(function(rows) {
            remisionesCache = rows;
            aplicarFiltroRemisiones();
        }).catch(function(err){
            $('#dcCuerpoRemisiones').html('<tr><td colspan="8" class="dc-empty">Error cargando remisiones.</td></tr>');
            notificar('error', 'Error', err && err.message ? err.message : 'Error cargando remisiones.');
        });
    }

    function cargarRemisionesDeGuia(idGuia, numGuia) {
        return new Promise(function(resolve, reject) {
            $.ajax({
                url: 'despachos_conductor_ajax.php',
                type: 'POST',
                dataType: 'json',
                data: { action: 'listar_remisiones_guia', id_guia: idGuia },
                success: function(resp) {
                    if (!resp || !resp.ok) {
                        reject(new Error((resp && resp.message) ? resp.message : 'No se pudo cargar remisiones de la guia.'));
                        return;
                    }

                    var rows = (resp.data || []).map(function(r) {
                        r.id_guia = Number(idGuia);
                        r.num_guia = numGuia || '';
                        return r;
                    });
                    resolve(rows);
                },
                error: function(xhr) {
                    reject(new Error('Error de comunicacion. ' + (xhr && xhr.responseText ? xhr.responseText : '')));
                }
            });
        });
    }

    function renderRemisiones(items) {
        if (!items || !items.length) {
            $('#dcCuerpoRemisiones').html('<tr><td colspan="8" class="dc-empty">No hay remisiones para mostrar.</td></tr>');
            remisionesVisibles = [];
            actualizarDashboardMovilConductor([]);
            return;
        }

        var html = '';
        for (var i = 0; i < items.length; i++) {
            var r = items[i];
            var mapUrl = urlMapa(r.direccion);
            var waUrl = urlWhatsapp(r.telefono, r.remision);
            var obs = r.observacion || '';

            html += '' +
                '<tr>' +
                '<td>' + escapeHtml(r.num_guia || '') + '</td>' +
                '<td>' + escapeHtml(r.remision || '') + '</td>' +
                '<td>' + escapeHtml(r.cliente || '') + '</td>' +
                '<td>' + escapeHtml(r.direccion || '') + '</td>' +
                '<td>' + escapeHtml(r.telefono || '') + '</td>' +
                '<td>' + badgeEstado(r.estado_entrega) + '</td>' +
                '<td>' + escapeHtml(obs) + '</td>' +
                '<td>' +
                    '<div class="dc-actions">' +
                        '<button type="button" class="btn btn-sm btn-success dc-btn-entregado" data-id-guia="' + r.id_guia + '" data-kardex-id="' + r.kardex_id + '" title="Marcar entregado"><i class="fas fa-check"></i></button>' +
                        '<button type="button" class="btn btn-sm btn-warning dc-btn-parcial" data-id-guia="' + r.id_guia + '" data-kardex-id="' + r.kardex_id + '" data-remision="' + escapeHtml(r.remision || '') + '" title="Entrega parcial"><i class="fas fa-box-open"></i></button>' +
                        '<button type="button" class="btn btn-sm btn-danger dc-btn-justificar" data-id-guia="' + r.id_guia + '" data-kardex-id="' + r.kardex_id + '" data-remision="' + escapeHtml(r.remision || '') + '" title="Justificar no entregado"><i class="fas fa-comment-dots"></i></button>' +
                        '<button type="button" class="btn btn-sm btn-primary dc-btn-chat" data-id-guia="' + r.id_guia + '" data-kardex-id="' + r.kardex_id + '" data-remision="' + escapeHtml(r.remision || '') + '" title="Chat entrega"><i class="fas fa-comments"></i><span class="ms-1">' + (r.total_chat || 0) + '</span></button>' +
                        '<a class="btn btn-sm btn-outline-secondary ' + (mapUrl === '' ? 'disabled' : '') + '" ' + (mapUrl ? ('href="' + mapUrl + '" target="_blank"') : '') + ' title="Ir a direccion"><i class="fas fa-location-dot"></i></a>' +
                        '<a class="btn btn-sm btn-outline-success ' + (waUrl === '' ? 'disabled' : '') + '" ' + (waUrl ? ('href="' + waUrl + '" target="_blank"') : '') + ' title="WhatsApp"><i class="fab fa-whatsapp"></i></a>' +
                    '</div>' +
                '</td>' +
                '</tr>';
        }

        $('#dcCuerpoRemisiones').html(html);
        remisionesVisibles = items.slice();
        actualizarDashboardMovilConductor(items);
    }

    function aplicarFiltroRemisiones() {
        var estado = String($('#dcFiltroEstadoRemision').val() || 'PENDIENTE').toUpperCase();
        var items = remisionesCache.slice();

        if (estado !== 'TODOS') {
            items = items.filter(function(r) {
                var e = String(r.estado_entrega || 'PENDIENTE').toUpperCase();
                return e === estado;
            });
        }

        renderRemisiones(items);
    }

    function marcarEntregado(idGuia, kardexId) {
        $.ajax({
            url: 'despachos_conductor_ajax.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'marcar_entregado', id_guia: idGuia, kardex_id: kardexId },
            success: function(resp) {
                if (!resp || !resp.ok) {
                    notificar('error', 'No se pudo actualizar', (resp && resp.message) ? resp.message : 'Error actualizando estado.');
                    return;
                }
                cargarGuias();
            },
            error: function(xhr) {
                notificar('error', 'Error', 'Error de comunicacion. ' + (xhr && xhr.responseText ? xhr.responseText : ''));
            }
        });
    }

    function abrirJustificacion(idGuia, kardexId, remision, accion, tipo) {
        var accionNorm = accion || 'justificar_no_entregado';
        var tipoTxt = tipo || 'NO ENTREGADO';
        $('#dcJIdGuia').val(idGuia);
        $('#dcJKardexId').val(kardexId);
        $('#dcJAccion').val(accionNorm);
        $('#dcJRemision').text(remision || '-');
        $('#dcJTipoEstado').text('Justificacion de ' + tipoTxt);
        $('#dcJTexto').val('');
        if (modalJustificacion && typeof modalJustificacion.show === 'function') {
            modalJustificacion.show();
        } else {
            $('#dcModalJustificar').show();
        }
    }

    function guardarJustificacion() {
        var idGuia = $('#dcJIdGuia').val();
        var kardexId = $('#dcJKardexId').val();
        var texto = $('#dcJTexto').val();
        var accion = $('#dcJAccion').val() || 'justificar_no_entregado';

        $.ajax({
            url: 'despachos_conductor_ajax.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: accion,
                id_guia: idGuia,
                kardex_id: kardexId,
                observacion: texto
            },
            success: function(resp) {
                if (!resp || !resp.ok) {
                    notificar('error', 'No se pudo guardar', (resp && resp.message) ? resp.message : 'Error guardando justificacion.');
                    return;
                }
                if (modalJustificacion && typeof modalJustificacion.hide === 'function') {
                    modalJustificacion.hide();
                } else {
                    $('#dcModalJustificar').hide();
                }
                cargarGuias();
            },
            error: function(xhr) {
                notificar('error', 'Error', 'Error de comunicacion. ' + (xhr && xhr.responseText ? xhr.responseText : ''));
            }
        });
    }

    function abrirChat(idGuia, kardexId, remision) {
        $('#dcCIdGuia').val(idGuia);
        $('#dcCKardexId').val(kardexId);
        $('#dcCRemision').text(remision || '-');
        $('#dcChatTexto').val('');
        cargarChat();
        if (modalChat && typeof modalChat.show === 'function') {
            modalChat.show();
        } else {
            $('#dcModalChat').show();
        }
    }

    function cargarChat() {
        var idGuia = $('#dcCIdGuia').val();
        var kardexId = $('#dcCKardexId').val();

        $('#dcChatLista').html('<div class="dc-empty">Cargando chat...</div>');

        $.ajax({
            url: 'despachos_conductor_ajax.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'obtener_chat_remision', id_guia: idGuia, kardex_id: kardexId },
            success: function(resp) {
                if (!resp || !resp.ok) {
                    $('#dcChatLista').html('<div class="dc-empty">No se pudo cargar el chat.</div>');
                    return;
                }

                var items = resp.data || [];
                if (!items.length) {
                    $('#dcChatLista').html('<div class="dc-empty">Sin mensajes de entrega.</div>');
                    return;
                }

                var html = '';
                for (var i = 0; i < items.length; i++) {
                    var m = items[i];
                    html += '<div class="dc-chat-msg">' +
                                '<div class="dc-chat-meta">' + escapeHtml(m.usuario || '') + ' | ' + fmtFecha(m.fecha_mensaje) + ' | ' + escapeHtml(m.tipo || 'CHAT') + '</div>' +
                                '<div>' + escapeHtml(m.mensaje || '') + '</div>' +
                            '</div>';
                }

                $('#dcChatLista').html(html);
                $('#dcChatLista').scrollTop($('#dcChatLista')[0].scrollHeight);
            },
            error: function() {
                $('#dcChatLista').html('<div class="dc-empty">Error de comunicacion cargando chat.</div>');
            }
        });
    }

    function enviarChat() {
        var idGuia = $('#dcCIdGuia').val();
        var kardexId = $('#dcCKardexId').val();
        var mensaje = $('#dcChatTexto').val();

        $.ajax({
            url: 'despachos_conductor_ajax.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'enviar_chat_remision',
                id_guia: idGuia,
                kardex_id: kardexId,
                mensaje: mensaje
            },
            success: function(resp) {
                if (!resp || !resp.ok) {
                    notificar('error', 'No se pudo enviar', (resp && resp.message) ? resp.message : 'Error enviando mensaje.');
                    return;
                }
                $('#dcChatTexto').val('');
                cargarChat();
                cargarGuias();
            },
            error: function(xhr) {
                notificar('error', 'Error', 'Error de comunicacion. ' + (xhr && xhr.responseText ? xhr.responseText : ''));
            }
        });
    }

    $(document).ready(function() {
        if (window.bootstrap && bootstrap.Modal) {
            modalJustificacion = new bootstrap.Modal(document.getElementById('dcModalJustificar'));
            modalChat = new bootstrap.Modal(document.getElementById('dcModalChat'));
        }

        $('#dcBtnActualizar').on('click', cargarGuias);
        $('#dcGuiaSelect').on('change', cargarRemisiones);
        $('#dcFiltroEstadoRemision').on('change', aplicarFiltroRemisiones);

        $('#dcBtnGuardarJustificacion').on('click', guardarJustificacion);
        $('#dcBtnEnviarChat').on('click', enviarChat);
        $('#dcChatTexto').on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                enviarChat();
            }
        });

        $(document).on('click', '.dc-btn-entregado', function() {
            marcarEntregado($(this).data('id-guia'), $(this).data('kardex-id'));
        });

        $(document).on('click', '.dc-btn-justificar', function() {
            abrirJustificacion($(this).data('id-guia'), $(this).data('kardex-id'), $(this).data('remision'), 'justificar_no_entregado', 'NO ENTREGADO');
        });

        $(document).on('click', '.dc-btn-parcial', function() {
            abrirJustificacion($(this).data('id-guia'), $(this).data('kardex-id'), $(this).data('remision'), 'justificar_parcial', 'ENTREGA PARCIAL');
        });

        $(document).on('click', '.dc-btn-chat', function() {
            abrirChat($(this).data('id-guia'), $(this).data('kardex-id'), $(this).data('remision'));
        });

        $(window).on('resize', function() {
            actualizarDashboardMovilConductor(remisionesVisibles);
        });

        cargarGuias();
    });
})();
</script>
</body>
</html>
