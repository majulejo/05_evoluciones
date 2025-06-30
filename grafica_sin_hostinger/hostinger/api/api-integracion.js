// ========================== //
// INTEGRACIÓN CON APIs DE BASE DE DATOS //
// ========================== //

/**
 * Configuración de la API
 */
const API_CONFIG = {
    baseURL: window.location.origin,
    endpoints: {
        pacientes: '/api/pacientes.php',
        constantes: '/api/constantes.php',
        oxigenacion: '/api/oxigenacion.php',
        perdidas: '/api/perdidas.php',
        sync: '/api/sync.php'
    },
    timeout: 10000,
    retries: 3
};

/**
 * Clase para manejar las llamadas a la API
 */
class UCIApiClient {
    constructor() {
        this.baseURL = API_CONFIG.baseURL;
        this.currentPacienteId = null;
        this.isOnline = navigator.onLine;
        this.initializeEventListeners();
    }

    /**
     * Inicializar listeners para eventos de red
     */
    initializeEventListeners() {
        window.addEventListener('online', () => {
            this.isOnline = true;
            console.log('🌐 Conexión restaurada');
            this.sincronizarPendientes();
        });

        window.addEventListener('offline', () => {
            this.isOnline = false;
            console.log('📴 Sin conexión - modo offline');
        });
    }

    /**
     * Realizar petición HTTP con reintentos
     */
    async makeRequest(url, options = {}) {
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            timeout: API_CONFIG.timeout
        };

        const finalOptions = { ...defaultOptions, ...options };

        for (let attempt = 1; attempt <= API_CONFIG.retries; attempt++) {
            try {
                console.log(`🔄 API Request (intento ${attempt}):`, url);
                
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), finalOptions.timeout);
                
                const response = await fetch(url, {
                    ...finalOptions,
                    signal: controller.signal
                });
                
