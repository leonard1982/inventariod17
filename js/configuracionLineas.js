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
        //mostrarCargando();
        realizarPeticion(url);
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

function filtro() {
    var tecla = event.key;
    if (['.', 'e'].includes(tecla)) {
        event.preventDefault();
    }
}

$("#GuardarConfiguracion").click(function (e) {
    e.preventDefault();

    var id = $("#id").val();

    var data = {
        presupuesto: $("#presupuesto").val().replace(/,/g, ''),
        id:id,
		lineaid: $("#linea").val(),
		terid: $("#terid").val(),
		agrupacion: $("#agrupacion").val(),
		cantidad_base: $("#cantidad_base").val()
    };

    if (id === "") {
        data.nuevo_registro = true;
    }

    if (Object.entries(data).every(([key, value]) => key === 'id' || value !== "")) {
        $.post("ActualizarConfiguracionLinea.php", data, function (r) {
            console.log(r);
            var obj = JSON.parse(r);
            var message;
            if (obj.accion === "actualizar") {
                message = 'Se Actualizo Correctamente la Configuracion';
            } else if (obj.accion === "nuevo") {
                message = 'Registro creado con éxito';
            } else {
                message = 'No se Actualizo la Configuracion';
            }
            var type = (obj.accion === "actualizar" || obj.accion === "nuevo") ? 'success' : 'error';
            alertify.set('notifier', 'position', 'top-center');
            alertify.notify(message, type, 3);
        });
    } else {
        alertify.set('notifier', 'position', 'top-center');
        alertify.notify('No pueden haber campos vacios.', 'error', 3);
    }
});

document.addEventListener('DOMContentLoaded', function () {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            customClass: 'custom-tooltip'
        })
    })
});

$("#NuevoRegistro").click(function (e) {
    e.preventDefault();

    // Limpiar el formulario
	$("#id").val('');
	$("#linea").val('').trigger('change');  // Para limpiar Select2
	$("#terid").val('').trigger('change');  // Para limpiar Select2
	$("#presupuesto").val('');

    // Deshabilitar el botón NuevoRegistro
    $("#NuevoRegistro").prop('disabled', true);
});

$("#Volver").click(function (e) {
    e.preventDefault();

    // Habilitar el botón NuevoRegistro
    $("#NuevoRegistro").prop('disabled', false);

    cargarReporte("ListaConfiguracionLineas.php", 'Cargando');
});
