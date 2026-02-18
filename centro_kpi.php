<?php
require('conecta.php');
if (empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
$fechaDesdeDefault = date('Y-m-01\T00:00');
$fechaHastaDefault = date('Y-m-d\T23:59');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centro KPI</title>
    <?php includeAssets(); ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.8.2/dist/jspdf.plugin.autotable.min.js"></script>
    <style>
        .kpi-page { padding: 0.95rem 0.55rem 1.1rem; background: #fff; }
        .kpi-shell { max-width: 1580px; margin: 0 auto; }
        .kpi-card { border: 1px solid #d8e5f0; border-radius: 16px; background: #fff; box-shadow: 0 10px 20px rgba(19, 51, 77, 0.08); overflow: hidden; }
        .kpi-head { padding: 0.95rem 1rem; border-bottom: 1px solid #e2edf7; display: flex; gap: 0.75rem; align-items: center; justify-content: space-between; flex-wrap: wrap; background: linear-gradient(180deg, #f8fbff 0%, #f2f8ff 100%); }
        .kpi-title { margin: 0; font-size: 1.16rem; font-weight: 800; color: #103451; display: inline-flex; align-items: center; gap: 0.45rem; }
        .kpi-sub { color: #3d5f78; font-size: 0.83rem; margin-top: 0.2rem; }
        .kpi-actions { display: flex; gap: 0.45rem; flex-wrap: wrap; }
        .kpi-filters { padding: 0.85rem 1rem 0.45rem; border-bottom: 1px solid #e2edf7; }
        .kpi-filters .form-label { font-size: 0.78rem; margin-bottom: 0.2rem; color: #35566f; font-weight: 600; }
        .kpi-tabs { display: flex; gap: 0.35rem; flex-wrap: wrap; padding: 0.65rem 1rem 0.5rem; border-bottom: 1px solid #dce8f3; background: #f6fbff; }
        .kpi-tab-btn { border: 1px solid #cde0ef; background: #fff; color: #1b4766; border-radius: 999px; font-size: 0.79rem; font-weight: 700; padding: 0.38rem 0.72rem; }
        .kpi-tab-btn.active { background: #1d5e88; border-color: #1d5e88; color: #fff; }
        .kpi-body { padding: 0.7rem 1rem 1rem; }
        .kpi-panel { display: none; }
        .kpi-panel.active { display: block; }
        .kpi-cards-grid { display: grid; gap: 0.55rem; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); margin-bottom: 0.8rem; }
        .kpi-mini { border: 1px solid #d5e6f4; border-radius: 10px; padding: 0.58rem 0.62rem; background: #fff; }
        .kpi-mini span { display: block; font-size: 0.73rem; text-transform: uppercase; color: #5b7387; }
        .kpi-mini strong { display: block; font-size: 1.1rem; color: #123f60; line-height: 1.12; margin-top: 0.16rem; }
        .kpi-mini small { display: block; color: #6d8191; font-size: 0.7rem; margin-top: 0.12rem; }
        .kpi-grid { display: grid; gap: 0.7rem; grid-template-columns: 1.35fr 1fr; margin-bottom: 0.7rem; }
        .kpi-block { border: 1px solid #d7e4ef; border-radius: 12px; background: #fff; padding: 0.6rem; }
        .kpi-block h6 { margin: 0 0 0.5rem; color: #154666; font-weight: 800; font-size: 0.9rem; }
        .kpi-chart-wrap { height: 290px; }
        .kpi-table-wrap { border: 1px solid #d7e4ef; border-radius: 12px; overflow: auto; max-height: 320px; }
        .kpi-table-wrap table { margin: 0; font-size: 0.82rem; }
        .kpi-table-wrap thead th { position: sticky; top: 0; z-index: 1; background: #edf4fb; font-size: 0.74rem; text-transform: uppercase; }
        .kpi-note { margin-top: 0.55rem; font-size: 0.78rem; color: #4d667a; }
        .kpi-loading { padding: 1rem 0.2rem; text-align: center; color: #446379; }
        .kpi-shortcuts { margin-top: 0.65rem; border: 1px dashed #ccddec; border-radius: 8px; padding: 0.4rem 0.55rem; font-size: 0.75rem; color: #4f6a7d; background: #f9fcff; }
        @media (max-width: 1200px) { .kpi-grid { grid-template-columns: 1fr; } .kpi-chart-wrap { height: 260px; } }
        @media (max-width: 768px) { .kpi-tab-btn { font-size: 0.74rem; } }
    </style>
</head>
<body class="bodyc kpi-page">
<section class="kpi-shell">
    <div class="kpi-card">
        <header class="kpi-head">
            <div>
                <h2 class="kpi-title"><i class="fas fa-chart-line"></i> CENTRO KPI OPERATIVO</h2>
                <div class="kpi-sub">Tablero profesional de despachos: tiempo real, trazabilidad, ruteo y analitica historica</div>
            </div>
            <div class="kpi-actions">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnExportExcel"><i class="fas fa-file-excel"></i> Excel</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnExportPdf"><i class="fas fa-file-pdf"></i> PDF</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnKpiActualizar"><i class="fas fa-rotate"></i> Actualizar</button>
            </div>
        </header>

        <section class="kpi-filters">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label" for="kpi_fecha_desde">Fecha desde</label>
                    <input type="datetime-local" class="form-control" id="kpi_fecha_desde" value="<?php echo $fechaDesdeDefault; ?>">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label" for="kpi_fecha_hasta">Fecha hasta</label>
                    <input type="datetime-local" class="form-control" id="kpi_fecha_hasta" value="<?php echo $fechaHastaDefault; ?>">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label" for="kpi_busqueda">Busqueda (auditoria)</label>
                    <input type="text" class="form-control" id="kpi_busqueda" maxlength="60" placeholder="Guia, usuario, estado o remision">
                </div>
                <div class="col-12 col-md-3 d-flex gap-2 flex-wrap justify-content-md-end">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnRangoHoy">Hoy</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnRango7">7 dias</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnRangoMes">Mes</button>
                </div>
            </div>
        </section>

        <nav class="kpi-tabs" aria-label="Submenu KPI">
            <button type="button" class="kpi-tab-btn active" data-sec="tiempo_real"><i class="fas fa-satellite-dish"></i> 1. Tiempo real</button>
            <button type="button" class="kpi-tab-btn" data-sec="tiempos_estados"><i class="fas fa-stopwatch"></i> 2. Tiempos estados</button>
            <button type="button" class="kpi-tab-btn" data-sec="entregas"><i class="fas fa-truck"></i> 3. Entregadas vs despachadas</button>
            <button type="button" class="kpi-tab-btn" data-sec="ruteo"><i class="fas fa-route"></i> 4. Ruteo inteligente</button>
            <button type="button" class="kpi-tab-btn" data-sec="auditoria"><i class="fas fa-fingerprint"></i> 5. Auditoria</button>
            <button type="button" class="kpi-tab-btn" data-sec="historica"><i class="fas fa-chart-area"></i> 6. Analitica historica</button>
        </nav>

        <section class="kpi-body" id="kpiBody">
            <?php foreach (array('tiempo_real','tiempos_estados','entregas','ruteo','auditoria','historica') as $sec): ?>
            <article class="kpi-panel<?php echo $sec === 'tiempo_real' ? ' active' : ''; ?>" data-sec="<?php echo $sec; ?>">
                <div class="kpi-cards-grid" id="cards-<?php echo $sec; ?>"></div>
                <div class="kpi-grid">
                    <section class="kpi-block">
                        <h6 id="chart-title-<?php echo $sec; ?>">Indicadores</h6>
                        <div class="kpi-chart-wrap"><canvas id="chart-<?php echo $sec; ?>"></canvas></div>
                    </section>
                    <section class="kpi-block">
                        <h6 id="table2-title-<?php echo $sec; ?>">Detalle</h6>
                        <div class="kpi-table-wrap" id="table2-<?php echo $sec; ?>"></div>
                    </section>
                </div>
                <section class="kpi-block">
                    <h6 id="table1-title-<?php echo $sec; ?>">Listado</h6>
                    <div class="kpi-table-wrap" id="table1-<?php echo $sec; ?>"></div>
                </section>
                <div class="kpi-note" id="notes-<?php echo $sec; ?>"></div>
            </article>
            <?php endforeach; ?>

            <div class="kpi-shortcuts">
                Atajos PC: <strong>Alt+1..Alt+6</strong> cambia submenus, <strong>Alt+R</strong> actualiza, <strong>Alt+E</strong> exporta Excel, <strong>Alt+P</strong> exporta PDF.
            </div>
        </section>
    </div>
</section>

<script>
(function(){
    const KEY_STORE = 'centro_kpi_filtros_v1';
    const app = { active: 'tiempo_real', charts: {}, cache: {} };

    function esc(v){ return String(v === null || v === undefined ? '' : v).replace(/[&<>\"']/g, function(s){ return ({'&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;',"'":'&#39;'}[s]); }); }
    function n(v, d){ const x = Number(v); if(!isFinite(x)){ return '0'; } if(typeof d === 'number'){ return x.toLocaleString('es-CO', { minimumFractionDigits: d, maximumFractionDigits: d }); } return Math.round(x).toLocaleString('es-CO'); }

    function guardarFiltros(){
        try {
            localStorage.setItem(KEY_STORE, JSON.stringify({
                fecha_desde: $('#kpi_fecha_desde').val() || '',
                fecha_hasta: $('#kpi_fecha_hasta').val() || '',
                busqueda: ($('#kpi_busqueda').val() || '').trim()
            }));
        } catch(e){}
    }
    function cargarFiltros(){
        try {
            const raw = localStorage.getItem(KEY_STORE);
            if(!raw){ return; }
            const o = JSON.parse(raw);
            if(o.fecha_desde){ $('#kpi_fecha_desde').val(o.fecha_desde); }
            if(o.fecha_hasta){ $('#kpi_fecha_hasta').val(o.fecha_hasta); }
            if(o.busqueda){ $('#kpi_busqueda').val(o.busqueda); }
        } catch(e){}
    }

    function setRango(tipo){
        const now = new Date();
        const pad = x => String(x).padStart(2, '0');
        const toInput = d => `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
        let desde = new Date(now.getTime());
        if(tipo === 'hoy'){
            desde.setHours(0,0,0,0);
        } else if(tipo === '7'){
            desde.setDate(desde.getDate() - 6);
            desde.setHours(0,0,0,0);
        } else {
            desde = new Date(now.getFullYear(), now.getMonth(), 1, 0, 0, 0, 0);
        }
        const hasta = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 23, 59, 0, 0);
        $('#kpi_fecha_desde').val(toInput(desde));
        $('#kpi_fecha_hasta').val(toInput(hasta));
        guardarFiltros();
    }

    function cardHtml(cards){
        if(!Array.isArray(cards) || !cards.length){ return '<div class="kpi-loading">Sin indicadores en este rango.</div>'; }
        return cards.map(c => `<article class="kpi-mini"><span>${esc(c.label || '')}</span><strong>${esc(n(c.value, (String(c.label||'').indexOf('%')>=0?1:undefined)))}</strong><small>${esc(c.hint || '')}</small></article>`).join('');
    }

    function tableHtml(rows){
        if(!Array.isArray(rows) || !rows.length){ return '<div class="kpi-loading">Sin datos</div>'; }
        const cols = Object.keys(rows[0]);
        let html = '<table class="table table-sm table-hover align-middle kpi-export-table"><thead><tr>';
        cols.forEach(c => html += `<th>${esc(c)}</th>`);
        html += '</tr></thead><tbody>';
        rows.forEach(r => {
            html += '<tr>';
            cols.forEach(c => html += `<td>${esc(r[c])}</td>`);
            html += '</tr>';
        });
        html += '</tbody></table>';
        return html;
    }

    function renderChart(sec, chartData){
        const canvas = document.getElementById('chart-' + sec);
        if(!canvas){ return; }
        if(app.charts[sec]){
            try { app.charts[sec].destroy(); } catch(e){}
            app.charts[sec] = null;
        }
        if(!chartData || !Array.isArray(chartData.labels) || !Array.isArray(chartData.series) || !chartData.series.length){
            return;
        }

        const datasets = chartData.series.map((s, idx) => ({
            label: s.label || ('Serie ' + (idx + 1)),
            data: Array.isArray(s.data) ? s.data : [],
            backgroundColor: (s.color || '#2f86bf') + (chartData.series.length > 1 ? '55' : '33'),
            borderColor: s.color || '#2f86bf',
            borderWidth: 2,
            fill: chartData.series.length === 1,
            tension: 0.25,
            pointRadius: 2
        }));

        app.charts[sec] = new Chart(canvas, {
            type: chartData.series.length > 1 ? 'line' : 'bar',
            data: { labels: chartData.labels, datasets: datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: true, position: 'bottom' } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    function renderSection(sec, data){
        const d = data || {};
        $('#cards-' + sec).html(cardHtml(d.cards || []));

        $('#chart-title-' + sec).text((d.chart && d.chart.title) ? d.chart.title : 'Indicadores');
        renderChart(sec, d.chart || null);

        const t1 = (d.tables && d.tables.principal) ? d.tables.principal : { title: 'Listado', rows: [] };
        $('#table1-title-' + sec).text(t1.title || 'Listado');
        $('#table1-' + sec).html(tableHtml(t1.rows || []));

        const t2 = (d.tables && d.tables.secundaria) ? d.tables.secundaria : { title: 'Detalle', rows: [] };
        $('#table2-title-' + sec).text(t2.title || 'Detalle');

        const secRows = (d.tables && d.tables.terciaria && Array.isArray(d.tables.terciaria.rows) && d.tables.terciaria.rows.length)
            ? d.tables.terciaria.rows
            : (t2.rows || []);
        const secTitle = (d.tables && d.tables.terciaria && d.tables.terciaria.title)
            ? d.tables.terciaria.title
            : (t2.title || 'Detalle');
        $('#table2-title-' + sec).text(secTitle);
        $('#table2-' + sec).html(tableHtml(secRows));

        let notes = '';
        if(Array.isArray(d.notes) && d.notes.length){
            notes = d.notes.map(x => '• ' + esc(x)).join('<br>');
        }
        if(d.tables && d.tables.cuarta && Array.isArray(d.tables.cuarta.rows) && d.tables.cuarta.rows.length){
            notes += (notes ? '<br>' : '') + '<strong>' + esc(d.tables.cuarta.title || '') + ':</strong> ' + esc(d.tables.cuarta.rows.slice(0, 3).map(r => Object.values(r).join(' | ')).join(' / '));
        }
        $('#notes-' + sec).html(notes);
    }

    function cargarSec(sec, forzar){
        guardarFiltros();
        app.active = sec;
        $('.kpi-tab-btn').removeClass('active');
        $('.kpi-tab-btn[data-sec="' + sec + '"]').addClass('active');
        $('.kpi-panel').removeClass('active');
        $('.kpi-panel[data-sec="' + sec + '"]').addClass('active');

        if(!forzar && app.cache[sec]){
            renderSection(sec, app.cache[sec]);
            return;
        }

        $('#cards-' + sec).html('<div class="kpi-loading"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>');

        $.ajax({
            url: 'centro_kpi_ajax.php',
            type: 'POST',
            dataType: 'json',
            cache: false,
            data: {
                action: 'cargar_kpi',
                seccion: sec,
                fecha_desde: $('#kpi_fecha_desde').val() || '',
                fecha_hasta: $('#kpi_fecha_hasta').val() || '',
                busqueda: ($('#kpi_busqueda').val() || '').trim()
            },
            success: function(resp){
                if(resp && resp.ok && resp.data){
                    app.cache[sec] = resp.data;
                    renderSection(sec, resp.data);
                    return;
                }
                Swal.fire({ icon: 'warning', title: 'KPI', text: (resp && resp.message) ? resp.message : 'No se pudo cargar la seccion.' });
            },
            error: function(xhr){
                const txt = (xhr && xhr.responseText) ? xhr.responseText : 'Error de comunicacion.';
                Swal.fire({ icon: 'error', title: 'KPI', text: txt.substring(0, 250) });
            }
        });
    }

    function exportExcel(){
        const panel = $('.kpi-panel.active');
        const table = panel.find('.kpi-export-table').first();
        if(!table.length || typeof XLSX === 'undefined'){
            Swal.fire({ icon: 'info', title: 'Exportar', text: 'No hay datos para exportar.' });
            return;
        }
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.table_to_sheet(table[0]);
        XLSX.utils.book_append_sheet(wb, ws, 'KPI');
        XLSX.writeFile(wb, 'kpi_' + app.active + '.xlsx');
    }

    function exportPdf(){
        const panel = $('.kpi-panel.active');
        const table = panel.find('.kpi-export-table').first();
        if(!table.length || !window.jspdf || typeof window.jspdf.jsPDF !== 'function'){
            Swal.fire({ icon: 'info', title: 'Exportar', text: 'No hay datos para exportar.' });
            return;
        }
        const doc = new window.jspdf.jsPDF({ orientation: 'landscape', unit: 'pt', format: 'a4' });
        doc.setFontSize(11);
        doc.text('Centro KPI - ' + app.active, 40, 30);
        doc.autoTable({ html: table[0], startY: 42, theme: 'grid', styles: { fontSize: 7 } });
        doc.save('kpi_' + app.active + '.pdf');
    }

    function initShortcuts(){
        $(document).on('keydown', function(e){
            if(!e.altKey){ return; }
            const key = (e.key || '').toLowerCase();
            const order = ['tiempo_real','tiempos_estados','entregas','ruteo','auditoria','historica'];
            if(['1','2','3','4','5','6'].indexOf(key) >= 0){
                e.preventDefault();
                cargarSec(order[parseInt(key, 10)-1], false);
                return;
            }
            if(key === 'r'){ e.preventDefault(); app.cache[app.active] = null; cargarSec(app.active, true); return; }
            if(key === 'e'){ e.preventDefault(); exportExcel(); return; }
            if(key === 'p'){ e.preventDefault(); exportPdf(); return; }
        });
    }

    $(document).ready(function(){
        cargarFiltros();
        initShortcuts();
        cargarSec('tiempo_real', true);

        $('.kpi-tab-btn').on('click', function(){
            const sec = $(this).data('sec');
            if(!sec){ return; }
            cargarSec(sec, false);
        });

        $('#btnKpiActualizar').on('click', function(){
            app.cache[app.active] = null;
            cargarSec(app.active, true);
        });

        $('#btnRangoHoy').on('click', function(){ setRango('hoy'); app.cache = {}; cargarSec(app.active, true); });
        $('#btnRango7').on('click', function(){ setRango('7'); app.cache = {}; cargarSec(app.active, true); });
        $('#btnRangoMes').on('click', function(){ setRango('mes'); app.cache = {}; cargarSec(app.active, true); });

        $('#kpi_fecha_desde, #kpi_fecha_hasta').on('change', function(){ guardarFiltros(); app.cache = {}; });
        $('#kpi_busqueda').on('change blur', function(){ guardarFiltros(); app.cache['auditoria'] = null; });

        $('#btnExportExcel').on('click', exportExcel);
        $('#btnExportPdf').on('click', exportPdf);
    });
})();
</script>
</body>
</html>
