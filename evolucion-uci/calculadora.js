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
        <button type="button" class="calculadora-btn cero" style="grid-column: span 2;">0</button>
        <button type="button" class="calculadora-btn operador">/</button>
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
          // Evitar operadores consecutivos
          const ultimoCaracter = display.value.slice(-1);
          const esOperador = ['+', '-', '*', '/'].includes(valorBoton);
          const ultimoEsOperador = ['+', '-', '*', '/'].includes(ultimoCaracter);
          
          if (esOperador && ultimoEsOperador) {
            display.value = display.value.slice(0, -1) + valorBoton;
          } else {
            display.value += valorBoton;
          }
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

    // Soporte de teclado - SIN DECIMALES
    document.addEventListener("keydown", (e) => {
      if (!calculadora.parentNode) return; // Si la calculadora no está visible, no hacer nada
      
      const key = e.key;
      
      if (key >= '0' && key <= '9') {
        e.preventDefault();
        display.value += key;
      } else if (['+', '-', '*', '/'].includes(key)) {
        e.preventDefault();
        const ultimoCaracter = display.value.slice(-1);
        const ultimoEsOperador = ['+', '-', '*', '/'].includes(ultimoCaracter);
        
        if (ultimoEsOperador) {
          display.value = display.value.slice(0, -1) + key;
        } else {
          display.value += key;
        }
      } else if (key === 'Enter' || key === '=') {
        e.preventDefault();
        calcularResultado(display, targetId, overlay, calculadora);
      } else if (key === 'Backspace') {
        e.preventDefault();
        display.value = display.value.slice(0, -1);
      }
      // ELIMINADO: El soporte para punto decimal (. y ,)
    });
  }

  function calcularResultado(display, targetId, overlay, calculadora) {
    try {
      // Validar que la expresión no termine en operador
      const ultimoCaracter = display.value.slice(-1);
      if (['+', '-', '*', '/'].includes(ultimoCaracter)) {
        display.value = "Error: Expresión incompleta";
        setTimeout(() => (display.value = ""), 1500);
        return;
      }

      const resultado = eval(display.value);
      
      if (!isFinite(resultado)) {
        display.value = "Error: División por cero";
        setTimeout(() => (display.value = ""), 1500);
        return;
      }

      // Redondear resultado a entero (sin decimales)
      const resultadoEntero = Math.round(resultado);
      display.value = resultadoEntero;

      const targetInput = document.getElementById(targetId);
      if (targetInput) {
        // Guardar el resultado como entero (sin decimales)
        targetInput.value = resultadoEntero;
        targetInput.dispatchEvent(new Event("input", { bubbles: true }));
      }

      setTimeout(() => {
        if (overlay.parentNode && calculadora.parentNode) {
          document.body.removeChild(overlay);
          document.body.removeChild(calculadora);
        }
      }, 1000);
    } catch (e) {
      display.value = "Error de cálculo";
      setTimeout(() => (display.value = ""), 1500);
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
    sng: "Sng",
    drenajes: "Drenajes",
    vomitos: "Vómitos, Sudor, Heces",
    default: "Calculadora",
  };
  return nombres[tipo] || nombres.default;
}