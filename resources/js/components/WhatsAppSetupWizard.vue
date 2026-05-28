<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm">
    <div class="bg-gray-900 border border-gray-700 rounded-2xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-gray-700 flex items-center justify-between">
        <div>
          <h3 class="text-white font-semibold">Connect WhatsApp Business</h3>
          <p class="text-xs text-gray-400 mt-0.5">Step {{ step }} of {{ totalSteps }}</p>
        </div>
        <button @click="$emit('close')" class="p-1.5 rounded-lg hover:bg-gray-700 text-gray-400">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <!-- Progress -->
      <div class="flex">
        <div
          v-for="s in totalSteps"
          :key="s"
          class="h-1 flex-1"
          :class="s <= step ? 'bg-brand-500' : 'bg-gray-700'"
        ></div>
      </div>

      <!-- Steps -->
      <div class="p-6">
        <!-- Step 1: Credentials -->
        <div v-if="step === 1">
          <h4 class="text-white font-medium mb-1">API Credentials</h4>
          <p class="text-gray-400 text-sm mb-4">Enter your Meta Business API credentials.</p>
          <div class="space-y-3">
            <div>
              <label class="text-xs text-gray-400 mb-1 block">Display Name</label>
              <input v-model="form.display_name" placeholder="e.g. Support Line" class="input-field" />
            </div>
            <div>
              <label class="text-xs text-gray-400 mb-1 block">Phone Number ID</label>
              <input v-model="form.phone_number_id" placeholder="From Meta Business Manager" class="input-field" />
            </div>
            <div>
              <label class="text-xs text-gray-400 mb-1 block">WhatsApp Business Account ID (WABA)</label>
              <input v-model="form.waba_id" placeholder="Your WABA ID" class="input-field" />
            </div>
            <div>
              <label class="text-xs text-gray-400 mb-1 block">Permanent Access Token</label>
              <input v-model="form.access_token" type="password" placeholder="EAAxxxxxxxx…" class="input-field" />
            </div>
          </div>
        </div>

        <!-- Step 2: Webhook -->
        <div v-if="step === 2">
          <h4 class="text-white font-medium mb-1">Configure Webhook</h4>
          <p class="text-gray-400 text-sm mb-4">Register this webhook URL in Meta Business Manager.</p>
          <div v-if="createdAccount" class="space-y-4">
            <div>
              <label class="text-xs text-gray-400 mb-1 block">Webhook URL (paste in Meta)</label>
              <div class="flex gap-2">
                <input
                  :value="webhookUrl"
                  readonly
                  class="input-field flex-1 font-mono text-xs"
                />
                <button @click="copy(webhookUrl)" class="btn-secondary text-xs px-3">Copy</button>
              </div>
            </div>
            <div>
              <label class="text-xs text-gray-400 mb-1 block">Verify Token (paste in Meta)</label>
              <div class="flex gap-2">
                <input
                  :value="createdAccount.verify_token"
                  readonly
                  class="input-field flex-1 font-mono text-xs"
                />
                <button @click="copy(createdAccount.verify_token)" class="btn-secondary text-xs px-3">Copy</button>
              </div>
            </div>
            <div>
              <label class="text-xs text-gray-400 mb-1 block">Webhook Secret (optional, for signature verification)</label>
              <input v-model="form.webhook_secret" placeholder="Set in Meta + paste here" class="input-field" />
            </div>
            <div class="bg-blue-900/20 border border-blue-700/50 rounded-lg p-3 text-xs text-blue-300">
              Subscribe to: <strong>messages</strong>, <strong>message_deliveries</strong>, <strong>message_reads</strong>
            </div>
          </div>
        </div>

        <!-- Step 3: Test -->
        <div v-if="step === 3">
          <h4 class="text-white font-medium mb-1">Test Connection</h4>
          <p class="text-gray-400 text-sm mb-4">Send a test message to verify everything works.</p>
          <div>
            <label class="text-xs text-gray-400 mb-1 block">Your WhatsApp number (with country code)</label>
            <input v-model="testPhone" placeholder="+1234567890" class="input-field mb-3" />
            <button @click="testConnection" :disabled="testing" class="btn-primary w-full">
              {{ testing ? 'Sending…' : 'Send Test Message' }}
            </button>
            <p v-if="testResult" :class="testResult.success ? 'text-green-400' : 'text-red-400'" class="text-sm mt-2 text-center">
              {{ testResult.success ? '✅ Message sent! Check your WhatsApp.' : '❌ Failed. Check your credentials.' }}
            </p>
          </div>
        </div>

        <!-- Step 4: Business Hours -->
        <div v-if="step === 4">
          <h4 class="text-white font-medium mb-1">Business Hours</h4>
          <p class="text-gray-400 text-sm mb-4">When should the bot auto-respond outside these hours?</p>
          <div class="space-y-2">
            <div
              v-for="day in days"
              :key="day.key"
              class="flex items-center gap-3"
            >
              <input
                type="checkbox"
                v-model="businessHours[day.key].enabled"
                :id="`day-${day.key}`"
                class="rounded border-gray-600"
              />
              <label :for="`day-${day.key}`" class="text-sm text-gray-300 w-24">{{ day.label }}</label>
              <template v-if="businessHours[day.key].enabled">
                <input v-model="businessHours[day.key].start" type="time" class="input-field w-28 text-xs" />
                <span class="text-gray-500 text-sm">–</span>
                <input v-model="businessHours[day.key].end" type="time" class="input-field w-28 text-xs" />
              </template>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 border-t border-gray-700 flex items-center justify-between">
        <button
          v-if="step > 1"
          @click="step--"
          class="btn-secondary"
        >Back</button>
        <div v-else></div>

        <button
          v-if="step < totalSteps"
          @click="nextStep"
          :disabled="!canProceed || saving"
          class="btn-primary"
        >{{ saving ? 'Saving…' : 'Continue' }}</button>
        <button
          v-else
          @click="finish"
          :disabled="saving"
          class="btn-primary"
        >{{ saving ? 'Saving…' : 'Finish Setup' }}</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'

