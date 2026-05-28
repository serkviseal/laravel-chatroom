<template>
  <div class="fixed inset-0 bg-black/80 flex items-center justify-center z-50" @click.self="$emit('close')">
    <div class="bg-gray-800 rounded-2xl max-w-2xl w-full mx-4 overflow-hidden">
      <div class="flex items-center justify-between px-4 py-3 border-b border-gray-700">
        <span class="font-semibold text-white truncate">{{ file.filename }}</span>
        <button @click="$emit('close')" class="text-gray-500 hover:text-white ml-4">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <!-- Preview area -->
      <div class="bg-gray-900 flex items-center justify-center" style="min-height: 300px; max-height: 60vh">
        <img v-if="file.is_image" :src="file.url" :alt="file.filename" class="max-w-full max-h-full object-contain" />
        <div v-else class="text-center p-8">
          <div class="text-6xl mb-4">📎</div>
          <div class="text-gray-400">{{ file.filename }}</div>
          <div class="text-gray-600 text-sm mt-1">{{ file.mime_type }}</div>
        </div>
      </div>

      <!-- Actions -->
      <div class="px-4 py-3 border-t border-gray-700 flex items-center justify-between">
        <div class="text-sm text-gray-400">
          {{ file.size_human }} · Uploaded by {{ file.uploader?.name }}
        </div>
        <div class="flex gap-2">
          <button
            @click="copyShareLink"
            class="text-sm bg-gray-700 hover:bg-gray-600 text-white px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1.5"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
            </svg>
            {{ copied ? 'Copied!' : 'Copy link' }}
          </button>
          <a :href="file.url" :download="file.filename" class="text-sm bg-indigo-600 hover:bg-indigo-500 text-white px-3 py-1.5 rounded-lg transition-colors">
            Download
          </a>
          <button @click="$emit('delete', file.id)" class="text-sm bg-red-900 hover:bg-red-800 text-red-300 px-3 py-1.5 rounded-lg transition-colors">
            Delete
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useFileStore } from '@/stores/files'

const props = defineProps({ file: { type: Object, required: true } })
defineEmits(['close', 'delete'])

const fileStore = useFileStore()
const copied = ref(false)

async function copyShareLink() {
    try {
        const link = await fileStore.shareFile(props.file.id)
        await navigator.clipboard.writeText(link.url)
        copied.value = true
        setTimeout(() => { copied.value = false }, 2000)
    } catch {}
}
</script>
