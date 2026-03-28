<template>
  <!-- Readonly: 5-column table matching FodaDataTab style -->
  <div v-if="readonly" class="overflow-x-auto">
    <div v-if="items.length === 0" class="rounded-lg bg-gray-50 p-6 text-center text-sm text-gray-400 italic">
      {{ t('No SWOT entries added yet.') }}
    </div>
    <table v-else class="w-full table-fixed border-collapse">
      <thead>
        <tr>
          <th class="border-b border-gray-200 p-4 pt-0 pb-3 text-left text-sm font-semibold text-gray-900" style="width: 20%">{{ t('Factor') }}</th>
          <th class="border-b border-gray-200 p-4 pt-0 pb-3 text-left text-sm font-semibold text-gray-900" style="width: 20%">{{ t('Strength') }}</th>
          <th class="border-b border-gray-200 p-4 pt-0 pb-3 text-left text-sm font-semibold text-gray-900" style="width: 20%">{{ t('Weakness') }}</th>
          <th class="border-b border-gray-200 p-4 pt-0 pb-3 text-left text-sm font-semibold text-gray-900" style="width: 20%">{{ t('Opportunity') }}</th>
          <th class="border-b border-gray-200 p-4 pt-0 pb-3 text-left text-sm font-semibold text-gray-900" style="width: 20%">{{ t('Threat') }}</th>
        </tr>
      </thead>
      <tbody class="bg-white text-justify">
        <tr v-for="item in items" :key="item.id">
          <td class="border-b border-gray-200 px-6 py-4 align-top font-semibold text-gray-800">{{ item.factor }}</td>
          <td class="border-b border-gray-100 px-6 py-4 align-top text-sm text-gray-700">{{ item.fortaleza }}</td>
          <td class="border-b border-gray-100 px-6 py-4 align-top text-sm text-gray-700">{{ item.debilidad }}</td>
          <td class="border-b border-gray-100 px-6 py-4 align-top text-sm text-gray-700">{{ item.oportunidad }}</td>
          <td class="border-b border-gray-100 px-6 py-4 align-top text-sm text-gray-700">{{ item.amenaza }}</td>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- Edit mode: card-based form -->
  <div v-else class="space-y-4">
    <p v-if="items.length === 0" class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-6 text-center text-sm text-gray-400 italic">
      {{ t('No SWOT entries yet. Click "Add Row" to begin.') }}
    </p>

    <div
      v-for="(item, index) in items"
      :key="item.id"
      class="rounded-lg border border-gray-200 bg-white shadow-sm"
    >
      <!-- Card header -->
      <div class="flex items-center justify-between gap-2 border-b border-gray-100 bg-gray-50 px-4 py-2.5">
        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">
          {{ t('Row') }} {{ index + 1 }}
          <span v-if="item.factor" class="ml-1 font-normal normal-case text-gray-700">— {{ item.factor }}</span>
        </span>
        <div class="flex items-center gap-1">
          <button type="button" :disabled="index === 0" @click="moveUp(index)"
            class="rounded p-1 text-gray-400 hover:bg-gray-200 hover:text-gray-700 disabled:opacity-30">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
            </svg>
          </button>
          <button type="button" :disabled="index === items.length - 1" @click="moveDown(index)"
            class="rounded p-1 text-gray-400 hover:bg-gray-200 hover:text-gray-700 disabled:opacity-30">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
          <button type="button" @click="removeItem(index)"
            class="ml-1 rounded p-1 text-red-400 hover:bg-red-50 hover:text-red-600">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <div class="p-4 space-y-4">
        <!-- Factor name -->
        <div>
          <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-teal-700">{{ t('Factor') }}</label>
          <input
            v-model="item.factor"
            type="text"
            :placeholder="t('e.g. Safe Work Environment')"
            class="w-full rounded-md border border-gray-300 px-3 py-1.5 text-sm font-semibold focus:border-teal-500 focus:ring-1 focus:ring-teal-500"
            @blur="sync"
          />
        </div>

        <!-- 2×2 SWOT quadrants -->
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <div>
            <label class="mb-1.5 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide">
              <span class="inline-block h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
              <span class="text-emerald-700">{{ t('Strength') }}</span>
            </label>
            <textarea
              v-model="item.fortaleza"
              :placeholder="t('Describe the strength...')"
              rows="3"
              class="w-full resize-none rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 focus:border-teal-500 focus:ring-1 focus:ring-teal-500"
              @blur="sync"
            />
          </div>
          <div>
            <label class="mb-1.5 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide">
              <span class="inline-block h-2.5 w-2.5 rounded-full bg-red-500"></span>
              <span class="text-red-700">{{ t('Weakness') }}</span>
            </label>
            <textarea
              v-model="item.debilidad"
              :placeholder="t('Describe the weakness...')"
              rows="3"
              class="w-full resize-none rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 focus:border-red-400 focus:ring-1 focus:ring-red-400"
              @blur="sync"
            />
          </div>
          <div>
            <label class="mb-1.5 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide">
              <span class="inline-block h-2.5 w-2.5 rounded-full bg-blue-500"></span>
              <span class="text-blue-700">{{ t('Opportunity') }}</span>
            </label>
            <textarea
              v-model="item.oportunidad"
              :placeholder="t('Describe the opportunity...')"
              rows="3"
              class="w-full resize-none rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 focus:border-blue-400 focus:ring-1 focus:ring-blue-400"
              @blur="sync"
            />
          </div>
          <div>
            <label class="mb-1.5 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide">
              <span class="inline-block h-2.5 w-2.5 rounded-full bg-amber-500"></span>
              <span class="text-amber-700">{{ t('Threat') }}</span>
            </label>
            <textarea
              v-model="item.amenaza"
              :placeholder="t('Describe the threat...')"
              rows="3"
              class="w-full resize-none rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 focus:border-amber-400 focus:ring-1 focus:ring-amber-400"
              @blur="sync"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Add row button -->
    <button
      type="button"
      @click="addItem"
      class="flex w-full items-center justify-center gap-2 rounded-lg border-2 border-dashed border-gray-300 py-3 text-sm font-medium text-gray-500 transition hover:border-teal-400 hover:text-teal-600"
    >
      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
      </svg>
      {{ t('Add Row') }}
    </button>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { useTranslations } from '@/composables/useTranslations';

