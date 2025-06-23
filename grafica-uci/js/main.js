/* ========================== */
/* main.js - JavaScript Principal */
/* ========================== */

// Variables globales
let vitalSigns = Array(24)
  .fill()
  .map(() => ({}));
let currentIndex = null;

/* ========================== */
/* INICIALIZACIÓN */
/* ========================== */

document.addEventListener("DOMContentLoaded", function () {
  console.log("Inicializando aplicación principal...");
  setupGridCells();
  initModal();
  setupResponsive();

  // Inicializar extensiones si están disponibles
  if (typeof initExtensions === "function") {
    initExtensions();
  }
});

/* ========================== */
/* CONFIGURACIÓN DE CELDAS */
/* ========================== */

function setupGridCells() {
  const chartGrid = document.getElementById("chartGrid");
  chartGrid.innerHTML = "";

  // Añadir líneas horizontales
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

    // Estructura del tooltip
    cell.innerHTML = `
            <div class="vital-signs-tooltip">
                <div class="tooltip-content">
                    <div class="tooltip-row"><span style="color: #000;">FR:</span> <span class="value-fr">--</span> <span style="color: #000;">rpm</span></div>
                    <div class="tooltip-row"><span style="color: #0d6efd;">FC:</span> <span class="value-fc">--</span> <span style="color: #0d6efd;">lpm</span></div>
                    <div class="tooltip-row"><span style="color: #198754;">TA:</span> <span class="value-ta">--/--</span> <span style="color: #198754;">mmHg</span></div>
                    <div class="tooltip-row"><span style="color: #dc3545;">Tª:</span> <span class="value-temp">--</span> <span style="color: #dc3545;">°C</span></div>
                    <div class="tooltip-row"><span style="color: #6f42c1;">SatO2:</span> <span class="value-sato2">--</span> <span style="color: #6f42c1;">%</span></div>
                    <div class="tooltip-row"><span style="color: #dc3545;">Gluc:</span> <span class="value-glucemia">--</span> <span style="color: #dc3545;">mg/dL</span></div>
                </div>
            </div>
        `;

    // Eventos
    cell.addEventListener("click", () => openDataModal(hour, i));
    chartGrid.appendChild(cell);
  }

  // Asignar data-hour a las celdas de cabecera
  const hourCells = document.querySelectorAll(".hour-cell");
  hourCells.forEach((cell, index) => {
    const hour = (index + 8) % 24;
    cell.dataset.hour = hour;
  });
}

/* ========================== */
/* CONFIGURACIÓN DEL MODAL */
/* ========================== */

function initModal() {
  document.getElementById("dataModal").addEventListener("click", (e) => {
    if (e.target === document.getElementById("dataModal")) {
      closeDataModal();
    }
  });

  document.getElementById("saveDataBtn").addEventListener("click", saveData);
  document
    .getElementById("deleteDataBtn")
    .addEventListener("click", deleteData);
  document
    .getElementById("cancelBtn")
    .addEventListener("click", closeDataModal);

  // Configurar validación en tiempo real para todos los campos
  setupFieldValidation("respRate", 0, 50);
  setupFieldValidation("temperature", 32, 42);
  setupFieldValidation("pulse", 0, 200);
  setupFieldValidation("systolic", 0, 250);
  setupFieldValidation("diastolic", 0, 250);
  setupFieldValidation("satO2", 0, 100);
  setupFieldValidation("glucemia", 0, 600);
}

/* ========================== */
/* FUNCIONES DEL MODAL */
/* ========================== */

function openDataModal(hour, index) {
  currentIndex = index;
  const modal = document.getElementById("dataModal");

  // Mostrar la hora
  document.getElementById("modalHour").textContent = `${hour
    .toString()
    .padStart(2, "0")}:00`;

  // Cargar datos existentes
  const data = vitalSigns[index] || {};
  document.getElementById("respRate").value = data.respRate || "";
  document.getElementById("temperature").value = data.temperature || "";
  document.getElementById("pulse").value = data.pulse || "";
  document.getElementById("systolic").value = data.systolic || "";
  document.getElementById("diastolic").value = data.diastolic || "";
  document.getElementById("satO2").value = data.satO2 || "";
  document.getElementById("glucemia").value = data.glucemia || "";

  modal.style.display = "block";
}

