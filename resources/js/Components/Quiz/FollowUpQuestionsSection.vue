<template>
    <div class="space-y-6">
        <div
            v-for="(questions, category) in followUpQuestions"
            :key="category"
            class="bg-slate-50 p-4 rounded-lg"
        >
            <h3 class="font-medium text-slate-900 mb-4">{{ category }}</h3>
            <div class="space-y-4">
                <div
                    v-for="(question, index) in questions"
                    :key="index"
                    class="bg-white p-4 rounded-lg border border-slate-100"
                >
                    <p class="mb-3 text-slate-900">{{ question }}</p>
                    <div class="flex flex-col space-y-2 sm:flex-row sm:space-y-0 sm:space-x-4">
                        <label
                            v-for="option in answerOptions"
                            :key="option.value"
                            class="flex items-center space-x-2 cursor-pointer p-2 rounded-md hover:bg-slate-50 transition-colors"
                        >
                            <input
                                type="radio"
                                :name="`trauma_follow_${category}_${index}`"
                                :value="option.value"
                                :checked="modelValue[`${category}_${index}`] === option.value"
                                @change="updateAnswer(`${category}_${index}`, option.value)"
                                class="form-radio h-4 w-4 text-slate-800"
                            >
                            <span class="text-sm text-slate-700">{{ option.label }}</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    followUpQuestions: {
        type: Object,
        required: true
    },
    modelValue: {
        type: Object,
        required: true
    },
    answerOptions: {
        type: Array,
        required: true
    }
});

const emit = defineEmits(['update:modelValue']);

const updateAnswer = (key, value) => {
    emit('update:modelValue', {
        ...props.modelValue,
        [key]: value
    });
};
</script>
