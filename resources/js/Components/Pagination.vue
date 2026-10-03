<template>
  <div v-if="links && links.length > 3" class="flex flex-col sm:flex-row items-center justify-between gap-3 p-4 border-t border-base-200 bg-base-100 text-xs">
    <!-- Meta Info -->
    <div v-if="meta && meta.total !== undefined" class="text-base-content/70 font-medium">
      Showing <span class="font-bold text-base-content">{{ meta.from || 0 }}</span>
      to <span class="font-bold text-base-content">{{ meta.to || 0 }}</span>
      of <span class="font-bold text-base-content">{{ meta.total || 0 }}</span> entries
    </div>
    <div v-else class="text-base-content/70 font-medium">
      Page <span class="font-bold text-base-content">{{ currentPage }}</span> of <span class="font-bold text-base-content">{{ lastPage }}</span>
    </div>

    <!-- Pagination Controls -->
    <div class="join shadow-xs flex-wrap justify-center">
      <template v-for="(link, idx) in formattedLinks" :key="idx">
        <span
          v-if="link.isEllipsis || !link.url"
          :class="[
            'join-item btn btn-xs border-base-300',
            link.active ? 'btn-primary font-bold' : 'btn-disabled opacity-40 pointer-events-none bg-base-200'
          ]"
        >
          <span v-html="cleanLabel(link.label)"></span>
        </span>

        <Link
          v-else
          :href="link.url"
          :class="[
            'join-item btn btn-xs transition-colors',
            link.active ? 'btn-primary font-bold shadow-xs' : 'btn-outline border-base-300 hover:bg-base-200'
          ]"
          preserve-scroll
        >
          <span v-html="cleanLabel(link.label)"></span>
        </Link>
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  links: { type: Array, required: true, default: () => [] },
  meta: { type: Object, default: () => null },
  maxVisible: { type: Number, default: 7 },
});

const currentPage = computed(() => {
  if (props.meta && props.meta.current_page) return props.meta.current_page;
  const activeLink = props.links.find(l => l.active);
  if (activeLink && !isNaN(parseInt(activeLink.label))) {
    return parseInt(activeLink.label);
  }
  return 1;
});

const lastPage = computed(() => {
  if (props.meta && props.meta.last_page) return props.meta.last_page;
  const pageNumbers = props.links
    .map(l => parseInt(l.label))
    .filter(n => !isNaN(n));
  if (pageNumbers.length === 0) return 1;
  return Math.max(...pageNumbers);
});

// Format and filter links array to enforce ellipsis and maximum window size
const formattedLinks = computed(() => {
  if (!props.links || props.links.length === 0) return [];
  
  const prevLink = props.links[0];
  const nextLink = props.links[props.links.length - 1];
  
  const pageLinks = props.links.slice(1, props.links.length - 1);
  
  // If overall pages <= maxVisible, return standard list
  if (pageLinks.length <= props.maxVisible) {
    return props.links.map(link => ({
      ...link,
      isEllipsis: link.label.includes('...') || link.label === '...',
    }));
  }

  const current = currentPage.value;
  const total = lastPage.value;

  const delta = 1; // Number of pages to show around current page
  const range = [];

  for (let i = 1; i <= total; i++) {
    if (i === 1 || i === total || (i >= current - delta && i <= current + delta)) {
      range.push(i);
    }
  }

  const result = [prevLink];
  let lastPushed = 0;

  for (let pageNum of range) {
    if (lastPushed > 0) {
      if (pageNum - lastPushed === 2) {
        const middlePage = lastPushed + 1;
        const middleLink = pageLinks.find(l => parseInt(l.label) === middlePage) || {
          url: prevLink.url ? prevLink.url.replace(/page=\d+/, `page=${middlePage}`) : null,
          label: String(middlePage),
          active: middlePage === current,
        };
        result.push(middleLink);
      } else if (pageNum - lastPushed > 2) {
        result.push({
          url: null,
          label: '...',
          active: false,
          isEllipsis: true,
        });
      }
    }

    const existingLink = pageLinks.find(l => parseInt(l.label) === pageNum);
    if (existingLink) {
      result.push(existingLink);
    } else {
      result.push({
        url: prevLink.url ? prevLink.url.replace(/page=\d+/, `page=${pageNum}`) : null,
        label: String(pageNum),
        active: pageNum === current,
      });
    }
    lastPushed = pageNum;
  }

  result.push(nextLink);
  return result;
});

function cleanLabel(label) {
  if (!label) return '';
  return label
    .replace('&laquo; Previous', '‹ Prev')
    .replace('Next &raquo;', 'Next ›')
    .replace('&laquo;', '‹')
    .replace('&raquo;', '›');
}
</script>
