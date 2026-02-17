<?php
require('conecta.php');

$usuarioActual = isset($_SESSION['user']) ? $_SESSION['user'] : '';
$prefijos = array();
$conductores = array();

$vsqlPrefijos = "SELECT CODPREFIJO, DESPREFIJO FROM PREFIJO ORDER BY CODPREFIJO";
if ($vcPrefijos = $conect_bd_actual->consulta($vsqlPrefijos)) {
    while ($vrPrefijo = ibase_fetch_object($vcPrefijos)) {
        $prefijos[] = $vrPrefijo;
    }
}

$vsqlConductores = "SELECT TERID, NOMBRE, NIT FROM TERCEROS WHERE COALESCE(CONDUCTOR, 'N') = 'S' AND COALESCE(INACTIVO, 'N') <> 'S' ORDER BY NOMBRE";
if ($vcConductores = $conect_bd_actual->consulta($vsqlConductores)) {
    while ($vrConductor = ibase_fetch_object($vcConductores)) {
        $conductores[] = $vrConductor;
    }
}

$fechaDesdeDefault = date('Y-m-01\\T00:00');
$fechaHastaDefault = date('Y-m-d\\TH:i');
?>
<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GUIAS (Despachos)</title>
    <?php includeAssets(); ?>
    <style>
        .guias-page {
            padding: 0.95rem 0.55rem 1.1rem;
            background: #ffffff;
            font-size: 0.94rem;
        }
        .guias-shell {
            max-width: 1500px;
            margin: 0 auto;
        }
        .guias-card {
            border: 1px solid #d8e5f0;
            border-radius: 16px;
            background: #ffffff;
            box-shadow: 0 10px 20px rgba(19, 51, 77, 0.08);
            overflow: hidden;
        }
        .guias-head {
            padding: 0.95rem 1rem;
            border-bottom: 1px solid #e2edf7;
            display: flex;
            gap: 0.75rem;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            background: linear-gradient(180deg, #f8fbff 0%, #f2f8ff 100%);
        }
        .guias-title {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 800;
            color: #103451;
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
        }
        .guias-head-actions {
            display: flex;
            gap: 0.55rem;
            flex-wrap: wrap;
        }
        .guias-filters {
            padding: 0.9rem 1rem 0.4rem;
        }
        .guias-table-wrap {
            padding: 0.35rem 1rem 1rem;
        }
        .guias-table-container {
            border: 1px solid #d6e5f1;
            border-radius: 12px;
            overflow: auto;
            max-height: calc(100vh - 290px);
        }
        #tablaGuias {
            margin: 0;
            min-width: 1100px;
        }
        #tablaGuias thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: #1c4f73;
            color: #fff;
            font-weight: 700;
            font-size: 0.84rem;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            border-bottom: 0;
        }
        #tablaGuias tbody td {
            vertical-align: middle;
            font-size: 0.84rem;
        }
        .num-guia {
            font-weight: 700;
            color: #16466a;
        }
        .badge-estado {
            padding: 0.34rem 0.6rem;
            border-radius: 30px;
            font-size: 0.74rem;
            font-weight: 700;
            display: inline-block;
        }
        .badge-estado.ali {
            color: #674f00;
            background: #fff2c7;
        }
        .badge-estado.rut {
            color: #0d4f66;
            background: #d5f0ff;
        }
        .badge-estado.fin {
            color: #0b5a31;
            background: #d6f6e6;
        }
        .badge-estado.nd {
            color: #4d5661;
            background: #e9edf3;
        }
        .guias-empty {
            padding: 1.6rem;
            text-align: center;
            color: #4e5f70;
        }
        .acciones-guia {
            display: inline-flex;
            gap: 0.35rem;
        }
        .modal-title i {
            margin-right: 0.35rem;
        }
        .historial-wrap {
            max-height: 320px;
            overflow: auto;
            border: 1px solid #d9e7f4;
            border-radius: 10px;
        }
        .historial-wrap table {
            margin: 0;
        }
        .estado-quick {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-weight: 600;
        }
        .remisiones-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.85rem;
        }
        .remisiones-box {
            border: 1px solid #d9e8f4;
            border-radius: 10px;
            padding: 0.7rem;
            background: #fff;
        }
        .remisiones-box h6 {
            margin: 0 0 0.55rem;
            font-size: 0.92rem;
            font-weight: 700;
            color: #123d5e;
        }
        .tabla-rem-wrap {
            border: 1px solid #d9e7f3;
            border-radius: 8px;
            max-height: 230px;
            overflow: auto;
        }
        .tabla-rem-wrap table {
            margin: 0;
            font-size: 0.83rem;
        }
        .tabla-rem-wrap thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            font-size: 0.76rem;
            text-transform: uppercase;
            background: #edf4fb;
        }
        @media (max-width: 992px) {
            .guias-table-container {
                max-height: calc(100vh - 250px);
            }
            .guias-head,
            .guias-filters,
            .guias-table-wrap {
                padding-left: 0.7rem;
                padding-right: 0.7rem;
            }
        }
    </style>
