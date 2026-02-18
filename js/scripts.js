const TAB_HOME_ID = 'tab_home';
const STORAGE_MENU_MODO = 'inventario_menu_modo';
const MODULO_THEME_VERSION = '20260217_01';
const HOME_DASHBOARD_STATE = {
    chartGuiasDia: null,
    chartEstados: null,
    chartConductores: null,
    chartMovilEstados: null,
    chartMovilGuias: null,
    chartJsLoading: false,
    dataLoading: false,
    dataAt: 0,
    data: null,
    callbacks: []
};
const NAV_LOCK_STATE = {
    enabled: false,
    marker: 'inventario_nav_lock'
};

function iniciarBloqueoNavegacionAtras() {
    if (NAV_LOCK_STATE.enabled) {
        return;
    }
    NAV_LOCK_STATE.enabled = true;

    try {
        if (window.history && typeof window.history.pushState === 'function') {
            var estado = { lock: NAV_LOCK_STATE.marker, ts: Date.now() };
            window.history.replaceState(estado, document.title, window.location.href);
            window.history.pushState(estado, document.title, window.location.href);
        }
    } catch (e) {
    }

    window.addEventListener('popstate', function() {
        try {
            if (window.history && typeof window.history.pushState === 'function') {
                var estado = { lock: NAV_LOCK_STATE.marker, ts: Date.now() };
                window.history.pushState(estado, document.title, window.location.href);
            }
        } catch (e) {
        }
    });

    $(document).on('keydown', function(e) {
        var key = e.key || '';
        var keyLower = key.toLowerCase();
        var target = e.target || null;
        var tag = target && target.tagName ? target.tagName.toLowerCase() : '';
        var editable = !!(
            target &&
            (
                tag === 'input' ||
                tag === 'textarea' ||
                tag === 'select' ||
                target.isContentEditable
            )
        );

        if (e.altKey && (key === 'ArrowLeft' || keyLower === 'left' || e.which === 37 || e.keyCode === 37)) {
            e.preventDefault();
            return false;
        }

        if (!editable && (key === 'Backspace' || e.which === 8 || e.keyCode === 8)) {
            e.preventDefault();
            return false;
        }

        if (key === 'BrowserBack' || keyLower === 'browserback') {
            e.preventDefault();
            return false;
        }
    });
}

function agregarRecursoEnDocumento(doc, tagName, attrs, id) {
    if (!doc || !doc.head) {
        return null;
    }

    if (id && doc.getElementById(id)) {
        return doc.getElementById(id);
    }

    var el = doc.createElement(tagName);
    if (id) {
        el.id = id;
    }

    for (var key in attrs) {
        if (Object.prototype.hasOwnProperty.call(attrs, key)) {
            el.setAttribute(key, attrs[key]);
        }
    }

    doc.head.appendChild(el);
    return el;
}

function inyectarTemaModulo(doc) {
    if (!doc || !doc.head) {
        return;
    }

    agregarRecursoEnDocumento(
        doc,
        'link',
        {
            rel: 'stylesheet',
            href: 'css/modulo_tema_global.css?v=' + MODULO_THEME_VERSION
        },
        'modulo-theme-global-css'
    );

    agregarRecursoEnDocumento(
        doc,
        'link',
        {
            rel: 'stylesheet',
            href: 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css'
        },
        'modulo-theme-fontawesome'
    );

    agregarRecursoEnDocumento(
        doc,
        'script',
        {
            src: 'js/modulo_tema_global.js?v=' + MODULO_THEME_VERSION,
            defer: 'defer'
        },
        'modulo-theme-global-js'
    );
}

function ajustarBarraLateral(anchoBarra, margenContenido) {
    var body = document.body;
    var sidebar = document.getElementById('sidebar');
    var botonToggle = document.querySelector('.menu-toggle-btn');

    if (!body || !sidebar) {
        return;
    }

    var abrir = anchoBarra !== '0';
    body.classList.toggle('menu-open', abrir);
    if (abrir) {
        sidebar.removeAttribute('inert');
        sidebar.setAttribute('aria-hidden', 'false');
    } else {
        if (sidebar.contains(document.activeElement) && botonToggle) {
            botonToggle.focus();
        }
        sidebar.setAttribute('aria-hidden', 'true');
        sidebar.setAttribute('inert', '');
    }
}

function mostrar() {
    ajustarBarraLateral('300px', '300px');
}

function ocultar() {
    ajustarBarraLateral('0', '0');
}

function alternarSidebar() {
    var estaAbierto = document.body.classList.contains('menu-open');
    if (estaAbierto) {
        ocultar();
    } else {
        mostrar();
    }
}

function cerrarMenu() {
    ocultar();
}

function esMovil() {
    return window.innerWidth < 992;
}

function esEscritorio() {
    return !esMovil();
}

function obtenerPanelInicio() {
    return document.querySelector('#tabPanels .app-tab-panel[data-tab-id="' + TAB_HOME_ID + '"]');
}

