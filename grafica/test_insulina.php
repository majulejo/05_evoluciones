<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Insulina</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-group { margin: 10px 0; }
        label { display: inline-block; width: 150px; }
        input, select { padding: 5px; }
        button { padding: 10px 20px; margin: 5px; }
        .result { margin: 20px 0; padding: 10px; background: #f0f0f0; }
    </style>
</head>
<body>
    <h2>Prueba de Funcionalidad de Insulina</h2>
    
    <h3>1. Calcular Insulina por Glucemia</h3>
    <form id="glucemiaForm">
        <div class="form-group">
            <label>Box:</label>
            <input type="number" id="box" value="1" min="1" max="12">
        </div>
        <div class="form-group">
            <label>Hora:</label>
            <input type="time" id="hora" value="14:00">
        </div>
        <div class="form-group">
            <label>Glucemia (mg/dL):</label>
            <input type="number" id="glucemia" placeholder="ej: 250" min="50" max="600">
        </div>
        <button type="button" onclick="calcularInsulina()">Calcular Insulina</button>
    </form>
    
    <h3>2. Guardar Insulina Manual</h3>
    <form id="manualForm">
        <div class="form-group">
            <label>Box:</label>
            <input type="number" id="boxManual" value="1" min="1" max="12">
        </div>
        <div class="form-group">
            <label>Hora:</label>
            <input type="time" id="horaManual" value="15:00">
        </div>
        <div class="form-group">
            <label>Modo:</label>
            <select id="modo" onchange="cambiarModo()">
                <option value="subcutanea">Subcutánea</option>
                <option value="iv">Intravenosa</option>
                <option value="mixta">Mixta</option>
            </select>
        </div>
        <div class="form-group" id="scGroup">
            <label>Unidades s/c:</label>
            <input type="number" id="unidadesSc" placeholder="ej: 14" min="0" max="50">
        </div>
        <div class="form-group" id="ivGroup" style="display:none;">
            <label>Unidades I.V.:</label>
            <input type="number" id="unidadesIv" placeholder="ej: 2.4" step="0.1" min="0" max="10">
        </div>
        <div class="form-group">
            <label>Observación:</label>
            <input type="text" id="observacion" placeholder="Opcional">
        </div>
        <button type="button" onclick="guardarInsulinaManual()">Guardar Insulina</button>
    </form>
    
    <h3>3. Obtener Datos de Oxigenación</h3>
    <button onclick="obtenerDatos()">Obtener Datos Box 1</button>
    
    <div id="resultado" class="result" style="display:none;"></div>

    <script>
        function cambiarModo() {
            const modo = document.getElementById('modo').value;
            const scGroup = document.getElementById('scGroup');
            const ivGroup = document.getElementById('ivGroup');
            
            if (modo === 'subcutanea') {
                scGroup.style.display = 'block';
                ivGroup.style.display = 'none';
            } else if (modo === 'iv') {
                scGroup.style.display = 'none';
                ivGroup.style.display = 'block';
            } else { // mixta
                scGroup.style.display = 'block';
                ivGroup.style.display = 'block';
            }
        }
        
        async function calcularInsulina() {
            const box = document.getElementById('box').value;
            const hora = document.getElementById('hora').value;
            const glucemia = document.getElementById('glucemia').value;
            
            if (!glucemia) {
                alert('Introduce un valor de glucemia');
                return;
            }
            
            try {
                const response = await fetch('api/calcular_insulina_por_glucemia.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        box: box,
                        hora: hora,
                        glucemia: parseFloat(glucemia)
                    })
                });
                
                const result = await response.json();
                mostrarResultado('Cálculo de Insulina', result);
            } catch (error) {
                mostrarResultado('Error', {success: false, message: error.message});
            }
        }
        
        async function guardarInsulinaManual() {
            const box = document.getElementById('boxManual').value;
            const hora = document.getElementById('horaManual').value;
            const modo = document.getElementById('modo').value;
            const unidadesSc = document.getElementById('unidadesSc').value || 0;
            const unidadesIv = document.getElementById('unidadesIv').value || 0;
            const observacion = document.getElementById('observacion').value;
            
            try {
                const response = await fetch('api/guardar_insulina_manual.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        box: box,
                        hora: hora,
                        modo: modo,
                        unidades_sc: parseFloat(unidadesSc),
                        unidades_iv: parseFloat(unidadesIv),
                        observacion: observacion
                    })
                });
                
                const result = await response.json();
                mostrarResultado('Guardar Insulina Manual', result);
            } catch (error) {
                mostrarResultado('Error', {success: false, message: error.message});
            }
        }
        
        async function obtenerDatos() {
            try {
                const response = await fetch('api/obtener_oxigenacion.php?box=1');
                const result = await response.json();
                mostrarResultado('Datos de Oxigenación', result);
            } catch (error) {
                mostrarResultado('Error', {success: false, message: error.message});
            }
        }
        
        function mostrarResultado(titulo, data) {
            const div = document.getElementById('resultado');
            div.style.display = 'block';
            div.innerHTML = `<h4>${titulo}</h4><pre>${JSON.stringify(data, null, 2)}</pre>`;
        }
    </script>
</body>
</html>