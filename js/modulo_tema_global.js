(function() {
    if (window.__moduloTemaGlobalInicializado) {
        return;
    }
    window.__moduloTemaGlobalInicializado = true;

    function normalizarTexto(texto) {
        return (texto || '').toLowerCase().trim();
    }

    function obtenerIconoPorContexto(texto, id, clases) {
        var t = normalizarTexto(texto);
        var i = normalizarTexto(id);
        var c = normalizarTexto(clases);

        if (t.indexOf('excel') >= 0 || i.indexOf('excel') >= 0 || i.indexOf('export') >= 0) {
            return 'fa-file-excel';
        }
        if (t.indexOf('generar') >= 0 || t.indexOf('consultar') >= 0 || t.indexOf('buscar') >= 0 || t.indexOf('filtrar') >= 0) {
            return 'fa-filter';
        }
        if (t.indexOf('actualizar') >= 0 || t.indexOf('recargar') >= 0) {
            return 'fa-rotate-right';
        }
        if (t.indexOf('guardar') >= 0 || t.indexOf('grabar') >= 0) {
            return 'fa-floppy-disk';
        }
        if (t.indexOf('eliminar') >= 0 || t.indexOf('borrar') >= 0) {
            return 'fa-trash';
        }
        if (t.indexOf('editar') >= 0 || t.indexOf('modificar') >= 0) {
            return 'fa-pen-to-square';
        }
        if (t.indexOf('ver') >= 0 || t.indexOf('detalle') >= 0 || c.indexOf('btn-primary') >= 0) {
            return 'fa-eye';
        }
        if (t.indexOf('cerrar') >= 0 || t.indexOf('salir') >= 0) {
            return 'fa-xmark';
        }
        if (t.indexOf('descargar') >= 0) {
            return 'fa-download';
        }
        if (t.indexOf('imprimir') >= 0) {
            return 'fa-print';
        }
        return 'fa-circle-dot';
    }

    function obtenerEmojiPorContexto(texto, id) {
        var t = normalizarTexto(texto);
        var i = normalizarTexto(id);

        if (t.indexOf('excel') >= 0 || i.indexOf('excel') >= 0 || i.indexOf('export') >= 0) {
            return '📊';
        }
        if (t.indexOf('generar') >= 0 || t.indexOf('consultar') >= 0 || t.indexOf('buscar') >= 0 || t.indexOf('filtrar') >= 0) {
            return '🔎';
        }
        if (t.indexOf('actualizar') >= 0 || t.indexOf('recargar') >= 0) {
            return '🔄';
        }
        if (t.indexOf('guardar') >= 0 || t.indexOf('grabar') >= 0) {
            return '💾';
        }
        if (t.indexOf('eliminar') >= 0 || t.indexOf('borrar') >= 0) {
            return '🗑';
        }
        if (t.indexOf('ver') >= 0 || t.indexOf('detalle') >= 0) {
            return '👁';
        }
        if (t.indexOf('descargar') >= 0) {
            return '⬇';
        }
        return '•';
    }

    function decorarBotonElement(el) {
        if (!el || el.dataset.iconizado === '1') {
            return;
        }

        if (el.classList && (el.classList.contains('btn-close') || el.classList.contains('app-tab-close'))) {
            el.dataset.iconizado = '1';
            return;
        }

        var tag = (el.tagName || '').toUpperCase();
        var texto = (el.textContent || el.value || '').trim();
        var id = el.id || '';
        var clases = el.className || '';

        if (tag === 'BUTTON' || (tag === 'A' && el.classList.contains('btn'))) {
            if (!el.querySelector('i')) {
                var icono = document.createElement('i');
                icono.className = 'fas ' + obtenerIconoPorContexto(texto, id, clases) + ' btn-icon-auto';
                el.insertBefore(icono, el.firstChild);
            }
            el.dataset.iconizado = '1';
            return;
        }

        if (tag === 'INPUT' && (el.type === 'button' || el.type === 'submit') && el.classList.contains('btn')) {
            if (!/^[\u2022🔎🔄💾🗑👁⬇📊]\s/.test(el.value)) {
                el.value = obtenerEmojiPorContexto(texto, id) + ' ' + el.value;
            }
            el.dataset.iconizado = '1';
        }
    }

    function decorarPaginacion() {
        var links = document.querySelectorAll('.page-link');
        for (var i = 0; i < links.length; i++) {
            var link = links[i];
            if (link.dataset.iconizado === '1') {
                continue;
            }
            var txt = (link.textContent || '').trim();
            if (txt === '<<') {
                link.innerHTML = '<i class="fas fa-angles-left"></i>';
            } else if (txt === '<') {
                link.innerHTML = '<i class="fas fa-angle-left"></i>';
            } else if (txt === '>') {
                link.innerHTML = '<i class="fas fa-angle-right"></i>';
            } else if (txt === '>>') {
                link.innerHTML = '<i class="fas fa-angles-right"></i>';
            }
            link.dataset.iconizado = '1';
        }
    }

    function aplicarTemaGlobal() {
        if (!document.body) {
            return;
        }

        document.body.classList.add('modulo-theme-ready');

        var botones = document.querySelectorAll('button, input[type="button"], input[type="submit"], a.btn');
        for (var i = 0; i < botones.length; i++) {
            decorarBotonElement(botones[i]);
        }

        decorarPaginacion();
    }

    function iniciarObservador() {
        if (!document.body || typeof MutationObserver === 'undefined') {
            return;
        }

        var enEjecucion = false;
        var observer = new MutationObserver(function() {
            if (enEjecucion) {
                return;
            }
            enEjecucion = true;
            setTimeout(function() {
                aplicarTemaGlobal();
                enEjecucion = false;
            }, 50);
        });

        observer.observe(document.body, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            aplicarTemaGlobal();
            iniciarObservador();
        });
    } else {
        aplicarTemaGlobal();
        iniciarObservador();
    }
})();