interface FodaItem {
    id: string;
    factor: string;
    fortaleza: string;
    debilidad: string;
    oportunidad: string;
    amenaza: string;
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

const makeItem = (): FodaItem => ({
    id: Math.random().toString(36).slice(2),
    factor: '',
    fortaleza: '',
    debilidad: '',
    oportunidad: '',
    amenaza: '',
});

const parse = (value: string): FodaItem[] => {
    if (!value) { return []; }
    try {
        const parsed = JSON.parse(value);
        if (!Array.isArray(parsed)) { return []; }
        return parsed.map((item: Partial<FodaItem>) => ({
            id: item.id ?? Math.random().toString(36).slice(2),
            factor: item.factor ?? '',
            fortaleza: item.fortaleza ?? '',
            debilidad: item.debilidad ?? '',
            oportunidad: item.oportunidad ?? '',
            amenaza: item.amenaza ?? '',
        }));
    } catch {
        return [];
    }
};

const items = ref<FodaItem[]>(parse(props.modelValue));

watch(
    () => props.modelValue,
    (value) => {
        const parsed = parse(value);
        if (JSON.stringify(parsed) !== JSON.stringify(items.value)) {
            items.value = parsed;
        }
    },
);

const sync = (): void => {
    emit('update:modelValue', JSON.stringify(items.value));
};

const addItem = (): void => {
    items.value.push(makeItem());
    sync();
};

const removeItem = (index: number): void => {
    items.value.splice(index, 1);
    sync();
};

const moveUp = (index: number): void => {
    if (index === 0) { return; }
    [items.value[index - 1], items.value[index]] = [items.value[index], items.value[index - 1]];
    sync();
};

const moveDown = (index: number): void => {
    if (index === items.value.length - 1) { return; }
    [items.value[index], items.value[index + 1]] = [items.value[index + 1], items.value[index]];
    sync();
};
</script>
