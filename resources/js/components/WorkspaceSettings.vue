<template>
  <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-800 bg-gray-900">
      <h2 class="font-bold text-white text-lg">Workspace Settings</h2>
      <p class="text-xs text-gray-500">{{ ws?.name }}</p>
    </div>

    <div class="flex-1 overflow-y-auto p-6 max-w-2xl">
      <!-- Tabs -->
      <div class="flex gap-1 bg-gray-800 rounded-lg p-1 mb-6 w-fit">
        <button
          v-for="tab in tabs"
          :key="tab"
          @click="activeTab = tab"
          class="px-4 py-1.5 text-sm rounded-md transition-colors capitalize"
          :class="activeTab === tab ? 'bg-indigo-600 text-white' : 'text-gray-400 hover:text-white'"
        >{{ tab }}</button>
      </div>

      <!-- General -->
      <div v-if="activeTab === 'general'" class="space-y-4">
        <div>
          <label class="block text-sm text-gray-400 mb-1">Workspace Name</label>
          <input v-model="form.name" type="text" class="w-full bg-gray-800 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" />
        </div>
        <div>
          <label class="block text-sm text-gray-400 mb-1">Description</label>
          <textarea v-model="form.description" rows="3" class="w-full bg-gray-800 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none" />
        </div>
        <button @click="save" class="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-lg text-sm font-semibold">Save Changes</button>
      </div>

      <!-- Members -->
      <div v-if="activeTab === 'members'" class="space-y-3">
        <div v-for="m in members" :key="m.id" class="flex items-center justify-between bg-gray-800 rounded-xl p-3">
          <div class="flex items-center gap-3">
            <img :src="m.avatar_url" class="w-9 h-9 rounded-full object-cover" />
            <div>
              <div class="font-medium text-white text-sm">{{ m.name }}</div>
              <div class="text-xs text-gray-500">{{ m.joined_at ? new Date(m.joined_at).toLocaleDateString() : '' }}</div>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <select
              v-if="m.role !== 'owner'"
              :value="m.role"
              @change="updateRole(m.id, $event.target.value)"
              class="bg-gray-700 text-white text-xs rounded-lg px-2 py-1 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            >
              <option value="admin">Admin</option>
              <option value="member">Member</option>
              <option value="guest">Guest</option>
            </select>
            <span v-else class="text-xs text-indigo-400 bg-indigo-900/50 px-2 py-1 rounded">Owner</span>
            <button v-if="m.role !== 'owner'" @click="remove(m.id)" class="text-red-500 hover:text-red-400 text-xs">Remove</button>
          </div>
        </div>
      </div>

      <!-- Storage -->
      <div v-if="activeTab === 'storage'" class="space-y-4">
        <div class="bg-gray-800 rounded-xl p-4">
          <div class="flex justify-between text-sm mb-2">
            <span class="text-gray-300">Storage Used</span>
            <span class="text-white font-medium">{{ storageStats.used_mb }}MB / {{ storageStats.quota_mb }}MB</span>
          </div>
          <div class="bg-gray-700 rounded-full h-2">
            <div
              class="h-2 rounded-full transition-all"
              :class="storageStats.percent_used > 90 ? 'bg-red-500' : storageStats.percent_used > 70 ? 'bg-yellow-500' : 'bg-indigo-500'"
              :style="{ width: Math.min(storageStats.percent_used, 100) + '%' }"
            />
          </div>
          <p class="text-xs text-gray-500 mt-2">{{ storageStats.percent_used }}% used</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { useWorkspaceStore } from '@/stores/workspace'

const workspaceStore = useWorkspaceStore()
const ws = computed(() => workspaceStore.currentWorkspace)
const activeTab = ref('general')
const tabs = ['general', 'members', 'storage']
const members = ref([])
const storageStats = ref({ used_mb: 0, quota_mb: 5120, percent_used: 0 })
const form = ref({ name: ws.value?.name || '', description: ws.value?.description || '' })

onMounted(async () => {
    if (!ws.value) return
    const { data: m } = await axios.get(`/api/v1/workspaces/${ws.value.id}/members`)
    members.value = m.data
    const { data: s } = await axios.get(`/api/v1/workspaces/${ws.value.id}/storage`)
    storageStats.value = s
})

async function save() {
    if (!ws.value) return
    await axios.put(`/api/v1/workspaces/${ws.value.id}`, form.value)
}

async function updateRole(userId, role) {
    if (!ws.value) return
    await workspaceStore.updateMemberRole(ws.value.id, userId, role)
    const m = members.value.find(m => m.id === userId)
    if (m) m.role = role
}

async function remove(userId) {
    if (!ws.value) return
    await workspaceStore.removeMember(ws.value.id, userId)
    members.value = members.value.filter(m => m.id !== userId)
}
</script>
