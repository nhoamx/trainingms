<template>
  <div class="bg-white rounded-lg border border-slate-200 p-6 space-y-4">
    <div class="flex items-center justify-between gap-3">
      <div>
        <h4 class="text-lg font-semibold text-slate-900">Contenido complementario de análisis</h4>
        <p class="text-sm text-slate-600">Bloques de texto/tabla para {{ instrumentLabel }} debajo de gráficas.</p>
      </div>
      <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
        {{ blocks.length }} bloque{{ blocks.length === 1 ? '' : 's' }}
      </span>
    </div>

    <form
      v-if="canManage"
      @submit.prevent="submitBlock"
      class="rounded-lg border border-indigo-200 bg-indigo-50 p-4 space-y-4"
    >
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label :for="`analysis_block_title_${instrumentType}`" class="block text-sm font-medium text-slate-700">Título (opcional)</label>
          <input
            :id="`analysis_block_title_${instrumentType}`"
            v-model="blockForm.title"
            type="text"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            placeholder="Ej. Tabla comparativa por área"
          />
          <p v-if="blockForm.errors.title" class="mt-1 text-xs text-red-500">{{ blockForm.errors.title }}</p>
        </div>

        <div>
          <label :for="`analysis_block_sort_${instrumentType}`" class="block text-sm font-medium text-slate-700">Orden</label>
          <input
            :id="`analysis_block_sort_${instrumentType}`"
            v-model.number="blockForm.sort_order"
            type="number"
            min="0"
            max="9999"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          />
          <p v-if="blockForm.errors.sort_order" class="mt-1 text-xs text-red-500">{{ blockForm.errors.sort_order }}</p>
        </div>
      </div>

      <div class="space-y-2">
        <span class="block text-sm font-medium text-slate-700">Editor</span>
        <div class="flex flex-wrap gap-2">
          <button type="button" @click="applyFormat('bold')" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Negrita</button>
          <button type="button" @click="applyFormat('italic')" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Cursiva</button>
          <button type="button" @click="applyFormat('underline')" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Subrayado</button>
          <button type="button" @click="insertTable" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">Insertar tabla</button>
        </div>

        <div
          :id="`analysis_block_editor_${instrumentType}`"
          ref="editorRef"
          contenteditable="true"
          role="textbox"
          aria-multiline="true"
          class="min-h-[180px] rounded-md border border-slate-300 bg-white p-3 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          @input="syncEditorContent"
        />

        <p v-if="blockForm.errors.content_html" class="text-xs text-red-500">{{ blockForm.errors.content_html }}</p>
      </div>

      <div class="flex items-center justify-end gap-2">
        <button
          v-if="editingBlockId !== null"
          type="button"
          @click="cancelEditing"
          class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
        >
          Cancelar
        </button>
        <button
          type="submit"
          :disabled="blockForm.processing"
          class="rounded-md bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50"
        >
          {{ blockForm.processing ? 'Guardando...' : (editingBlockId === null ? 'Guardar bloque' : 'Actualizar bloque') }}
        </button>
      </div>
    </form>

    <div v-if="blocks.length === 0" class="rounded-lg border-2 border-dashed border-slate-300 p-6 text-center">
      <p class="text-sm font-medium text-slate-700">Sin contenido complementario</p>
      <p class="text-xs text-slate-500 mt-1">Agrega una tabla o texto para documentar hallazgos y recomendaciones.</p>
    </div>

    <div v-else class="space-y-4">
      <article
        v-for="block in sortedBlocks"
        :key="block.id"
        class="rounded-lg border border-slate-200 p-4 space-y-3"
      >
        <div class="flex items-start justify-between gap-3">
          <h5 v-if="block.title" class="text-sm font-semibold text-slate-900">{{ block.title }}</h5>
          <span v-else class="text-sm font-semibold text-slate-500">Sin título</span>

          <div class="flex items-center gap-2">
            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-1 text-[11px] font-medium text-slate-600">Orden {{ block.sort_order }}</span>
            <template v-if="canManage">
              <button type="button" @click="editBlock(block)" class="rounded-md bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-700 hover:bg-slate-200">Editar</button>
              <button type="button" @click="deleteBlock(block.id)" class="rounded-md bg-red-600 px-2 py-1 text-[11px] font-semibold text-white hover:bg-red-500">Eliminar</button>
            </template>
          </div>
        </div>

        <div class="analysis-richtext text-sm text-slate-700" v-html="block.content_html" />
      </article>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';

