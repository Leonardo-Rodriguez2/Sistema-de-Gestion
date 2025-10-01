<?php
// Inicia la sesión de PHP
session_start();

// Variable global para la tasa de cambio
$tasa_guardada = 0.00;
$fecha_sesion = 'N/A';

// Carga Inicial: Revisa si hay una tasa guardada en la sesión
if (isset($_SESSION['dolar_rate'])) {
    $tasa_guardada = $_SESSION['dolar_rate'];
    $fecha_sesion = $_SESSION['dolar_fecha'];
}
?>
<head>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

    <style>

        .compact-card {
            width: 100%;
            padding: 30px;
            height: 80vh;
        }

        .title-group {
            border-bottom: 2px solid #3498db; /* Línea de color debajo del título */
            padding-bottom: 15px;
            margin-bottom: 25px;
            text-align: center;
        }
        .title {
            color: #34495e;
            font-weight: 700;
            margin-bottom: 0 !important;
        }
        .subtitle {
            color: #7f8c8d;
            font-size: 1rem;
            margin-top: 5px;
        }

        /* Estilo para la Tasa Guardada */
        .rate-display {
            text-align: center;
            padding: 15px;
            margin-bottom: 25px;
            background-color: #ecf0f1; /* Fondo suave */
            border-radius: 6px;
        }
        .rate-display .value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #2ecc71; /* Verde destacado */
        }
        .rate-display .date {
            font-size: 0.8rem;
            color: #95a5a6;
            margin-top: 5px;
            display: block;
        }

        /* Campos de Conversión */
        .field-group {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        .field-group .field {
            flex: 1;
        }
        
        .input, .button {
            border-radius: 6px; /* Borde de botón e input más uniforme */
        }
        
        /* Mejorar el botón BCV */
        .btn-bcv {
            background-color: #3498db; /* Azul corporativo */
            border-color: #3498db;
        }
        .btn-bcv:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }
        
        /* Mejorar el botón Guardar */
        .btn-guardar {
            background-color: #2ecc71; /* Verde */
            border-color: #2ecc71;
        }
        .btn-guardar:hover {
            background-color: #27ae60;
            border-color: #27ae60;
        }
    </style>

    <div class="compact-card">
        <div class="title-group">
            <h1 class="title is-4"><i class="fas fa-money-check-alt fa-fw"></i> Control de Moneda</h1>
        </div>

        <div class="rate-display" style="height: 10rem;">
            Tasa Guardada: 1 USD = 
            <span id="valor-bcv" class="value"><?= number_format($tasa_guardada, 2); ?></span> Bs.
            <span id="fecha-actualizacion" class="date">
                Fecha Sesión: <span id="fecha-sesion-span"><?= $fecha_sesion; ?></span>
            </span>
        </div>

        <div class="field-group">
            <div class="field">
                <label class="label is-small">Dólar ($)</label>
                <div class="control">
                    <input class="input is-medium has-text-right" type="number" id="input-usd" value="1.00" min="0.01" step="0.01">
                </div>
            </div>
            
            <div class="field">
                <label class="label is-small">Bolívares (Bs.)</label>
                <div class="control">
                    <input class="input is-medium has-text-right" type="number" id="output-bs" value="<?= number_format($tasa_guardada, 2); ?>" step="0.01"> 
                </div>
            </div>
        </div>
        
        <div class="buttons is-centered mt-4">
            <button class="button is-info btn-bcv" id="btn-bcv">
                <span class="icon"><i class="fas fa-sync-alt"></i></span>
                <span>Cargar BCV</span>
            </button>
            <button class="button is-success btn-guardar" id="btn-guardar">
                <span class="icon"><i class="far fa-save"></i></span>
                <span>Guardar Tasa</span>
            </button>
        </div>

    </div>

    <script>
        // --- JavaScript (La lógica se mantiene funcional) ---

        const API_URL = "https://ve.dolarapi.com/v1/dolares/oficial";
        const SAVE_URL = "save_rate.php"; 
        const inputUsd = document.getElementById('input-usd');
        const outputBs = document.getElementById('output-bs');
        const valorBcvSpan = document.getElementById('valor-bcv');
        const fechaSesionSpan = document.getElementById('fecha-sesion-span');
        const btnBcv = document.getElementById('btn-bcv');
        const btnGuardar = document.getElementById('btn-guardar');

        // Tasa de cambio global, cargada desde PHP
        let currentRate = <?= $tasa_guardada ?>; 

        // 1. Funciones de Conversión (Misma lógica bidireccional)
        function calculateConversion(source) {
            const usdValue = parseFloat(inputUsd.value);
            const bsValue = parseFloat(outputBs.value);
            
            if (currentRate === 0) return;

            if (source === 'usd') {
                if (isNaN(usdValue) || usdValue < 0) { outputBs.value = '0.00'; return; }
                const newBsValue = usdValue * currentRate;
                outputBs.value = newBsValue.toFixed(2); 

            } else if (source === 'bs') {
                if (isNaN(bsValue) || bsValue < 0) { inputUsd.value = '0.00'; return; }
                const newUsdValue = bsValue / currentRate;
                inputUsd.value = newUsdValue.toFixed(2); 
            }
        }
        
        // 2. Cargar Tasa del BCV (API)
        async function fetchBcvRate() {
            btnBcv.classList.add('is-loading');

            try {
                const response = await fetch(API_URL);
                const data = await response.json();
                
                const officialRate = parseFloat(data.promedio); 
                const updateDate = data.fecha_actualizacion;

                if (isNaN(officialRate) || officialRate <= 0) throw new Error("Valor inválido.");

                currentRate = officialRate; 
                valorBcvSpan.textContent = currentRate.toFixed(2);
                
                const dateOptions = { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' };
                const formattedDate = new Date(updateDate).toLocaleDateString('es-VE', dateOptions);
                fechaSesionSpan.textContent = `BCV (${formattedDate})`;

                inputUsd.value = '1.00'; 
                calculateConversion('usd'); 

            } catch (error) {
                console.error("Error al obtener la tasa BCV:", error);
                alert("No se pudo obtener la tasa BCV. Usando la tasa de sesión.");
            } finally {
                btnBcv.classList.remove('is-loading');
            }
        }

        // 3. Guardar en la Sesión ($SESSION) usando PHP
        btnGuardar.addEventListener('click', async function() {
            const rateToSave = currentRate.toFixed(4); 
            
            if (currentRate <= 0) {
                alert('La tasa debe ser mayor a cero para guardar.');
                return;
            }
            
            btnGuardar.classList.add('is-loading');

            try {
                const response = await fetch(SAVE_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ rate: rateToSave })
                });

                if (!response.ok) throw new Error("Error del servidor.");
                
                const result = await response.json();

                if (result.success) {
                    alert(`Tasa ${rateToSave} guardada exitosamente en la $SESSION.`);
                    const now = new Date().toLocaleTimeString('es-VE');
                    fechaSesionSpan.textContent = `GUARDADO (${now})`; 
                } else {
                    alert('Error al guardar la sesión.');
                }
            } catch (error) {
                console.error("Error al guardar:", error);
                alert('No se pudo guardar la sesión.');
            } finally {
                btnGuardar.classList.remove('is-loading');
            }
        });

        // 4. Listeners y Carga Inicial
        inputUsd.addEventListener('input', () => calculateConversion('usd'));
        outputBs.addEventListener('input', () => calculateConversion('bs'));

        window.onload = function() {
            if (currentRate === 0) {
                fetchBcvRate();
            } else {
                calculateConversion('usd'); 
            }
        };

    </script>