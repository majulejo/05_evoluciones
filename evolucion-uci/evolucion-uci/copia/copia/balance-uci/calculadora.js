document.addEventListener("DOMContentLoaded", function () {
  function setupCalculadora() {
    const calculadoraIconos = document.querySelectorAll(".calculadora-icon");

    if (!calculadoraIconos.length) {
      console.error("No se encontraron iconos de calculadora");
      return;
    }

    calculadoraIconos.forEach((icono) => {
      icono.addEventListener("click", (e) => {
        e.stopPropagation();
        const targetId = icono.getAttribute("data-target");
        const tipoCalculadora = icono.getAttribute("data-type");
        if (targetId && tipoCalculadora) {
          mostrarCalculadora(targetId, tipoCalculadora);
        }
      });
    });
  }

  function positionCalculator(calculator) {
    if (!calculator) return;
    calculator.style.position = "fixed";
    calculator.style.top = "50%";
    calculator.style.left = "50%";
    calculator.style.transform = "translate(-50%, -50%)";
    calculator.style.zIndex = "1001";
  }

  function mostrarCalculadora(targetId, tipo) {
    // Crear overlay
    const overlay = document.createElement("div");
    overlay.className = "calculadora-overlay";

    // Crear calculadora
    const calculadora = document.createElement("div");
    calculadora.className = `calculadora-container calculadora-${tipo}`;
    calculadora.innerHTML = `
      <div class="calculadora-header">
<span>${nombreAmigable(tipo)}</span>
    <button class="calculadora-cerrar"><i class='bx bx-x-circle'></i></button>
      </div>
      <input type="text" class="calculadora-display" readonly>
      <div class="calculadora-botones">
        <button type="button" class="calculadora-btn">7</button>
        <button type="button" class="calculadora-btn">8</button>
        <button type="button" class="calculadora-btn">9</button>
        <button type="button" class="calculadora-btn operador">+</button>
        <button type="button" class="calculadora-btn">4</button>
        <button type="button" class="calculadora-btn">5</button>
        <button type="button" class="calculadora-btn">6</button>
        <button type="button" class="calculadora-btn operador">-</button>
        <button type="button" class="calculadora-btn">1</button>
        <button type="button" class="calculadora-btn">2</button>
        <button type="button" class="calculadora-btn">3</button>
        <button type="button" class="calculadora-btn operador">*</button>
        <button type="button" class="calculadora-btn limpiar">C</button>
        <button type="button" class="calculadora-btn">0</button>
        <button type="button" class="calculadora-btn">.</button>
        <button type="button" class="calculadora-btn igual">=</button>
      </div>
    `;

    // Añadir al DOM
    document.body.appendChild(overlay);
    document.body.appendChild(calculadora);

    // Centrado responsive
    positionCalculator(calculadora);

    // Manejar eventos
    const display = calculadora.querySelector(".calculadora-display");
    const btnCerrar = calculadora.querySelector(".calculadora-cerrar");

    calculadora.querySelectorAll(".calculadora-btn").forEach((boton) => {
      boton.addEventListener("click", () => {
        const valorBoton = boton.textContent;
        if (valorBoton === "=") {
          calcularResultado(display, targetId, overlay, calculadora);
        } else if (valorBoton === "C") {
          display.value = "";
        } else {
          display.value += valorBoton;
        }
      });
    });

    // Función para cerrar calculadora
    const cerrarCalculadora = () => {
      document.body.removeChild(overlay);
      document.body.removeChild(calculadora);
      window.removeEventListener("resize", handleResize);
      document.removeEventListener("keydown", handleEscape);
    };

    // Event listeners
    const handleResize = () => positionCalculator(calculadora);
    const handleEscape = (e) => e.key === "Escape" && cerrarCalculadora();

    window.addEventListener("resize", handleResize);
    btnCerrar.addEventListener("click", cerrarCalculadora);
    overlay.addEventListener("click", cerrarCalculadora);
    document.addEventListener("keydown", handleEscape);
  }

  function calcularResultado(display, targetId, overlay, calculadora) {
    try {
      const resultado = eval(display.value);
      display.value = resultado;

      const targetInput = document.getElementById(targetId);
      if (targetInput) {
        targetInput.value = resultado;
        targetInput.dispatchEvent(new Event("input", { bubbles: true }));
      }

      setTimeout(() => {
        document.body.removeChild(overlay);
        document.body.removeChild(calculadora);
      }, 1000);
    } catch (e) {
      display.value = "Error";
      setTimeout(() => (display.value = ""), 1000);
    }
  }

  // Inicialización
  setTimeout(setupCalculadora, 100);
});
function nombreAmigable(tipo) {
  const nombres = {
    medicacion: "Medicación",
    sangre: "Sangre/Plasma",
    oral: "Vía Oral",
    default: "Calculadora",
  };
  return nombres[tipo] || nombres.default;
}
