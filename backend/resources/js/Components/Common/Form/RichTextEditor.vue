<script setup lang="ts" name="RichTextEditor">
  import { watch, onBeforeUnmount } from 'vue'
  import { useEditor, EditorContent } from '@tiptap/vue-3'
  import StarterKit from '@tiptap/starter-kit'
  import Underline from '@tiptap/extension-underline'
  import TextAlign from '@tiptap/extension-text-align'
  import { TextStyle } from '@tiptap/extension-text-style'
  import Color from '@tiptap/extension-color'
  import Highlight from '@tiptap/extension-highlight'

  const props = defineProps<{
    modelValue: string
    placeholder?: string
  }>()

  const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void
  }>()

  const editor = useEditor({
    content: props.modelValue,
    extensions: [
      StarterKit.configure({ underline: false }),
      Underline,
      TextStyle,
      Color,
      Highlight.configure({ multicolor: true }),
      TextAlign.configure({ types: ['heading', 'paragraph'] }),
    ],
    editorProps: {
      attributes: {
        class: 'rich-editor-body',
        spellcheck: 'false',
      },
    },
    onUpdate: ({ editor }) => {
      emit('update:modelValue', editor.getHTML())
    },
  })

  watch(() => props.modelValue, (val) => {
    if (editor.value && editor.value.getHTML() !== val) {
      editor.value.commands.setContent(val, { emitUpdate: false })
    }
  })

  onBeforeUnmount(() => editor.value?.destroy())

  const COLORS = ['#000000', '#ef4444', '#f97316', '#eab308', '#22c55e', '#3b82f6', '#8b5cf6', '#ec4899', '#ffffff']
</script>

<template>
  <div class="rich-editor" v-if="editor">

    <!-- ── Toolbar ── -->
    <div class="rich-editor-toolbar">

      <!-- Historia -->
      <div class="rte-group">
        <button type="button" title="Deshacer" @click="editor.chain().focus().undo().run()" :disabled="!editor.can().undo()">
          <i class="fa-solid fa-rotate-left" />
        </button>
        <button type="button" title="Rehacer" @click="editor.chain().focus().redo().run()" :disabled="!editor.can().redo()">
          <i class="fa-solid fa-rotate-right" />
        </button>
      </div>

      <div class="rte-sep" />

      <!-- Headings -->
      <div class="rte-group">
        <button type="button" title="Título 1"
          :class="{ active: editor.isActive('heading', { level: 1 }) }"
          @click="editor.chain().focus().toggleHeading({ level: 1 }).run()">
          H1
        </button>
        <button type="button" title="Título 2"
          :class="{ active: editor.isActive('heading', { level: 2 }) }"
          @click="editor.chain().focus().toggleHeading({ level: 2 }).run()">
          H2
        </button>
        <button type="button" title="Título 3"
          :class="{ active: editor.isActive('heading', { level: 3 }) }"
          @click="editor.chain().focus().toggleHeading({ level: 3 }).run()">
          H3
        </button>
      </div>

      <div class="rte-sep" />

      <!-- Formato -->
      <div class="rte-group">
        <button type="button" title="Negrita" :class="{ active: editor.isActive('bold') }"
          @click="editor.chain().focus().toggleBold().run()">
          <i class="fa-solid fa-bold" />
        </button>
        <button type="button" title="Cursiva" :class="{ active: editor.isActive('italic') }"
          @click="editor.chain().focus().toggleItalic().run()">
          <i class="fa-solid fa-italic" />
        </button>
        <button type="button" title="Subrayado" :class="{ active: editor.isActive('underline') }"
          @click="editor.chain().focus().toggleUnderline().run()">
          <i class="fa-solid fa-underline" />
        </button>
        <button type="button" title="Tachado" :class="{ active: editor.isActive('strike') }"
          @click="editor.chain().focus().toggleStrike().run()">
          <i class="fa-solid fa-strikethrough" />
        </button>
        <button type="button" title="Código" :class="{ active: editor.isActive('code') }"
          @click="editor.chain().focus().toggleCode().run()">
          <i class="fa-solid fa-code" />
        </button>
      </div>

      <div class="rte-sep" />

      <!-- Alineación -->
      <div class="rte-group">
        <button type="button" title="Izquierda" :class="{ active: editor.isActive({ textAlign: 'left' }) }"
          @click="editor.chain().focus().setTextAlign('left').run()">
          <i class="fa-solid fa-align-left" />
        </button>
        <button type="button" title="Centrar" :class="{ active: editor.isActive({ textAlign: 'center' }) }"
          @click="editor.chain().focus().setTextAlign('center').run()">
          <i class="fa-solid fa-align-center" />
        </button>
        <button type="button" title="Derecha" :class="{ active: editor.isActive({ textAlign: 'right' }) }"
          @click="editor.chain().focus().setTextAlign('right').run()">
          <i class="fa-solid fa-align-right" />
        </button>
        <button type="button" title="Justificar" :class="{ active: editor.isActive({ textAlign: 'justify' }) }"
          @click="editor.chain().focus().setTextAlign('justify').run()">
          <i class="fa-solid fa-align-justify" />
        </button>
      </div>

      <div class="rte-sep" />

      <!-- Listas -->
      <div class="rte-group">
        <button type="button" title="Lista con viñetas" :class="{ active: editor.isActive('bulletList') }"
          @click="editor.chain().focus().toggleBulletList().run()">
          <i class="fa-solid fa-list-ul" />
        </button>
        <button type="button" title="Lista numerada" :class="{ active: editor.isActive('orderedList') }"
          @click="editor.chain().focus().toggleOrderedList().run()">
          <i class="fa-solid fa-list-ol" />
        </button>
        <button type="button" title="Sangría +" @click="editor.chain().focus().sinkListItem('listItem').run()">
          <i class="fa-solid fa-indent" />
        </button>
        <button type="button" title="Sangría -" @click="editor.chain().focus().liftListItem('listItem').run()">
          <i class="fa-solid fa-outdent" />
        </button>
      </div>

      <div class="rte-sep" />

      <!-- Bloque -->
      <div class="rte-group">
        <button type="button" title="Cita" :class="{ active: editor.isActive('blockquote') }"
          @click="editor.chain().focus().toggleBlockquote().run()">
          <i class="fa-solid fa-quote-left" />
        </button>
        <button type="button" title="Línea horizontal"
          @click="editor.chain().focus().setHorizontalRule().run()">
          <i class="fa-solid fa-minus" />
        </button>
      </div>

      <div class="rte-sep" />

      <!-- Color de texto -->
      <div class="rte-group rte-colors">
        <span class="rte-label">A</span>
        <button
          v-for="color in COLORS"
          :key="color"
          type="button"
          :title="`Color ${color}`"
          class="rte-color-dot"
          :style="{ background: color }"
          :class="{ active: editor.isActive('textStyle', { color }) }"
          @click="editor.chain().focus().setColor(color).run()"
        />
        <button type="button" title="Sin color" class="rte-color-clear"
          @click="editor.chain().focus().unsetColor().run()">
          <i class="fa-solid fa-xmark" />
        </button>
      </div>

      <div class="rte-sep" />

      <!-- Resaltado -->
      <div class="rte-group">
        <button type="button" title="Resaltar" :class="{ active: editor.isActive('highlight') }"
          @click="editor.chain().focus().toggleHighlight().run()">
          <i class="fa-solid fa-highlighter" />
        </button>
        <button type="button" title="Limpiar formato"
          @click="editor.chain().focus().clearNodes().unsetAllMarks().run()">
          <i class="fa-solid fa-eraser" />
        </button>
      </div>

    </div>

    <!-- ── Contenido ── -->
    <EditorContent :editor="editor" />
  </div>
