<template>
  <div
    :class="[
      'relative flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed px-4 py-5 text-center transition-colors',
      isDragging ? 'border-teal-400 bg-teal-50' : 'border-gray-300 bg-gray-50 hover:border-teal-400 hover:bg-teal-50',
      uploading ? 'pointer-events-none opacity-60' : '',
    ]"
    @dragover.prevent="isDragging = true"
    @dragleave.prevent="isDragging = false"
    @drop.prevent="onDrop"
    @click="trigger"
  >
    <input
      ref="inputRef"
      type="file"
      :accept="accept"
      :multiple="multiple"
      class="hidden"
      @change="onFileChange"
    />

    <template v-if="uploading">
      <svg class="h-5 w-5 animate-spin text-teal-600" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
      </svg>
      <span class="text-xs text-teal-600 font-medium">{{ t('Uploading...') }}</span>
    </template>
    <template v-else>
      <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5V18a2.25 2.25 0 002.25 2.25h13.5A2.25 2.25 0 0021 18v-1.5m-9-12v12m0-12l-3 3m3-3l3 3" />
      </svg>
      <span class="text-xs text-gray-500">{{ resolvedHint }}</span>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { useTranslations } from '@/composables/useTranslations';

interface Props {
    language?: 'es' | 'en';
    type?: 'pdf' | 'image';
    uploading?: boolean;
    multiple?: boolean;
    accept?: string;
    hint?: string;
}

const props = withDefaults(defineProps<Props>(), {
    type: 'pdf',
    uploading: false,
    multiple: false,
    accept: '.pdf',
    hint: '',
});

const emit = defineEmits<{
    'file-selected': [file: File];
    'files-selected': [files: File[]];
}>();

const { t } = useTranslations();
const isDragging = ref(false);
const inputRef = ref<HTMLInputElement | null>(null);

const resolvedHint = computed((): string => {
    if (props.hint) { return props.hint; }
    if (props.type === 'image') {
        if (props.language === 'es') { return 'Arrastra o suelta fotos · PNG, JPG, WEBP'; }
        if (props.language === 'en') { return 'Drag & drop photos · PNG, JPG, WEBP'; }
    }
    if (props.language === 'es') { return 'Arrastra o haz clic · PDF Español'; }
    if (props.language === 'en') { return 'Drag & drop or click · PDF English'; }
    return t('Drag & drop or click to upload');
});

const trigger = (): void => {
    inputRef.value?.click();
};

const emitFiles = (fileList: FileList | null): void => {
    if (!fileList || fileList.length === 0) { return; }

    const files = Array.from(fileList);

    if (files.length > 1) {
        emit('files-selected', files);
    } else {
        emit('file-selected', files[0]);
        emit('files-selected', files);
    }

    if (inputRef.value) {
        inputRef.value.value = '';
    }
};

const onFileChange = (event: Event): void => {
    emitFiles((event.target as HTMLInputElement).files);
};

const onDrop = (event: DragEvent): void => {
    isDragging.value = false;
    emitFiles(event.dataTransfer?.files ?? null);
};
</script>
