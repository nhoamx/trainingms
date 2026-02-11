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
        const urls = {
            general: {},
            conditional: {},
            traumatic: {},
            cisneros: {},
            referencia_i: {}
        };
        
        const audioEnabled = typeof window !== 'undefined' ? window.__AUDIO_ENABLED !== false : true;

        if (!quiz || !audioEnabled || !window.__AUDIO_BASE_URL) {
            return urls;
        }
        
        const { questions, reference_i } = quiz;
        
        if (!questions) return urls;
        
        // Preguntas generales (Referencia III)
        // Mapea ID de pregunta a URL de audio: { 1: [...], 2: [...], 3: [...], ... }
        const generalQuestions = questions.general || {};
        Object.keys(generalQuestions).forEach((questionId, idx) => {
            // Usar questionId para que coincida con archivos: 1.mp3, 2.mp3, 3.mp3...
            urls.general[questionId] = getAudioUrl('general', questionId);
        });
        
        // Preguntas condicionales
        // Mapea ID de pregunta a URL de audio
        const conditionalSections = questions.conditional_sections || [];
        let conditionalIdx = 0;
        if (Array.isArray(conditionalSections)) {
            conditionalSections.forEach((section) => {
                if (section.initial_question_key) {
                    urls.conditional[section.initial_question_key] = getAudioUrl('conditional', conditionalIdx);
                    conditionalIdx++;
                }
                
                if (section.follow_up_questions && Array.isArray(section.follow_up_questions)) {
                    section.follow_up_questions.forEach((fq) => {
                        urls.conditional[fq.key] = getAudioUrl('conditional', conditionalIdx);
                        conditionalIdx++;
                    });
                }
            });
        }
        
        // Eventos traumáticos
        // Mapea índice 1-based a URL de audio: { 1: [...], 2: [...], 3: [...], ... }
        const traumaticQuestions = questions.acontecimientos_traumaticos?.questions || [];
        if (Array.isArray(traumaticQuestions)) {
            traumaticQuestions.forEach((_, idx) => {
                // idx es 0-based, pero archivos son 1-based: usar idx+1
                urls.traumatic[idx + 1] = getAudioUrl('traumatic', idx + 1);
            });
        }
        
        // Escala Cisneros
        // Mapea ID de pregunta a URL de audio
        const cisnerosQuestions = questions.escala_cisneros?.questions || {};
        Object.keys(cisnerosQuestions).forEach((questionId, idx) => {
            // Usar questionId para que coincida con archivos: 1.mp3, 2.mp3, 3.mp3...
            urls.cisneros[questionId] = getAudioUrl('cisneros', questionId);
        });
        
        // Referencia I (preguntas de seguimiento)
        // Mapea índice 1-based a URL de audio: { 1: [...], 2: [...], 3: [...], ... }
        if (Array.isArray(reference_i)) {
            let globalIdx = 1;
            Object.entries(reference_i).forEach(([category, questions]) => {
                if (Array.isArray(questions)) {
                    questions.forEach((_, localIdx) => {
                        // Usar globalIdx directamente para archivos 1-based: 1.mp3, 2.mp3, 3.mp3...
                        urls.referencia_i[globalIdx] = getAudioUrl('referencia_i', globalIdx);
                        globalIdx++;
                    });
                }
            });
        }
        
        return urls;
    });
}

/**
 * Genera URLs de audio con múltiples formatos para fallback automático
 * Soporta múltiples formatos de audio (mp3, m4a, wav, ogg) con fallback
 * 
 * @param {string} questionType - Tipo de pregunta (general, conditional, traumatic, cisneros, referencia_i)
 * @param {string|number} questionId - Identificador de la pregunta (clave o índice)
 * @returns {Array<{src: string, type: string}>} Array de objetos con URLs y tipos MIME para fallback
 */
function getAudioUrl(questionType, questionId) {
    const baseUrl = window.__AUDIO_BASE_URL || '/storage/audio';
    
    // Formatos soportados en orden de preferencia con sus tipos MIME
    // El navegador intentará cargar en orden hasta encontrar uno compatible
    const formats = [
        { ext: 'mp3', type: 'audio/mpeg' },
        { ext: 'm4a', type: 'audio/mp4' },
        { ext: 'ogg', type: 'audio/ogg' },
        { ext: 'wav', type: 'audio/wav' }
    ];
    
    // Retornar array de URLs con todos los formatos posibles
    return formats.map(format => ({
        src: `${baseUrl}/${questionType}/${questionId}.${format.ext}`,
        type: format.type
    }));
}
