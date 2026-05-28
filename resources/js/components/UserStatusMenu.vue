<template>
  <div class="absolute bottom-14 left-2 w-60 bg-gray-800 border border-gray-700 rounded-xl shadow-2xl z-50 p-3">
    <div class="text-xs text-gray-500 mb-2 font-medium">Set Status</div>
    <div class="space-y-1 mb-3">
      <button
        v-for="s in statuses"
        :key="s.value"
        @click="setStatus(s.value)"
        class="w-full flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors text-sm"
        :class="current === s.value ? 'bg-gray-700 text-white' : 'text-gray-300'"
      >
        <span class="w-2 h-2 rounded-full flex-shrink-0" :class="s.dot" />
        {{ s.label }}
      </button>
    </div>
    <div class="border-t border-gray-700 pt-2 space-y-1.5">
      <input
        v-model="emoji"
        type="text"
        placeholder="Emoji"
        maxlength="4"
        class="w-full bg-gray-700 text-white text-sm rounded-lg px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-indigo-500"
      />
      <input
        v-model="text"
        type="text"
        placeholder="What's your status?"
        maxlength="100"
        class="w-full bg-gray-700 text-white text-sm rounded-lg px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-indigo-500"
      />
      <button @click="saveCustom" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white text-sm rounded-lg py-1.5">Save</button>
    </div>
    <div class="border-t border-gray-700 mt-2 pt-2">
      <button @click="logout" class="w-full text-sm text-gray-400 hover:text-white py-1.5 text-left px-1">Sign out</button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useWorkspaceStore } from '@/stores/workspace'
import axios from 'axios'

defineEmits(['close'])

const auth = useAuthStore()
const workspaceStore = useWorkspaceStore()
const current = ref('online')
const emoji = ref('')
const text = ref('')

const statuses = [
    { value: 'online', label: 'Active', dot: 'bg-green-500' },
    { value: 'away', label: 'Away', dot: 'bg-yellow-500' },
    { value: 'dnd', label: 'Do Not Disturb', dot: 'bg-red-500' },
    { value: 'offline', label: 'Appear Offline', dot: 'bg-gray-500' },
]

async function setStatus(status) {
    current.value = status
    await postStatus(status)
}

async function saveCustom() {
    await postStatus(current.value)
}

async function postStatus(status) {
    const wsId = workspaceStore.currentWorkspace?.id
    if (!wsId) return
    await axios.patch('/api/v1/user/status', {
        workspace_id: wsId,
        status,
        status_emoji: emoji.value || null,
        status_text: text.value || null,
    })
}

function logout() {
    auth.logout()
}
</script>
