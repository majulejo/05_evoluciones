/* ========================== */
/* extensions.js - JavaScript para Nuevas Zonas */
/* ========================== */

// Variables globales para extensiones
let glucemiaData = Array(24)
  .fill()
  .map(() => ({}));
let medicationData = Array(24)
  .fill()
  .map(() => ({}));
let balanceData = Array(24)
  .fill()
  .map(() => ({}));

/* ========================== */
/* INICIALIZACIÓN DE EXTENSIONES */
/* ========================== */

function initExtensions() {
  console.log("Inicializando extensiones...");
  createGlucemiaSection();
  // Aquí se pueden añadir más secciones en el futuro
  // createMedicationSection();
  // createBalanceSection();
}

/* ========================== */
/* ZONA DE GLUCEMIA */
/* ========================== */

function createGlucemiaSection() {
  const extensionsArea = document.getElementById("extensions-area");

  const glucemiaHTML = `
        <div class="glucemia-section">
            <div class="glucemia-header">
                <div class="glucemia-title">GLUCEMIA</div>
            </div>
            <div class="glucemia-grid">
                <div class="glucemia-scale">
                    <div class="glucemia-scale-title">mg/dL</div>
                    <div class="glucemia-scale-value">600</div>
                    <div class="glucemia-scale-value">500</div>
                    <div class="glucemia-scale-value">400</div>
                    <div class="glucemia-scale-value">300</div>
                    <div class="glucemia-scale-value">200</div>
                    <div class="glucemia-scale-value">100</div>
                    <div class="glucemia-scale-value">0</div>
                </div>
                <div class="glucemia-chart-area">
                    <div class="glucemia-hours-header">
                        <div class="glucemia-hour-cell">8</div>
                        <div class="glucemia-hour-cell">9</div>
                        <div class="glucemia-hour-cell">10</div>
                        <div class="glucemia-hour-cell">11</div>
                        <div class="glucemia-hour-cell">12</div>
                        <div class="glucemia-hour-cell">13</div>
                        <div class="glucemia-hour-cell">14</div>
                        <div class="glucemia-hour-cell">15</div>
                        <div class="glucemia-hour-cell">16</div>
                        <div class="glucemia-hour-cell">17</div>
                        <div class="glucemia-hour-cell">18</div>
                        <div class="glucemia-hour-cell">19</div>
                        <div class="glucemia-hour-cell">20</div>
                        <div class="glucemia-hour-cell">21</div>
                        <div class="glucemia-hour-cell">22</div>
                        <div class="glucemia-hour-cell">23</div>
                        <div class="glucemia-hour-cell">0</div>
                        <div class="glucemia-hour-cell">1</div>
                        <div class="glucemia-hour-cell">2</div>
                        <div class="glucemia-hour-cell">3</div>
                        <div class="glucemia-hour-cell">4</div>
                        <div class="glucemia-hour-cell">5</div>
                        <div class="glucemia-hour-cell">6</div>
                        <div class="glucemia-hour-cell">7</div>
                    </div>
                    <div class="glucemia-chart-grid" id="glucemiaGrid">
                        <!-- Las líneas horizontales y celdas se generarán con JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    `;

  extensionsArea.innerHTML = glucemiaHTML;
  setupGlucemiaGrid();
}

function setupGlucemiaGrid() {
  const glucemiaGrid = document.getElementById("glucemiaGrid");
  glucemiaGrid.innerHTML = "";

  // Añadir líneas horizontales
  for (let i = 0; i <= 5; i++) {
    const line = document.createElement("div");
    line.className = "glucemia-horizontal-line";
    glucemiaGrid.appendChild(line);
  }

  // Crear 24 celdas para glucemia
  for (let i = 0; i < 24; i++) {
    const hour = (i + 8) % 24;
    const cell = document.createElement("div");
    cell.className = "glucemia-grid-cell";
    cell.dataset.hour = hour;
    cell.dataset.index = i;

    // Tooltip para glucemia
    cell.innerHTML = `
            <div class="glucemia-tooltip">
                <span class="glucemia-value">--</span> mg/dL
            </div>
        `;

    // No añadir eventos de clic aquí, se manejará desde el modal principal
    glucemiaGrid.appendChild(cell);
  }

  // Asignar data-hour a las celdas de cabecera de glucemia
  const glucemiaHourCells = document.querySelectorAll(".glucemia-hour-cell");
  glucemiaHourCells.forEach((cell, index) => {
    const hour = (index + 8) % 24;
    cell.dataset.hour = hour;
  });
}

