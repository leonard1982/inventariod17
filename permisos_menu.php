<?php
require('conecta.php');

if (empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

if (!usuarioPuedeAdministrarPermisosMenu($_SESSION['user'])) {
    echo '<div style="padding:12px; font-family:Arial;">ACCESO DENEGADO: NO TIENE PERMISO PARA GESTIONAR PERMISOS DE MENU.</div>';
    exit;
}

if (!$conect_bd_inventario) {
    echo '<div style="padding:12px; font-family:Arial;">NO HAY CONEXION A LA BD DE INVENTARIOS. NO SE PUEDEN GESTIONAR PERMISOS.</div>';
    exit;
}

if (!existeTablaPermisosMenu()) {
    echo '<div style="padding:12px; font-family:Arial;">NO EXISTE LA TABLA SN_MENU_PERMISOS. EJECUTA 02_create_permisos_menu.sql EN LA BD DE INVENTARIOS.</div>';
    exit;
}

$catalogo = obtenerCatalogoMenusAplicacion();
$menusAdmin = array();
foreach ($catalogo as $menuId => $meta) {
    if ($menuId === 'salir') {
        continue;
    }
    $menusAdmin[$menuId] = $meta;
}

$usuarios = array();
$vsqlUsuarios = "SELECT NOMBRE, NOMUSUARIO, ROL FROM USUARIOS WHERE COALESCE(INACTIVO,'N') <> 'S' ORDER BY NOMBRE";
if ($vcUsuarios = $conect_bd_actual->consulta($vsqlUsuarios)) {
    while ($vrUsuario = ibase_fetch_object($vcUsuarios)) {
        $usuarios[] = $vrUsuario;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permisos de menu</title>
    <?php includeAssets(); ?>
    <style>
        .perm-page { padding: 0.95rem 0.55rem 1.1rem; }
        .perm-shell { max-width: 1250px; margin: 0 auto; }
        .perm-card {
            border: 1px solid #d7e4ef;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 8px 18px rgba(12, 46, 77, 0.08);
            overflow: hidden;
        }
        .perm-head {
            background: linear-gradient(180deg, #f7fbff 0%, #edf5fd 100%);
            border-bottom: 1px solid #dce8f3;
            padding: 0.95rem 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.8rem;
            flex-wrap: wrap;
        }
        .perm-title {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 800;
            color: #123f60;
        }
        .perm-body { padding: 0.95rem 1rem 1rem; }
        .perm-nota {
            border: 1px solid #d8e7f5;
            background: #f5faff;
            color: #33566f;
            border-radius: 9px;
            padding: 0.6rem 0.75rem;
            font-size: 0.88rem;
            margin-bottom: 0.8rem;
        }
        .perm-grid {
            display: grid;
            gap: 0.8rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .perm-col {
            border: 1px solid #d8e5f0;
            border-radius: 11px;
            background: #fff;
            overflow: hidden;
        }
        .perm-col-head {
            padding: 0.62rem 0.8rem;
            background: #eef5fc;
            border-bottom: 1px solid #d8e5f0;
            color: #194766;
            font-weight: 700;
            font-size: 0.9rem;
        }
        .perm-col-body { padding: 0.65rem 0.8rem; max-height: 440px; overflow: auto; }
        .perm-item { display: flex; align-items: center; gap: 0.55rem; margin-bottom: 0.45rem; font-size: 0.9rem; }
        .perm-item:last-child { margin-bottom: 0; }
        .perm-badge {
            margin-left: auto;
            font-size: 0.68rem;
            font-weight: 700;
            color: #0f4f66;
            background: #d7f2ff;
            border: 1px solid #b8e6fb;
            border-radius: 999px;
            padding: 0.1rem 0.45rem;
        }
        .perm-vinculo {
            margin-top: 0.85rem;
            border: 1px solid #d8e5f0;
            border-radius: 11px;
            background: #fff;
            overflow: hidden;
        }
        .perm-vinculo-head {
            padding: 0.62rem 0.8rem;
            background: #eef5fc;
            border-bottom: 1px solid #d8e5f0;
            color: #194766;
            font-weight: 700;
            font-size: 0.9rem;
        }
        .perm-vinculo-body {
            padding: 0.75rem 0.8rem 0.85rem;
        }
        .perm-vinculo-resumen {
            border: 1px solid #d8e7f3;
            border-radius: 9px;
            background: #f8fbff;
            padding: 0.5rem 0.65rem;
            color: #2f4f66;
            font-size: 0.85rem;
        }
        @media (max-width: 992px) {
            .perm-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="bodyc perm-page">
<section class="perm-shell">
    <div class="perm-card">
        <header class="perm-head">
            <h2 class="perm-title"><i class="fas fa-user-shield"></i> Permisos de menu por usuario</h2>
            <div class="d-flex gap-2 flex-wrap align-items-center">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnMarcarTodos"><i class="fas fa-check-double"></i> Marcar todo</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnDesmarcarTodos"><i class="fas fa-ban"></i> Desmarcar todo</button>
                <button type="button" class="btn btn-outline-primary btn-sm" id="btnPerfilConductor"><i class="fas fa-truck-ramp-box"></i> Perfil conductor</button>
                <div class="form-check form-switch m-0 d-flex align-items-center gap-1">
                    <input class="form-check-input" type="checkbox" id="chkPermTodo">
                    <label class="form-check-label small mb-0" for="chkPermTodo">Todos</label>
                </div>
                <div class="form-check form-switch m-0 d-flex align-items-center gap-1">
                    <input class="form-check-input" type="checkbox" id="chkPermNinguno">
                    <label class="form-check-label small mb-0" for="chkPermNinguno">Ninguno</label>
                </div>
            </div>
        </header>

        <div class="perm-body">
            <div class="perm-nota">
                <strong>Regla:</strong> puede editar permisos el administrador o un usuario delegado con permiso explicito en este modulo. Si un usuario no tiene configuracion guardada, tendra acceso total por defecto.
            </div>

            <div class="row g-2 align-items-end mb-3">
                <div class="col-12 col-md-6">
                    <label class="form-label mb-1" for="perm_usuario">Usuario</label>
                    <select id="perm_usuario" class="form-select">
                        <option value="">Seleccionar usuario...</option>
                        <?php foreach ($usuarios as $u): ?>
                            <?php
                                $nombre = strtoupper(trim((string)$u->NOMBRE));
                                $nomUsuario = trim((string)$u->NOMUSUARIO);
                                $rol = strtoupper(trim((string)$u->ROL));
                            ?>
                            <option value="<?php echo htmlspecialchars($nombre); ?>">
                                <?php echo htmlspecialchars($nombre . ' - ' . $nomUsuario . ($rol !== '' ? ' [' . $rol . ']' : '')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6 d-flex gap-2 flex-wrap justify-content-md-end">
                    <button type="button" class="btn btn-outline-primary" id="btnCargarPermisos"><i class="fas fa-rotate"></i> Cargar</button>
                    <button type="button" class="btn btn-primary" id="btnGuardarPermisos"><i class="fas fa-save"></i> Guardar permisos</button>
                    <button type="button" class="btn btn-danger" id="btnResetPermisos"><i class="fas fa-trash"></i> Restaurar default</button>
                </div>
            </div>

            <div class="perm-grid">
                <section class="perm-col">
                    <div class="perm-col-head"><i class="fas fa-bars"></i> Menus principales</div>
                    <div class="perm-col-body" id="perm_principal">
                        <?php foreach ($menusAdmin as $menuId => $meta): ?>
                            <?php if (isset($meta['tipo']) && $meta['tipo'] === 'principal'): ?>
                                <label class="perm-item">
                                    <input type="checkbox" class="form-check-input perm-check" data-menu-id="<?php echo htmlspecialchars($menuId); ?>">
                                    <span><?php echo htmlspecialchars($meta['texto']); ?></span>
                                    <?php if (!empty($meta['solo_admin'])): ?><span class="perm-badge"><?php echo !empty($meta['delegable_admin']) ? 'ADMIN/DELEGABLE' : 'ADMIN'; ?></span><?php endif; ?>
                                </label>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="perm-col">
                    <div class="perm-col-head"><i class="fas fa-user-cog"></i> Menus de usuario</div>
                    <div class="perm-col-body" id="perm_usuario_menus">
                        <?php foreach ($menusAdmin as $menuId => $meta): ?>
                            <?php if (isset($meta['tipo']) && $meta['tipo'] === 'usuario'): ?>
                                <label class="perm-item">
                                    <input type="checkbox" class="form-check-input perm-check" data-menu-id="<?php echo htmlspecialchars($menuId); ?>">
                                    <span><?php echo htmlspecialchars($meta['texto']); ?></span>
                                    <?php if (!empty($meta['solo_admin'])): ?><span class="perm-badge"><?php echo !empty($meta['delegable_admin']) ? 'ADMIN/DELEGABLE' : 'ADMIN'; ?></span><?php endif; ?>
                                </label>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>

            <section class="perm-vinculo">
                <div class="perm-vinculo-head"><i class="fas fa-id-card"></i> Vinculo conductor por VARIOS (GVENDE&lt;USUARIO&gt;)</div>
                <div class="perm-vinculo-body">
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-md-3">
                            <label class="form-label mb-1" for="vende_variab">Variable</label>
                            <input type="text" class="form-control" id="vende_variab" readonly placeholder="GVENDEUSUARIO">
                        </div>
                        <div class="col-12 col-md-5">
                            <label class="form-label mb-1" for="vende_valor">Valor</label>
                            <input type="text" class="form-control" id="vende_valor" maxlength="60" placeholder="TERID o NIT/NITTRI del conductor">
                        </div>
                        <div class="col-12 col-md-4 d-flex gap-2 flex-wrap justify-content-md-end">
                            <button type="button" class="btn btn-outline-primary" id="btnCargarVende"><i class="fas fa-rotate"></i> Cargar vinculo</button>
                            <button type="button" class="btn btn-primary" id="btnGuardarVende"><i class="fas fa-save"></i> Guardar vinculo</button>
                            <button type="button" class="btn btn-outline-danger" id="btnLimpiarVende"><i class="fas fa-eraser"></i> Limpiar</button>
                        </div>
                    </div>
                    <div class="perm-vinculo-resumen mt-2" id="vende_resumen">
                        Selecciona un usuario para cargar GVENDE&lt;USUARIO&gt;.
                    </div>
                </div>
            </section>
        </div>
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

    function obtenerUsuarioSeleccionado() {
        return ($('#perm_usuario').val() || '').trim().toUpperCase();
    }

    function marcarTodos(valor) {
        $('.perm-check').prop('checked', !!valor);
        $('#chkPermTodo').prop('checked', !!valor);
        $('#chkPermNinguno').prop('checked', !valor);
    }

    function aplicarPerfilConductor() {
        marcarTodos(false);
        $('.perm-check[data-menu-id="despachosconductor"]').prop('checked', true);
        $('#chkPermTodo').prop('checked', false);
        $('#chkPermNinguno').prop('checked', false);
    }

    function variabVendeUsuario(usuario) {
        var u = String(usuario || '').trim().toUpperCase();
        if (!u) {
            return '';
        }
        return 'GVENDE' + u;
    }

    function renderResumenVende(resp) {
        var base = 'Sin vinculo de conductor en VARIOS.';
        if (!resp || !resp.valor) {
            $('#vende_resumen').text(base);
            return;
        }

        var txt = 'Valor guardado: ' + resp.valor;
        if (resp.resuelto && resp.conductor) {
            txt += ' | Conductor: ' + (resp.conductor.nombre || '') + ' (TERID ' + (resp.conductor.terid || '') + ')';
            txt += ' | NIT: ' + (resp.conductor.nit || '');
            txt += ' | NITTRI: ' + (resp.conductor.nittri || '');
        } else {
            txt += ' | No se resolvio conductor activo con ese dato.';
        }
        $('#vende_resumen').text(txt);
    }

    function cargarVendeUsuario(mostrarMensaje) {
        var usuario = obtenerUsuarioSeleccionado();
        if (!usuario) {
            $('#vende_variab').val('');
            $('#vende_valor').val('');
            $('#vende_resumen').text('Selecciona un usuario para cargar GVENDE<USUARIO>.');
            if (mostrarMensaje) {
                notificar('warning', 'Usuario requerido', 'Selecciona un usuario para cargar vinculo.');
            }
            return;
        }

        $('#vende_variab').val(variabVendeUsuario(usuario));

        $.ajax({
            url: 'permisos_menu_ajax.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'obtener_vende_usuario',
                usuario: usuario
            },
            success: function(resp) {
                if (!resp || !resp.ok) {
                    notificar('error', 'Error', (resp && resp.message) ? resp.message : 'No se pudo cargar variable GVENDE.');
                    return;
                }

                $('#vende_variab').val(resp.variab || variabVendeUsuario(usuario));
                $('#vende_valor').val(resp.valor || '');
                renderResumenVende(resp);
            },
            error: function() {
                notificar('error', 'Error', 'Error de comunicacion al cargar GVENDE.');
            }
        });
    }

    function guardarVendeUsuario() {
        var usuario = obtenerUsuarioSeleccionado();
        var valor = ($('#vende_valor').val() || '').trim();

        if (!usuario) {
            notificar('warning', 'Usuario requerido', 'Selecciona un usuario para guardar vinculo.');
            return;
        }

        if (!valor) {
            notificar('warning', 'Dato requerido', 'Ingresa TERID o NIT/NITTRI para guardar.');
            return;
        }

        $.ajax({
            url: 'permisos_menu_ajax.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'guardar_vende_usuario',
                usuario: usuario,
                valor: valor
            },
            success: function(resp) {
                if (!resp || !resp.ok) {
                    notificar('error', 'Error', (resp && resp.message) ? resp.message : 'No se pudo guardar GVENDE.');
                    return;
                }

                $('#vende_variab').val(resp.variab || variabVendeUsuario(usuario));
                $('#vende_valor').val(resp.valor || valor);
                renderResumenVende(resp);
                notificar('success', 'Vinculo guardado', resp.message || 'Vinculo conductor actualizado.');
            },
            error: function() {
                notificar('error', 'Error', 'Error de comunicacion al guardar GVENDE.');
            }
        });
    }

    function limpiarVendeUsuario() {
        var usuario = obtenerUsuarioSeleccionado();
        if (!usuario) {
            notificar('warning', 'Usuario requerido', 'Selecciona un usuario para limpiar vinculo.');
            return;
        }

        Swal.fire({
            icon: 'warning',
            title: 'Limpiar vinculo conductor',
            text: 'Se eliminara la variable GVENDE<USUARIO>. Continuar?',
            showCancelButton: true,
            confirmButtonText: 'Si, limpiar',
            cancelButtonText: 'Cancelar'
        }).then(function(result) {
            if (!result.isConfirmed) {
                return;
            }

            $.ajax({
                url: 'permisos_menu_ajax.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'limpiar_vende_usuario',
                    usuario: usuario
                },
                success: function(resp) {
                    if (!resp || !resp.ok) {
                        notificar('error', 'Error', (resp && resp.message) ? resp.message : 'No se pudo limpiar GVENDE.');
                        return;
                    }
                    $('#vende_variab').val(resp.variab || variabVendeUsuario(usuario));
                    $('#vende_valor').val('');
                    $('#vende_resumen').text('Sin vinculo de conductor en VARIOS.');
                    notificar('success', 'Vinculo limpiado', resp.message || 'Variable eliminada.');
                },
                error: function() {
                    notificar('error', 'Error', 'Error de comunicacion al limpiar GVENDE.');
                }
            });
        });
    }

    function aplicarMapaPermisos(configurado, mapa) {
        if (!configurado) {
            marcarTodos(true);
            return;
        }

        $('.perm-check').each(function() {
            var menuId = String($(this).data('menu-id') || '').toLowerCase();
            var permitido = (mapa && typeof mapa === 'object' && Object.prototype.hasOwnProperty.call(mapa, menuId)) ? mapa[menuId] : 'N';
            $(this).prop('checked', permitido === 'S');
        });
        $('#chkPermTodo').prop('checked', false);
        $('#chkPermNinguno').prop('checked', false);
    }

    function cargarPermisosUsuario() {
        var usuario = obtenerUsuarioSeleccionado();
        if (!usuario) {
            notificar('warning', 'Usuario requerido', 'Selecciona un usuario para cargar permisos.');
            return;
        }

        $.ajax({
            url: 'permisos_menu_ajax.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'obtener_permisos_usuario',
                usuario: usuario
            },
            success: function(resp) {
                if (!resp || !resp.ok) {
                    notificar('error', 'Error', (resp && resp.message) ? resp.message : 'No se pudo cargar permisos.');
                    return;
                }
                aplicarMapaPermisos(!!resp.configurado, resp.permisos || {});
                if (!resp.configurado) {
                    notificar('info', 'Sin configuracion', 'Este usuario no tiene configuracion guardada: aplica acceso total por defecto.');
                }
            },
            error: function() {
                notificar('error', 'Error', 'Error de comunicacion al cargar permisos.');
            }
        });
    }

    function construirEstadoPermisos() {
        var estado = {};
        $('.perm-check').each(function() {
            var menuId = String($(this).data('menu-id') || '').toLowerCase();
            if (!menuId) {
                return;
            }
            estado[menuId] = $(this).is(':checked') ? 'S' : 'N';
        });
        return estado;
    }

    function guardarPermisosUsuario() {
        var usuario = obtenerUsuarioSeleccionado();
        if (!usuario) {
            notificar('warning', 'Usuario requerido', 'Selecciona un usuario para guardar permisos.');
            return;
        }

        var estado = construirEstadoPermisos();

        $.ajax({
            url: 'permisos_menu_ajax.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'guardar_permisos_usuario',
                usuario: usuario,
                estado_json: JSON.stringify(estado)
            },
            success: function(resp) {
                if (!resp || !resp.ok) {
                    notificar('error', 'Error', (resp && resp.message) ? resp.message : 'No se pudo guardar permisos.');
                    return;
                }
                notificar('success', 'Permisos guardados', 'Permisos de menu actualizados para ' + usuario + '.');
            },
            error: function() {
                notificar('error', 'Error', 'Error de comunicacion al guardar permisos.');
            }
        });
    }

    function restaurarDefaultUsuario() {
        var usuario = obtenerUsuarioSeleccionado();
        if (!usuario) {
            notificar('warning', 'Usuario requerido', 'Selecciona un usuario para restaurar.');
            return;
        }

        Swal.fire({
            icon: 'warning',
            title: 'Restaurar permisos por defecto',
            text: 'Se eliminaran permisos personalizados y el usuario tendra acceso total por defecto. Continuar?',
            showCancelButton: true,
            confirmButtonText: 'Si, restaurar',
            cancelButtonText: 'Cancelar'
        }).then(function(result) {
            if (!result.isConfirmed) {
                return;
            }

            $.ajax({
                url: 'permisos_menu_ajax.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'limpiar_permisos_usuario',
                    usuario: usuario
                },
                success: function(resp) {
                    if (!resp || !resp.ok) {
                        notificar('error', 'Error', (resp && resp.message) ? resp.message : 'No se pudo restaurar permisos.');
                        return;
                    }
                    marcarTodos(true);
                    notificar('success', 'Restaurado', 'Se restauraron permisos por defecto para ' + usuario + '.');
                },
                error: function() {
                    notificar('error', 'Error', 'Error de comunicacion al restaurar permisos.');
                }
            });
        });
    }

    $(document).ready(function() {
        $('#btnMarcarTodos').on('click', function() { marcarTodos(true); });
        $('#btnDesmarcarTodos').on('click', function() { marcarTodos(false); });
        $('#btnPerfilConductor').on('click', aplicarPerfilConductor);
        $('#chkPermTodo').on('change', function() {
            if ($(this).is(':checked')) {
                marcarTodos(true);
            }
        });
        $('#chkPermNinguno').on('change', function() {
            if ($(this).is(':checked')) {
                marcarTodos(false);
            }
        });
        $('#btnCargarPermisos').on('click', cargarPermisosUsuario);
        $('#btnGuardarPermisos').on('click', guardarPermisosUsuario);
        $('#btnResetPermisos').on('click', restaurarDefaultUsuario);
        $('#btnCargarVende').on('click', function() { cargarVendeUsuario(true); });
        $('#btnGuardarVende').on('click', guardarVendeUsuario);
        $('#btnLimpiarVende').on('click', limpiarVendeUsuario);

        $('#perm_usuario').on('change', function() {
            cargarPermisosUsuario();
            cargarVendeUsuario(false);
        });

        $('#chkPermTodo').prop('checked', false);
        $('#chkPermNinguno').prop('checked', false);
    });
})();
</script>
</body>
</html>