                clearTimeout(timeoutId);

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message || 'Error en la respuesta de la API');
                }

                console.log('✅ API Response:', data);
                return data;

            } catch (error) {
                console.warn(`⚠️ Intento ${attempt} falló:`, error.message);
                
                if (attempt === API_CONFIG.retries) {
                    console.error('❌ Todos los intentos fallaron:', error);
                    throw new Error(`Error de API después de ${API_CONFIG.retries} intentos: ${error.message}`);
                }
                
                // Esperar antes del siguiente intento
                await this.delay(1000 * attempt);
            }
        }
    }

    /**
     * Delay helper
     */
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    /**
     * Obtener paciente por cama
     */
    async obtenerPacientePorCama(cama, fecha = null) {
        try {
            const params = new URLSearchParams({ cama });
            if (fecha) params.append('fecha', fecha);
            
            const url = `${this.baseURL}${API_CONFIG.endpoints.pacientes}?${params}`;
            const response = await this.makeRequest(url);
            
            if (response.data) {
                this.currentPacienteId = response.data.id;
                return response.data;
            }
            
            return null;
        } catch (error) {
            console.error('Error al obtener paciente por cama:', error);
            return null;
        }
    }

    /**
     * Crear nuevo paciente
     */
    async crearPaciente(datosDelPaciente) {
        try {
            const url = `${this.baseURL}${API_CONFIG.endpoints.pacientes}`;
            const response = await this.makeRequest(url, {
                method: 'POST',
                body: JSON.stringify(datosDelPaciente)
            });
            
            if (response.data) {
                this.currentPacienteId = response.data.id;
                console.log('✅ Paciente creado:', response.data);
                return response.data;
            }
            
            throw new Error('No se recibieron datos del paciente creado');
        } catch (error) {
            console.error('Error al crear paciente:', error);
            throw error;
        }
    }

    /**
     * Actualizar paciente
     */
    async actualizarPaciente(pacienteId, datosActualizados) {
        try {
            const url = `${this.baseURL}${API_CONFIG.endpoints.pacientes}`;
            const response = await this.makeRequest(url, {
                method: 'PUT',
                body: JSON.stringify({ id: pacienteId, ...datosActualizados })
            });
            
            console.log('✅ Paciente actualizado:', response.data);
            return response.data;
        } catch (error) {
            console.error('Error al actualizar paciente:', error);
            throw error;
        }
    }

    /**
     * Cargar todos los datos de un paciente
     */
    async cargarDatosCompletos(pacienteId) {
        try {
            const url = `${this.baseURL}${API_CONFIG.endpoints.sync}?paciente_id=${pacienteId}`;
            const response = await this.makeRequest(url);
            
            console.log('✅ Datos completos cargados:', response.data);
            return response.data;
        } catch (error) {
            console.error('Error al cargar datos completos:', error);
            return null;
        }
    }

    /**
     * Sincronizar datos con la base de datos
     */
    async sincronizarDatos(pacienteId, datosParaSincronizar) {
        try {
            const url = `${this.baseURL}${API_CONFIG.endpoints.sync}`;
            const response = await this.makeRequest(url, {
                method: 'POST',
                body: JSON.stringify({
                    paciente_id: pacienteId,
                    ...datosParaSincronizar
                })
            });
            
            console.log('✅ Sincronización completada:', response.data);
            return response.data;
        } catch (error) {
            console.error('Error en sincronización:', error);
            
            // Guardar para sincronizar más tarde si estamos offline
            if (!this.isOnline) {
                this.guardarParaSincronizacionPosterior(pacienteId, datosParaSincronizar);
            }
            
            throw error;
        }
    }

    /**
     * Guardar constantes vitales específicas
     */
    async guardarConstantesVitales(pacienteId, vitalSigns) {
        try {
            const url = `${this.baseURL}${API_CONFIG.endpoints.constantes}`;
            const response = await this.makeRequest(url, {
                method: 'PUT',
                body: JSON.stringify({
                    paciente_id: pacienteId,
                    constantes: vitalSigns
                })
            });
            
            console.log('✅ Constantes vitales guardadas');
            return response.data;
        } catch (error) {
            console.error('Error al guardar constantes vitales:', error);
            throw error;
        }
    }

    /**
     * Guardar datos de sección 2
     */
    async guardarSection2(pacienteId, section2Data) {
        try {
            const url = `${this.baseURL}${API_CONFIG.endpoints.oxigenacion}`;
            const response = await this.makeRequest(url, {
                method: 'PUT',
                body: JSON.stringify({
                    paciente_id: pacienteId,
                    section2Data: section2Data
                })
            });
            
            console.log('✅ Sección 2 guardada');
            return response.data;
        } catch (error) {
            console.error('Error al guardar sección 2:', error);
            throw error;
        }
    }

    /**
     * Guardar datos de sección 3
     */
    async guardarSection3(pacienteId, section3Data) {
        try {
            const url = `${this.baseURL}${API_CONFIG.endpoints.perdidas}`;
            const response = await this.makeRequest(url, {
                method: 'PUT',
                body: JSON.stringify({
                    paciente_id: pacienteId,
                    section3Data: section3Data
                })
            });
            
            console.log('✅ Sección 3 guardada');
            return response.data;
        } catch (error) {
            console.error('Error al guardar sección 3:', error);
            throw error;
        }
    }

    /**
     * Obtener resumen de ocupación de camas
     */
    async obtenerResumenCamas(fecha = null) {
        try {
            const params = new URLSearchParams({ resumen_camas: '1' });
            if (fecha) params.append('fecha', fecha);
            
            const url = `${this.baseURL}${API_CONFIG.endpoints.pacientes}?${params}`;
            const response = await this.makeRequest(url);
            
            return response.data;
        } catch (error) {
            console.error('Error al obtener resumen de camas:', error);
            return null;
        }
    }

    /**
     * Test de conectividad
     */
    async testConectividad() {
        try {
            const url = `${this.baseURL}${API_CONFIG.endpoints.sync}?test_connection=1`;
            const response = await this.makeRequest(url);
            
            console.log('✅ Test de conectividad exitoso:', response.data);
            return true;
        } catch (error) {
            console.error('❌ Test de conectividad falló:', error);
            return false;
        }
    }

    /**
     * Guardar datos para sincronización posterior (offline)
     */
    guardarParaSincronizacionPosterior(pacienteId, datos) {
        try {
            const pendientes = JSON.parse(localStorage.getItem('syncPendiente')) || [];
            
            pendientes.push({
                pacienteId,
                datos,
                timestamp: new Date().toISOString(),
                tipo: 'sincronizacion'
            });
            
            localStorage.setItem('syncPendiente', JSON.stringify(pendientes));
            console.log('💾 Datos guardados para sincronización posterior');
        } catch (error) {
            console.error('Error al guardar para sincronización posterior:', error);
        }
    }

    /**
     * Sincronizar datos pendientes cuando se recupere la conexión
     */
    async sincronizarPendientes() {
        try {
            const pendientes = JSON.parse(localStorage.getItem('syncPendiente')) || [];
            
            if (pendientes.length === 0) {
                return;
            }
            
            console.log(`🔄 Sincronizando ${pendientes.length} elementos pendientes...`);
            
            for (const item of pendientes) {
                try {
                    await this.sincronizarDatos(item.pacienteId, item.datos);
                    console.log('✅ Elemento sincronizado:', item.timestamp);
                } catch (error) {
                    console.error('❌ Error al sincronizar elemento:', error);
                    // Mantener el elemento para intentar más tarde
                    continue;
                }
            }
            
            // Limpiar elementos sincronizados
            localStorage.removeItem('syncPendiente');
            console.log('✅ Sincronización de pendientes completada');
            
        } catch (error) {
            console.error('Error en sincronización de pendientes:', error);
        }
    }
}

