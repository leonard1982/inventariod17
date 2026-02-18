<?php
require('conecta.php');

$usuarioActual = isset($_SESSION['user']) ? $_SESSION['user'] : '';
$prefijos = array();
$conductores = array();
$vehiculos = array();

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

$existeVehiculo = false;
$vsqlExisteVehiculo = "SELECT COUNT(*) AS TOTAL FROM RDB\$RELATIONS WHERE RDB\$RELATION_NAME = 'VEHICULO'";
if ($vcExisteVehiculo = $conect_bd_actual->consulta($vsqlExisteVehiculo)) {
    if ($vrExisteVehiculo = ibase_fetch_object($vcExisteVehiculo)) {
        $existeVehiculo = ((int)$vrExisteVehiculo->TOTAL) > 0;
    }
}

if ($existeVehiculo) {
    $vsqlVehiculos = "SELECT VEHICULOID, PLACA FROM VEHICULO WHERE COALESCE(SUSPENDIDO, 'N') <> 'S' ORDER BY PLACA";
    if ($vcVehiculos = $conect_bd_actual->consulta($vsqlVehiculos)) {
        while ($vrVehiculo = ibase_fetch_object($vcVehiculos)) {
            $vehiculos[] = $vrVehiculo;
        }
    }
}

$fechaDesdeDefault = date('Y-m-01\\T00:00');
$fechaHastaDefault = date('Y-m-d\\T23:59');
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
            max-width: 1720px;
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
            min-width: 1240px;
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
            max-height: 380px;
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
        .modal-remisiones-wide .modal-dialog {
            width: min(1360px, 94vw);
            max-width: min(1360px, 94vw);
            margin: 0.75rem auto;
        }
        .modal-remisiones-wide .modal-body {
            padding-top: 0.85rem;
        }
        .filtros-candidatas .form-label {
            font-size: 0.75rem;
            margin-bottom: 0.2rem;
            color: #35566f;
            font-weight: 600;
        }
        .filtros-candidatas .form-control,
        .filtros-candidatas .form-select {
            font-size: 0.82rem;
        }
        #r_zonas {
            min-height: 74px;
        }
        .estado-catalogo-wrap {
            border: 1px solid #d9e7f4;
            border-radius: 10px;
            max-height: 260px;
            overflow: auto;
        }
        .estado-catalogo-wrap table {
            margin: 0;
            font-size: 0.82rem;
        }
        .estado-catalogo-wrap thead th {
            position: sticky;
            top: 0;
            z-index: 1;
        }
        .badge-uso {
            padding: 0.25rem 0.5rem;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
            display: inline-block;
        }
        .badge-uso.si {
            background: #fff2c7;
            color: #6d5604;
        }
        .badge-uso.no {
            background: #d7f4e6;
            color: #0f6a3b;
        }
        @media (min-width: 1200px) {
            .remisiones-grid {
                grid-template-columns: 0.78fr 2.22fr;
                align-items: start;
            }
        }
        @media (max-width: 1366px) {
            .remisiones-grid {
                grid-template-columns: 1fr;
            }
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
                        <th>Vehiculo</th>
                        <th class="text-end">Remisiones</th>
                        <th class="text-end">Peso</th>
                        <th class="text-end">$BASE</th>
                        <th>CREÓ</th>
                        <th class="text-center">Imprimir</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody id="cuerpoGuias">
                    <tr><td colspan="11" class="guias-empty">Cargando guias...</td></tr>
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
                        <label class="form-label" for="n_fecha_info">Fecha y hora guia</label>
                        <input type="text" id="n_fecha_info" class="form-control" value="Se asigna automaticamente al guardar" readonly>
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
                    <div class="col-12 col-md-5">
                        <label class="form-label" for="n_vehiculo">Vehiculo (placa)</label>
                        <select id="n_vehiculo" class="form-select">
                            <option value="">Sin vehiculo</option>
                            <?php foreach ($vehiculos as $vehiculo): ?>
                                <option value="<?php echo (int)$vehiculo->VEHICULOID; ?>">
                                    <?php echo htmlspecialchars(trim((string)$vehiculo->PLACA)); ?>
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
                        <label class="form-label" for="u_fecha_guia_info">Fecha y hora guia</label>
                        <input type="text" id="u_fecha_guia_info" class="form-control" readonly>
                    </div>
                    <div class="col-12 col-md-6">
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
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="u_vehiculo">Vehiculo (placa)</label>
                        <select id="u_vehiculo" class="form-select">
                            <option value="">Sin vehiculo</option>
                            <?php foreach ($vehiculos as $vehiculo): ?>
                                <option value="<?php echo (int)$vehiculo->VEHICULOID; ?>">
                                    <?php echo htmlspecialchars(trim((string)$vehiculo->PLACA)); ?>
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

<div class="modal fade modal-remisiones-wide" id="modalRemisionesGuia" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen-lg-down modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-circle-plus"></i> Remisiones de guia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="r_id_guia" value="0">
                <input type="hidden" id="r_estado_guia" value="">
                <div class="alert alert-light border d-flex flex-wrap align-items-center gap-2 py-2 mb-3">
                    <span class="estado-quick"><i class="fas fa-hashtag"></i> <strong id="r_num_guia">-</strong></span>
                    <span class="ms-md-3 estado-quick"><i class="fas fa-boxes-stacked"></i> Remisiones: <strong id="r_total_remisiones">0</strong></span>
                    <span class="ms-md-3 estado-quick"><i class="fas fa-tag"></i> Estado: <strong id="r_estado_guia_txt">-</strong></span>
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
                                    <th class="text-center">Cliente PDF</th>
                                    <th class="text-center">Quitar</th>
                                </tr>
                                </thead>
                                <tbody id="cuerpoRemisionesGuia">
                                <tr><td colspan="7" class="text-center py-3 text-muted">Sin datos</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="remisiones-box">
                        <h6><i class="fas fa-magnifying-glass"></i> Agregar remisiones (CODCOMP='RS')</h6>
                        <div class="row g-2 mb-2 filtros-candidatas">
                            <div class="col-12 col-lg-3">
                                <label class="form-label" for="r_busqueda">Buscar</label>
                                <input type="text" id="r_busqueda" class="form-control form-control-sm" placeholder="Buscar remision, cliente o vendedor">
                            </div>
                            <div class="col-6 col-lg-1">
                                <label class="form-label" for="r_prefijo">Prefijo</label>
                                <select id="r_prefijo" class="form-select form-select-sm">
                                    <option value="todos">Todos</option>
                                    <option value="00">00</option>
                                    <option value="01">01</option>
                                    <option value="50">50</option>
                                </select>
                            </div>
                            <div class="col-12 col-lg-3">
                                <label class="form-label" for="r_zonas">Zona (multiple)</label>
                                <select id="r_zonas" class="form-select form-select-sm" multiple></select>
                            </div>
                            <div class="col-6 col-lg-2">
                                <label class="form-label" for="r_fecha_desde">Fecha desde</label>
                                <input type="date" id="r_fecha_desde" class="form-control form-control-sm">
                            </div>
                            <div class="col-6 col-lg-2">
                                <label class="form-label" for="r_fecha_hasta">Fecha hasta</label>
                                <input type="date" id="r_fecha_hasta" class="form-control form-control-sm">
                            </div>
                            <div class="col-12 col-lg-1 d-grid">
                                <label class="form-label d-none d-lg-block">&nbsp;</label>
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
                                    <th>Zona</th>
                                    <th>Vendedor</th>
                                    <th class="text-end">Peso</th>
                                    <th class="text-end">Valor</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Agregar</th>
                                </tr>
                                </thead>
                                <tbody id="cuerpoCandidatasRem">
                                <tr><td colspan="9" class="text-center py-3 text-muted">Sin datos</td></tr>
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
                        </select>
                    </div>
                    <div class="col-12 col-md-7">
                        <label class="form-label" for="e_observacion">Observacion</label>
                        <input type="text" id="e_observacion" class="form-control" maxlength="200" placeholder="Motivo del cambio de estado">
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnRefrescarCatalogoEstados">
                        <i class="fas fa-rotate"></i> Refrescar catalogo
                    </button>
                    <button type="button" class="btn btn-primary" id="btnCambiarEstado">
                        <i class="fas fa-check"></i> Aplicar cambio
                    </button>
                </div>

                <div class="border rounded-3 p-2 mb-3 bg-light">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong><i class="fas fa-sliders"></i> Catalogo de estados</strong>
                        <small class="text-muted">No se permite editar/eliminar estados en uso</small>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-12 col-md-3">
                            <input type="text" id="cfg_estado_codigo" class="form-control form-control-sm" maxlength="30" placeholder="Codigo (EJ: EN_PATIO)">
                        </div>
                        <div class="col-12 col-md-5">
                            <input type="text" id="cfg_estado_nombre" class="form-control form-control-sm" maxlength="60" placeholder="Nombre visible">
                        </div>
                        <div class="col-6 col-md-2">
                            <input type="number" id="cfg_estado_orden" class="form-control form-control-sm" value="40" min="0" step="1">
                        </div>
                        <div class="col-6 col-md-2 d-grid">
                            <button type="button" class="btn btn-sm btn-success" id="btnAgregarEstadoCfg">
                                <i class="fas fa-plus"></i> Agregar
                            </button>
                        </div>
                    </div>
                    <div class="estado-catalogo-wrap">
                        <table class="table table-sm align-middle">
                            <thead class="table-dark">
                            <tr>
                                <th>Codigo</th>
                                <th>Nombre</th>
                                <th class="text-center">Uso</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                            </thead>
                            <tbody id="cuerpoCatalogoEstados">
                            <tr><td colspan="4" class="text-center text-muted py-2">Cargando catalogo...</td></tr>
                            </tbody>
                        </table>
                    </div>
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
    var catalogoEstados = [];
    var usaCatalogoEstadosDb = false;

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

    function formatearFechaSolo(fechaIso) {
        if (!fechaIso) {
            return '';
        }

        var txt = String(fechaIso).trim();
        var soloFecha = txt.split(' ')[0];
        var candidata = soloFecha;
        if (soloFecha.indexOf('T') !== -1) {
            candidata = soloFecha.split('T')[0];
        }

        var fecha = new Date(candidata + 'T00:00:00');
        if (!isNaN(fecha.getTime())) {
            return fecha.toLocaleDateString('es-CO', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            });
        }

        fecha = new Date(txt.replace(' ', 'T'));
        if (!isNaN(fecha.getTime())) {
            return fecha.toLocaleDateString('es-CO', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            });
        }

        return candidata || txt;
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

    function telefonoSoloDigitos(tel) {
        return String(tel || '').replace(/[^0-9]/g, '');
    }

    function estadoPermiteEnvioPdf(estado) {
        var e = normalizarCodigoEstado(estado || '');
        return (e === 'EN_ALISTAMIENTO' || e === 'EN_RUTA');
    }

    function urlPdfRemision(kardexId, token) {
        var base = 'remision_entrega_pdf.php?kardex_id=' + encodeURIComponent(kardexId) + '&t=' + encodeURIComponent(token || '');
        try {
            return new URL(base, window.location.href).href;
        } catch (e) {
            return base;
        }
    }

    function urlWhatsappPdfRemision(telefono, remision, urlPdf) {
        var tel = telefonoSoloDigitos(telefono);
        if (!tel) {
            return '';
        }
        if (tel.length === 10) {
            tel = '57' + tel;
        }
        var msg = 'Compartimos el PDF de la remision ' + (remision || '') + ': ' + (urlPdf || '');
        return 'https://wa.me/' + tel + '?text=' + encodeURIComponent(msg);
    }

    function normalizarCodigoEstado(estado) {
        var codigo = String(estado || '').trim().toUpperCase();
        if (codigo === 'FINALIZADO') {
            return 'ENTREGADO';
        }
        return codigo;
    }

    function claseEstado(estado) {
        var e = normalizarCodigoEstado(estado);
        switch (e) {
            case 'EN_ALISTAMIENTO':
                return 'ali';
            case 'EN_RUTA':
                return 'rut';
            case 'ENTREGADO':
                return 'fin';
            default:
                return 'nd';
        }
    }

    function etiquetaEstado(estado) {
        var e = normalizarCodigoEstado(estado);
        for (var i = 0; i < catalogoEstados.length; i++) {
            if (normalizarCodigoEstado(catalogoEstados[i].codigo) === e) {
                return catalogoEstados[i].nombre || e.replace(/_/g, ' ');
            }
        }
        if (e === 'EN_ALISTAMIENTO') return 'EN ALISTAMIENTO';
        if (e === 'EN_RUTA') return 'EN RUTA';
        if (e === 'ENTREGADO') return 'ENTREGADO';
        return e || 'SIN ESTADO';
    }

    function renderCatalogoEstadosTabla() {
        var $body = $('#cuerpoCatalogoEstados');
        if (!$body.length) {
            return;
        }
        if (!catalogoEstados.length) {
            $body.html('<tr><td colspan="4" class="text-center text-muted py-2">Sin estados configurados.</td></tr>');
            return;
        }

        var html = '';
        for (var i = 0; i < catalogoEstados.length; i++) {
            var st = catalogoEstados[i];
            var enUso = Number(st.en_uso || 0) > 0;
            var usoClass = enUso ? 'si' : 'no';
            var usoText = enUso ? 'EN USO' : 'LIBRE';
            var id = Number(st.id || 0);
            var btnEdit = usaCatalogoEstadosDb ? '<button type="button" class="btn btn-sm btn-outline-secondary btn-editar-estado-cfg" data-id="' + id + '" ' + (enUso ? 'disabled' : '') + '><i class="fas fa-pen"></i></button>' : '';
            var btnDel = usaCatalogoEstadosDb ? '<button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-estado-cfg" data-id="' + id + '" ' + (enUso ? 'disabled' : '') + '><i class="fas fa-trash"></i></button>' : '';

            html += '' +
                '<tr>' +
                '<td><strong>' + escapeHtml(st.codigo) + '</strong></td>' +
                '<td>' + escapeHtml(st.nombre) + '</td>' +
                '<td class="text-center"><span class="badge-uso ' + usoClass + '">' + usoText + '</span></td>' +
                '<td class="text-center"><div class="d-inline-flex gap-1">' + btnEdit + btnDel + '</div></td>' +
                '</tr>';
        }
        $body.html(html);
    }

    function refrescarSelectEstados(estadoActual) {
        var estadoNorm = normalizarCodigoEstado(estadoActual);
        var htmlFiltro = '<option value="">Todos</option>';
        var htmlCambio = '';

        for (var i = 0; i < catalogoEstados.length; i++) {
            var st = catalogoEstados[i];
            if (String(st.activo || 'S').toUpperCase() === 'N') {
                continue;
            }
            var codigo = normalizarCodigoEstado(st.codigo);
            var nombre = st.nombre || codigo.replace(/_/g, ' ');
            htmlFiltro += '<option value="' + escapeHtml(codigo) + '">' + escapeHtml(nombre) + '</option>';
            htmlCambio += '<option value="' + escapeHtml(codigo) + '">' + escapeHtml(nombre) + '</option>';
        }

        $('#f_estado').html(htmlFiltro);
        $('#e_estado').html(htmlCambio);

        if (estadoNorm) {
            $('#e_estado').val(estadoNorm);
            $('#f_estado').val(estadoNorm);
        }
    }

    function cargarCatalogoEstados(callback, estadoActual) {
        $.ajax({
            url: 'guias_despachos_ajax.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'listar_estados_catalogo',
                solo_activos: 'N'
            },
            success: function(resp) {
                if (!resp || !resp.ok) {
                    notificar('error', 'Catalogo de estados', (resp && resp.message) ? resp.message : 'No fue posible cargar el catalogo.');
                    if (typeof callback === 'function') {
                        callback(false);
                    }
                    return;
                }

                usaCatalogoEstadosDb = Number(resp.usa_catalogo_db || 0) > 0;
                catalogoEstados = resp.data || [];
                refrescarSelectEstados(estadoActual || '');
                renderCatalogoEstadosTabla();
                if (typeof callback === 'function') {
                    callback(true);
                }
            },
            error: function() {
                notificar('error', 'Catalogo de estados', 'Error de comunicacion al cargar catalogo.');
                if (typeof callback === 'function') {
                    callback(false);
                }
            }
        });
    }

    function renderGuias(items) {
        var $cuerpo = $('#cuerpoGuias');
        if (!items || !items.length) {
            $cuerpo.html('<tr><td colspan="11" class="guias-empty">No hay guias para los filtros seleccionados.</td></tr>');
            return;
        }

        var html = '';
        for (var i = 0; i < items.length; i++) {
            var item = items[i];
            var estado = normalizarCodigoEstado(item.estado_actual || '');
            var numeroGuia = (item.prefijo || '') + '-' + (item.consecutivo || '');
            html += '' +
                '<tr>' +
                '<td><span class="num-guia">' + numeroGuia + '</span></td>' +
                '<td>' + formatearFecha(item.fecha_guia) + '</td>' +
                '<td><span class="badge-estado ' + claseEstado(estado) + '">' + etiquetaEstado(estado) + '</span></td>' +
                '<td>' + (item.conductor || '') + '</td>' +
                '<td>' + escapeHtml(item.placa_vehiculo || '') + '</td>' +
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
                '      <button type="button" class="btn btn-sm btn-outline-success btn-remisiones" data-id="' + item.id + '" data-num="' + numeroGuia + '" data-estado="' + estado + '">' +
                '          <i class="fas fa-file-circle-plus"></i> Remisiones' +
                '      </button>' +
                '      <button type="button" class="btn btn-sm btn-outline-secondary btn-editar-guia" data-id="' + item.id + '" title="Editar guia">' +
                '          <i class="fas fa-pen"></i>' +
                '      </button>' +
                '      <button type="button" class="btn btn-sm btn-outline-primary btn-estado" data-id="' + item.id + '" data-num="' + numeroGuia + '" data-estado="' + estado + '">' +
                '          <i class="fas fa-route"></i> Estados' +
                '      </button>' +
                '      <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-guia" data-id="' + item.id + '" data-num="' + numeroGuia + '" title="Eliminar guia">' +
                '          <i class="fas fa-trash"></i>' +
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

        $('#cuerpoGuias').html('<tr><td colspan="11" class="guias-empty">Consultando informacion...</td></tr>');

        $.ajax({
            url: 'guias_despachos_ajax.php',
            type: 'POST',
            dataType: 'json',
            data: data,
            success: function(resp) {
                if (!resp || !resp.ok) {
                    var msg = (resp && resp.message) ? resp.message : 'No fue posible cargar las guias.';
                    $('#cuerpoGuias').html('<tr><td colspan="11" class="guias-empty">' + msg + '</td></tr>');
                    return;
                }
                renderGuias(resp.data || []);
            },
            error: function(xhr) {
                var texto = xhr && xhr.responseText ? xhr.responseText : '';
                $('#cuerpoGuias').html('<tr><td colspan="11" class="guias-empty">Error de comunicacion con el modulo. ' + texto + '</td></tr>');
            }
        });
    }

    function limpiarFormularioGuia() {
        $('#n_prefijo').val('');
        $('#n_conductor').val('');
        $('#n_vehiculo').val('');
        $('#n_observacion').val('');
    }

    function guardarGuia() {
        var payload = {
            action: 'crear_guia',
            prefijo: $('#n_prefijo').val(),
            id_conductor: $('#n_conductor').val(),
            id_vehiculo: $('#n_vehiculo').val(),
            observacion: $('#n_observacion').val()
        };

        if (!payload.prefijo) {
            notificar('warning', 'Prefijo requerido', 'Selecciona un prefijo para la guia.');
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
                $('#u_fecha_guia_info').val(formatearFecha(g.fecha_guia));
                $('#u_conductor').val(g.id_conductor ? String(g.id_conductor) : '');
                $('#u_vehiculo').val(g.id_vehiculo ? String(g.id_vehiculo) : '');
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
            id_conductor: $('#u_conductor').val(),
            id_vehiculo: $('#u_vehiculo').val()
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
        $('#cuerpoRemisionesGuia').html('<tr><td colspan="7" class="text-center py-3 text-muted">Consultando remisiones...</td></tr>');

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
                    $('#cuerpoRemisionesGuia').html('<tr><td colspan="7" class="text-center py-3 text-muted">' + ((resp && resp.message) ? escapeHtml(resp.message) : 'No fue posible cargar el detalle.') + '</td></tr>');
                    return;
                }

                var rows = resp.data || [];
                $('#r_total_remisiones').text(rows.length);
                var estadoGuia = $('#r_estado_guia').val() || '';
                var permiteEnvio = estadoPermiteEnvioPdf(estadoGuia);

                if (!rows.length) {
                    $('#cuerpoRemisionesGuia').html('<tr><td colspan="7" class="text-center py-3 text-muted">La guia no tiene remisiones.</td></tr>');
                    return;
                }

                var html = '';
                for (var i = 0; i < rows.length; i++) {
                    var item = rows[i];
                    var pdfUrl = urlPdfRemision(item.kardex_id, item.token_pdf || '');
                    var waPdf = urlWhatsappPdfRemision(item.telefono, item.remision, pdfUrl);
                    var deshabilitarEnvio = !permiteEnvio;
                    var claseBtn = deshabilitarEnvio ? 'btn-outline-secondary disabled' : 'btn-outline-success';
                    var claseWsp = deshabilitarEnvio ? 'btn-outline-secondary disabled' : ((waPdf === '') ? 'btn-outline-secondary disabled' : 'btn-outline-primary');
                    var hrefPdf = deshabilitarEnvio ? '' : ('href="' + escapeHtml(pdfUrl) + '" target="_blank"');
                    var hrefWsp = (!deshabilitarEnvio && waPdf !== '') ? ('href="' + waPdf + '" target="_blank"') : '';

                    html += '' +
                        '<tr>' +
                        '<td>' + escapeHtml(item.remision) + '</td>' +
                        '<td>' + escapeHtml(formatearFechaSolo(item.fecha_hora)) + '</td>' +
                        '<td>' + escapeHtml(item.cliente) + '</td>' +
                        '<td class="text-end">' + formatearNumero(item.peso, 0) + '</td>' +
                        '<td class="text-end">$ ' + formatearNumero(item.valor_base, 0) + '</td>' +
                        '<td class="text-center">' +
                            '<div class="d-inline-flex gap-1">' +
                                '<a class="btn btn-sm ' + claseBtn + '" ' + hrefPdf + ' title="Ver PDF de remision"><i class="fas fa-file-pdf"></i></a>' +
                                '<a class="btn btn-sm ' + claseWsp + '" ' + hrefWsp + ' title="Enviar PDF al cliente por WhatsApp"><i class="fab fa-whatsapp"></i></a>' +
                            '</div>' +
                        '</td>' +
                        '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger btn-quitar-remision" data-kardex="' + item.kardex_id + '"><i class="fas fa-trash"></i></button></td>' +
                        '</tr>';
                }
                $('#cuerpoRemisionesGuia').html(html);
            },
            error: function() {
                $('#cuerpoRemisionesGuia').html('<tr><td colspan="7" class="text-center py-3 text-muted">Error consultando detalle.</td></tr>');
            }
        });
    }

    function cargarZonasCandidatas(callback) {
        var seleccionActual = $('#r_zonas').val() || [];
        var prefijo = $('#r_prefijo').val() || 'todos';

        $.ajax({
            url: 'guias_despachos_ajax.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'listar_zonas_filtro_remision',
                prefijo: prefijo
            },
            success: function(resp) {
                var zonas = (resp && resp.ok && resp.data) ? resp.data : [];
                var html = '';
                var seleccionValidada = [];

                for (var i = 0; i < zonas.length; i++) {
                    var zona = String(zonas[i] || '').trim();
                    if (!zona) {
                        continue;
                    }
                    html += '<option value="' + escapeHtml(zona) + '">' + escapeHtml(zona) + '</option>';
                    if (seleccionActual.indexOf(zona) !== -1) {
                        seleccionValidada.push(zona);
                    }
                }

                $('#r_zonas').html(html);
                if (seleccionValidada.length) {
                    $('#r_zonas').val(seleccionValidada);
                }

                if (typeof callback === 'function') {
                    callback();
                }
            },
            error: function() {
                $('#r_zonas').html('');
                if (typeof callback === 'function') {
                    callback();
                }
            }
        });
    }

    function cargarCandidatasRemisiones(idGuia) {
        $('#cuerpoCandidatasRem').html('<tr><td colspan="9" class="text-center py-3 text-muted">Consultando remisiones candidatas...</td></tr>');

        $.ajax({
            url: 'guias_despachos_ajax.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'listar_candidatas_remision',
                id_guia: idGuia,
                busqueda: $('#r_busqueda').val(),
                fecha_desde: $('#r_fecha_desde').val(),
                fecha_hasta: $('#r_fecha_hasta').val(),
                prefijo: $('#r_prefijo').val(),
                zonas_json: JSON.stringify($('#r_zonas').val() || [])
            },
            success: function(resp) {
                if (!resp || !resp.ok) {
                    $('#cuerpoCandidatasRem').html('<tr><td colspan="9" class="text-center py-3 text-muted">' + ((resp && resp.message) ? escapeHtml(resp.message) : 'No fue posible cargar candidatas.') + '</td></tr>');
                    return;
                }

                var rows = resp.data || [];
                if (!rows.length) {
                    $('#cuerpoCandidatasRem').html('<tr><td colspan="9" class="text-center py-3 text-muted">No hay remisiones para agregar.</td></tr>');
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
                        '<td>' + escapeHtml(formatearFechaSolo(item.fecha_hora)) + '</td>' +
                        '<td>' + escapeHtml(item.cliente) + '</td>' +
                        '<td>' + escapeHtml(item.zona || '') + '</td>' +
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
                $('#cuerpoCandidatasRem').html('<tr><td colspan="9" class="text-center py-3 text-muted">' + msg + '</td></tr>');
            }
        });
    }

    function abrirModalRemisiones(idGuia, numGuia, estadoGuia) {
        $('#r_id_guia').val(idGuia);
        $('#r_num_guia').text(numGuia || '-');
        $('#r_estado_guia').val(normalizarCodigoEstado(estadoGuia || ''));
        $('#r_estado_guia_txt').text(etiquetaEstado(normalizarCodigoEstado(estadoGuia || '')));
        $('#r_busqueda').val('');
        $('#r_prefijo').val('todos');
        $('#r_zonas').html('');
        var hoy = new Date();
        var desde = new Date();
        desde.setDate(hoy.getDate() - 15);
        $('#r_fecha_desde').val(fechaSoloLocalInput(desde));
        $('#r_fecha_hasta').val(fechaSoloLocalInput(hoy));
        cargarDetalleRemisiones(idGuia);
        cargarZonasCandidatas(function() {
            cargarCandidatasRemisiones(idGuia);
        });
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
        var estadoNorm = normalizarCodigoEstado(estadoActual || 'EN_ALISTAMIENTO');
        $('#e_id_guia').val(idGuia);
        $('#e_num_guia').text(numGuia || '-');
        $('#e_estado_actual').text(etiquetaEstado(estadoNorm));
        $('#e_estado').val(estadoNorm);
        $('#e_observacion').val('');
        $('#cuerpoCatalogoEstados').html('<tr><td colspan="4" class="text-center text-muted py-2">Cargando catalogo...</td></tr>');
        cargarCatalogoEstados(function() {
            $('#e_estado').val(estadoNorm);
        }, estadoNorm);
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

                $('#e_estado_actual').text(etiquetaEstado(normalizarCodigoEstado(payload.estado)));
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

    function agregarEstadoCatalogo() {
        var codigo = normalizarCodigoEstado($('#cfg_estado_codigo').val());
        var nombre = String($('#cfg_estado_nombre').val() || '').trim().toUpperCase();
        var orden = Number($('#cfg_estado_orden').val() || 0);

        if (!codigo) {
            notificar('warning', 'Codigo requerido', 'Ingresa el codigo del nuevo estado.');
            return;
        }

        $.ajax({
            url: 'guias_despachos_ajax.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'agregar_estado_catalogo',
                codigo: codigo,
                nombre: nombre,
                orden_visual: orden
            },
            success: function(resp) {
                if (!resp || !resp.ok) {
                    notificar('error', 'No se pudo agregar', (resp && resp.message) ? resp.message : 'Error agregando estado.');
                    return;
                }
                $('#cfg_estado_codigo').val('');
                $('#cfg_estado_nombre').val('');
                $('#cfg_estado_orden').val('40');
                cargarCatalogoEstados();
                notificar('success', 'Estado agregado', 'El estado se agrego al catalogo.');
            },
            error: function() {
                notificar('error', 'Error', 'Error de comunicacion al agregar estado.');
            }
        });
    }

    function editarEstadoCatalogo(id) {
        var estado = null;
        for (var i = 0; i < catalogoEstados.length; i++) {
            if (Number(catalogoEstados[i].id) === Number(id)) {
                estado = catalogoEstados[i];
                break;
            }
        }
        if (!estado) {
            notificar('warning', 'Estado no encontrado', 'No se encontro el estado seleccionado.');
            return;
        }
        if (Number(estado.en_uso || 0) > 0) {
            notificar('warning', 'Estado en uso', 'No se puede editar un estado que ya esta en uso.');
            return;
        }

        if (typeof Swal !== 'undefined' && Swal.fire) {
            Swal.fire({
                title: 'Editar estado',
                html:
                    '<input id="sw_estado_codigo" class="swal2-input" maxlength="30" placeholder="Codigo" value="' + escapeHtml(estado.codigo || '') + '">' +
                    '<input id="sw_estado_nombre" class="swal2-input" maxlength="60" placeholder="Nombre" value="' + escapeHtml(estado.nombre || '') + '">' +
                    '<input id="sw_estado_orden" class="swal2-input" type="number" placeholder="Orden" value="' + Number(estado.orden_visual || 0) + '">',
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Guardar',
                cancelButtonText: 'Cancelar',
                preConfirm: function() {
                    return {
                        codigo: normalizarCodigoEstado(document.getElementById('sw_estado_codigo').value),
                        nombre: String(document.getElementById('sw_estado_nombre').value || '').trim().toUpperCase(),
                        orden: Number(document.getElementById('sw_estado_orden').value || 0)
                    };
                }
            }).then(function(result) {
                if (!result || !result.isConfirmed || !result.value) {
                    return;
                }
                var val = result.value;
                $.ajax({
                    url: 'guias_despachos_ajax.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'editar_estado_catalogo',
                        id: id,
                        codigo: val.codigo,
                        nombre: val.nombre,
                        activo: 'S',
                        orden_visual: val.orden
                    },
                    success: function(resp) {
                        if (!resp || !resp.ok) {
                            notificar('error', 'No se pudo editar', (resp && resp.message) ? resp.message : 'Error editando estado.');
                            return;
                        }
                        cargarCatalogoEstados();
                        notificar('success', 'Estado actualizado', 'El estado se actualizo.');
                    },
                    error: function() {
                        notificar('error', 'Error', 'Error de comunicacion al editar estado.');
                    }
                });
            });
            return;
        }

        var codigoNuevo = prompt('Codigo del estado:', estado.codigo || '');
        if (codigoNuevo === null) return;
        var nombreNuevo = prompt('Nombre del estado:', estado.nombre || '');
        if (nombreNuevo === null) return;

        $.ajax({
            url: 'guias_despachos_ajax.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'editar_estado_catalogo',
                id: id,
                codigo: normalizarCodigoEstado(codigoNuevo),
                nombre: String(nombreNuevo || '').trim().toUpperCase(),
                activo: 'S',
                orden_visual: Number(estado.orden_visual || 0)
            },
            success: function(resp) {
                if (!resp || !resp.ok) {
                    notificar('error', 'No se pudo editar', (resp && resp.message) ? resp.message : 'Error editando estado.');
                    return;
                }
                cargarCatalogoEstados();
                notificar('success', 'Estado actualizado', 'El estado se actualizo.');
            },
            error: function() {
                notificar('error', 'Error', 'Error de comunicacion al editar estado.');
            }
        });
    }

    function eliminarEstadoCatalogo(id) {
        var estado = null;
        for (var i = 0; i < catalogoEstados.length; i++) {
            if (Number(catalogoEstados[i].id) === Number(id)) {
                estado = catalogoEstados[i];
                break;
            }
        }
        if (!estado) {
            notificar('warning', 'Estado no encontrado', 'No se encontro el estado seleccionado.');
            return;
        }
        if (Number(estado.en_uso || 0) > 0) {
            notificar('warning', 'Estado en uso', 'No se puede eliminar un estado que ya esta en uso.');
            return;
        }

        var ejecutarBorrado = function(ok) {
            if (!ok) return;
            $.ajax({
                url: 'guias_despachos_ajax.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'eliminar_estado_catalogo',
                    id: id
                },
                success: function(resp) {
                    if (!resp || !resp.ok) {
                        notificar('error', 'No se pudo eliminar', (resp && resp.message) ? resp.message : 'Error eliminando estado.');
                        return;
                    }
                    cargarCatalogoEstados();
                    notificar('success', 'Estado eliminado', 'El estado fue eliminado del catalogo.');
                },
                error: function() {
                    notificar('error', 'Error', 'Error de comunicacion al eliminar estado.');
                }
            });
        };

        if (typeof Swal !== 'undefined' && Swal.fire) {
            Swal.fire({
                icon: 'warning',
                title: 'Eliminar estado',
                text: 'Se eliminara el estado ' + (estado.codigo || '') + '. Continuar?',
                showCancelButton: true,
                confirmButtonText: 'Si, eliminar',
                cancelButtonText: 'Cancelar'
            }).then(function(result) {
                ejecutarBorrado(!!(result && result.isConfirmed));
            });
            return;
        }

        ejecutarBorrado(window.confirm('Eliminar estado ' + (estado.codigo || '') + '?'));
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

        $('#btnRefrescarCatalogoEstados').on('click', function() {
            cargarCatalogoEstados();
        });

        $('#btnAgregarEstadoCfg').on('click', function() {
            agregarEstadoCatalogo();
        });

        $('#btnBuscarCandidatasRem').on('click', function() {
            var idGuia = $('#r_id_guia').val();
            if (idGuia && idGuia !== '0') {
                cargarCandidatasRemisiones(idGuia);
            }
        });

        $('#r_prefijo').on('change', function() {
            var idGuia = $('#r_id_guia').val();
            if (!idGuia || idGuia === '0') {
                return;
            }
            cargarZonasCandidatas(function() {
                cargarCandidatasRemisiones(idGuia);
            });
        });

        $('#r_zonas').on('change', function() {
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
            var estado = $(this).data('estado');
            abrirModalRemisiones(id, num, estado);
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

        $(document).on('click', '.btn-editar-estado-cfg', function() {
            editarEstadoCatalogo($(this).data('id'));
        });

        $(document).on('click', '.btn-eliminar-estado-cfg', function() {
            eliminarEstadoCatalogo($(this).data('id'));
        });

        cargarCatalogoEstados(function() {
            cargarGuias();
        });
    });
})();
</script>
</body>
</html>
