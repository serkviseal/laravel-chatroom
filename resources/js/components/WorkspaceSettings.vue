<template>
  <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-800 bg-gray-900">
      <h2 class="font-bold text-white text-lg">Workspace Settings</h2>
      <p class="text-xs text-gray-500">{{ ws?.name }}</p>
    </div>

    <div class="flex-1 overflow-y-auto p-6 max-w-3xl">
      <!-- Tabs -->
      <div class="flex gap-1 bg-gray-800 rounded-lg p-1 mb-6 flex-wrap">
        <button
          v-for="tab in tabs"
          :key="tab.value"
          @click="activeTab = tab.value"
          class="px-4 py-1.5 text-sm rounded-md transition-colors"
          :class="activeTab === tab.value ? 'bg-brand-600 text-white' : 'text-gray-400 hover:text-white'"
        >{{ tab.label }}</button>
      </div>

      <!-- General -->
      <div v-if="activeTab === 'general'" class="space-y-4">
        <div>
          <label class="block text-sm text-gray-400 mb-1">Workspace Name</label>
          <input v-model="form.name" type="text" class="input-field" />
        </div>
        <div>
          <label class="block text-sm text-gray-400 mb-1">Description</label>
          <textarea v-model="form.description" rows="3" class="input-field resize-none" />
        </div>
        <button @click="save" class="btn-primary">Save Changes</button>
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
              class="bg-gray-700 text-white text-xs rounded-lg px-2 py-1 focus:outline-none focus:ring-1 focus:ring-brand-500"
            >
              <option value="admin">Admin</option>
              <option value="member">Member</option>
              <option value="guest">Guest</option>
            </select>
            <span v-else class="text-xs text-brand-400 bg-brand-900/50 px-2 py-1 rounded">Owner</span>
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
              :class="storageStats.percent_used > 90 ? 'bg-red-500' : storageStats.percent_used > 70 ? 'bg-yellow-500' : 'bg-brand-500'"
              :style="{ width: Math.min(storageStats.percent_used, 100) + '%' }"
            />
          </div>
          <p class="text-xs text-gray-500 mt-2">{{ storageStats.percent_used }}% used</p>
        </div>
      </div>

      <!-- WhatsApp Integration -->
      <div v-if="activeTab === 'whatsapp'" class="space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-white font-medium">WhatsApp Business Accounts</h3>
            <p class="text-xs text-gray-400 mt-0.5">Connect a WhatsApp Business number to receive and reply to customer messages.</p>
          </div>
          <button @click="showWizard = true" class="btn-primary text-sm">+ Connect Number</button>
        </div>

        <div v-if="waAccountsLoading" class="flex justify-center py-8">
          <div class="animate-spin h-6 w-6 border-2 border-brand-500 border-t-transparent rounded-full"></div>
        </div>

        <div v-else-if="waAccounts.length === 0" class="bg-gray-800/40 border border-gray-700/50 rounded-xl p-8 text-center">
          <div class="text-3xl mb-3">📱</div>
          <p class="text-gray-400 text-sm">No WhatsApp accounts connected yet.</p>
          <p class="text-gray-500 text-xs mt-1">Connect a WhatsApp Business number to start receiving customer messages in the Agent Inbox.</p>
        </div>

        <div v-else class="space-y-3">
          <div
            v-for="acc in waAccounts"
            :key="acc.id"
            class="bg-gray-800 border border-gray-700/50 rounded-xl p-4 flex items-center gap-4"
          >
            <div class="w-10 h-10 bg-green-900/40 rounded-full flex items-center justify-center flex-shrink-0">
              <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347"/>
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-white">{{ acc.display_name }}</p>
              <p class="text-xs text-gray-400">Phone ID: {{ acc.phone_number_id }}</p>
            </div>
            <span :class="acc.is_active ? 'bg-green-900/50 text-green-300' : 'bg-gray-700 text-gray-400'" class="text-xs px-2 py-0.5 rounded-full">
              {{ acc.is_active ? 'Active' : 'Inactive' }}
            </span>
            <button @click="deleteWaAccount(acc)" class="p-1.5 rounded-lg hover:bg-red-900/40 text-gray-400 hover:text-red-400">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
            </button>
          </div>
        </div>

        <WhatsAppSetupWizard
          v-if="showWizard"
          :workspaceSlug="ws?.slug"
          @close="showWizard = false"
          @created="onAccountCreated"
        />
      </div>

      <!-- Bot Rules -->
      <div v-if="activeTab === 'bot-rules'">
        <BotRulesManager :workspaceSlug="ws?.slug" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'
import { useWorkspaceStore } from '@/stores/workspace'
import WhatsAppSetupWizard from './WhatsAppSetupWizard.vue'
import BotRulesManager from './BotRulesManager.vue'

const workspaceStore = useWorkspaceStore()
const ws = computed(() => workspaceStore.currentWorkspace)

const activeTab   = ref('general')
const tabs = [
  { label: 'General', value: 'general' },
  { label: 'Members', value: 'members' },
  { label: 'Storage', value: 'storage' },
  { label: 'WhatsApp', value: 'whatsapp' },
  { label: 'Bot Rules', value: 'bot-rules' },
]

const members      = ref([])
const storageStats = ref({ used_mb: 0, quota_mb: 5120, percent_used: 0 })
const form         = ref({ name: '', description: '' })

// WhatsApp
const waAccounts       = ref([])
const waAccountsLoading = ref(false)
const showWizard       = ref(false)

watch(ws, (val) => {
  if (val) {
    form.value = { name: val.name, description: val.description || '' }
  }
}, { immediate: true })

watch(activeTab, (tab) => {
  if (tab === 'whatsapp') fetchWaAccounts()
})

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

async function fetchWaAccounts() {
  if (!ws.value) return
  waAccountsLoading.value = true
  try {
    const { data } = await axios.get(`/api/v1/workspaces/${ws.value.slug}/whatsapp-accounts`)
    waAccounts.value = data
  } finally {
    waAccountsLoading.value = false
  }
}

async function deleteWaAccount(acc) {
  if (!confirm(`Disconnect "${acc.display_name}"?`)) return
  await axios.delete(`/api/v1/workspaces/${ws.value.slug}/whatsapp-accounts/${acc.id}`)
  fetchWaAccounts()
}

function onAccountCreated(acc) {
  waAccounts.value.push(acc)
}
</script>
