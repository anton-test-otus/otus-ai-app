<template>
  <div class="markdown-preview h-full overflow-auto bg-white dark:bg-gray-800 content-padding">
    <div ref="rootRef" class="milkdown-preview-root markdown-prose min-h-full w-full max-w-full" />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import { Editor, editorViewOptionsCtx, rootCtx } from '@milkdown/core';
import { commonmark } from '@milkdown/preset-commonmark';
import { gfm } from '@milkdown/preset-gfm';
import { replaceAll } from '@milkdown/utils';
import {
  remarkWikiLinkPlugin,
  remarkWikiLinkStringifyPlugin,
  wikiLinkNodeView,
  wikiLinkSchema,
} from './wikiLinkNode';
import { remarkStripHtmlPlugin } from './remarkStripHtml';

interface Props {
  content: string;
}

const props = defineProps<Props>();
const router = useRouter();
const toast = useToast();
const rootRef = ref<HTMLElement>();
let editor: Editor | null = null;
let isReady = false;

function syncContent(content: string) {
  if (!editor || !isReady) return;
  editor.action(replaceAll(content ?? ''));
}

const handleClick = (event: MouseEvent) => {
  const target = event.target as HTMLElement;
  const link = target.closest('[data-wiki-link]') as HTMLElement | null;
  if (!link) return;

  event.preventDefault();

  const linkType = link.getAttribute('data-link-type');

  if (linkType === 'exists') {
    const noteId = link.getAttribute('data-note-id');
    if (noteId) {
      router.push({ name: 'note', params: { id: noteId }, query: { mode: 'preview' } });
    }
  } else if (linkType === 'missing') {
    toast.add({
      severity: 'warn',
      summary: 'Заметка не найдена',
      detail: 'Связанная заметка удалена или недоступна',
      life: 3000,
    });
  }
};

onMounted(async () => {
  if (!rootRef.value) return;

  rootRef.value.addEventListener('click', handleClick);

  try {
    editor = await Editor.make()
      .config((ctx) => {
        ctx.set(rootCtx, rootRef.value!);
        ctx.set(editorViewOptionsCtx, {
          editable: () => false,
          attributes: {
            class: 'outline-none max-w-none cursor-default',
          },
        });
      })
      .use(commonmark)
      .use(gfm)
      .use(remarkStripHtmlPlugin)
      .use(wikiLinkSchema)
      .use(remarkWikiLinkPlugin)
      .use(remarkWikiLinkStringifyPlugin)
      .use(wikiLinkNodeView)
      .create();

    isReady = true;
    syncContent(props.content ?? '');
  } catch (error) {
    console.error('Failed to create Milkdown preview:', error);
  }
});

watch(
  () => props.content,
  (content) => {
    syncContent(content ?? '');
  },
);

onBeforeUnmount(() => {
  rootRef.value?.removeEventListener('click', handleClick);
  editor?.destroy();
});
</script>

<style>
.milkdown-preview-root .milkdown {
  @apply h-full w-full max-w-full;
}

.milkdown-preview-root .milkdown .editor,
.milkdown-preview-root .milkdown .ProseMirror {
  @apply min-h-full w-full max-w-full outline-none;
}

.milkdown-preview-root .wiki-link {
  @apply no-underline border-b transition-colors cursor-pointer;
  text-decoration: none !important;
}

.milkdown-preview-root .wiki-link-exists {
  @apply text-blue-600 border-blue-300 hover:bg-blue-50;
}

.dark .milkdown-preview-root .wiki-link-exists {
  @apply text-blue-400 border-blue-700 hover:bg-blue-900/20;
}

.milkdown-preview-root .wiki-link-missing {
  @apply text-gray-500 border-gray-300 border-dashed hover:bg-gray-50 cursor-default;
}

.dark .milkdown-preview-root .wiki-link-missing {
  @apply text-gray-500 border-gray-600 hover:bg-gray-800;
}
</style>
