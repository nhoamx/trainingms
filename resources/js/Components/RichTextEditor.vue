<template>
  <div :class="['rich-editor', { 'rich-editor--readonly': readonly }]">
    <!-- Read-only: render stored HTML directly -->
    <div v-if="readonly" class="rich-editor__readonly" v-html="modelValue || ''" />

    <!-- Edit mode -->
    <template v-else>
      <div class="rich-editor__toolbar">
        <!-- Text formatting -->
        <button
          v-for="fmt in textButtons"
          :key="fmt.key"
          type="button"
          @click="fmt.action()"
          :class="['rich-editor__btn', { 'is-active': fmt.isActive() }]"
          :title="fmt.title"
          v-html="fmt.label"
        />

        <!-- Text color palette -->
        <div class="relative">
          <button
            type="button"
            class="rich-editor__btn rich-editor__colorpick-btn"
            @mousedown.prevent="toggleTextColorPicker"
            title="Color de texto"
          >
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 20h12M12 4l5.5 12H6.5L12 4z" />
            </svg>
            <span class="rich-editor__colorbar" :style="{ backgroundColor: currentTextColor }" />
          </button>
          <template v-if="showTextColorPicker">
            <div class="fixed inset-0 z-10" @mousedown.prevent="showTextColorPicker = false" />
            <div class="rich-editor__palette absolute left-0 top-full z-20 mt-1">
              <button
                v-for="color in colorPalette"
                :key="color"
                type="button"
                class="rich-editor__swatch"
                :style="{ backgroundColor: color }"
                :title="color"
                @mousedown.prevent="applyTextColor(color)"
              />
            </div>
          </template>
        </div>

        <span class="rich-editor__sep" />

        <!-- Headings -->
        <button
          v-for="h in headingButtons"
          :key="h.level"
          type="button"
          @click="editor?.chain().focus().toggleHeading({ level: h.level }).run()"
          :class="['rich-editor__btn', { 'is-active': editor?.isActive('heading', { level: h.level }) }]"
          :title="`Heading ${h.level}`"
        >
          H{{ h.level }}
        </button>

        <span class="rich-editor__sep" />

        <!-- Lists -->
        <button
          type="button"
          @click="editor?.chain().focus().toggleBulletList().run()"
          :class="['rich-editor__btn', { 'is-active': editor?.isActive('bulletList') }]"
          title="Lista con viñetas"
        >
          <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
          </svg>
        </button>
        <button
          type="button"
          @click="editor?.chain().focus().toggleOrderedList().run()"
          :class="['rich-editor__btn', { 'is-active': editor?.isActive('orderedList') }]"
          title="Lista numerada"
        >
          <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5h11M9 12h11M9 19h11M4 5v.01M4 12v.01M4 19v.01" />
          </svg>
        </button>

        <span class="rich-editor__sep" />

        <!-- Table: insert (always visible) -->
        <button
          type="button"
          @click="insertTable"
          :class="['rich-editor__btn', 'gap-1']"
          title="Insertar tabla (también puedes pegar desde Excel)"
        >
          <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18M10 3v18M3 7a1 1 0 011-1h16a1 1 0 011 1v10a1 1 0 01-1 1H4a1 1 0 01-1-1V7z" />
          </svg>
          <span class="text-xs">Tabla</span>
        </button>

        <!-- Table operations: only when inside a table -->
        <template v-if="editor?.isActive('table')">
          <span class="rich-editor__sep" />
          <button
            type="button"
            @click="editor?.chain().focus().addColumnAfter().run()"
            class="rich-editor__btn"
            title="Agregar columna a la derecha"
          >+Col</button>
          <button
            type="button"
            @click="editor?.chain().focus().addRowAfter().run()"
            class="rich-editor__btn"
            title="Agregar fila abajo"
          >+Fila</button>
          <button
            type="button"
            @click="editor?.chain().focus().mergeCells().run()"
            class="rich-editor__btn"
            title="Combinar celdas seleccionadas"
          >Combinar</button>
          <button
            type="button"
            @click="editor?.chain().focus().splitCell().run()"
            class="rich-editor__btn"
            title="Dividir celda"
          >Dividir</button>

          <!-- Cell / row background color palette -->
          <div class="relative">
            <button
              type="button"
              class="rich-editor__btn rich-editor__colorpick-btn"
              @mousedown.prevent="toggleCellBgPicker"
              title="Color de fondo de celda (clic en celda) · colorear fila (clic en fila)"
            >
              <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2" />
                <path stroke-linecap="round" d="M3 9h18M3 15h18M9 3v18M15 3v18" />
              </svg>
              <span class="rich-editor__colorbar" :style="{ backgroundColor: currentCellBgColor }" />
            </button>
            <template v-if="showCellBgPicker">
              <div class="fixed inset-0 z-10" @mousedown.prevent="showCellBgPicker = false" />
              <div class="rich-editor__palette absolute left-0 top-full z-20 mt-1">
                <button
                  v-for="color in colorPalette"
                  :key="color"
                  type="button"
                  class="rich-editor__swatch"
                  :style="{ backgroundColor: color }"
                  :title="color"
                  @mousedown.prevent="applyCellBgColor(color)"
                />
                <div class="col-span-5 mt-1 border-t border-gray-200 pt-1">
                  <button
                    type="button"
                    class="w-full rounded bg-gray-100 px-2 py-1 text-left text-xs font-medium text-gray-700 hover:bg-gray-200"
                    @mousedown.prevent="colorizeCurrentRow"
                  >
                    Colorear fila completa
                  </button>
                </div>
              </div>
            </template>
          </div>

          <span class="rich-editor__sep" />
          <button
            type="button"
            @click="editor?.chain().focus().deleteColumn().run()"
            class="rich-editor__btn is-danger"
            title="Eliminar columna"
          >−Col</button>
          <button
            type="button"
            @click="editor?.chain().focus().deleteRow().run()"
            class="rich-editor__btn is-danger"
            title="Eliminar fila"
          >−Fila</button>
          <button
            type="button"
            @click="editor?.chain().focus().deleteTable().run()"
            class="rich-editor__btn is-danger"
            title="Eliminar tabla"
          >✕ Tabla</button>
        </template>
      </div>

      <div class="rich-editor__body">
        <editor-content :editor="editor" />
      </div>

      <div class="rich-editor__hint">
        <kbd>Ctrl+B</kbd> negrita · <kbd>Ctrl+I</kbd> cursiva · Pega una tabla desde Excel directamente
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import { Table } from '@tiptap/extension-table';
import { TableRow } from '@tiptap/extension-table-row';
import { TableCell } from '@tiptap/extension-table-cell';
import { TableHeader } from '@tiptap/extension-table-header';
import { Color } from '@tiptap/extension-color';
import { TextStyle } from '@tiptap/extension-text-style';