</head>
<body class="bodyc guias-page">
<section class="guias-shell">
    <div class="guias-card">
        <header class="guias-head">
            <h2 class="guias-title"><i class="fas fa-truck-fast"></i> GUIAS (Despachos)</h2>
            <div class="guias-head-actions">
                <button type="button" class="btn btn-outline-primary" id="btnActualizarGuias">
                    <i class="fas fa-rotate"></i> Actualizar
                </button>
                <button type="button" class="btn btn-primary" id="btnNuevaGuia">
                    <i class="fas fa-plus"></i> Nueva guia
                </button>
            </div>
        </header>

        <section class="guias-filters">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label mb-1" for="f_fecha_desde">Fecha desde</label>
                    <input type="datetime-local" class="form-control" id="f_fecha_desde" value="<?php echo $fechaDesdeDefault; ?>">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label mb-1" for="f_fecha_hasta">Fecha hasta</label>
                    <input type="datetime-local" class="form-control" id="f_fecha_hasta" value="<?php echo $fechaHastaDefault; ?>">
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label mb-1" for="f_estado">Estado</label>
                    <select id="f_estado" class="form-select">
                        <option value="">Todos</option>
                        <option value="EN_ALISTAMIENTO">EN ALISTAMIENTO</option>
                        <option value="EN_RUTA">EN RUTA</option>
                        <option value="FINALIZADO">FINALIZADO</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label mb-1" for="f_busqueda">Buscar</label>
                    <input type="text" class="form-control" id="f_busqueda" placeholder="Prefijo, consecutivo o conductor">
                </div>
                <div class="col-12 col-md-1 d-grid">
                    <button type="button" class="btn btn-outline-secondary" id="btnFiltrar">
                        <i class="fas fa-filter"></i>
                    </button>
                </div>
            </div>
        </section>

        <section class="guias-table-wrap">
            <div class="guias-table-container">
                <table class="table table-hover align-middle" id="tablaGuias">
                    <thead>
                    <tr>
                        <th>Guia</th>
                        <th>Fecha guia</th>
                        <th>Estado</th>
                        <th>Conductor</th>
                        <th class="text-end">Remisiones</th>
                        <th class="text-end">Peso</th>
                        <th class="text-end">Valor base</th>
                        <th>Usuario crea</th>
                        <th class="text-center">Imprimir</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody id="cuerpoGuias">
                    <tr><td colspan="10" class="guias-empty">Cargando guias...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</section>