interface AnalysisBlock {
  id: number;
  title: string | null;
  content_html: string;
  sort_order: number;
}

interface Props {
  organizationId: string | number;
  instrumentType: 'referencia_i' | 'referencia_iii';
  blocks: AnalysisBlock[];
  canManage?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  canManage: false,
});

const route = (...args: unknown[]): string => (window as unknown as Window & { route: (...params: unknown[]) => string }).route(...args);

const editorRef = ref<HTMLDivElement | null>(null);
const editingBlockId = ref<number | null>(null);

const blockForm = useForm({
  instrument_type: props.instrumentType,
  title: '',
  content_html: '',
  sort_order: 0,
});

const instrumentLabel = computed(() => {
  return props.instrumentType === 'referencia_i' ? 'GRI (Referencia I)' : 'GRIII (Referencia III)';
});

const sortedBlocks = computed(() => {
  return [...props.blocks].sort((a, b) => {
    if (a.sort_order === b.sort_order) {
      return a.id - b.id;
    }

    return a.sort_order - b.sort_order;
  });
});

const syncEditorContent = (): void => {
  blockForm.content_html = editorRef.value?.innerHTML ?? '';
};

const applyFormat = (command: 'bold' | 'italic' | 'underline'): void => {
  document.execCommand(command);
  syncEditorContent();
  editorRef.value?.focus();
};

const insertTable = (): void => {
  const tableHtml = '<table><thead><tr><th>Columna 1</th><th>Columna 2</th></tr></thead><tbody><tr><td>Dato 1</td><td>Dato 2</td></tr></tbody></table><p></p>';
  document.execCommand('insertHTML', false, tableHtml);
  syncEditorContent();
  editorRef.value?.focus();
};

const resetForm = (): void => {
  blockForm.reset();
  blockForm.instrument_type = props.instrumentType;
  blockForm.sort_order = 0;
  editingBlockId.value = null;

  if (editorRef.value) {
    editorRef.value.innerHTML = '';
  }
};

const cancelEditing = (): void => {
  resetForm();
};

const editBlock = async (block: AnalysisBlock): Promise<void> => {
  editingBlockId.value = block.id;
  blockForm.instrument_type = props.instrumentType;
  blockForm.title = block.title ?? '';
  blockForm.content_html = block.content_html;
  blockForm.sort_order = block.sort_order;

  await nextTick();

  if (editorRef.value) {
    editorRef.value.innerHTML = block.content_html;
  }
};

const submitBlock = (): void => {
  syncEditorContent();

  const options = {
    preserveScroll: true,
    onSuccess: () => resetForm(),
  };

  if (editingBlockId.value === null) {
    blockForm.post(route('organization.analysis-blocks.store', props.organizationId), options);

    return;
  }

  blockForm.put(route('organization.analysis-blocks.update', [props.organizationId, editingBlockId.value]), options);
};

const deleteBlock = (blockId: number): void => {
  router.delete(route('organization.analysis-blocks.destroy', [props.organizationId, blockId]), {
    preserveScroll: true,
    onSuccess: () => {
      if (editingBlockId.value === blockId) {
        resetForm();
      }
    },
  });
};
</script>

<style scoped>
.analysis-richtext :deep(table) {
  width: 100%;
  border-collapse: collapse;
}

.analysis-richtext :deep(th),
.analysis-richtext :deep(td) {
  border: 1px solid rgb(226 232 240);
  padding: 0.5rem;
  text-align: left;
}

.analysis-richtext :deep(th) {
  background-color: rgb(248 250 252);
  font-weight: 600;
}
</style>