interface Props {
  modelValue: string;
  readonly?: boolean;
  placeholder?: string;
  minHeight?: string;
}

const props = withDefaults(defineProps<Props>(), {
  readonly: false,
  placeholder: 'Escribe aquí o pega una tabla desde Excel…',
  minHeight: '180px',
});

const emit = defineEmits<{
  'update:modelValue': [value: string];
}>();

const CustomTableCell = TableCell.extend({
  addAttributes() {
    return {
      ...this.parent?.(),
      backgroundColor: {
        default: null,
        parseHTML: (element: HTMLElement) =>
          element.getAttribute('data-bg-color') ?? element.style.backgroundColor ?? null,
        renderHTML: (attributes: Record<string, unknown>) => {
          if (!attributes.backgroundColor) return {};
          return {
            'data-bg-color': attributes.backgroundColor as string,
            style: `background-color: ${attributes.backgroundColor}`,
          };
        },
      },
    };
  },
});

const CustomTableHeader = TableHeader.extend({
  addAttributes() {
    return {
      ...this.parent?.(),
      backgroundColor: {
        default: null,
        parseHTML: (element: HTMLElement) =>
          element.getAttribute('data-bg-color') ?? element.style.backgroundColor ?? null,
        renderHTML: (attributes: Record<string, unknown>) => {
          if (!attributes.backgroundColor) return {};
          return {
            'data-bg-color': attributes.backgroundColor as string,
            style: `background-color: ${attributes.backgroundColor}`,
          };
        },
      },
    };
  },
});

const editor = useEditor({
  content: props.modelValue,
  editable: !props.readonly,
  extensions: [
    StarterKit.configure({
      heading: { levels: [2, 3, 4] },
    }),
    TextStyle,
    Color,
    Table.configure({ resizable: true }),
    TableRow,
    CustomTableHeader,
    CustomTableCell,
  ],
  onUpdate: ({ editor: e }) => {
    emit('update:modelValue', e.getHTML());
  },
  onSelectionUpdate: ({ editor: e }) => {
    const color = e.getAttributes('textStyle').color as string | undefined;
    if (color) {
      currentTextColor.value = color;
    }
  },
});

// Sync when value changes externally (e.g. after save)
watch(
  () => props.modelValue,
  (value) => {
    if (editor.value && editor.value.getHTML() !== value) {
      editor.value.commands.setContent(value ?? '', false);
    }
  },
);

