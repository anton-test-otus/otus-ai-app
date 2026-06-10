<template>
  <div class="markdown-preview h-full overflow-auto bg-white dark:bg-gray-800 p-3 md:p-4 3xl:p-6">
    <div ref="rootRef" class="milkdown-preview-root markdown-prose min-h-full w-full max-w-full" />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import { Editor, editorViewOptionsCtx, rootCtx } from '@milkdown/core';
import { commonmark } from '@milkdown/preset-commonmark';
import { gfm } from '@milkdown/preset-gfm';
import { replaceAll } from '@milkdown/utils';
import { wikiLinksApi, type ResolvedWikiLink } from '@/api/wikilinks';
import { createWikiLinkPattern, parseWikiLinks, type ParsedWikiLink } from '@/lib/wikiLinks';

interface Props {
  content: string;
}

const props = defineProps<Props>();
const router = useRouter();
const toast = useToast();
const rootRef = ref<HTMLElement>();
let editor: Editor | null = null;
let isReady = false;

const resolvedLinks = ref<Map<string, ResolvedWikiLink | ResolvedWikiLink[] | null>>(new Map());

async function resolveLinks(links: ParsedWikiLink[]) {
  if (links.length === 0) {
    resolvedLinks.value = new Map();
    return;
  }

  const titles = [...new Set(links.map((l) => l.title.toLowerCase()))];

  try {
    const response = await wikiLinksApi.resolveWikiLinks(titles);
    const newMap = new Map<string, ResolvedWikiLink | ResolvedWikiLink[] | null>();
    for (const [title, value] of Object.entries(response)) {
      newMap.set(title.toLowerCase(), value);
    }
    resolvedLinks.value = newMap;
  } catch (error) {
    console.error('Failed to resolve wiki links:', error);
  }
}

function createWikiLinkAnchor(title: string, alias: string | null): HTMLAnchorElement {
  const anchor = document.createElement('a');
  anchor.href = '#';
  anchor.className = 'wiki-link';
  anchor.textContent = alias || title;

  const lowerTitle = title.toLowerCase();
  const resolved = resolvedLinks.value.get(lowerTitle);

  if (resolved === null || resolved === undefined) {
    anchor.classList.add('wiki-link-missing');
    anchor.dataset.title = title;
    anchor.dataset.linkType = 'missing';
  } else if (Array.isArray(resolved)) {
    anchor.classList.add('wiki-link-ambiguous');
    anchor.dataset.title = title;
    anchor.dataset.linkType = 'ambiguous';
  } else {
    anchor.classList.add('wiki-link-exists');
    anchor.dataset.noteId = resolved.id;
    anchor.dataset.linkType = 'exists';
  }

  return anchor;
}

function decorateWikiLinks() {
  const root = rootRef.value;
  if (!root) return;

  const textNodes: Text[] = [];
  const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);

  let node: Node | null;
  while ((node = walker.nextNode())) {
    if (node.nodeValue?.includes('[[')) {
      textNodes.push(node as Text);
    }
  }

  for (const textNode of textNodes) {
    const value = textNode.nodeValue ?? '';
    const pattern = createWikiLinkPattern();

    const fragment = document.createDocumentFragment();
    let lastIndex = 0;
    let match: RegExpExecArray | null;

    while ((match = pattern.exec(value)) !== null) {
      if (match.index > lastIndex) {
        fragment.appendChild(document.createTextNode(value.slice(lastIndex, match.index)));
      }

      fragment.appendChild(createWikiLinkAnchor(match[1].trim(), match[2]?.trim() || null));
      lastIndex = match.index + match[0].length;
    }

    if (lastIndex === 0) continue;

    if (lastIndex < value.length) {
      fragment.appendChild(document.createTextNode(value.slice(lastIndex)));
    }

    textNode.parentNode?.replaceChild(fragment, textNode);
  }
}

function syncContent(content: string) {
  if (!editor || !isReady) return;

  const links = parseWikiLinks(content ?? '');
  editor.action(replaceAll(content ?? ''));

  resolveLinks(links).then(async () => {
    await nextTick();
    requestAnimationFrame(() => decorateWikiLinks());
  });
}

const handleClick = (event: MouseEvent) => {
  const target = event.target as HTMLElement;
  const link = target.closest('.wiki-link') as HTMLAnchorElement;
  if (!link) return;

  event.preventDefault();

  const linkType = link.getAttribute('data-link-type');

  if (linkType === 'exists') {
    const noteId = link.getAttribute('data-note-id');
    if (noteId) {
      router.push({ name: 'note', params: { id: noteId }, query: { mode: 'preview' } });
    }
  } else if (linkType === 'missing') {
    const title = link.getAttribute('data-title');
    toast.add({
      severity: 'warn',
      summary: 'Заметка не найдена',
      detail: `Заметка "${title}" не существует`,
      life: 3000,
    });
  } else if (linkType === 'ambiguous') {
    const title = link.getAttribute('data-title');
    toast.add({
      severity: 'info',
      summary: 'Неоднозначная ссылка',
      detail: `Найдено несколько заметок с названием "${title}"`,
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
  @apply no-underline border-b transition-colors;
  text-decoration: none !important;
}

.milkdown-preview-root .wiki-link-exists {
  @apply text-blue-600 border-blue-300 hover:bg-blue-50;
}

.dark .milkdown-preview-root .wiki-link-exists {
  @apply text-blue-400 border-blue-700 hover:bg-blue-900/20;
}

.milkdown-preview-root .wiki-link-missing {
  @apply text-gray-500 border-gray-300 border-dashed hover:bg-gray-50;
}

.dark .milkdown-preview-root .wiki-link-missing {
  @apply text-gray-500 border-gray-600 hover:bg-gray-800;
}

.milkdown-preview-root .wiki-link-ambiguous {
  @apply text-yellow-600 border-yellow-300 hover:bg-yellow-50;
}

.dark .milkdown-preview-root .wiki-link-ambiguous {
  @apply text-yellow-500 border-yellow-700 hover:bg-yellow-900/20;
}

</style>
