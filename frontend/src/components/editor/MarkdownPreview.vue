<template>
  <div class="markdown-preview prose dark:prose-invert max-w-none p-4 overflow-auto h-full">
    <div v-html="renderedContent" @click="handleClick"></div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch, onMounted } from 'vue';
import { marked } from 'marked';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';
import { wikiLinksApi, type ResolvedWikiLink } from '@/api/wikilinks';

interface Props {
  content: string;
}

const props = defineProps<Props>();
const router = useRouter();
const toast = useToast();

const resolvedLinks = ref<Map<string, ResolvedWikiLink | ResolvedWikiLink[] | null>>(new Map());

interface ParsedWikiLink {
  raw: string;
  title: string;
  alias: string | null;
  placeholder: string;
}

const parseWikiLinks = (content: string): { content: string; links: ParsedWikiLink[] } => {
  const links: ParsedWikiLink[] = [];
  const pattern = /\[\[([^\]|]+)(?:\|([^\]]+))?\]\]/g;
  let index = 0;
  
  const processedContent = content.replace(pattern, (raw, title, alias) => {
    const placeholder = `__WIKILINK_${index}__`;
    links.push({
      raw,
      title: title.trim(),
      alias: alias ? alias.trim() : null,
      placeholder,
    });
    index++;
    return placeholder;
  });
  
  return { content: processedContent, links };
};

const resolveLinks = async (links: ParsedWikiLink[]) => {
  if (links.length === 0) return;
  
  const titles = [...new Set(links.map(l => l.title.toLowerCase()))];
  
  try {
    const response = await wikiLinksApi.resolveWikiLinks(titles);
    
    // Convert response to case-insensitive map
    const newMap = new Map<string, ResolvedWikiLink | ResolvedWikiLink[] | null>();
    for (const [title, value] of Object.entries(response)) {
      newMap.set(title.toLowerCase(), value);
    }
    
    resolvedLinks.value = newMap;
  } catch (error) {
    console.error('Failed to resolve wiki links:', error);
  }
};

const renderWikiLink = (link: ParsedWikiLink): string => {
  const displayText = link.alias || link.title;
  const lowerTitle = link.title.toLowerCase();
  const resolved = resolvedLinks.value.get(lowerTitle);
  
  if (resolved === null || resolved === undefined) {
    // Non-existent note - gray style (Notion-like)
    return `<a href="#" class="wiki-link wiki-link-missing" data-title="${link.title}" data-link-type="missing">
      <svg class="wiki-link-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
        <polyline points="14 2 14 8 20 8"></polyline>
      </svg>
      ${displayText}
    </a>`;
  } else if (Array.isArray(resolved)) {
    // Multiple notes - show as ambiguous
    return `<a href="#" class="wiki-link wiki-link-ambiguous" data-title="${link.title}" data-link-type="ambiguous">
      <svg class="wiki-link-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
        <polyline points="14 2 14 8 20 8"></polyline>
      </svg>
      ${displayText}
    </a>`;
  } else {
    // Existing note - blue link (Notion-like)
    return `<a href="#" class="wiki-link wiki-link-exists" data-note-id="${resolved.id}" data-link-type="exists">
      <svg class="wiki-link-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
        <polyline points="14 2 14 8 20 8"></polyline>
      </svg>
      ${displayText}
    </a>`;
  }
};

const renderedContent = computed(() => {
  try {
    // Parse wiki-links
    const { content: processedContent, links } = parseWikiLinks(props.content);
    
    // Parse markdown
    let html = marked.parse(processedContent) as string;
    
    // Replace placeholders with rendered wiki-links
    for (const link of links) {
      html = html.replace(link.placeholder, renderWikiLink(link));
    }
    
    return html;
  } catch (error) {
    console.error('Markdown parsing error:', error);
    return '<p>Ошибка отображения содержимого</p>';
  }
});

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

// Watch for content changes and resolve links
watch(() => props.content, async () => {
  const { links } = parseWikiLinks(props.content);
  await resolveLinks(links);
}, { immediate: true });

onMounted(async () => {
  const { links } = parseWikiLinks(props.content);
  await resolveLinks(links);
});
</script>

<style scoped>
.markdown-preview {
  @apply bg-white dark:bg-gray-800;
}

:deep(.wiki-link) {
  @apply inline-flex items-center gap-1 no-underline border-b transition-colors;
  text-decoration: none !important;
}

:deep(.wiki-link-icon) {
  @apply w-4 h-4 inline-block flex-shrink-0;
}

/* Existing links - Notion style (blue with underline) */
:deep(.wiki-link-exists) {
  @apply text-blue-600 border-blue-300 hover:bg-blue-50;
}

:deep(.dark .wiki-link-exists) {
  @apply text-blue-400 border-blue-700 hover:bg-blue-900/20;
}

/* Missing links - gray with dashed underline */
:deep(.wiki-link-missing) {
  @apply text-gray-500 border-gray-300 border-dashed hover:bg-gray-50;
}

:deep(.dark .wiki-link-missing) {
  @apply text-gray-500 border-gray-600 hover:bg-gray-800;
}

/* Ambiguous links - yellow/warning style */
:deep(.wiki-link-ambiguous) {
  @apply text-yellow-600 border-yellow-300 hover:bg-yellow-50;
}

:deep(.dark .wiki-link-ambiguous) {
  @apply text-yellow-500 border-yellow-700 hover:bg-yellow-900/20;
}
</style>
