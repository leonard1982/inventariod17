$(document).ready(function() {
    // Foco en el input de usuario al cargar la página
    $('#usuario').focus();

    // Verificar si hay credenciales guardadas
    cargarCredencialesGuardadas();

    // Enviar formulario al dar enter en el input de usuario
    $('#usuario').keypress(function(e) {
        if (e.which == 13) {
            $('#password').focus();
        }
    });

    // Enviar formulario al dar enter en el input de password
    $('#password').keypress(function(e) {
        if (e.which == 13) {
            $('#ingresar').click();
        }
    });

    // Enviar formulario al hacer clic en el botón
    $('#ingresar').click(function(e) {
        e.preventDefault();
        enviarFormulario();
    });
});

// Función para cargar credenciales guardadas
function cargarCredencialesGuardadas() {
    if (Cookies.get('credenciales')) {
        var credenciales = JSON.parse(Cookies.get('credenciales'));
        $('#usuario').val(credenciales.usuario);
        $('#password').val(credenciales.password);
    }
}

// Función para enviar el formulario
function enviarFormulario() {
    var usuario = $('#usuario').val();
    var password = $('#password').val();

    if (usuario != '' && password != '') {
        // Guardar credenciales si se selecciona la opción
        if ($('#recordar-credenciales').is(':checked')) {
            Cookies.set('credenciales', JSON.stringify({ usuario: usuario, password: password }));
        }

        $.ajax({
            type: 'POST',
            url: 'ValidaUser.php',
            data: {
                usuario: usuario,
                password: password
            },
            success: function(data) {
                manejarRespuesta(data);
            }
        });
    } else {
        toastr.error('Debe ingresar un usuario y una contraseña');
    }
}

// Función para manejar la respuesta del servidor
function manejarRespuesta(data) {
    console.log(data);
    var obj;
    try {
        obj = JSON.parse(data);
    } catch (error) {
        toastr.error('La respuesta no tiene la estructura JSON esperada');
        return;
    }

    if (typeof obj !== 'object') {
        toastr.error('La respuesta no tiene la estructura JSON esperada');
    } else {
        if (obj.accion == "conregistro") {
            location.href = "Principal.php";
        } else {
            toastr.error('Las credenciales son erróneas');
        }
    }
}

// Función para ver la contraseña
function verContraseña() {
    var password = $('#password');
    if (password.attr('type') == 'password') {
        password.attr('type', 'text');
        $('#ojo').removeClass('fas fa-eye').addClass('fas fa-eye-slash');
    } else {
        password.attr('type', 'password');
        $('#ojo').removeClass('fas fa-eye-slash').addClass('fas fa-eye');
    }
}