// ========================== //
// FUNCIONES DE INTEGRACIÓN CON EL SISTEMA EXISTENTE //
// ========================== //

// Instancia global del cliente API
const apiClient = new UCIApiClient();

/**
 * Función mejorada para cargar datos del paciente desde la API
 */
async function loadPatientDataFromAPI(cama) {
    try {
        console.log(`🔄 Cargando datos del paciente de la cama ${cama} desde API...`);
        
        const pacienteData = await apiClient.obtenerPacientePorCama(cama);
        
        if (pacienteData) {
            // Actualizar campos del formulario
            if (pacienteData.paciente) {
                const p = pacienteData.paciente;
                setElementValue('patientName', p.nombre);
                setElementValue('patientAge', p.edad);
                setElementValue('patientPeso', p.peso);
                setElementValue('patientHistory', p.historia_clinica);
                setElementValue('patientBed', p.cama);
                setElementValue('patientIngreso', p.fecha_ingreso);
            }
            
            // Cargar constantes vitales si existen
            if (pacienteData.vitalSigns) {
                vitalSigns = pacienteData.vitalSigns;
                updateChart();
            }
            
            // Cargar sección 2 si existe
            if (pacienteData.section2Data) {
                section2Data = pacienteData.section2Data;
                if (typeof initializeSection2 === 'function') {
                    initializeSection2();
                }
            }
            
            // Cargar sección 3 si existe
            if (pacienteData.section3Data) {
                section3Data = pacienteData.section3Data;
                if (typeof initializeSection3 === 'function') {
                    initializeSection3();
                }
            }
            
            console.log('✅ Datos del paciente cargados desde API');
            return pacienteData;
        } else {
            console.log('ℹ️ No hay paciente en esa cama');
            return null;
        }
        
    } catch (error) {
        console.error('❌ Error al cargar datos desde API:', error);
        
        // Fallback: intentar cargar desde localStorage
        console.log('🔄 Intentando cargar desde localStorage...');
        return loadPatientDataInChart();
    }
}

/**
 * Función mejorada para guardar datos del paciente en la API
 */
async function savePatientDataToAPI() {
    try {
        const selectedBed = localStorage.getItem('selectedBed');
        if (!selectedBed) {
            throw new Error('No hay cama seleccionada');
        }

        // Recopilar datos del paciente
        const pacienteData = {
            nombre: getElementValue('patientName', ''),
            edad: getElementValue('patientAge', ''),
            peso: getElementValue('patientPeso', ''),
            historia_clinica: getElementValue('patientHistory', ''),
            cama: getElementValue('patientBed', ''),
            fecha_ingreso: getElementValue('patientIngreso', ''),
            fecha_grafica: getElementValue('patientDate', '') || new Date().toISOString().split('T')[0],
            hoja_clinica: getElementValue('patientSheet', '') || 1
        };

        // Verificar si es un paciente nuevo o existente
        let pacienteId = apiClient.currentPacienteId;
        
        if (!pacienteId) {
            // Intentar obtener paciente existente por cama
            const pacienteExistente = await apiClient.obtenerPacientePorCama(selectedBed);
            pacienteId = pacienteExistente ? pacienteExistente.id : null;
        }

        let resultado;
        if (pacienteId) {
            // Actualizar paciente existente
            resultado = await apiClient.actualizarPaciente(pacienteId, pacienteData);
        } else {
            // Crear nuevo paciente
            resultado = await apiClient.crearPaciente(pacienteData);
            pacienteId = resultado.id;
        }

        // Guardar ID para futuras operaciones
        apiClient.currentPacienteId = pacienteId;
        localStorage.setItem('currentPacienteId', pacienteId);

        console.log('✅ Datos del paciente guardados en API');
        return resultado;

    } catch (error) {
        console.error('❌ Error al guardar datos del paciente en API:', error);
        
        // Fallback: guardar en localStorage
        console.log('💾 Guardando en localStorage como fallback...');
        saveCurrentPatientDataFromChart();
        throw error;
    }
}