function closeDataModal() {
  document.getElementById("dataModal").style.display = "none";
}

function saveData() {
  // Verificar que todos los campos son válidos antes de guardar
  const isValid = validateAllFields();
  if (!isValid) {
    alert("Por favor, corrige los valores fuera de rango antes de guardar.");
    return;
  }

  const getValue = (id) => {
    const val = parseFloat(document.getElementById(id).value);
    return isNaN(val) ? undefined : val;
  };

  if (currentIndex === null || currentIndex < 0 || currentIndex >= 24) {
    console.error("Índice inválido:", currentIndex);
    return;
  }

  const newData = {
    respRate: getValue("respRate"),
    temperature: getValue("temperature"),
    pulse: getValue("pulse"),
    systolic: getValue("systolic"),
    diastolic: getValue("diastolic"),
    satO2: getValue("satO2"),
    glucemia: getValue("glucemia"),
  };

  vitalSigns[currentIndex] = newData;

  // Actualizar gráfico principal
  updateChart();
  updateTooltip(currentIndex, newData);

  // Actualizar extensiones si están disponibles
  if (typeof updateExtensions === "function") {
    updateExtensions(currentIndex, newData);
  }

  closeDataModal();
}

function deleteData() {
  if (currentIndex === null) return;

  vitalSigns[currentIndex] = {};

  // Actualizar gráfico principal
  updateChart();
  updateTooltip(currentIndex, {});

  // Actualizar extensiones si están disponibles
  if (typeof updateExtensions === "function") {
    updateExtensions(currentIndex, {});
  }

  closeDataModal();
}

/* ========================== */
/* ACTUALIZACIÓN DEL GRÁFICO */
/* ========================== */

function updateChart() {
  // Limpiar elementos existentes
  document
    .querySelectorAll(".chart-point, .chart-line")
    .forEach((el) => el.remove());

  const grid = document.getElementById("chartGrid");
  const gridWidth = grid.offsetWidth;
  const gridHeight = grid.offsetHeight;
  const cellWidth = gridWidth / 24;

  const offsets = {
    FR: -8,
    Temp: -3,
    FC: 3,
    TA: 8,
  };

  // Limpiar todas las clases has-data
  document.querySelectorAll(".grid-cell").forEach((cell) => {
    cell.classList.remove("has-data");
  });

  // Arrays para almacenar puntos de cada tipo para crear líneas
  const frPoints = [];
  const tempPoints = [];
  const fcPoints = [];

  vitalSigns.forEach((data, index) => {
    const cell = document.querySelector(`.grid-cell[data-index="${index}"]`);
    if (!cell) return;

    // Verificar si hay datos válidos
    const hasValidData =
      data &&
      Object.keys(data).some(
        (key) =>
          data[key] !== undefined && data[key] !== null && data[key] !== ""
      );

    if (!hasValidData) {
      cell.classList.remove("has-data");
      return;
    }

    // Marcar la celda como con datos
    cell.classList.add("has-data");

    // Posición X centrada en la franja
    const xCenter = index * cellWidth + cellWidth / 2;

    // FR (Frecuencia Respiratoria) - Escala 0-50
    if (
      data.respRate !== undefined &&
      data.respRate !== null &&
      data.respRate !== ""
    ) {
      const y = gridHeight - (data.respRate / 50) * gridHeight;
      const x = xCenter + offsets.FR;
      createPoint(x, y, "resp-point");
      frPoints.push({ x, y, index });
    }

    // Temperatura - Escala 32-42°C
    if (
      data.temperature !== undefined &&
      data.temperature !== null &&
      data.temperature !== ""
    ) {
      const y = gridHeight - ((data.temperature - 32) / 10) * gridHeight;
      const x = xCenter + offsets.Temp;
      createPoint(x, y, "temp-point");
      tempPoints.push({ x, y, index });
    }

    // FC (Frecuencia Cardíaca) - Escala 0-200
    if (data.pulse !== undefined && data.pulse !== null && data.pulse !== "") {
      const y = gridHeight - (data.pulse / 200) * gridHeight;
      const x = xCenter + offsets.FC;
      createPoint(x, y, "pulse-point");
      fcPoints.push({ x, y, index });
    }

    // TA (Tensión Arterial) - Escala 0-250
    if (
      data.systolic !== undefined &&
      data.systolic !== null &&
      data.systolic !== "" &&
      data.diastolic !== undefined &&
      data.diastolic !== null &&
      data.diastolic !== ""
    ) {
      const ySys = gridHeight - (data.systolic / 250) * gridHeight;
      const yDias = gridHeight - (data.diastolic / 250) * gridHeight;

      // Línea vertical para TA
      const line = document.createElement("div");
      line.className = "chart-line bp-line";
      line.style.left = `${xCenter + offsets.TA}px`;
      line.style.top = `${Math.min(ySys, yDias)}px`;
      line.style.height = `${Math.abs(yDias - ySys)}px`;
      grid.appendChild(line);

      // Puntos de sistólica y diastólica
      createPoint(xCenter + offsets.TA, ySys, "bp-point");
      createPoint(xCenter + offsets.TA, yDias, "bp-point");
    }
  });

  // Crear líneas conectoras entre puntos consecutivos
  createConnectingLines(frPoints, "resp-line");
  createConnectingLines(tempPoints, "temp-line");
  createConnectingLines(fcPoints, "pulse-line");
}

