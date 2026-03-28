<template>
  <div>
    <!-- Readonly / preview-only mode -->
    <div v-if="readonly">
      <div v-if="hasConfig" :class="cardClasses.wrapper" class="max-w-lg rounded-xl border p-8 shadow-sm">
        <div class="mb-6 flex items-start justify-between">
          <div>
            <h3 class="mb-1 text-2xl font-bold text-gray-900">{{ displayTitle }}</h3>
            <p v-if="displaySubtitle" class="text-sm text-gray-600">{{ displaySubtitle }}</p>
          </div>
          <svg class="h-9 w-9 flex-shrink-0" :class="cardClasses.icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
          </svg>
        </div>
        <ul v-if="displayBullets.length" class="mb-7 space-y-3">
          <li v-for="(bullet, i) in displayBullets" :key="i" class="flex items-center gap-3 text-gray-700">
            <svg class="h-5 w-5 flex-shrink-0" :class="cardClasses.icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ bullet }}</span>
          </li>
        </ul>
        <a
          v-if="activeReportForLocale"
          :href="downloadRoute(activeReportForLocale.id)"
          :class="cardClasses.button"
          class="flex w-full items-center justify-center gap-2 rounded-lg py-3 px-6 font-bold text-white transition-colors"
        >
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
          </svg>
          {{ locale === 'es' ? 'Descargar Informe' : 'Download Report' }}
        </a>
        <div v-else class="rounded-lg border border-dashed border-gray-300 p-4 text-center text-sm text-gray-400 italic">
          {{ t('No file uploaded yet for this language.') }}
        </div>
      </div>
      <div v-else class="rounded-lg border border-dashed border-gray-300 bg-gray-50 py-12 text-center text-sm text-gray-400 italic">
        {{ t('No report configured yet.') }}
      </div>
    </div>

    <!-- Builder mode -->
    <div v-else class="grid grid-cols-1 gap-6 xl:grid-cols-2">
      <!-- Left: Builder form -->
      <div class="space-y-5">
        <!-- Language tabs -->
        <div class="flex rounded-lg border border-gray-200 p-1 bg-gray-50">
          <button
            v-for="lang in (['es', 'en'] as const)"
            :key="lang"
            type="button"
            @click="activeLang = lang"
            :class="activeLang === lang
              ? 'bg-white shadow text-gray-900 font-semibold'
              : 'text-gray-500 hover:text-gray-700'"
            class="flex-1 rounded-md py-1.5 text-sm transition-all"
          >
            {{ lang === 'es' ? '🇲🇽 Español' : '🇺🇸 English' }}
          </button>
        </div>

        <!-- Title -->
        <div>
          <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-600">
            {{ t('Card title') }} ({{ activeLang.toUpperCase() }})
          </label>
          <input
            v-model="config[`title_${activeLang}`]"
            type="text"
            :placeholder="activeLang === 'es' ? 'Informe' : 'Report'"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500"
          />
        </div>

        <!-- Subtitle -->
        <div>
          <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-600">
            {{ t('Subtitle') }} ({{ activeLang.toUpperCase() }}) — {{ t('optional') }}
          </label>
          <input
            v-model="config[`subtitle_${activeLang}`]"
            type="text"
            :placeholder="activeLang === 'es' ? 'Análisis de Clima Laboral' : 'Work Climate Analysis'"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500"
          />
        </div>

        <!-- Bullet points -->
        <div>
          <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-600">
            {{ t('Key highlights') }} ({{ activeLang.toUpperCase() }})
          </label>
          <ul class="space-y-2">
            <li v-for="(bullet, i) in config[`bullets_${activeLang}`]" :key="i" class="flex items-center gap-2">
              <span class="text-gray-400">✓</span>
              <input
                v-model="config[`bullets_${activeLang}`][i]"
                type="text"
                :placeholder="`${t('Highlight')} ${i + 1}`"
                class="min-w-0 flex-1 rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500"
              />
              <button type="button" @click="removeBullet(activeLang, i)" class="rounded p-1 text-gray-300 hover:text-red-400">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </li>
          </ul>
          <button type="button" @click="addBullet(activeLang)" class="mt-2 flex items-center gap-1.5 rounded-md px-2 py-1 text-xs font-medium text-teal-600 hover:bg-teal-50">
            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            {{ t('Add highlight') }}
          </button>
        </div>

        <!-- Color accent -->
        <div>
          <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-600">{{ t('Accent color') }}</label>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="opt in colorOptions"
              :key="opt.value"
              type="button"
              @click="config.color = opt.value"
              :class="[opt.ring, config.color === opt.value ? 'ring-2 ring-offset-2' : 'opacity-70 hover:opacity-100']"
              class="flex items-center gap-2 rounded-lg border px-3 py-1.5 text-xs font-medium transition-all"
              :style="{ borderColor: opt.border, backgroundColor: opt.bg, color: opt.text }"
            >
              <span class="inline-block h-3 w-3 rounded-full" :style="{ backgroundColor: opt.swatch }"></span>
              {{ opt.label }}
            </button>
          </div>
        </div>

        <!-- Divider -->
        <div class="border-t border-gray-200 pt-4">
          <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-600">{{ t('Upload PDF file') }}</p>

          <!-- ES file -->
          <div class="mb-3 rounded-lg border border-gray-200 p-3">
            <div class="mb-2 flex items-center justify-between">
              <span class="text-sm font-medium text-gray-700">🇲🇽 PDF Español</span>
              <span v-if="activeReportEs" class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">{{ t('Uploaded') }}</span>
              <span v-else class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500">{{ t('No file') }}</span>
            </div>
            <div v-if="activeReportEs" class="mb-2 flex items-center justify-between rounded-md bg-gray-50 px-3 py-2">
              <span class="truncate text-xs text-gray-600">{{ activeReportEs.original_filename }} · {{ activeReportEs.file_size_human }}</span>
              <a :href="downloadRoute(activeReportEs.id)" class="ml-2 flex-shrink-0 text-xs font-medium text-teal-600 hover:underline">{{ t('Download') }}</a>
            </div>
            <FileDropZone language="es" @file-selected="uploadReport('es', $event)" :uploading="uploadingEs" />
          </div>

          <!-- EN file -->
          <div class="rounded-lg border border-gray-200 p-3">
            <div class="mb-2 flex items-center justify-between">
              <span class="text-sm font-medium text-gray-700">🇺🇸 PDF English</span>
              <span v-if="activeReportEn" class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">{{ t('Uploaded') }}</span>
              <span v-else class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500">{{ t('No file') }}</span>
            </div>
            <div v-if="activeReportEn" class="mb-2 flex items-center justify-between rounded-md bg-gray-50 px-3 py-2">
              <span class="truncate text-xs text-gray-600">{{ activeReportEn.original_filename }} · {{ activeReportEn.file_size_human }}</span>
              <a :href="downloadRoute(activeReportEn.id)" class="ml-2 flex-shrink-0 text-xs font-medium text-teal-600 hover:underline">{{ t('Download') }}</a>
            </div>
            <FileDropZone language="en" @file-selected="uploadReport('en', $event)" :uploading="uploadingEn" />
          </div>
        </div>

        <!-- Save buttons -->
        <div class="flex gap-2 pt-1">
          <button type="button" @click="emit('save', 'draft')" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">
            {{ t('Save Draft') }}
          </button>
          <button type="button" @click="emit('save', 'published')" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700">
            {{ t('Publish') }}
          </button>
        </div>
      </div>

      <!-- Right: Live preview -->
      <div class="xl:pl-4">
        <div class="sticky top-24">
          <div class="mb-3 flex items-center justify-between">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ t('Live Preview') }}</p>
            <div class="flex rounded-lg border border-gray-200 bg-gray-50 p-0.5">
              <button
                v-for="lang in (['es', 'en'] as const)"
                :key="lang"
                type="button"
                @click="previewLang = lang"
                :class="previewLang === lang ? 'bg-white shadow text-gray-900 font-semibold' : 'text-gray-400 hover:text-gray-600'"
                class="rounded-md px-3 py-1 text-xs transition-all"
              >
                {{ lang.toUpperCase() }}
              </button>
            </div>
          </div>

          <!-- Preview card -->
          <div :class="cardClasses.wrapper" class="rounded-xl border p-8 shadow-sm transition-all duration-300">
            <div class="mb-6 flex items-start justify-between">
              <div>
                <h3 class="mb-1 text-2xl font-bold text-gray-900">
                  {{ previewTitle || (previewLang === 'es' ? 'Informe' : 'Report') }}
                </h3>
                <p v-if="previewSubtitle" class="text-sm text-gray-600">{{ previewSubtitle }}</p>
              </div>
              <svg class="h-9 w-9 flex-shrink-0" :class="cardClasses.icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
              </svg>
            </div>
            <ul v-if="previewBullets.length" class="mb-7 space-y-3">
              <li v-for="(bullet, i) in previewBullets" :key="i" class="flex items-center gap-3 text-gray-700">
                <svg class="h-5 w-5 flex-shrink-0" :class="cardClasses.icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ bullet }}</span>
              </li>
              <li v-if="!previewBullets.length" class="text-sm italic text-gray-400">{{ t('Add highlights to see them here...') }}</li>
            </ul>
            <ul v-else class="mb-7 space-y-3">
              <li class="flex items-center gap-3 text-gray-400 italic text-sm">
                <svg class="h-5 w-5 flex-shrink-0 opacity-40" :class="cardClasses.icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ t('Add highlights to see them here...') }}
              </li>
            </ul>
            <button type="button" :class="cardClasses.button" class="flex w-full cursor-default items-center justify-center gap-2 rounded-lg py-3 px-6 font-bold text-white">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
              {{ previewLang === 'es' ? 'Descargar Informe' : 'Download Report' }}
            </button>
          </div>

          <!-- Uploaded reports list below preview -->
          <div v-if="reports.length" class="mt-5 rounded-lg border border-gray-200 divide-y divide-gray-100">
            <p class="px-4 py-2.5 text-xs font-semibold uppercase tracking-wide text-gray-500 bg-gray-50 rounded-t-lg">{{ t('Uploaded files') }}</p>
            <div v-for="report in reports" :key="report.id" class="flex items-center justify-between gap-3 px-4 py-3">
              <div class="min-w-0">
                <p class="truncate text-sm font-medium text-gray-900">{{ report.title }}</p>
                <p class="text-xs text-gray-500">
                  <span class="rounded-full bg-gray-100 px-1.5 py-0.5 font-semibold uppercase">{{ report.language }}</span>
                  &nbsp;·&nbsp;{{ report.file_size_human }}
                  &nbsp;·&nbsp;<span :class="report.is_published ? 'text-emerald-600' : 'text-amber-600'">{{ report.is_published ? t('Published') : t('Draft') }}</span>
                  <span v-if="report.is_active" class="ml-1 text-teal-600 font-medium">· {{ t('Active') }}</span>
                </p>
              </div>
              <div class="flex flex-shrink-0 gap-1.5">
                <a :href="downloadRoute(report.id)" class="rounded-md border border-gray-300 px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-100">↓</a>
                <button type="button" @click="emit('toggle-publish', report.id)" class="rounded-md border border-gray-300 px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-100">
                  {{ report.is_published ? t('Unpublish') : t('Publish') }}
                </button>
                <button type="button" @click="emit('set-active', report.id)" class="rounded-md border border-gray-300 px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-100" :title="t('Set as current')">★</button>
                <button type="button" @click="emit('delete-report', report.id)" class="rounded-md border border-red-200 px-2.5 py-1 text-xs font-medium text-red-600 hover:bg-red-50">✕</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useTranslations } from '@/composables/useTranslations';