/**
 * Función para sincronizar todos los datos con la API
 */
async function syncAllDataToAPI() {
    try {
        let pacienteId = apiClient.currentPacienteId || localStorage.getItem('currentPacienteId');
        
        if (!pacienteId) {
            // Intentar obtener por cama
            const selectedBed = localStorage.getItem('selectedBed');
            if (selectedBed) {
                const paciente = await apiClient.obtenerPacientePorCama(selectedBed);
                if (paciente) {
                    pacienteId = paciente.id;
                    apiClient.currentPacienteId = pacienteId;
                    localStorage.setItem('currentPacienteId', pacienteId);
                }
            }
        }

        if (!pacienteId) {
            throw new Error('No se puede sincronizar sin ID de paciente');
        }

        // Preparar datos para sincronización
        const datosParaSincronizar = {};

        // Incluir datos del paciente si han cambiado
        const pacienteData = {
            nombre: getElementValue('patientName', ''),
            edad: getElementValue('patientAge', ''),
            peso: getElementValue('patientPeso', ''),
            historia_clinica: getElementValue('patientHistory', ''),
            cama: getElementValue('patientBed', ''),
            fecha_ingreso: getElementValue('patientIngreso', '')
        };

        // Solo incluir si hay datos válidos
        if (pacienteData.nombre) {
            datosParaSincronizar.paciente_data = pacienteData;
        }

        // Incluir constantes vitales si existen
        if (typeof vitalSigns !== 'undefined' && vitalSigns.length > 0) {
            datosParaSincronizar.vitalSigns = vitalSigns;
        }

        // Incluir sección 2 si existe
        if (typeof section2Data !== 'undefined' && section2Data) {
            datosParaSincronizar.section2Data = section2Data;
        }

        // Incluir sección 3 si existe
        if (typeof section3Data !== 'undefined' && section3Data) {
            datosParaSincronizar.section3Data = section3Data;
        }

        // Realizar sincronización
        const resultado = await apiClient.sincronizarDatos(pacienteId, datosParaSincronizar);
        
        console.log('✅ Sincronización completa exitosa');
        
        // Mostrar notificación de éxito
        mostrarNotificacion('Datos sincronizados correctamente', 'success');
        
        return resultado;

    } catch (error) {
        console.error('❌ Error en sincronización completa:', error);
        
        // Mostrar notificación de error
        mostrarNotificacion('Error al sincronizar datos', 'error');
        
        throw error;
    }
}

/**
 * Función para auto-guardado periódico
 */
function initAutoSyncToAPI() {
    // Auto-sync cada 5 minutos
    setInterval(async () => {
        try {
            if (apiClient.currentPacienteId && navigator.onLine) {
                console.log('🔄 Auto-sincronización iniciada...');
                await syncAllDataToAPI();
                console.log('✅ Auto-sincronización completada');
            }
        } catch (error) {
            console.warn('⚠️ Auto-sincronización falló:', error);
        }
    }, 5 * 60 * 1000); // 5 minutos

    // Sync al cambiar de página/cerrar
    window.addEventListener('beforeunload', () => {
        if (apiClient.currentPacienteId && navigator.onLine) {
            // Usar sendBeacon para envío rápido antes de cerrar
            const data = {
                paciente_id: apiClient.currentPacienteId,
                vitalSigns: typeof vitalSigns !== 'undefined' ? vitalSigns : [],
                section2Data: typeof section2Data !== 'undefined' ? section2Data : null,
                section3Data: typeof section3Data !== 'undefined' ? section3Data : null
            };
            
            const blob = new Blob([JSON.stringify(data)], { type: 'application/json' });
            navigator.sendBeacon(`${API_CONFIG.baseURL}${API_CONFIG.endpoints.sync}`, blob);
        }
    });
}

