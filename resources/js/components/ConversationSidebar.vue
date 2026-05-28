<template>
  <div class="w-72 border-l border-gray-700 flex flex-col bg-gray-900 flex-shrink-0 overflow-y-auto">
    <!-- Header -->
    <div class="h-14 flex items-center justify-between px-4 border-b border-gray-700">
      <span class="text-sm font-medium text-white">Contact Info</span>
      <button @click="$emit('close')" class="p-1 rounded hover:bg-gray-700 text-gray-400">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <!-- Contact card -->
    <div class="p-4 border-b border-gray-700">
      <div class="flex items-center gap-3 mb-3">
        <div class="w-12 h-12 rounded-full bg-brand-600 flex items-center justify-center text-white font-medium text-lg">
          {{ initials(contact?.display_name || contact?.phone) }}
        </div>
        <div>
          <p class="font-medium text-white">{{ contact?.display_name || contact?.phone }}</p>
          <p class="text-sm text-gray-400">{{ contact?.phone }}</p>
        </div>
      </div>
      <div class="grid grid-cols-2 gap-2 text-xs">
        <div v-if="contact?.locale" class="bg-gray-800 rounded-lg p-2">
          <p class="text-gray-500 mb-0.5">Language</p>
          <p class="text-gray-200">{{ contact.locale }}</p>
        </div>
        <div v-if="contact?.opt_in_at" class="bg-gray-800 rounded-lg p-2">
          <p class="text-gray-500 mb-0.5">Opted in</p>
          <p class="text-gray-200">{{ formatDate(contact.opt_in_at) }}</p>
        </div>
        <div class="bg-gray-800 rounded-lg p-2">
          <p class="text-gray-500 mb-0.5">Conversations</p>
          <p class="text-gray-200">{{ contactConvCount }}</p>
        </div>
      </div>
    </div>

    <!-- 24h window -->
    <div class="p-4 border-b border-gray-700">
      <p class="text-xs text-gray-500 mb-2 uppercase tracking-wider font-medium">Messaging Window</p>
      <div class="bg-gray-800 rounded-lg p-3">
        <div v-if="conversation.window_expires_at">
          <div class="flex items-center justify-between mb-1">
            <span class="text-xs text-gray-400">Time remaining</span>
            <WindowTimer :expiresAt="conversation.window_expires_at" />
          </div>
          <div class="w-full bg-gray-700 rounded-full h-1.5">
            <div
              class="h-1.5 rounded-full transition-all"
              :class="windowBarColor"
              :style="{ width: windowProgress + '%' }"
            ></div>
          </div>
        </div>
        <p v-else class="text-xs text-gray-400">No active window</p>
      </div>
    </div>

    <!-- Assignment -->
    <div class="p-4 border-b border-gray-700">
      <p class="text-xs text-gray-500 mb-2 uppercase tracking-wider font-medium">Assignment</p>
      <select
        v-model="agentId"
        @change="doAssign"
        class="w-full bg-gray-800 border border-gray-600 text-gray-200 text-sm rounded-lg px-3 py-2 mb-2"
      >
        <option :value="null">Unassigned</option>
        <option v-for="m in members" :key="m.id" :value="m.id">{{ m.name }}</option>
      </select>
    </div>

    <!-- Labels -->
    <div class="p-4 border-b border-gray-700">
      <p class="text-xs text-gray-500 mb-2 uppercase tracking-wider font-medium">Labels</p>
      <div class="flex flex-wrap gap-1 mb-2">
        <span
          v-for="label in labels"
          :key="label"
          class="bg-brand-900/40 text-brand-300 text-xs px-2 py-0.5 rounded-full flex items-center gap-1"
        >
          {{ label }}
          <button @click="removeLabel(label)" class="hover:text-brand-100 leading-none">×</button>
        </span>
      </div>
      <div class="flex gap-1">
        <input
          v-model="newLabel"
          @keydown.enter="addLabel"
          placeholder="Add label…"
          class="flex-1 bg-gray-800 border border-gray-600 text-gray-200 text-xs rounded px-2 py-1"
        />
        <button @click="addLabel" class="bg-brand-600 text-white text-xs px-2 py-1 rounded hover:bg-brand-700">+</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import WindowTimer from './WindowTimer.vue'

const props = defineProps({
  conversation: Object,
})
const emit = defineEmits(['assign', 'close'])

const members      = ref([])
const agentId      = ref(props.conversation?.assigned_agent_id ?? null)
const newLabel     = ref('')
const contactConvCount = ref(1)

const contact = computed(() => props.conversation?.contact)
const labels  = computed(() => props.conversation?.room?.contact_metadata?.labels ?? [])

const windowProgress = computed(() => {
  const exp = new Date(props.conversation?.window_expires_at ?? 0).getTime()
  const created = new Date(props.conversation?.created_at ?? 0).getTime()
  const total = 24 * 3600 * 1000
  const elapsed = Date.now() - (exp - total)
  return Math.max(0, Math.min(100, ((total - (exp - Date.now())) / total) * 100))
})

const windowBarColor = computed(() => {
  if (windowProgress.value < 50) return 'bg-green-500'
  if (windowProgress.value < 80) return 'bg-yellow-500'
  return 'bg-red-500'
})

onMounted(async () => {
  try {
    const workspaceSlug = window.location.pathname.split('/')[2]
    const { data } = await axios.get(`/api/v1/workspaces/${workspaceSlug}/members`)
    members.value = data.data ?? data
  } catch {}
})

function doAssign() {
  emit('assign', { agentId: agentId.value, botId: null })
}

function initials(name) {
  if (!name) return '?'
  return name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase()
}

function formatDate(ts) {
  return new Date(ts).toLocaleDateString()
}

async function addLabel() {
  const label = newLabel.value.trim()
  if (!label) return
  newLabel.value = ''
  const room = props.conversation?.room
  if (!room) return
  const metadata = { ...(room.contact_metadata ?? {}), labels: [...labels.value, label] }
  await axios.put(`/api/v1/rooms/${room.id}`, { contact_metadata: metadata }).catch(() => {})
}

async function removeLabel(label) {
  const room = props.conversation?.room
  if (!room) return
  const updated = labels.value.filter(l => l !== label)
  const metadata = { ...(room.contact_metadata ?? {}), labels: updated }
  await axios.put(`/api/v1/rooms/${room.id}`, { contact_metadata: metadata }).catch(() => {})
}
</script>
