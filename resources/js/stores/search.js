import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'

export const useSearchStore = defineStore('search', () => {
    const query = ref('')
    const results = ref([])
    const loading = ref(false)
    const history = ref(JSON.parse(localStorage.getItem('search_history') || '[]'))

    async function search(workspaceId, q, type = null) {
        if (! q || q.length < 2) {
            results.value = []
            return
        }
        loading.value = true
        try {
            const params = { q }
            if (type) params.type = type
            const { data } = await axios.get(`/api/v1/workspaces/${workspaceId}/search`, { params })
            results.value = data.data
            addToHistory(q)
        } finally {
            loading.value = false
        }
    }

    function addToHistory(q) {
        history.value = [q, ...history.value.filter(h => h !== q)].slice(0, 10)
        localStorage.setItem('search_history', JSON.stringify(history.value))
    }

    function clear() {
        query.value = ''
        results.value = []
    }

    return { query, results, loading, history, search, clear }
})