/**
 * Función para mostrar notificaciones al usuario
 */
function mostrarNotificacion(mensaje, tipo = 'info') {
    // Crear elemento de notificación
    const notificacion = document.createElement('div');
    notificacion.className = `notificacion notificacion-${tipo}`;
    notificacion.innerHTML = `
        <span class="notificacion-icono">${getIconoNotificacion(tipo)}</span>
        <span class="notificacion-mensaje">${mensaje}</span>
        <button class="notificacion-cerrar" onclick="this.parentElement.remove()">×</button>
    `;

    // Agregar estilos si no existen
    if (!document.getElementById('notificacion-styles')) {
        const styles = document.createElement('style');
        styles.id = 'notificacion-styles';
        styles.textContent = `
            .notificacion {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                z-index: 10000;
                display: flex;
                align-items: center;
                gap: 10px;
                font-family: Arial, sans-serif;
                font-size: 14px;
                max-width: 300px;
                animation: slideIn 0.3s ease-out;
            }
            .notificacion-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
            .notificacion-error { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
            .notificacion-warning { background: #fff3cd; color: #856404; border-left: 4px solid #ffc107; }
            .notificacion-info { background: #d1ecf1; color: #0c5460; border-left: 4px solid #17a2b8; }
            .notificacion-cerrar {
                background: none;
                border: none;
                font-size: 18px;
                cursor: pointer;
                padding: 0;
                margin-left: auto;
            }
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        `;
        document.head.appendChild(styles);
    }

    // Agregar al DOM
    document.body.appendChild(notificacion);

    // Auto-remover después de 5 segundos
    setTimeout(() => {
        if (notificacion.parentElement) {
            notificacion.remove();
        }
    }, 5000);
}

function getIconoNotificacion(tipo) {
    const iconos = {
        success: '✅',
        error: '❌',
        warning: '⚠️',
        info: 'ℹ️'
    };
    return iconos[tipo] || iconos.info;
}

/**
 * Función para cargar lista de pacientes (para index.html)
 */
async function cargarResumenCamas() {
    try {
        const resumen = await apiClient.obtenerResumenCamas();
        
        if (resumen) {
            console.log('✅ Resumen de camas cargado:', resumen);
            
            // Actualizar interfaz si estamos en index.html
            if (typeof updateBedsDisplay === 'function') {
                updateBedsDisplay(resumen);
            }
            
            return resumen;
        }
    } catch (error) {
        console.error('❌ Error al cargar resumen de camas:', error);
        
        // Fallback: mostrar datos desde localStorage
        if (typeof loadBedsFromLocalStorage === 'function') {
            return loadBedsFromLocalStorage();
        }
    }
}

/**
 * Función mejorada para guardar datos específicos
 */
async function saveCurrentDataToAPI() {
    try {
        // Guardar datos del paciente primero
        await savePatientDataToAPI();
        
        // Luego sincronizar todo
        await syncAllDataToAPI();
        
        mostrarNotificacion('Datos guardados correctamente', 'success');
        
    } catch (error) {
        console.error('❌ Error al guardar datos:', error);
        mostrarNotificacion('Error al guardar datos', 'error');
        
        // Fallback: guardar en localStorage
        if (typeof saveCurrentPatientDataFromChart === 'function') {
            saveCurrentPatientDataFromChart();
        }
    }
}

// ========================== //
// SOBRESCRIBIR FUNCIONES EXISTENTES PARA USAR API //
// ========================== //

/**
 * Sobrescribir función de guardado para usar API
 */
const originalSaveCurrentData = window.saveCurrentData;
window.saveCurrentData = async function() {
    try {
        if (navigator.onLine && apiClient.currentPacienteId) {
            await saveCurrentDataToAPI();
        } else {
            // Usar función original como fallback
            if (originalSaveCurrentData) {
                return originalSaveCurrentData();
            }
        }
    } catch (error) {
        console.error('Error en saveCurrentData:', error);
        // Usar función original como fallback
        if (originalSaveCurrentData) {
            return originalSaveCurrentData();
        }
    }
};

/**
 * Sobrescribir función de carga para usar API
 */
