<template>
    <div
        v-for="question in paginatedQuestions"
        :key="question.id"
        class="border-b border-slate-100 last:border-0 pb-6 last:pb-0 mb-6"
        :class="{ 'bg-slate-50 p-4 rounded-lg': viewMode === 'comfortable' }"
    >
        <p class="mb-3 text-slate-900">{{ question.id }}. {{ question.text }}</p>
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
            <label
                v-for="option in answerOptions"
                :key="option.value"
                class="flex items-center space-x-2 cursor-pointer p-2 rounded-md hover:bg-slate-50 transition-colors"
            >
                <input
                    type="radio"
                    :name="`question_${question.id}`"
                    :value="option.value"
                    :checked="modelValue[question.id] === option.value"
                    @change="updateAnswer(question.id, option.value)"
                    class="form-radio h-4 w-4 text-slate-800"
                >
                <span class="text-sm text-slate-700">{{ option.label }}</span>
            </label>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    paginatedQuestions: {
        type: Array,
        required: true
    },
    modelValue: {
        type: Object,
        required: true
    },
    answerOptions: {
        type: Array,
        required: true
    },
    viewMode: {
        type: String,
        default: 'comfortable'
    }
});

const emit = defineEmits(['update:modelValue']);

const updateAnswer = (questionId, value) => {
    emit('update:modelValue', {
        ...props.modelValue,
        [questionId]: value
    });
};
</script>
