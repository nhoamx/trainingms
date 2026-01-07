import { computed } from 'vue';

/**
 * Composable para generar URLs de audio para preguntas de quiz
 * Maneja la generación dinámica de URLs de archivos de audio
 * 
 * @param {Object} quiz - Objeto del quiz con la estructura de preguntas
 * @returns {Object} audioUrls - Objeto computed con las URLs de audio mapeadas
 */
export function useAudioUrls(quiz) {
    return computed(() => {
        const urls = {};
        
        const audioEnabled = typeof window !== 'undefined' ? window.__AUDIO_ENABLED !== false : true;

        if (!quiz || !audioEnabled || !window.__AUDIO_BASE_URL) {
            return urls;
        }
        
        const { questions, reference_i } = quiz;
        
        if (!questions) return urls;
        
        // Preguntas generales (Referencia III)
        // Usa índices numéricos: 0, 1, 2, ... → 0.mp3, 1.mp3, 2.mp3
        const generalQuestions = questions.general || {};
        Object.entries(generalQuestions).forEach((_, idx) => {
            urls[idx] = getAudioUrl('general', idx);
        });
        
        // Preguntas condicionales
        // Usa índices numéricos para cada sección y sus preguntas de seguimiento
        const conditionalSections = questions.conditional_sections || [];
        let conditionalIdx = 0;
        if (Array.isArray(conditionalSections)) {
            conditionalSections.forEach((section) => {
                if (section.initial_question_key) {
                    urls[`conditional_${conditionalIdx}`] = getAudioUrl('conditional', conditionalIdx);
                    conditionalIdx++;
                }
                
                if (section.follow_up_questions && Array.isArray(section.follow_up_questions)) {
                    section.follow_up_questions.forEach(() => {
                        urls[`conditional_${conditionalIdx}`] = getAudioUrl('conditional', conditionalIdx);
                        conditionalIdx++;
                    });
                }
            });
        }
        
        // Eventos traumáticos
        // Usa índices numéricos: 0, 1, 2, ... → 0.mp3, 1.mp3, 2.mp3
        const traumaticQuestions = questions.acontecimientos_traumaticos?.questions || [];
        if (Array.isArray(traumaticQuestions)) {
            traumaticQuestions.forEach((_, idx) => {
                urls[`traumatic_${idx}`] = getAudioUrl('traumatic', idx);
            });
        }
        
        // Escala Cisneros
        // Usa índices numéricos: 0, 1, 2, ... → 0.mp3, 1.mp3, 2.mp3
        const cisnerosQuestions = questions.escala_cisneros?.questions || {};
        Object.entries(cisnerosQuestions).forEach((_, idx) => {
            urls[`cisneros_${idx}`] = getAudioUrl('cisneros', idx);
        });
        
        // Referencia I (preguntas de seguimiento)
        // Usa índices numéricos: 0, 1, 2, ... → 0.mp3, 1.mp3, 2.mp3
        if (Array.isArray(reference_i)) {
            reference_i.forEach((_, idx) => {
                urls[`referencia_i_${idx}`] = getAudioUrl('referencia_i', idx);
            });
        }
        
        return urls;
    });
}

/**
 * Genera la URL del audio basándose en el tipo de pregunta y su identificador
 * 
 * @param {string} questionType - Tipo de pregunta (general, conditional, traumatic, cisneros, referencia_i)
 * @param {string|number} questionId - Identificador de la pregunta (clave o índice)
 * @returns {string} URL del archivo de audio
 */
function getAudioUrl(questionType, questionId) {
    // Aquí se usa la configuración de audio que será pasada desde el backend
    // Por ahora retorna un patrón que será reemplazado por la URL real desde el servidor
    // La configuración será inyectada a través de props o una ruta configurada
    
    const baseUrl = window.__AUDIO_BASE_URL || '/storage/audio';
    
    return `${baseUrl}/${questionType}/${questionId}.mp3`;
}
