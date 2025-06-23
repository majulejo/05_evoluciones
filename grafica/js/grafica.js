// Configuración de escalas con offsets verticales
const SCALE_CONFIG = {
  FR: { min: 0, max: 50, offset: -20, color: "#000", unit: "rpm" },
  Temp: { min: 32, max: 42, offset: 0, color: "#d63384", unit: "°C" },
  FC: { min: 0, max: 200, offset: 20, color: "#0d6efd", unit: "lpm" },
  TA: { min: 0, max: 250, offset: 0, color: "#198754", unit: "mmHg" },
};

// Datos y estado
let vitalSigns = Array(24)
  .fill()
  .map(() => ({}));
let currentIndex = null;

// Inicialización
document.addEventListener("DOMContentLoaded", function () {
  setupGridCells(); // Usar la función que crea todas las celdas
  initModal();
  setupResponsive();
});

// Modificar la función initGridCells para simplificarla
function initGridCells() {
  const cells = document.querySelectorAll(".grid-cell");
  cells.forEach((cell, index) => {
    const hour = (index + 8) % 24;
    cell.dataset.hour = hour;
    cell.dataset.index = index;

    // Limpiar contenido existente
    cell.innerHTML = `
      <div class="hour-data-indicator"></div>
      <div class="tooltip-container"></div>
    `;

    // Eventos
    cell.addEventListener("click", () => {
      currentIndex = index;
      openDataModal(hour);
    });

    cell.addEventListener("mouseenter", () => showTooltip(cell, index));
    cell.addEventListener("mouseleave", () => hideTooltip(cell));
  });
}

// Optimizar la función showTooltip
function showTooltip(cell, index) {
  const data = vitalSigns[index];
  if (!data || Object.keys(data).length === 0) return;

  const tooltipContainer = cell.querySelector(".tooltip-container");

  // Limpiar tooltip existente
  tooltipContainer.innerHTML = "";

  const tooltip = document.createElement("div");
  tooltip.className = "vital-signs-tooltip";
  tooltip.innerHTML = `
    <div class="tooltip-content">
      <div class="tooltip-row"><span>FR:</span> ${
        data.respRate || "--"
      } rpm</div>
      <div class="tooltip-row"><span>FC:</span> ${data.pulse || "--"} lpm</div>
      <div class="tooltip-row"><span>TA:</span> ${data.systolic || "--"}/${
    data.diastolic || "--"
  } mmHg</div>
      <div class="tooltip-row"><span>Tª:</span> ${
        data.temperature || "--"
      }°C</div>
    </div>
  `;

  tooltipContainer.appendChild(tooltip);
  tooltip.style.opacity = "1";
}

function hideTooltip(cell) {
  const tooltip = cell.querySelector(".vital-signs-tooltip");
  if (tooltip) {
    tooltip.style.opacity = "0";
  }
}

function initModal() {
  // Cerrar modal al hacer clic fuera
  document.getElementById("dataModal").addEventListener("click", (e) => {
    if (e.target === document.getElementById("dataModal")) {
      closeDataModal();
    }
  });

  // Botones del modal
  document.getElementById("saveDataBtn").addEventListener("click", saveData);
  document
    .getElementById("deleteDataBtn")
    .addEventListener("click", deleteData);
  document
    .getElementById("cancelBtn")
    .addEventListener("click", closeDataModal);
}

function openDataModal(hour, index) {
  currentIndex = index; // Asegurar que se guarda el índice

  const modal = document.getElementById("dataModal");
  document.getElementById("modalHour").textContent = `${hour}:00`;

  // Cargar datos existentes (usar el índice correcto)
  const data = vitalSigns[index] || {};
  document.getElementById("respRate").value = data.respRate || "";
  document.getElementById("temperature").value = data.temperature || "";
  document.getElementById("pulse").value = data.pulse || "";
  document.getElementById("systolic").value = data.systolic || "";
  document.getElementById("diastolic").value = data.diastolic || "";

  modal.style.display = "block";
}

function closeDataModal() {
  document.getElementById("dataModal").style.display = "none";
}

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
    diastolic: getValue("diastolic"),
  };

  updateChart();
  updateTooltip(currentIndex, vitalSigns[currentIndex]); // Actualizar tooltip
  closeDataModal();
}
function deleteData() {
  vitalSigns[currentIndex] = {};
  updateChart();
  closeDataModal();
}