import FileDropZone from '@/Components/WorkCenter/FileDropZone.vue';

interface ReportCardConfig {
    title_es: string;
    title_en: string;
    subtitle_es: string;
    subtitle_en: string;
    bullets_es: string[];
    bullets_en: string[];
    color: 'red' | 'teal' | 'blue' | 'amber';
}

interface ClimaReportItem {
    id: number;
    title: string;
    language: string;
    original_filename: string;
    file_size_human: string;
    is_published: boolean;
    is_active: boolean;
    created_at: string;
}

interface Props {
    modelValue: string;
    readonly?: boolean;
    reports: ClimaReportItem[];
    workCenterId: string;
    locale: string;
}

const props = withDefaults(defineProps<Props>(), {
    readonly: false,
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
    'save': [status: 'draft' | 'published'];
    'toggle-publish': [id: number];
    'set-active': [id: number];
    'delete-report': [id: number];
}>();

const { t } = useTranslations();

const defaultConfig = (): ReportCardConfig => ({
    title_es: '',
    title_en: '',
    subtitle_es: '',
    subtitle_en: '',
    bullets_es: [''],
    bullets_en: [''],
    color: 'red',
});

const parseConfig = (value: string): ReportCardConfig => {
    if (!value) { return defaultConfig(); }
    try {
        const parsed = JSON.parse(value);
        return {
            ...defaultConfig(),
            ...parsed,
            bullets_es: Array.isArray(parsed.bullets_es) ? parsed.bullets_es : [''],
            bullets_en: Array.isArray(parsed.bullets_en) ? parsed.bullets_en : [''],
        };
    } catch {
        return defaultConfig();
    }
};

const config = reactive<ReportCardConfig>(parseConfig(props.modelValue));

watch(
    () => props.modelValue,
    (value) => {
        const parsed = parseConfig(value);
        if (JSON.stringify(parsed) !== JSON.stringify(config)) {
            Object.assign(config, parsed);
        }
    },
);

watch(config, () => {
    emit('update:modelValue', JSON.stringify(config));
}, { deep: true });

const hasConfig = computed(() =>
    !!(config.title_es || config.title_en || config.bullets_es.some(b => b) || config.bullets_en.some(b => b)),
);

const activeLang = ref<'es' | 'en'>('es');
const previewLang = ref<'es' | 'en'>('es');

const uploadingEs = ref(false);
const uploadingEn = ref(false);

const activeReportEs = computed(() =>
    props.reports.find(r => r.language === 'es' && r.is_active)
    ?? props.reports.filter(r => r.language === 'es').sort((a, b) => b.id - a.id)[0]
    ?? null,
);

const activeReportEn = computed(() =>
    props.reports.find(r => r.language === 'en' && r.is_active)
    ?? props.reports.filter(r => r.language === 'en').sort((a, b) => b.id - a.id)[0]
    ?? null,
);

const activeReportForLocale = computed(() =>
    props.locale === 'en' ? activeReportEn.value : activeReportEs.value,
);

const colorOptions = [
    { value: 'red', label: 'Rojo', swatch: '#dc2626', bg: '#fef2f2', border: '#fecaca', text: '#991b1b', ring: 'ring-red-500' },
    { value: 'teal', label: 'Verde', swatch: '#0d9488', bg: '#f0fdfa', border: '#99f6e4', text: '#065f46', ring: 'ring-teal-500' },
    { value: 'blue', label: 'Azul', swatch: '#2563eb', bg: '#eff6ff', border: '#bfdbfe', text: '#1e3a8a', ring: 'ring-blue-500' },
    { value: 'amber', label: 'Naranja', swatch: '#d97706', bg: '#fffbeb', border: '#fde68a', text: '#92400e', ring: 'ring-amber-500' },
] as const;

const colorMap: Record<string, { wrapper: string; icon: string; button: string }> = {
    red: { wrapper: 'bg-gradient-to-br from-red-50 to-red-100 border-red-200', icon: 'text-red-600', button: 'bg-red-600 hover:bg-red-700' },
    teal: { wrapper: 'bg-gradient-to-br from-teal-50 to-teal-100 border-teal-200', icon: 'text-teal-600', button: 'bg-teal-600 hover:bg-teal-700' },
    blue: { wrapper: 'bg-gradient-to-br from-blue-50 to-blue-100 border-blue-200', icon: 'text-blue-600', button: 'bg-blue-600 hover:bg-blue-700' },
    amber: { wrapper: 'bg-gradient-to-br from-amber-50 to-amber-100 border-amber-200', icon: 'text-amber-600', button: 'bg-amber-600 hover:bg-amber-700' },
};

const cardClasses = computed(() => colorMap[config.color] ?? colorMap.red);

const displayTitle = computed(() => (props.locale === 'en' ? config.title_en : config.title_es) || '');
const displaySubtitle = computed(() => (props.locale === 'en' ? config.subtitle_en : config.subtitle_es) || '');
const displayBullets = computed(() => (props.locale === 'en' ? config.bullets_en : config.bullets_es).filter(b => b.trim()));

const previewTitle = computed(() => (previewLang.value === 'en' ? config.title_en : config.title_es) || '');
const previewSubtitle = computed(() => (previewLang.value === 'en' ? config.subtitle_en : config.subtitle_es) || '');
const previewBullets = computed(() => (previewLang.value === 'en' ? config.bullets_en : config.bullets_es).filter(b => b.trim()));

const addBullet = (lang: 'es' | 'en'): void => {
    config[`bullets_${lang}`].push('');
};

const removeBullet = (lang: 'es' | 'en', index: number): void => {
    config[`bullets_${lang}`].splice(index, 1);
    if (config[`bullets_${lang}`].length === 0) {
        config[`bullets_${lang}`].push('');
    }
};

const route = (...args: unknown[]): string =>
    (window as unknown as Window & { route: (...params: unknown[]) => string }).route(...args);

const downloadRoute = (reportId: number): string =>
    route('work-centers.clima.reports.download', [props.workCenterId, reportId]);

const uploadReport = (lang: 'es' | 'en', file: File): void => {
    const isEs = lang === 'es';
    if (isEs) { uploadingEs.value = true; } else { uploadingEn.value = true; }

    const form = useForm({
        title: (isEs ? config.title_es : config.title_en) || (isEs ? 'Informe' : 'Report'),
        language: lang,
        report_file: file as File | null,
        is_published: true,
        is_active: true,
    });

    form.post(route('work-centers.clima.reports.store', props.workCenterId), {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => {
            if (isEs) { uploadingEs.value = false; } else { uploadingEn.value = false; }
        },
    });
};
</script>
