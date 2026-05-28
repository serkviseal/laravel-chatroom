<template>
  <div class="flex flex-col items-center justify-center min-h-screen bg-gray-950">
    <div class="w-full max-w-md px-6">
      <h1 class="text-3xl font-bold text-white mb-2 text-center">Welcome to Chatroom</h1>
      <p class="text-gray-400 text-center mb-8">Select a workspace or create a new one</p>

      <div v-if="workspaceStore.loading" class="text-center text-gray-500">Loading...</div>

      <div v-else class="space-y-3 mb-6">
        <button
          v-for="ws in workspaceStore.sortedWorkspaces"
          :key="ws.id"
          @click="enter(ws)"
          class="w-full flex items-center gap-4 p-4 bg-gray-800 hover:bg-gray-700 rounded-xl transition"
        >
          <img :src="ws.avatar_url" :alt="ws.name" class="w-12 h-12 rounded-xl object-cover" />
          <div class="text-left flex-1">
            <div class="font-semibold text-white">{{ ws.name }}</div>
            <div class="text-sm text-gray-400">{{ ws.members_count }} members · {{ ws.role }}</div>
          </div>
          <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
          </svg>
        </button>
      </div>

      <button
        @click="showCreate = true"
        class="w-full flex items-center justify-center gap-2 p-4 border-2 border-dashed border-gray-700 hover:border-indigo-500 rounded-xl text-gray-400 hover:text-indigo-400 transition"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Create a new workspace
      </button>
    </div>

    <!-- Create Workspace Modal -->
    <div v-if="showCreate" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50">
      <div class="bg-gray-800 rounded-2xl p-6 w-full max-w-sm mx-4">
        <h2 class="text-xl font-bold text-white mb-4">Create Workspace</h2>
        <input
          v-model="newName"
          type="text"
          placeholder="Workspace name"
          class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 mb-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          @keyup.enter="create"
        />
        <textarea
          v-model="newDesc"
          placeholder="Description (optional)"
          rows="2"
          class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 mb-4 focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"
        />
        <div class="flex gap-3">
          <button @click="showCreate = false" class="flex-1 bg-gray-700 hover:bg-gray-600 text-white rounded-lg py-2">Cancel</button>
          <button @click="create" :disabled="!newName" class="flex-1 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white rounded-lg py-2 font-semibold">Create</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useWorkspaceStore } from '../stores/workspace'

const router = useRouter()
const workspaceStore = useWorkspaceStore()
const showCreate = ref(false)
const newName = ref('')
const newDesc = ref('')

onMounted(() => workspaceStore.fetchWorkspaces())

function enter(ws) {
  workspaceStore.setCurrentWorkspace(ws)
  router.push(`/w/${ws.slug}`)
}

async function create() {
  if (!newName.value.trim()) return
  const ws = await workspaceStore.createWorkspace({ name: newName.value, description: newDesc.value })
  showCreate.value = false
  newName.value = ''
  newDesc.value = ''
  enter(ws)
}
</script>
