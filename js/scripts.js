// Función genérica para ajustar la barra lateral y el contenido
function ajustarBarraLateral(anchoBarra, margenContenido) {
    document.getElementById("sidebar").style.width = anchoBarra;
    document.getElementById("contenido").style.marginLeft = margenContenido;
}

// Función para mostrar la barra lateral
function mostrar() {
    ajustarBarraLateral("300px", "300px");
}

// Función para ocultar la barra lateral
function ocultar() {
    ajustarBarraLateral("0", "0");
}

// Función para cerrar el menú al seleccionar un ítem
function cerrarMenu() {
    ocultar();
}

// Función genérica para manejar la carga de reportes con AJAX
function cargarReporte(url, mensaje, confirmacion = false) {
    if (confirmacion) {
        Swal.fire({
            title: mensaje,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, continuar',
            cancelButtonText: 'No, cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                mostrarCargando();
                realizarPeticion(url);
                cerrarMenu();
            }
        });
    } else {
        mostrarCargando();
        realizarPeticion(url);
        cerrarMenu();
    }
}

// Función para mostrar el mensaje de carga
function mostrarCargando() {
    $('.bodyp').block({
        message: 'Cargando',
        css: {
            border: 'none',
            padding: '15px',
            backgroundColor: '#000',
            '-webkit-border-radius': '10px',
            '-moz-border-radius': '10px',
            opacity: .5,
            color: '#fff'
        }
    });
}

// Función para realizar la petición AJAX
function realizarPeticion(url) {
    $.ajax({
        type: "POST",
        url: url,
        success: function(response) {
            $('#contenido').html(response);
            $('.bodyp').unblock();
        }
    });
}

// Función que se ejecuta cuando el documento está listo
$(document).ready(function() {
    // Evento para salir del sistema
    $('#salir').on('click', function() {
        Swal.fire({
            title: '¿Desea Salir del Sistema?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, salir',
            cancelButtonText: 'No, cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                location.href = "index.php";
            }
        });
    });

    // Eventos para cargar reportes
    $('#listasmovcexis').on('click', function() {
        cargarReporte("ListaSinMovConExis.php", 'Cargando');
    });

    $('#listasmovsexis').on('click', function() {
        cargarReporte("ListaSinMovSinExis.php", 'Cargando');
    });

    $('#listacmovsexis').on('click', function() {
        cargarReporte("ListaConMovSinExis.php", 'Cargando');
    });

    $('#listaclasificac').on('click', function() {
        cargarReporte("ListaClasificacionCosto.php", '¿Este Reporte puede tardar un poco, desea continuar?', true);
    });

    $('#listaabcexistenciainventario').on('click', function() {
        cargarReporte("ListaABCExistenciaInventario.php", 'Este Reporte puede tardar un poco, ¿desea continuar?', true);
    });

    $('#comparativo').on('click', function() {
        cargarReporte("comparativo.php", 'Este Reporte puede tardar un poco, ¿desea continuar?', true);
    });

    $('#log').on('click', function() {
        cargarReporte("Log_maximos_minimos.php", 'Este Reporte puede tardar un poco, ¿desea continuar?', true);
    });

    $('#informeped').on('click', function() {
        cargarReporte("informe_pedido_mensual.php", 'Este Reporte puede tardar un poco, ¿desea continuar?', true);
    });

    $('#configuracionvencimientoxgrupos').on('click', function() {
        cargarReporte("ConfiguracionVencimientoPorProductos.php", 'Cargando');
    });

    $('#listaconexiones').on('click', function() {
        cargarReporte("conexiones.php", 'Cargando');
    });

    $('#listaconfiguraciones').on('click', function() {
        cargarReporte("ListaConfiguraciones.php", 'Cargando');
    });
	
	$('#listadoproductosclasificados').on('click', function() {
		console.log("listado_productos_clasificados.php");
        cargarReporte("listado_productos_clasificados.php", 'Cargando');
    });

    $('#listarotacion').on('click', function() {
        cargarReporte("rotacion_inventario.php", 'Este Reporte puede tardar un poco, ¿desea continuar?', true);
    });

    $('#listaventas').on('click', function() {
        cargarReporte("abc_precio.php", 'Este Reporte puede tardar un poco, ¿desea continuar?', true);
    });

    $('#listaestados').on('click', function() {
        cargarReporte("estados_pedidos.php", 'Este Reporte puede tardar un poco, ¿desea continuar?', true);
    });

    $('#listaabcventainventario').on('click', function() {
        cargarReporte("ListaVentaInventario.php", 'Este Reporte puede tardar un poco, ¿desea continuar?', true);
    });

    $('#listaabccostorepuestos').on('click', function() {
        cargarReporte("ListaCostoRepuestos.php", 'Este Reporte puede tardar un poco, ¿desea continuar?', true);
    });

    $('#listaabccostomotos').on('click', function() {
        cargarReporte("ListaCostoMotos.php", 'Este Reporte puede tardar un poco, ¿desea continuar?', true);
    });

    $('#listaabcexistenciarepuestos').on('click', function() {
        cargarReporte("ListaABCExistenciaRepuestos.php", 'Este Reporte puede tardar un poco, ¿desea continuar?', true);
    });

    $('#listaabcexistenciamotos').on('click', function() {
        cargarReporte("ListaABCExistenciaMotos.php", 'Este Reporte puede tardar un poco, ¿desea continuar?', true);
    });

    $('#listaabcventarepuestos').on('click', function() {
        cargarReporte("ListaVentaRepuestos.php", 'Este Reporte puede tardar un poco, ¿desea continuar?', true);
    });

    $('#listaabcventamotos').on('click', function() {
        cargarReporte("ListaVentaMotos.php", 'Este Reporte puede tardar un poco, ¿desea continuar?', true);
    });
	
	$('#backorder').on('click', function() {
        cargarReporte("backorder.php", 'Este Reporte puede tardar un poco, ¿desea continuar?', true);
    });
	
	$('#pedidosgeneradosauto').on('click', function() {
        cargarReporte("PedidosGeneradosAutomaticamente.php", 'Este Reporte puede tardar un poco, ¿desea continuar?', true);
    });
	
	$('#recalcularnumericas').on('click', function() {
        cargarReporte("recalcularnumericas.php", 'Este Reporte puede tardar un poco, ¿desea continuar?', true);
    });
	
	$('#listaconfiguracionlineas').on('click', function() {
        cargarReporte("ListaConfiguracionLineas.php", 'Este Reporte puede tardar un poco, ¿desea continuar?', true);
    });

    // Cerrar el menú al seleccionar un ítem
    $('#sidebar a').on('click', function() {
        cerrarMenu();
    });
	
});