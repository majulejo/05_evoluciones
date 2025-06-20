// script_dark.js-----

document.addEventListener("DOMContentLoaded", () => {
  // ——————————————————————————————
  // 1) Seleccionar los elementos del DOM
  // ——————————————————————————————
  const pesoInput = document.querySelector("#peso_box");
  const horasDesdeIngresoInput = document.querySelector(
    "#horas_desde_ingreso_box"
  );
  const boxLosses = document.querySelector("#box-losses");
  const boxEarings = document.querySelector("#box-earings");
  const selectedBoxElement = document.querySelector("#selected-box h2");
  const boxLinks = document.querySelectorAll("#box-navigation ul li a");

  // Reúne todos los <input> cuyo id termina en "_box"
  const allInputs = Array.from(document.querySelectorAll("input")).filter(
    (input) => input.id && input.id.endsWith("_box")
  );
  allInputs.forEach((input) => {
    input.addEventListener("change", async () => {
      // Solo guardamos si ya hay un Box seleccionado
      const currentBox = selectedBoxElement?.getAttribute("data-current-box");
      if (!currentBox) return;
      console.log("Campo cambiado, guardando Box", currentBox);
      await saveCurrentBoxData(currentBox);
    });
  });

  // Bandera para saber si ya se pulsó algún Box
  let boxSeleccionado = false;

  // Antes de seleccionar ningún Box, deshabilitamos “Pérdidas” y “Ingresos”
  if (boxLosses) boxLosses.classList.add("disabled");
  if (boxEarings) boxEarings.classList.add("disabled");
  // También deshabilitamos los inputs de “peso” y “horas”
  if (pesoInput) pesoInput.disabled = true;
  if (horasDesdeIngresoInput) horasDesdeIngresoInput.disabled = true;

  // ——————————————————————————————
  // 2) Función para habilitar/deshabilitar paneles
  // ——————————————————————————————
  function verificarHabilitacion() {
    const pesoValido = pesoInput && pesoInput.value.trim() !== "";
    const horasValido =
      horasDesdeIngresoInput && horasDesdeIngresoInput.value.trim() !== "";
    if (pesoValido && horasValido && boxSeleccionado) {
      if (boxLosses) boxLosses.classList.remove("disabled");
      if (boxEarings) boxEarings.classList.remove("disabled");
    } else {
      if (boxLosses) boxLosses.classList.add("disabled");
      if (boxEarings) boxEarings.classList.add("disabled");
    }
  }

  // ——————————————————————————————
  // 3) Guardar datos del Box anterior (action = "save")
  // ——————————————————————————————
  async function saveCurrentBoxData(boxNumber) {
    if (!boxNumber) return;

    const dataToSend = {};
    allInputs.forEach((input) => {
      dataToSend[input.id] = input.value;
    });

    try {
      const response = await fetch("api.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          action: "save",
          userId: 1, // estamos forzando UserId = 1 para depurar
          boxNumber,
          data: dataToSend,
        }),
      });

      console.log("[saveCurrentBoxData] HTTP status:", response.status);

      let json;
      try {
        json = await response.json();
        console.log("[saveCurrentBoxData] Respuesta JSON de api.php:", json);
      } catch (eParse) {
        const raw = await response.text();
        console.error(
          "[saveCurrentBoxData] No es JSON válido. Contenido crudo:",
          raw
        );
        throw new Error("Respuesta inválida del servidor");
      }

      if (!json.success) {
        console.error(
          `[saveCurrentBoxData] El servidor devolvió success:false → mensaje:`,
          json.message
        );
        alert(`Error al guardar Box ${boxNumber}:\n${json.message}`);
        throw new Error("Error al guardar los datos");
      }

      console.log(`[saveCurrentBoxData] Guardado exitoso Box ${boxNumber}`);
    } catch (err) {
      console.error(
        `[saveCurrentBoxData] Excepción al intentar guardar Box ${boxNumber}:`,
        err
      );
    }
  }

  // ——————————————————————————————
  // 4) Cargar datos de un Box (action = "load")
  // ——————————————————————————————
  async function loadBoxData(boxNumber) {
    if (!boxNumber) return;

    try {
      const resp = await fetch("api.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          action: "load",
          boxNumber,
        }),
      });
      const data = await resp.json();
      // Si devuelve algún campo, lo volcamos a cada <input>
      if (data && Object.keys(data).length > 0) {
        allInputs.forEach((input) => {
          input.value = data[input.id] || "";
        });
      } else {
        // Si no existe fila en la BD, limpiamos todos los inputs
        allInputs.forEach((input) => (input.value = ""));
      }
      verificarHabilitacion();
      calculateDerivedValues();
    } catch (err) {
      console.error("Error al cargar datos del Box", boxNumber, err);
      alert("Error al cargar datos. Inténtalo de nuevo.");
    }
  }

  // ——————————————————————————————
  // 5) Cálculo de valores derivados (totales, fiebre, etc.)
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

    // Cálculos de fiebre y pérdidas insensibles
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

    // Función auxiliar para escribir en un <span> o <div> (opcional)
    const updateText = (selector, val) => {
      const el = document.querySelector(selector);
      if (el) el.textContent = val.toFixed(2);
    };
    updateText("#total-ingresos-balance", totalIngresos);
    updateText("#total-perdidas-balance", totalPerdidas);
    updateText("#balance-total", balanceTotal);

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

    // Finalmente, actualiza el campo de balance total con color
    const balanceField = document.querySelector("#balance_total_box");
    if (balanceField) {
      const isDarkMode = document.body.classList.contains("active");
      balanceField.style.backgroundColor =
        balanceTotal >= 0
          ? isDarkMode
            ? "#0070C0"
            : "#00A2E8"
          : isDarkMode
          ? "#a31c1c"
          : "#ff0000";
      balanceField.style.fontWeight = "bold";
      balanceField.value = balanceTotal.toFixed(2);
    }
  }

  // ——————————————————————————————
  // 6) Cambiar el texto del botón “Borrar Datos” según el Box seleccionado
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
  // 7) Al clicar en “Box X”
  // ——————————————————————————————
