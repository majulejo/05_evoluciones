// ========================== //
// NAVIGATION-SCROLL.JS - CONTROL COMPLETO DE NAVEGACIÓN //
// ========================== //

// Variables globales para el control del scroll
let lastScrollTop = 0;
let scrollTimer = null;
const SCROLL_THRESHOLD = 50;

// ========================== //
// INICIALIZACIÓN //
// ========================== //

document.addEventListener("DOMContentLoaded", function () {
  initScrollNavigation();
  initAutoSave();
  initBeforeUnload();
});

// ========================== //
// CONTROL DEL SCROLL PARA ICONOS FLOTANTES //
// ========================== //

function initScrollNavigation() {
  const floatingNav = document.querySelector(".floating-nav");
  if (!floatingNav) return;

  window.addEventListener("scroll", function () {
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

    if (scrollTimer) {
      clearTimeout(scrollTimer);
    }

    // Determinar dirección del scroll
    if (scrollTop > lastScrollTop && scrollTop > SCROLL_THRESHOLD) {
      // Scrolling hacia abajo - mover iconos hacia la derecha
      floatingNav.classList.add("scroll-down");
      floatingNav.classList.remove("scroll-up");
    } else if (scrollTop < lastScrollTop) {
      // Scrolling hacia arriba - mostrar iconos
      floatingNav.classList.add("scroll-up");
      floatingNav.classList.remove("scroll-down");
    }

    // Si está cerca del top, mostrar siempre
    if (scrollTop <= SCROLL_THRESHOLD) {
      floatingNav.classList.add("scroll-up");
      floatingNav.classList.remove("scroll-down");
    }

    lastScrollTop = scrollTop;

    // Timer para mostrar después de parar el scroll
    scrollTimer = setTimeout(() => {
      floatingNav.classList.add("scroll-up");
      floatingNav.classList.remove("scroll-down");
    }, 1500);
  });

  // Mostrar al pasar el mouse por encima
  floatingNav.addEventListener("mouseenter", function () {
    this.classList.add("scroll-up");
    this.classList.remove("scroll-down");
  });
}

// ========================== //
// FUNCIONES DE NAVEGACIÓN PRINCIPALES //
// ========================== //

function goToIndex() {
  if (hasUnsavedChanges()) {
    if (
      confirm(
        "¿Estás seguro de que quieres volver al inicio?\nLos cambios no guardados se perderán."
      )
    ) {
      animateButtonAction("home");
      setTimeout(() => (window.location.href = "index.html"), 300);
    }
  } else {
    animateButtonAction("home");
    setTimeout(() => (window.location.href = "index.html"), 300);
  }
}

function goToPatientData() {
  animateButtonAction("edit", () => {
    try {
      if (typeof saveCurrentPatientDataFromChart === "function") {
        saveCurrentPatientDataFromChart();
      }
      setTimeout(() => (window.location.href = "datos.html"), 100);
    } catch (error) {
      console.error("Error al guardar:", error);
      alert("Error al guardar los datos. Inténtalo de nuevo.");
    }
  });
}

function saveCurrentData() {
  animateButtonAction("save", () => {
    try {
      // Guardar datos del paciente
      if (typeof saveCurrentPatientDataFromChart === "function") {
        saveCurrentPatientDataFromChart();
      }

      // Guardar datos adicionales si existen
      const selectedBed = localStorage.getItem("selectedBed");
      if (selectedBed) {
        if (typeof vitalSigns !== "undefined") {
          localStorage.setItem(
            `vitalSigns_${selectedBed}`,
            JSON.stringify(vitalSigns)
          );
        }
        if (typeof section2Data !== "undefined") {
          localStorage.setItem(
            `section2Data_${selectedBed}`,
            JSON.stringify(section2Data)
          );
        }
        if (typeof section3Data !== "undefined") {
          localStorage.setItem(
            `section3Data_${selectedBed}`,
            JSON.stringify(section3Data)
          );
        }
      }

      showSaveSuccess();
    } catch (error) {
      console.error("Error al guardar:", error);
      alert("Error al guardar los datos. Inténtalo de nuevo.");
    }
  });
}

function printChart() {
  animateButtonAction("print", () => {
    try {
      saveCurrentData();

      setTimeout(() => {
        const elementsToHide = [
          ".footer-navigation",
          ".floating-nav",
          ".view-controls",
          ".section-navigation",
        ];

        const hiddenElements = [];
        elementsToHide.forEach((selector) => {
          document.querySelectorAll(selector).forEach((el) => {
            hiddenElements.push({
              element: el,
              originalDisplay: el.style.display,
            });
            el.style.display = "none";
          });
        });

        const wasCompact =
          typeof currentView !== "undefined" && currentView === "compact";
        if (wasCompact && typeof switchView === "function") {
          switchView("extended");
        }

        setTimeout(() => {
          window.print();

          hiddenElements.forEach((item) => {
            item.element.style.display = item.originalDisplay;
          });

          if (wasCompact && typeof switchView === "function") {
            setTimeout(() => switchView("compact"), 100);
          }
        }, 500);
      }, 300);
    } catch (error) {
      console.error("Error al imprimir:", error);
      alert("Error al preparar la impresión.");
    }
  });
}

// ========================== //
// FUNCIONES ESPECÍFICAS PARA DATOS.HTML //
// ========================== //

