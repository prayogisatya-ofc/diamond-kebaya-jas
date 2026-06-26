import { ref, watch } from 'vue'

const STORAGE_KEY = 'diamond.sidebar.collapsed'

function readInitialState() {
    if (typeof window === 'undefined') {
        return false
    }

    return window.localStorage.getItem(STORAGE_KEY) === '1'
}

const collapsed = ref(readInitialState())

watch(collapsed, (value) => {
    if (typeof window === 'undefined') {
        return
    }

    window.localStorage.setItem(STORAGE_KEY, value ? '1' : '0')
})

export function useSidebar() {
    function toggleCollapsed() {
        collapsed.value = !collapsed.value
    }

    return {
        collapsed,
        toggleCollapsed,
    }
}