onBeforeUnmount(() => {
  editor.value?.destroy();
});

const textButtons = computed(() => [
  {
    key: 'bold',
    label: '<strong>B</strong>',
    title: 'Negrita (Ctrl+B)',
    action: () => editor.value?.chain().focus().toggleBold().run(),
    isActive: () => editor.value?.isActive('bold') ?? false,
  },
  {
    key: 'italic',
    label: '<em>I</em>',
    title: 'Cursiva (Ctrl+I)',
    action: () => editor.value?.chain().focus().toggleItalic().run(),
    isActive: () => editor.value?.isActive('italic') ?? false,
  },
]);

const headingButtons = [
  { level: 2 as const },
  { level: 3 as const },
  { level: 4 as const },
];

const insertTable = (): void => {
  editor.value?.chain().focus().insertTable({ rows: 4, cols: 6, withHeaderRow: true }).run();
};

const colorPalette: string[] = [
  '#000000', '#374151', '#6b7280', '#9ca3af', '#ffffff',
  '#dc2626', '#f97316', '#f59e0b', '#84cc16', '#22c55e',
  '#14b8a6', '#3b82f6', '#6366f1', '#a855f7', '#ec4899',
  '#fca5a5', '#fed7aa', '#fde68a', '#bbf7d0', '#a5f3fc',
  '#bfdbfe', '#e9d5ff', '#fce7f3', '#e5e7eb', '#1e293b',
];

const currentTextColor = ref<string>('#000000');
const currentCellBgColor = ref<string>('#e2e8f0');
const showTextColorPicker = ref<boolean>(false);
const showCellBgPicker = ref<boolean>(false);

const toggleTextColorPicker = (): void => {
  showTextColorPicker.value = !showTextColorPicker.value;
  showCellBgPicker.value = false;
};

const toggleCellBgPicker = (): void => {
  showCellBgPicker.value = !showCellBgPicker.value;
  showTextColorPicker.value = false;
};

const applyTextColor = (color: string): void => {
  currentTextColor.value = color;
  showTextColorPicker.value = false;
  editor.value?.chain().focus().setColor(color).run();
};

const applyCellBgColor = (color: string): void => {
  currentCellBgColor.value = color;
  showCellBgPicker.value = false;
  editor.value?.chain().focus().setCellAttribute('backgroundColor', color).run();
};

const colorizeCurrentRow = (): void => {
  showCellBgPicker.value = false;
  if (!editor.value) return;
  const { state } = editor.value;
  const { $anchor } = state.selection;
  // Walk up to find the row (tr) depth
  let depth = $anchor.depth;
  while (depth > 0 && $anchor.node(depth).type.name !== 'tableRow') {
    depth--;
  }
  if (depth === 0) return;
  const rowNode = $anchor.node(depth);
  const rowStart = $anchor.start(depth);
  let offset = 0;
  const chain = editor.value.chain().focus();
  rowNode.forEach((cell, cellOffset) => {
    const cellPos = rowStart + cellOffset + offset;
    chain.setNodeSelection(cellPos).setCellAttribute('backgroundColor', currentCellBgColor.value);
    offset += cell.nodeSize - 1;
  });
  chain.run();
};
</script>

<style scoped>
.rich-editor {
  @apply rounded-lg border border-gray-300;
  min-width: 0;
  overflow: hidden;
}

.rich-editor--readonly {
  @apply border-transparent shadow-xl;
}

.rich-editor__toolbar {
  @apply flex flex-wrap items-center gap-1 border-b border-gray-200 bg-gray-50 px-2.5 py-2;
  position: sticky;
  top: 0;
  z-index: 5;
}

.rich-editor__btn {
  @apply inline-flex cursor-pointer items-center justify-center rounded px-2 py-1 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-200;
  min-width: 1.75rem;
  min-height: 1.75rem;
}

.rich-editor__btn.is-active {
  @apply bg-teal-100 text-teal-800;
}

.rich-editor__btn.is-danger {
  @apply text-red-600 hover:bg-red-50;
}

.rich-editor__sep {
  @apply mx-0.5 self-stretch border-l border-gray-200;
}

.rich-editor__body {
  @apply bg-white px-4 py-3;
  overflow-x: auto;
}

.rich-editor__hint {
  @apply border-t border-gray-100 bg-gray-50 px-3 py-1.5 text-xs text-gray-400;
}

.rich-editor__hint kbd {
  @apply rounded border border-gray-300 bg-white px-1 py-0.5 font-mono text-xs text-gray-500;
}

/* ProseMirror editor content */
:deep(.ProseMirror) {
  outline: none;
}