</template>

<style scoped>
.rich-editor {
  border: 1px solid var(--ds-border);
  border-radius: 0.375rem;
  overflow: hidden;
  font-size: 0.875rem;
}

.rich-editor-toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 2px;
  padding: 6px 8px;
  background: var(--ds-surface-2);
  border-bottom: 1px solid var(--ds-border);
}

.rte-group {
  display: flex;
  align-items: center;
  gap: 2px;
}

.rte-sep {
  width: 1px;
  height: 20px;
  background: var(--ds-border-2);
  margin: 0 4px;
}

.rich-editor-toolbar button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 28px;
  height: 28px;
  padding: 0 5px;
  border: none;
  border-radius: 4px;
  background: transparent;
  color: var(--ds-text-2);
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.15s, color 0.15s;
}

.rich-editor-toolbar button:hover:not(:disabled) {
  background: var(--ds-border);
  color: var(--ds-text);
}

.rich-editor-toolbar button.active {
  background: #0d6efd;
  color: #fff;
}

.rich-editor-toolbar button:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}

/* Colores */
.rte-colors {
  align-items: center;
  gap: 3px;
}

.rte-label {
  font-weight: 700;
  font-size: 0.8rem;
  margin-right: 2px;
  color: var(--ds-text-2);
}

.rte-color-dot {
  width: 18px !important;
  height: 18px !important;
  min-width: 18px !important;
  border-radius: 50% !important;
  border: 2px solid transparent !important;
  padding: 0 !important;
  cursor: pointer;
}

.rte-color-dot.active,
.rte-color-dot:hover {
  border-color: #0d6efd !important;
  transform: scale(1.2);
}

.rte-color-clear {
  font-size: 0.65rem !important;
  color: var(--ds-text-2) !important;
}

/* Área de edición */
:deep(.rich-editor-body) {
  min-height: 280px;
  max-height: 480px;
  overflow-y: auto;
  padding: 12px 16px;
  outline: none;
  line-height: 1.7;
  color: var(--ds-text);
  background: var(--ds-surface);
}

:deep(.rich-editor-body p) { margin-bottom: 0.6rem; }
:deep(.rich-editor-body h1) { font-size: 1.5rem; font-weight: 700; margin: 1rem 0 0.4rem; }
:deep(.rich-editor-body h2) { font-size: 1.25rem; font-weight: 600; margin: 0.9rem 0 0.4rem; }
:deep(.rich-editor-body h3) { font-size: 1.05rem; font-weight: 600; margin: 0.8rem 0 0.3rem; }
:deep(.rich-editor-body ul),
:deep(.rich-editor-body ol) { padding-left: 1.5rem; margin-bottom: 0.6rem; }
:deep(.rich-editor-body blockquote) {
  border-left: 3px solid #0d6efd;
  margin: 0.6rem 0;
  padding: 0.3rem 0.8rem;
  color: var(--ds-text-2);
  background: rgba(13,110,253,.1);
  border-radius: 0 4px 4px 0;
}
:deep(.rich-editor-body code) {
  background: var(--ds-surface-2);
  padding: 2px 5px;
  border-radius: 3px;
  font-size: 0.85em;
  color: #d63384;
}
:deep(.rich-editor-body hr) {
  border: none;
  border-top: 2px solid var(--ds-border);
  margin: 0.8rem 0;
}
:deep(.rich-editor-body mark) {
  background: #fff3cd;
  padding: 0 2px;
  border-radius: 2px;
  color: #212529;
}
</style>
