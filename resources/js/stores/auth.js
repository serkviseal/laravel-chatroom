import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null)
    const loading = ref(false)

    async function fetchUser() {
        try {
            const { data } = await axios.get('/api/v1/user')
            user.value = data.data
        } catch {
            user.value = null
        }
    }

    async function logout() {
        await axios.post('/logout')
        user.value = null
        window.location.href = '/'
    }

    async function updateAvatar(file) {
        loading.value = true
        const form = new FormData()
        form.append('avatar', file)
        const { data } = await axios.post('/api/v1/user', form)
        user.value = data.data
        loading.value = false
    }

    return { user, loading, fetchUser, logout, updateAvatar }
})
