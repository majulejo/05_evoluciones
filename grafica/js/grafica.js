// Configuración de escalas
const SCALE_CONFIG = {
  FR: { min: 0, max: 60, offset: -18, color: "#000", unit: "rpm" },
  Temp: { min: 32, max: 42, offset: -6, color: "#d63384", unit: "°C" },
  FC: { min: 0, max: 200, offset: 6, color: "#0d6efd", unit: "lpm" },
  TA: { min: 0, max: 260, offset: 18, color: "#198754", unit: "mmHg" }
};

// Datos del paciente
let vitalSigns = Array(24).fill().map(() => ({}));
let currentIndex = null;

// Inicialización
document.addEventListener("DOMContentLoaded", function() {
  setupGridCells();
  initModal();
  setupResponsive();
});

// Configurar celdas de la gráfica
function setupGridCells() {
  const chartGrid = document.getElementById("chartGrid");
  chartGrid.innerHTML = "";

  // Añadir líneas horizontales de referencia
  for (let i = 0; i <= 10; i++) {
    const line = document.createElement("div");
    line.className = "horizontal-line";
    chartGrid.appendChild(line);
  }

  // Crear 24 celdas (de 8:00 a 7:00)
  for (let i = 0; i < 24; i++) {
    const hour = (i + 8) % 24;
    const cell = document.createElement("div");
    cell.className = "grid-cell";
    cell.dataset.hour = hour;
    cell.dataset.index = i;

    cell.innerHTML = `
      <div class="hour-data-indicator"></div>
      <div class="tooltip-container"></div>
    `;

    cell.addEventListener("click", () => openDataModal(hour, i));
    chartGrid.appendChild(cell);
  }
}

// Inicializar modal
function initModal() {
  document.getElementById("dataModal").addEventListener("click", (e) => {
    if (e.target === document.getElementById("dataModal")) {
      closeDataModal();
    }
  });

  document.getElementById("saveDataBtn").addEventListener("click", saveData);
  document.getElementById("deleteDataBtn").addEventListener("click", deleteData);
  document.getElementById("cancelBtn").addEventListener("click", closeDataModal);
  
  document.querySelector('.modal-content').addEventListener('click', (e) => {
    e.stopPropagation();
  });
}

// Abrir modal para introducir datos
function openDataModal(hour, index) {
  currentIndex = index;
  const modal = document.getElementById("dataModal");
  document.getElementById("modalHour").textContent = `${hour}:00`;

  const data = vitalSigns[index] || {};
  document.getElementById("respRate").value = data.respRate || "";
  document.getElementById("temperature").value = data.temperature || "";
  document.getElementById("pulse").value = data.pulse || "";
  document.getElementById("systolic").value = data.systolic || "";
  document.getElementById("diastolic").value = data.diastolic || "";

  modal.style.display = "block";
}

// Cerrar modal
function closeDataModal() {
  document.getElementById("dataModal").style.display = "none";
}

// Guardar datos
function saveData() {
  const getValue = (id) => {
    const val = parseFloat(document.getElementById(id).value);
    return isNaN(val) ? undefined : val;
  };

  vitalSigns[currentIndex] = {
    respRate: getValue("respRate"),
    temperature: getValue("temperature"),
    pulse: getValue("pulse"),
    systolic: getValue("systolic"),
    diastolic: getValue("diastolic")
  };

  updateChart();
  updateTooltip(currentIndex, vitalSigns[currentIndex]);
  closeDataModal();
}

// Eliminar datos
function deleteData() {
  vitalSigns[currentIndex] = {};
  updateChart();
  closeDataModal();
}

