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
        html, body {
            overscroll-behavior-y: none;
        }
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
        .dc-select-col.reset {
            flex: 0 0 auto;
            min-width: 112px;
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
        .dc-pod-wrap {
            margin-top: 0.55rem;
            border: 1px solid #d8e7f3;
            border-radius: 10px;
            background: #f8fbff;
            padding: 0.55rem;
        }
        .dc-pod-title {
            margin: 0 0 0.4rem;
            color: #164361;
            font-size: 0.8rem;
            font-weight: 700;
        }
        .dc-sign-canvas {
            width: 100%;
            max-width: 420px;
            height: 140px;
            border: 1px dashed #9fb8cd;
            border-radius: 8px;
            background: #fff;
            touch-action: none;
            display: block;
        }
        .dc-pod-preview {
            margin-top: 0.35rem;
            max-width: 220px;
            max-height: 120px;
            border: 1px solid #d8e7f3;
            border-radius: 8px;
            display: none;
            object-fit: cover;
            background: #fff;
        }
        .dc-pod-meta {
            font-size: 0.75rem;
            color: #48647a;
            margin-top: 0.3rem;
        }
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
                    <div class="dc-select-col estado">
                        <label class="form-label mb-1" for="dcFiltroEstadoGuia">Estado guia</label>
                        <select id="dcFiltroEstadoGuia" class="form-select">
                            <option value="PENDIENTES" selected>PENDIENTES</option>
                            <option value="TODAS">TODAS</option>
                            <option value="ENTREGADAS">ENTREGADAS</option>
                        </select>
                    </div>
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
                    <div class="dc-select-col reset">
                        <label class="form-label mb-1 d-none d-md-block">&nbsp;</label>
                        <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="dcBtnResetFiltros">
                            <i class="fas fa-eraser"></i> Reset
                        </button>
                    </div>
                </div>
                <div class="dc-meta-line">
                    <span class="dc-pendiente-fijo" id="dcGuiaModoTexto"><i class="fas fa-filter"></i> Solo guias pendientes</span>
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

            <div class="dc-resumen" id="dcResumenSeleccion">Selecciona una guia o deja TODAS para ver remisiones.</div>

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
                <h5 class="modal-title"><i class="fas fa-clipboard-check"></i> Registrar estado de entrega</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="dcJIdGuia" value="0">
                <input type="hidden" id="dcJKardexId" value="0">
                <div class="mb-2"><strong id="dcJRemision">-</strong></div>
                <div class="mb-2">
                    <label class="form-label mb-1" for="dcJEstado">Estado de entrega</label>
                    <select id="dcJEstado" class="form-select">
                        <option value="ENTREGADO">ENTREGADO</option>
                        <option value="NO_ENTREGADO">NO ENTREGADO</option>
                        <option value="ENTREGA_PARCIAL">ENTREGA PARCIAL</option>
                    </select>
                </div>
                <div class="mb-1">
                    <label class="form-label mb-1" for="dcJTexto">Justificacion</label>
                    <textarea id="dcJTexto" class="form-control" rows="4" maxlength="300" placeholder="Opcional para ENTREGADO"></textarea>
                    <div class="form-text" id="dcJTextoAyuda">Para ENTREGADO es opcional.</div>
                </div>

                <div class="dc-pod-wrap" id="dcJPodWrap">
                    <h6 class="dc-pod-title"><i class="fas fa-shield-check"></i> Prueba de entrega (POD)</h6>
                    <div class="row g-2">
                        <div class="col-12 col-md-6">
                            <label class="form-label mb-1" for="dcJFoto">Foto de entrega</label>
                            <input type="file" class="form-control" id="dcJFoto" accept="image/*" capture="environment">
                            <img id="dcJFotoPreview" class="dc-pod-preview" alt="Preview foto POD">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label mb-1">Firma receptor</label>
                            <canvas id="dcJFirmaCanvas" class="dc-sign-canvas" width="420" height="140"></canvas>
                            <div class="mt-1 d-flex gap-2 flex-wrap">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="dcBtnLimpiarFirma"><i class="fas fa-eraser"></i> Limpiar firma</button>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="dcBtnGeo"><i class="fas fa-location-crosshairs"></i> Capturar ubicacion</button>
                            </div>
                            <div class="dc-pod-meta" id="dcJGeoTxt">Ubicacion no capturada.</div>
                            <input type="hidden" id="dcJLat" value="">
                            <input type="hidden" id="dcJLng" value="">
                            <input type="hidden" id="dcJAcc" value="">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="dcBtnGuardarJustificacion"><i class="fas fa-save"></i> Guardar</button>
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
    var dcFirmaTieneTrazos = false;
    var dcFirmaCanvas = null;
    var dcFirmaCtx = null;
    var dcFirmaDibujando = false;

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

    function urlPdfRemision(kardexId, token) {
        if (!kardexId || !token) {
            return '';
        }
        return 'remision_entrega_pdf.php?kardex_id=' + encodeURIComponent(kardexId) + '&t=' + encodeURIComponent(token);
    }

    function esVistaMovilConductor() {
        return false;
    }

    function bloquearPullToRefreshMovil() {
        if (!('ontouchstart' in window)) {
            return;
        }

        var startY = 0;
        var startX = 0;

        function buscarScrollPadre(el) {
            var n = el;
            while (n && n !== document.body && n !== document.documentElement) {
                try {
                    var st = window.getComputedStyle(n);
                    var oy = st ? st.overflowY : '';
                    if ((oy === 'auto' || oy === 'scroll') && n.scrollHeight > n.clientHeight) {
                        return n;
                    }
                } catch (e) {}
                n = n.parentElement;
            }
            return null;
        }

        document.addEventListener('touchstart', function(e) {
            if (!e.touches || e.touches.length !== 1) {
                return;
            }
            startY = e.touches[0].clientY;
            startX = e.touches[0].clientX;
        }, { passive: true });

        document.addEventListener('touchmove', function(e) {
            if (!e.touches || e.touches.length !== 1) {
                return;
            }

            var dy = e.touches[0].clientY - startY;
            var dx = Math.abs(e.touches[0].clientX - startX);
            if (dy <= 8 || dy <= dx) {
                return;
            }

            var scrollPadre = buscarScrollPadre(e.target);
            var enTope = scrollPadre ? scrollPadre.scrollTop <= 0 : ((window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0) <= 0);
            if (enTope) {
                e.preventDefault();
            }
        }, { passive: false });
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

    function estadoGuiaFiltro() {
        return String($('#dcFiltroEstadoGuia').val() || 'PENDIENTES').toUpperCase();
    }

    function textoModoGuias() {
        var estado = estadoGuiaFiltro();
        if (estado === 'TODAS') {
            return '<i class="fas fa-filter"></i> Todas las guias';
        }
        if (estado === 'ENTREGADAS') {
            return '<i class="fas fa-filter"></i> Solo guias entregadas';
        }
        return '<i class="fas fa-filter"></i> Solo guias pendientes';
    }

    function actualizarModoGuiasUI() {
        $('#dcGuiaModoTexto').html(textoModoGuias());
    }

    function cargarGuias() {
        var estado = estadoGuiaFiltro();
        actualizarModoGuiasUI();

        $.ajax({
            url: 'despachos_conductor_ajax.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'listar_guias', estado_guia: estado },
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
        var estado = estadoGuiaFiltro();

        if (idGuia === '') {
            var visibles = guiasCache.slice();
            if (!visibles.length) {
                if (estado === 'ENTREGADAS') {
                    $('#dcResumenSeleccion').text('No hay guias entregadas para mostrar.');
                } else if (estado === 'TODAS') {
                    $('#dcResumenSeleccion').text('No hay guias para mostrar.');
                } else {
                    $('#dcResumenSeleccion').text('No hay guias pendientes para mostrar.');
                }
                $('#dcCuerpoRemisiones').html('<tr><td colspan="8" class="dc-empty">No hay remisiones para mostrar.</td></tr>');
                return;
            }

            var tareas = visibles.map(function(g){ return cargarRemisionesDeGuia(g.id, g.num_guia); });
            Promise.all(tareas).then(function(grupos) {
                var rows = [];
                for (var i = 0; i < grupos.length; i++) {
                    rows = rows.concat(grupos[i]);
                }
                remisionesCache = rows;
                $('#dcResumenSeleccion').text('Mostrando remisiones de todas las guias visibles.');
                aplicarFiltroRemisiones();
            }).catch(function(err){
                $('#dcCuerpoRemisiones').html('<tr><td colspan="8" class="dc-empty">Error cargando remisiones.</td></tr>');
                notificar('error', 'Error', err && err.message ? err.message : 'Error cargando remisiones.');
            });
            return;
        }

        var guia = guiasCache.find(function(g){ return String(g.id) === String(idGuia); });
        $('#dcResumenSeleccion').text(guia ? ('Guia seleccionada: ' + guia.num_guia + ' | Pendientes: ' + guia.total_pendientes + ' | Gestionadas: ' + (Number(guia.total_remisiones || 0) - Number(guia.total_pendientes || 0))) : 'Guia seleccionada');

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
            var estadoEntrega = String(r.estado_entrega || 'PENDIENTE').toUpperCase();
            var bloqueado = estadoEntrega !== 'PENDIENTE';
            var pdfUrl = urlPdfRemision(r.kardex_id, r.token_pdf || '');
            var podRegistrado = Number(r.tiene_pod || 0) > 0;
            var pdfDisponible = (estadoEntrega !== 'PENDIENTE') && pdfUrl !== '';
            var btnEstadoClass = bloqueado ? 'btn-outline-secondary disabled' : 'btn-outline-primary dc-btn-gestionar';
            var btnEstadoTitle = bloqueado ? 'Estado ya registrado (no editable)' : 'Registrar estado de entrega';
            var btnEstadoAttrs = bloqueado ? 'disabled aria-disabled="true"' : (
                'data-id-guia="' + r.id_guia + '" ' +
                'data-kardex-id="' + r.kardex_id + '" ' +
                'data-remision="' + escapeHtml(r.remision || '') + '"'
            );
            var iconoEstado = bloqueado ? 'fa-lock' : 'fa-clipboard-check';

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
                        '<button type="button" class="btn btn-sm ' + btnEstadoClass + '" ' + btnEstadoAttrs + ' title="' + btnEstadoTitle + '"><i class="fas ' + iconoEstado + '"></i></button>' +
                        '<a class="btn btn-sm btn-outline-danger ' + (pdfDisponible ? '' : 'disabled') + '" ' + (pdfDisponible ? ('href="' + pdfUrl + '" target="_blank"') : '') + ' title="' + (podRegistrado ? 'PDF final con evidencia POD' : 'PDF remision (sin evidencia POD)') + '"><i class="fas fa-file-pdf"></i></a>' +
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

    function resetFiltrosConductor() {
        $('#dcFiltroEstadoGuia').val('PENDIENTES');
        $('#dcGuiaSelect').val('');
        $('#dcFiltroEstadoRemision').val('PENDIENTE');
        cargarGuias();
    }

    function actualizarGeoPodTexto() {
        var lat = $('#dcJLat').val();
        var lng = $('#dcJLng').val();
        var acc = $('#dcJAcc').val();
        if (lat && lng) {
            var txt = 'Ubicacion: ' + lat + ', ' + lng;
            if (acc) {
                txt += ' (precision: ' + acc + ' m)';
            }
            $('#dcJGeoTxt').text(txt);
            return;
        }
        $('#dcJGeoTxt').text('Ubicacion no capturada.');
    }

    function limpiarFirmaPad() {
        if (!dcFirmaCanvas || !dcFirmaCtx) {
            return;
        }
        dcFirmaCtx.clearRect(0, 0, dcFirmaCanvas.width, dcFirmaCanvas.height);
        dcFirmaTieneTrazos = false;
    }

    function firmaDataUrl() {
        if (!dcFirmaCanvas || !dcFirmaTieneTrazos) {
            return '';
        }
        try {
            return dcFirmaCanvas.toDataURL('image/png');
        } catch (e) {
            return '';
        }
    }

    function coordFirmaDesdeEvento(evt) {
        if (!dcFirmaCanvas) {
            return null;
        }
        var rect = dcFirmaCanvas.getBoundingClientRect();
        var scaleX = dcFirmaCanvas.width / rect.width;
        var scaleY = dcFirmaCanvas.height / rect.height;
        var cx = 0;
        var cy = 0;

        if (evt.touches && evt.touches.length) {
            cx = evt.touches[0].clientX;
            cy = evt.touches[0].clientY;
        } else if (evt.changedTouches && evt.changedTouches.length) {
            cx = evt.changedTouches[0].clientX;
            cy = evt.changedTouches[0].clientY;
        } else {
            cx = evt.clientX;
            cy = evt.clientY;
        }

        return {
            x: (cx - rect.left) * scaleX,
            y: (cy - rect.top) * scaleY
        };
    }

    function initFirmaPad() {
        dcFirmaCanvas = document.getElementById('dcJFirmaCanvas');
        if (!dcFirmaCanvas) {
            return;
        }
        dcFirmaCtx = dcFirmaCanvas.getContext('2d');
        if (!dcFirmaCtx) {
            return;
        }
        dcFirmaCtx.lineWidth = 2.2;
        dcFirmaCtx.lineCap = 'round';
        dcFirmaCtx.lineJoin = 'round';
        dcFirmaCtx.strokeStyle = '#163d5c';

        var iniciar = function(evt) {
            evt.preventDefault();
            var p = coordFirmaDesdeEvento(evt);
            if (!p) {
                return;
            }
            dcFirmaDibujando = true;
            dcFirmaCtx.beginPath();
            dcFirmaCtx.moveTo(p.x, p.y);
        };
        var mover = function(evt) {
            if (!dcFirmaDibujando) {
                return;
            }
            evt.preventDefault();
            var p = coordFirmaDesdeEvento(evt);
            if (!p) {
                return;
            }
            dcFirmaCtx.lineTo(p.x, p.y);
            dcFirmaCtx.stroke();
            dcFirmaTieneTrazos = true;
        };
        var detener = function(evt) {
            if (!dcFirmaDibujando) {
                return;
            }
            if (evt && evt.preventDefault) {
                evt.preventDefault();
            }
            dcFirmaDibujando = false;
        };

        dcFirmaCanvas.addEventListener('mousedown', iniciar);
        dcFirmaCanvas.addEventListener('mousemove', mover);
        window.addEventListener('mouseup', detener);

        dcFirmaCanvas.addEventListener('touchstart', iniciar, { passive: false });
        dcFirmaCanvas.addEventListener('touchmove', mover, { passive: false });
        dcFirmaCanvas.addEventListener('touchend', detener, { passive: false });
        dcFirmaCanvas.addEventListener('touchcancel', detener, { passive: false });
    }

    function resetPodCampos() {
        $('#dcJFoto').val('');
        $('#dcJFotoPreview').hide().attr('src', '');
        $('#dcJLat').val('');
        $('#dcJLng').val('');
        $('#dcJAcc').val('');
        actualizarGeoPodTexto();
        limpiarFirmaPad();
    }

    function capturarGeolocalizacionPod() {
        if (!navigator.geolocation) {
            notificar('warning', 'Geolocalizacion', 'El navegador no soporta geolocalizacion.');
            return;
        }
        $('#dcJGeoTxt').text('Capturando ubicacion...');
        navigator.geolocation.getCurrentPosition(function(pos) {
            var c = pos.coords || {};
            $('#dcJLat').val((typeof c.latitude === 'number') ? c.latitude.toFixed(6) : '');
            $('#dcJLng').val((typeof c.longitude === 'number') ? c.longitude.toFixed(6) : '');
            $('#dcJAcc').val((typeof c.accuracy === 'number') ? c.accuracy.toFixed(1) : '');
            actualizarGeoPodTexto();
        }, function(err) {
            $('#dcJGeoTxt').text('No fue posible capturar ubicacion.');
            notificar('warning', 'Geolocalizacion', (err && err.message) ? err.message : 'No se pudo obtener ubicacion.');
        }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
    }

    function previewFotoPod() {
        var input = document.getElementById('dcJFoto');
        var img = document.getElementById('dcJFotoPreview');
        if (!input || !img) {
            return;
        }
        var file = (input.files && input.files.length) ? input.files[0] : null;
        if (!file) {
            img.style.display = 'none';
            img.removeAttribute('src');
            return;
        }
        var reader = new FileReader();
        reader.onload = function(e) {
            img.src = (e && e.target) ? (e.target.result || '') : '';
            img.style.display = img.src ? 'block' : 'none';
        };
        reader.readAsDataURL(file);
    }

    function actualizarModalEstadoEntrega() {
        var estado = String($('#dcJEstado').val() || 'ENTREGADO').toUpperCase();
        var requiereObs = estado !== 'ENTREGADO';
        var podObligatorio = estado === 'ENTREGADO';
        if (requiereObs) {
            $('#dcJTexto').attr('required', 'required').attr('placeholder', 'Obligatoria para este estado');
            $('#dcJTextoAyuda').text('Para ' + estado.replace('_', ' ') + ' la justificacion es obligatoria.');
        } else {
            $('#dcJTexto').removeAttr('required').attr('placeholder', 'Opcional para ENTREGADO');
            $('#dcJTextoAyuda').text('Para ENTREGADO la justificacion es opcional.');
        }

        if (podObligatorio) {
            $('#dcJPodWrap').removeClass('border-secondary').addClass('border-primary');
        } else {
            $('#dcJPodWrap').removeClass('border-primary').addClass('border-secondary');
        }
    }

    function abrirModalEstadoEntrega(idGuia, kardexId, remision) {
        $('#dcJIdGuia').val(idGuia);
        $('#dcJKardexId').val(kardexId);
        $('#dcJRemision').text(remision || '-');
        $('#dcJEstado').val('ENTREGADO');
        $('#dcJTexto').val('');
        resetPodCampos();
        actualizarModalEstadoEntrega();
        if (modalJustificacion && typeof modalJustificacion.show === 'function') {
            modalJustificacion.show();
        } else {
            $('#dcModalJustificar').show();
        }
    }

    function confirmarGuardadoEstado(callback) {
        if (typeof Swal !== 'undefined' && Swal.fire) {
            Swal.fire({
                icon: 'question',
                title: 'Confirmar registro',
                text: 'Luego de guardar no podras cambiar este estado de entrega. Deseas continuar?',
                showCancelButton: true,
                confirmButtonText: 'Si, guardar',
                cancelButtonText: 'Cancelar'
            }).then(function(result) {
                callback(!!(result && result.isConfirmed));
            });
            return;
        }
        callback(window.confirm('Luego de guardar no podras cambiar este estado de entrega. Deseas continuar?'));
    }

    function guardarJustificacion() {
        var idGuia = $('#dcJIdGuia').val();
        var kardexId = $('#dcJKardexId').val();
        var estadoEntrega = String($('#dcJEstado').val() || 'ENTREGADO').toUpperCase();
        var texto = $('#dcJTexto').val();
        var latitud = String($('#dcJLat').val() || '').trim();
        var longitud = String($('#dcJLng').val() || '').trim();
        var precisionGps = String($('#dcJAcc').val() || '').trim();
        var firma = firmaDataUrl();
        var fotoInput = document.getElementById('dcJFoto');
        var fotoFile = (fotoInput && fotoInput.files && fotoInput.files.length) ? fotoInput.files[0] : null;

        if (estadoEntrega !== 'ENTREGADO' && String(texto || '').trim() === '') {
            notificar('warning', 'Justificacion requerida', 'Debes ingresar una justificacion para este estado.');
            return;
        }

        if (estadoEntrega === 'ENTREGADO') {
            if (!fotoFile) {
                notificar('warning', 'POD requerido', 'Debes adjuntar una foto de entrega.');
                return;
            }
            if (!firma) {
                notificar('warning', 'POD requerido', 'Debes registrar la firma del receptor.');
                return;
            }
            if (!latitud || !longitud) {
                notificar('warning', 'POD requerido', 'Debes capturar geolocalizacion para ENTREGADO.');
                return;
            }
        }

        confirmarGuardadoEstado(function(aceptado) {
            if (!aceptado) {
                return;
            }

            var formData = new FormData();
            formData.append('action', 'guardar_estado_entrega');
            formData.append('id_guia', idGuia);
            formData.append('kardex_id', kardexId);
            formData.append('estado_entrega', estadoEntrega);
            formData.append('observacion', texto || '');
            formData.append('latitud', latitud);
            formData.append('longitud', longitud);
            formData.append('precision_gps', precisionGps);
            if (firma) {
                formData.append('firma_data', firma);
            }
            if (fotoFile) {
                formData.append('foto', fotoFile);
            }

            $.ajax({
                url: 'despachos_conductor_ajax.php',
                type: 'POST',
                dataType: 'json',
                processData: false,
                contentType: false,
                data: formData,
                success: function(resp) {
                    if (!resp || !resp.ok) {
                        notificar('error', 'No se pudo guardar', (resp && resp.message) ? resp.message : 'Error guardando estado.');
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

        initFirmaPad();

        bloquearPullToRefreshMovil();
        actualizarModoGuiasUI();

        $('#dcBtnActualizar').on('click', cargarGuias);
        $('#dcBtnResetFiltros').on('click', resetFiltrosConductor);
        $('#dcFiltroEstadoGuia').on('change', function() {
            var estadoGuia = estadoGuiaFiltro();
            var estadoRem = String($('#dcFiltroEstadoRemision').val() || 'PENDIENTE').toUpperCase();
            if (estadoGuia === 'ENTREGADAS' && estadoRem === 'PENDIENTE') {
                $('#dcFiltroEstadoRemision').val('TODOS');
            }
            cargarGuias();
        });
        $('#dcGuiaSelect').on('change', cargarRemisiones);
        $('#dcFiltroEstadoRemision').on('change', aplicarFiltroRemisiones);
        $('#dcJEstado').on('change', actualizarModalEstadoEntrega);
        $('#dcBtnLimpiarFirma').on('click', limpiarFirmaPad);
        $('#dcBtnGeo').on('click', capturarGeolocalizacionPod);
        $('#dcJFoto').on('change', previewFotoPod);

        $('#dcBtnGuardarJustificacion').on('click', guardarJustificacion);
        $('#dcBtnEnviarChat').on('click', enviarChat);
        $('#dcChatTexto').on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                enviarChat();
            }
        });

        $(document).on('click', '.dc-btn-gestionar', function() {
            abrirModalEstadoEntrega($(this).data('id-guia'), $(this).data('kardex-id'), $(this).data('remision'));
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