function destruirGraficoInicio(key) {
    if (!HOME_DASHBOARD_STATE[key]) {
        return;
    }
    try {
        if (typeof HOME_DASHBOARD_STATE[key].destroy === 'function') {
            HOME_DASHBOARD_STATE[key].destroy();
        }
    } catch (e) {
    }
    HOME_DASHBOARD_STATE[key] = null;
}

function destruirDashboardInicio() {
    destruirGraficoInicio('chartGuiasDia');
    destruirGraficoInicio('chartEstados');
    destruirGraficoInicio('chartConductores');
    destruirGraficoInicio('chartMovilEstados');
    destruirGraficoInicio('chartMovilGuias');
}

function asegurarChartJsPrincipal(callback) {
    if (window.Chart) {
        callback();
        return;
    }

    if (HOME_DASHBOARD_STATE.chartJsLoading) {
        setTimeout(function() {
            asegurarChartJsPrincipal(callback);
        }, 120);
        return;
    }

    HOME_DASHBOARD_STATE.chartJsLoading = true;
    var script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js';
    script.async = true;
    script.onload = function() {
        HOME_DASHBOARD_STATE.chartJsLoading = false;
        callback();
    };
    script.onerror = function() {
        HOME_DASHBOARD_STATE.chartJsLoading = false;
    };
    document.head.appendChild(script);
}

function homeNumero(v, decimales) {
    var n = Number(v);
    if (!isFinite(n)) {
        n = 0;
    }
    if (typeof decimales === 'number') {
        return n.toLocaleString('es-CO', {
            minimumFractionDigits: decimales,
            maximumFractionDigits: decimales
        });
    }
    return Math.round(n).toLocaleString('es-CO');
}

function homeDataSerie(serie, fallbackLabel) {
    var labels = (serie && Array.isArray(serie.labels)) ? serie.labels.slice() : [];
    var values = (serie && Array.isArray(serie.values)) ? serie.values.slice() : [];
    if (!labels.length || !values.length) {
        labels = [fallbackLabel || 'SIN DATOS'];
        values = [0];
    }
    return { labels: labels, values: values };
}

function finalizarCargaDashboardInicio(data, error) {
    HOME_DASHBOARD_STATE.dataLoading = false;
    var callbacks = HOME_DASHBOARD_STATE.callbacks.slice();
    HOME_DASHBOARD_STATE.callbacks = [];
    for (var i = 0; i < callbacks.length; i++) {
        try {
            callbacks[i](data, error);
        } catch (e) {
        }
    }
}

function dashboardInicioCacheVigente() {
    var ttl = 45000;
    return !!(HOME_DASHBOARD_STATE.data && (Date.now() - HOME_DASHBOARD_STATE.dataAt < ttl));
}

function cargarDatosDashboardInicio(forzar, callback) {
    var cacheVigente = dashboardInicioCacheVigente();

    if (!forzar && cacheVigente) {
        callback(HOME_DASHBOARD_STATE.data, null);
        return;
    }

    HOME_DASHBOARD_STATE.callbacks.push(callback);
    if (HOME_DASHBOARD_STATE.dataLoading) {
        return;
    }

    HOME_DASHBOARD_STATE.dataLoading = true;

    $.ajax({
        url: 'dashboard_inicio_ajax.php',
        type: 'POST',
        dataType: 'json',
        cache: false,
        data: { action: 'dashboard_inicio' },
        success: function(resp) {
            if (resp && resp.ok && resp.data) {
                HOME_DASHBOARD_STATE.data = resp.data;
                HOME_DASHBOARD_STATE.dataAt = Date.now();
                finalizarCargaDashboardInicio(resp.data, null);
                return;
            }
            finalizarCargaDashboardInicio(null, (resp && resp.message) ? resp.message : 'No se pudo obtener indicadores.');
        },
        error: function(xhr) {
            var txt = (xhr && xhr.responseText) ? xhr.responseText : 'Error de comunicacion.';
            finalizarCargaDashboardInicio(null, txt);
        }
    });
}

