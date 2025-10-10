import { reactive, readonly } from 'vue'
import { router } from '@inertiajs/vue3'

interface Notification {
    id: string
    type: 'success' | 'error' | 'info' | 'warning' | 'processing'
    title: string
    message: string
    timeout?: number
    persistent?: boolean
}

interface NotificationState {
    notifications: Notification[]
}

// Global reactive state for notifications
const state = reactive<NotificationState>({
    notifications: [],
})

let notificationCounter = 0

/**
 * Composable for managing persistent notifications
 * Notifications survive page navigation and can be manually dismissed
 */
export function useNotifications() {
    /**
     * Add a notification to the queue
     */
    const addNotification = (notification: Omit<Notification, 'id'>): string => {
        const id = `notification-${Date.now()}-${notificationCounter++}`
        
        const newNotification: Notification = {
            id,
            ...notification,
        }

        state.notifications.push(newNotification)

        // Auto-remove non-persistent notifications after timeout
        if (!notification.persistent && notification.timeout !== 0) {
            const timeout = notification.timeout ?? 5000
            setTimeout(() => {
                removeNotification(id)
            }, timeout)
        }

        return id
    }

    /**
     * Remove a notification by ID
     */
    const removeNotification = (id: string): void => {
        const index = state.notifications.findIndex(n => n.id === id)
        if (index > -1) {
            state.notifications.splice(index, 1)
        }
    }

    /**
     * Remove all notifications
     */
    const clearAll = (): void => {
        state.notifications = []
    }

    /**
     * Update an existing notification
     */
    const updateNotification = (id: string, updates: Partial<Omit<Notification, 'id'>>): void => {
        const notification = state.notifications.find(n => n.id === id)
        if (notification) {
            Object.assign(notification, updates)
        }
    }

    /**
     * Add a success notification
     */
    const success = (title: string, message: string, timeout?: number): string => {
        return addNotification({ type: 'success', title, message, timeout })
    }

    /**
     * Add an error notification
     */
    const error = (title: string, message: string, persistent = true): string => {
        return addNotification({ type: 'error', title, message, persistent, timeout: persistent ? 0 : 8000 })
    }

    /**
     * Add an info notification
     */
    const info = (title: string, message: string, timeout?: number): string => {
        return addNotification({ type: 'info', title, message, timeout })
    }

    /**
     * Add a warning notification
     */
    const warning = (title: string, message: string, timeout?: number): string => {
        return addNotification({ type: 'warning', title, message, timeout })
    }

    /**
     * Add a processing notification (persistent by default)
     */
    const processing = (title: string, message: string): string => {
        return addNotification({ type: 'processing', title, message, persistent: true, timeout: 0 })
    }

    return {
        notifications: readonly(state.notifications),
        addNotification,
        removeNotification,
        updateNotification,
        clearAll,
        success,
        error,
        info,
        warning,
        processing,
    }
}