const originalLoadPatientDataInChart = window.loadPatientDataInChart;
window.loadPatientDataInChart = async function() {
    try {
        const selectedBed = localStorage.getItem('selectedBed');
        
        if (navigator.onLine && selectedBed) {
            const data = await loadPatientDataFromAPI(selectedBed);
            if (data) {
                return data;
            }
        }
        
        // Usar función original como fallback
        if (originalLoadPatientDataInChart) {
            return originalLoadPatientDataInChart();
        }
        
    } catch (error) {
        console.error('Error en loadPatientDataInChart:', error);
        // Usar función original como fallback
        if (originalLoadPatientDataInChart) {
            return originalLoadPatientDataInChart();
        }
    }
};

// ========================== //
// INICIALIZACIÓN //
// ========================== //

/**
 * Inicializar integración con API cuando el DOM esté listo
 */
document.addEventListener('DOMContentLoaded', async function() {
    try {
        console.log('🔄 Inicializando integración con API...');
        
        // Test de conectividad inicial
        const conectividad = await apiClient.testConectividad();
        
        if (conectividad) {
            console.log('✅ API disponible');
            
            // Inicializar auto-sync
            initAutoSyncToAPI();
            
            // Sincronizar datos pendientes si los hay
            await apiClient.sincronizarPendientes();
            
            // Si estamos en grafica.html, cargar datos desde API
            if (window.location.pathname.includes('grafica.html')) {
                const selectedBed = localStorage.getItem('selectedBed');
                if (selectedBed) {
                    await loadPatientDataFromAPI(selectedBed);
                }
            }
            
            // Si estamos en index.html, cargar resumen de camas
            if (window.location.pathname.includes('index.html') || window.location.pathname === '/') {
                await cargarResumenCamas();
            }
            
        } else {
            console.warn('⚠️ API no disponible - usando modo offline');
        }
        
    } catch (error) {
        console.error('❌ Error al inicializar integración con API:', error);
    }
});

// ========================== //
// FUNCIONES GLOBALES ADICIONALES //
// ========================== //

/**
 * Función para forzar sincronización manual
 */
window.forceSyncToAPI = async function() {
    try {
        mostrarNotificacion('Iniciando sincronización...', 'info');
        await syncAllDataToAPI();
    } catch (error) {
        console.error('Error en sincronización manual:', error);
    }
};

/**
 * Función para verificar estado de API
 */
window.checkAPIStatus = async function() {
    try {
        const status = await apiClient.testConectividad();
        mostrarNotificacion(
            status ? 'API disponible' : 'API no disponible', 
            status ? 'success' : 'warning'
        );
        return status;
    } catch (error) {
        mostrarNotificacion('Error al verificar API', 'error');
        return false;
    }
};

/**
 * Función para limpiar datos locales y recargar desde API
 */
window.reloadFromAPI = async function() {
    try {
        const selectedBed = localStorage.getItem('selectedBed');
        if (!selectedBed) {
            mostrarNotificacion('No hay cama seleccionada', 'warning');
            return;
        }
        
        mostrarNotificacion('Recargando datos desde servidor...', 'info');
        
        // Limpiar datos locales
        vitalSigns = Array(24).fill().map(() => ({}));
        if (typeof section2Data !== 'undefined') {
            section2Data = {
                pneumo: Array(24).fill(""),
                oxygen: Array(24).fill(""),
                saturation: Array(24).fill(""),
                eva: Array(24).fill().map(() => ({ eva: "", rass: "" })),
                glucose: Array(24).fill(""),
                insulin: Array(24).fill().map(() => ({
                    value: "", type: "S/P", recommended: "", message: ""
                }))
            };
        }
        
        // Recargar desde API
        await loadPatientDataFromAPI(selectedBed);
        
        // Actualizar interfaz
        if (typeof updateChart === 'function') {
            updateChart();
        }
        if (typeof initializeSection2 === 'function') {
            initializeSection2();
        }
        if (typeof initializeSection3 === 'function') {
            initializeSection3();
        }
        
        mostrarNotificacion('Datos recargados correctamente', 'success');
        
    } catch (error) {
        console.error('Error al recargar desde API:', error);
        mostrarNotificacion('Error al recargar datos', 'error');
    }
};

// Hacer disponibles las funciones globalmente
window.apiClient = apiClient;
window.loadPatientDataFromAPI = loadPatientDataFromAPI;
window.savePatientDataToAPI = savePatientDataToAPI;
window.syncAllDataToAPI = syncAllDataToAPI;
window.cargarResumenCamas = cargarResumenCamas;
window.mostrarNotificacion = mostrarNotificacion;

console.log('✅ Integración con API inicializada');