<template>
  <div class="flex h-full bg-gray-900">
    <!-- Conversation list -->
    <div class="w-80 flex-shrink-0 border-r border-gray-700 flex flex-col">
      <!-- Header + filters -->
      <div class="p-4 border-b border-gray-700">
        <h2 class="text-white font-semibold text-lg mb-3">Inbox</h2>
        <div class="flex gap-1">
          <button
            v-for="tab in tabs"
            :key="tab.value"
            @click="activeTab = tab.value"
            :class="[
              'px-3 py-1 rounded-full text-xs font-medium transition-colors',
              activeTab === tab.value
                ? 'bg-brand-600 text-white'
                : 'text-gray-400 hover:text-white hover:bg-gray-700'
            ]"
          >{{ tab.label }}</button>
        </div>
      </div>

      <!-- Assignment filter -->
      <div class="px-4 py-2 border-b border-gray-700">
        <select
          v-model="assignedFilter"
          class="w-full bg-gray-800 border border-gray-600 text-gray-200 text-xs rounded-lg px-3 py-1.5 focus:ring-brand-500"
        >
          <option value="all">All conversations</option>
          <option value="me">Assigned to me</option>
          <option value="unassigned">Unassigned</option>
        </select>
      </div>

      <!-- Conversation list -->
      <div class="flex-1 overflow-y-auto">
        <div v-if="loading" class="flex items-center justify-center h-32">
          <div class="animate-spin h-6 w-6 border-2 border-brand-500 border-t-transparent rounded-full"></div>
        </div>

        <div v-else-if="conversations.length === 0" class="p-6 text-center text-gray-500 text-sm">
          No conversations
        </div>

        <button
          v-for="conv in conversations"
          :key="conv.id"
          @click="selectConversation(conv)"
          :class="[
            'w-full text-left px-4 py-3 border-b border-gray-800 hover:bg-gray-800/50 transition-colors',
            selected?.id === conv.id ? 'bg-gray-800 border-l-2 border-l-brand-500' : ''
          ]"
        >
          <div class="flex items-start gap-3">
            <div class="w-9 h-9 rounded-full bg-brand-600 flex items-center justify-center flex-shrink-0 text-sm font-medium text-white">
              {{ initials(conv.contact?.display_name || conv.contact?.phone) }}
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-100 truncate">
                  {{ conv.contact?.display_name || conv.contact?.phone }}
                </span>
                <span class="text-xs text-gray-500 ml-2 flex-shrink-0">
                  {{ timeAgo(conv.updated_at) }}
                </span>
              </div>
              <div class="flex items-center gap-2 mt-0.5">
                <span
                  :class="statusBadgeClass(conv.status)"
                  class="text-xs px-1.5 py-0.5 rounded-full font-medium"
                >{{ conv.status }}</span>
                <WindowTimer v-if="conv.window_expires_at" :expiresAt="conv.window_expires_at" />
              </div>
              <p v-if="conv.assigned_agent" class="text-xs text-gray-500 mt-0.5 truncate">
                → {{ conv.assigned_agent.name }}
              </p>
            </div>
          </div>
        </button>
      </div>
    </div>

    <!-- Active conversation -->
    <div class="flex-1 flex flex-col">
      <div v-if="!selected" class="flex-1 flex items-center justify-center">
        <div class="text-center">
          <div class="text-4xl mb-3">💬</div>
          <p class="text-gray-400">Select a conversation</p>
        </div>
      </div>

      <template v-else>
        <!-- Conversation header -->
        <div class="h-14 border-b border-gray-700 flex items-center justify-between px-4">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-brand-600 flex items-center justify-center text-sm font-medium text-white">
              {{ initials(selected.contact?.display_name || selected.contact?.phone) }}
            </div>
            <div>
              <p class="text-sm font-medium text-white">
                {{ selected.contact?.display_name || selected.contact?.phone }}
              </p>
              <p class="text-xs text-gray-400">
                {{ selected.contact?.phone }}
                <span v-if="selected.whatsapp_account?.display_name"> · via {{ selected.whatsapp_account?.display_name }}</span>
              </p>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <select
              v-model="selectedStatus"
              @change="updateStatus"
              class="bg-gray-800 border border-gray-600 text-gray-200 text-xs rounded-lg px-2 py-1"
            >
              <option value="open">Open</option>
              <option value="pending">Pending</option>
              <option value="snoozed">Snoozed</option>
              <option value="resolved">Resolved</option>
              <option value="spam">Spam</option>
            </select>

            <button
              @click="showSidebar = !showSidebar"
              class="p-1.5 rounded-lg hover:bg-gray-700 text-gray-400 hover:text-white"
              title="Contact info"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Chat + sidebar -->
        <div class="flex flex-1 overflow-hidden">
          <!-- Messages area — renders ChatRoom for the mirrored room -->
          <div class="flex-1 overflow-hidden">
            <ChatRoomEmbed v-if="selected.room_id" :roomId="selected.room_id" :conversationId="selected.id" />
          </div>

          <!-- Contact sidebar -->
          <ConversationSidebar
            v-if="showSidebar"
            :conversation="selected"
            @assign="handleAssign"
            @close="showSidebar = false"
          />
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import ChatRoomEmbed from './ChatRoomEmbed.vue'
import ConversationSidebar from './ConversationSidebar.vue'
import WindowTimer from './WindowTimer.vue'

