import { computed } from 'vue';

/**
 * Composable para generar URLs de video para preguntas de quiz
 * Maneja la generación dinámica de URLs de archivos de video
 * Sigue el mismo patrón que useAudioUrls para consistencia
 * 
 * @param {Object} quiz - Objeto del quiz con la estructura de preguntas
 * @returns {Object} videoUrls - Objeto computed con las URLs de video mapeadas
 */
export function useVideoUrls(quiz) {
    return computed(() => {
        const urls = {};
        
        const videoEnabled = typeof window !== 'undefined' ? window.__VIDEO_ENABLED !== false : true;

        if (!quiz || !videoEnabled || !window.__VIDEO_BASE_URL) {
            return urls;
        }
        
        const { questions, reference_i } = quiz;
        
        if (!questions) return urls;
        
        // Preguntas generales (Referencia III)
        // Usa índices numéricos: 0, 1, 2, ... → 0.mp4, 1.mp4, 2.mp4
        const generalQuestions = questions.general || {};
        Object.entries(generalQuestions).forEach((_, idx) => {
            urls[idx] = getVideoUrl('general', idx);
        });
        
        // Preguntas condicionales
        // Usa índices numéricos para cada sección y sus preguntas de seguimiento
        const conditionalSections = questions.conditional_sections || [];
        let conditionalIdx = 0;
        if (Array.isArray(conditionalSections)) {
            conditionalSections.forEach((section) => {
                if (section.initial_question_key) {
                    urls[`conditional_${conditionalIdx}`] = getVideoUrl('conditional', conditionalIdx);
                    conditionalIdx++;
                }
                
                if (section.follow_up_questions && Array.isArray(section.follow_up_questions)) {
                    section.follow_up_questions.forEach(() => {
                        urls[`conditional_${conditionalIdx}`] = getVideoUrl('conditional', conditionalIdx);
                        conditionalIdx++;
                    });
                }
            });
        }
        
        // Eventos traumáticos
        // Usa índices numéricos: 0, 1, 2, ... → 0.mp4, 1.mp4, 2.mp4
        const traumaticQuestions = questions.acontecimientos_traumaticos?.questions || [];
        if (Array.isArray(traumaticQuestions)) {
            traumaticQuestions.forEach((_, idx) => {
                urls[`traumatic_${idx}`] = getVideoUrl('traumatic', idx);
            });
        }
        
        // Escala Cisneros
        // Usa índices numéricos: 0, 1, 2, ... → 0.mp4, 1.mp4, 2.mp4
        const cisnerosQuestions = questions.escala_cisneros?.questions || {};
        Object.entries(cisnerosQuestions).forEach((_, idx) => {
            urls[`cisneros_${idx}`] = getVideoUrl('cisneros', idx);
        });
        
        // Referencia I (preguntas de seguimiento)
        // Usa índices numéricos: 0, 1, 2, ... → 0.mp4, 1.mp4, 2.mp4
        if (Array.isArray(reference_i)) {
            reference_i.forEach((_, idx) => {
                urls[`referencia_i_${idx}`] = getVideoUrl('referencia_i', idx);
            });
        }
        
        return urls;
    });
}

/**
 * Genera la URL del video basándose en el tipo de pregunta y su identificador
 * 
 * @param {string} questionType - Tipo de pregunta (general, conditional, traumatic, cisneros, referencia_i)
 * @param {string|number} questionId - Identificador de la pregunta (clave o índice)
 * @returns {string} URL del archivo de video
 */
function getVideoUrl(questionType, questionId) {
    const baseUrl = window.__VIDEO_BASE_URL || '/storage/video';
    
    return `${baseUrl}/${questionType}/${questionId}.mp4`;
}
