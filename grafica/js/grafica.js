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

// Simplificar la función initGridCells
function initGridCells() {
  const cells = document.querySelectorAll(".grid-cell");
  cells.forEach((cell, index) => {
    const hour = (index + 8) % 24;
    cell.dataset.hour = hour;
    cell.dataset.index = index;

    // Limpiar contenido existente y establecer estructura básica
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
  const tooltipContainer = cell.querySelector(".tooltip-container");
  tooltipContainer.innerHTML = "";
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

  // Prevenir el cierre accidental
  document.querySelector(".modal-content").addEventListener("click", (e) => {
    e.stopPropagation();
  });
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
  updateTooltip(currentIndex, vitalSigns[currentIndex]);
  closeDataModal();

  // Opcional: Mostrar confirmación en consola
  console.log("Datos guardados:", vitalSigns[currentIndex]);
}

function updateChart() {
  // Limpiar elementos existentes
  document
    .querySelectorAll(".grid-cell.has-data, .horizontal-line.has-data")
    .forEach((el) => {
      el.classList.remove("has-data");
    });

  const grid = document.getElementById("chartGrid");
  const gridWidth = grid.offsetWidth;
  const gridHeight = grid.offsetHeight;
  const cellWidth = gridWidth / 24;

  // Offsets horizontales para cada tipo de punto
  const offsets = {
    FR: -18,
    Temp: -6,
    FC: 6,
    TA: 18,
  };

  // Variables para almacenar puntos previos y crear conexiones
  let prevPoints = {
    FR: null,
    Temp: null,
    FC: null,
    TA: null,
  };

  vitalSigns.forEach((data, index) => {
    if (!data || Object.keys(data).length === 0) {
      // Resetear puntos previos si no hay datos en esta hora
      prevPoints = {
        FR: null,
        Temp: null,
        FC: null,
        TA: null,
      };
      return;
    }

    const x = index * cellWidth + cellWidth / 2; // <-- Añadir esta línea
    const cell = document.querySelector(`.grid-cell[data-index="${index}"]`);
    if (cell) {
      cell.classList.add("has-data");

      // También marcamos la línea horizontal correspondiente
      const lineIndex = Math.floor((index / 24) * 10); // Aproximación a las líneas
      const lines = document.querySelectorAll(".horizontal-line");
      if (lines[lineIndex]) {
        lines[lineIndex].classList.add("has-data");
      }
    }

    // FR (Frecuencia Respiratoria)
    if (data.respRate !== undefined) {
      const y = gridHeight - (data.respRate / 50) * gridHeight;
      createPoint(x + offsets.FR, y, "resp-point", "#000");

      // Conectar con punto anterior si existe
      if (prevPoints.FR) {
        createConnectionLine(
          prevPoints.FR.x,
          prevPoints.FR.y,
          x + offsets.FR,
          y,
          "resp-line"
        );
      }
      prevPoints.FR = { x: x + offsets.FR, y };
    }

    // Temperatura
    if (data.temperature !== undefined) {
      const y = gridHeight - ((data.temperature - 32) / 10) * gridHeight;
      createPoint(x + offsets.Temp, y, "temp-point", "#d63384");

      if (prevPoints.Temp) {
        createConnectionLine(
          prevPoints.Temp.x,
          prevPoints.Temp.y,
          x + offsets.Temp,
          y,
          "temp-line"
        );
      }
      prevPoints.Temp = { x: x + offsets.Temp, y };
    }

    // FC (Frecuencia Cardíaca)
    if (data.pulse !== undefined) {
      const y = gridHeight - (data.pulse / 200) * gridHeight;
      createPoint(x + offsets.FC, y, "pulse-point", "#0d6efd");

      if (prevPoints.FC) {
        createConnectionLine(
          prevPoints.FC.x,
          prevPoints.FC.y,
          x + offsets.FC,
          y,
          "pulse-line"
        );
      }
      prevPoints.FC = { x: x + offsets.FC, y };
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
      line.style.backgroundColor = "#198754";
      grid.appendChild(line);

      // Puntos de TA
      createPoint(x + offsets.TA, ySys, "bp-point", "#198754");
      createPoint(x + offsets.TA, yDias, "bp-point", "#198754");

      // Conectar punto medio con registro anterior
      const yMid = (ySys + yDias) / 2;
      if (prevPoints.TA) {
        createConnectionLine(
          prevPoints.TA.x,
          prevPoints.TA.y,
          x + offsets.TA,
          yMid,
          "bp-line"
        );
      }
      prevPoints.TA = { x: x + offsets.TA, y: yMid };
    }
  });
}

// Función para crear puntos mejorada
function createPoint(x, y, className, color) {
  const point = document.createElement("div");
  point.className = `chart-point ${className}`;
  point.style.cssText = `
    width: 8px;
    height: 8px;
    left: ${x}px;
    top: ${y}px;
    background-color: ${color};
    transform: translate(-50%, -50%);
    border-radius: 50%;
    position: absolute;
    z-index: 2;
    border: 1px solid white;
  `;
  document.getElementById("chartGrid").appendChild(point);
}

// Nueva función para crear líneas de conexión
function createConnectionLine(x1, y1, x2, y2, lineClass) {
  const line = document.createElement("div");
  line.className = `connection-line ${lineClass}`;

  // Calcular longitud y ángulo de la línea
  const length = Math.sqrt(Math.pow(x2 - x1, 2) + Math.pow(y2 - y1, 2));
  const angle = (Math.atan2(y2 - y1, x2 - x1) * 180) / Math.PI;

  line.style.cssText = `
    position: absolute;
    left: ${x1}px;
    top: ${y1}px;
    width: ${length}px;
    height: 2px;
    background-color: inherit;
    transform-origin: 0 0;
    transform: rotate(${angle}deg);
    z-index: 1;
  `;

  document.getElementById("chartGrid").appendChild(line);
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
    // Resto del código existente...
  } else {
    cell.classList.remove("has-data");
    // Resto del código existente...
  }
}
