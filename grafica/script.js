/*
INSTRUCCIONES DE INSTALACIÓN:
1. Guarda este archivo como "script.js" en el mismo directorio que 1.html
2. Reemplaza completamente el archivo JavaScript existente
3. Incluye todas las funcionalidades mejoradas para la sección 2
*/

// ========================== //
// VARIABLES GLOBALES         //
// ========================== //

// Datos principales
let vitalSigns = Array(24)
  .fill()
  .map(() => ({}));
let currentIndex = null;

// Datos de la sección 2
let section2Data = {
  pneumo: Array(24).fill(""),
  oxygen: Array(24).fill(""),
  saturation: Array(24).fill(""),
  eva: Array(24)
    .fill()
    .map(() => ({
      eva: "",
      rass: "",
    })),
  glucose: Array(24).fill(""),
  insulin: Array(24)
    .fill()
    .map(() => ({
      value: "",
      type: "S/P",
      recommended: "",
      message: "",
    })),
};

// Tabla de insulina según glucemia
const insulinTable = {
  "<150": "NADA",
  "151-225": "6U s/c",
  "226-250": "10U s/c",
  "251-300": "15U s/c",
  "301-350": "20U s/c",
  "351-400": "20U s/c + 5U I.V.",
  ">400": "AVISAR AL FACULTATIVO",
};

// Estado de la aplicación
let currentSection = 1;
let currentView = "compact"; // 'compact' o 'extended'

// ========================== //
// INICIALIZACIÓN             //
// ========================== //

document.addEventListener("DOMContentLoaded", function () {
  initializeApp();
});

function initializeApp() {
  // Inicializar control de vistas
  setupViewToggle();

  // Inicializar navegación entre secciones
  setupSectionNavigation();

  // Configurar vista inicial
  setupInitialView();

  // Inicializar sección 1 (Datos y constantes)
  initializeSection1();

  // Inicializar modal de datos
  initModal();

  // AÑADIR ESTA LÍNEA:
  initImageModal();

  // Configurar responsive
  setupResponsive();

  console.log("Aplicación inicializada correctamente");
}

// ========================== //
// CONTROL DE VISTAS          //
// ========================== //

function setupViewToggle() {
  const toggleBtn = document.getElementById("toggleViewBtn");
  if (!toggleBtn) return;

  toggleBtn.addEventListener("click", function () {
    const currentViewData = this.getAttribute("data-view");
    const newView = currentViewData === "compact" ? "extended" : "compact";

    switchView(newView);

    // Actualizar botón
    this.setAttribute("data-view", newView);
    const icon = this.querySelector(".toggle-icon");
    const text = this.querySelector(".toggle-text");

    if (newView === "extended") {
      icon.textContent = "📋";
      text.textContent = "Vista Compacta";
    } else {
      icon.textContent = "📄";
      text.textContent = "Vista Extendida";
    }
  });
}

function switchView(view) {
  currentView = view;
  const mainContainer = document.getElementById("mainContainer");
  const sectionNavigation = document.getElementById("sectionNavigation");

  if (!mainContainer) return;

  // Remover clases anteriores
  mainContainer.classList.remove("compact-view", "extended-view");

  if (view === "extended") {
    // Vista extendida - mostrar todas las secciones
    mainContainer.classList.add("extended-view");
    if (sectionNavigation) {
      sectionNavigation.style.display = "none";
    }

    // Mostrar todas las secciones
    document.querySelectorAll(".content-section").forEach((section) => {
      section.classList.add("active");
    });

    // Inicializar todas las secciones
    initializeAllSections();
  } else {
    // Vista compacta - mostrar solo la sección activa
    mainContainer.classList.add("compact-view");
    if (sectionNavigation) {
      sectionNavigation.style.display = "flex";
    }

    // Ocultar todas las secciones excepto la activa
    document.querySelectorAll(".content-section").forEach((section) => {
      section.classList.remove("active");
    });

    // Mostrar solo la sección actual
    const currentSectionElement = document.getElementById(
      `section-${currentSection}`
    );
    if (currentSectionElement) {
      currentSectionElement.classList.add("active");
    }
  }

  console.log(`Vista cambiada a: ${view}`);
}

function setupInitialView() {
  const mainContainer = document.getElementById("mainContainer");
  if (!mainContainer) return;

  // Detectar si es móvil para vista inicial
  const isMobile = window.innerWidth <= 768;
  const initialView = isMobile ? "compact" : "extended";

  // Configurar vista inicial
  switchView(initialView);

  // Actualizar botón
  const toggleBtn = document.getElementById("toggleViewBtn");
  if (toggleBtn) {
    toggleBtn.setAttribute("data-view", initialView);
    const icon = toggleBtn.querySelector(".toggle-icon");
    const text = toggleBtn.querySelector(".toggle-text");

    if (initialView === "extended") {
      icon.textContent = "📋";
      text.textContent = "Vista Compacta";
    } else {
      icon.textContent = "📄";
      text.textContent = "Vista Extendida";
    }
  }
}

function initializeAllSections() {
  // Inicializar todas las secciones para vista extendida
  initializeSection1();
  initializeSection2();
  initializeSection3();
  initializeSection4();
  initializeSection5();
}

// ========================== //
// NAVEGACIÓN ENTRE SECCIONES //
// ========================== //

function setupSectionNavigation() {
  const sectionTabs = document.querySelectorAll(".section-tab");

  sectionTabs.forEach((tab) => {
    tab.addEventListener("click", function () {
      // Solo funcionar en vista compacta
      if (currentView === "compact") {
        const sectionId = this.getAttribute("data-section");
        switchToSection(sectionId);
      }
    });
  });
}

function switchToSection(sectionId) {
  // Solo funcionar en vista compacta
  if (currentView !== "compact") return;

  // Actualizar estado
  currentSection = parseInt(sectionId);

  // Ocultar todas las secciones
  document.querySelectorAll(".content-section").forEach((section) => {
    section.classList.remove("active");
  });

  // Remover clase active de todas las pestañas
  document.querySelectorAll(".section-tab").forEach((tab) => {
    tab.classList.remove("active");
  });

  // Mostrar sección seleccionada
  const targetSection = document.getElementById(`section-${sectionId}`);
  if (targetSection) {
    targetSection.classList.add("active");
  }

  // Activar pestaña seleccionada
  const targetTab = document.querySelector(`[data-section="${sectionId}"]`);
  if (targetTab) {
    targetTab.classList.add("active");
  }

  // Ejecutar inicialización específica de la sección
  initializeSpecificSection(sectionId);

  console.log(`Cambiado a sección ${sectionId}`);
}

function initializeSpecificSection(sectionId) {
  switch (sectionId) {
    case "1":
      // Ya inicializada
      break;
    case "2":
      initializeSection2();
      break;
    case "3":
      initializeSection3();
      break;
    case "4":
      initializeSection4();
      break;
    case "5":
      initializeSection5();
      break;
  }
}

// ========================== //
// SECCIÓN 1: DATOS Y CONSTANTES //
// ========================== //

function initializeSection1() {
  setupGridCells();
  console.log("Sección 1 inicializada");
}

