<template>
  <!-- Readonly: table matching RecommendationsTab style -->
  <div v-if="readonly" class="overflow-x-auto">
    <div v-if="factors.length === 0" class="rounded-lg bg-gray-50 p-6 text-center text-sm text-gray-400 italic">
      {{ t('No factors added yet.') }}
    </div>
    <table v-else class="w-full table-fixed border-collapse">
      <thead>
        <tr>
          <th class="border-b border-gray-200 p-4 pt-0 pb-3 text-left text-sm font-semibold text-gray-900" style="width: 30%">
            {{ t('Factor') }}
          </th>
          <th class="border-b border-gray-200 p-4 pt-0 pb-3 text-left text-sm font-semibold text-gray-900" style="width: 48%">
            {{ t('Action') }}
          </th>
          <th class="border-b border-gray-200 p-4 pt-0 pb-3 text-left text-sm font-semibold text-gray-900" style="width: 22%">
            {{ t('Department') }}
          </th>
        </tr>
      </thead>
      <tbody class="bg-white text-justify">
        <tr v-for="factor in factors" :key="factor.id">
          <td class="border-b border-gray-100 px-6 py-4 text-gray-800 align-top">
            <span class="block text-base font-bold leading-snug">{{ factor.title }}</span>
            <p class="mt-1 text-sm leading-relaxed text-gray-600">{{ factor.description }}</p>
          </td>
          <td class="border-b border-gray-100 p-4 align-top text-gray-800">
            <ol v-if="factor.actions.length" class="list-decimal list-inside space-y-1.5 marker:font-bold">
              <li v-for="(action, i) in factor.actions" :key="i" class="text-sm">{{ action }}</li>
            </ol>
          </td>
          <td class="border-b border-gray-100 p-4 align-top text-gray-500">
            <ul v-if="factor.departments.length" class="space-y-1">
              <li v-for="(dept, i) in factor.departments" :key="i" class="text-sm">{{ dept }}</li>
            </ul>
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- Edit mode: card-based form -->
  <div v-else class="space-y-4">
    <!-- Empty state -->
    <p v-if="factors.length === 0" class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-6 text-center text-sm text-gray-400 italic">
      {{ t('No factors yet. Click "Add Factor" to begin.') }}
    </p>

    <!-- Factor cards -->
    <div
      v-for="(factor, index) in factors"
      :key="factor.id"
      class="rounded-lg border border-gray-200 bg-white shadow-sm"
    >
      <!-- Card header -->
      <div class="flex items-center justify-between gap-2 border-b border-gray-100 bg-gray-50 px-4 py-2.5">
        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">
          {{ t('Factor') }} {{ index + 1 }}
          <span v-if="factor.title" class="ml-1 font-normal normal-case text-gray-700">— {{ factor.title }}</span>
        </span>
        <div class="flex items-center gap-1">
          <button type="button" :disabled="index === 0" @click="moveUp(index)"
            class="rounded p-1 text-gray-400 hover:bg-gray-200 hover:text-gray-700 disabled:opacity-30"
            title="Mover arriba">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
            </svg>
          </button>
          <button type="button" :disabled="index === factors.length - 1" @click="moveDown(index)"
            class="rounded p-1 text-gray-400 hover:bg-gray-200 hover:text-gray-700 disabled:opacity-30"
            title="Mover abajo">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
          <button type="button" @click="removeFactor(index)"
            class="ml-1 rounded p-1 text-red-400 hover:bg-red-50 hover:text-red-600"
            title="Eliminar factor">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-0 divide-y divide-gray-100 md:grid-cols-3 md:divide-x md:divide-y-0">
        <!-- Column 1: Factor -->
        <div class="p-4">
          <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-teal-700">{{ t('Factor') }}</p>
          <input
            v-model="factor.title"
            type="text"
            :placeholder="t('Factor title, e.g. Work-Life Balance')"
            class="mb-2 w-full rounded-md border border-gray-300 px-3 py-1.5 text-sm font-semibold focus:border-teal-500 focus:ring-1 focus:ring-teal-500"
            @input="emit('update:modelValue', serialize())"
          />
          <textarea
            v-model="factor.description"
            :placeholder="t('Short description of what this factor measures...')"
            rows="4"
            class="w-full resize-none rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 focus:border-teal-500 focus:ring-1 focus:ring-teal-500"
            @input="emit('update:modelValue', serialize())"
          />
        </div>

        <!-- Column 2: Actions (numbered list) -->
        <div class="p-4">
          <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-teal-700">{{ t('Action') }}</p>
          <ol class="space-y-1.5">
            <li v-for="(action, ai) in factor.actions" :key="ai" class="flex items-start gap-2">
              <span class="mt-1.5 min-w-[1.25rem] text-xs font-bold text-gray-400">{{ ai + 1 }}.</span>
              <input
                v-model="factor.actions[ai]"
                type="text"
                :placeholder="`${t('Action')} ${ai + 1}`"
                class="min-w-0 flex-1 rounded-md border border-gray-300 px-2.5 py-1 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500"
                @input="emit('update:modelValue', serialize())"
              />
              <button type="button" @click="removeItem(factor.actions, ai)"
                class="mt-0.5 rounded p-1 text-gray-300 hover:text-red-400"
                title="Eliminar">
                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </li>
          </ol>
          <button type="button" @click="addItem(factor.actions)"
            class="mt-2 flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium text-teal-600 hover:bg-teal-50">
            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            {{ t('Add action') }}
          </button>
        </div>

        <!-- Column 3: Departments (bullet list) -->
        <div class="p-4">
          <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-teal-700">{{ t('Department') }}</p>
          <ul class="space-y-1.5">
            <li v-for="(dept, di) in factor.departments" :key="di" class="flex items-start gap-2">
              <span class="mt-1.5 text-gray-400">•</span>
              <input
                v-model="factor.departments[di]"
                type="text"
                :placeholder="`${t('Department')} ${di + 1}`"
                class="min-w-0 flex-1 rounded-md border border-gray-300 px-2.5 py-1 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500"
                @input="emit('update:modelValue', serialize())"
              />
              <button type="button" @click="removeItem(factor.departments, di)"
                class="mt-0.5 rounded p-1 text-gray-300 hover:text-red-400"
                title="Eliminar">
                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </li>
          </ul>
          <button type="button" @click="addItem(factor.departments)"
            class="mt-2 flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium text-teal-600 hover:bg-teal-50">
            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            {{ t('Add department') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Add factor button -->
    <button
      type="button"
      @click="addFactor"
      class="flex w-full items-center justify-center gap-2 rounded-lg border-2 border-dashed border-gray-300 py-3 text-sm font-medium text-gray-500 transition hover:border-teal-400 hover:text-teal-600"
    >
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
      </svg>
      {{ t('Add Factor') }}
    </button>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { useTranslations } from '@/composables/useTranslations';

interface RecommendationFactor {
    id: string;
    title: string;
    description: string;
    actions: string[];
    departments: string[];
}

interface Props {
    modelValue: string;
    readonly?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    readonly: false,
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const { t } = useTranslations();

const parse = (value: string): RecommendationFactor[] => {
    if (!value) { return []; }
    try {
        const parsed = JSON.parse(value);
        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
};

const serialize = (): string => JSON.stringify(factors.value);

const factors = ref<RecommendationFactor[]>(parse(props.modelValue));

watch(
    () => props.modelValue,
    (value) => {
        const parsed = parse(value);
        if (JSON.stringify(parsed) !== JSON.stringify(factors.value)) {
            factors.value = parsed;
        }
    },
);

const newId = (): string => `f_${Date.now()}_${Math.random().toString(36).slice(2, 7)}`;

const addFactor = (): void => {
    factors.value.push({
        id: newId(),
        title: '',
        description: '',
        actions: [''],
        departments: [''],
    });
    emit('update:modelValue', serialize());
};

const removeFactor = (index: number): void => {
    factors.value.splice(index, 1);
    emit('update:modelValue', serialize());
};

const moveUp = (index: number): void => {
    if (index === 0) { return; }
    const arr = factors.value;
    [arr[index - 1], arr[index]] = [arr[index], arr[index - 1]];
    emit('update:modelValue', serialize());
};

const moveDown = (index: number): void => {
    const arr = factors.value;
    if (index === arr.length - 1) { return; }
    [arr[index], arr[index + 1]] = [arr[index + 1], arr[index]];
    emit('update:modelValue', serialize());
};

const addItem = (list: string[]): void => {
    list.push('');
    emit('update:modelValue', serialize());
};

const removeItem = (list: string[], index: number): void => {
    list.splice(index, 1);
    emit('update:modelValue', serialize());
};
</script>
