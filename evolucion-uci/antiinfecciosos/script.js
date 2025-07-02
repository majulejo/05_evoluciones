document.addEventListener("DOMContentLoaded", async function () {
    // 1. Selección de elementos del DOM
    const inicialesContainer = document.getElementById("iniciales");
    const listaAntiinfecciosos = document.getElementById("lista-antiinfecciosos");
    const detalleAntiinfeccioso = document.getElementById("detalle-antiinfeccioso");
    const zonaBusqueda = document.querySelector(".zona-busqueda");
    const inputBusqueda = document.querySelector("#buscar-input");

    // 2. Cargar datos desde el archivo JSON
    let antiinfecciosos = {};

    try {
        const response = await fetch("antiinfecciosos.json");
        if (!response.ok) {
            throw new Error(`Error al cargar el archivo JSON: ${response.status} ${response.statusText}`);
        }
        antiinfecciosos = await response.json();
        console.log("Datos cargados:", antiinfecciosos);
    } catch (error) {
        console.error("Error al cargar los datos:", error);
        alert("Hubo un problema al cargar los datos. Por favor, inténtalo de nuevo más tarde.");
        return;
    }
    // 3. Mostrar iniciales
    function mostrarIniciales() {
        inicialesContainer.innerHTML = ""; // Limpia botones previos

        Object.keys(antiinfecciosos).forEach(inicial => {
            const button = document.createElement("button");
            button.textContent = inicial;
            button.classList.add("inicial-button"); // Agregar clase para estilizar
            button.addEventListener("click", () => {
                console.log("Inicial seleccionada:", inicial); // Depuración
                mostrarAntiinfecciosos(inicial);
            });
            inicialesContainer.appendChild(button);
        });
    }

    // 4. Mostrar antiinfecciosos de una inicial seleccionada
    function mostrarAntiinfecciosos(inicial) {
        console.log("Inicial seleccionada:", inicial);

        const resultados = antiinfecciosos[inicial];
        console.log("Antiinfecciosos disponibles:", resultados);

        listaAntiinfecciosos.innerHTML = ""; // Limpia el contenedor

        if (resultados && resultados.length > 0) {
            resultados.forEach(antiinfeccioso => {
                const item = document.createElement("div");
                item.classList.add("aii-item");
                item.textContent = antiinfeccioso.name;
                item.addEventListener("click", () => mostrarDetalles(antiinfeccioso));
                listaAntiinfecciosos.appendChild(item);
            });
        } else {
            listaAntiinfecciosos.innerHTML = `No se encontraron antiinfecciosos para la inicial "${inicial}".`;
        }

        // Mostrar el contenedor
        listaAntiinfecciosos.style.display = "block";
        listaAntiinfecciosos.classList.add("active");
    }

    // 5. Mostrar detalles de un antiinfeccioso seleccionado
    function mostrarDetalles(antiinfeccioso) {
        // Ocultar las demás secciones
        document.querySelector("#iniciales").style.display = "none";
        document.querySelector(".zona-busqueda").style.display = "none";
        document.querySelector("#lista-antiinfecciosos").style.display = "none";

        // Mostrar la sección de detalles
        detalleAntiinfeccioso.style.display = "block";
    
        // Agregar los detalles del antiinfeccioso
        detalleAntiinfeccioso.innerHTML = `
        <h2>${antiinfeccioso.name}</h2>

            <table class="details-table">
        <tr>
            <th>
                <div class="th-content">
                <span>Presentación</span>
                <img src="imagenes/01_presentacion.png" alt="Icono de presentación" class="icon">      
                </div>
            </th>
            <td>${antiinfeccioso.presentation || "N/A"}</td>
        </tr>
        <tr>
            <th>
                <div class="th-content">
                <span>Tipo de Antiinfecioso</span>
                <img src="imagenes/02_tipo.png" alt="Icono de presentación" class="icon">
                </div>
            </th>
            <td>${antiinfeccioso.type || "N/A"}</td>
        </tr>
        <tr>
            <th>
                <div class="th-content">
                <span>Dosis</span>
                <img src="imagenes/03_dosis.png" alt="Icono de dosis" class="icon">                
                </div>
            </th>
            <td>${antiinfeccioso.dose || "N/A"}</td>
        </tr>
        <tr>
            <th>
                <div class="th-content">
                <span>Preparación</span>
                <img src="imagenes/04_preparacion.png" alt="Icono de preparación" class="icon">
                </div>
            </th>
            <td>${antiinfeccioso.preparation || "N/A"}</td>
        </tr>
        <tr>
            <th>
                <div class="th-content">
                <span>Aspecto</span>
                <img src="imagenes/05_aspecto.png" alt="Icono de Aspecto" class="icon">
                </div>
            </th>
            <td>${antiinfeccioso.appearance || "N/A"}</td>
        </tr>
        <tr>
            <th>
                <div class="th-content">
                <span>Tiempo de administración</span>
                <img src="imagenes/06_tiempo.png" alt="Icono de Tiempo de administración" class="icon">
                </div>
            </th>
            <td>${antiinfeccioso.administrationTime || "N/A"}</td>
        </tr>
        <tr>
            <th>
                <div class="th-content">
                <span>Conservación</span>
                <img src="imagenes/07_conservacion.png" alt="Icono de Conservación" class="icon">               
                </div>
            </th>
            <td>${antiinfeccioso.storage || "N/A"}</td>
        </tr>
        <tr>
            <th>
                <div class="th-content">
                <span>Ficha técnica</span>
                <img src="imagenes/08_ficha_tecnica.png" alt="Icono de Ficha técnica" class="icon">
                </div>
            </th>
            <td><a href="#" id="view-technical-sheet" data-url="${antiinfeccioso.technicalSheet}">Ver ficha técnica</a></td>
        </tr>
    </table>`
    ;
    const technicalSheetLink = document.getElementById("view-technical-sheet");
        technicalSheetLink.addEventListener("click", (event) => {
            event.preventDefault();
            const url = technicalSheetLink.getAttribute("data-url");
            loadTechnicalSheet(url);
        });
    }

    // Cargar la ficha técnica (versión segura con noreferrer)
function loadTechnicalSheet(url) {
    if (!url || url === "#") {
        alert("La ficha técnica no está disponible.");
        return;
    }

    // Crear un enlace temporal con rel="noreferrer"
    const link = document.createElement('a');
    link.href = url;
    link.target = '_blank';
    link.rel = 'noreferrer'; // Agrega el atributo noreferrer
    link.style.display = 'none'; // Ocultar el enlace visualmente

    // Añadir el enlace al DOM y simular un clic
    document.body.appendChild(link);
    link.click();

    // Eliminar el enlace después de usarlo
    document.body.removeChild(link);
}
    
    

    // 6. Funcionalidad de búsqueda por texto
    inputBusqueda.addEventListener("input", () => {
        const searchText = inputBusqueda.value.toLowerCase().trim();
        listaAntiinfecciosos.innerHTML = "";
        detalleAntiinfeccioso.innerHTML = "";

        if (searchText === "") {
            listaAntiinfecciosos.innerHTML = `Ingrese un término para buscar.`;
            return;
        }

        const results = Object.values(antiinfecciosos)
            .flat()
            .filter(antiinfeccioso => antiinfeccioso.name.toLowerCase().includes(searchText));

        if (results.length > 0) {
            results.forEach(antiinfeccioso => {
                const item = document.createElement("div");
                item.classList.add("aii-item");
                item.textContent = antiinfeccioso.name;
                item.addEventListener("click", () => mostrarDetalles(antiinfeccioso));
                listaAntiinfecciosos.appendChild(item);
            });
        } else {
            listaAntiinfecciosos.innerHTML = `No se encontraron resultados para "${searchText}".`;
        }

        listaAntiinfecciosos.style.display = "block";
    });

    // 7. Funcionalidad del botón de volver al inicio
    function reiniciarEstado() {
        // Ocultar secciones secundarias
        listaAntiinfecciosos.style.display = "none";
        detalleAntiinfeccioso.style.display = "none";

        // Mostrar secciones principales
        inicialesContainer.style.display = "block";
        zonaBusqueda.style.display = "block";

        // Limpiar contenido previo
        listaAntiinfecciosos.innerHTML = "";
        detalleAntiinfeccioso.innerHTML = "";

        // Volver a mostrar las iniciales
        mostrarIniciales();

        // Opcional: Redirigir al inicio de la página actual
        window.location.href = "index.html"; // Cambia "#" por la URL deseada si necesitas redirigir a otra página
    }

    // Asignar evento al botón de volver al inicio
    document.querySelector("#main-button").addEventListener("click", (e) => {
        e.preventDefault(); // Evita comportamiento predeterminado (si es un enlace)
        console.log("Botón de volver al inicio clickeado"); // Mensaje de depuración
        reiniciarEstado();
    });

    // 8. Iniciar la visualización de las iniciales
    mostrarIniciales();
});