<template>
    <div :class="`overflow-x-auto ${className}`">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th v-for="column in columns" 
                        :key="column.key"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                    >
                        {{ column.header }}
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="row in data" :key="row.id">
                    <td v-for="column in columns" 
                        :key="column.key"
                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                    >
                        <slot :name="column.key" :row="row">
                            {{ typeof column.accessor === 'function' ? column.accessor(row) : row[column.accessor || column.key] }}
                        </slot>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
defineProps({
    data: {
        type: Array,
        required: true
    },
    columns: {
        type: Array,
        required: true
    },
    className: {
        type: String,
        default: ''
    }
})
</script>