// Función para exportar la tabla a Excel
function exportTableToExcel(tableID, filename = '') {
    console.log("Exportando tabla a Excel");

    var downloadLink;
    var dataType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    var tableSelect = document.getElementById(tableID);
    var tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');

    // Especificar nombre del archivo
    filename = filename ? filename + '.xls' : 'Reporte.xls';

    // Crear elemento de enlace de descarga
    downloadLink = document.createElement("a");
    document.body.appendChild(downloadLink);

    if (navigator.msSaveOrOpenBlob) {
        var blob = new Blob(['ufeff', tableHTML], { type: dataType });
        navigator.msSaveOrOpenBlob(blob, filename);
    } else {
        // Crear un enlace al archivo
        downloadLink.href = 'data:' + dataType + ', ' + tableHTML;

        // Establecer el nombre del archivo
        downloadLink.download = filename;

        // Ejecutar la función
        downloadLink.click();
    }
}

// Función para cambiar de página
function cambiarPagina(page, url, menuItem) {
    generarInforme('generar', url, menuItem, page);
}

// Función para generar el informe o exportar a Excel
function generarInforme(tipo, url, menuItem, page = 1) {
    // Obtener todos los campos del formulario de manera dinámica
    var formData = {};
    $('form').find('input, select').each(function() {
        var input = $(this);
        formData[input.attr('name')] = input.val();
    });

    formData['page'] = page;

    if (tipo === 'excel') {
        // Exportar a Excel
        var excelFilename = menuItem + '.xls';
        var excelUrl = url + "?tipo=excel";
        for (var key in formData) {
            if (formData.hasOwnProperty(key)) {
                excelUrl += "&" + key + "=" + encodeURIComponent(formData[key]);
            }
        }
        var a = document.createElement('a');
        a.href = excelUrl;
        a.download = excelFilename;
        a.target = '_blank';
        a.click();
    } else {
        if (page > 1) {
            // Generar informe sin confirmación
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

            $.ajax({
                type: "POST",
                url: url,
                data: formData,
                success: function(response) {
                    $('#contenidosmovsexis').html(response);
                    $('.bodyp').unblock();
                }
            });
        } else {
            // Generar informe con confirmación
            Swal.fire({
                title: '¿Desea continuar?',
                text: "Este Reporte puede tardar un poco.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, continuar',
                cancelButtonText: 'No, cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
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

                    $.ajax({
                        type: "POST",
                        url: url,
                        data: formData,
                        success: function(response) {
                            $('#contenidosmovsexis').html(response);
                            $('.bodyp').unblock();
                        }
                    });
                }
            });
        }
    }
}

// Función para buscar en la tabla
function doSearch(e) {
    var code = (e.keyCode ? e.keyCode : e.which);

    if (code == 13) {
        return false;
    } else {
        var tableReg = document.getElementById('tabledatos');
        var searchText = document.getElementById('searchTerm').value.toLowerCase();
        var cellsOfRow = "";
        var found = false;
        var compareWith = "";

        // Recorremos todas las filas con contenido de la tabla
        for (var i = 1; i < tableReg.rows.length; i++) {
            cellsOfRow = tableReg.rows[i].getElementsByTagName('td');
            found = false;
            // Recorremos todas las celdas
            for (var j = 0; j < cellsOfRow.length && !found; j++) {
                compareWith = cellsOfRow[j].innerHTML.toLowerCase();
                // Buscamos el texto en el contenido de la celda
                if (searchText.length === 0 || (compareWith.indexOf(searchText) > -1)) {
                    found = true;
                }
            }
            if (found) {
                tableReg.rows[i].style.display = '';
            } else {
                // Si no ha encontrado ninguna coincidencia, esconde la fila de la tabla
                tableReg.rows[i].style.display = 'none';
            }
        }
    }
}