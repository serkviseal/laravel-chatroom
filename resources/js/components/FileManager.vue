<template>
  <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <!-- Header -->
    <div class="px-6 py-4 border-b border-gray-800 flex items-center justify-between bg-gray-900">
      <div>
        <h2 class="font-bold text-white text-lg">Files</h2>
        <p class="text-xs text-gray-500">{{ storageStats.used_mb }}MB / {{ storageStats.quota_mb }}MB used</p>
      </div>
      <div class="flex items-center gap-3">
        <!-- Filter tabs -->
        <div class="flex gap-1 bg-gray-800 rounded-lg p-1">
          <button
            v-for="f in filters"
            :key="f.value"
            @click="activeFilter = f.value; loadFiles()"
            class="px-3 py-1 text-xs rounded-md transition-colors"
            :class="activeFilter === f.value ? 'bg-indigo-600 text-white' : 'text-gray-400 hover:text-white'"
          >{{ f.label }}</button>
        </div>
        <!-- Upload button -->
        <label class="bg-indigo-600 hover:bg-indigo-500 text-white text-sm px-4 py-2 rounded-lg cursor-pointer transition-colors">
          Upload File
          <input type="file" class="hidden" multiple @change="handleUpload" />
        </label>
      </div>
    </div>

    <!-- Upload progress -->
    <div v-if="fileStore.uploading" class="px-6 py-2 bg-indigo-900/30 border-b border-indigo-800/50">
      <div class="flex items-center gap-3">
        <span class="text-sm text-indigo-300">Uploading... {{ fileStore.uploadProgress }}%</span>
        <div class="flex-1 bg-gray-700 rounded-full h-1.5">
          <div class="bg-indigo-500 h-1.5 rounded-full transition-all" :style="{ width: fileStore.uploadProgress + '%' }" />
        </div>
      </div>
    </div>

    <!-- File grid -->
    <div class="flex-1 overflow-y-auto p-6">
      <div v-if="fileStore.files.length === 0" class="flex flex-col items-center justify-center h-full text-gray-600">
        <div class="text-5xl mb-4">📁</div>
        <p class="text-lg font-medium text-gray-500">No files yet</p>
        <p class="text-sm text-gray-600 mt-1">Upload files to share with your team</p>
      </div>

      <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
        <div
          v-for="file in fileStore.files"
          :key="file.id"
          @click="preview = file"
          class="bg-gray-800 rounded-xl overflow-hidden cursor-pointer hover:bg-gray-700 transition-colors group"
        >
          <!-- Thumbnail or icon -->
          <div class="aspect-square bg-gray-900 flex items-center justify-center overflow-hidden">
            <img v-if="file.is_image && file.thumbnail_url" :src="file.thumbnail_url" class="w-full h-full object-cover" :alt="file.filename" />
            <div v-else class="text-4xl">{{ fileIcon(file.mime_type) }}</div>
          </div>
          <div class="p-2">
            <div class="text-xs text-white truncate font-medium">{{ file.filename }}</div>
            <div class="text-xs text-gray-500">{{ file.size_human }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- File Preview Modal -->
    <FilePreview v-if="preview" :file="preview" @close="preview = null" @delete="deleteFile" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useFileStore } from '@/stores/files'
import { useWorkspaceStore } from '@/stores/workspace'
import FilePreview from './FilePreview.vue'
import axios from 'axios'

const fileStore = useFileStore()
const workspaceStore = useWorkspaceStore()
const activeFilter = ref(null)
const preview = ref(null)
const storageStats = ref({ used_mb: 0, quota_mb: 5120 })

const filters = [
    { label: 'All', value: null },
    { label: 'Images', value: 'images' },
    { label: 'Docs', value: 'docs' },
    { label: 'Videos', value: 'videos' },
]

onMounted(async () => {
    await loadFiles()
    const wsId = workspaceStore.currentWorkspace?.id
    if (wsId) {
        const { data } = await axios.get(`/api/v1/workspaces/${wsId}/storage`)
        storageStats.value = data
    }
})

async function loadFiles() {
    const wsId = workspaceStore.currentWorkspace?.id
    if (!wsId) return
    await fileStore.fetchFiles(wsId, { type: activeFilter.value })
}

async function handleUpload(e) {
    const wsId = workspaceStore.currentWorkspace?.id
    if (!wsId) return
    for (const file of Array.from(e.target.files)) {
        await fileStore.uploadFile(wsId, file)
    }
    e.target.value = ''
}

async function deleteFile(fileId) {
    await fileStore.deleteFile(fileId)
    preview.value = null
}

function fileIcon(mime) {
    if (mime?.startsWith('image/')) return '🖼️'
    if (mime?.startsWith('video/')) return '🎥'
    if (mime?.startsWith('audio/')) return '🎵'
    if (mime?.includes('pdf')) return '📄'
    if (mime?.includes('spreadsheet') || mime?.includes('excel')) return '📊'
    if (mime?.includes('word') || mime?.includes('document')) return '📝'
    return '📎'
}
</script>
