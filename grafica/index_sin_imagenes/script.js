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
// SECCIÓN 3: PÉRDIDAS/PÉRDIDAS INSENSIBLES //
// ========================== //

function initializeSection3() {
  console.log("Sección 3 inicializada");
  // Aquí se añadirá la funcionalidad específica de la sección 3
}

// ========================== //
// SECCIÓN 4: INGRESOS Y NUTRICIÓN //
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
    updateGlucoseInSection2(currentIndex, newData.glucemia, true); // Añadir true
  }
  if (newData.satO2) {
    updateSaturationInSection2(currentIndex, newData.satO2, true); // Añadir true
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