function updateGlucemiaChart() {
  // Limpiar elementos existentes
  document
    .querySelectorAll(".glucemia-point, .glucemia-line")
    .forEach((el) => el.remove());

  const grid = document.getElementById("glucemiaGrid");
  if (!grid) return;

  const gridWidth = grid.offsetWidth;
  const gridHeight = grid.offsetHeight;
  const cellWidth = gridWidth / 24;

  // Limpiar todas las clases has-data de glucemia
  document
    .querySelectorAll(".glucemia-grid-cell, .glucemia-hour-cell")
    .forEach((cell) => {
      cell.classList.remove("has-data");
    });

  // Array para almacenar puntos de glucemia para crear líneas
  const glucemiaPoints = [];

  // Obtener datos de glucemia del sistema principal
  const vitalSigns = getVitalSigns();

  vitalSigns.forEach((data, index) => {
    if (!data || !data.glucemia) return;

    const glucemiaCell = document.querySelector(
      `.glucemia-grid-cell[data-index="${index}"]`
    );
    const glucemiaHeaderCell = document.querySelector(
      `.glucemia-hour-cell[data-hour="${(index + 8) % 24}"]`
    );

    if (!glucemiaCell) return;

    // Marcar celdas como con datos
    glucemiaCell.classList.add("has-data");
    if (glucemiaHeaderCell) {
      glucemiaHeaderCell.classList.add("has-data");
    }

    // Posición X centrada en la franja
    const xCenter = index * cellWidth + cellWidth / 2;

    // Glucemia - Escala 0-600 mg/dL
    const y = gridHeight - (data.glucemia / 600) * gridHeight;
    createGlucemiaPoint(xCenter, y);
    glucemiaPoints.push({ x: xCenter, y, index });

    // Actualizar tooltip de glucemia
    const tooltipValue = glucemiaCell.querySelector(".glucemia-value");
    if (tooltipValue) {
      tooltipValue.textContent = data.glucemia;
    }
  });

  // Crear líneas conectoras para glucemia
  createGlucemiaConnectingLines(glucemiaPoints);
}

function createGlucemiaPoint(x, y) {
  const point = document.createElement("div");
  point.className = "glucemia-point";
  point.style.left = `${x}px`;
  point.style.top = `${y}px`;
  document.getElementById("glucemiaGrid").appendChild(point);
}

function createGlucemiaConnectingLines(points) {
  // Ordenar puntos por índice para conectar consecutivos
  points.sort((a, b) => a.index - b.index);

  for (let i = 0; i < points.length - 1; i++) {
    const start = points[i];
    const end = points[i + 1];

    // Calcular distancia y ángulo
    const dx = end.x - start.x;
    const dy = end.y - start.y;
    const distance = Math.sqrt(dx * dx + dy * dy);
    const angle = Math.atan2(dy, dx) * (180 / Math.PI);

    // Crear línea
    const line = document.createElement("div");
    line.className = "glucemia-line";
    line.style.left = `${start.x}px`;
    line.style.top = `${start.y}px`;
    line.style.width = `${distance}px`;
    line.style.height = "2px";
    line.style.transform = `rotate(${angle}deg)`;
    line.style.transformOrigin = "0 0";

    document.getElementById("glucemiaGrid").appendChild(line);
  }
}

/* ========================== */
/* ZONA DE MEDICACIÓN (PREPARADA PARA FUTURO) */
/* ========================== */

function createMedicationSection() {
  const extensionsArea = document.getElementById("extensions-area");

  const medicationHTML = `
        <div class="medication-section">
            <div class="medication-header">
                <div class="medication-title">MEDICACIÓN</div>
                <button class="add-medication-btn" onclick="addMedicationRow()">+ Añadir</button>
            </div>
            <div class="medication-grid">
                <div class="medication-labels" id="medicationLabels">
                    <!-- Se generarán dinámicamente -->
                </div>
                <div class="medication-chart-area">
                    <div class="medication-hours-header">
                        <!-- 24 celdas de horas -->
                    </div>
                    <div class="medication-chart-grid" id="medicationGrid">
                        <!-- Se generará dinámicamente -->
                    </div>
                </div>
            </div>
        </div>
    `;

  // Añadir después de la sección de glucemia
  const glucemiaSection = document.querySelector(".glucemia-section");
  if (glucemiaSection) {
    glucemiaSection.insertAdjacentHTML("afterend", medicationHTML);
  }
}

function addMedicationRow() {
  const medicationName = prompt("Introducir nombre del medicamento:");
  if (!medicationName) return;

  // Aquí se implementaría la lógica para añadir una nueva fila de medicación
  console.log(`Añadiendo medicamento: ${medicationName}`);
}

/* ========================== */
/* ZONA DE BALANCE HÍDRICO (PREPARADA PARA FUTURO) */
/* ========================== */

