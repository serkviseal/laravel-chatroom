import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'
import echo from '../echo'

export const useNotificationStore = defineStore('notifications', () => {
    const notifications = ref([])
    const unreadCount = ref(0)
    const nextPageUrl = ref(null)

    async function fetchNotifications() {
        const { data } = await axios.get('/api/v1/notifications')
        notifications.value = data.data
        unreadCount.value = data.unread_count
        nextPageUrl.value = data.next_page_url
    }

    async function fetchCount() {
        const { data } = await axios.get('/api/v1/notifications/count')
        unreadCount.value = data.unread_count
    }

    async function markRead(id = null) {
        await axios.post('/api/v1/notifications/read', id ? { id } : {})
        if (id) {
            const n = notifications.value.find(n => n.id === id)
            if (n) n.read_at = new Date().toISOString()
        } else {
            notifications.value.forEach(n => { n.read_at = new Date().toISOString() })
        }
        await fetchCount()
    }

    function subscribeForUser(userId) {
        echo.private(`App.Models.User.${userId}`)
            .notification(notification => {
                notifications.value.unshift(notification)
                unreadCount.value++
            })
    }

    return {
        notifications,
        unreadCount,
        nextPageUrl,
        fetchNotifications,
        fetchCount,
        markRead,
        subscribeForUser,
    }
})
