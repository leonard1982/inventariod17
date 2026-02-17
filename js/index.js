$(document).ready(function() {
    $('#usuario').focus();
    cargarFraseProductividad();
    cargarCredencialesGuardadas();
    inicializarInstalacionPwa();
    registrarServiceWorkerPwa();

    $('#usuario').keypress(function(e) {
        if (e.which == 13) {
            $('#password').focus();
        }
    });

    $('#password').keypress(function(e) {
        if (e.which == 13) {
            $('#ingresar').click();
        }
    });

    $('#ingresar').click(function(e) {
        e.preventDefault();
        enviarFormulario();
    });
});

function cargarFraseProductividad() {
    var frases = [
        'Cada guia bien gestionada mejora toda la operacion.',
        'Menos reprocesos, mas resultados.',
        'La disciplina diaria construye grandes cierres.',
        'Hoy es buen dia para despachar sin atrasos.',
        'Un inventario ordenado acelera cada entrega.',
        'Lo que se mide, mejora.',
        'Enfocate en lo importante y ejecuta con ritmo.',
        'Una buena trazabilidad evita problemas futuros.',
        'Planear bien reduce urgencias.',
        'Calidad y velocidad pueden ir juntas.',
        'Un paso claro hoy evita tres correcciones manana.',
        'Haz simple lo complejo y avanza.',
        'La constancia supera la improvisacion.',
        'Cada entrega a tiempo fortalece la confianza.',
        'Una operacion limpia empieza con datos limpios.',
        'Decisiones rapidas, con informacion correcta.',
        'Tu mejor indicador es cumplir lo prometido.',
        'Procesos estables, equipo mas productivo.',
        'Menos friccion, mas flujo.',
        'Documentar bien tambien es producir.',
        'La mejora continua se construye cada dia.',
        'Un buen control evita perdidas innecesarias.',
        'Primero lo critico, luego lo urgente.',
        'Cierra tareas, abre capacidad.',
        'El orden operativo genera rentabilidad.',
        'La puntualidad tambien es estrategia.',
        'Verifica, confirma y despacha.',
        'Cada minuto ahorrado suma en el mes.',
        'Estandarizar hoy, escalar manana.',
        'Productividad es enfoque con seguimiento.'
    ];

    var idx = Math.floor(Math.random() * frases.length);
    var nodo = document.getElementById('frase-productividad');
    if (nodo) {
        nodo.textContent = frases[idx];
    }
}

function cargarCredencialesGuardadas() {
    if (Cookies.get('credenciales')) {
        var credenciales = JSON.parse(Cookies.get('credenciales'));
        $('#usuario').val(credenciales.usuario);
        $('#password').val(credenciales.password);
    }
}

function enviarFormulario() {
    var usuario = $('#usuario').val();
    var password = $('#password').val();

    if (usuario != '' && password != '') {
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
        toastr.error('Debe ingresar un usuario y una contrasena');
    }
}

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
        if (obj.accion == 'conregistro') {
            location.href = 'Principal.php';
        } else {
            toastr.error('Las credenciales son erroneas');
        }
    }
}

function verContrasena() {
    var password = $('#password');
    if (password.attr('type') == 'password') {
        password.attr('type', 'text');
        $('#ojo').removeClass('fas fa-eye').addClass('fas fa-eye-slash');
    } else {
        password.attr('type', 'password');
        $('#ojo').removeClass('fas fa-eye-slash').addClass('fas fa-eye');
    }
}

var deferredInstallPrompt = null;

function inicializarInstalacionPwa() {
    var btnInstalar = document.getElementById('instalar-app');
    if (!btnInstalar) {
        return;
    }

    window.addEventListener('beforeinstallprompt', function(e) {
        e.preventDefault();
        deferredInstallPrompt = e;
        btnInstalar.classList.remove('d-none');
    });

    btnInstalar.addEventListener('click', async function() {
        if (!deferredInstallPrompt) {
            toastr.info('La instalacion no esta disponible en este navegador o contexto.');
            return;
        }

        deferredInstallPrompt.prompt();
        try {
            await deferredInstallPrompt.userChoice;
        } catch (e) {
        }
        deferredInstallPrompt = null;
        btnInstalar.classList.add('d-none');
    });

    window.addEventListener('appinstalled', function() {
        btnInstalar.classList.add('d-none');
        deferredInstallPrompt = null;
    });
}

function registrarServiceWorkerPwa() {
    if (!('serviceWorker' in navigator)) {
        return;
    }

    window.addEventListener('load', function() {
        navigator.serviceWorker.register('sw.js', { scope: './' }).catch(function(err) {
            if (window.console && typeof console.warn === 'function') {
                console.warn('No se pudo registrar el service worker:', err);
            }
        });
    });
}
