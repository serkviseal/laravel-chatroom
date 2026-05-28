<template>
  <div v-if="suggestions.length || loading" class="border-t border-gray-700/50 px-3 py-2 bg-gray-850">
    <div class="flex items-center gap-2 mb-2">
      <svg class="w-3.5 h-3.5 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
      </svg>
      <span class="text-xs font-medium text-brand-400">AI Suggestions</span>
      <button @click="fetch" :disabled="loading" class="ml-auto text-xs text-gray-500 hover:text-gray-300 transition-colors">
        {{ loading ? 'Generating…' : 'Refresh' }}
      </button>
    </div>
    <div v-if="loading" class="flex gap-1.5">
      <div v-for="i in 3" :key="i" class="h-7 flex-1 bg-gray-700/50 rounded-lg animate-pulse"></div>
    </div>
    <div v-else class="flex flex-col gap-1">
      <button
        v-for="(s, i) in suggestions"
        :key="i"
        @click="$emit('use', s.text)"
        class="text-left text-xs text-gray-300 bg-gray-800 hover:bg-gray-700 border border-gray-700 hover:border-brand-500/50 rounded-lg px-3 py-1.5 transition-colors truncate"
      >{{ s.text }}</button>
    </div>
  </div>

  <div v-else class="border-t border-gray-700/50 px-3 py-2 flex items-center gap-2">
    <button
      @click="fetch"
      class="flex items-center gap-1.5 text-xs text-gray-500 hover:text-brand-400 transition-colors"
    >
      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
      </svg>
      Suggest reply
    </button>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'

const props = defineProps({ conversationId: Number })
const emit  = defineEmits(['use'])

const suggestions = ref([])
const loading     = ref(false)

async function fetch() {
  loading.value = true
  suggestions.value = []
  try {
    const { data } = await axios.post(`/api/v1/conversations/${props.conversationId}/suggest-reply`)
    suggestions.value = data.suggestions ?? []
  } catch {
    suggestions.value = []
  } finally {
    loading.value = false
  }
}
</script>