// Actualizar gráfico
function updateChart() {
  // Limpiar elementos anteriores
  document.querySelectorAll(".chart-point, .connection-line, .bp-line").forEach(el => el.remove());
  document.querySelectorAll(".grid-cell.has-data").forEach(el => el.classList.remove("has-data"));

  const grid = document.getElementById("chartGrid");
  const gridWidth = grid.offsetWidth;
  const gridHeight = grid.offsetHeight;
  const cellWidth = gridWidth / 24;

  // Variables para puntos previos
  let prevPoints = { FR: null, Temp: null, FC: null, TA: null };

  vitalSigns.forEach((data, index) => {
    if (!data || Object.keys(data).length === 0) {
      prevPoints = { FR: null, Temp: null, FC: null, TA: null };
      return;
    }

    const x = index * cellWidth + cellWidth / 2;
    const cell = document.querySelector(`.grid-cell[data-index="${index}"]`);
    
    if (cell) {
      cell.classList.add("has-data");
      updateTooltip(index, data);
    }

    // Frecuencia Respiratoria
    if (data.respRate !== undefined) {
      const y = gridHeight - ((data.respRate - SCALE_CONFIG.FR.min) / (SCALE_CONFIG.FR.max - SCALE_CONFIG.FR.min)) * gridHeight;
      createPoint(x + SCALE_CONFIG.FR.offset, y, 'resp-point', SCALE_CONFIG.FR.color);
      
      if (prevPoints.FR) {
        createConnectionLine(prevPoints.FR.x, prevPoints.FR.y, x + SCALE_CONFIG.FR.offset, y, 'resp-line');
      }
      prevPoints.FR = { x: x + SCALE_CONFIG.FR.offset, y };
    }

    // Temperatura
    if (data.temperature !== undefined) {
      const y = gridHeight - ((data.temperature - SCALE_CONFIG.Temp.min) / (SCALE_CONFIG.Temp.max - SCALE_CONFIG.Temp.min)) * gridHeight;
      createPoint(x + SCALE_CONFIG.Temp.offset, y, 'temp-point', SCALE_CONFIG.Temp.color);
      
      if (prevPoints.Temp) {
        createConnectionLine(prevPoints.Temp.x, prevPoints.Temp.y, x + SCALE_CONFIG.Temp.offset, y, 'temp-line');
      }
      prevPoints.Temp = { x: x + SCALE_CONFIG.Temp.offset, y };
    }

    // Frecuencia Cardíaca
    if (data.pulse !== undefined) {
      const y = gridHeight - ((data.pulse - SCALE_CONFIG.FC.min) / (SCALE_CONFIG.FC.max - SCALE_CONFIG.FC.min)) * gridHeight;
      createPoint(x + SCALE_CONFIG.FC.offset, y, 'pulse-point', SCALE_CONFIG.FC.color);
      
      if (prevPoints.FC) {
        createConnectionLine(prevPoints.FC.x, prevPoints.FC.y, x + SCALE_CONFIG.FC.offset, y, 'pulse-line');
      }
      prevPoints.FC = { x: x + SCALE_CONFIG.FC.offset, y };
    }

    // Tensión Arterial
    if (data.systolic !== undefined && data.diastolic !== undefined) {
      const ySys = gridHeight - ((data.systolic - SCALE_CONFIG.TA.min) / (SCALE_CONFIG.TA.max - SCALE_CONFIG.TA.min)) * gridHeight;
      const yDias = gridHeight - ((data.diastolic - SCALE_CONFIG.TA.min) / (SCALE_CONFIG.TA.max - SCALE_CONFIG.TA.min)) * gridHeight;
      
      // Línea vertical
      const line = document.createElement('div');
      line.className = 'bp-line';
      line.style.cssText = `
        position: absolute;
        left: ${x + SCALE_CONFIG.TA.offset}px;
        top: ${ySys}px;
        height: ${yDias - ySys}px;
        width: 1px;
        background-color: ${SCALE_CONFIG.TA.color};
        z-index: 1;
      `;
      grid.appendChild(line);
      
      // Puntos
      createPoint(x + SCALE_CONFIG.TA.offset, ySys, 'bp-point', SCALE_CONFIG.TA.color);
      createPoint(x + SCALE_CONFIG.TA.offset, yDias, 'bp-point', SCALE_CONFIG.TA.color);
    }
  });
}

// Crear punto en el gráfico
function createPoint(x, y, className, color) {
  const point = document.createElement("div");
  point.className = `chart-point ${className}`;
  point.style.cssText = `
    left: ${x}px;
    top: ${y}px;
    background-color: ${color};
  `;
  document.getElementById("chartGrid").appendChild(point);
}

// Crear línea de conexión
function createConnectionLine(x1, y1, x2, y2, lineClass) {
  const line = document.createElement("div");
  line.className = `connection-line ${lineClass}`;

  const length = Math.sqrt(Math.pow(x2 - x1, 2) + Math.pow(y2 - y1, 2));
  const angle = (Math.atan2(y2 - y1, x2 - x1) * 180 / Math.PI;

  line.style.cssText = `
    left: ${x1}px;
    top: ${y1}px;
    width: ${length}px;
    transform: rotate(${angle}deg);
    background-color: inherit;
  `;

  document.getElementById("chartGrid").appendChild(line);
}

// Actualizar tooltip
function updateTooltip(index, data) {
  const cell = document.querySelector(`.grid-cell[data-index="${index}"]`);
  if (!cell) return;

  const tooltip = cell.querySelector('.tooltip-container');
  if (!tooltip) return;

  tooltip.innerHTML = `
    <div class="tooltip-content">
      <div><strong>Hora:</strong> ${(index + 8) % 24}:00</div>
      ${data.respRate ? `<div><strong>FR:</strong> ${data.respRate} rpm</div>` : ''}
      ${data.temperature ? `<div><strong>Tª:</strong> ${data.temperature}°C</div>` : ''}
      ${data.pulse ? `<div><strong>FC:</strong> ${data.pulse} lpm</div>` : ''}
      ${data.systolic && data.diastolic ? 
        `<div><strong>TA:</strong> ${data.systolic}/${data.diastolic} mmHg</div>` : ''}
    </div>
  `;
}

// Configurar responsive
function setupResponsive() {
  window.addEventListener("resize", () => {
    if (vitalSigns.some(data => data && Object.keys(data).length > 0)) {
      setTimeout(updateChart, 100);
    }
  });

  document.querySelectorAll(".grid-cell").forEach((cell) => {
    cell.addEventListener("touchend", (e) => {
      e.preventDefault();
      cell.click();
    });
  });
}