<template>
  <div class="fixed inset-0 bg-black/70 flex items-start justify-center z-50 pt-20" @click.self="$emit('close')">
    <div class="bg-gray-800 rounded-2xl w-full max-w-xl mx-4 shadow-2xl overflow-hidden">
      <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-700">
        <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input
          ref="inputEl"
          v-model="store.query"
          type="text"
          placeholder="Search messages, channels, files... (Cmd+K)"
          class="flex-1 bg-transparent text-white focus:outline-none text-sm"
          @input="doSearch"
        />
        <span class="text-xs text-gray-600 bg-gray-700 px-2 py-1 rounded">ESC</span>
      </div>

      <!-- Results -->
      <div class="max-h-96 overflow-y-auto">
        <div v-if="store.loading" class="p-4 text-center text-gray-500 text-sm">Searching...</div>

        <div v-else-if="store.results.length > 0">
          <div
            v-for="r in store.results"
            :key="`${r.type}-${r.id}`"
            class="flex items-start gap-3 px-4 py-3 hover:bg-gray-700 cursor-pointer border-b border-gray-700/50"
            @click="$emit('close')"
          >
            <div class="w-6 h-6 rounded flex items-center justify-center text-xs flex-shrink-0 mt-0.5"
              :class="{
                'bg-indigo-900 text-indigo-400': r.type === 'message',
                'bg-green-900 text-green-400': r.type === 'channel',
                'bg-orange-900 text-orange-400': r.type === 'file',
              }"
            >
              {{ r.type === 'message' ? '💬' : r.type === 'channel' ? '#' : '📎' }}
            </div>
            <div class="min-w-0 flex-1">
              <div class="text-sm text-white truncate">
                {{ r.type === 'message' ? r.body : r.type === 'channel' ? r.name : r.filename }}
              </div>
              <div class="text-xs text-gray-500 mt-0.5">
                {{ r.type === 'message' ? `#${r.channel} · ${r.user}` : r.type === 'file' ? `${r.uploader} · ${r.mime_type}` : r.description }}
              </div>
            </div>
          </div>
        </div>

        <div v-else-if="store.query.length >= 2" class="p-6 text-center text-gray-500 text-sm">No results for "{{ store.query }}"</div>

        <div v-else class="p-4">
          <div v-if="store.history.length > 0">
            <div class="text-xs text-gray-500 px-0 mb-2">Recent searches</div>
            <div
              v-for="h in store.history"
              :key="h"
              @click="store.query = h; doSearch()"
              class="text-sm text-gray-300 hover:text-white py-1.5 cursor-pointer flex items-center gap-2"
            >
              <svg class="w-4 h-4 text-gray-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              {{ h }}
            </div>
          </div>
          <div v-else class="text-center text-gray-600 text-sm">Start typing to search</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useSearchStore } from '@/stores/search'
import { useWorkspaceStore } from '@/stores/workspace'

defineEmits(['close'])

const store = useSearchStore()
const workspaceStore = useWorkspaceStore()
const inputEl = ref(null)
let debounce = null

onMounted(() => {
    inputEl.value?.focus()
    document.addEventListener('keydown', onKey)
})

onBeforeUnmount(() => {
    document.removeEventListener('keydown', onKey)
    store.clear()
})

function onKey(e) {
    if (e.key === 'Escape') emit('close')
}

function doSearch() {
    clearTimeout(debounce)
    debounce = setTimeout(() => {
        const wsId = workspaceStore.currentWorkspace?.id
        if (wsId) store.search(wsId, store.query)
    }, 300)
}
</script>
