<template>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-2 mb-4">
        <div v-for="risk in riskLevels" :key="risk"
            :class="[
                'text-center p-2 rounded-md shadow border-2',
                risk === 'Nulo' ? 'bg-blue-100 text-blue-800 border-blue-300' :
                risk === 'Bajo' ? 'bg-green-100 text-green-800 border-green-300' :
                risk === 'Medio' ? 'bg-yellow-100 text-yellow-800 border-yellow-300' :
                risk === 'Alto' ? 'bg-orange-100 text-orange-800 border-orange-300' :
                'bg-red-100 text-red-800 border-red-300'
            ]"
            :title="`Personas en nivel ${risk}`"
        >
            <div class="text-xs font-medium mb-1">{{ risk }}</div>
            <div class="font-bold text-sm sm:text-base">{{ itemData.risk_levels[risk] || 0 }}</div>
            <div class="text-xs">
                {{ itemData.total > 0 ? ((itemData.risk_levels[risk] || 0) / itemData.total * 100).toFixed(1) : '0.0' }}%
            </div>
        </div>
    </div>
</template>

<script setup>
import { defineProps } from 'vue';

const props = defineProps({
    itemData: {
        type: Object,
        required: true
    }
});

const riskLevels = ['Nulo', 'Bajo', 'Medio', 'Alto', 'Muy Alto'];
</script>