:deep(.ProseMirror p) {
  @apply my-2 text-sm leading-relaxed text-gray-800;
}

:deep(.ProseMirror > *:first-child) {
  margin-top: 0;
}

:deep(.ProseMirror > *:last-child) {
  margin-bottom: 0;
}

:deep(.ProseMirror h2) {
  @apply mb-2 mt-4 text-lg font-bold text-gray-900;
}

:deep(.ProseMirror h3) {
  @apply mb-1.5 mt-3 text-base font-semibold text-gray-900;
}

:deep(.ProseMirror h4) {
  @apply mb-1 mt-2 text-sm font-semibold text-gray-800;
}

:deep(.ProseMirror ul) {
  @apply my-2 ml-5 list-disc space-y-1 text-sm text-gray-800;
}

:deep(.ProseMirror ol) {
  @apply my-2 ml-5 list-decimal space-y-1 text-sm text-gray-800;
}

:deep(.ProseMirror strong) {
  @apply font-semibold;
}

/* Table styles — match AnalisisPlanta1.vue visual */
:deep(.ProseMirror table),
:deep(.rich-editor__readonly table) {
  border-collapse: collapse;
  min-width: max-content;
  margin: 0.75rem 0;
}

:deep(.ProseMirror table th),
:deep(.rich-editor__readonly table th) {
  background-color: #1e293b;
  color: #fff;
  font-weight: 500;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 12px 24px;
  text-align: left;
  border-right: 1px solid #334155;
  white-space: nowrap;
  position: relative;
}

:deep(.ProseMirror table td),
:deep(.rich-editor__readonly table td) {
  padding: 12px 24px;
  border-bottom: 1px solid #e5e7eb;
  vertical-align: middle;
  color: #1f2937;
  font-size: 0.875rem;
  white-space: nowrap;
}

:deep(.ProseMirror table tr:nth-child(even) td),
:deep(.rich-editor__readonly table tr:nth-child(even) td) {
  background-color: #f9fafb;
}

:deep(.ProseMirror table tr:hover td) {
  background-color: #f0fdf4;
}

/* TipTap selected cell highlight */
:deep(.ProseMirror .selectedCell::after) {
  background: rgba(20, 184, 166, 0.12);
  content: '';
  left: 0;
  right: 0;
  top: 0;
  bottom: 0;
  pointer-events: none;
  position: absolute;
  z-index: 2;
}

/* Column resize handle */
:deep(.ProseMirror .column-resize-handle) {
  background-color: #14b8a6;
  bottom: -2px;
  position: absolute;
  right: -2px;
  top: 0;
  width: 4px;
  z-index: 20;
  pointer-events: none;
}

/* Readonly view scrollable wrapper */
:deep(.rich-editor__readonly) {
  overflow-x: auto;
}
:deep(.rich-editor__readonly p) {
  @apply my-2 text-sm leading-relaxed text-gray-700;
}

:deep(.rich-editor__readonly h2) {
  @apply mb-2 mt-4 text-lg font-bold text-gray-900;
}

:deep(.rich-editor__readonly h3) {
  @apply mb-1.5 mt-3 text-base font-semibold text-gray-900;
}

:deep(.rich-editor__readonly h4) {
  @apply mb-1 mt-2 text-sm font-semibold text-gray-800;
}

:deep(.rich-editor__readonly ul) {
  @apply my-2 ml-5 list-disc space-y-1 text-sm text-gray-700;
}

:deep(.rich-editor__readonly ol) {
  @apply my-2 ml-5 list-decimal space-y-1 text-sm text-gray-700;
}

:deep(.rich-editor__readonly strong) {
  @apply font-semibold;
}

/* Color picker buttons */
.rich-editor__colorpick-btn {
  @apply flex cursor-pointer flex-col items-center gap-0.5;
}

.rich-editor__colorbar {
  display: block;
  height: 3px;
  width: 1.25rem;
  border-radius: 2px;
  flex-shrink: 0;
}

/* Color palette dropdown */
.rich-editor__palette {
  display: grid;
  grid-template-columns: repeat(5, 1.25rem);
  gap: 3px;
  padding: 8px;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  box-shadow: 0 4px 16px rgba(0,0,0,0.12);
  min-width: 8rem;
}

.rich-editor__swatch {
  width: 1.25rem;
  height: 1.25rem;
  border-radius: 3px;
  border: 1px solid rgba(0,0,0,0.15);
  cursor: pointer;
  transition: transform 0.1s, box-shadow 0.1s;
}

.rich-editor__swatch:hover {
  transform: scale(1.2);
  box-shadow: 0 0 0 2px #14b8a6;
}
</style>
