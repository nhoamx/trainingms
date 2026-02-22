<template>
  <div class="space-y-8">
    <!-- Encabezado -->
    <div class="border-b border-slate-200 pb-6">
      <div class="flex items-center gap-3 mb-2">
        <div class="p-2 bg-amber-100 rounded-lg">
          <LightBulbIcon class="w-6 h-6 text-amber-600" />
        </div>
        <h2 class="text-3xl font-bold text-slate-900">Sensibilización y Capacitación</h2>
      </div>
      <p class="text-slate-600 mt-2 ml-11">Programas de conciencia sobre riesgos psicosociales</p>
    </div>

    <!-- Presentación -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-8 border border-blue-200 hover:shadow-lg transition-shadow">
      <div class="flex items-center gap-3 mb-6">
        <div class="p-2 bg-blue-100 rounded-lg">
          <PresentationChartBarIcon class="w-6 h-6 text-blue-600" />
        </div>
        <h3 class="text-2xl font-bold text-blue-900">Presentación</h3>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl p-6 border border-slate-200 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between mb-4">
            <BriefcaseIcon class="w-8 h-8 text-purple-600" />
            <div class="p-2 bg-purple-50 rounded-lg">
              <CheckIcon class="w-4 h-4 text-purple-600" />
            </div>
          </div>
          <h4 class="font-bold text-slate-900 mb-2">Gerencia</h4>
          <p class="text-sm text-slate-600">Presentación ejecutiva para nivel directivo</p>
        </div>
        <div class="bg-white rounded-xl p-6 border border-slate-200 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between mb-4">
            <UserGroupIcon class="w-8 h-8 text-blue-600" />
            <div class="p-2 bg-blue-50 rounded-lg">
              <CheckIcon class="w-4 h-4 text-blue-600" />
            </div>
          </div>
          <h4 class="font-bold text-slate-900 mb-2">Mandos Medios</h4>
          <p class="text-sm text-slate-600">Capacitación para supervisores y coordinadores</p>
        </div>
        <div class="bg-white rounded-xl p-6 border border-slate-200 hover:shadow-md transition-shadow">
          <div class="flex items-center justify-between mb-4">
            <UsersIcon class="w-8 h-8 text-emerald-600" />
            <div class="p-2 bg-emerald-50 rounded-lg">
              <CheckIcon class="w-4 h-4 text-emerald-600" />
            </div>
          </div>
          <h4 class="font-bold text-slate-900 mb-2">Trabajadores</h4>
          <p class="text-sm text-slate-600">Material informativo para personal operativo</p>
        </div>
      </div>
    </div>

    <!-- Videos -->
    <div class="bg-gradient-to-r from-amber-50 to-orange-50 rounded-xl p-8 border border-amber-200 hover:shadow-lg transition-shadow">
      <div class="flex items-center gap-3 mb-6">
        <div class="p-2 bg-amber-100 rounded-lg">
          <VideoCameraIcon class="w-6 h-6 text-amber-600" />
        </div>
        <h3 class="text-2xl font-bold text-amber-900">Videos</h3>
      </div>
      <div class="bg-white rounded-lg p-6 space-y-6">
        <form
          v-if="canManageVideos && workCenterId"
          @submit.prevent="submitVideo"
          class="rounded-lg border border-amber-200 bg-amber-50 p-4 space-y-4"
        >
          <h4 class="text-sm font-semibold text-amber-900">Cargar nuevo video</h4>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="video_title" class="block text-sm font-medium text-slate-700">Título</label>
              <input
                id="video_title"
                v-model="videoForm.title"
                type="text"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm"
              />
              <p v-if="videoForm.errors.title" class="mt-1 text-xs text-red-500">{{ videoForm.errors.title }}</p>
            </div>

            <div>
              <label for="video_audience" class="block text-sm font-medium text-slate-700">Público</label>
              <select
                id="video_audience"
                v-model="videoForm.audience"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm"
              >
                <option value="general">General</option>
                <option value="gerencia">Gerencia</option>
                <option value="mandos_medios">Mandos medios</option>
                <option value="trabajadores">Trabajadores</option>
              </select>
              <p v-if="videoForm.errors.audience" class="mt-1 text-xs text-red-500">{{ videoForm.errors.audience }}</p>
            </div>
          </div>

          <div>
            <label for="video_description" class="block text-sm font-medium text-slate-700">Descripción</label>
            <textarea
              id="video_description"
              v-model="videoForm.description"
              rows="3"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm"
            />
            <p v-if="videoForm.errors.description" class="mt-1 text-xs text-red-500">{{ videoForm.errors.description }}</p>
          </div>

          <div>
            <label for="video_file" class="block text-sm font-medium text-slate-700">Archivo de video</label>
            <input
              id="video_file"
              type="file"
              accept="video/mp4,video/webm,video/quicktime,video/x-msvideo"
              class="mt-1 block w-full text-sm text-slate-700"
              @change="onVideoSelected"
            />
            <p v-if="videoForm.errors.video" class="mt-1 text-xs text-red-500">{{ videoForm.errors.video }}</p>
          </div>

          <progress
            v-if="videoForm.progress"
            :value="videoForm.progress.percentage"
            max="100"
            class="w-full h-2"
          >
            {{ videoForm.progress.percentage }}%
          </progress>

          <div class="flex justify-end">
            <button
              type="submit"
              :disabled="videoForm.processing"
              class="rounded-md bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-500 disabled:opacity-50"
            >
              {{ videoForm.processing ? 'Cargando...' : 'Cargar video' }}
            </button>
          </div>
        </form>

        <div v-if="videos.length === 0" class="flex items-center justify-center p-8 border-2 border-dashed border-amber-300 rounded-lg">
          <div class="text-center">
            <Cog6ToothIcon class="w-12 h-12 text-amber-400 mx-auto mb-3 animate-spin" />
            <p class="text-amber-700 font-medium">Sin videos cargados</p>
            <p class="text-sm text-amber-600 mt-1">Aún no hay contenido multimedia disponible para este centro</p>
          </div>
        </div>

        <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <article
            v-for="video in videos"
            :key="video.id"
            class="rounded-xl border border-slate-200 p-4 space-y-3"
          >
            <video
              class="w-full rounded-lg border border-slate-200 bg-black"
              controls
              preload="metadata"
              :src="video.video_url"
            />

            <div>
              <h4 class="font-semibold text-slate-900">{{ video.title }}</h4>
              <p v-if="video.description" class="text-sm text-slate-600 mt-1">{{ video.description }}</p>
            </div>

            <div class="flex items-center justify-between text-xs text-slate-500">
              <span>Audiencia: {{ audienceLabels[video.audience] ?? video.audience }}</span>
              <span>{{ video.file_size_human }}</span>
            </div>

            <div v-if="canManageVideos && workCenterId" class="flex justify-end">
              <button
                type="button"
                @click="deleteVideo(video.id)"
                class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-red-500"
              >
                Eliminar
              </button>
            </div>
          </article>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useForm, router } from '@inertiajs/vue3';
