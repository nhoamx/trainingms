<script setup>
import { computed } from 'vue';

const props = defineProps({
    qualificationsData: { // Expects Array: [{id, name, qualifications: {Nulo, ...}}, ...]
        type: Array,
        required: true,
        default: () => []
    }
});

// Define levels and colors (same as category table)
const levels = [
    { key: 'Nulo', label: 'Nulo', color: 'bg-teal-100', textColor: 'text-teal-800' },
    { key: 'Bajo', label: 'Bajo', color: 'bg-green-200', textColor: 'text-green-800' },
    { key: 'Medio', label: 'Medio', color: 'bg-yellow-200', textColor: 'text-yellow-800' },
    { key: 'Alto', label: 'Alto', color: 'bg-orange-300', textColor: 'text-orange-800' },
    { key: 'Muy Alto', label: 'Muy Alto', color: 'bg-red-400', textColor: 'text-red-800' },
];

// Calculate total attention count per domain (Medio + Alto + Muy Alto)
const reportWithAttention = computed(() => {
    return props.qualificationsData.map(item => {
        const counts = item.qualifications;
        const attentionCount = (counts['Medio'] || 0) + (counts['Alto'] || 0) + (counts['Muy Alto'] || 0);
        return {
            ...item, // Keep id and name
            qualificationsWithAttention: { ...counts, Atencion: attentionCount }
        };
    });
});

// Cell class logic (same as category table)
const getCellClass = (levelKey, count) => {
    if (count > 0) {
        const levelConfig = levels.find(l => l.key === levelKey);
        if (levelConfig && ['Bajo', 'Medio', 'Alto', 'Muy Alto'].includes(levelKey)) {
            return `${levelConfig.color} ${levelConfig.textColor}`;
        }
    }
    if (levelKey === 'Nulo' && count > 0) {
        const levelConfig = levels.find(l => l.key === 'Nulo');
        return levelConfig ? `${levelConfig.color} ${levelConfig.textColor}` : '';
    }
    return 'bg-white text-gray-900';
};

</script>

<template>
    <div class="overflow-x-auto shadow-md sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-100">
                 <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dominio</th> <!-- Changed label -->
                    <th v-for="level in levels" :key="level.key" scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ level.label }}</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Atención</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="item in reportWithAttention" :key="item.id">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ item.name }} <!-- Domain name -->
                    </td>
                    <td v-for="level in levels" :key="level.key"
                        :class="getCellClass(level.key, item.qualificationsWithAttention[level.key])"
                        class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-center transition-colors duration-200">
                        {{ item.qualificationsWithAttention[level.key] > 0 ? item.qualificationsWithAttention[level.key] : '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-center text-gray-700 bg-gray-50">
                        {{ item.qualificationsWithAttention.Atencion > 0 ? item.qualificationsWithAttention.Atencion : '-' }}
                    </td>
                </tr>
                <tr v-if="reportWithAttention.length === 0">
                     <td :colspan="levels.length + 2" class="px-6 py-4 text-center text-gray-500">
                         No hay datos de calificación por dominio disponibles.
                     </td>
                 </tr>
            </tbody>
        </table>
    </div>
</template>

<style scoped>
td {
    transition: background-color 0.3s ease;
}
</style>