function createBalanceSection() {
  const extensionsArea = document.getElementById("extensions-area");

  const balanceHTML = `
        <div class="balance-section">
            <div class="balance-header">
                <div class="balance-title">BALANCE HÍDRICO</div>
            </div>
            <div class="balance-grid">
                <div class="balance-labels">
                    <div class="balance-label">INGRESOS</div>
                    <div class="balance-label">PÉRDIDAS</div>
                    <div class="balance-label">BALANCE</div>
                </div>
                <div class="balance-input-section">
                    <div class="balance-section-header">ENTRADAS (ml)</div>
                    <div class="balance-hours-grid">
                        <!-- 24 celdas de horas -->
                    </div>
                    <div class="balance-data-grid" id="balanceInputGrid">
                        <!-- Grid de inputs para ingresos -->
                    </div>
                </div>
                <div class="balance-output-section">
                    <div class="balance-section-header">SALIDAS (ml)</div>
                    <div class="balance-hours-grid">
                        <!-- 24 celdas de horas -->
                    </div>
                    <div class="balance-data-grid" id="balanceOutputGrid">
                        <!-- Grid de inputs para pérdidas -->
                    </div>
                </div>
            </div>
        </div>
    `;

  // Añadir después de la sección de medicación
  const medicationSection = document.querySelector(".medication-section");
  if (medicationSection) {
    medicationSection.insertAdjacentHTML("afterend", balanceHTML);
  } else {
    // Si no hay sección de medicación, añadir después de glucemia
    const glucemiaSection = document.querySelector(".glucemia-section");
    if (glucemiaSection) {
      glucemiaSection.insertAdjacentHTML("afterend", balanceHTML);
    }
  }
}

/* ========================== */
/* FUNCIÓN DE ACTUALIZACIÓN GLOBAL */
/* ========================== */

function updateExtensions(index, data) {
  // Actualizar gráfico de glucemia cuando cambian los datos principales
  updateGlucemiaChart();

  // Aquí se pueden añadir actualizaciones para otras extensiones
  // updateMedicationChart(index, data);
  // updateBalanceChart(index, data);
}

/* ========================== */
/* FUNCIONES DE UTILIDAD */
/* ========================== */

function showLoadingOverlay(containerId) {
  const container = document.getElementById(containerId);
  if (!container) return;

  const overlay = document.createElement("div");
  overlay.className = "loading-overlay";
  overlay.innerHTML = '<div class="loading-spinner"></div>';
  overlay.id = `${containerId}-loading`;

  container.style.position = "relative";
  container.appendChild(overlay);
}

function hideLoadingOverlay(containerId) {
  const overlay = document.getElementById(`${containerId}-loading`);
  if (overlay) {
    overlay.remove();
  }
}

function formatValue(value, unit = "") {
  if (value === undefined || value === null || value === "") {
    return "--";
  }
  return `${value}${unit}`;
}

function isValidGlucemiaValue(value) {
  return !isNaN(value) && value >= 0 && value <= 600;
}

/* ========================== */
/* EVENTOS GLOBALES PARA EXTENSIONES */
/* ========================== */

document.addEventListener("DOMContentLoaded", function () {
  // Escuchar cambios en el tamaño de ventana para reajustar gráficos de extensiones
  window.addEventListener("resize", function () {
    setTimeout(() => {
      updateGlucemiaChart();
      // Aquí se pueden añadir más actualizaciones de gráficos
    }, 100);
  });
});

/* ========================== */
/* FUNCIONES DE EXPORTACIÓN PARA EXTENSIONES */
/* ========================== */

function exportExtensionsData() {
  return {
    glucemia: glucemiaData,
    medication: medicationData,
    balance: balanceData,
    timestamp: new Date().toISOString(),
  };
}

function importExtensionsData(data) {
  if (data.glucemia) {
    glucemiaData = data.glucemia;
  }
  if (data.medication) {
    medicationData = data.medication;
  }
  if (data.balance) {
    balanceData = data.balance;
  }

  // Actualizar todas las visualizaciones
  updateGlucemiaChart();
}

/* ========================== */
/* FUNCIONES ADICIONALES PARA DEPURACIÓN */
/* ========================== */

function debugExtensions() {
  console.log("=== DEBUG EXTENSIONES ===");
  console.log("Datos vitales:", getVitalSigns());
  console.log("Datos glucemia:", glucemiaData);
  console.log("Datos medicación:", medicationData);
  console.log("Datos balance:", balanceData);

  // Verificar elementos DOM
  const glucemiaGrid = document.getElementById("glucemiaGrid");
  console.log("Grid glucemia:", glucemiaGrid ? "OK" : "NO ENCONTRADO");

  const extensionsArea = document.getElementById("extensions-area");
  console.log("Área extensiones:", extensionsArea ? "OK" : "NO ENCONTRADO");
}

// Función para verificar que las extensiones se han cargado correctamente
function checkExtensionsLoaded() {
  if (typeof initExtensions === "function") {
    console.log("✅ Extensions.js cargado correctamente");
    return true;
  } else {
    console.error("❌ Error al cargar extensions.js");
    return false;
  }
}

// Ejecutar verificación al cargar
console.log("Extensions.js cargado - versión completa");
checkExtensionsLoaded();