const props = defineProps({ workspaceSlug: String })
const emit  = defineEmits(['close', 'created'])

const totalSteps    = 4
const step          = ref(1)
const saving        = ref(false)
const testing       = ref(false)
const testPhone     = ref('')
const testResult    = ref(null)
const createdAccount = ref(null)
const webhookUrl    = ref('')

const form = ref({
  display_name:   '',
  phone_number_id: '',
  waba_id:        '',
  access_token:   '',
  webhook_secret: '',
})

const days = [
  { key: 'monday', label: 'Monday' },
  { key: 'tuesday', label: 'Tuesday' },
  { key: 'wednesday', label: 'Wednesday' },
  { key: 'thursday', label: 'Thursday' },
  { key: 'friday', label: 'Friday' },
  { key: 'saturday', label: 'Saturday' },
  { key: 'sunday', label: 'Sunday' },
]

const businessHours = ref(Object.fromEntries(
  days.map(d => [d.key, { enabled: !['saturday','sunday'].includes(d.key), start: '09:00', end: '17:00' }])
))

const canProceed = computed(() => {
  if (step.value === 1) return form.value.display_name && form.value.phone_number_id && form.value.waba_id && form.value.access_token
  return true
})

async function nextStep() {
  if (step.value === 1) {
    await createAccount()
  } else {
    step.value++
  }
}

async function createAccount() {
  saving.value = true
  try {
    const { data } = await axios.post(`/api/v1/workspaces/${props.workspaceSlug}/whatsapp-accounts`, form.value)
    createdAccount.value = data.account
    webhookUrl.value = data.webhook_url
    step.value++
  } catch (e) {
    alert(e.response?.data?.message ?? 'Failed to create account')
  } finally {
    saving.value = false
  }
}

async function testConnection() {
  testing.value = true
  testResult.value = null
  try {
    const { data } = await axios.post(
      `/api/v1/workspaces/${props.workspaceSlug}/whatsapp-accounts/${createdAccount.value.id}/test`,
      { phone: testPhone.value }
    )
    testResult.value = data
  } catch {
    testResult.value = { success: false }
  } finally {
    testing.value = false
  }
}

async function finish() {
  saving.value = true
  try {
    await axios.put(
      `/api/v1/workspaces/${props.workspaceSlug}/whatsapp-accounts/${createdAccount.value.id}`,
      {
        webhook_secret: form.value.webhook_secret || undefined,
        business_hours: businessHours.value,
      }
    )
    emit('created', createdAccount.value)
    emit('close')
  } finally {
    saving.value = false
  }
}

function copy(text) {
  navigator.clipboard.writeText(text).catch(() => {})
}
</script>
