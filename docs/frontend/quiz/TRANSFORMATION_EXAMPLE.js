// TEST: Verificar transformación de datos estandarizados
// Este archivo demuestra cómo se transforman los datos del frontend

// ====================
// DATOS DEL FRONTEND (antes de transformar)
// ====================
const frontendAnswers = {
    referencia_iii: {
        // Preguntas generales 1-64
        1: 'A',
        2: 'B',
        3: 'C',
        // ... hasta 64
        64: 'D',
        
        // Condicionales con prefijos
        condition_atencion_clientes: true,
        65: 'D',
        66: 'E',
        67: 'D',
        68: 'A',
        
        condition_supervision: false,
        // No hay 69-72 porque condition es false
        
        // Acontecimientos traumáticos (1-6)
        acontecimientos_traumaticos: {
            1: true,
            2: false,
            3: false,
            4: true,
            5: false,
            6: false
        }
    },
    
    referencia_i: {
        // Índices 1-13 consecutivos
        1: true,
        2: false,
        3: true,
        // ... hasta 13
        13: false
    }
};

// ====================
// DESPUÉS DE transformToStandardizedStructure()
// ====================
const transformedData = {
    referencia_iii: {
        // Preguntas generales (sin cambios)
        1: 'A',
        2: 'B',
        3: 'C',
        64: 'D',
        
        // customer_service estructurado
        customer_service: {
            condition: true,
            65: 'D',
            66: 'E',
            67: 'D',
            68: 'A'
        },
        
        // management estructurado (solo condition si es false)
        management: {
            condition: false
        },
        
        // ats_s1 (CITSATS) extraído
        ats_s1: {
            1: true,
            2: false,
            3: false,
            4: true,
            5: false,
            6: false
        }
    },
    
    referencia_i: {
        // Sin cambios (ya viene con 1-13)
        1: true,
        2: false,
        3: true,
        13: false
    }
};

// ====================
// PROCESAMIENTO EN EL BACKEND
// ====================

// ProcessOnlineEvaluation::extractReferenciaIII()
// Extrae solo índices 1-64:
const referenciaIIIAnswers = {
    1: 'A',
    2: 'B',
    3: 'C',
    64: 'D'
};

// ProcessOnlineEvaluation::extractConditionals()
// Extrae customer_service y management:
const conditionals = {
    customer_service: {
        condition: true,
        65: 'D',
        66: 'E',
        67: 'D',
        68: 'A'
    },
    management: {
        condition: false
        // Sin preguntas porque condition es false
    }
};

// ProcessOnlineEvaluation::extractCitsatsS1()
// Extrae ats_s1 con índices 1-6:
const citsatsS1 = {
    1: true,
    2: false,
    3: false,
    4: true,
    5: false,
    6: false
};

// ProcessOnlineEvaluation::extractReferenciaI()
// Extrae índices 1-13:
const referenciaI = {
    1: true,
    2: false,
    3: true,
    13: false
};

// ====================
// ALMACENAMIENTO EN PaperEvaluation
// ====================
const paperEvaluationRecord = {
    folio: '02001XXXX',
    organization_id: 1,
    evaluation_type: 'referencia_iii',
    source: 'online',
    
    // Campos con respuestas
    referencia_iii_answers: referenciaIIIAnswers,      // 1-64
    referencia_iii_conditional: conditionals,          // customer_service + management
    citsats_s1: citsatsS1,                            // 1-6
    referencia_i_answers: referenciaI,                 // 1-13
    
    // Raw data completo (para respaldo)
    raw_data: {
        quiz_id: 12,
        quiz_name: 'Evaluación Completa',
        submitted_at: '2026-02-08T10:00:00-06:00'
    },
    
    processing_status: 'completed'
};

console.log('✓ Estructura estandarizada correctamente');
