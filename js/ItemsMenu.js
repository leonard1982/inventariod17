// Funcion para exportar la tabla a Excel
function exportTableToExcel(tableID, filename = '') {
    var downloadLink;
    var dataType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    var tableSelect = document.getElementById(tableID);
    var tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');

    filename = filename ? filename + '.xls' : 'Reporte.xls';

    downloadLink = document.createElement('a');
    document.body.appendChild(downloadLink);

    if (navigator.msSaveOrOpenBlob) {
        var blob = new Blob(['ufeff', tableHTML], { type: dataType });
        navigator.msSaveOrOpenBlob(blob, filename);
    } else {
        downloadLink.href = 'data:' + dataType + ', ' + tableHTML;
        downloadLink.download = filename;
        downloadLink.click();
    }
}

// Funcion para cambiar de pagina
function cambiarPagina(page, url, menuItem) {
    generarInforme('generar', url, menuItem, page);
}

function obtenerSwal() {
    if (typeof Swal !== 'undefined') {
        return Swal;
    }

    if (window.parent && typeof window.parent.Swal !== 'undefined') {
        return window.parent.Swal;
    }

    return null;
}

// Funcion para generar el informe o exportar a Excel
function generarInforme(tipo, url, menuItem, page = 1) {
    var formData = {};
    $('form').find('input, select').each(function() {
        var input = $(this);
        formData[input.attr('name')] = input.val();
    });

    formData.page = page;

    if (tipo === 'excel') {
        var excelFilename = menuItem + '.xls';
        var excelUrl = url + '?tipo=excel';
        for (var key in formData) {
            if (formData.hasOwnProperty(key)) {
                excelUrl += '&' + key + '=' + encodeURIComponent(formData[key]);
            }
        }

        var a = document.createElement('a');
        a.href = excelUrl;
        a.download = excelFilename;
        a.target = '_blank';
        a.click();
        return;
    }

    var ejecutarPeticion = function() {
        $('.bodyp').block({
            message: 'Cargando',
            css: {
                border: 'none',
                padding: '15px',
                backgroundColor: '#000',
                '-webkit-border-radius': '10px',
                '-moz-border-radius': '10px',
                opacity: 0.5,
                color: '#fff'
            }
        });

        $.ajax({
            type: 'POST',
            url: url,
            data: formData,
            success: function(response) {
                $('#contenidosmovsexis').html(response);
                $('.bodyp').unblock();
            }
        });
    };

    if (page > 1) {
        ejecutarPeticion();
        return;
    }

    var swalRef = obtenerSwal();
    if (swalRef) {
        swalRef.fire({
            title: 'Desea continuar?',
            text: 'Este reporte puede tardar un poco.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Si, continuar',
            cancelButtonText: 'No, cancelar'
        }).then(function(result) {
            if (result.isConfirmed) {
                ejecutarPeticion();
            }
        });
    } else if (window.confirm('Este reporte puede tardar un poco. Desea continuar?')) {
        ejecutarPeticion();
    }
}

// Funcion para buscar en la tabla
function doSearch(e) {
    var code = (e.keyCode ? e.keyCode : e.which);

    if (code === 13) {
        return false;
    }

    var tableReg = document.getElementById('tabledatos');
    var searchText = document.getElementById('searchTerm').value.toLowerCase();

    for (var i = 1; i < tableReg.rows.length; i++) {
        var cellsOfRow = tableReg.rows[i].getElementsByTagName('td');
        var found = false;

        for (var j = 0; j < cellsOfRow.length && !found; j++) {
            var compareWith = cellsOfRow[j].innerHTML.toLowerCase();
            if (searchText.length === 0 || compareWith.indexOf(searchText) > -1) {
                found = true;
            }
        }

        tableReg.rows[i].style.display = found ? '' : 'none';
    }
}