const props = defineProps({ workspaceSlug: String })

const tabs = [
  { label: 'Open', value: 'open' },
  { label: 'Pending', value: 'pending' },
  { label: 'Resolved', value: 'resolved' },
  { label: 'All', value: 'all' },
]

const conversations   = ref([])
const selected        = ref(null)
const loading         = ref(false)
const activeTab       = ref('open')
const assignedFilter  = ref('all')
const showSidebar     = ref(false)
const selectedStatus  = ref('open')

watch([activeTab, assignedFilter], fetchConversations)
watch(selected, (conv) => { if (conv) selectedStatus.value = conv.status })

onMounted(fetchConversations)

async function fetchConversations() {
  if (!props.workspaceSlug) return
  loading.value = true
  try {
    const { data } = await axios.get(`/api/v1/workspaces/${props.workspaceSlug}/inbox`, {
      params: { status: activeTab.value, assigned_to: assignedFilter.value }
    })
    conversations.value = data.data ?? data
  } finally {
    loading.value = false
  }
}

function selectConversation(conv) {
  selected.value = conv
}

async function updateStatus() {
  if (!selected.value) return
  await axios.put(`/api/v1/conversations/${selected.value.id}/status`, {
    status: selectedStatus.value
  })
  fetchConversations()
}

async function handleAssign({ agentId, botId }) {
  if (!selected.value) return
  const { data } = await axios.put(`/api/v1/conversations/${selected.value.id}/assign`, {
    agent_id: agentId,
    bot_id: botId,
  })
  selected.value = { ...selected.value, ...data }
}

function initials(name) {
  if (!name) return '?'
  return name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase()
}

function timeAgo(ts) {
  if (!ts) return ''
  const diff = Date.now() - new Date(ts).getTime()
  const mins = Math.floor(diff / 60000)
  if (mins < 1) return 'just now'
  if (mins < 60) return `${mins}m`
  const hrs = Math.floor(mins / 60)
  if (hrs < 24) return `${hrs}h`
  return `${Math.floor(hrs / 24)}d`
}

function statusBadgeClass(status) {
  return {
    open:     'bg-green-900/50 text-green-300',
    pending:  'bg-yellow-900/50 text-yellow-300',
    snoozed:  'bg-blue-900/50 text-blue-300',
    resolved: 'bg-gray-700 text-gray-300',
    spam:     'bg-red-900/50 text-red-300',
  }[status] || 'bg-gray-700 text-gray-300'
}
</script>
