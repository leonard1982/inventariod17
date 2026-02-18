<?php
require('conecta.php');

$prefijos = array();
$vsqlPrefijos = "SELECT CODPREFIJO, DESPREFIJO FROM PREFIJO ORDER BY CODPREFIJO";
if ($vcPrefijos = $conect_bd_actual->consulta($vsqlPrefijos)) {
    while ($vrPrefijo = ibase_fetch_object($vcPrefijos)) {
        $prefijos[] = $vrPrefijo;
    }
}

$fechaDesdeDefault = date('Y-m-d');
$fechaHastaDefault = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retirados</title>
    <?php includeAssets(); ?>
    <style>
        .ret-page { padding: 0.95rem 0.55rem 1.1rem; background: #fff; font-size: 0.92rem; }
        .ret-shell { max-width: 1240px; margin: 0 auto; }
        .ret-card { border: 1px solid #d8e5f0; border-radius: 16px; background: #fff; box-shadow: 0 10px 20px rgba(19, 51, 77, 0.08); overflow: hidden; }
        .ret-head { padding: 0.95rem 1rem; border-bottom: 1px solid #e2edf7; display: flex; align-items: center; justify-content: space-between; gap: 0.7rem; flex-wrap: wrap; background: linear-gradient(180deg, #f8fbff 0%, #f2f8ff 100%); }
        .ret-title { margin: 0; font-size: 1.16rem; color: #123d5e; font-weight: 800; display: inline-flex; align-items: center; gap: 0.5rem; }
        .ret-sub { color: #4c677d; font-size: 0.82rem; }
        .ret-filters { padding: 0.85rem 1rem 0.45rem; }
        .ret-table-wrap { padding: 0.35rem 1rem 1rem; }
        .ret-table-box { border: 1px solid #d6e5f1; border-radius: 12px; overflow: auto; max-height: calc(100vh - 280px); }
        #tablaRetirados { margin: 0; min-width: 1040px; }
        #tablaRetirados thead th { position: sticky; top: 0; z-index: 2; background: #1c4f73; color: #fff; font-weight: 700; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.02em; border-bottom: 0; }
        #tablaRetirados tbody td { vertical-align: middle; font-size: 0.82rem; }
        .ret-empty { padding: 1.4rem; text-align: center; color: #5b6e80; }
        .ret-badge { padding: 0.24rem 0.52rem; border-radius: 999px; font-size: 0.7rem; font-weight: 700; display: inline-block; }
        .ret-badge.disponible { background: #d9f3e2; color: #0f5f35; }
        .ret-badge.para_retirar { background: #fff2cc; color: #6f5302; }
        .ret-badge.retirado { background: #d8ebff; color: #1f4f7d; }
        tr.ret-row-retirado { background: #f7fbff; }
        .ret-acciones { display: inline-flex; gap: 0.28rem; flex-wrap: wrap; }
        .ret-acciones .btn { padding: 0.17rem 0.42rem; }
    </style>
</head>
<body class="bodyc ret-page">
<section class="ret-shell">
    <div class="ret-card">
        <header class="ret-head">
            <div>
                <h2 class="ret-title"><i class="fas fa-box-open"></i> RETIRADOS</h2>
                <div class="ret-sub">Gestion de remisiones para retiro en punto fisico</div>
            </div>
            <div>
                <button type="button" class="btn btn-outline-primary btn-sm" id="btnRetActualizar">
                    <i class="fas fa-rotate"></i> Actualizar
                </button>
            </div>
        </header>

        <section class="ret-filters">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label mb-1" for="ret_busqueda">Buscar</label>
                    <input type="text" id="ret_busqueda" class="form-control" placeholder="Remision, cliente o direccion">
                </div>
                <div class="col-6 col-md-1">
                    <label class="form-label mb-1" for="ret_prefijo">Prefijo</label>
                    <select id="ret_prefijo" class="form-select">
                        <option value="TODOS">TODOS</option>
                        <?php foreach ($prefijos as $prefijo): ?>
                            <option value="<?php echo htmlspecialchars(trim((string)$prefijo->CODPREFIJO)); ?>">
                                <?php echo htmlspecialchars(trim((string)$prefijo->CODPREFIJO)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1" for="ret_estado">Estado retiro</label>
                    <select id="ret_estado" class="form-select">
                        <option value="TODOS">TODOS</option>
                        <option value="DISPONIBLE" selected>DISPONIBLE</option>
                        <option value="PARA_RETIRAR">PARA RETIRAR</option>
                        <option value="RETIRADO">RETIRADO</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1" for="ret_desde">Fecha desde</label>
                    <input type="date" id="ret_desde" class="form-control" value="<?php echo $fechaDesdeDefault; ?>">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-1" for="ret_hasta">Fecha hasta</label>
                    <input type="date" id="ret_hasta" class="form-control" value="<?php echo $fechaHastaDefault; ?>">
                </div>
                <div class="col-12 col-md-1 d-grid">
                    <button type="button" class="btn btn-outline-secondary" id="btnRetFiltrar">
                        <i class="fas fa-filter"></i>
                    </button>
                </div>
            </div>
        </section>

        <section class="ret-table-wrap">
            <div class="ret-table-box">
                <table class="table table-hover align-middle" id="tablaRetirados">
                    <thead>
                    <tr>
                        <th>Remision</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Direccion</th>
                        <th>Telefono</th>
                        <th class="text-end">Valor base</th>
                        <th>Estado retiro</th>
                        <th>Obs.</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                    </thead>
                    <tbody id="ret_cuerpo">
                    <tr><td colspan="9" class="ret-empty">Cargando remisiones...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</section>

<script>
(function() {
    function notificar(icon, title, text) {
        if (typeof Swal !== 'undefined' && Swal.fire) {
            Swal.fire({ icon: icon, title: title, text: text });
            return;
        }
        alert(title + ': ' + text);
    }

    function escapeHtml(valor) {
        return String(valor == null ? '' : valor)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function mostrarDireccion(direccion) {
        var dir = String(direccion || '').trim();
        if (!dir) {
            notificar('info', 'Direccion', 'Esta remision no tiene direccion registrada.');
            return;
        }

        if (typeof Swal !== 'undefined' && Swal.fire) {
            Swal.fire({
                icon: 'info',
                title: 'Direccion de entrega',
                text: dir
            });
            return;
        }

        alert('Direccion de entrega: ' + dir);
    }

    function formatearNumero(v) {
        var n = Number(v || 0);
        return n.toLocaleString('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }

    function formatearFecha(v) {
        if (!v) return '';
        var txt = String(v).replace(' ', 'T');
        var d = new Date(txt);
        if (isNaN(d.getTime())) return String(v);
        return d.toLocaleString('es-CO', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function formatearFechaSolo(v) {
        if (!v) return '';
        var txt = String(v).trim();
        var soloFecha = txt.split(' ')[0];
        if (soloFecha.indexOf('T') !== -1) {
            soloFecha = soloFecha.split('T')[0];
        }

        var d = new Date(soloFecha + 'T00:00:00');
        if (isNaN(d.getTime())) {
            return soloFecha || txt;
        }
        return d.toLocaleDateString('es-CO', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        });
    }

    function badgeEstado(estado) {
        var e = String(estado || '').toUpperCase();
        if (e === 'PARA_RETIRAR') return '<span class="ret-badge para_retirar">PARA RETIRAR</span>';
        if (e === 'RETIRADO') return '<span class="ret-badge retirado">RETIRADO</span>';
        return '<span class="ret-badge disponible">DISPONIBLE</span>';
    }

    function claseFila(estado) {
        var e = String(estado || '').toUpperCase();
        if (e === 'RETIRADO') return 'ret-row-retirado';
        return '';
    }

    function cargarRetirados() {
        $('#ret_cuerpo').html('<tr><td colspan="9" class="ret-empty">Consultando remisiones...</td></tr>');

        $.ajax({
            url: 'retirados_ajax.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'listar',
                busqueda: $('#ret_busqueda').val(),
                prefijo: $('#ret_prefijo').val(),
                estado: $('#ret_estado').val(),
                fecha_desde: $('#ret_desde').val(),
                fecha_hasta: $('#ret_hasta').val()
            },
            success: function(resp) {
                if (!resp || !resp.ok) {
                    $('#ret_cuerpo').html('<tr><td colspan="9" class="ret-empty">' + escapeHtml((resp && resp.message) ? resp.message : 'No fue posible cargar la lista.') + '</td></tr>');
                    return;
                }

                var rows = resp.data || [];
                if (!rows.length) {
                    $('#ret_cuerpo').html('<tr><td colspan="9" class="ret-empty">No hay remisiones para los filtros seleccionados.</td></tr>');
                    return;
                }

                var html = '';
                for (var i = 0; i < rows.length; i++) {
                    var r = rows[i];
                    var estado = String(r.estado_retiro || 'DISPONIBLE').toUpperCase();
                    var puedeParaRetirar = estado === 'DISPONIBLE';
                    var puedeRetirado = estado === 'PARA_RETIRAR';
                    var direccionFull = String(r.direccion || '');
                    var direccionEncoded = encodeURIComponent(direccionFull);
                    var btnDireccionClass = direccionFull ? 'btn-outline-secondary' : 'btn-outline-secondary disabled';

                    html += '' +
                        '<tr class="' + claseFila(estado) + '">' +
                        '<td><strong>' + escapeHtml(r.remision || '') + '</strong></td>' +
                        '<td>' + escapeHtml(formatearFechaSolo(r.fecha_hora)) + '</td>' +
                        '<td>' + escapeHtml(r.cliente || '') + '</td>' +
                        '<td class="text-center"><button type="button" class="btn btn-sm ' + btnDireccionClass + ' btn-ret-direccion" data-direccion="' + direccionEncoded + '" title="Ver direccion"><i class="fas fa-location-dot"></i></button></td>' +
                        '<td>' + escapeHtml(r.telefono || '') + '</td>' +
                        '<td class="text-end">$ ' + formatearNumero(r.valor_base) + '</td>' +
                        '<td>' + badgeEstado(estado) + '</td>' +
                        '<td>' + escapeHtml(r.observacion || '') + '</td>' +
                        '<td class="text-center">' +
                            '<div class="ret-acciones">' +
                                '<button type="button" class="btn btn-sm btn-outline-warning btn-ret-estado" data-kardex="' + Number(r.kardex_id || 0) + '" data-estado="PARA_RETIRAR" ' + (puedeParaRetirar ? '' : 'disabled') + ' title="Marcar PARA RETIRAR"><i class="fas fa-hand-paper"></i></button>' +
                                '<button type="button" class="btn btn-sm btn-outline-primary btn-ret-estado" data-kardex="' + Number(r.kardex_id || 0) + '" data-estado="RETIRADO" ' + (puedeRetirado ? '' : 'disabled') + ' title="Marcar RETIRADO"><i class="fas fa-check"></i></button>' +
                            '</div>' +
                        '</td>' +
                        '</tr>';
                }
                $('#ret_cuerpo').html(html);
            },
            error: function(xhr) {
                var detalle = (xhr && xhr.responseText) ? xhr.responseText : '';
                $('#ret_cuerpo').html('<tr><td colspan="9" class="ret-empty">Error de comunicacion. ' + escapeHtml(detalle) + '</td></tr>');
            }
        });
    }

    function cambiarEstado(kardexId, estado) {
        var txt = (estado === 'PARA_RETIRAR')
            ? 'Confirma marcar esta remision como PARA RETIRAR?'
            : 'Confirma marcar esta remision como RETIRADO?';

        var ejecutar = function(aceptado) {
            if (!aceptado) {
                return;
            }

            $.ajax({
                url: 'retirados_ajax.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'actualizar_estado',
                    kardex_id: kardexId,
                    estado: estado
                },
                success: function(resp) {
                    if (!resp || !resp.ok) {
                        notificar('error', 'No se pudo actualizar', (resp && resp.message) ? resp.message : 'Error actualizando estado.');
                        return;
                    }
                    cargarRetirados();
                },
                error: function(xhr) {
                    notificar('error', 'Error', 'Error de comunicacion. ' + ((xhr && xhr.responseText) ? xhr.responseText : ''));
                }
            });
        };

        if (typeof Swal !== 'undefined' && Swal.fire) {
            Swal.fire({
                icon: 'question',
                title: 'Confirmar',
                text: txt,
                showCancelButton: true,
                confirmButtonText: 'Si',
                cancelButtonText: 'No'
            }).then(function(result) {
                ejecutar(!!(result && result.isConfirmed));
            });
            return;
        }

        ejecutar(window.confirm(txt));
    }

    $(document).ready(function() {
        $('#btnRetActualizar').on('click', cargarRetirados);
        $('#btnRetFiltrar').on('click', cargarRetirados);
        $('#ret_busqueda').on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                cargarRetirados();
            }
        });

        $(document).on('click', '.btn-ret-estado', function() {
            var kardexId = Number($(this).data('kardex') || 0);
            var estado = String($(this).data('estado') || '').toUpperCase();
            if (kardexId <= 0 || (estado !== 'PARA_RETIRAR' && estado !== 'RETIRADO')) {
                return;
            }
            cambiarEstado(kardexId, estado);
        });

        $(document).on('click', '.btn-ret-direccion', function() {
            var direccionCodificada = String($(this).attr('data-direccion') || '');
            var direccion = '';
            try {
                direccion = decodeURIComponent(direccionCodificada);
            } catch (e) {
                direccion = direccionCodificada;
            }
            mostrarDireccion(direccion);
        });

        cargarRetirados();
    });
})();
</script>
</body>
</html>