function goToChart() {
  const name = document.getElementById("patientName")?.value?.trim();
  if (!name) {
    alert(
      "Por favor, introduce al menos el nombre del paciente antes de ir a la gráfica."
    );
    document.getElementById("patientName")?.focus();
    return;
  }

  animateButtonAction("save", () => {
    const selectedBed = localStorage.getItem("selectedBed");
    if (selectedBed) {
      const currentData = {
        name: document.getElementById("patientName").value,
        age: document.getElementById("patientAge").value,
        weight: document.getElementById("patientPeso").value,
        history: document.getElementById("patientHistory").value,
        bed: document.getElementById("patientBed").value,
        admission: document.getElementById("patientIngreso").value,
      };

      if (typeof savePatientData === "function") {
        savePatientData(selectedBed, currentData);
      }
    }

    setTimeout(() => (window.location.href = "grafica.html"), 100);
  });
}

// ========================== //
// FUNCIONES ESPECÍFICAS PARA INDEX.HTML //
// ========================== //

function showHelp() {
  animateButtonAction("home", () => {
    alert(
      "UCI - Sistema de Gráficas de Enfermería\n\n" +
        '• Seleccione "Nuevo Ingreso" para registrar un nuevo paciente\n' +
        '• Seleccione "Paciente Ingresado" para ver datos existentes\n' +
        "• Los datos se guardan automáticamente\n" +
        "• Use los iconos flotantes para navegación rápida"
    );
  });
}

function showStats() {
  animateButtonAction("print", () => {
    const patients = JSON.parse(localStorage.getItem("patients")) || {};
    const totalPatients = Object.keys(patients).length;
    const occupiedBeds = Object.values(patients).filter(
      (p) => p && p.name
    ).length;

    alert(
      `📊 Estadísticas UCI:\n\n` +
        `🛏️ Camas ocupadas: ${occupiedBeds}/12\n` +
        `👥 Pacientes registrados: ${totalPatients}\n` +
        `🆓 Camas libres: ${12 - occupiedBeds}\n` +
        `📈 Ocupación: ${Math.round((occupiedBeds / 12) * 100)}%`
    );
  });
}

// ========================== //
// FUNCIONES DE ANIMACIÓN Y FEEDBACK //
// ========================== //

function animateButtonAction(actionType, callback) {
  const footerBtn = document.querySelector(
    `.footer-btn.${getButtonClass(actionType)}`
  );
  if (footerBtn) {
    footerBtn.classList.add("loading");
  }

  const iconIndex = getIconIndex(actionType);
  const floatingIcon = document.querySelector(
    `.floating-nav-item:nth-child(${iconIndex})`
  );
  if (floatingIcon) {
    floatingIcon.classList.add("active");
  }

  if (callback) {
    setTimeout(callback, 200);
  }

  setTimeout(() => {
    if (footerBtn) footerBtn.classList.remove("loading");
    if (floatingIcon) floatingIcon.classList.remove("active");
  }, 1000);
}

function getButtonClass(actionType) {
  const classes = {
    home: "secondary",
    edit: "warning",
    save: "success",
    print: "primary",
  };
  return classes[actionType] || "secondary";
}

function getIconIndex(actionType) {
  const indices = {
    home: 1,
    edit: 2,
    save: 3,
    print: 4,
  };
  return indices[actionType] || 1;
}

function showSaveSuccess() {
  const saveBtn = document.querySelector(".footer-btn.success");
  if (saveBtn) {
    const originalHTML = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-check"></i><span>Guardado ✓</span>';
    saveBtn.classList.add("saved");

    setTimeout(() => {
      saveBtn.innerHTML = originalHTML;
      saveBtn.classList.remove("saved");
    }, 2000);
  }

  const saveIcon = document.querySelector(".floating-nav-item:nth-child(3)");
  if (saveIcon) {
    const icon = saveIcon.querySelector("i");
    const originalClass = icon.className;
    icon.className = "fas fa-check";
    saveIcon.classList.add("saved");

    setTimeout(() => {
      icon.className = originalClass;
      saveIcon.classList.remove("saved");
    }, 2000);
  }
}

// ========================== //
// FUNCIONES DE UTILIDAD //
// ========================== //

function hasUnsavedChanges() {
  const selectedBed = localStorage.getItem("selectedBed");
  if (!selectedBed) return false;

  try {
    const currentData = {
      name: document.getElementById("patientName")?.value || "",
      age: document.getElementById("patientAge")?.value || "",
      weight: document.getElementById("patientPeso")?.value || "",
      history: document.getElementById("patientHistory")?.value || "",
      bed: document.getElementById("patientBed")?.value || "",
      admission: document.getElementById("patientIngreso")?.value || "",
    };

    const savedData =
      typeof loadPatientData === "function"
        ? loadPatientData(selectedBed)
        : null;
    return JSON.stringify(currentData) !== JSON.stringify(savedData || {});
  } catch (error) {
    return false;
  }
}

function initAutoSave() {
  if (window.location.pathname.includes("grafica.html")) {
    setInterval(() => {
      try {
        if (typeof saveCurrentPatientDataFromChart === "function") {
          saveCurrentPatientDataFromChart();
        }
      } catch (error) {
        console.error("Auto-save failed:", error);
      }
    }, 30000);
  }
}

function initBeforeUnload() {
  window.addEventListener("beforeunload", function (e) {
    if (hasUnsavedChanges()) {
      e.preventDefault();
      e.returnValue =
        "¿Estás seguro de que quieres salir? Los cambios no guardados se perderán.";
    }
  });
}