function createPoint(x, y, className) {
  const point = document.createElement("div");
  point.className = `chart-point ${className}`;
  point.style.left = `${x}px`;
  point.style.top = `${y}px`;
  document.getElementById("chartGrid").appendChild(point);
}

function createConnectingLines(points, lineClass) {
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
    line.className = `chart-line ${lineClass}`;
    line.style.left = `${start.x}px`;
    line.style.top = `${start.y}px`;
    line.style.width = `${distance}px`;
    line.style.height = "2px";
    line.style.transform = `rotate(${angle}deg)`;
    line.style.transformOrigin = "0 0";

    document.getElementById("chartGrid").appendChild(line);
  }
}

/* ========================== */
/* ACTUALIZACIÓN DE TOOLTIP */
/* ========================== */

function updateTooltip(index, data) {
  const cell = document.querySelector(`.grid-cell[data-index="${index}"]`);
  if (!cell) return;

  const hasData =
    data &&
    Object.keys(data).some(
      (key) => data[key] !== undefined && data[key] !== null && data[key] !== ""
    );

  // Actualizar celda del gráfico
  if (hasData) {
    cell.classList.add("has-data");
    cell.querySelector(".value-fr").textContent = data.respRate || "--";
    cell.querySelector(".value-fc").textContent = data.pulse || "--";
    cell.querySelector(".value-ta").textContent = `${data.systolic || "--"}/${
      data.diastolic || "--"
    }`;
    cell.querySelector(".value-temp").textContent = data.temperature || "--";
    cell.querySelector(".value-sato2").textContent = data.satO2 || "--";
    cell.querySelector(".value-glucemia").textContent = data.glucemia || "--";
  } else {
    cell.classList.remove("has-data");
    cell.querySelector(".value-fr").textContent = "--";
    cell.querySelector(".value-fc").textContent = "--";
    cell.querySelector(".value-ta").textContent = "--/--";
    cell.querySelector(".value-temp").textContent = "--";
    cell.querySelector(".value-sato2").textContent = "--";
    cell.querySelector(".value-glucemia").textContent = "--";
  }

  // Actualizar también la celda de cabecera correspondiente
  const hour = (index + 8) % 24;
  const headerCell = document.querySelector(`.hour-cell[data-hour="${hour}"]`);
  if (headerCell) {
    if (hasData) {
      headerCell.classList.add("has-data");
    } else {
      headerCell.classList.remove("has-data");
    }
  }
}

/* ========================== */
/* VALIDACIÓN DE CAMPOS */
/* ========================== */

