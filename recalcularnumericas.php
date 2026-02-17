<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consulta de Indicadores</title>
    <style>
        .formulario {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            margin: 30px auto;
        }

        h2 {
            text-align: center;
            color: #0A2963;
        }

        label {
            font-weight: bold;
            display: block;
            margin-bottom: 8px;
        }

        select, button {
            width: 100%;
            padding: 10px;
            margin-top: 8px;
            margin-bottom: 20px;
            border-radius: 8px;
            border: 1px solid #ccc;
        }

        button {
            background-color: #0A2963;
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }

        button:hover {
            background-color: #083577;
        }

        .resultado {
            background-color: #eef3f9;
            padding: 15px;
            border-radius: 8px;
            font-size: 14px;
            overflow-x: auto;
            max-height: 300px;
        }

        .titulo-respuesta {
            font-weight: bold;
            color: #0A2963;
            margin-top: 20px;
        }

        #spinner {
            display: none;
            margin-bottom: 15px;
            text-align: center;
        }

        .spinner-icon {
            border: 4px solid #ccc;
            border-top: 4px solid #0A2963;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            animation: spin 1s linear infinite;
            display: inline-block;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .mensaje-exito {
            color: green;
            font-weight: bold;
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="formulario" id="formulario">
    <h2>Recalcular Indicadores Numérica</h2>

    <form id="formRecalculo">
        <label for="mes">Selecciona un mes:</label>
        <select name="mes" id="mes" required>
            <option value="">-- Elige un mes --</option>
            <option value="1">Enero</option>
            <option value="2">Febrero</option>
            <option value="3">Marzo</option>
            <option value="4">Abril</option>
            <option value="5">Mayo</option>
            <option value="6">Junio</option>
            <option value="7">Julio</option>
            <option value="8">Agosto</option>
            <option value="9">Septiembre</option>
            <option value="10">Octubre</option>
            <option value="11">Noviembre</option>
            <option value="12">Diciembre</option>
        </select>

        <button type="submit" id="btnSubmit">Recalcular</button>

        <div id="spinner" style="display:none;">
            <div class="spinner-icon"></div>
            <div>Cargando, por favor espera...</div>
        </div>
    </form>

    <div id="mensaje-exito" class="mensaje-exito" style="display:none;"></div>
    <div id="resultado1" class="resultado" style="display:none;"></div>
    <div id="resultado2" class="resultado" style="display:none;"></div>
</div>

<script>
document.getElementById('formRecalculo').addEventListener('submit', function(e) {
    e.preventDefault();
    const mes = document.getElementById('mes').value;
    if (!mes) return;

    document.getElementById('spinner').style.display = 'block';
    document.getElementById('btnSubmit').disabled = true;

    fetch('recalcularnumericas_ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'mes=' + mes
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('spinner').style.display = 'none';
        document.getElementById('btnSubmit').disabled = false;

        if (data.success) {
            document.getElementById('mensaje-exito').innerText = '✔ Indicadores recalculados correctamente.';
            document.getElementById('mensaje-exito').style.display = 'block';

            //document.getElementById('resultado1').innerHTML = "<strong>r_numerica_general.php</strong><br>" + data.resultado1;
            //document.getElementById('resultado2').innerHTML = "<strong>r_numerica.php</strong><br>" + data.resultado2;
            //document.getElementById('resultado1').style.display = 'block';
            //document.getElementById('resultado2').style.display = 'block';
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(err => {
        document.getElementById('spinner').style.display = 'none';
        document.getElementById('btnSubmit').disabled = false;
        alert('Ocurrió un error en la solicitud AJAX');
    });
});
</script>


</body>
</html>
