import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'

interface Notification {
    id: string
    type: string
    data: {
        type: string
        folio: string
        personal_id: string
        organization_id?: string
        work_center_id?: string
        organization_name?: string
        work_center_name?: string
        title: string
        message: string
        timestamp: string
    }
    read_at: string | null
    created_at: string
}

interface NotificationsPagination {
    current_page: number
    last_page: number
    per_page: number
    total: number
}

interface UseNotificationCenterReturn {
    notifications: import('vue').Ref<Notification[]>
    unreadCount: import('vue').ComputedRef<number>
    isLoading: import('vue').Ref<boolean>
    isOpen: import('vue').Ref<boolean>
    pagination: import('vue').Ref<NotificationsPagination | null>
    fetchNotifications: () => Promise<void>
    markAsRead: (id: string) => Promise<void>
    markAllAsRead: () => Promise<void>
    deleteNotification: (id: string) => Promise<void>
    deleteAll: () => Promise<void>
    togglePanel: () => void
    closePanel: () => void
    loadMore: () => Promise<void>
}

// Global state for notifications
const notifications = ref<Notification[]>([])
const unreadCountState = ref(0)
const isLoading = ref(false)
const isOpen = ref(false)
const pagination = ref<NotificationsPagination | null>(null)

let echoChannel: any = null
let isInitialized = false

/**
 * Composable for managing user notifications (database + real-time)
 */
export function useNotificationCenter(userId?: string): UseNotificationCenterReturn {
    
    const unreadCount = computed(() => unreadCountState.value)

    /**
     * Initialize Echo listener for real-time notifications
     */
    const initializeEcho = () => {
        if (isInitialized || !userId || !window.Echo) {
            return
        }

        const channelName = `App.Models.User.${userId}`

        try {
            echoChannel = window.Echo.private(channelName)
                .notification((notification: any) => {
                    // Add new notification to the top of the list
                    notifications.value.unshift({
                        id: notification.id,
                        type: notification.type,
                        data: notification,
                        read_at: null,
                        created_at: new Date().toISOString(),
                    })

                    // Increment unread count
                    unreadCountState.value++

                    // Show browser notification if permitted
                    if ('Notification' in window && Notification.permission === 'granted') {
                        new Notification(notification.title, {
                            body: notification.message,
                            icon: '/favicon.ico',
                            tag: notification.id,
                        })
                    }
                })

            isInitialized = true
        } catch (error) {
            console.error('Error initializing Echo for notifications:', error)
        }
    }

    /**
     * Fetch notifications from the server
     */
    const fetchNotifications = async (page = 1): Promise<void> => {
        if (isLoading.value) return

        isLoading.value = true

        try {
            const response = await axios.get('/notifications', {
                params: { page, per_page: 15 }
            })

            if (page === 1) {
                notifications.value = response.data.notifications
            } else {
                notifications.value.push(...response.data.notifications)
            }

            unreadCountState.value = response.data.unread_count
            pagination.value = response.data.pagination

        } catch (error) {
            console.error('Error fetching notifications:', error)
        } finally {
            isLoading.value = false
        }
    }

    /**
     * Load more notifications (pagination)
     */
    const loadMore = async (): Promise<void> => {
        if (!pagination.value || pagination.value.current_page >= pagination.value.last_page) {
            return
        }

        await fetchNotifications(pagination.value.current_page + 1)
    }

    /**
     * Mark a notification as read
     */
    const markAsRead = async (id: string): Promise<void> => {
        try {
            const response = await axios.post(`/notifications/${id}/read`)

            // Update local state
            const notification = notifications.value.find(n => n.id === id)
            if (notification && !notification.read_at) {
                notification.read_at = new Date().toISOString()
                unreadCountState.value = response.data.unread_count
            }

        } catch (error) {
            console.error('Error marking notification as read:', error)
        }
    }

    /**
     * Mark all notifications as read
     */
    const markAllAsRead = async (): Promise<void> => {
        try {
            await axios.post('/notifications/read-all')

            // Update local state
            notifications.value.forEach(notification => {
                if (!notification.read_at) {
                    notification.read_at = new Date().toISOString()
                }
            })

            unreadCountState.value = 0

        } catch (error) {
            console.error('Error marking all notifications as read:', error)
        }
    }

    /**
     * Delete a notification
     */
    const deleteNotification = async (id: string): Promise<void> => {
        try {
            const response = await axios.delete(`/notifications/${id}`)

            // Remove from local state
            const index = notifications.value.findIndex(n => n.id === id)
            if (index !== -1) {
                const wasUnread = !notifications.value[index].read_at
                notifications.value.splice(index, 1)
                
                if (wasUnread) {
                    unreadCountState.value = response.data.unread_count
                }
            }

        } catch (error) {
            console.error('Error deleting notification:', error)
        }
    }

    /**
     * Delete all notifications
     */
    const deleteAll = async (): Promise<void> => {
        try {
            await axios.delete('/notifications')

            // Clear local state
            notifications.value = []
            unreadCountState.value = 0

        } catch (error) {
            console.error('Error deleting all notifications:', error)
        }
    }

    /**
     * Toggle notification panel
     */
    const togglePanel = (): void => {
        isOpen.value = !isOpen.value

        if (isOpen.value && notifications.value.length === 0) {
            fetchNotifications()
        }
    }

    /**
     * Close notification panel
     */
    const closePanel = (): void => {
        isOpen.value = false
    }

    /**
     * Request browser notification permission
     */
    const requestNotificationPermission = (): void => {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission()
        }
    }

    // Initialize on mount
    onMounted(() => {
        initializeEcho()
        requestNotificationPermission()
    })

    // Cleanup on unmount
    onUnmounted(() => {
        if (echoChannel && window.Echo) {
            try {
                window.Echo.leaveChannel(`private-App.Models.User.${userId}`)
            } catch (error) {
                console.error('Error leaving notification channel:', error)
            }
        }
    })

    return {
        notifications,
        unreadCount,
        isLoading,
        isOpen,
        pagination,
        fetchNotifications,
        markAsRead,
        markAllAsRead,
        deleteNotification,
        deleteAll,
        togglePanel,
        closePanel,
        loadMore,
    }
}
