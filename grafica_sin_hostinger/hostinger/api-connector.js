/**
 * Conector API para el sistema UCI
 * Conecta el frontend con la API de base de datos
 */

class UCIApiConnector {
    constructor() {
        this.baseUrl = window.location.origin + '/grafica/api';
        this.isOnline = navigator.onLine;
        
        // Escuchar cambios de conectividad
        window.addEventListener('online', () => {
            this.isOnline = true;
            this.syncPendingData();
        });
        
        window.addEventListener('offline', () => {
            this.isOnline = false;
        });
    }
    
    /**
     * Realizar petición HTTP
     */
    async makeRequest(url, options = {}) {
        try {
            const response = await fetch(`${this.baseUrl}${url}`, {
                headers: {
                    'Content-Type': 'application/json',
                    ...options.headers
                },
                ...options
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('Error en API:', error);
            throw error;
        }
    }
    
    /**
     * PACIENTES
     */
    
    // Guardar paciente
    async savePatient(patientData) {
        try {
            // Adaptar datos del frontend al formato de la API
            const apiData = {
                nombre: patientData.name,
                edad: parseInt(patientData.age) || 0,
                peso: parseFloat(patientData.weight) || null,
                historia_clinica: patientData.history || '',
                cama: parseInt(patientData.bed) || 1,
                fecha_ingreso: patientData.admission || new Date().toISOString(),
                hoja_clinica: 1,
                fecha_grafica: new Date().toISOString().split('T')[0],
                activo: 1
            };
            
            if (this.isOnline) {
                const response = await this.makeRequest('/pacientes.php', {
                    method: 'POST',
                    body: JSON.stringify(apiData)
                });
                
                if (response.success) {
                    // Guardar ID del paciente para referencias futuras
                    const patientId = response.data.id;
                    this.savePatientMapping(patientData.bed, patientId);
                    
                    console.log('✅ Paciente guardado en API:', response);
                    return response.data;
                } else {
                    throw new Error(response.message);
                }
            } else {
                // Sin conexión: guardar en localStorage para sincronizar después
                this.savePendingData('patient', patientData);
                return { offline: true, data: patientData };
            }
        } catch (error) {
            console.error('❌ Error guardando paciente:', error);
            // Fallback a localStorage
            this.savePatientLocal(patientData);
            throw error;
        }
    }
    
    // Obtener paciente por cama
    async getPatientByBed(bedNumber) {
        try {
            if (this.isOnline) {
                // Primero intentar obtener de la API
                const patientId = this.getPatientIdByBed(bedNumber);
                if (patientId) {
                    const response = await this.makeRequest(`/pacientes.php?id=${patientId}`);
                    if (response.success) {
                        return this.adaptPatientFromApi(response.data);
                    }
                }
            }
            
            // Fallback a localStorage
            return this.getPatientLocal(bedNumber);
        } catch (error) {
            console.error('❌ Error obteniendo paciente:', error);
            return this.getPatientLocal(bedNumber);
        }
    }
    
    /**
     * CONSTANTES VITALES
     */
    
    // Guardar constantes vitales
    async saveVitalSigns(patientBed, hour, vitalSigns) {
        try {
            const patientId = this.getPatientIdByBed(patientBed);
            if (!patientId && this.isOnline) {
                throw new Error('No se encontró ID del paciente');
            }
            
            // Adaptar datos para la API
            const apiData = {
                paciente_id: patientId,
                fecha_hora: this.buildDateTime(hour),
                temperatura: parseFloat(vitalSigns.temperature) || null,
                presion_sistolica: parseInt(vitalSigns.systolic) || null,
                presion_diastolica: parseInt(vitalSigns.diastolic) || null,
                frecuencia_cardiaca: parseInt(vitalSigns.pulse) || null,
                frecuencia_respiratoria: parseInt(vitalSigns.respRate) || null,
                saturacion_oxigeno: parseInt(vitalSigns.satO2) || null
            };
            
            if (this.isOnline && patientId) {
                const response = await this.makeRequest('/constantes.php', {
                    method: 'POST',
                    body: JSON.stringify(apiData)
                });
                
                if (response.success) {
                    console.log('✅ Constantes guardadas en API:', response);
                    return response.data;
                } else {
                    throw new Error(response.message);
                }
            } else {
                // Sin conexión: guardar localmente
                this.saveVitalSignsLocal(patientBed, hour, vitalSigns);
                this.savePendingData('vitals', { patientBed, hour, vitalSigns });
                return { offline: true, data: vitalSigns };
            }
        } catch (error) {
            console.error('❌ Error guardando constantes:', error);
            // Fallback a localStorage
            this.saveVitalSignsLocal(patientBed, hour, vitalSigns);
            throw error;
        }
    }
    
    // Obtener constantes vitales de un paciente
    async getVitalSigns(patientBed, date = null) {
        try {
            const patientId = this.getPatientIdByBed(patientBed);
            
            if (this.isOnline && patientId) {
                let url = `/constantes.php?paciente_id=${patientId}`;
                if (date) {
                    url += `&fecha=${date}`;
                }
                
                const response = await this.makeRequest(url);
                if (response.success) {
                    return this.adaptVitalSignsFromApi(response.data.constantes);
                }
            }
            
            // Fallback a localStorage
            return this.getVitalSignsLocal(patientBed);
        } catch (error) {
            console.error('❌ Error obteniendo constantes:', error);
            return this.getVitalSignsLocal(patientBed);
        }
    }
    
    /**
     * UTILIDADES Y ADAPTADORES
     */
    
    // Adaptar datos de paciente desde API
    adaptPatientFromApi(apiData) {
        return {
            name: apiData.nombre,
            age: apiData.edad,
            weight: apiData.peso,
            history: apiData.historia_clinica,
            bed: apiData.cama,
            admission: apiData.fecha_ingreso,
            lastUpdate: new Date().toISOString()
        };
    }
    
    // Adaptar constantes vitales desde API
    adaptVitalSignsFromApi(apiConstantes) {
        const localFormat = {};
        
        apiConstantes.forEach(constante => {
            const hour = new Date(constante.fecha_hora).getHours();
            localFormat[hour] = {
                temperature: constante.temperatura,
                pulse: constante.frecuencia_cardiaca,
                respRate: constante.frecuencia_respiratoria,
                systolic: constante.presion_sistolica,
                diastolic: constante.presion_diastolica,
                satO2: constante.saturacion_oxigeno
            };
        });
        
        return localFormat;
    }
    
    // Construir fecha y hora para la API
    buildDateTime(hour) {
        const today = new Date();
        today.setHours(hour, 0, 0, 0);
        return today.toISOString().slice(0, 19).replace('T', ' ');
    }
    
    // Mapeo de camas a IDs de pacientes
    savePatientMapping(bedNumber, patientId) {
        const mappings = JSON.parse(localStorage.getItem('patientMappings')) || {};
        mappings[bedNumber] = patientId;
        localStorage.setItem('patientMappings', JSON.stringify(mappings));
    }
    
    getPatientIdByBed(bedNumber) {
        const mappings = JSON.parse(localStorage.getItem('patientMappings')) || {};
        return mappings[bedNumber] || null;
    }
    
    // Funciones de localStorage (fallback)
    savePatientLocal(patientData) {
        const patients = JSON.parse(localStorage.getItem('patients')) || {};
        patients[patientData.bed] = {
            ...patientData,
            lastUpdate: new Date().toISOString()
        };
        localStorage.setItem('patients', JSON.stringify(patients));
    }
    
    getPatientLocal(bedNumber) {
        const patients = JSON.parse(localStorage.getItem('patients')) || {};
        return patients[bedNumber] || null;
    }
    
    saveVitalSignsLocal(patientBed, hour, vitalSigns) {
        const key = `vitalSigns_bed_${patientBed}`;
        const existing = JSON.parse(localStorage.getItem(key)) || {};
        existing[hour] = {
            ...vitalSigns,
            timestamp: new Date().toISOString()
        };
        localStorage.setItem(key, JSON.stringify(existing));
    }
    
    getVitalSignsLocal(patientBed) {
        const key = `vitalSigns_bed_${patientBed}`;
        return JSON.parse(localStorage.getItem(key)) || {};
    }
    
    // Datos pendientes de sincronización
    savePendingData(type, data) {
        const pending = JSON.parse(localStorage.getItem('pendingSync')) || [];
        pending.push({
            type,
            data,
            timestamp: new Date().toISOString()
        });
        localStorage.setItem('pendingSync', JSON.stringify(pending));
    }
    
    // Sincronizar datos pendientes cuando hay conexión
    async syncPendingData() {
        const pending = JSON.parse(localStorage.getItem('pendingSync')) || [];
        if (pending.length === 0) return;
        
        console.log(`🔄 Sincronizando ${pending.length} elementos pendientes...`);
        
        for (const item of pending) {
            try {
                if (item.type === 'patient') {
                    await this.savePatient(item.data);
                } else if (item.type === 'vitals') {
                    await this.saveVitalSigns(item.data.patientBed, item.data.hour, item.data.vitalSigns);
                }
            } catch (error) {
                console.error('❌ Error sincronizando:', error);
            }
        }
        
        // Limpiar datos sincronizados
        localStorage.removeItem('pendingSync');
        console.log('✅ Sincronización completada');
    }
    
    // Obtener estadísticas desde la API
    async getStats() {
        try {
            if (this.isOnline) {
                const response = await this.makeRequest('/reportes.php?tipo=general');
                if (response.success) {
                    return response.data;
                }
            }
            
            // Fallback a datos locales
            return this.getLocalStats();
        } catch (error) {
            console.error('❌ Error obteniendo estadísticas:', error);
            return this.getLocalStats();
        }
    }
    
    getLocalStats() {
        const patients = JSON.parse(localStorage.getItem('patients')) || {};
        const totalPatients = Object.keys(patients).length;
        const occupiedBeds = Object.values(patients).filter(p => p && p.name).length;
        
        return {
            pacientes: {
                total: totalPatients,
                activos: occupiedBeds
            },
            ocupacion_camas: Object.values(patients).filter(p => p && p.name)
        };
    }
}

// Crear instancia global
window.uciApi = new UCIApiConnector();

// Funciones de compatibilidad para el código existente
window.savePatientData = function(bedNumber, patientData) {
    return window.uciApi.savePatient({ ...patientData, bed: bedNumber });
};

window.loadPatientData = function(bedNumber) {
    return window.uciApi.getPatientLocal(bedNumber);
};

window.saveVitalSigns = function(patientBed, hour, vitalSigns) {
    return window.uciApi.saveVitalSigns(patientBed, hour, vitalSigns);
};

window.loadVitalSigns = function(patientBed) {
    return window.uciApi.getVitalSignsLocal(patientBed);
};

console.log('🚀 UCI API Connector cargado y listo');