import {
  LightBulbIcon,
  PresentationChartBarIcon,
  BriefcaseIcon,
  UserGroupIcon,
  UsersIcon,
  CheckIcon,
  VideoCameraIcon,
  Cog6ToothIcon,
} from '@heroicons/vue/24/outline';

interface SensitizationVideo {
  id: number;
  title: string;
  description: string | null;
  audience: string;
  video_url: string;
  original_filename: string;
  file_size_human: string;
  created_at: string | null;
}

interface Props {
  videos?: SensitizationVideo[];
  canManageVideos?: boolean;
  workCenterId?: string;
}

const props = withDefaults(defineProps<Props>(), {
  videos: () => [],
  canManageVideos: false,
  workCenterId: undefined,
});

const audienceLabels: Record<string, string> = {
  general: 'General',
  gerencia: 'Gerencia',
  mandos_medios: 'Mandos medios',
  trabajadores: 'Trabajadores',
};

const route = (...args: unknown[]): string => (window as unknown as Window & { route: (...params: unknown[]) => string }).route(...args);

const videoForm = useForm({
  title: '',
  description: '',
  audience: 'general',
  sort_order: 0,
  video: null as File | null,
});

const onVideoSelected = (event: Event): void => {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0] ?? null;
  videoForm.video = file;
};

const submitVideo = (): void => {
  if (!props.workCenterId) {
    return;
  }

  videoForm.post(route('work-centers.sensitization-videos.store', props.workCenterId), {
    preserveScroll: true,
    onSuccess: () => {
      videoForm.reset();
      videoForm.clearErrors();
    },
  });
};

const deleteVideo = (videoId: number): void => {
  if (!props.workCenterId) {
    return;
  }

  router.delete(route('work-centers.sensitization-videos.destroy', [props.workCenterId, videoId]), {
    preserveScroll: true,
  });
};
</script>
