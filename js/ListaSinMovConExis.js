// Función para exportar una tabla a Excel
function exportTableToExcel(tableID, filename = '') {
    console.log("Exportando tabla a Excel");

    const dataType = 'application/vnd.ms-excel';
    const tableSelect = document.getElementById(tableID);
    const tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');

    // Especificar el nombre del archivo
    filename = filename ? `${filename}.xls` : 'Lista Sin Movimiento y Con Existencia.xls';

    // Crear elemento de enlace de descarga
    const downloadLink = document.createElement("a");
    document.body.appendChild(downloadLink);

    if (navigator.msSaveOrOpenBlob) {
        const blob = new Blob(['ufeff', tableHTML], { type: dataType });
        navigator.msSaveOrOpenBlob(blob, filename);
    } else {
        // Crear un enlace al archivo
        downloadLink.href = `data:${dataType}, ${tableHTML}`;

        // Establecer el nombre del archivo
        downloadLink.download = filename;

        // Ejecutar la descarga
        downloadLink.click();
    }
}

// Función para obtener los valores de los campos de entrada
function obtenerValoresCampos() {
    return {
        reg: $('#reg').val(),
        grupo: $('#grupo').val(),
        linea: $('#linea').val(),
        traslado: $('#traslado').val(),
        bodega: $('#bodega').val()
    };
}

// Función para mostrar un mensaje de carga
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

// Función para realizar una petición AJAX
function realizarPeticionAjax(url, data, successCallback) {
    $.ajax({
        type: "POST",
        url: url,
        data: data,
        success: successCallback
    });
}

// Evento para exportar la tabla a Excel
$("#btnExport").click(function(e) {
    e.preventDefault();
    const valores = obtenerValoresCampos();
    const queryString = `reg=${valores.reg}&grupo=${valores.grupo}&linea=${valores.linea}&tras=${valores.traslado}&bodega=${valores.bodega}`;
    window.open(`ListaSinMovConExis_excel.php?${queryString}`, "ventana1", "width=1200,height=600,scrollbars=NO");
});

// Evento para actualizar el reporte
$('#actualizar').on('click', function() {
    const opcion = confirm('Este Reporte puede tardar un poco, ¿desea continuar?');

    if (opcion) {
        mostrarCargando();
        const valores = obtenerValoresCampos();
        realizarPeticionAjax("ListaSinMovConExis_ajax.php", valores, function(response) {
            $('#contenidosmovcexis').html(response);
            $('.bodyp').unblock();
        });
    }
});

// Función para buscar en la tabla
function doSearch(e) {
    const code = (e.keyCode ? e.keyCode : e.which);

    if (code == 13) {
        return false;
    } else {
        const tableReg = document.getElementById('tabledatos');
        const searchText = document.getElementById('searchTerm').value.toLowerCase();

        // Recorremos todas las filas con contenido de la tabla
        for (let i = 1; i < tableReg.rows.length; i++) {
            const cellsOfRow = tableReg.rows[i].getElementsByTagName('td');
            let found = false;

            // Recorremos todas las celdas
            for (let j = 0; j < cellsOfRow.length && !found; j++) {
                const compareWith = cellsOfRow[j].innerHTML.toLowerCase();

                // Buscamos el texto en el contenido de la celda
                if (searchText.length === 0 || (compareWith.indexOf(searchText) > -1)) {
                    found = true;
                }
            }

            // Mostrar u ocultar la fila según si se encontró coincidencia
            tableReg.rows[i].style.display = found ? '' : 'none';
        }
    }
}