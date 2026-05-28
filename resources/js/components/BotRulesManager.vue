<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h3 class="text-white font-medium">Bot Rules & Automation</h3>
        <p class="text-xs text-gray-400 mt-0.5">Rules are evaluated in priority order when a WhatsApp message arrives.</p>
      </div>
      <button @click="openCreate" class="btn-primary text-sm">+ Add Rule</button>
    </div>

    <!-- Rules list -->
    <div v-if="loading" class="flex justify-center py-8">
      <div class="animate-spin h-6 w-6 border-2 border-brand-500 border-t-transparent rounded-full"></div>
    </div>

    <div v-else-if="rules.length === 0" class="text-center py-12 text-gray-500 text-sm bg-gray-800/40 rounded-xl border border-gray-700/50">
      No rules yet. Add a rule to automate responses.
    </div>

    <div v-else class="space-y-2">
      <div
        v-for="(rule, i) in rules"
        :key="rule.id"
        class="bg-gray-800/60 border border-gray-700/50 rounded-xl p-4 flex items-start gap-4"
      >
        <!-- Priority badge -->
        <div class="w-8 h-8 rounded-lg bg-gray-700 flex items-center justify-center text-xs font-mono text-gray-300 flex-shrink-0 mt-0.5">
          {{ rule.priority }}
        </div>

        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 mb-1">
            <span class="text-sm font-medium text-white">{{ rule.name }}</span>
            <span :class="rule.is_active ? 'bg-green-900/50 text-green-300' : 'bg-gray-700 text-gray-400'" class="text-xs px-1.5 py-0.5 rounded-full">
              {{ rule.is_active ? 'Active' : 'Paused' }}
            </span>
          </div>
          <div class="flex items-center gap-3 text-xs text-gray-400">
            <span class="flex items-center gap-1">
              <span class="text-brand-400 font-medium">IF</span>
              {{ triggerLabel(rule) }}
            </span>
            <span class="text-gray-600">→</span>
            <span class="flex items-center gap-1">
              <span class="text-green-400 font-medium">THEN</span>
              {{ actionLabel(rule) }}
            </span>
          </div>
        </div>

        <div class="flex items-center gap-1 flex-shrink-0">
          <button
            @click="toggleActive(rule)"
            class="p-1.5 rounded-lg hover:bg-gray-700 text-gray-400 hover:text-white"
            :title="rule.is_active ? 'Pause' : 'Activate'"
          >
            <svg v-if="rule.is_active" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </button>
          <button @click="editRule(rule)" class="p-1.5 rounded-lg hover:bg-gray-700 text-gray-400 hover:text-white">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
          </button>
          <button @click="deleteRule(rule)" class="p-1.5 rounded-lg hover:bg-red-900/40 text-gray-400 hover:text-red-400">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="editing" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm">
      <div class="bg-gray-900 border border-gray-700 rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6">
        <h4 class="text-white font-medium mb-4">{{ editing.id ? 'Edit Rule' : 'New Rule' }}</h4>

        <div class="space-y-3">
          <div>
            <label class="text-xs text-gray-400 mb-1 block">Rule Name</label>
            <input v-model="editing.name" placeholder="e.g. Keyword: hello" class="input-field" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-xs text-gray-400 mb-1 block">Trigger</label>
              <select v-model="editing.trigger_type" class="input-field">
                <option value="keyword">Keyword match</option>
                <option value="regex">Regex pattern</option>
                <option value="always">Always (first message)</option>
                <option value="first_contact">First contact ever</option>
                <option value="outside_hours">Outside business hours</option>
                <option value="unassigned_timeout">Unassigned timeout</option>
              </select>
            </div>
            <div>
              <label class="text-xs text-gray-400 mb-1 block">Action</label>
              <select v-model="editing.action_type" class="input-field">
                <option value="reply">Send reply</option>
                <option value="send_template">Send template</option>
                <option value="assign_agent">Assign agent</option>
                <option value="assign_bot">Assign bot</option>
                <option value="add_label">Add label</option>
                <option value="close">Close conversation</option>
                <option value="escalate_ai">Escalate to AI</option>
              </select>
            </div>
          </div>

          <div v-if="['keyword','regex'].includes(editing.trigger_type)">
            <label class="text-xs text-gray-400 mb-1 block">{{ editing.trigger_type === 'keyword' ? 'Keyword' : 'Regex pattern' }}</label>
            <input v-model="editing.trigger_value" :placeholder="editing.trigger_type === 'keyword' ? 'hello, hi, hey' : '^(help|support)'" class="input-field" />
          </div>

          <div v-if="editing.action_type === 'reply'">
            <label class="text-xs text-gray-400 mb-1 block">Reply text</label>
            <textarea v-model="replyText" rows="3" placeholder="Hi! Thanks for reaching out. We'll get back to you shortly." class="input-field resize-none"></textarea>
          </div>

          <div v-if="editing.action_type === 'add_label'">
            <label class="text-xs text-gray-400 mb-1 block">Label</label>
            <input v-model="labelValue" placeholder="vip, urgent, sales" class="input-field" />
          </div>

          <div class="flex items-center gap-3">
            <div class="flex items-center gap-2">
              <input type="checkbox" v-model="editing.is_active" id="rule-active" class="rounded border-gray-600" />
              <label for="rule-active" class="text-sm text-gray-300">Active</label>
            </div>
            <div class="flex items-center gap-2">
              <input type="checkbox" v-model="editing.stop_on_match" id="rule-stop" class="rounded border-gray-600" />
              <label for="rule-stop" class="text-sm text-gray-300">Stop on match</label>
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-2 mt-5">
          <button @click="editing = null" class="btn-secondary">Cancel</button>
          <button @click="saveRule" :disabled="saving" class="btn-primary">
            {{ saving ? 'Saving…' : 'Save Rule' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({ workspaceSlug: String })

const rules   = ref([])
const loading = ref(false)
const saving  = ref(false)
const editing = ref(null)

const replyText  = computed({
  get: () => editing.value?.action_value?.text ?? '',
  set: v => { if (editing.value) editing.value.action_value = { ...editing.value.action_value, text: v } }
})
const labelValue = computed({
  get: () => editing.value?.action_value?.label ?? '',
  set: v => { if (editing.value) editing.value.action_value = { ...editing.value.action_value, label: v } }
})

onMounted(fetchRules)

async function fetchRules() {
  loading.value = true
  try {
    const { data } = await axios.get(`/api/v1/workspaces/${props.workspaceSlug}/bot-rules`)
    rules.value = data
  } finally {
    loading.value = false
  }
}

function openCreate() {
  editing.value = {
    name: '', trigger_type: 'keyword', trigger_value: '',
    action_type: 'reply', action_value: {}, priority: null,
    is_active: true, stop_on_match: true,
  }
}

function editRule(rule) {
  editing.value = { ...rule, action_value: { ...(rule.action_value ?? {}) } }
}

async function saveRule() {
  saving.value = true
  try {
    const url = editing.value.id
      ? `/api/v1/workspaces/${props.workspaceSlug}/bot-rules/${editing.value.id}`
      : `/api/v1/workspaces/${props.workspaceSlug}/bot-rules`
    const method = editing.value.id ? 'put' : 'post'
    await axios[method](url, editing.value)
    editing.value = null
    await fetchRules()
  } finally {
    saving.value = false
  }
}

async function toggleActive(rule) {
  await axios.put(`/api/v1/workspaces/${props.workspaceSlug}/bot-rules/${rule.id}`, { is_active: !rule.is_active })
  fetchRules()
}

async function deleteRule(rule) {
  if (!confirm(`Delete rule "${rule.name}"?`)) return
  await axios.delete(`/api/v1/workspaces/${props.workspaceSlug}/bot-rules/${rule.id}`)
  fetchRules()
}

function triggerLabel(rule) {
  const map = {
    keyword: `keyword: "${rule.trigger_value}"`,
    regex: `regex: ${rule.trigger_value}`,
    always: 'every message',
    first_contact: 'first contact',
    outside_hours: 'outside business hours',
    unassigned_timeout: 'unassigned timeout',
  }
  return map[rule.trigger_type] ?? rule.trigger_type
}

function actionLabel(rule) {
  const map = {
    reply: `reply "${(rule.action_value?.text ?? '').slice(0, 40)}…"`,
    send_template: `send template: ${rule.action_value?.template_name ?? ''}`,
    assign_agent: 'assign to agent',
    assign_bot: 'assign to bot',
    add_label: `add label: ${rule.action_value?.label ?? ''}`,
    close: 'close conversation',
    escalate_ai: 'escalate to AI',
  }
  return map[rule.action_type] ?? rule.action_type
}
</script>