function actualizarHoraDashboardInicio() {
    var hora = new Date().toLocaleTimeString('es-CO', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
    if ($('#homeDashHora').length) {
        $('#homeDashHora').text(hora);
    }
}

function renderizarGraficosDashboardEscritorio(payloadPc) {
    var serieGuiasDia = homeDataSerie(payloadPc.series ? payloadPc.series.guias_dia : null, '01');
    var serieEstados = homeDataSerie(payloadPc.series ? payloadPc.series.estados_guia : null, 'SIN DATOS');
    var serieTop = homeDataSerie(payloadPc.series ? payloadPc.series.top_conductores : null, 'SIN DATOS');

    var canvasGuiasDia = document.getElementById('homeChartGuiasDia');
    var canvasEstados = document.getElementById('homeChartEstados');
    var canvasConductores = document.getElementById('homeChartConductores');
    if (!canvasGuiasDia || !canvasEstados || !canvasConductores || !window.Chart) {
        return;
    }

    if (HOME_DASHBOARD_STATE.chartGuiasDia) {
        HOME_DASHBOARD_STATE.chartGuiasDia.data.labels = serieGuiasDia.labels;
        HOME_DASHBOARD_STATE.chartGuiasDia.data.datasets[0].data = serieGuiasDia.values;
        HOME_DASHBOARD_STATE.chartGuiasDia.update();
    } else {
        HOME_DASHBOARD_STATE.chartGuiasDia = new Chart(canvasGuiasDia, {
            type: 'line',
            data: {
                labels: serieGuiasDia.labels,
                datasets: [{
                    label: 'Guias',
                    data: serieGuiasDia.values,
                    borderColor: '#245d86',
                    backgroundColor: 'rgba(36, 93, 134, 0.15)',
                    fill: true,
                    tension: 0.28,
                    pointRadius: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }

    if (HOME_DASHBOARD_STATE.chartEstados) {
        HOME_DASHBOARD_STATE.chartEstados.data.labels = serieEstados.labels;
        HOME_DASHBOARD_STATE.chartEstados.data.datasets[0].data = serieEstados.values;
        HOME_DASHBOARD_STATE.chartEstados.update();
    } else {
        HOME_DASHBOARD_STATE.chartEstados = new Chart(canvasEstados, {
            type: 'doughnut',
            data: {
                labels: serieEstados.labels,
                datasets: [{
                    data: serieEstados.values,
                    backgroundColor: ['#2f9460', '#2d79b2', '#e2b14d', '#bd4a4a', '#8799aa'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 10, font: { size: 11 } }
                    }
                }
            }
        });
    }

    if (HOME_DASHBOARD_STATE.chartConductores) {
        HOME_DASHBOARD_STATE.chartConductores.data.labels = serieTop.labels;
        HOME_DASHBOARD_STATE.chartConductores.data.datasets[0].data = serieTop.values;
        HOME_DASHBOARD_STATE.chartConductores.update();
    } else {
        HOME_DASHBOARD_STATE.chartConductores = new Chart(canvasConductores, {
            type: 'bar',
            data: {
                labels: serieTop.labels,
                datasets: [{
                    label: 'Guias',
                    data: serieTop.values,
                    backgroundColor: '#2d79b2'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } },
                    x: { ticks: { autoSkip: true, maxRotation: 0, minRotation: 0 } }
                }
            }
        });
    }
}

function renderizarGraficosDashboardMovilConductor(payloadMovil) {
    var serieEstados = homeDataSerie(payloadMovil.series ? payloadMovil.series.estados : null, 'SIN DATOS');
    var serieGuias = homeDataSerie(payloadMovil.series ? payloadMovil.series.guias : null, 'SIN DATOS');

    var canvasEstados = document.getElementById('homeMobileChartEstados');
    var canvasGuias = document.getElementById('homeMobileChartGuias');
    if (!canvasEstados || !canvasGuias || !window.Chart) {
        return;
    }

    if (HOME_DASHBOARD_STATE.chartMovilEstados) {
        HOME_DASHBOARD_STATE.chartMovilEstados.data.labels = serieEstados.labels;
        HOME_DASHBOARD_STATE.chartMovilEstados.data.datasets[0].data = serieEstados.values;
        HOME_DASHBOARD_STATE.chartMovilEstados.update();
    } else {
        HOME_DASHBOARD_STATE.chartMovilEstados = new Chart(canvasEstados, {
            type: 'doughnut',
            data: {
                labels: serieEstados.labels,
                datasets: [{
                    data: serieEstados.values,
                    backgroundColor: ['#e2b14d', '#2f9460', '#bd4a4a', '#2d79b2', '#8799aa'],
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

    if (HOME_DASHBOARD_STATE.chartMovilGuias) {
        HOME_DASHBOARD_STATE.chartMovilGuias.data.labels = serieGuias.labels;
        HOME_DASHBOARD_STATE.chartMovilGuias.data.datasets[0].data = serieGuias.values;
        HOME_DASHBOARD_STATE.chartMovilGuias.update();
    } else {
        HOME_DASHBOARD_STATE.chartMovilGuias = new Chart(canvasGuias, {
            type: 'bar',
            data: {
                labels: serieGuias.labels,
                datasets: [{
                    label: 'Pendientes',
                    data: serieGuias.values,
                    backgroundColor: '#2d79b2'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } },
                    x: { ticks: { autoSkip: true, maxRotation: 0, minRotation: 0 } }
                }
            }
        });
    }
}

function renderDashboardInicio(forzarDatos) {
    var panel = obtenerPanelInicio();
    if (!panel) {
        return;
    }

    if (!!forzarDatos || !dashboardInicioCacheVigente()) {
        panel.innerHTML = '<div class="home-loading"><i class="fas fa-spinner fa-spin"></i> Cargando indicadores...</div>';
    }

    cargarDatosDashboardInicio(!!forzarDatos, function(data, error) {
        if (!obtenerPanelInicio() || !$('#tabPanels .app-tab-panel[data-tab-id="' + TAB_HOME_ID + '"]').hasClass('active')) {
            return;
        }

        if (error || !data) {
            destruirDashboardInicio();
            if (window.console && typeof console.warn === 'function') {
                console.warn('Dashboard inicio sin datos:', error || 'respuesta vacia');
            }
            panel.innerHTML = '<div class="home-empty"></div>';
            return;
        }

        if (esEscritorio()) {
            destruirGraficoInicio('chartMovilEstados');
            destruirGraficoInicio('chartMovilGuias');
            destruirGraficoInicio('chartGuiasDia');
            destruirGraficoInicio('chartEstados');
            destruirGraficoInicio('chartConductores');

            var pc = data.pc || {};
            var k = pc.kpis || {};
            var condInicio = data.mobile_conductor || {};
            var condK = condInicio.kpis || {};
            var condInicioDesktopHtml = '';
            if (condInicio.habilitado) {
                condInicioDesktopHtml = '' +
                    '<section class="home-cond-inline">' +
                        '<h4 class="home-cond-title"><i class="fas fa-truck-ramp-box"></i> Indicadores del conductor: ' + (condInicio.conductor || 'Conductor') + '</h4>' +
                        '<div class="home-cond-grid">' +
                            '<article class="home-cond-kpi"><span>Guias pendientes</span><strong>' + homeNumero(condK.guias_pendientes) + '</strong></article>' +
                            '<article class="home-cond-kpi"><span>Remisiones pendientes</span><strong>' + homeNumero(condK.remisiones_pendientes) + '</strong></article>' +
                            '<article class="home-cond-kpi"><span>Entregadas hoy</span><strong>' + homeNumero(condK.entregadas_hoy) + '</strong></article>' +
                            '<article class="home-cond-kpi"><span>Incidencias hoy</span><strong>' + homeNumero(condK.incidencias_hoy) + '</strong></article>' +
                        '</div>' +
                    '</section>';
            }
            panel.innerHTML = '' +
                '<section class="home-dashboard">' +
                    '<div class="home-dash-head">' +
                        '<div>' +
                            '<h3 class="home-dash-title"><i class="fas fa-chart-line"></i> Indicadores de despachos e inventario</h3>' +
                            '<div class="home-dash-subtitle">Corte mensual operativo</div>' +
                        '</div>' +
                        '<div class="home-dash-subtitle">Hora: <strong id="homeDashHora">--:--:--</strong></div>' +
                    '</div>' +
                    '<div class="home-dash-kpi-grid home-dash-kpi-grid-ops">' +
                        '<article class="home-dash-kpi"><span class="home-dash-kpi-label">Guias hoy</span><strong class="home-dash-kpi-value">' + homeNumero(k.guias_hoy) + '</strong></article>' +
                        '<article class="home-dash-kpi"><span class="home-dash-kpi-label">Guias del mes</span><strong class="home-dash-kpi-value">' + homeNumero(k.guias_mes) + '</strong></article>' +
                        '<article class="home-dash-kpi"><span class="home-dash-kpi-label">Remisiones mes</span><strong class="home-dash-kpi-value">' + homeNumero(k.remisiones_mes) + '</strong></article>' +
                        '<article class="home-dash-kpi"><span class="home-dash-kpi-label">Conductores activos</span><strong class="home-dash-kpi-value">' + homeNumero(k.conductores_activos) + '</strong></article>' +
                        '<article class="home-dash-kpi"><span class="home-dash-kpi-label">Guias en ruta</span><strong class="home-dash-kpi-value">' + homeNumero(k.guias_en_ruta) + '</strong></article>' +
                        '<article class="home-dash-kpi"><span class="home-dash-kpi-label">Pendientes de entrega</span><strong class="home-dash-kpi-value">' + homeNumero(k.pendientes_remision) + '</strong></article>' +
                        '<article class="home-dash-kpi"><span class="home-dash-kpi-label">Cumplimiento mes</span><strong class="home-dash-kpi-value">' + homeNumero(k.cumplimiento_pct, 1) + '%</strong></article>' +
                        '<article class="home-dash-kpi"><span class="home-dash-kpi-label">Prom. remisiones/guia</span><strong class="home-dash-kpi-value">' + homeNumero(k.promedio_remision_por_guia, 1) + '</strong></article>' +
                    '</div>' +
                    '<div class="home-dash-chart-grid home-dash-chart-grid-ops">' +
                        '<article class="home-dash-card">' +
                            '<h4 class="home-dash-card-title">Guias por dia (mes actual)</h4>' +
                            '<canvas id="homeChartGuiasDia" aria-label="Grafico guias por dia"></canvas>' +
                        '</article>' +
                        '<article class="home-dash-card">' +
                            '<h4 class="home-dash-card-title">Estado de guias</h4>' +
                            '<canvas id="homeChartEstados" aria-label="Grafico estado de guias"></canvas>' +
                        '</article>' +
                        '<article class="home-dash-card home-dash-card-wide">' +
                            '<h4 class="home-dash-card-title">Conductores con mas guias</h4>' +
                            '<canvas id="homeChartConductores" aria-label="Grafico top conductores"></canvas>' +
                        '</article>' +
                    '</div>' +
                    condInicioDesktopHtml +
                    '<div class="home-dash-foot" id="homeDashMsg">' + (pc.message ? pc.message : '') + '</div>' +
                '</section>';

            actualizarHoraDashboardInicio();
            asegurarChartJsPrincipal(function() {
                renderizarGraficosDashboardEscritorio(pc);
            });
            return;
        }

        var movilConductor = data.mobile_conductor || {};
        if (!movilConductor.habilitado) {
            destruirDashboardInicio();
            panel.innerHTML = '<div class="home-empty"></div>';
            return;
        }

        destruirGraficoInicio('chartGuiasDia');
        destruirGraficoInicio('chartEstados');
        destruirGraficoInicio('chartConductores');

        var mk = movilConductor.kpis || {};
        panel.innerHTML = '' +
            '<section class="home-dashboard home-dashboard-mobile-cond">' +
                '<div class="home-dash-head home-dash-head-mobile">' +
                    '<div>' +
                        '<h3 class="home-dash-title"><i class="fas fa-truck-ramp-box"></i> DESPACHOS CONDUCTOR</h3>' +
                        '<div class="home-dash-subtitle">' + (movilConductor.conductor || 'Conductor') + '</div>' +
                        '<div class="home-dash-subtitle">' + (movilConductor.mensaje || '') + '</div>' +
                    '</div>' +
                '</div>' +
                '<div class="home-mobile-kpi-grid">' +
                    '<article class="home-mobile-kpi"><span>Guias pendientes</span><strong>' + homeNumero(mk.guias_pendientes) + '</strong></article>' +
                    '<article class="home-mobile-kpi"><span>Remisiones pendientes</span><strong>' + homeNumero(mk.remisiones_pendientes) + '</strong></article>' +
                    '<article class="home-mobile-kpi"><span>Entregadas hoy</span><strong>' + homeNumero(mk.entregadas_hoy) + '</strong></article>' +
                    '<article class="home-mobile-kpi"><span>Incidencias hoy</span><strong>' + homeNumero(mk.incidencias_hoy) + '</strong></article>' +
                '</div>' +
                '<div class="home-mobile-chart-grid">' +
                    '<article class="home-dash-card">' +
                        '<h4 class="home-dash-card-title">Estado de remisiones</h4>' +
                        '<canvas id="homeMobileChartEstados" aria-label="Grafico estado remisiones"></canvas>' +
                    '</article>' +
                    '<article class="home-dash-card">' +
                        '<h4 class="home-dash-card-title">Guias con pendientes</h4>' +
                        '<canvas id="homeMobileChartGuias" aria-label="Grafico guias pendientes"></canvas>' +
                    '</article>' +
                '</div>' +
            '</section>';

        asegurarChartJsPrincipal(function() {
            renderizarGraficosDashboardMovilConductor(movilConductor);
        });
    });
}

function aplicarModoMenu(colapsado) {
    document.body.classList.toggle('menu-collapsed', !!colapsado);
    var botonModo = document.getElementById('menuModeToggle');
    if (botonModo) {
        botonModo.setAttribute('aria-pressed', colapsado ? 'true' : 'false');
        botonModo.title = colapsado ? 'Cambiar a menu completo' : 'Cambiar a menu solo iconos';
    }
}

function alternarModoMenu(forzar) {
    var colapsado = typeof forzar === 'boolean' ? forzar : !document.body.classList.contains('menu-collapsed');
    aplicarModoMenu(colapsado);
    try {
        localStorage.setItem(STORAGE_MENU_MODO, colapsado ? 'iconos' : 'completo');
    } catch (e) {
    }
}

function alternarMenuUsuario(forzar) {
    var contenedor = document.getElementById('userMenu');
    var boton = document.getElementById('userMenuToggle');
    var desplegable = document.getElementById('userMenuDropdown');

    if (!contenedor || !boton || !desplegable) {
        return;
    }

    var abrir = typeof forzar === 'boolean' ? forzar : !contenedor.classList.contains('open');

    if (abrir) {
        desplegable.removeAttribute('inert');
        contenedor.classList.add('open');
        boton.setAttribute('aria-expanded', 'true');
        desplegable.setAttribute('aria-hidden', 'false');
        return;
    }

    if (desplegable.contains(document.activeElement)) {
        boton.focus();
    }
    contenedor.classList.remove('open');
    boton.setAttribute('aria-expanded', 'false');
    desplegable.setAttribute('aria-hidden', 'true');
    desplegable.setAttribute('inert', '');
}

function obtenerTituloMenu(menuId) {
    var enlace = document.getElementById(menuId);
    if (!enlace) {
        return menuId;
    }

    var spanTexto = enlace.querySelector('span');
    if (spanTexto && spanTexto.textContent.trim() !== '') {
        return spanTexto.textContent.trim();
    }

    return enlace.textContent.trim() || menuId;
}

function marcarMenuActivo(menuId) {
    $('#sidebar .menu-link, .user-menu-item').removeClass('active');
    if (menuId) {
        $('#' + menuId).addClass('active');
    }
}

function actualizarIndicadores() {
    var hora = new Date();
    var horaTexto = hora.toLocaleTimeString('es-CO', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });

    if ($('#indHora').length) {
        $('#indHora').text(horaTexto);
    }
    if ($('#indEstadoRed').length) {
        $('#indEstadoRed').text(navigator.onLine ? 'En linea' : 'Sin conexion');
    }

    var abiertas = Math.max(0, $('#tabBar .app-tab').length - 1);
    if ($('#indModulos').length) {
        $('#indModulos').text(abiertas);
    }

    if ($('#tabPanels .app-tab-panel[data-tab-id="' + TAB_HOME_ID + '"]').hasClass('active')) {
        actualizarHoraDashboardInicio();
    }
}

function crearPestana(tabId, titulo, menuId, cerrable) {
    var tabBar = document.getElementById('tabBar');
    var tabPanels = document.getElementById('tabPanels');

    if (!tabBar || !tabPanels) {
        return;
    }

    var tab = document.createElement('button');
    tab.type = 'button';
    tab.className = 'app-tab';
    tab.setAttribute('data-tab-id', tabId);
    tab.setAttribute('data-menu-id', menuId || '');
    tab.innerHTML = '<span class="app-tab-title">' + titulo + '</span>';

    if (cerrable) {
        var botonCerrar = document.createElement('button');
        botonCerrar.type = 'button';
        botonCerrar.className = 'app-tab-close';
        botonCerrar.setAttribute('data-close-tab', tabId);
        botonCerrar.setAttribute('aria-label', 'Cerrar pestana');
        botonCerrar.textContent = 'X';
        tab.appendChild(botonCerrar);
    }

    tabBar.appendChild(tab);

    var panel = document.createElement('article');
    panel.className = 'app-tab-panel';
    panel.setAttribute('data-tab-id', tabId);

    if (tabId === TAB_HOME_ID) {
        panel.classList.add('tab-home');
        panel.innerHTML = '<div class="home-empty"></div>';
    }

    tabPanels.appendChild(panel);
}

function activarPestana(tabId) {
    $('#tabBar .app-tab').removeClass('active');
    $('#tabPanels .app-tab-panel').removeClass('active');

    var tab = $('#tabBar .app-tab[data-tab-id="' + tabId + '"]');
    var panel = $('#tabPanels .app-tab-panel[data-tab-id="' + tabId + '"]');

    tab.addClass('active');
    panel.addClass('active');

    var menuId = tab.attr('data-menu-id') || '';
    marcarMenuActivo(menuId);
    actualizarIndicadores();

    if (tabId === TAB_HOME_ID) {
        renderDashboardInicio();
    }
}

function existePestana(tabId) {
    return $('#tabBar .app-tab[data-tab-id="' + tabId + '"]').length > 0;
}

function usuarioTienePermisoMenuLocal(menuId) {
    if (!menuId || menuId === 'salir') {
        return true;
    }

    if (!Array.isArray(window.MENU_IDS_PERMITIDOS) || window.MENU_IDS_PERMITIDOS.length === 0) {
        return true;
    }

    return window.MENU_IDS_PERMITIDOS.indexOf(menuId) >= 0;
}

function abrirPestana(menuId, url, titulo) {
    var tabId = 'tab_' + menuId;

    if (!existePestana(tabId)) {
        crearPestana(tabId, titulo, menuId, true);

        var panel = $('#tabPanels .app-tab-panel[data-tab-id="' + tabId + '"]');
        var separador = url.indexOf('?') >= 0 ? '&' : '?';
        var srcFinal = url + separador + '_tab=' + Date.now();

        panel.html(
            '<span class="tab-loading">Cargando modulo...</span>' +
            '<iframe class="tab-frame" src="' + srcFinal + '" loading="lazy" scrolling="yes"></iframe>'
        );

        panel.find('iframe').on('load', function() {
            panel.find('.tab-loading').remove();
            try {
                var doc = this.contentWindow && this.contentWindow.document;
                if (doc && doc.documentElement && doc.body) {
                    inyectarTemaModulo(doc);
                    doc.documentElement.style.margin = '0';
                    doc.documentElement.style.padding = '0';
                    doc.body.style.margin = '0';
                    doc.body.style.padding = '0';
                    doc.documentElement.style.height = '100%';
                    doc.body.style.height = '100%';
                    doc.documentElement.style.minHeight = '100%';
                    doc.documentElement.style.overflowX = 'hidden';
                    doc.documentElement.style.overflowY = 'auto';
                    doc.body.style.overflowX = 'hidden';
                    doc.body.style.overflowY = 'auto';
                    doc.documentElement.style.backgroundColor = '#ffffff';
                    doc.body.style.backgroundColor = '#ffffff';
                    doc.body.style.minHeight = '100%';
                }
            } catch (e) {
            }
        });
    }

    activarPestana(tabId);
}

function cerrarPestana(tabId) {
    if (tabId === TAB_HOME_ID) {
        return;
    }

    var tabActual = $('#tabBar .app-tab.active').attr('data-tab-id');
    var tabs = $('#tabBar .app-tab');
    var indexCerrar = tabs.index($('#tabBar .app-tab[data-tab-id="' + tabId + '"]'));

    $('#tabBar .app-tab[data-tab-id="' + tabId + '"]').remove();
    $('#tabPanels .app-tab-panel[data-tab-id="' + tabId + '"]').remove();

    if (tabActual === tabId) {
        var tabsRestantes = $('#tabBar .app-tab');
        if (tabsRestantes.length === 0) {
            crearPestana(TAB_HOME_ID, 'Inicio', '', false);
            activarPestana(TAB_HOME_ID);
        } else {
            var indiceSiguiente = Math.max(0, indexCerrar - 1);
            var siguienteId = $(tabsRestantes[indiceSiguiente]).attr('data-tab-id');
            activarPestana(siguienteId || TAB_HOME_ID);
        }
    }

    actualizarIndicadores();
}

function inicializarPestanas() {
    if (!existePestana(TAB_HOME_ID)) {
        crearPestana(TAB_HOME_ID, 'Inicio', '', false);
    }

    activarPestana(TAB_HOME_ID);
    renderDashboardInicio();

    $('#tabBar').on('click', '.app-tab', function(e) {
        if ($(e.target).closest('.app-tab-close').length) {
            return;
        }

        var tabId = $(this).attr('data-tab-id');
        activarPestana(tabId);
    });

    $('#tabBar').on('click', '.app-tab-close', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var tabId = $(this).attr('data-close-tab');
        cerrarPestana(tabId);
    });
}

function cargarReporte(url, mensaje, confirmacion, menuId) {
    if (!usuarioTienePermisoMenuLocal(menuId)) {
        Swal.fire({
            icon: 'error',
            title: 'Acceso denegado',
            text: 'No tienes permiso para ingresar a este menu.'
        });
        alternarMenuUsuario(false);
        return;
    }

    var titulo = obtenerTituloMenu(menuId);

    function comportamientoPostSeleccion() {
        if (esMovil()) {
            cerrarMenu();
        } else {
            // En escritorio, al seleccionar un modulo se encoge a modo iconos.
            alternarModoMenu(true);
        }
    }

    if (confirmacion) {
        Swal.fire({
            title: mensaje,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#1d5278',
            cancelButtonColor: '#c73a2d',
            confirmButtonText: 'Si, continuar',
            cancelButtonText: 'No, cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                abrirPestana(menuId, url, titulo);
                comportamientoPostSeleccion();
            }
        });
    } else {
        abrirPestana(menuId, url, titulo);
        comportamientoPostSeleccion();
    }

    alternarMenuUsuario(false);
}

function registrarAccionMenu(id, url, mensaje, confirmacion) {
    $('#' + id).on('click', function(e) {
        e.preventDefault();
        cargarReporte(url, mensaje, confirmacion, id);
    });
}

$(document).ready(function() {
    iniciarBloqueoNavegacionAtras();

    if (window.innerWidth >= 1200) {
        mostrar();
    }

    try {
        var modoGuardado = localStorage.getItem(STORAGE_MENU_MODO);
        if (modoGuardado === 'iconos') {
            aplicarModoMenu(true);
        }
    } catch (e) {
    }

    inicializarPestanas();

    $('#sidebar .menu-link').each(function() {
        var texto = $(this).find('span').text().trim();
        if (texto !== '') {
            $(this).attr('title', texto);
        }
    });

    $('#menuModeToggle').on('click', function(e) {
        e.preventDefault();
        alternarModoMenu();
    });

    $('#userMenuToggle').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        alternarMenuUsuario();
    });

    var dropdownUsuario = document.getElementById('userMenuDropdown');
    if (dropdownUsuario && dropdownUsuario.getAttribute('aria-hidden') !== 'false') {
        dropdownUsuario.setAttribute('inert', '');
    }

    $(document).on('click', function(e) {
        var menuUsuario = document.getElementById('userMenu');
        if (menuUsuario && !menuUsuario.contains(e.target)) {
            alternarMenuUsuario(false);
        }
    });

    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            ocultar();
            alternarMenuUsuario(false);
        }
    });

    $(window).on('resize', function() {
        if (esMovil()) {
            aplicarModoMenu(false);
        }
        renderDashboardInicio();
    });

    window.addEventListener('online', actualizarIndicadores);
    window.addEventListener('offline', actualizarIndicadores);
    actualizarIndicadores();
    setInterval(actualizarIndicadores, 1000);

    $('#salir').on('click', function(e) {
        e.preventDefault();
        alternarMenuUsuario(false);
        Swal.fire({
            title: 'Desea salir del sistema?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#1d5278',
            cancelButtonColor: '#c73a2d',
            confirmButtonText: 'Si, salir',
            cancelButtonText: 'No, cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                location.href = 'index.php';
            }
        });
    });

    registrarAccionMenu('listasmovcexis', 'ListaSinMovConExis.php', 'Cargando', false);
    registrarAccionMenu('listasmovsexis', 'ListaSinMovSinExis.php', 'Cargando', false);
    registrarAccionMenu('listacmovsexis', 'ListaConMovSinExis.php', 'Cargando', false);
    registrarAccionMenu('listaclasificac', 'ListaClasificacionCosto.php', 'Este reporte puede tardar un poco. Desea continuar?', true);
    registrarAccionMenu('listaabcexistenciainventario', 'ListaABCExistenciaInventario.php', 'Este reporte puede tardar un poco. Desea continuar?', true);
    registrarAccionMenu('comparativo', 'comparativo.php', 'Este reporte puede tardar un poco. Desea continuar?', true);
    registrarAccionMenu('log', 'Log_maximos_minimos.php', 'Este reporte puede tardar un poco. Desea continuar?', true);
    registrarAccionMenu('informeped', 'informe_pedido_mensual.php', 'Este reporte puede tardar un poco. Desea continuar?', true);
    registrarAccionMenu('configuracionvencimientoxgrupos', 'ConfiguracionVencimientoPorProductos.php', 'Cargando', false);
    registrarAccionMenu('listaconexiones', 'conexiones.php', 'Cargando', false);
    registrarAccionMenu('listaconfiguraciones', 'ListaConfiguraciones.php', 'Cargando', false);
    registrarAccionMenu('permisosmenu', 'permisos_menu.php', 'Cargando', false);
    registrarAccionMenu('listadoproductosclasificados', 'listado_productos_clasificados.php', 'Cargando', false);
    registrarAccionMenu('listarotacion', 'rotacion_inventario.php', 'Este reporte puede tardar un poco. Desea continuar?', true);
    registrarAccionMenu('listaventas', 'abc_precio.php', 'Este reporte puede tardar un poco. Desea continuar?', true);
    registrarAccionMenu('listaestados', 'estados_pedidos.php', 'Este reporte puede tardar un poco. Desea continuar?', true);
    registrarAccionMenu('listaabcventainventario', 'ListaVentaInventario.php', 'Este reporte puede tardar un poco. Desea continuar?', true);
    registrarAccionMenu('listaabccostorepuestos', 'ListaCostoRepuestos.php', 'Este reporte puede tardar un poco. Desea continuar?', true);
    registrarAccionMenu('listaabccostomotos', 'ListaCostoMotos.php', 'Este reporte puede tardar un poco. Desea continuar?', true);
    registrarAccionMenu('listaabcexistenciarepuestos', 'ListaABCExistenciaRepuestos.php', 'Este reporte puede tardar un poco. Desea continuar?', true);
    registrarAccionMenu('listaabcexistenciamotos', 'ListaABCExistenciaMotos.php', 'Este reporte puede tardar un poco. Desea continuar?', true);
    registrarAccionMenu('listaabcventarepuestos', 'ListaVentaRepuestos.php', 'Este reporte puede tardar un poco. Desea continuar?', true);
    registrarAccionMenu('listaabcventamotos', 'ListaVentaMotos.php', 'Este reporte puede tardar un poco. Desea continuar?', true);
    registrarAccionMenu('backorder', 'backorder.php', 'Este reporte puede tardar un poco. Desea continuar?', true);
    registrarAccionMenu('guiasdespachos', 'guias_despachos.php', 'Cargando', false);
    registrarAccionMenu('centrokpi', 'centro_kpi.php', 'Cargando', false);
    registrarAccionMenu('despachosconductor', 'despachos_conductor.php', 'Cargando', false);
    registrarAccionMenu('rutaconductor', 'ruta_conductor_mapa.php', 'Cargando', false);
    registrarAccionMenu('retirados', 'retirados.php', 'Cargando', false);
    registrarAccionMenu('pedidosgeneradosauto', 'PedidosGeneradosAutomaticamente.php', 'Este reporte puede tardar un poco. Desea continuar?', true);
    registrarAccionMenu('recalcularnumericas', 'recalcularnumericas.php', 'Este reporte puede tardar un poco. Desea continuar?', true);
    registrarAccionMenu('listaconfiguracionlineas', 'ListaConfiguracionLineas.php', 'Este reporte puede tardar un poco. Desea continuar?', true);
});