<div class="modal fade" id="modalNuevaGuia" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Nueva guia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12 col-md-3">
                        <label class="form-label" for="n_prefijo">Prefijo</label>
                        <select id="n_prefijo" class="form-select">
                            <option value="">Seleccionar</option>
                            <?php foreach ($prefijos as $prefijo): ?>
                                <option value="<?php echo htmlspecialchars(trim($prefijo->CODPREFIJO)); ?>">
                                    <?php echo htmlspecialchars(trim($prefijo->CODPREFIJO) . ' - ' . trim((string)$prefijo->DESPREFIJO)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="n_fecha_guia">Fecha y hora guia</label>
                        <input type="datetime-local" id="n_fecha_guia" class="form-control" value="<?php echo $fechaHastaDefault; ?>">
                    </div>
                    <div class="col-12 col-md-5">
                        <label class="form-label" for="n_conductor">Conductor</label>
                        <select id="n_conductor" class="form-select">
                            <option value="">Sin conductor</option>
                            <?php foreach ($conductores as $conductor): ?>
                                <option value="<?php echo (int)$conductor->TERID; ?>">
                                    <?php echo htmlspecialchars(trim((string)$conductor->NOMBRE) . ' (' . trim((string)$conductor->NIT) . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="n_observacion">Observacion inicial (opcional)</label>
                        <textarea class="form-control" id="n_observacion" rows="3" maxlength="200" placeholder="Comentario de creacion"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cerrar
                </button>
                <button type="button" class="btn btn-primary" id="btnGuardarGuia">
                    <i class="fas fa-save"></i> Guardar guia
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarGuia" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-pen"></i> Editar guia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="u_id_guia" value="0">
                <div class="row g-3">
                    <div class="col-12 col-md-3">
                        <label class="form-label" for="u_prefijo">Prefijo</label>
                        <input type="text" id="u_prefijo" class="form-control" readonly>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label" for="u_consecutivo">Consecutivo</label>
                        <input type="text" id="u_consecutivo" class="form-control" readonly>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="u_fecha_guia">Fecha y hora guia</label>
                        <input type="datetime-local" id="u_fecha_guia" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="u_conductor">Conductor</label>
                        <select id="u_conductor" class="form-select">
                            <option value="">Sin conductor</option>
                            <?php foreach ($conductores as $conductor): ?>
                                <option value="<?php echo (int)$conductor->TERID; ?>">
                                    <?php echo htmlspecialchars(trim((string)$conductor->NOMBRE) . ' (' . trim((string)$conductor->NIT) . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cerrar
                </button>
                <button type="button" class="btn btn-primary" id="btnActualizarGuia">
                    <i class="fas fa-save"></i> Guardar cambios
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRemisionesGuia" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen-lg-down modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-circle-plus"></i> Remisiones de guia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="r_id_guia" value="0">
                <div class="alert alert-light border d-flex flex-wrap align-items-center gap-2 py-2 mb-3">
                    <span class="estado-quick"><i class="fas fa-hashtag"></i> <strong id="r_num_guia">-</strong></span>
                    <span class="ms-md-3 estado-quick"><i class="fas fa-boxes-stacked"></i> Remisiones: <strong id="r_total_remisiones">0</strong></span>
                </div>

                <div class="remisiones-grid">
                    <section class="remisiones-box">
                        <h6><i class="fas fa-list-check"></i> Remisiones en la guia</h6>
                        <div class="tabla-rem-wrap">
                            <table class="table table-sm table-striped align-middle">
                                <thead>
                                <tr>
                                    <th>Remision</th>
                                    <th>Fecha</th>
                                    <th>Cliente</th>
                                    <th class="text-end">Peso</th>
                                    <th class="text-end">Valor base</th>
                                    <th class="text-center">Quitar</th>
                                </tr>
                                </thead>
                                <tbody id="cuerpoRemisionesGuia">
                                <tr><td colspan="6" class="text-center py-3 text-muted">Sin datos</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="remisiones-box">
                        <h6><i class="fas fa-magnifying-glass"></i> Agregar remisiones (CODCOMP='RS')</h6>
                        <div class="row g-2 mb-2">
                            <div class="col-12 col-md-4">
                                <input type="text" id="r_busqueda" class="form-control form-control-sm" placeholder="Buscar remision, cliente o vendedor">
                            </div>
                            <div class="col-6 col-md-3">
                                <input type="date" id="r_fecha_desde" class="form-control form-control-sm">
                            </div>
                            <div class="col-6 col-md-3">
                                <input type="date" id="r_fecha_hasta" class="form-control form-control-sm">
                            </div>
                            <div class="col-12 col-md-2 d-grid">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="btnBuscarCandidatasRem">
                                    <i class="fas fa-filter"></i> Filtrar
                                </button>
                            </div>
                        </div>
                        <div class="tabla-rem-wrap">
                            <table class="table table-sm table-hover align-middle">
                                <thead>
                                <tr>
                                    <th>Remision</th>
                                    <th>Fecha</th>
                                    <th>Cliente</th>
                                    <th>Vendedor</th>
                                    <th class="text-end">Peso</th>
                                    <th class="text-end">Valor</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Agregar</th>
                                </tr>
                                </thead>
                                <tbody id="cuerpoCandidatasRem">
                                <tr><td colspan="8" class="text-center py-3 text-muted">Sin datos</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEstadosGuia" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-route"></i> Historial de estados</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="e_id_guia" value="0">
                <div class="alert alert-light border d-flex flex-wrap align-items-center gap-2 py-2 mb-3">
                    <span class="estado-quick"><i class="fas fa-hashtag"></i> <strong id="e_num_guia">-</strong></span>
                    <span class="ms-md-3 estado-quick"><i class="fas fa-tag"></i> Actual: <strong id="e_estado_actual">-</strong></span>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-5">
                        <label class="form-label" for="e_estado">Nuevo estado</label>
                        <select id="e_estado" class="form-select">
                            <option value="EN_ALISTAMIENTO">EN ALISTAMIENTO</option>
                            <option value="EN_RUTA">EN RUTA</option>
                            <option value="FINALIZADO">FINALIZADO</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-7">
                        <label class="form-label" for="e_observacion">Observacion</label>
                        <input type="text" id="e_observacion" class="form-control" maxlength="200" placeholder="Motivo del cambio de estado">
                    </div>
                </div>

                <div class="d-flex justify-content-end mb-3">
                    <button type="button" class="btn btn-primary" id="btnCambiarEstado">
                        <i class="fas fa-check"></i> Aplicar cambio
                    </button>
                </div>

                <div class="historial-wrap">
                    <table class="table table-sm table-striped align-middle mb-0">
                        <thead class="table-dark">
                        <tr>
                            <th>Fecha y hora</th>
                            <th>Estado</th>
                            <th>Usuario</th>
                            <th>Observacion</th>
                        </tr>
                        </thead>
                        <tbody id="cuerpoHistorial">
                        <tr><td colspan="4" class="text-center py-3 text-muted">Sin datos</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var modalNuevaGuia = null;
    var modalEditarGuia = null;
    var modalRemisionesGuia = null;
    var modalEstados = null;

    function abrirModal(modal) {
        if (modal && typeof modal.show === 'function') {
            modal.show();
        }
    }

    function cerrarModal(modal) {
        if (modal && typeof modal.hide === 'function') {
            modal.hide();
        }
    }

    function notificar(tipo, titulo, texto) {
        if (typeof Swal !== 'undefined' && Swal.fire) {
            Swal.fire({ icon: tipo, title: titulo, text: texto });
            return;
        }
        alert(titulo + ': ' + texto);
    }

    function formatearNumero(valor, decimales) {
        var numero = Number(valor || 0);
        return numero.toLocaleString('es-CO', {
            minimumFractionDigits: decimales,
            maximumFractionDigits: decimales
        });
    }

    function formatearFecha(fechaIso) {
        if (!fechaIso) {
            return '';
        }
        var normalizada = String(fechaIso).replace(' ', 'T');
        var fecha = new Date(normalizada);
        if (isNaN(fecha.getTime())) {
            return fechaIso;
        }
        return fecha.toLocaleString('es-CO', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function fechaInputLocal(fechaIso) {
        if (!fechaIso) {
            return '';
        }
        var txt = String(fechaIso).replace(' ', 'T');
        if (txt.length >= 16) {
            return txt.substring(0, 16);
        }
        return txt;
    }

    function fechaSoloLocalInput(fecha) {
        var y = fecha.getFullYear();
        var m = String(fecha.getMonth() + 1).padStart(2, '0');
        var d = String(fecha.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + d;
    }

    function escapeHtml(valor) {
        return String(valor || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function claseEstado(estado) {
        switch (estado) {
            case 'EN_ALISTAMIENTO':
                return 'ali';
            case 'EN_RUTA':
                return 'rut';
            case 'FINALIZADO':
                return 'fin';
            default:
                return 'nd';
        }
    }

    function etiquetaEstado(estado) {
        switch (estado) {
            case 'EN_ALISTAMIENTO':
                return 'EN ALISTAMIENTO';
            case 'EN_RUTA':
                return 'EN RUTA';
            case 'FINALIZADO':
                return 'FINALIZADO';
            default:
                return estado || 'SIN ESTADO';
        }
    }

    function renderGuias(items) {
        var $cuerpo = $('#cuerpoGuias');
        if (!items || !items.length) {
            $cuerpo.html('<tr><td colspan="10" class="guias-empty">No hay guias para los filtros seleccionados.</td></tr>');
            return;
        }

        var html = '';
        for (var i = 0; i < items.length; i++) {
            var item = items[i];
            var estado = item.estado_actual || '';
            var numeroGuia = (item.prefijo || '') + '-' + (item.consecutivo || '');
            html += '' +
                '<tr>' +
                '<td><span class="num-guia">' + numeroGuia + '</span></td>' +
                '<td>' + formatearFecha(item.fecha_guia) + '</td>' +
                '<td><span class="badge-estado ' + claseEstado(estado) + '">' + etiquetaEstado(estado) + '</span></td>' +
                '<td>' + (item.conductor || '') + '</td>' +
                '<td class="text-end">' + formatearNumero(item.total_remisiones, 0) + '</td>' +
                '<td class="text-end">' + formatearNumero(item.total_peso, 0) + '</td>' +
                '<td class="text-end">$ ' + formatearNumero(item.total_valor_base, 0) + '</td>' +
                '<td>' + (item.usuario_crea || '') + '</td>' +
                '<td class="text-center">' +
                '   <button type="button" class="btn btn-sm btn-outline-dark btn-imprimir-guia" data-id="' + item.id + '">' +
                '      <i class="fas fa-print"></i>' +
                '   </button>' +
                '</td>' +
                '<td>' +
                '   <div class="acciones-guia">' +
                '      <button type="button" class="btn btn-sm btn-outline-success btn-remisiones" data-id="' + item.id + '" data-num="' + numeroGuia + '">' +
                '          <i class="fas fa-file-circle-plus"></i> Remisiones' +
                '      </button>' +
                '      <button type="button" class="btn btn-sm btn-outline-secondary btn-editar-guia" data-id="' + item.id + '">' +
                '          <i class="fas fa-pen"></i> Editar' +
                '      </button>' +
                '      <button type="button" class="btn btn-sm btn-outline-primary btn-estado" data-id="' + item.id + '" data-num="' + numeroGuia + '" data-estado="' + estado + '">' +
                '          <i class="fas fa-route"></i> Estados' +
                '      </button>' +
                '      <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-guia" data-id="' + item.id + '" data-num="' + numeroGuia + '">' +
                '          <i class="fas fa-trash"></i> Eliminar' +
                '      </button>' +
                '   </div>' +
                '</td>' +
                '</tr>';
        }
        $cuerpo.html(html);
    }

    function cargarGuias() {
        var data = {
            action: 'listar',
            fecha_desde: $('#f_fecha_desde').val(),
            fecha_hasta: $('#f_fecha_hasta').val(),
            estado: $('#f_estado').val(),
            busqueda: $('#f_busqueda').val()
        };

        $('#cuerpoGuias').html('<tr><td colspan="10" class="guias-empty">Consultando informacion...</td></tr>');

        $.ajax({
            url: 'guias_despachos_ajax.php',
            type: 'POST',
            dataType: 'json',
            data: data,
            success: function(resp) {
                if (!resp || !resp.ok) {
                    var msg = (resp && resp.message) ? resp.message : 'No fue posible cargar las guias.';
                    $('#cuerpoGuias').html('<tr><td colspan="10" class="guias-empty">' + msg + '</td></tr>');
                    return;
                }
                renderGuias(resp.data || []);
            },
            error: function(xhr) {
                var texto = xhr && xhr.responseText ? xhr.responseText : '';
                $('#cuerpoGuias').html('<tr><td colspan="10" class="guias-empty">Error de comunicacion con el modulo. ' + texto + '</td></tr>');
            }
        });
    }

    function limpiarFormularioGuia() {
        $('#n_prefijo').val('');
        $('#n_fecha_guia').val('<?php echo $fechaHastaDefault; ?>');
        $('#n_conductor').val('');
        $('#n_observacion').val('');
    }

    function guardarGuia() {
        var payload = {
            action: 'crear_guia',
            prefijo: $('#n_prefijo').val(),
            fecha_guia: $('#n_fecha_guia').val(),
            id_conductor: $('#n_conductor').val(),
            observacion: $('#n_observacion').val()
        };

        if (!payload.prefijo) {
            notificar('warning', 'Prefijo requerido', 'Selecciona un prefijo para la guia.');
            return;
        }
        if (!payload.fecha_guia) {
            notificar('warning', 'Fecha requerida', 'Selecciona fecha y hora de la guia.');
            return;
        }

        $.ajax({
            url: 'guias_despachos_ajax.php',
            type: 'POST',
            dataType: 'json',
            data: payload,
            success: function(resp) {
                if (!resp || !resp.ok) {
                    notificar('error', 'No se pudo guardar', (resp && resp.message) ? resp.message : 'Error en el registro.');
                    return;
                }

                notificar('success', 'Guia creada', 'Se creo la guia ' + resp.num_guia + '.');
                cerrarModal(modalNuevaGuia);
                limpiarFormularioGuia();
                cargarGuias();
            },
            error: function() {
                notificar('error', 'Error', 'Error de comunicacion al guardar la guia.');
            }
        });
    }

    function abrirEditarGuia(idGuia) {
        $.ajax({
            url: 'guias_despachos_ajax.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'obtener_guia',
                id_guia: idGuia
            },
            success: function(resp) {
                if (!resp || !resp.ok || !resp.data) {
                    notificar('error', 'No se pudo abrir', (resp && resp.message) ? resp.message : 'No se encontro la guia.');
                    return;
                }

                var g = resp.data;
                $('#u_id_guia').val(g.id);
                $('#u_prefijo').val(g.prefijo || '');
                $('#u_consecutivo').val(g.consecutivo || '');
                $('#u_fecha_guia').val(fechaInputLocal(g.fecha_guia));
                $('#u_conductor').val(g.id_conductor ? String(g.id_conductor) : '');
                abrirModal(modalEditarGuia);
            },
            error: function() {
                notificar('error', 'Error', 'Error de comunicacion al consultar la guia.');
            }
        });
    }

    function actualizarGuia() {
        var payload = {
            action: 'actualizar_guia',
            id_guia: $('#u_id_guia').val(),
            fecha_guia: $('#u_fecha_guia').val(),
            id_conductor: $('#u_conductor').val()
        };

        if (!payload.id_guia || payload.id_guia === '0') {
            notificar('warning', 'Guia invalida', 'No se encontro la guia seleccionada.');
            return;
        }
        if (!payload.fecha_guia) {
            notificar('warning', 'Fecha requerida', 'La fecha y hora de la guia es obligatoria.');
            return;
        }

        $.ajax({
            url: 'guias_despachos_ajax.php',
            type: 'POST',
            dataType: 'json',
            data: payload,
            success: function(resp) {
                if (!resp || !resp.ok) {
                    notificar('error', 'No se pudo actualizar', (resp && resp.message) ? resp.message : 'Error actualizando guia.');
                    return;
                }
                notificar('success', 'Guia actualizada', 'Se actualizaron los datos de la guia.');
                cerrarModal(modalEditarGuia);
                cargarGuias();
            },
            error: function() {
                notificar('error', 'Error', 'Error de comunicacion al actualizar la guia.');
            }
        });
    }

    function cargarDetalleRemisiones(idGuia) {
        $('#cuerpoRemisionesGuia').html('<tr><td colspan="6" class="text-center py-3 text-muted">Consultando remisiones...</td></tr>');

        $.ajax({
            url: 'guias_despachos_ajax.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'listar_detalle_guia',
                id_guia: idGuia
            },
            success: function(resp) {
                if (!resp || !resp.ok) {
                    $('#cuerpoRemisionesGuia').html('<tr><td colspan="6" class="text-center py-3 text-muted">' + ((resp && resp.message) ? escapeHtml(resp.message) : 'No fue posible cargar el detalle.') + '</td></tr>');
                    return;
                }

                var rows = resp.data || [];
                $('#r_total_remisiones').text(rows.length);

                if (!rows.length) {
                    $('#cuerpoRemisionesGuia').html('<tr><td colspan="6" class="text-center py-3 text-muted">La guia no tiene remisiones.</td></tr>');
                    return;
                }

                var html = '';
                for (var i = 0; i < rows.length; i++) {
                    var item = rows[i];
                    html += '' +
                        '<tr>' +
                        '<td>' + escapeHtml(item.remision) + '</td>' +
                        '<td>' + escapeHtml(formatearFecha(item.fecha_hora)) + '</td>' +
                        '<td>' + escapeHtml(item.cliente) + '</td>' +
                        '<td class="text-end">' + formatearNumero(item.peso, 0) + '</td>' +
                        '<td class="text-end">$ ' + formatearNumero(item.valor_base, 0) + '</td>' +
                        '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger btn-quitar-remision" data-kardex="' + item.kardex_id + '"><i class="fas fa-trash"></i></button></td>' +
                        '</tr>';
                }
                $('#cuerpoRemisionesGuia').html(html);
            },
            error: function() {
                $('#cuerpoRemisionesGuia').html('<tr><td colspan="6" class="text-center py-3 text-muted">Error consultando detalle.</td></tr>');
            }
        });
    }

    function cargarCandidatasRemisiones(idGuia) {
        $('#cuerpoCandidatasRem').html('<tr><td colspan="8" class="text-center py-3 text-muted">Consultando remisiones candidatas...</td></tr>');

        $.ajax({
            url: 'guias_despachos_ajax.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'listar_candidatas_remision',
                id_guia: idGuia,
                busqueda: $('#r_busqueda').val(),
                fecha_desde: $('#r_fecha_desde').val(),
                fecha_hasta: $('#r_fecha_hasta').val()
            },
            success: function(resp) {
                if (!resp || !resp.ok) {
                    $('#cuerpoCandidatasRem').html('<tr><td colspan="8" class="text-center py-3 text-muted">' + ((resp && resp.message) ? escapeHtml(resp.message) : 'No fue posible cargar candidatas.') + '</td></tr>');
                    return;
                }

                var rows = resp.data || [];
                if (!rows.length) {
                    $('#cuerpoCandidatasRem').html('<tr><td colspan="8" class="text-center py-3 text-muted">No hay remisiones para agregar.</td></tr>');
                    return;
                }

                var html = '';
                for (var i = 0; i < rows.length; i++) {
                    var item = rows[i];
                    var asignada = item.sn_guia_id && Number(item.sn_guia_id) > 0;
                    var enEstaGuia = asignada && String(item.sn_guia_id) === String(idGuia);
                    var estado = enEstaGuia ? 'EN ESTA GUIA' : (asignada ? 'ASIGNADA' : 'DISPONIBLE');
                    var claseEstado = enEstaGuia ? 'text-primary' : (asignada ? 'text-danger' : 'text-success');
                    var disabled = asignada ? 'disabled' : '';

                    html += '' +
                        '<tr>' +
                        '<td>' + escapeHtml(item.remision) + '</td>' +
                        '<td>' + escapeHtml(formatearFecha(item.fecha_hora)) + '</td>' +
                        '<td>' + escapeHtml(item.cliente) + '</td>' +
                        '<td>' + escapeHtml(item.vendedor) + '</td>' +
                        '<td class="text-end">' + formatearNumero(item.peso, 0) + '</td>' +
                        '<td class="text-end">$ ' + formatearNumero(item.valor_base, 0) + '</td>' +
                        '<td class="text-center ' + claseEstado + '">' + estado + '</td>' +
                        '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-success btn-agregar-remision" data-kardex="' + item.kardex_id + '" ' + disabled + '><i class="fas fa-plus"></i></button></td>' +
                        '</tr>';
                }
                $('#cuerpoCandidatasRem').html(html);
            },
            error: function(xhr, textStatus) {
                var detalle = '';
                if (xhr && xhr.responseText) {
                    detalle = String(xhr.responseText).replace(/</g, '&lt;').replace(/>/g, '&gt;');
                    if (detalle.length > 900) {
                        detalle = detalle.substring(0, 900) + '...';
                    }
                }
                var msg = 'Error consultando candidatas';
                if (textStatus) {
                    msg += ' (' + textStatus + ')';
                }
                if (detalle) {
                    msg += ': ' + detalle;
                }
                $('#cuerpoCandidatasRem').html('<tr><td colspan="8" class="text-center py-3 text-muted">' + msg + '</td></tr>');
            }
        });
    }

    function abrirModalRemisiones(idGuia, numGuia) {
        $('#r_id_guia').val(idGuia);
        $('#r_num_guia').text(numGuia || '-');
        $('#r_busqueda').val('');
        var hoy = new Date();
        var desde = new Date();
        desde.setDate(hoy.getDate() - 15);
        $('#r_fecha_desde').val(fechaSoloLocalInput(desde));
        $('#r_fecha_hasta').val(fechaSoloLocalInput(hoy));
        cargarDetalleRemisiones(idGuia);
        cargarCandidatasRemisiones(idGuia);
        abrirModal(modalRemisionesGuia);
    }

    function agregarRemision(kardexId) {
        var idGuia = $('#r_id_guia').val();
        $.ajax({
            url: 'guias_despachos_ajax.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'agregar_remision_guia',
                id_guia: idGuia,
                kardex_id: kardexId
            },
            success: function(resp) {
                if (!resp || !resp.ok) {
                    notificar('error', 'No se pudo agregar', (resp && resp.message) ? resp.message : 'Error agregando remision.');
                    return;
                }
                cargarDetalleRemisiones(idGuia);
                cargarCandidatasRemisiones(idGuia);
                cargarGuias();
            },
            error: function() {
                notificar('error', 'Error', 'Error de comunicacion al agregar remision.');
            }
        });
    }

    function quitarRemision(kardexId) {
        var idGuia = $('#r_id_guia').val();
        $.ajax({
            url: 'guias_despachos_ajax.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'quitar_remision_guia',
                id_guia: idGuia,
                kardex_id: kardexId
            },
            success: function(resp) {
                if (!resp || !resp.ok) {
                    notificar('error', 'No se pudo quitar', (resp && resp.message) ? resp.message : 'Error quitando remision.');
                    return;
                }
                cargarDetalleRemisiones(idGuia);
                cargarCandidatasRemisiones(idGuia);
                cargarGuias();
            },
            error: function() {
                notificar('error', 'Error', 'Error de comunicacion al quitar remision.');
            }
        });
    }

    function eliminarGuia(idGuia, numeroGuia) {
        var texto = 'Solo se puede eliminar una guia sin remisiones. Desea eliminar ' + (numeroGuia || '') + '?';

        var confirmar = function(callback) {
            if (typeof Swal !== 'undefined' && Swal.fire) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Confirmar eliminacion',
                    text: texto,
                    showCancelButton: true,
                    confirmButtonText: 'Si, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then(function(result) {
                    callback(!!(result && result.isConfirmed));
                });
                return;
            }
            callback(window.confirm(texto));
        };

        confirmar(function(aceptado) {
            if (!aceptado) {
                return;
            }

            $.ajax({
                url: 'guias_despachos_ajax.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'eliminar_guia',
                    id_guia: idGuia
                },
                success: function(resp) {
                    if (!resp || !resp.ok) {
                        notificar('error', 'No se pudo eliminar', (resp && resp.message) ? resp.message : 'Error eliminando guia.');
                        return;
                    }
                    notificar('success', 'Guia eliminada', 'La guia fue eliminada correctamente.');
                    cargarGuias();
                },
                error: function() {
                    notificar('error', 'Error', 'Error de comunicacion al eliminar la guia.');
                }
            });
        });
    }

    function imprimirGuia(idGuia) {
        var url = 'guia_despacho_print.php?id_guia=' + encodeURIComponent(idGuia);
        window.open(url, '_blank');
    }

    function cargarHistorial(idGuia) {
        $('#cuerpoHistorial').html('<tr><td colspan="4" class="text-center py-3 text-muted">Consultando historial...</td></tr>');

        $.ajax({
            url: 'guias_despachos_ajax.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'historial_estados',
                id_guia: idGuia
            },
            success: function(resp) {
                if (!resp || !resp.ok) {
                    var msg = (resp && resp.message) ? resp.message : 'No fue posible consultar el historial.';
                    $('#cuerpoHistorial').html('<tr><td colspan="4" class="text-center py-3 text-muted">' + msg + '</td></tr>');
                    return;
                }

                var rows = resp.data || [];
                if (!rows.length) {
                    $('#cuerpoHistorial').html('<tr><td colspan="4" class="text-center py-3 text-muted">Sin movimientos de estado.</td></tr>');
                    return;
                }

                var html = '';
                for (var i = 0; i < rows.length; i++) {
                    var item = rows[i];
                    html += '' +
                        '<tr>' +
                        '<td>' + formatearFecha(item.fecha_hora_estado) + '</td>' +
                        '<td>' + etiquetaEstado(item.estado) + '</td>' +
                        '<td>' + (item.usuario || '') + '</td>' +
                        '<td>' + (item.observacion || '') + '</td>' +
                        '</tr>';
                }
                $('#cuerpoHistorial').html(html);
            },
            error: function() {
                $('#cuerpoHistorial').html('<tr><td colspan="4" class="text-center py-3 text-muted">Error consultando historial.</td></tr>');
            }
        });
    }

    function abrirModalEstados(idGuia, numGuia, estadoActual) {
        $('#e_id_guia').val(idGuia);
        $('#e_num_guia').text(numGuia || '-');
        $('#e_estado_actual').text(etiquetaEstado(estadoActual));
        $('#e_estado').val(estadoActual || 'EN_ALISTAMIENTO');
        $('#e_observacion').val('');
        cargarHistorial(idGuia);
        abrirModal(modalEstados);
    }

    function cambiarEstadoGuia() {
        var payload = {
            action: 'cambiar_estado',
            id_guia: $('#e_id_guia').val(),
            estado: $('#e_estado').val(),
            observacion: $('#e_observacion').val()
        };

        if (!payload.id_guia || payload.id_guia === '0') {
            notificar('warning', 'Guia invalida', 'No se encontro la guia seleccionada.');
            return;
        }

        $.ajax({
            url: 'guias_despachos_ajax.php',
            type: 'POST',
            dataType: 'json',
            data: payload,
            success: function(resp) {
                if (!resp || !resp.ok) {
                    notificar('error', 'No se pudo actualizar', (resp && resp.message) ? resp.message : 'Error cambiando estado.');
                    return;
                }

                $('#e_estado_actual').text(etiquetaEstado(payload.estado));
                $('#e_observacion').val('');
                notificar('success', 'Estado actualizado', 'El estado de la guia fue actualizado.');
                cargarHistorial(payload.id_guia);
                cargarGuias();
            },
            error: function() {
                notificar('error', 'Error', 'Error de comunicacion al cambiar estado.');
            }
        });
    }

    $(document).ready(function() {
        if (window.bootstrap && bootstrap.Modal) {
            modalNuevaGuia = new bootstrap.Modal(document.getElementById('modalNuevaGuia'));
            modalEditarGuia = new bootstrap.Modal(document.getElementById('modalEditarGuia'));
            modalRemisionesGuia = new bootstrap.Modal(document.getElementById('modalRemisionesGuia'));
            modalEstados = new bootstrap.Modal(document.getElementById('modalEstadosGuia'));
        }

        $('#btnActualizarGuias, #btnFiltrar').on('click', function() {
            cargarGuias();
        });

        $('#f_busqueda').on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                cargarGuias();
            }
        });

        $('#btnNuevaGuia').on('click', function() {
            limpiarFormularioGuia();
            abrirModal(modalNuevaGuia);
        });

        $('#btnGuardarGuia').on('click', function() {
            guardarGuia();
        });

        $('#btnActualizarGuia').on('click', function() {
            actualizarGuia();
        });

        $('#btnCambiarEstado').on('click', function() {
            cambiarEstadoGuia();
        });

        $('#btnBuscarCandidatasRem').on('click', function() {
            var idGuia = $('#r_id_guia').val();
            if (idGuia && idGuia !== '0') {
                cargarCandidatasRemisiones(idGuia);
            }
        });

        $('#r_busqueda').on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                $('#btnBuscarCandidatasRem').trigger('click');
            }
        });

        $(document).on('click', '.btn-editar-guia', function() {
            var id = $(this).data('id');
            abrirEditarGuia(id);
        });

        $(document).on('click', '.btn-remisiones', function() {
            var id = $(this).data('id');
            var num = $(this).data('num');
            abrirModalRemisiones(id, num);
        });

        $(document).on('click', '.btn-agregar-remision', function() {
            var kardexId = $(this).data('kardex');
            agregarRemision(kardexId);
        });

        $(document).on('click', '.btn-quitar-remision', function() {
            var kardexId = $(this).data('kardex');
            quitarRemision(kardexId);
        });

        $(document).on('click', '.btn-estado', function() {
            var id = $(this).data('id');
            var num = $(this).data('num');
            var estado = $(this).data('estado');
            abrirModalEstados(id, num, estado);
        });

        $(document).on('click', '.btn-eliminar-guia', function() {
            var id = $(this).data('id');
            var num = $(this).data('num');
            eliminarGuia(id, num);
        });

        $(document).on('click', '.btn-imprimir-guia', function() {
            var id = $(this).data('id');
            imprimirGuia(id);
        });

        cargarGuias();
    });
})();
</script>
</body>
</html>
