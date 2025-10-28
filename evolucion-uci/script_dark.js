// script_dark.js - Versión mejorada con modo oscuro corregido

document.addEventListener("DOMContentLoaded", () => {
  // ——————————————————————————————
  // 1) Seleccionar los elementos del DOM
  // ——————————————————————————————
  const pesoInput = document.querySelector("#peso_box");
  const horasDesdeIngresoInput = document.querySelector("#horas_desde_ingreso_box");
  const boxLosses = document.querySelector("#box-losses");
  const boxEarings = document.querySelector("#box-earings");
  const selectedBoxElement = document.querySelector("#selected-box h2");
  const boxLinks = document.querySelectorAll("#box-navigation ul li a");

  // Reúne todos los <input> cuyo id termina en "_box"
  const allInputs = Array.from(document.querySelectorAll("input")).filter(
    (input) => input.id && input.id.endsWith("_box")
  );

  // Variable para controlar el guardado automático
  let autoSaveTimeout = null;
  let boxSeleccionado = false;

  // ——————————————————————————————
  // MODO OSCURO - Inicialización y manejo
  // ——————————————————————————————
  function initializeDarkMode() {
    const switchElement = document.getElementById('switch');
    const body = document.body;
    
    if (!switchElement) {
      console.error('Switch element not found');
      return;
    }

    // Cargar preferencia guardada o usar modo claro por defecto
    const savedTheme = localStorage.getItem('darkMode');
    const isDarkMode = savedTheme === 'true';
    
    // Aplicar el modo guardado
    if (isDarkMode) {
      body.classList.add('active');
      switchElement.classList.add('active');
    } else {
      body.classList.remove('active');
      switchElement.classList.remove('active');
    }

    // Event listener para el switch
    switchElement.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      
      // Toggle de clases
      body.classList.toggle('active');
      switchElement.classList.toggle('active');
      
      // Guardar preferencia
      const isNowDark = body.classList.contains('active');
      localStorage.setItem('darkMode', isNowDark.toString());
      
      console.log('Modo oscuro:', isNowDark ? 'Activado' : 'Desactivado');
      
      // Recalcular colores del balance total
      calculateDerivedValues();
    });

    console.log('Modo oscuro inicializado. Estado actual:', isDarkMode ? 'Oscuro' : 'Claro');
  }

  // ——————————————————————————————
  // 2) Función de guardado automático con debounce
  // ——————————————————————————————
  function scheduleAutoSave() {
    const currentBox = selectedBoxElement?.getAttribute("data-current-box");
    if (!currentBox) return;

    // Cancelar guardado anterior si existe
    if (autoSaveTimeout) {
      clearTimeout(autoSaveTimeout);
    }

    // Programar nuevo guardado en 2 segundos
    autoSaveTimeout = setTimeout(async () => {
      console.log("Guardado automático para Box", currentBox);
      await saveCurrentBoxData(currentBox);
    }, 2000);
  }

  // ——————————————————————————————
  // 3) Event listeners para inputs con guardado automático
  // ——————————————————————————————
  allInputs.forEach((input) => {
    // Guardado en evento 'input' (mientras escribe)
    input.addEventListener("input", () => {
      verificarHabilitacion();
      calculateDerivedValues();
      scheduleAutoSave();
    });

    // Guardado inmediato en evento 'change' (cuando termina de editar)
    input.addEventListener("change", async () => {
      const currentBox = selectedBoxElement?.getAttribute("data-current-box");
      if (!currentBox) return;
      
      // Cancelar guardado programado y guardar inmediatamente
      if (autoSaveTimeout) {
        clearTimeout(autoSaveTimeout);
        autoSaveTimeout = null;
      }
      
      console.log("Campo cambiado, guardando inmediatamente Box", currentBox);
      await saveCurrentBoxData(currentBox);
    });

    // Guardado al perder el foco
    input.addEventListener("blur", async () => {
      const currentBox = selectedBoxElement?.getAttribute("data-current-box");
      if (!currentBox) return;
      
      if (autoSaveTimeout) {
        clearTimeout(autoSaveTimeout);
        autoSaveTimeout = null;
      }
      
      await saveCurrentBoxData(currentBox);
    });
  });

  // ——————————————————————————————
  // 4) Guardado antes de cerrar la página
  // ——————————————————————————————
  window.addEventListener("beforeunload", async (e) => {
    const currentBox = selectedBoxElement?.getAttribute("data-current-box");
    if (currentBox) {
      // Guardado síncrono antes de cerrar
      await saveCurrentBoxDataSync(currentBox);
    }
  });

  // ——————————————————————————————
  // 5) Guardado periódico cada 30 segundos
  // ——————————————————————————————
  setInterval(async () => {
    const currentBox = selectedBoxElement?.getAttribute("data-current-box");
    if (currentBox) {
      console.log("Guardado periódico automático para Box", currentBox);
      await saveCurrentBoxData(currentBox);
    }
  }, 30000); // 30 segundos

  // Antes de seleccionar ningún Box, deshabilitamos "Pérdidas" y "Ingresos"
  if (boxLosses) boxLosses.classList.add("disabled");
  if (boxEarings) boxEarings.classList.add("disabled");
  if (pesoInput) pesoInput.disabled = true;
  if (horasDesdeIngresoInput) horasDesdeIngresoInput.disabled = true;

  // ——————————————————————————————
  // 6) Función para habilitar/deshabilitar paneles
  // ——————————————————————————————
  function verificarHabilitacion() {
    const pesoValido = pesoInput && pesoInput.value.trim() !== "";
    const horasValido = horasDesdeIngresoInput && horasDesdeIngresoInput.value.trim() !== "";
    
    if (pesoValido && horasValido && boxSeleccionado) {
      if (boxLosses) boxLosses.classList.remove("disabled");
      if (boxEarings) boxEarings.classList.remove("disabled");
    } else {
      if (boxLosses) boxLosses.classList.add("disabled");
      if (boxEarings) boxEarings.classList.add("disabled");
    }
  }

  // ——————————————————————————————
  // 7) Guardar datos del Box (versión asíncrona)
  // ——————————————————————————————
  async function saveCurrentBoxData(boxNumber) {
    if (!boxNumber) return;

    const dataToSend = {};
    allInputs.forEach((input) => {
      // Solo incluir campos que no sean readonly
      if (!input.hasAttribute('readonly')) {
        dataToSend[input.id] = input.value;
      }
    });

    try {
      const response = await fetch("api.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          action: "save",
          boxNumber,
          data: dataToSend,
        }),
      });

      if (!response.ok) {
        throw new Error(`HTTP Error: ${response.status}`);
      }

      const json = await response.json();
      
      if (!json.success) {
        console.error(`Error al guardar Box ${boxNumber}:`, json.message);
        // Mostrar notificación discreta en lugar de alert
        showNotification(`Error al guardar: ${json.message}`, 'error');
        return false;
      }

      console.log(`Guardado exitoso Box ${boxNumber}`);
      showNotification(`Datos guardados automáticamente`, 'success');
      return true;
    } catch (err) {
      console.error(`Error al guardar Box ${boxNumber}:`, err);
      showNotification(`Error de conexión al guardar`, 'error');
      return false;
    }
  }

  // ——————————————————————————————
  // 8) Guardar datos síncrono (para beforeunload)
  // ——————————————————————————————
  async function saveCurrentBoxDataSync(boxNumber) {
    if (!boxNumber) return;

    const dataToSend = {};
    allInputs.forEach((input) => {
      if (!input.hasAttribute('readonly')) {
        dataToSend[input.id] = input.value;
      }
    });

    try {
      await fetch("api.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          action: "save",
          boxNumber,
          data: dataToSend,
        }),
        keepalive: true // Importante para beforeunload
      });
    } catch (err) {
      console.error("Error en guardado síncrono:", err);
    }
  }

  // ——————————————————————————————
  // 9) Cargar datos de un Box
  // ——————————————————————————————
  async function loadBoxData(boxNumber) {
    if (!boxNumber) return;

    try {
      showNotification(`Cargando datos del Box ${boxNumber}...`, 'info');
      
      const resp = await fetch("api.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          action: "load",
          boxNumber,
        }),
      });

      if (!resp.ok) {
        throw new Error(`HTTP Error: ${resp.status}`);
      }

      const data = await resp.json();
      
      // Cargar datos en los inputs
      if (data && Object.keys(data).length > 0) {
        allInputs.forEach((input) => {
          const value = data[input.id];
          input.value = value !== null && value !== undefined ? value : "";
        });
        showNotification(`Datos cargados correctamente`, 'success');
      } else {
        // Si no hay datos, limpiar todos los inputs
        allInputs.forEach((input) => {
          if (!input.hasAttribute('readonly')) {
            input.value = "";
          }
        });
        showNotification(`Box ${boxNumber} está vacío`, 'info');
      }
      
      verificarHabilitacion();
      calculateDerivedValues();
    } catch (err) {
      console.error("Error al cargar datos del Box", boxNumber, err);
      showNotification("Error al cargar datos", 'error');
    }
  }

  // ——————————————————————————————
  // 10) Sistema de notificaciones discretas
  // ——————————————————————————————
  function showNotification(message, type = 'info') {
    // Buscar contenedor de notificaciones o crearlo
    let container = document.getElementById('notification-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'notification-container';
      container.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        max-width: 300px;
      `;
      document.body.appendChild(container);
    }

    // Crear notificación
    const notification = document.createElement('div');
    notification.style.cssText = `
      background: ${type === 'success' ? '#f4f7fa' : type === 'error' ? '#f44336' : '#f4f7fa'};
      color: #272626;
      border:   1px solid var(--borde);
      padding: 12px 16px;
      margin-bottom: 10px;
      border-radius: 4px;
      font-size: 14px;
      opacity: 0;
      transition: opacity 0.3s ease;
      box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    `;
    notification.textContent = message;

    container.appendChild(notification);

    // Animar entrada
    setTimeout(() => notification.style.opacity = '1', 10);

    // Remover después de 3 segundos
    setTimeout(() => {
      notification.style.opacity = '0';
      setTimeout(() => {
        if (notification.parentNode) {
          notification.parentNode.removeChild(notification);
        }
      }, 300);
    }, 3000);
  }

  // ——————————————————————————————
  // 11) Cálculo de valores derivados
  // ——————————————————————————————
  function calculateDerivedValues() {
    const getValue = (selector) =>
      parseFloat(document.querySelector(selector)?.value) || 0;

    const peso = getValue("#peso_box");
    const horasIngreso = getValue("#horas_desde_ingreso_box");
    const fiebre37Horas = getValue("#fiebre37_horas_box");
    const fiebre38Horas = getValue("#fiebre38_horas_box");
    const fiebre39Horas = getValue("#fiebre39_horas_box");
    const rpm25Horas = getValue("#rpm25_horas_box");
    const rpm35Horas = getValue("#rpm35_horas_box");
    const vomitosSudor = getValue("#perdida_vomitos_box");
    const diuresis = getValue("#perdida_orina_box");
    const sng = getValue("#perdida_sng_box");
    const hdfvvc = getValue("#perdida_hdfvvc_box");
    const drenajes = getValue("#perdida_drenajes_box");
    const midazolam = getValue("#ingreso_midazolam_box");
    const fentanest = getValue("#ingreso_fentanest_box");
    const propofol = getValue("#ingreso_propofol_box");
    const remifentanilo = getValue("#ingreso_remifentanilo_box");
    const dexdor = getValue("#ingreso_dexdor_box");
    const noradrenalina = getValue("#ingreso_noradrenalina_box");
    const insulina = getValue("#ingreso_insulina_box");
    const sueroterapia1 = getValue("#ingreso_sueroterapia1_box");
    const sueroterapia2 = getValue("#ingreso_sueroterapia2_box");
    const sueroterapia3 = getValue("#ingreso_sueroterapia3_box");
    const medicacion = getValue("#ingreso_medicacion_box");
    const sangrePlasma = getValue("#ingreso_sangreplasma_box");
    const oral = getValue("#ingreso_oral_box");
    const enteral = getValue("#ingreso_enteral_box");
    const parenteral = getValue("#ingreso_parenteral_box");

    // Cálculos
    const calculoFiebre37 = peso * 0.1 * fiebre37Horas;
    const calculoFiebre38 = peso * 0.2 * fiebre38Horas;
    const calculoFiebre39 = peso * 0.3 * fiebre39Horas;
    const calculoRpm25 = peso * 0.2 * rpm25Horas;
    const calculoRpm35 = peso * 0.3 * rpm35Horas;
    const perdidasInsensibles = peso * 0.5 * horasIngreso;
    const calculoVomitosSudor =
      vomitosSudor +
      calculoFiebre37 +
      calculoFiebre38 +
      calculoFiebre39 +
      calculoRpm25 +
      calculoRpm35;
    const totalPerdidas =
      diuresis +
      sng +
      hdfvvc +
      drenajes +
      perdidasInsensibles +
      calculoVomitosSudor;

    // Agua endógena y total de ingresos
    const aguaEndogena = horasIngreso > 20 ? 400 : 20 * horasIngreso;
    const totalIngresos =
      midazolam +
      fentanest +
      propofol +
      remifentanilo +
      dexdor +
      noradrenalina +
      insulina +
      sueroterapia1 +
      sueroterapia2 +
      sueroterapia3 +
      medicacion +
      sangrePlasma +
      aguaEndogena +
      oral +
      enteral +
      parenteral;

    const balanceTotal = totalIngresos - totalPerdidas;

    // Función auxiliar para poner valor en un <input> readonly
    const setValue = (selector, val) => {
      const inp = document.querySelector(selector);
      if (inp) inp.value = val.toFixed(2);
    };
    
    setValue("#fiebre37_calculo_box", calculoFiebre37);
    setValue("#fiebre38_calculo_box", calculoFiebre38);
    setValue("#fiebre39_calculo_box", calculoFiebre39);
    setValue("#rpm25_calculo_box", calculoRpm25);
    setValue("#rpm35_calculo_box", calculoRpm35);
    setValue("#perdidas_insensibles_box", perdidasInsensibles);
    setValue("#perdida_fuerafluidos_box", calculoVomitosSudor);
    setValue("#total_perdidas_box", totalPerdidas);
    setValue("#balance_total_perdidas_box", totalPerdidas);
    setValue("#ingreso_agua_endogena_box", aguaEndogena);
    setValue("#resumen_total_ingresos_box", totalIngresos);
    setValue("#balance_total_ingresos_box", totalIngresos);

    // Balance total con color - MEJORADO para modo oscuro
    const balanceField = document.querySelector("#balance_total_box");
    if (balanceField) {
      const isDarkMode = document.body.classList.contains("active");
      
      if (balanceTotal >= 0) {
        // Balance positivo
        balanceField.style.backgroundColor = isDarkMode ? "#0070C0" : "#00A2E8";
        balanceField.style.color = "white";
      } else {
        // Balance negativo
        balanceField.style.backgroundColor = isDarkMode ? "#a31c1c" : "#ff0000";
        balanceField.style.color = "white";
      }
      
      balanceField.style.fontWeight = "bold";
      balanceField.value = balanceTotal.toFixed(2);
    }
  }

  // ——————————————————————————————
  // 12) Cambiar el texto del botón "Borrar Datos"
  // ——————————————————————————————
  function updateMainDeleteButtonText() {
    const currentBox = selectedBoxElement?.getAttribute("data-current-box");
    const btn = document.querySelector("#borrar-datos-principal");
    if (btn) {
      if (currentBox) {
        btn.textContent = `Borrar Datos del Box ${currentBox}`;
      } else {
        btn.textContent = "Borrar Datos";
      }
    }
  }

  // ——————————————————————————————
  // 13) Al clicar en "Box X"
  // ——————————————————————————————
  boxLinks.forEach((link) => {
    link.addEventListener("click", async (ev) => {
      ev.preventDefault();

      const boxNumber = link.getAttribute("data-box");
      if (!boxNumber) return;

      // Marcado visual
      boxLinks.forEach((l) => l.classList.remove("active"));
      link.classList.add("active");

      // Actualizar indicador flotante
      const indicador = document.getElementById("box-indicador-flotante");
      document.getElementById("box-indicador-num").textContent = boxNumber;
      indicador.style.display = "block";

      // Guardar Box anterior si existía
      const prevBox = selectedBoxElement?.getAttribute("data-current-box");
      if (prevBox && prevBox !== boxNumber) {
        showNotification(`Guardando Box ${prevBox}...`, 'info');
        await saveCurrentBoxData(prevBox);
      }

      // Cargar datos del box nuevo
      await loadBoxData(boxNumber);

      // Actualizar estado
      selectedBoxElement.textContent = `Has seleccionado el Box ${boxNumber}`;
      selectedBoxElement.setAttribute("data-current-box", boxNumber);
      boxSeleccionado = true;

      // Habilitar inputs
      pesoInput.disabled = false;
      horasDesdeIngresoInput.disabled = false;
      verificarHabilitacion();
      calculateDerivedValues();
      updateMainDeleteButtonText();
    });
  });

  // ——————————————————————————————
  // 14) Validar campos "horas" entre 0 y 24
  // ——————————————————————————————
  const restrictedInputs = [
    "#horas_desde_ingreso_box",
    "#fiebre37_horas_box",
    "#fiebre38_horas_box",
    "#fiebre39_horas_box",
    "#rpm25_horas_box",
    "#rpm35_horas_box",
  ];
  
  restrictedInputs.forEach((selector) => {
    const inp = document.querySelector(selector);
    if (inp) {
      inp.setAttribute("type", "number");
      inp.setAttribute("min", "0");
      inp.setAttribute("max", "24");
      inp.setAttribute("step", "1");
      inp.addEventListener("input", () => {
        let v = parseInt(inp.value, 10);
        if (isNaN(v) || v < 0) inp.value = 0;
        else if (v > 24) inp.value = 24;
        else inp.value = v;
      });
    }
  });

  // ——————————————————————————————
  // 15) Botones de borrado
  // ——————————————————————————————
  
  // Borrar todos los datos
  document.getElementById("borrar-datos-principal")?.addEventListener("click", async () => {
    const currentBox = selectedBoxElement?.getAttribute("data-current-box");
    if (!currentBox) {
      alert("No hay ningún box seleccionado.");
      return;
    }
    
    if (!confirm(`¿Estás seguro de que quieres borrar TODOS los datos del Box ${currentBox}?`)) {
      return;
    }
    
    try {
      const resp = await fetch("api.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          action: "deleteAll",
          boxNumber: currentBox,
        }),
      });
      
      const json = await resp.json();
      if (json.success) {
        allInputs.forEach((i) => {
          if (!i.hasAttribute('readonly')) {
            i.value = "";
          }
        });
        calculateDerivedValues();
        showNotification("Todos los datos han sido borrados correctamente", 'success');
      } else {
        showNotification("Error al borrar datos: " + (json.message || ""), 'error');
      }
    } catch (err) {
      console.error("Error al borrar datos:", err);
      showNotification("Error al borrar datos", 'error');
    }
  });

  // Borrar ingresos
  document.getElementById("borrar-ingresos")?.addEventListener("click", async () => {
    const currentBox = selectedBoxElement?.getAttribute("data-current-box");
    if (!currentBox) {
      alert("No hay ningún box seleccionado.");
      return;
    }
    
    if (!confirm(`¿Estás seguro de que quieres borrar los datos de INGRESOS del Box ${currentBox}?`)) {
      return;
    }
    
    try {
      const resp = await fetch("api.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          action: "deleteIngresos",
          boxNumber: currentBox,
        }),
      });
      
      const json = await resp.json();
      if (json.success) {
        document.querySelectorAll(".ingreso:not([readonly])").forEach((i) => (i.value = ""));
        calculateDerivedValues();
        showNotification("Datos de Ingresos borrados correctamente", 'success');
      } else {
        showNotification("Error al borrar datos de Ingresos: " + (json.message || ""), 'error');
      }
    } catch (err) {
      console.error("Error al borrar ingresos:", err);
      showNotification("Error al borrar ingresos", 'error');
    }
  });

  // Borrar pérdidas
  document.getElementById("borrar-perdidas")?.addEventListener("click", async () => {
    const currentBox = selectedBoxElement?.getAttribute("data-current-box");
    if (!currentBox) {
      alert("No hay ningún box seleccionado.");
      return;
    }
    
    if (!confirm(`¿Estás seguro de que quieres borrar los datos de PÉRDIDAS del Box ${currentBox}?`)) {
      return;
    }
    
    try {
      const resp = await fetch("api.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          action: "deletePerdidas",
          boxNumber: currentBox,
        }),
      });
      
      const json = await resp.json();
      if (json.success) {
        document.querySelectorAll(".perdida:not([readonly])").forEach((i) => (i.value = ""));
        calculateDerivedValues();
        showNotification("Datos de Pérdidas borrados correctamente", 'success');
      } else {
        showNotification("Error al borrar datos de Pérdidas: " + (json.message || ""), 'error');
      }
    } catch (err) {
      console.error("Error al borrar pérdidas:", err);
      showNotification("Error al borrar pérdidas", 'error');
    }
  });

  // ——————————————————————————————
  // 16) Inicialización
  // ——————————————————————————————
  verificarHabilitacion();
  calculateDerivedValues();
  
  // Inicializar modo oscuro - MUY IMPORTANTE
  initializeDarkMode();
});