function setupFieldValidation(fieldId, min, max) {
  const field = document.getElementById(fieldId);
  const errorDiv = document.getElementById(fieldId + "-error");

  function validateField() {
    const value = parseFloat(field.value);
    const isEmpty = field.value === "";

    if (isEmpty) {
      // Campo vacío es válido
      field.classList.remove("invalid");
      errorDiv.style.display = "none";
      return true;
    }

    if (isNaN(value) || value < min || value > max) {
      // Valor inválido
      field.classList.add("invalid");
      errorDiv.style.display = "block";
      return false;
    } else {
      // Valor válido
      field.classList.remove("invalid");
      errorDiv.style.display = "none";
      return true;
    }
  }

  // Validar en tiempo real mientras escribe
  field.addEventListener("input", validateField);
  field.addEventListener("blur", validateField);

  // Prevenir valores fuera de rango al escribir
  field.addEventListener("keydown", function (e) {
    // Permitir teclas especiales (backspace, delete, arrow keys, etc.)
    const allowedKeys = [8, 9, 27, 13, 46, 37, 38, 39, 40, 190, 110, 188, 189];
    if (
      allowedKeys.includes(e.keyCode) ||
      (e.keyCode >= 48 && e.keyCode <= 57) || // números 0-9
      (e.keyCode >= 96 && e.keyCode <= 105) || // numpad 0-9
      (e.ctrlKey === true &&
        (e.keyCode === 65 ||
          e.keyCode === 67 ||
          e.keyCode === 86 ||
          e.keyCode === 88))
    ) {
      return;
    }
    e.preventDefault();
  });
}

function validateAllFields() {
  const fields = [
    { id: "respRate", min: 0, max: 50 },
    { id: "temperature", min: 32, max: 42 },
    { id: "pulse", min: 0, max: 200 },
    { id: "systolic", min: 0, max: 250 },
    { id: "diastolic", min: 0, max: 250 },
    { id: "satO2", min: 0, max: 100 },
    { id: "glucemia", min: 0, max: 600 },
  ];

  let allValid = true;

  fields.forEach((field) => {
    const input = document.getElementById(field.id);
    const value = parseFloat(input.value);
    const isEmpty = input.value === "";

    if (!isEmpty && (isNaN(value) || value < field.min || value > field.max)) {
      allValid = false;
    }
  });

  return allValid;
}

/* ========================== */
/* CONFIGURACIÓN RESPONSIVA */
/* ========================== */

function setupResponsive() {
  window.addEventListener("resize", () => {
    if (vitalSigns.some((data) => data && Object.keys(data).length > 0)) {
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

/* ========================== */
/* FUNCIONES PÚBLICAS PARA EXTENSIONES */
/* ========================== */

// Función para que las extensiones puedan acceder a los datos
function getVitalSigns() {
  return vitalSigns;
}

// Función para que las extensiones puedan actualizar datos
function setVitalSigns(newData) {
  vitalSigns = newData;
  updateChart();

  // Actualizar todos los tooltips
  vitalSigns.forEach((data, index) => {
    updateTooltip(index, data);
  });
}

// Función para exportar datos (para futuras funcionalidades)
function exportData() {
  const dataToExport = {
    timestamp: new Date().toISOString(),
    patientInfo: {
      name: document.getElementById("patientName").value,
      date: document.getElementById("patientDate").value,
      age: document.getElementById("patientAge").value,
      history: document.getElementById("patientHistory").value,
      bed: document.getElementById("patientBed").value,
      sheet: document.getElementById("patientSheet").value,
    },
    vitalSigns: vitalSigns,
  };

  return JSON.stringify(dataToExport, null, 2);
}

// Función para importar datos (para futuras funcionalidades)
// Función para importar datos (para futuras funcionalidades)
function importData(jsonData) {
  try {
    const data = JSON.parse(jsonData);

    if (data.patientInfo) {
      document.getElementById("patientName").value =
        data.patientInfo.name || "";
      document.getElementById("patientDate").value =
        data.patientInfo.date || "";
      document.getElementById("patientAge").value = data.patientInfo.age || "";
      document.getElementById("patientHistory").value =
        data.patientInfo.history || "";
      document.getElementById("patientBed").value = data.patientInfo.bed || "";
      document.getElementById("patientSheet").value =
        data.patientInfo.sheet || "";
    }

    if (data.vitalSigns) {
      setVitalSigns(data.vitalSigns);
    }

    return true;
  } catch (error) {
    console.error("Error importando datos:", error);
    return false;
  }
}
