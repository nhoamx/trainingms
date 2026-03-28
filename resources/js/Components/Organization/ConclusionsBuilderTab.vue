<template>
  <div>
    <!-- Toolbar (admin only) -->
    <div
      v-if="canManage"
      class="mb-6 flex flex-col gap-4 rounded-xl border border-gray-200 bg-gray-50 p-4 sm:flex-row sm:items-center sm:justify-between"
    >
      <div class="flex items-center gap-3">
        <span class="text-sm font-semibold text-gray-700">{{ t('Conclusions') }}</span>
        <span :class="statusBadgeClass">{{ statusBadgeLabel }}</span>
      </div>
      <div class="flex gap-2">
        <button
          type="button"
          :disabled="sectionForm.processing"
          @click="save('draft')"
          class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 disabled:opacity-50"
        >
          {{ t('Save Draft') }}
        </button>
        <button
          type="button"
          :disabled="sectionForm.processing"
          @click="save('published')"
          class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700 disabled:opacity-50"
        >
          {{ t('Publish') }}
        </button>
      </div>
    </div>

    <!-- Grid: editor (left) + preview (right) -->
    <div :class="canManage ? 'grid grid-cols-1 items-start gap-8 xl:grid-cols-2' : ''">

      <!-- ─── EDITOR (admin only) ─── -->
      <div v-if="canManage" class="space-y-6">

        <!-- Metadata block -->
        <div class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
          <h3 class="text-base font-semibold text-gray-800">{{ t('Document Header') }}</h3>

          <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-600">
              {{ t('Work Center') }}
            </label>
            <input
              v-model="config.work_center"
              type="text"
              :placeholder="t('e.g. Planta 1')"
              @blur="sync"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500"
            />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-600">
                {{ t('Evaluation Period') }}
              </label>
              <input
                v-model="config.evaluation_period"
                type="text"
                :placeholder="t('e.g. Noviembre 2025')"
                @blur="sync"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500"
              />
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-600">
                {{ t('Issue Date') }}
              </label>
              <input
                v-model="config.issue_date"
                type="text"
                placeholder="DD/MM/YYYY"
                @blur="sync"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500"
              />
            </div>
          </div>

          <div>
            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-600">
              {{ t('General Objective') }}
            </label>
            <textarea
              v-model="config.objective"
              rows="3"
              @blur="sync"
              :placeholder="t('Describe the general study objective...')"
              class="w-full resize-none rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500"
            />
          </div>
        </div>

        <!-- Sections builder -->
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
          <div class="mb-4 flex items-center justify-between">
            <h3 class="text-base font-semibold text-gray-800">{{ t('Analysis Sections') }}</h3>
            <button
              type="button"
              @click="addSection"
              class="rounded-lg bg-teal-50 px-3 py-1.5 text-xs font-semibold text-teal-700 transition-colors hover:bg-teal-100"
            >
              + {{ t('Add Section') }}
            </button>
          </div>

          <p
            v-if="config.sections.length === 0"
            class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 text-center text-sm italic text-gray-400"
          >
            {{ t('No sections yet. Click "+ Add Section" to begin.') }}
          </p>

          <div class="space-y-4">
            <div
              v-for="(section, index) in config.sections"
              :key="section.id"
              class="rounded-lg border border-gray-200"
            >
              <!-- Card header -->
              <div class="flex items-center justify-between gap-2 rounded-t-lg border-b border-gray-100 bg-gray-50 px-4 py-2.5">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                  {{ t('Section') }} {{ index + 1 }}
                  <span v-if="section.title" class="ml-1 font-normal normal-case text-gray-700">— {{ section.title }}</span>
                </span>
                <div class="flex items-center gap-1">
                  <button
                    type="button"
                    :disabled="index === 0"
                    @click="moveSectionUp(index)"
                    class="rounded p-1 text-gray-400 hover:bg-gray-200 hover:text-gray-700 disabled:opacity-30"
                  >
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                    </svg>
                  </button>
                  <button
                    type="button"
                    :disabled="index === config.sections.length - 1"
                    @click="moveSectionDown(index)"
                    class="rounded p-1 text-gray-400 hover:bg-gray-200 hover:text-gray-700 disabled:opacity-30"
                  >
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                  </button>
                  <button
                    type="button"
                    @click="removeSection(index)"
                    class="ml-1 rounded p-1 text-red-400 hover:bg-red-50 hover:text-red-600"
                  >
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
              </div>

              <!-- Card body -->
              <div class="space-y-3 p-4">
                <div class="grid grid-cols-2 gap-3">
                  <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">{{ t('Title') }}</label>
                    <input
                      v-model="section.title"
                      type="text"
                      @blur="sync"
                      class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500"
                    />
                  </div>
                  <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">{{ t('Badge') }}</label>
                    <select
                      v-model="section.badge"
                      @change="sync"
                      class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500"
                    >
                      <option value="critical">{{ t('Critical') }}</option>
                      <option value="important">{{ t('Important') }}</option>
                      <option value="positive">{{ t('Positive') }}</option>
                    </select>
                  </div>
                </div>

                <div>
                  <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">{{ t('Content') }}</label>
                  <textarea
                    v-model="section.content"
                    rows="3"
                    @blur="sync"
                    class="w-full resize-none rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500"
                  />
                </div>

                <!-- Actions list -->
                <div>
                  <div class="mb-1 flex items-center justify-between">
                    <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ t('Priority Actions') }}</label>
                    <button
                      type="button"
                      @click="addAction(index)"
                      class="text-xs text-teal-600 hover:text-teal-700"
                    >
                      + {{ t('Add') }}
                    </button>
                  </div>
                  <div
                    v-for="(_, ai) in section.actions"
                    :key="ai"
                    class="mb-1.5 flex gap-2"
                  >
                    <input
                      v-model="section.actions[ai]"
                      type="text"
                      @blur="sync"
                      class="flex-1 rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500"
                    />
                    <button
                      type="button"
                      @click="removeAction(index, ai)"
                      class="px-1 text-red-400 hover:text-red-600"
                    >
                      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    </button>
                  </div>
                  <p v-if="section.actions.length === 0" class="text-xs italic text-gray-400">
                    {{ t('No actions yet.') }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- File slots block -->
        <div class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
          <h3 class="text-base font-semibold text-gray-800">{{ t('Downloadable Documents') }}</h3>

          <div
            v-for="slotCfg in config.file_slot_configs"
            :key="slotCfg.slot"
            class="space-y-3 rounded-lg border border-gray-200 p-4"
          >
            <div class="flex items-center justify-between">
              <span class="text-sm font-semibold text-gray-700">{{ t('Slot') }} {{ slotCfg.slot }}</span>
              <div v-if="files[String(slotCfg.slot)]" class="flex items-center gap-2 text-xs text-gray-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span class="font-medium">{{ files[String(slotCfg.slot)].original_filename }}</span>
                <span class="text-gray-400">({{ files[String(slotCfg.slot)].file_size_human }})</span>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">{{ t('Button Label') }}</label>
                <input
                  v-model="slotCfg.title"
                  type="text"
                  :placeholder="t('e.g. Download Report')"
                  @blur="sync"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500"
                />
              </div>
              <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">{{ t('Button Color') }}</label>
                <select
                  v-model="slotCfg.color"
                  @change="sync"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500"
                >
                  <option value="teal">Teal</option>
                  <option value="blue">Blue</option>
                  <option value="red">Red</option>
                  <option value="amber">Amber</option>
                  <option value="slate">Slate</option>
                </select>
              </div>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
              <!-- Upload button -->
              <label :class="['relative cursor-pointer rounded-lg px-4 py-2 text-sm font-medium text-white transition-colors', colorButtonClass(slotCfg.color)]">
                <span>{{ files[String(slotCfg.slot)] ? t('Replace File') : t('Upload File') }}</span>
                <input
                  type="file"
                  :key="`file-input-${slotCfg.slot}-${fileInputKeys[slotCfg.slot - 1]}`"
                  class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                  accept=".pdf,.doc,.docx"
                  @change="(e) => handleFileUpload(slotCfg, e)"
                />
              </label>

              <template v-if="files[String(slotCfg.slot)]">
                <button
                  type="button"
                  @click="togglePublish(String(slotCfg.slot))"
                  :class="[
                    'rounded-lg border px-4 py-2 text-sm font-medium transition-colors',
                    files[String(slotCfg.slot)].is_published
                      ? 'border-amber-300 bg-amber-50 text-amber-700 hover:bg-amber-100'
                      : 'border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-100',
                  ]"
                >
                  {{ files[String(slotCfg.slot)].is_published ? t('Unpublish') : t('Publish') }}
                </button>
                <button
                  type="button"
                  @click="deleteFile(String(slotCfg.slot))"
                  class="rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-600 transition-colors hover:bg-red-50"
                >
                  {{ t('Delete') }}
                </button>
              </template>
            </div>

            <p v-if="uploadingSlot === slotCfg.slot" class="animate-pulse text-xs text-teal-600">
              {{ t('Uploading...') }}
            </p>
          </div>
        </div>
      </div>

      <!-- ─── PREVIEW ─── -->
      <div :class="canManage ? 'sticky top-4' : 'mx-auto w-full max-w-4xl'">
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

          <!-- Document header -->
          <div class="bg-gradient-to-r from-blue-700 to-blue-900 px-8 py-6 text-white">
            <h2 class="mb-1 text-2xl font-bold">{{ t('Conclusions') }}</h2>
            <p class="font-medium text-blue-200">{{ organizationName }}</p>
            <div class="mt-3 flex flex-wrap items-center gap-x-6 gap-y-1 text-sm text-blue-100">
              <span v-if="config.work_center">
                <span class="font-semibold text-white">{{ t('Work Center') }}:</span>
                {{ config.work_center }}
              </span>
              <span v-if="config.evaluation_period">
                <span class="font-semibold text-white">{{ t('Evaluation Period') }}:</span>
                {{ config.evaluation_period }}
              </span>
              <span v-if="config.issue_date">
                <span class="font-semibold text-white">{{ t('Issue Date') }}:</span>
                {{ config.issue_date }}
              </span>
            </div>
          </div>

          <div class="space-y-6 px-8 py-6">

            <!-- Objective -->
            <div v-if="config.objective" class="rounded-xl border border-blue-200 bg-blue-50 p-5">
              <h3 class="mb-2 text-base font-semibold text-blue-900">{{ t('General Objective') }}</h3>
              <p class="text-sm leading-relaxed text-blue-800">{{ config.objective }}</p>
            </div>
            <div
              v-else-if="canManage"
              class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-4 text-center text-sm italic text-gray-400"
            >
              {{ t('General objective will appear here.') }}
            </div>

            <!-- Sections -->
            <div v-if="config.sections.length > 0">
              <h3 class="mb-4 text-lg font-bold text-gray-900">{{ t('Analysis') }}</h3>
              <div class="space-y-4">
                <div
                  v-for="(section, i) in config.sections"
                  :key="section.id"
                  :class="[
                    'border-l-4 rounded-r-xl p-5',
                    badgeStyles[section.badge]?.bg ?? 'bg-gray-50',
                    badgeStyles[section.badge]?.border ?? 'border-l-gray-400',
                  ]"
                >
                  <div class="mb-2 flex items-center gap-2">
                    <span :class="['rounded px-2 py-0.5 text-xs font-bold text-white', badgeStyles[section.badge]?.badge ?? 'bg-gray-500']">
                      {{ badgeStyles[section.badge]?.label ?? section.badge }}
                    </span>
                    <h4 class="font-bold text-gray-900">{{ i + 1 }}) {{ section.title || t('Untitled Section') }}</h4>
                  </div>
                  <p v-if="section.content" class="mb-3 text-sm leading-relaxed text-gray-700">
                    {{ section.content }}
                  </p>
                  <ul v-if="section.actions.length > 0" class="space-y-1">
                    <li
                      v-for="(action, ai) in section.actions"
                      :key="ai"
                      class="flex items-start gap-2 text-sm text-gray-700"
                    >
                      <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                      </svg>
                      {{ action }}
                    </li>
                  </ul>
                </div>
              </div>
            </div>

            <!-- File download cards -->
            <div v-if="hasAnyVisibleSlot">
              <h3 class="mb-4 text-lg font-bold text-gray-900">{{ t('Intervention Programs') }}</h3>
              <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div
                  v-for="slotCfg in config.file_slot_configs"
                  v-show="isSlotVisible(slotCfg.slot)"
                  :key="slotCfg.slot"
                  :class="['flex flex-col gap-3 rounded-xl border p-5', colorCardClass(slotCfg.color)]"
                >
                  <div class="flex items-center gap-2">
                    <svg class="h-6 w-6 flex-shrink-0" :class="colorIconClass(slotCfg.color)" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span class="text-sm font-semibold text-gray-900">
                      {{ slotCfg.title || `${t('Document')} ${slotCfg.slot}` }}
                    </span>
                  </div>
                  <div v-if="files[String(slotCfg.slot)]" class="text-xs text-gray-500">
                    {{ files[String(slotCfg.slot)].original_filename }}
                    · {{ files[String(slotCfg.slot)].file_size_human }}
                    <span v-if="!files[String(slotCfg.slot)].is_published" class="ml-1 text-amber-600">
                      ({{ t('Draft') }})
                    </span>
                  </div>
                  <a
                    v-if="files[String(slotCfg.slot)]"
                    :href="downloadUrl(files[String(slotCfg.slot)].id)"
                    target="_blank"
                    rel="noopener noreferrer"
                    :class="['mt-auto flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-bold text-white transition-colors', colorButtonClass(slotCfg.color)]"
                  >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    {{ slotCfg.title || t('Download') }}
                  </a>
                  <div v-else class="rounded-lg border border-dashed border-gray-300 p-2 text-center text-xs italic text-gray-400">
                    {{ t('No file uploaded yet.') }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Empty state (non-admin, nothing published) -->
            <div
              v-if="!canManage && config.sections.length === 0 && !hasAnyPublishedFile"
              class="py-16 text-center"
            >
              <div class="text-5xl mb-4">🚧</div>
              <p class="text-xl font-semibold text-gray-900 mb-2">{{ t('Document in Preparation') }}</p>
              <p class="text-gray-600">{{ t('We are working on the conclusions for your organization. It will be available soon.') }}</p>
            </div>

          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { useTranslations } from '@/composables/useTranslations';

const route = (...args: unknown[]): string =>
    (window as unknown as Window & { route: (...params: unknown[]) => string }).route(...args);

const { t } = useTranslations();

interface ConclusionsSection {
    id: string;
    title: string;
    badge: 'critical' | 'important' | 'positive';
    content: string;
    actions: string[];
}

interface FileSlotConfig {
    slot: number;
    title: string;
    color: 'teal' | 'blue' | 'red' | 'amber' | 'slate';
}

interface ConclusionsConfig {
    work_center: string;
    evaluation_period: string;
    issue_date: string;
    objective: string;
    sections: ConclusionsSection[];
    file_slot_configs: FileSlotConfig[];
}

interface ConclusionsFileItem {
    id: number;
    slot: number;
    title: string;
    color: string;
    original_filename: string;
    file_size_human: string;
    is_published: boolean;
}

interface Props {
    modelValue: string;
    organizationName: string;
    workCenterId: string;
    files: Record<string, ConclusionsFileItem>;
    sectionStatus?: 'draft' | 'published';
    canManage?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    sectionStatus: 'draft',
    canManage: false,
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const makeConfig = (): ConclusionsConfig => ({
    work_center: '',
    evaluation_period: '',
    issue_date: '',
    objective: '',
    sections: [],
    file_slot_configs: [
        { slot: 1, title: '', color: 'teal' },
        { slot: 2, title: '', color: 'blue' },
        { slot: 3, title: '', color: 'slate' },
    ],
});

const parseConfig = (value: string | null | undefined): ConclusionsConfig => {
    if (!value) {
        return makeConfig();
    }
    try {
        const parsed = JSON.parse(value) as Partial<ConclusionsConfig>;
        const defaults = makeConfig();
        return {
            work_center: parsed.work_center ?? '',
            evaluation_period: parsed.evaluation_period ?? '',
            issue_date: parsed.issue_date ?? '',
            objective: parsed.objective ?? '',
            sections: (parsed.sections ?? []).map((s) => ({
                id: s.id ?? Math.random().toString(36).slice(2),
                title: s.title ?? '',
                badge: s.badge ?? 'critical',
                content: s.content ?? '',
                actions: s.actions ?? [],
            })),
            file_slot_configs: defaults.file_slot_configs.map((def) => {
                const saved = (parsed.file_slot_configs ?? []).find((fs) => fs.slot === def.slot);
                return {
                    slot: def.slot,
                    title: saved?.title ?? '',
                    color: saved?.color ?? def.color,
                };
            }),
        };
    } catch {
        return makeConfig();
    }
};

const config = reactive<ConclusionsConfig>(parseConfig(props.modelValue));

watch(
    () => props.modelValue,
    (value) => {
        const parsed = parseConfig(value);
        if (JSON.stringify(parsed) !== JSON.stringify(config)) {
            Object.assign(config, parsed);
        }
    },
);

const sync = (): void => {
    emit('update:modelValue', JSON.stringify(config));
};

// ── Section helpers ──

const makeSection = (): ConclusionsSection => ({
    id: Math.random().toString(36).slice(2),
    title: '',
    badge: 'critical',
    content: '',
    actions: [],
});

const addSection = (): void => {
    config.sections.push(makeSection());
    sync();
};

const removeSection = (index: number): void => {
    config.sections.splice(index, 1);
    sync();
};

const moveSectionUp = (index: number): void => {
    if (index === 0) {
        return;
    }
    [config.sections[index - 1], config.sections[index]] = [config.sections[index], config.sections[index - 1]];
    sync();
};

const moveSectionDown = (index: number): void => {
    if (index === config.sections.length - 1) {
        return;
    }
    [config.sections[index], config.sections[index + 1]] = [config.sections[index + 1], config.sections[index]];
    sync();
};

const addAction = (sectionIndex: number): void => {
    config.sections[sectionIndex].actions.push('');
    sync();
};

const removeAction = (sectionIndex: number, actionIndex: number): void => {
    config.sections[sectionIndex].actions.splice(actionIndex, 1);
    sync();
};

// ── Save section ──

const sectionForm = useForm({
    section_key: 'conclusions_config',
    content: '',
    status: 'draft' as 'draft' | 'published',
});

const save = (status: 'draft' | 'published'): void => {
    sectionForm.section_key = 'conclusions_config';
    sectionForm.content = JSON.stringify(config);
    sectionForm.status = status;
    sectionForm.post(route('work-centers.clima.sections.upsert', props.workCenterId), {
        preserveScroll: true,
    });
};

// ── File uploads ──

const uploadingSlot = ref<number | null>(null);
const fileInputKeys = ref<number[]>([0, 0, 0]);

const handleFileUpload = (slotCfg: FileSlotConfig, event: Event): void => {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) {
        return;
    }

    uploadingSlot.value = slotCfg.slot;

    const form = useForm({
        slot: slotCfg.slot,
        title: slotCfg.title || `Documento ${slotCfg.slot}`,
        color: slotCfg.color,
        conclusions_file: file as File | null,
        is_published: false,
    });

    form.post(route('work-centers.clima.conclusions-files.store', props.workCenterId), {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => {
            uploadingSlot.value = null;
            fileInputKeys.value[slotCfg.slot - 1]++;
        },
    });
};

const togglePublish = (slot: string): void => {
    const file = props.files[slot];
    if (!file) {
        return;
    }
    router.patch(
        route('work-centers.clima.conclusions-files.toggle-publish', { workCenter: props.workCenterId, file: file.id }),
        {},
        { preserveScroll: true },
    );
};

const deleteFile = (slot: string): void => {
    const file = props.files[slot];
    if (!file) {
        return;
    }
    router.delete(
        route('work-centers.clima.conclusions-files.destroy', { workCenter: props.workCenterId, file: file.id }),
        { preserveScroll: true },
    );
};

const downloadUrl = (fileId: number): string =>
    route('work-centers.clima.conclusions-files.download', { workCenter: props.workCenterId, file: fileId });

// ── Status badge ──

const statusBadgeClass = computed(() =>
    props.sectionStatus === 'published'
        ? 'rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700'
        : 'rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700',
);

const statusBadgeLabel = computed(() =>
    props.sectionStatus === 'published' ? t('Published') : t('Draft'),
);

// ── Color helpers ──

const colorButtonClass = (color: string): string => {
    const map: Record<string, string> = {
        teal: 'bg-teal-600 hover:bg-teal-700',
        blue: 'bg-blue-600 hover:bg-blue-700',
        red: 'bg-red-600 hover:bg-red-700',
        amber: 'bg-amber-600 hover:bg-amber-700',
        slate: 'bg-slate-600 hover:bg-slate-700',
    };
    return map[color] ?? 'bg-blue-600 hover:bg-blue-700';
};

const colorCardClass = (color: string): string => {
    const map: Record<string, string> = {
        teal: 'border-teal-200 bg-teal-50',
        blue: 'border-blue-200 bg-blue-50',
        red: 'border-red-200 bg-red-50',
        amber: 'border-amber-200 bg-amber-50',
        slate: 'border-slate-200 bg-slate-50',
    };
    return map[color] ?? 'border-gray-200 bg-gray-50';
};

const colorIconClass = (color: string): string => {
    const map: Record<string, string> = {
        teal: 'text-teal-600',
        blue: 'text-blue-600',
        red: 'text-red-600',
        amber: 'text-amber-600',
        slate: 'text-slate-600',
    };
    return map[color] ?? 'text-blue-600';
};

// ── Badge styles ──

const badgeStyles: Record<string, { bg: string; border: string; badge: string; label: string }> = {
    critical: { bg: 'bg-red-50', border: 'border-l-red-500', badge: 'bg-red-600', label: t('CRITICAL') },
    important: { bg: 'bg-amber-50', border: 'border-l-amber-500', badge: 'bg-amber-600', label: t('IMPORTANT') },
    positive: { bg: 'bg-green-50', border: 'border-l-green-500', badge: 'bg-green-600', label: t('POSITIVE') },
};

// ── Visibility helpers ──

const isSlotVisible = (slot: number): boolean => {
    const file = props.files[String(slot)];
    if (!file) {
        return canManage;
    }
    return canManage || file.is_published;
};

const canManage = props.canManage;

const hasAnyPublishedFile = computed(() =>
    Object.values(props.files).some((f) => f.is_published),
);

const hasAnyVisibleSlot = computed(() =>
    config.file_slot_configs.some((s) => isSlotVisible(s.slot)),
);
</script>