function setupGridCells() {
  const chartGrid = document.getElementById("chartGrid");
  if (!chartGrid) return;

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

// ========================== //
// SECCIÓN 2: OXIGENACIÓN, DOLOR Y GLUCEMIAS //
// ========================== //

function initializeSection2() {
  console.log("Inicializando Sección 2...");

  // Crear celdas para cada subsección
  createPneumoCells();
  createOxygenCells();
  createSaturationCells();
  createEvaCells();
  createGlucoseCells();
  createInsulinCells();

  // Sincronizar datos de glucemia y saturación de la sección 1
  syncDataFromSection1();

  console.log("Sección 2 inicializada");
}

function createPneumoCells() {
  const container = document.getElementById("pneumo-cells");
  if (!container) return;

  container.innerHTML = "";

  for (let i = 0; i < 24; i++) {
    const cell = document.createElement("div");
    cell.className = "data-cell-secondary pneumo-cell";

    const input = document.createElement("input");
    input.type = "number";
    input.min = "0";
    input.max = "100";
    input.step = "1";
    input.placeholder = "━"; // AÑADIR ESTA LÍNEA

    input.value = section2Data.pneumo[i] || "";
    input.addEventListener("input", (e) => {
      const value = parseInt(e.target.value);
      if (isNaN(value) || value < 0 || value > 100) {
        e.target.style.borderColor = "red";
      } else {
        e.target.style.borderColor = "";
        section2Data.pneumo[i] = value;
      }
    });

    cell.appendChild(input);
    container.appendChild(cell);
  }
}

function createOxygenCells() {
  const container = document.getElementById("oxygen-cells");
  if (!container) return;

  container.innerHTML = "";

  for (let i = 0; i < 24; i++) {
    const cell = document.createElement("div");
    cell.className = "data-cell-secondary oxygen-cell";

    const select = document.createElement("select");

    // Opciones del desplegable
    const options = ["", "VMI", "VMNI", "O2"];
    options.forEach((optionValue) => {
      const option = document.createElement("option");
      option.value = optionValue;
      option.textContent = optionValue || "—";
      select.appendChild(option);
    });

    select.value = section2Data.oxygen[i] || "";
    select.addEventListener("change", (e) => {
      section2Data.oxygen[i] = e.target.value;
    });

    cell.appendChild(select);
    container.appendChild(cell);
  }
}

function createSaturationCells() {
  const container = document.getElementById("saturation-cells");
  if (!container) return;

  container.innerHTML = "";

  for (let i = 0; i < 24; i++) {
    const cell = document.createElement("div");
    cell.className = "data-cell-secondary saturation-cell";

    const input = document.createElement("input");
    input.type = "number"; // Cambiado de "text" a "number"
    input.className = "saturation-value";
    input.readOnly = false; // Cambiado de true a false
    input.min = "0";
    input.max = "100";
    input.step = "1";
    input.value = section2Data.saturation[i] || "";

    // Añadir event listener para sincronizar con sección 1
    input.addEventListener("input", (e) => {
      const value = e.target.value;
      section2Data.saturation[i] = value;

      // Sincronizar con sección 1
      if (vitalSigns[i]) {
        vitalSigns[i].satO2 = value ? parseFloat(value) : undefined;
      } else {
        vitalSigns[i] = { satO2: value ? parseFloat(value) : undefined };
      }

      // Actualizar tooltip en sección 1
      updateTooltip(i, vitalSigns[i]);
    });

    cell.appendChild(input);
    container.appendChild(cell);
  }
}

function createEvaCells() {
  const container = document.getElementById("eva-cells");
  if (!container) return;

  container.innerHTML = "";

  for (let i = 0; i < 24; i++) {
    const cell = document.createElement("div");
    cell.className = "data-cell-secondary eva-cell";

    // Contenedor para los dos desplegables
    const evaContainer = document.createElement("div");
    evaContainer.className = "eva-container";

    // Desplegable EVA (0-10)
    const evaSelect = document.createElement("select");
    evaSelect.className = "eva-select";

    // Opción vacía
    const emptyOptionEva = document.createElement("option");
    emptyOptionEva.value = "";
    emptyOptionEva.textContent = "—";
    evaSelect.appendChild(emptyOptionEva);

    // Opciones 0-10
    for (let val = 0; val <= 10; val++) {
      const option = document.createElement("option");
      option.value = val;
      option.textContent = val;
      evaSelect.appendChild(option);
    }

    evaSelect.value = section2Data.eva[i].eva || "";
    evaSelect.addEventListener("change", (e) => {
      section2Data.eva[i].eva = e.target.value;
    });

    // Desplegable RASS (-5 a 4)
    const rassSelect = document.createElement("select");
    rassSelect.className = "rass-select";

    // Opción vacía
    const emptyOptionRass = document.createElement("option");
    emptyOptionRass.value = "";
    emptyOptionRass.textContent = "—";
    rassSelect.appendChild(emptyOptionRass);

    // Opciones -5 a 4
    for (let val = -5; val <= 4; val++) {
      const option = document.createElement("option");
      option.value = val;
      option.textContent = val;
      rassSelect.appendChild(option);
    }

    rassSelect.value = section2Data.eva[i].rass || "";
    rassSelect.addEventListener("change", (e) => {
      section2Data.eva[i].rass = e.target.value;
    });

    // Añadir elementos al contenedor
    evaContainer.appendChild(evaSelect);
    evaContainer.appendChild(rassSelect);

    cell.appendChild(evaContainer);
    container.appendChild(cell);
  }
}

function createGlucoseCells() {
  const container = document.getElementById("glucose-cells");
  if (!container) return;

  container.innerHTML = "";

  for (let i = 0; i < 24; i++) {
    const cell = document.createElement("div");
    cell.className = "data-cell-secondary glucose-cell";

    const input = document.createElement("input");
    input.type = "number"; // Cambiado de "text" a "number"
    input.className = "glucose-value";
    input.readOnly = false; // Cambiado de true a false
    input.min = "0";
    input.max = "600";
    input.step = "1";
    input.value = section2Data.glucose[i] || "";

    // Añadir event listener para sincronizar con sección 1
    input.addEventListener("input", (e) => {
      const value = e.target.value;
      section2Data.glucose[i] = value;

      // Sincronizar con sección 1
      if (vitalSigns[i]) {
        vitalSigns[i].glucemia = value ? parseFloat(value) : undefined;
      } else {
        vitalSigns[i] = { glucemia: value ? parseFloat(value) : undefined };
      }

      // Actualizar tooltip en sección 1
      updateTooltip(i, vitalSigns[i]);

      // Actualizar insulina basada en nueva glucemia
      updateGlucoseInSection2(i, value);
    });

    cell.appendChild(input);
    container.appendChild(cell);
  }
}

function createInsulinCells() {
  const container = document.getElementById("insulin-cells");
  if (!container) return;

  container.innerHTML = "";

  for (let i = 0; i < 24; i++) {
    const cell = document.createElement("div");
    cell.className = "data-cell-secondary insulin-cell";

    const insulinContainer = document.createElement("div");
    insulinContainer.className = "insulin-container";

    // Mensaje tooltip
    const message = document.createElement("div");
    message.className = "insulin-message";
    message.textContent = section2Data.insulin[i].message || "";

    // Input de insulina (se puede convertir en select dinámicamente)
    const insulinInput = document.createElement("input");
    insulinInput.type = "text";
    insulinInput.className = "insulin-value";
    insulinInput.value = section2Data.insulin[i].value || "";
    insulinInput.addEventListener("input", (e) => {
      section2Data.insulin[i].value = e.target.value;
    });

    // Select de insulina para casos críticos (inicialmente oculto)
    const insulinSelect = document.createElement("select");
    insulinSelect.className = "insulin-select";
    insulinSelect.style.display = "none";
    insulinSelect.addEventListener("change", (e) => {
      if (e.target.value !== "") {
        section2Data.insulin[i].value = e.target.value;
        // Actualizar inmediatamente para ocultar el desplegable y mostrar el valor
        updateInsulinDisplay(i);
      }
    });

    // Desplegable de tipo
    const typeSelect = document.createElement("select");
    typeSelect.className = "insulin-type";

    const optionSP = document.createElement("option");
    optionSP.value = "S/P";
    optionSP.textContent = "S/P";

    const optionPerfusion = document.createElement("option");
    optionPerfusion.value = "PERFUSIÓN";
    optionPerfusion.textContent = "PERFUSIÓN";

    typeSelect.appendChild(optionSP);
    typeSelect.appendChild(optionPerfusion);
    typeSelect.value = section2Data.insulin[i].type || "S/P";

    // Flecha para perfusión
    const arrow = document.createElement("div");
    arrow.className = "insulin-arrow";
    arrow.textContent = "→";

    // Event listener para cambio de tipo
    typeSelect.addEventListener("change", (e) => {
      const newType = e.target.value;
      section2Data.insulin[i].type = newType;

      // Propagar el cambio a las horas siguientes
      for (let j = i; j < 24; j++) {
        section2Data.insulin[j].type = newType;
        const nextSelect = container.children[j].querySelector(".insulin-type");
        if (nextSelect) {
          nextSelect.value = newType;
        }
        updateInsulinDisplay(j);
      }

      updateInsulinDisplay(i);
    });

    insulinContainer.appendChild(message);
    insulinContainer.appendChild(insulinInput);
    insulinContainer.appendChild(insulinSelect);
    insulinContainer.appendChild(typeSelect);
    insulinContainer.appendChild(arrow);

    cell.appendChild(insulinContainer);
    container.appendChild(cell);

    updateInsulinDisplay(i);
  }
}

function updateInsulinDisplay(index) {
  const container = document.getElementById("insulin-cells");
  if (!container || !container.children[index]) return;

  const cell = container.children[index];
  const insulinInput = cell.querySelector(".insulin-value");
  const insulinSelect = cell.querySelector(".insulin-select");
  const arrow = cell.querySelector(".insulin-arrow");
  const message = cell.querySelector(".insulin-message");

  const insulinData = section2Data.insulin[index];
  const glucoseValue = parseFloat(section2Data.glucose[index]);

  // Debug
  console.log(
    `Index: ${index}, Glucose: ${glucoseValue}, Type: ${insulinData.type}, Value: ${insulinData.value}`
  );

  if (insulinData.type === "PERFUSIÓN") {
    insulinInput.style.display = "none";
    insulinSelect.style.display = "none";
    arrow.classList.add("show");
    message.classList.remove("show-alert", "critical-alert");
    message.textContent = "";
  } else {
    arrow.classList.remove("show");

    // Casos críticos: glucemia >350
    if (!isNaN(glucoseValue) && glucoseValue > 350) {
      // Si ya hay un valor seleccionado, mostrar input con el valor
      if (insulinData.value && insulinData.value !== "") {
        insulinInput.style.display = "block";
        insulinSelect.style.display = "none";
        insulinInput.value = insulinData.value;

        // OCULTAR el mensaje de alerta una vez seleccionada la opción
        message.classList.remove("show-alert", "critical-alert");
        message.textContent = "";
      } else {
        // Si no hay valor, mostrar desplegable y MOSTRAR alerta
        insulinInput.style.display = "none";
        insulinSelect.style.display = "block";

        // Limpiar opciones previas
        insulinSelect.innerHTML = "";

        if (glucoseValue > 350 && glucoseValue <= 400) {
          // Caso 351-400: solo "20U s/c + 5U I.V."
          const option1 = document.createElement("option");
          option1.value = "";
          option1.textContent = "Seleccionar acción...";

          const option2 = document.createElement("option");
          option2.value = "20U s/c + 5U I.V.";
          option2.textContent = "20U s/c + 5U I.V.";

          insulinSelect.appendChild(option1);
          insulinSelect.appendChild(option2);

          // MOSTRAR mensaje de alerta
          message.textContent = "Glucemia crítica (351-400)";
          message.classList.add("show-alert", "critical-alert");
        } else {
          // Caso >400: solo "AVISAR AL FACULTATIVO"
          const option1 = document.createElement("option");
          option1.value = "";
          option1.textContent = "Seleccionar acción...";

          const option2 = document.createElement("option");
          option2.value = "AVISAR AL FACULTATIVO";
          option2.textContent = "AVISAR AL FACULTATIVO";

          insulinSelect.appendChild(option1);
          insulinSelect.appendChild(option2);

          // MOSTRAR mensaje de alerta
          message.textContent = "Glucemia muy crítica (>400)";
          message.classList.add("show-alert", "critical-alert");
        }
      }
    } else {
      // Casos normales: mostrar input
      insulinInput.style.display = "block";
      insulinSelect.style.display = "none";
      message.classList.remove("show-alert", "critical-alert");

      if (insulinData.recommended && insulinData.recommended !== "NADA") {
        if (!insulinInput.value) {
          insulinInput.value = insulinData.recommended;
          section2Data.insulin[index].value = insulinData.recommended;
        }
        message.textContent = "";
      } else {
        message.textContent = "";
      }
    }
  }
}

function getInsulinDose(glucose) {
  const glucoseValue = parseFloat(glucose);
  if (isNaN(glucoseValue)) return "NADA";

  if (glucoseValue < 150) return "NADA";
  if (glucoseValue >= 151 && glucoseValue <= 225) return "6U s/c";
  if (glucoseValue >= 226 && glucoseValue <= 250) return "10U s/c";
  if (glucoseValue >= 251 && glucoseValue <= 300) return "15U s/c";
  if (glucoseValue >= 301 && glucoseValue <= 350) return "20U s/c";
  if (glucoseValue >= 351 && glucoseValue <= 400) return "20U s/c + 5U I.V.";
  if (glucoseValue > 400) return "AVISAR AL FACULTATIVO";

  return "NADA";
}

function syncDataFromSection1() {
  for (let i = 0; i < 24; i++) {
    if (vitalSigns[i]) {
      // Sincronizar glucemia
      if (vitalSigns[i].glucemia) {
        updateGlucoseInSection2(i, vitalSigns[i].glucemia);
      }
      // Sincronizar saturación
      if (vitalSigns[i].satO2) {
        updateSaturationInSection2(i, vitalSigns[i].satO2);
      }
    }
  }
}

function updateGlucoseInSection2(index, glucoseValue, fromSection1 = false) {
  // Actualizar valor de glucemia en sección 2
  section2Data.glucose[index] = glucoseValue;

  // Solo actualizar el input si viene de sección 1 (evitar bucles)
  if (fromSection1) {
    const glucoseContainer = document.getElementById("glucose-cells");
    if (glucoseContainer && glucoseContainer.children[index]) {
      const glucoseInput =
        glucoseContainer.children[index].querySelector("input");
      if (glucoseInput) {
        glucoseInput.value = glucoseValue;
      }
    }
  }

  // Calcular dosis de insulina recomendada
  const recommendedDose = getInsulinDose(glucoseValue);
  section2Data.insulin[index].recommended = recommendedDose;

  // Para casos críticos (>350), limpiar valor previo y mostrar desplegable
  const glucoseNum = parseFloat(glucoseValue);
  if (!isNaN(glucoseNum) && glucoseNum > 350) {
    section2Data.insulin[index].value = ""; // Limpiar valor previo
    section2Data.insulin[index].recommended = ""; // Limpiar recomendación también
  }

  // Crear mensaje
  let message = "";
  if (
    recommendedDose !== "NADA" &&
    recommendedDose !== "AVISAR AL FACULTATIVO"
  ) {
    message = `Indicado administrar ${recommendedDose} de insulina rápida`;
  } else if (recommendedDose === "AVISAR AL FACULTATIVO") {
    message = "AVISAR AL FACULTATIVO";
  }

  section2Data.insulin[index].message = message;

  // Actualizar celda de insulina si existe
  const insulinContainer = document.getElementById("insulin-cells");
  if (insulinContainer && insulinContainer.children[index]) {
    const insulinData = section2Data.insulin[index];

    // Solo actualizar si no hay valor previo o si es tipo S/P
    if (
      insulinData.type === "S/P" &&
      (!insulinData.value || insulinData.value === insulinData.recommended)
    ) {
      if (
        recommendedDose !== "NADA" &&
        recommendedDose !== "AVISAR AL FACULTATIVO" &&
        glucoseNum <= 350
      ) {
        section2Data.insulin[index].value = recommendedDose;
      }
    }

    updateInsulinDisplay(index);
  }

  console.log(
    `Glucemia actualizada: índice ${index}, valor ${glucoseValue}, dosis recomendada: ${recommendedDose}`
  );

  // Al final de updateGlucoseInSection2, añade:
  setTimeout(() => {
    updateInsulinDisplay(index);
  }, 100);
}

function updateSaturationInSection2(index, satO2Value, fromSection1 = false) {
  // Actualizar valor de saturación en sección 2
  section2Data.saturation[index] = satO2Value;

  // Solo actualizar el input si viene de sección 1 (evitar bucles)
  if (fromSection1) {
    const saturationContainer = document.getElementById("saturation-cells");
    if (saturationContainer && saturationContainer.children[index]) {
      const saturationInput =
        saturationContainer.children[index].querySelector("input");
      if (saturationInput) {
        saturationInput.value = satO2Value;
      }
    }
  }

  console.log(`Saturación actualizada: índice ${index}, valor ${satO2Value}`);
}

// ========================== //
// SECCIÓN 3: PÉRDIDAS //
// ========================== //

// Datos de la sección 3
let section3Data = {
  diuresis: Array(24).fill(""),
  deposiciones: Array(24).fill(""),
  vomitos: Array(24).fill(""), // Solo vómitos y sudor
  fiebreTqn: Array(24).fill(0), // Calculado automáticamente
  sng: Array(24).fill(""),
  drenajes: Array(24).fill(0), // Calculado desde los subdrenajes
  controlResiduos: Array(24).fill(""), // Nueva fila editable
  perdidasInsensibles: 0, // Solo balance calculado
  // Subdrenajes individuales
  drenaje1: Array(24).fill(""),
  drenaje2: Array(24).fill(""),
  drenaje3: Array(24).fill(""),
  drenaje4: Array(24).fill(""),
  drenaje5: Array(24).fill(""),
  totals: Array(24).fill(0), // Totales calculados por hora
  balances: {
    diuresis: 0,
    deposiciones: 0,
    vomitos: 0,
    fiebreTqn: 0,
    sng: 0,
    drenajes: 0,
    drenaje1: 0,
    drenaje2: 0,
    drenaje3: 0,
    drenaje4: 0,
    drenaje5: 0,
    controlResiduos: 0, // Nueva fila
    perdidasInsensibles: 0,
    total: 0,
  },
  drenajesExpanded: false,
};

function initializeSection3() {
  console.log("Inicializando Sección 3...");

  // Crear celdas para cada subsección
  createDiuresisCells();
  createDeposicionesCells();
  createVomitosCells(); // Solo vómitos y sudor
  createFiebreTqnCells(); // Nueva fila calculada
  createSngCells();
  createDrenajesCells(); // Fila principal calculada
  createControlResiduosCells(); // Nueva fila editable
  createPerdidasInsensiblesCells(); // Solo balance
  createTotalPerdidasCells();

  // Crear celdas para drenajes individuales
  createDrenajeIndividualCells(
    "drenaje1-cells",
    section3Data.drenaje1,
    "drenaje-cell",
    1
  );
  createDrenajeIndividualCells(
    "drenaje2-cells",
    section3Data.drenaje2,
    "drenaje-cell",
    2
  );
  createDrenajeIndividualCells(
    "drenaje3-cells",
    section3Data.drenaje3,
    "drenaje-cell",
    3
  );
  createDrenajeIndividualCells(
    "drenaje4-cells",
    section3Data.drenaje4,
    "drenaje-cell",
    4
  );
  createDrenajeIndividualCells(
    "drenaje5-cells",
    section3Data.drenaje5,
    "drenaje-cell",
    5
  );

  // Configurar eventos para peso e ingreso
  setupPatientDataEvents();

  console.log("Sección 3 inicializada");
}

function createSection3Cells(
  containerId,
  dataArray,
  className,
  inputType = "number"
) {
  const container = document.getElementById(containerId);
  if (!container) return;

  container.innerHTML = "";

  // Crear 24 celdas horarias
  for (let i = 0; i < 24; i++) {
    const cell = document.createElement("div");
    cell.className = `data-cell-s3 ${className}`;

    const input = document.createElement("input");
    input.type = inputType;
    if (inputType === "number") {
      input.min = "0";
      input.max = "9999";
      input.step = "1";
    }
    input.placeholder = "━";
    input.value = dataArray[i] || "";

    input.addEventListener("input", (e) => {
      const value =
        inputType === "number"
          ? parseFloat(e.target.value) || 0
          : e.target.value;
      dataArray[i] = value;

      // Recalcular totales cuando cambie cualquier valor
      updateSection3Totals();
    });

    cell.appendChild(input);
    container.appendChild(cell);
  }

  // Crear celda de balance
  const balanceCell = document.createElement("div");
  balanceCell.className = "data-cell-s3 balance-cell";
  balanceCell.id = `${className}-balance`;
  balanceCell.textContent = "0";
  container.appendChild(balanceCell);
}

function createDiuresisCells() {
  createSection3Cells("diuresis-cells", section3Data.diuresis, "diuresis-cell");
}

function createDeposicionesCells() {
  createSection3Cells(
    "deposiciones-cells",
    section3Data.deposiciones,
    "deposiciones-cell"
  );
}

function createVomitosCells() {
  createSection3Cells("vomitos-cells", section3Data.vomitos, "vomitos-cell");
}

function createFiebreTqnCells() {
  const container = document.getElementById("fiebre-tqn-cells");
  if (!container) return;

  container.innerHTML = "";

  // Crear 24 celdas calculadas (no editables)
  for (let i = 0; i < 24; i++) {
    const cell = document.createElement("div");
    cell.className = "data-cell-s3 fiebre-tqn-cell calculated";
    cell.id = `fiebre-tqn-${i}`;
    cell.textContent = "0";
    container.appendChild(cell);
  }

  // Crear celda de balance
  const balanceCell = document.createElement("div");
  balanceCell.className = "data-cell-s3 balance-cell";
  balanceCell.id = "fiebre-tqn-balance";
  balanceCell.textContent = "0";
  container.appendChild(balanceCell);
}

function createSngCells() {
  createSection3Cells("sng-cells", section3Data.sng, "sng-cell");
}

function createDrenajesCells() {
  const container = document.getElementById("drenajes-cells");
  if (!container) return;

  container.innerHTML = "";

  // Crear 24 celdas calculadas (no editables)
  for (let i = 0; i < 24; i++) {
    const cell = document.createElement("div");
    cell.className = "data-cell-s3 drenajes-main calculated";
    cell.id = `drenajes-total-${i}`;
    cell.textContent = "0";
    container.appendChild(cell);
  }

  // Crear celda de balance
  const balanceCell = document.createElement("div");
  balanceCell.className = "data-cell-s3 balance-cell";
  balanceCell.id = "drenajes-cell-balance";
  balanceCell.textContent = "0";
  container.appendChild(balanceCell);
}

function createDrenajeIndividualCells(
  containerId,
  dataArray,
  className,
  drenajeNumber
) {
  const container = document.getElementById(containerId);
  if (!container) return;

  container.innerHTML = "";

  // Crear 24 celdas editables
  for (let i = 0; i < 24; i++) {
    const cell = document.createElement("div");
    cell.className = `data-cell-s3 ${className}`;

    const input = document.createElement("input");
    input.type = "number";
    input.min = "0";
    input.max = "9999";
    input.step = "1";
    input.placeholder = "━";
    input.value = dataArray[i] || "";

    input.addEventListener("input", (e) => {
      const value = parseFloat(e.target.value) || 0;
      dataArray[i] = value;

      // Recalcular totales de drenajes y totales generales
      updateDrenajesTotal();
      updateSection3Totals();
    });

    cell.appendChild(input);
    container.appendChild(cell);
  }

  // Crear celda de balance
  const balanceCell = document.createElement("div");
  balanceCell.className = "data-cell-s3 balance-cell";
  balanceCell.id = `drenaje${drenajeNumber}-balance`;
  balanceCell.textContent = "0";
  container.appendChild(balanceCell);
}

function updateDrenajesTotal() {
  // Calcular totales de drenajes por hora
  for (let i = 0; i < 24; i++) {
    const hourTotal =
      (parseFloat(section3Data.drenaje1[i]) || 0) +
      (parseFloat(section3Data.drenaje2[i]) || 0) +
      (parseFloat(section3Data.drenaje3[i]) || 0) +
      (parseFloat(section3Data.drenaje4[i]) || 0) +
      (parseFloat(section3Data.drenaje5[i]) || 0);

    section3Data.drenajes[i] = hourTotal;

    // Actualizar celda visual de drenajes principal
    const drenajesCell = document.getElementById(`drenajes-total-${i}`);
    if (drenajesCell) {
      drenajesCell.textContent = hourTotal.toString();
    }
  }

  // Calcular balances de cada subdrenaje
  section3Data.balances.drenaje1 = section3Data.drenaje1.reduce(
    (sum, val) => sum + (parseFloat(val) || 0),
    0
  );
  section3Data.balances.drenaje2 = section3Data.drenaje2.reduce(
    (sum, val) => sum + (parseFloat(val) || 0),
    0
  );
  section3Data.balances.drenaje3 = section3Data.drenaje3.reduce(
    (sum, val) => sum + (parseFloat(val) || 0),
    0
  );
  section3Data.balances.drenaje4 = section3Data.drenaje4.reduce(
    (sum, val) => sum + (parseFloat(val) || 0),
    0
  );
  section3Data.balances.drenaje5 = section3Data.drenaje5.reduce(
    (sum, val) => sum + (parseFloat(val) || 0),
    0
  );
  section3Data.balances.drenajes = section3Data.drenajes.reduce(
    (sum, val) => sum + val,
    0
  );

  // Actualizar celdas de balance de subdrenajes
  updateBalanceCell("drenaje1-balance", section3Data.balances.drenaje1);
  updateBalanceCell("drenaje2-balance", section3Data.balances.drenaje2);
  updateBalanceCell("drenaje3-balance", section3Data.balances.drenaje3);
  updateBalanceCell("drenaje4-balance", section3Data.balances.drenaje4);
  updateBalanceCell("drenaje5-balance", section3Data.balances.drenaje5);
  updateBalanceCell("drenajes-cell-balance", section3Data.balances.drenajes);
}

function toggleDrenajes() {
  const expandableDiv = document.getElementById("drenajes-expandable");
  const expandIcon = document.getElementById("drenajes-expand");

  if (!expandableDiv || !expandIcon) return;

  section3Data.drenajesExpanded = !section3Data.drenajesExpanded;

  if (section3Data.drenajesExpanded) {
    expandableDiv.classList.add("expanded");
    expandIcon.classList.add("expanded");
    expandIcon.title = "Contraer drenajes";
  } else {
    expandableDiv.classList.remove("expanded");
    expandIcon.classList.remove("expanded");
    expandIcon.title = "Expandir drenajes";
  }

  console.log("Drenajes expandidos:", section3Data.drenajesExpanded);
}

function createPerdidasCells() {
  createSection3Cells("perdidas-cells", section3Data.perdidas, "perdidas-cell");
}

function createControlResiduosCells() {
  createSection3Cells(
    "control-residuos-cells",
    section3Data.controlResiduos,
    "control-residuos-cell"
  );
}

function createPerdidasInsensiblesCells() {
  const container = document.getElementById("perdidas-cells");
  if (!container) return;

  container.innerHTML = "";

  // Crear 24 celdas vacías
  for (let i = 0; i < 24; i++) {
    const cell = document.createElement("div");
    cell.className = "data-cell-s3 empty-calculated";
    container.appendChild(cell);
  }

  // Crear celda de balance calculado
  const balanceCell = document.createElement("div");
  balanceCell.className = "data-cell-s3 perdidas-insensibles-cell balance-cell";
  balanceCell.id = "perdidas-insensibles-balance";
  balanceCell.textContent = "0";
  container.appendChild(balanceCell);
}

function setupPatientDataEvents() {
  const pesoInput = document.getElementById("patientPeso");
  const ingresoInput = document.getElementById("patientIngreso");

  if (pesoInput) {
    pesoInput.addEventListener("input", () => {
      calculateAllFormulas();
    });
  }

  if (ingresoInput) {
    ingresoInput.addEventListener("change", () => {
      calculateAllFormulas();
    });
  }
}

function calculateAllFormulas() {
  const peso = parseFloat(document.getElementById("patientPeso")?.value) || 0;
  const ingresoDatetime = document.getElementById("patientIngreso")?.value;

  if (peso === 0) {
    console.log("Peso no definido, no se pueden calcular fórmulas");
    return;
  }

  // Calcular número de horas desde el ingreso
  const horasTranscurridas = calculateHorasTranscurridas(ingresoDatetime);

  // Calcular FIEBRE, TQN por hora
  calculateFiebreTqnByHour(peso);

  // Calcular PÉRDIDAS INSENSIBLES (solo balance)
  calculatePerdidasInsensibles(peso, horasTranscurridas);

  // Recalcular totales
  updateSection3Totals();
}

function calculateHorasTranscurridas(ingresoDatetime) {
  if (!ingresoDatetime) {
    return 24; // Por defecto 24 horas si no se especifica
  }

  const ingreso = new Date(ingresoDatetime);
  const ahora = new Date();
  const diferenciaMs = ahora - ingreso;
  const horasTranscurridas = Math.max(
    0,
    Math.min(24, Math.floor(diferenciaMs / (1000 * 60 * 60)))
  );

  return horasTranscurridas;
}

function calculateFiebreTqnByHour(peso) {
  // Recorrer las 24 horas y calcular FIEBRE, TQN para cada una
  for (let i = 0; i < 24; i++) {
    let totalHour = 0;

    // Obtener temperatura y frecuencia respiratoria de la hora i
    const temperatura = vitalSigns[i]?.temperature || 0;
    const frecuenciaResp = vitalSigns[i]?.respRate || 0;

    // Cálculos de FIEBRE
    if (temperatura > 39) {
      totalHour += 0.3 * peso; // >39°C
    } else if (temperatura > 38) {
      totalHour += 0.2 * peso; // >38°C
    } else if (temperatura > 37) {
      totalHour += 0.1 * peso; // >37°C
    }

    // Cálculos de TQN (Taquipnea)
    if (frecuenciaResp > 35) {
      totalHour += 0.3 * peso; // >35 rpm
    } else if (frecuenciaResp > 25) {
      totalHour += 0.2 * peso; // >25 rpm
    }

    // Actualizar datos
    section3Data.fiebreTqn[i] = totalHour;

    // Actualizar celda visual
    const cell = document.getElementById(`fiebre-tqn-${i}`);
    if (cell) {
      cell.textContent = totalHour.toFixed(1);
    }
  }

  // Calcular balance total de FIEBRE, TQN
  section3Data.balances.fiebreTqn = section3Data.fiebreTqn.reduce(
    (sum, val) => sum + val,
    0
  );
  updateBalanceCell(
    "fiebre-tqn-balance",
    Math.round(section3Data.balances.fiebreTqn)
  );
}

function calculatePerdidasInsensibles(peso, horas) {
  // Fórmula: 0.5 × PESO × Nº HORAS
  const perdidas = 0.5 * peso * horas;

  section3Data.balances.perdidasInsensibles = perdidas;

  // Actualizar celda de balance
  const balanceCell = document.getElementById("perdidas-insensibles-balance");
  if (balanceCell) {
    balanceCell.textContent = Math.round(perdidas).toString();
  }

  console.log(
    `Pérdidas insensibles: ${peso}kg × 0.5 × ${horas}h = ${perdidas.toFixed(
      1
    )}ml`
  );
}

function createTotalPerdidasCells() {
  const container = document.getElementById("total-perdidas-cells");
  if (!container) return;

  container.innerHTML = "";

  // Crear 24 celdas vacías (sin números, solo líneas divisorias)
  for (let i = 0; i < 24; i++) {
    const cell = document.createElement("div");
    cell.className = "data-cell-s3 total-cell-empty";
    // Celda vacía, solo visual
    container.appendChild(cell);
  }

  // Crear celda de balance total con color rojo
  const totalBalanceCell = document.createElement("div");
  totalBalanceCell.className = "data-cell-s3 total-balance-cell-red";
  totalBalanceCell.id = "total-balance";
  totalBalanceCell.textContent = "0";
  container.appendChild(totalBalanceCell);
}

function updateSection3Totals() {
  // Primero actualizar los totales de drenajes
  updateDrenajesTotal();

  // Recalcular fórmulas automáticas si hay peso
  const peso = parseFloat(document.getElementById("patientPeso")?.value) || 0;
  if (peso > 0) {
    const ingresoDatetime = document.getElementById("patientIngreso")?.value;
    const horasTranscurridas = calculateHorasTranscurridas(ingresoDatetime);

    calculateFiebreTqnByHour(peso);
    calculatePerdidasInsensibles(peso, horasTranscurridas);
  }

  // Calcular balances por categoría
  section3Data.balances.diuresis = section3Data.diuresis.reduce(
    (sum, val) => sum + (parseFloat(val) || 0),
    0
  );
  section3Data.balances.deposiciones = section3Data.deposiciones.reduce(
    (sum, val) => sum + (parseFloat(val) || 0),
    0
  );
  section3Data.balances.vomitos = section3Data.vomitos.reduce(
    (sum, val) => sum + (parseFloat(val) || 0),
    0
  );
  section3Data.balances.sng = section3Data.sng.reduce(
    (sum, val) => sum + (parseFloat(val) || 0),
    0
  );
  section3Data.balances.controlResiduos = section3Data.controlResiduos.reduce(
    (sum, val) => sum + (parseFloat(val) || 0),
    0
  );

  // Calcular el total final (suma de todos los balances)
  section3Data.balances.total =
    section3Data.balances.diuresis +
    section3Data.balances.deposiciones +
    section3Data.balances.vomitos +
    section3Data.balances.fiebreTqn + // Ya calculado
    section3Data.balances.sng +
    section3Data.balances.drenajes + // Ya calculado desde subdrenajes
    section3Data.balances.controlResiduos + // Nueva fila
    section3Data.balances.perdidasInsensibles; // Ya calculado

  // Actualizar celdas de balance principales
  updateBalanceCell("diuresis-cell-balance", section3Data.balances.diuresis);
  updateBalanceCell(
    "deposiciones-cell-balance",
    section3Data.balances.deposiciones
  );
  updateBalanceCell("vomitos-cell-balance", section3Data.balances.vomitos);
  updateBalanceCell("sng-cell-balance", section3Data.balances.sng);
  updateBalanceCell(
    "control-residuos-cell-balance",
    section3Data.balances.controlResiduos
  );

  // Actualizar el balance total con color rojo
  const totalBalanceCell = document.getElementById("total-balance");
  if (totalBalanceCell) {
    totalBalanceCell.textContent = Math.round(
      section3Data.balances.total
    ).toString();
  }

  console.log("Totales de sección 3 actualizados:", section3Data.balances);
}

function updateBalanceCell(cellId, value) {
  const cell = document.getElementById(cellId);
  if (cell) {
    cell.textContent = value.toString();
  }
}

// Función para obtener datos de la sección 3
function getSection3Data() {
  return section3Data;
}

console.log("Script.js cargado correctamente");

// ========================== //
// TOOLTIPS PARA FÓRMULAS MÉDICAS //
// ========================== //

let formulaTooltip = null;

function createFormulaTooltip() {
  if (formulaTooltip) return formulaTooltip;

  formulaTooltip = document.createElement("div");
  formulaTooltip.className = "formula-tooltip";
  formulaTooltip.innerHTML = `
    <div class="formula-tooltip-title" id="tooltipTitle">Fórmula</div>
    <div class="formula-tooltip-content" id="tooltipContent">
      <!-- Contenido dinámico -->
    </div>
  `;

  // Evitar que se cierre el tooltip al hacer hover sobre él
  formulaTooltip.addEventListener("mouseenter", () => {
    clearTimeout(tooltipTimeout);
  });

  formulaTooltip.addEventListener("mouseleave", () => {
    tooltipTimeout = setTimeout(hideFormulaTooltip, 300);
  });

  document.body.appendChild(formulaTooltip);
  return formulaTooltip;
}

let tooltipTimeout;

function showFormulaTooltip(element, formulaType) {
  const tooltip = createFormulaTooltip();
  const title = tooltip.querySelector("#tooltipTitle");
  const content = tooltip.querySelector("#tooltipContent");

  let titleText = "";
  let contentHTML = "";

  switch (formulaType) {
    case "fiebre-tqn":
      titleText = "Fórmula FIEBRE + TQN";
      contentHTML = `
        <div class="formula-row">
          <span class="formula-fever">FIEBRE:</span>
        </div>
        <div class="formula-row">
          <span>• Fiebre >37°C:</span>
          <span class="formula-fever">0,1 × PESO × Nº HORAS</span>
        </div>
        <div class="formula-row">
          <span>• Fiebre >38°C:</span>
          <span class="formula-fever">0,2 × PESO × Nº HORAS</span>
        </div>
        <div class="formula-row">
          <span>• Fiebre >39°C:</span>
          <span class="formula-fever">0,3 × PESO × Nº HORAS</span>
        </div>
        <div class="formula-row" style="margin-top: 8px;">
          <span class="formula-tachypnea">TAQUIPNEA:</span>
        </div>
        <div class="formula-row">
          <span>• RPM >25:</span>
          <span class="formula-tachypnea">0,2 × PESO × Nº HORAS</span>
        </div>
        <div class="formula-row">
          <span>• RPM >35:</span>
          <span class="formula-tachypnea">0,3 × PESO × Nº HORAS</span>
        </div>
        <div class="formula-note">
          Se calculan automáticamente basándose en temperatura y frecuencia respiratoria de cada hora
        </div>
      `;
      break;

    case "perdidas-insensibles":
      titleText = "Fórmula PÉRDIDAS INSENSIBLES";
      contentHTML = `
        <div class="formula-row">
          <span class="formula-insensible">FÓRMULA:</span>
          <span class="formula-insensible">0.5 × PESO × HORAS</span>
        </div>
        <div class="formula-row" style="margin-top: 8px;">
          <span>Donde:</span>
        </div>
        <div class="formula-row">
          <span>• PESO:</span>
          <span class="formula-result">Peso del paciente (kg)</span>
        </div>
        <div class="formula-row">
          <span>• HORAS:</span>
          <span class="formula-result">Tiempo desde el ingreso</span>
        </div>
        <div class="formula-note">
          Se calcula automáticamente considerando el peso y las horas transcurridas desde el ingreso
        </div>
      `;
      break;
  }

  title.textContent = titleText;
  content.innerHTML = contentHTML;

  // Posicionar tooltip en el centro de la pantalla
  tooltip.style.left = "50%";
  tooltip.style.top = "50%";

  // Mostrar tooltip
  tooltip.classList.add("show");

  // Ocultar tooltip después de 5 segundos o al hacer click fuera
  setTimeout(() => {
    hideFormulaTooltip();
  }, 5000);

  // Agregar event listener para cerrar al hacer click fuera
  document.addEventListener("click", hideFormulaTooltipOnClick);
}

function hideFormulaTooltip() {
  if (formulaTooltip) {
    formulaTooltip.classList.remove("show");
    document.removeEventListener("click", hideFormulaTooltipOnClick);
  }
}

function hideFormulaTooltipOnClick(e) {
  if (
    formulaTooltip &&
    !formulaTooltip.contains(e.target) &&
    !e.target.classList.contains("calc-indicator")
  ) {
    hideFormulaTooltip();
  }
}

// Modificar la función existente para usar tooltips en lugar del modal
function showFormulaModal(formulaType) {
  // Buscar el elemento que disparó el evento
  const callerElement = document.querySelector(
    `[onclick="showFormulaModal('${formulaType}')"]`
  );
  showFormulaTooltip(callerElement, formulaType);
}

// FINAL DE TOOLTIP FIEBRE/TQN/PERDIDAS INSENSIBLES

// ========================== //
// SECCIÓN 4: INGRESOS  //
// ========================== //

function initializeSection4() {
  console.log("Sección 4 inicializada");
  // Aquí se añadirá la funcionalidad específica de la sección 4
}

// ========================== //
// SECCIÓN 5: MEDICACIÓN //
// ========================== //

function initializeSection5() {
  console.log("Sección 5 inicializada");
  // Aquí se añadirá la funcionalidad específica de la sección 5
}

// ========================== //
// MODAL Y GESTIÓN DE DATOS   //
// ========================== //

function initModal() {
  const modal = document.getElementById("dataModal");
  if (!modal) return;

  modal.addEventListener("click", (e) => {
    if (e.target === modal) {
      closeDataModal();
    }
  });

  // Botones del modal
  const saveBtn = document.getElementById("saveDataBtn");
  const deleteBtn = document.getElementById("deleteDataBtn");
  const cancelBtn = document.getElementById("cancelBtn");

  if (saveBtn) saveBtn.addEventListener("click", saveData);
  if (deleteBtn) deleteBtn.addEventListener("click", deleteData);
  if (cancelBtn) cancelBtn.addEventListener("click", closeDataModal);

  // Configurar validación en tiempo real para todos los campos
  setupFieldValidation("respRate", 0, 50);
  setupFieldValidation("temperature", 32, 42);
  setupFieldValidation("pulse", 0, 200);
  setupFieldValidation("systolic", 0, 250);
  setupFieldValidation("diastolic", 0, 250);
  setupFieldValidation("satO2", 0, 100);
  setupFieldValidation("glucemia", 0, 600);
}

function openDataModal(hour, index) {
  currentIndex = index;
  const modal = document.getElementById("dataModal");
  if (!modal) return;

  // Mostrar la hora
  const modalHour = document.getElementById("modalHour");
  if (modalHour) {
    modalHour.textContent = `${hour.toString().padStart(2, "0")}:00`;
  }

  // Cargar datos existentes
  const data = vitalSigns[index] || {};
  setFieldValue("respRate", data.respRate);
  setFieldValue("temperature", data.temperature);
  setFieldValue("pulse", data.pulse);
  setFieldValue("systolic", data.systolic);
  setFieldValue("diastolic", data.diastolic);
  setFieldValue("satO2", data.satO2);
  setFieldValue("glucemia", data.glucemia);

  modal.style.display = "block";
}

function closeDataModal() {
  const modal = document.getElementById("dataModal");
  if (modal) {
    modal.style.display = "none";
  }
}

function saveData() {
  // Verificar que todos los campos son válidos antes de guardar
  const isValid = validateAllFields();
  if (!isValid) {
    alert("Por favor, corrige los valores fuera de rango antes de guardar.");
    return;
  }

  if (currentIndex === null || currentIndex < 0 || currentIndex >= 24) {
    console.error("Índice inválido:", currentIndex);
    return;
  }

  const newData = {
    respRate: getFieldValue("respRate"),
    temperature: getFieldValue("temperature"),
    pulse: getFieldValue("pulse"),
    systolic: getFieldValue("systolic"),
    diastolic: getFieldValue("diastolic"),
    satO2: getFieldValue("satO2"),
    glucemia: getFieldValue("glucemia"),
  };

  vitalSigns[currentIndex] = newData;
  updateChart();
  updateTooltip(currentIndex, newData);

  // Actualizar sección 2 si hay datos de glucemia o saturación
  if (newData.glucemia) {
    updateGlucoseInSection2(currentIndex, newData.glucemia, true);
  }
  if (newData.satO2) {
    updateSaturationInSection2(currentIndex, newData.satO2, true);
  }

  // NUEVO: Recalcular fórmulas de sección 3 si cambió temperatura o frecuencia respiratoria
  if (newData.temperature || newData.respRate) {
    const peso = parseFloat(document.getElementById("patientPeso")?.value) || 0;
    if (peso > 0) {
      calculateFiebreTqnByHour(peso);
      updateSection3Totals();
    }
  }

  closeDataModal();
}

function deleteData() {
  if (currentIndex === null) return;

  vitalSigns[currentIndex] = {};
  updateChart();
  updateTooltip(currentIndex, {});

  // Limpiar datos en sección 2
  updateGlucoseInSection2(currentIndex, "");
  updateSaturationInSection2(currentIndex, "");

  closeDataModal();
}

// ========================== //
// FUNCIONES DE UTILIDAD      //
// ========================== //

function setFieldValue(fieldId, value) {
  const field = document.getElementById(fieldId);
  if (field) {
    field.value = value || "";
  }
}

function getFieldValue(fieldId) {
  const field = document.getElementById(fieldId);
  if (field) {
    const val = parseFloat(field.value);
    return isNaN(val) ? undefined : val;
  }
  return undefined;
}

// ========================== //
// ACTUALIZACIÓN DEL GRÁFICO  //
// ========================== //

function updateChart() {
  // Limpiar elementos existentes
  document
    .querySelectorAll(".chart-point, .chart-line")
    .forEach((el) => el.remove());

  const grid = document.getElementById("chartGrid");
  if (!grid) return;

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
    if (isValidValue(data.respRate)) {
      const y = gridHeight - (data.respRate / 50) * gridHeight;
      const x = xCenter + offsets.FR;
      createPoint(x, y, "resp-point");
      frPoints.push({ x, y, index });
    }

    // Temperatura - Escala 32-42°C
    if (isValidValue(data.temperature)) {
      const y = gridHeight - ((data.temperature - 32) / 10) * gridHeight;
      const x = xCenter + offsets.Temp;
      createPoint(x, y, "temp-point");
      tempPoints.push({ x, y, index });
    }

    // FC (Frecuencia Cardíaca) - Escala 0-200
    if (isValidValue(data.pulse)) {
      const y = gridHeight - (data.pulse / 200) * gridHeight;
      const x = xCenter + offsets.FC;
      createPoint(x, y, "pulse-point");
      fcPoints.push({ x, y, index });
    }

    // TA (Tensión Arterial) - Escala 0-250
    if (isValidValue(data.systolic) && isValidValue(data.diastolic)) {
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

function isValidValue(value) {
  return value !== undefined && value !== null && value !== "";
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
    updateTooltipValue(cell, ".value-fr", data.respRate);
    updateTooltipValue(cell, ".value-fc", data.pulse);
    updateTooltipValue(
      cell,
      ".value-ta",
      `${data.systolic || "--"}/${data.diastolic || "--"}`
    );
    updateTooltipValue(cell, ".value-temp", data.temperature);
    updateTooltipValue(cell, ".value-sato2", data.satO2);
    updateTooltipValue(cell, ".value-glucemia", data.glucemia);
  } else {
    cell.classList.remove("has-data");
    updateTooltipValue(cell, ".value-fr", "--");
    updateTooltipValue(cell, ".value-fc", "--");
    updateTooltipValue(cell, ".value-ta", "--/--");
    updateTooltipValue(cell, ".value-temp", "--");
    updateTooltipValue(cell, ".value-sato2", "--");
    updateTooltipValue(cell, ".value-glucemia", "--");
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

function updateTooltipValue(cell, selector, value) {
  const element = cell.querySelector(selector);
  if (element) {
    element.textContent = value || "--";
  }
}

// ========================== //
// VALIDACIÓN DE CAMPOS       //
// ========================== //

function setupFieldValidation(fieldId, min, max) {
  const field = document.getElementById(fieldId);
  const errorDiv = document.getElementById(fieldId + "-error");

  if (!field) return;

  function validateField() {
    const value = parseFloat(field.value);
    const isEmpty = field.value === "";

    if (isEmpty) {
      // Campo vacío es válido
      field.classList.remove("invalid");
      if (errorDiv) errorDiv.style.display = "none";
      return true;
    }

    if (isNaN(value) || value < min || value > max) {
      // Valor inválido
      field.classList.add("invalid");
      if (errorDiv) errorDiv.style.display = "block";
      return false;
    } else {
      // Valor válido
      field.classList.remove("invalid");
      if (errorDiv) errorDiv.style.display = "none";
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
    if (!input) return;

    const value = parseFloat(input.value);
    const isEmpty = input.value === "";

    if (!isEmpty && (isNaN(value) || value < field.min || value > field.max)) {
      allValid = false;
    }
  });

  return allValid;
}

// ========================== //
// RESPONSIVE Y EVENTOS       //
// ========================== //

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

// ========================== //
// FUNCIONES GLOBALES         //
// ========================== //

// Función para cerrar modal desde HTML
window.closeDataModal = closeDataModal;

// Funciones de utilidad que pueden ser usadas por otras secciones
window.switchToSection = switchToSection;
window.getCurrentSection = () => currentSection;
window.getVitalSigns = () => vitalSigns;
// Añadir las nuevas funciones globales
window.openImageModal = openImageModal;
window.closeImageModal = closeImageModal;
window.toggleDrenajes = toggleDrenajes;

console.log("Script.js cargado correctamente");

// ========================== //
// MODAL DE IMÁGENES DE REFERENCIA //
// ========================== //

function openImageModal(imagePath, title) {
  const modal = document.getElementById("imageModal");
  const modalImage = document.getElementById("modalImage");
  const modalTitle = document.getElementById("imageModalTitle");

  if (!modal || !modalImage || !modalTitle) {
    console.error("Elementos del modal de imagen no encontrados");
    return;
  }

  // Establecer título y imagen
  modalTitle.textContent = title;
  modalImage.src = imagePath;
  modalImage.alt = title;

  // Mostrar modal
  modal.style.display = "block";

  // Añadir evento para cerrar al hacer click fuera de la imagen
  modal.addEventListener("click", function (e) {
    if (e.target === modal) {
      closeImageModal();
    }
  });

  console.log(`Abriendo modal de imagen: ${imagePath}`);
}

function closeImageModal() {
  const modal = document.getElementById("imageModal");
  if (modal) {
    modal.style.display = "none";
  }
}

// Inicializar modal de imágenes
function initImageModal() {
  const imageModal = document.getElementById("imageModal");
  if (!imageModal) return;

  // Cerrar modal al hacer click en el fondo
  imageModal.addEventListener("click", function (e) {
    if (e.target === imageModal) {
      closeImageModal();
    }
  });

  // Cerrar modal con tecla Escape
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && imageModal.style.display === "block") {
      closeImageModal();
    }
  });

  console.log("Modal de imágenes inicializado");
}

// ========================== //
// ACTUALIZAR FUNCIONES GLOBALES //
// ========================== //

// Añadir las nuevas funciones globales
window.openImageModal = openImageModal;
window.closeImageModal = closeImageModal;

// ========================== //
// SISTEMA DE ACORDEONES INTEGRADO //
// ========================== //

// Datos de las escalas médicas
const scalesData = {
  insulin: {
    title: "Pauta de Insulina",
    sections: [
      {
        title: "Protocolo de Insulina Rápida",
        content: `
                    <table class="medical-table-accordion insulin-table-accordion">
                        <thead>
                            <tr>
                                <th class="range-col">Glucemia (mg/dl)</th>
                                <th class="dosage-col">Dosis de Insulina</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="range-col">&lt; 150 mg/dl</td>
                                <td class="dosage-col">NADA</td>
                            </tr>
                            <tr>
                                <td class="range-col">151-225 mg/dl</td>
                                <td class="dosage-col">6 U.I. s/c</td>
                            </tr>
                            <tr>
                                <td class="range-col">226-250 mg/dl</td>
                                <td class="dosage-col">10 U.I. s/c</td>
                            </tr>
                            <tr>
                                <td class="range-col">251-300 mg/dl</td>
                                <td class="dosage-col">15 U.I. s/c</td>
                            </tr>
                            <tr>
                                <td class="range-col">301-350 mg/dl</td>
                                <td class="dosage-col">20 U.I. s/c</td>
                            </tr>
                            <tr>
                                <td class="range-col">351-400 mg/dl</td>
                                <td class="dosage-col" style="background: #ffebee; color: #d32f2f;">
                                    20 U.I. s/c + 5 U.I. I.V.<br>
                                    <strong>⚠️ AVISAR AL FACULTATIVO</strong>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div style="margin-top: 15px; padding: 10px; background: #fff3e0; border-left: 4px solid #ff9800; border-radius: 4px;">
                        <strong>📋 Notas importantes:</strong><br>
                        • s/c = subcutánea<br>
                        • I.V. = intravenosa<br>
                        • Controlar glucemia cada 4-6 horas<br>
                        • Avisar facultativo si glucemia > 350 mg/dl
                    </div>
                `,
      },
    ],
  },
  "eva-rass": {
    title: "Escalas EVA/ESCID/RASS",
    sections: [
      {
        title: "Escala ESCID (Evaluación del Dolor)",
        content: `
                    <table class="medical-table-accordion escid-table-accordion">
                        <thead>
                            <tr>
                                <th style="width: 25%;">PARÁMETRO</th>
                                <th style="width: 25%;">0 PUNTOS</th>
                                <th style="width: 25%;">1 PUNTO</th>
                                <th style="width: 25%;">2 PUNTOS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="category-col">MUSCULATURA FACIAL</td>
                                <td class="score-col">Relajada</td>
                                <td class="score-col">En tensión, ceño fruncido/gesto de dolor</td>
                                <td class="score-col">Ceño fruncido de forma habitual, dientes apretados</td>
                            </tr>
                            <tr>
                                <td class="category-col">TRANQUILIDAD</td>
                                <td class="score-col">Tranquilo, relajado, movimientos normales</td>
                                <td class="score-col">Movimientos ocasionales, inquietud y/o posición</td>
                                <td class="score-col">Movimientos frecuentes, incluyendo cabeza o extremidades</td>
                            </tr>
                            <tr>
                                <td class="category-col">TONO MUSCULAR</td>
                                <td class="score-col">Normal</td>
                                <td class="score-col">Aumento de la flexión de dedos de manos y/o pies</td>
                                <td class="score-col">Rígido</td>
                            </tr>
                            <tr>
                                <td class="category-col">ADAPTACIÓN A VM</td>
                                <td class="score-col">Tolera la ventilación mecánica</td>
                                <td class="score-col">Tose, pero tolera la ventilación mecánica</td>
                                <td class="score-col">Lucha con el respirador</td>
                            </tr>
                            <tr>
                                <td class="category-col">CONFORTABILIDAD</td>
                                <td class="score-col">Confortable, tranquilo</td>
                                <td class="score-col">Se tranquiliza al tacto y/o a la voz. Fácil de distraer</td>
                                <td class="score-col">Difícil de controlar al tacto o hablándole</td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="score-summary">
                        <h4>Interpretación ESCID (máximo 10 puntos)</h4>
                        <div class="score-ranges">
                            <div class="score-range" style="background: #e8f5e8;">0: No dolor</div>
                            <div class="score-range" style="background: #fff3e0;">1-3: Dolor leve/moderado</div>
                            <div class="score-range" style="background: #fce4ec;">4-6: Dolor moderado/grave</div>
                            <div class="score-range" style="background: #ffebee;">&gt;6: Dolor muy intenso</div>
                        </div>
                    </div>
                `,
      },
      {
        title: "Escala RASS (Richmond Agitation Sedation Scale)",
        content: `
                    <table class="medical-table-accordion rass-table-accordion">
                        <thead>
                            <tr>
                                <th style="width: 25%;">PUNTUACIÓN</th>
                                <th style="width: 75%;">DESCRIPCIÓN</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="background: #ffebee;">
                                <td class="level-col">+4 Combativo</td>
                                <td class="description-col">Combativo, violento, peligro inmediato para el grupo</td>
                            </tr>
                            <tr style="background: #fff3e0;">
                                <td class="level-col">+3 Muy agitado</td>
                                <td class="description-col">Agresivo, se intenta retirar tubos o catéteres</td>
                            </tr>
                            <tr style="background: #fff8e1;">
                                <td class="level-col">+2 Agitado</td>
                                <td class="description-col">Movimientos frecuentes y sin propósito, lucha con el respirador</td>
                            </tr>
                            <tr style="background: #f3e5f5;">
                                <td class="level-col">+1 Inquieto</td>
                                <td class="description-col">Ansioso, pero sin movimientos agresivos o violentos</td>
                            </tr>
                            <tr style="background: #e8f5e8;">
                                <td class="level-col">0 Despierto</td>
                                <td class="description-col">Despierto y tranquilo</td>
                            </tr>
                            <tr style="background: #e3f2fd;">
                                <td class="level-col">-1 Somnoliento</td>
                                <td class="description-col">No está plenamente alerta, pero se mantiene despierto más de 10 segundos</td>
                            </tr>
                            <tr style="background: #e8eaf6;">
                                <td class="level-col">-2 Sedación leve</td>
                                <td class="description-col">Despierta brevemente a la voz, mantiene contacto visual de hasta 10 segundos</td>
                            </tr>
                            <tr style="background: #f3e5f5;">
                                <td class="level-col">-3 Sedación moderada</td>
                                <td class="description-col">Movimiento o apertura ocular a la voz, sin contacto visual</td>
                            </tr>
                            <tr style="background: #fce4ec;">
                                <td class="level-col">-4 Sedación profunda</td>
                                <td class="description-col">Sin respuesta a la voz, con movimiento o apertura ocular al estímulo físico</td>
                            </tr>
                            <tr style="background: #ffebee;">
                                <td class="level-col">-5 Sin respuesta</td>
                                <td class="description-col">Sin respuesta a la voz o al estímulo físico</td>
                            </tr>
                        </tbody>
                    </table>
                `,
      },
    ],
  },
};

// Función para mostrar el acordeón
function showAccordion(scaleType) {
  const container = document.getElementById("accordionContainer");
  const overlay = document.getElementById("accordionOverlay");
  const title = document.getElementById("accordionTitle");
  const content = document.getElementById("accordionContent");

  const scaleData = scalesData[scaleType];
  if (!scaleData) return;

  // Establecer título
  title.textContent = scaleData.title;

  // Generar contenido
  let htmlContent = "";
  scaleData.sections.forEach((section, index) => {
    htmlContent += `
            <div class="accordion-section">
                <button class="accordion-button" onclick="toggleAccordionSection(${index})">
                    <span>${section.title}</span>
                    <span class="accordion-arrow">▼</span>
                </button>
                <div class="accordion-panel" id="accordion-panel-${index}">
                    ${section.content}
                </div>
            </div>
        `;
  });

  content.innerHTML = htmlContent;

  // Mostrar acordeón
  overlay.classList.add("show");
  container.classList.add("show");

  // Auto-abrir la primera sección
  setTimeout(() => {
    if (document.getElementById("accordion-panel-0")) {
      toggleAccordionSection(0);
    }
  }, 100);
}

// Función para ocultar el acordeón
function hideAccordion() {
  const container = document.getElementById("accordionContainer");
  const overlay = document.getElementById("accordionOverlay");

  container.classList.remove("show");
  overlay.classList.remove("show");
}

// Función para toggle de secciones del acordeón
function toggleAccordionSection(index) {
  const button = document.querySelector(
    `.accordion-section:nth-child(${index + 1}) .accordion-button`
  );
  const panel = document.getElementById(`accordion-panel-${index}`);

  if (!button || !panel) return;

  const isActive = button.classList.contains("active");

  // Cerrar todas las secciones
  document
    .querySelectorAll(".accordion-button")
    .forEach((btn) => btn.classList.remove("active"));
  document.querySelectorAll(".accordion-panel").forEach((p) => {
    p.classList.remove("active");
    p.style.maxHeight = "0";
  });

  // Si no estaba activa, abrirla
  if (!isActive) {
    button.classList.add("active");
    panel.classList.add("active");
    panel.style.maxHeight = panel.scrollHeight + "px";
  }
}

// Cerrar acordeón con ESC
document.addEventListener("keydown", function (e) {
  if (e.key === "Escape") {
    hideAccordion();
  }
});

// Inicializar acordeones cuando el DOM esté listo
document.addEventListener("DOMContentLoaded", function () {
  // Crear el HTML necesario para los acordeones si no existe
  if (!document.getElementById("accordionContainer")) {
    const accordionHTML = `
            <!-- Overlay transparente -->
            <div class="accordion-overlay" id="accordionOverlay" onclick="hideAccordion()"></div>

            <!-- Contenedor del acordeón -->
            <div class="accordion-container" id="accordionContainer">
                <div class="accordion-header">
                    <h3 class="accordion-title" id="accordionTitle">Escalas Médicas</h3>
                    <button class="accordion-close" onclick="hideAccordion()">×</button>
                </div>
                
                <div class="accordion-content" id="accordionContent">
                    <!-- El contenido se cargará dinámicamente -->
                </div>
            </div>
        `;

    document.body.insertAdjacentHTML("beforeend", accordionHTML);

    // Prevenir cierre al hacer clic dentro del acordeón
    document
      .getElementById("accordionContainer")
      .addEventListener("click", function (e) {
        e.stopPropagation();
      });
  }
});