function updateChart() {
  // Limpiar elementos existentes
  document
    .querySelectorAll(".chart-point, .chart-line")
    .forEach((el) => el.remove());

  const grid = document.getElementById("chartGrid");
  const gridWidth = grid.offsetWidth;
  const gridHeight = grid.offsetHeight;
  const cellWidth = gridWidth / 24;

  // Offsets horizontales para cada tipo de punto
  // Cambia los offsets a estos valores:
  const offsets = {
    FR: -18, // Aumentado de -15
    Temp: -6, // Aumentado de -5
    FC: 6, // Aumentado de 5
    TA: 18, // Aumentado de 15
  };

  vitalSigns.forEach((data, index) => {
    if (!data || Object.keys(data).length === 0) return;

    const x = index * cellWidth + cellWidth / 2;
    const cell = document.querySelector(`.grid-cell[data-index="${index}"]`);
    cell.classList.add("has-data");

    // FR (Frecuencia Respiratoria)
    if (data.respRate !== undefined) {
      const y = gridHeight - (data.respRate / 50) * gridHeight;
      createPoint(x + offsets.FR, y, "resp-point");
    }

    // Temperatura
    if (data.temperature !== undefined) {
      const y = gridHeight - ((data.temperature - 32) / 10) * gridHeight;
      createPoint(x + offsets.Temp, y, "temp-point");
    }

    // FC (Frecuencia Cardíaca)
    if (data.pulse !== undefined) {
      const y = gridHeight - (data.pulse / 200) * gridHeight;
      createPoint(x + offsets.FC, y, "pulse-point");
    }

    // TA (Tensión Arterial)
    if (data.systolic !== undefined && data.diastolic !== undefined) {
      const ySys = gridHeight - (data.systolic / 250) * gridHeight;
      const yDias = gridHeight - (data.diastolic / 250) * gridHeight;

      // Línea vertical para TA
      const line = document.createElement("div");
      line.className = "chart-line bp-line";
      line.style.left = `${x + offsets.TA}px`;
      line.style.top = `${ySys}px`;
      line.style.height = `${yDias - ySys}px`;
      grid.appendChild(line);

      createPoint(x + offsets.TA, ySys, "bp-point");
      createPoint(x + offsets.TA, yDias, "bp-point");
    }
  });
}

function createPoint(x, y, className) {
  const point = document.createElement("div");
  point.className = `chart-point ${className}`;
  point.style.width = "6px"; // Reducido de 8px
  point.style.height = "6px"; // Reducido de 8px
  point.style.left = `${x}px`;
  point.style.top = `${y}px`;
  point.style.transform = "translate(-50%, -50%)";
  document.getElementById("chartGrid").appendChild(point);
}

function setupResponsive() {
  // Ajustar gráfico al redimensionar
  window.addEventListener("resize", () => {
    if (vitalSigns.some((data) => data && Object.keys(data).length > 0)) {
      setTimeout(updateChart, 100);
    }
  });

  // Soporte táctil para tablets/móviles
  document.querySelectorAll(".grid-cell").forEach((cell) => {
    cell.addEventListener("touchend", (e) => {
      e.preventDefault();
      cell.click();
    });
  });
}
function setupGridCells() {
  const chartGrid = document.getElementById("chartGrid");

  // Limpiar celdas existentes
  chartGrid.innerHTML = "";

  // Añadir líneas horizontales (si es necesario)
  for (let i = 0; i <= 10; i++) {
    const line = document.createElement("div");
    line.className = "horizontal-line";
    chartGrid.appendChild(line);
  }

  // Crear 24 celdas (de 8:00 a 7:00)
  for (let i = 0; i < 24; i++) {
    const hour = (i + 8) % 24; // Horas de 8 a 7
    const cell = document.createElement("div");
    cell.className = "grid-cell";
    cell.dataset.hour = hour;
    cell.dataset.index = i;

    // Estructura del tooltip
    cell.innerHTML = `
      <div class="hour-data-indicator"></div>
      <div class="tooltip-container">
        <div class="vital-signs-tooltip">
          <div class="tooltip-content">
            <div class="tooltip-row"><span>FR:</span> <span class="value-fr">--</span> rpm</div>
            <div class="tooltip-row"><span>FC:</span> <span class="value-fc">--</span> lpm</div>
            <div class="tooltip-row"><span>TA:</span> <span class="value-ta">--/--</span> mmHg</div>
            <div class="tooltip-row"><span>Tª:</span> <span class="value-temp">--</span>°C</div>
          </div>
        </div>
      </div>
    `;

    // Añadir eventos
    cell.addEventListener("click", () => openDataModal(hour, i));
    chartGrid.appendChild(cell);
  }
}

function updateTooltip(index, data) {
  const cell = document.querySelector(`.grid-cell[data-index="${index}"]`);
  if (!cell) return;

  if (data && Object.keys(data).length > 0) {
    cell.classList.add("has-data");
    cell.querySelector(".value-fr").textContent = data.respRate || "--";
    cell.querySelector(".value-fc").textContent = data.pulse || "--";
    cell.querySelector(".value-ta").textContent = `${data.systolic || "--"}/${
      data.diastolic || "--"
    }`;
    cell.querySelector(".value-temp").textContent = data.temperature || "--";
  } else {
    cell.classList.remove("has-data");
    // Restablecer valores por defecto
    ["fr", "fc", "ta", "temp"].forEach((type) => {
      cell.querySelector(`.value-${type}`).textContent = "--";
    });
  }
}