boxLinks.forEach((link) => {
  link.addEventListener("click", async (ev) => {
    ev.preventDefault();

    // 1) Leo el número de box desde el atributo data antes de todo
    const boxNumber = link.getAttribute("data-box");
    if (!boxNumber) return;

    // 2) Marcado visual: quito “active” de todos y lo añado al clicado
    boxLinks.forEach((l) => l.classList.remove("active"));
    link.classList.add("active");

    // 3) Actualizo y muestro el indicador flotante
    const indicador = document.getElementById("box-indicador-flotante");
    document.getElementById("box-indicador-num").textContent = boxNumber;
    indicador.style.display = "block";

    // 4) Guardar el Box anterior (si existía)
    const prevBox = selectedBoxElement?.getAttribute("data-current-box");
    if (prevBox) {
      await saveCurrentBoxData(prevBox);
    }

    // 5) Cargo datos del box nuevo
    await loadBoxData(boxNumber);

    // 6) Actualizo el texto y estado interno
    selectedBoxElement.textContent = `Has seleccionado el Box ${boxNumber}`;
    selectedBoxElement.setAttribute("data-current-box", boxNumber);
    boxSeleccionado = true;

    // 7) Habilito inputs y recalculo todo
    pesoInput.disabled = false;
    horasDesdeIngresoInput.disabled = false;
    verificarHabilitacion();
    calculateDerivedValues();
    updateMainDeleteButtonText();
  });
});


  // ——————————————————————————————
  // 8) Cada vez que cambie cualquier input, volvemos a calcular
  // ——————————————————————————————
  allInputs.forEach((input) => {
    input.addEventListener("input", () => {
      verificarHabilitacion();
      calculateDerivedValues();
    });
  });

  // ——————————————————————————————
  // 9) Validar campos “horas” entre 0 y 24
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
  // 10) Botón “Borrar Datos” (action = "deleteAll")
  // ——————————————————————————————
  document
    .getElementById("borrar-datos-principal")
    ?.addEventListener("click", async () => {
      const currentBox = selectedBoxElement?.getAttribute("data-current-box");
      if (!currentBox) {
        alert("No hay ningún box seleccionado.");
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
          allInputs.forEach((i) => (i.value = ""));
          calculateDerivedValues();
          alert("Todos los datos han sido borrados correctamente.");
        } else {
          alert("Error al borrar datos: " + (json.message || ""));
        }
      } catch (err) {
        console.error("Error al borrar datos:", err);
        alert("Error al borrar datos. Inténtalo de nuevo.");
      }
    });

  // ——————————————————————————————
  // 11) Botón “Borrar Ingresos” (action = "deleteIngresos")
  // ——————————————————————————————
  document
    .getElementById("borrar-ingresos")
    ?.addEventListener("click", async () => {
      const currentBox = selectedBoxElement?.getAttribute("data-current-box");
      if (!currentBox) {
        alert("No hay ningún box seleccionado.");
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
          document
            .querySelectorAll(".ingreso:not([readonly])")
            .forEach((i) => (i.value = ""));
          calculateDerivedValues();
          alert("Datos de Ingresos borrados correctamente.");
        } else {
          alert("Error al borrar datos de Ingresos: " + (json.message || ""));
        }
      } catch (err) {
        console.error("Error al borrar ingresos:", err);
        alert("Error al borrar ingresos. Inténtalo de nuevo.");
      }
    });

  // ——————————————————————————————
  // 12) Botón “Borrar Pérdidas” (action = "deletePerdidas")
  // ——————————————————————————————
  document
    .getElementById("borrar-perdidas")
    ?.addEventListener("click", async () => {
      const currentBox = selectedBoxElement?.getAttribute("data-current-box");
      if (!currentBox) {
        alert("No hay ningún box seleccionado.");
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
          document
            .querySelectorAll(".perdida:not([readonly])")
            .forEach((i) => (i.value = ""));
          calculateDerivedValues();
          alert("Datos de Pérdidas borrados correctamente.");
        } else {
          alert("Error al borrar datos de Pérdidas: " + (json.message || ""));
        }
      } catch (err) {
        console.error("Error al borrar pérdidas:", err);
        alert("Error al borrar pérdidas. Inténtalo de nuevo.");
      }
    });

  // ——————————————————————————————
  // 13) Primera verificación y primer cálculo al cargar la página
  // ——————————————————————————————
  verificarHabilitacion();
  calculateDerivedValues();
});
