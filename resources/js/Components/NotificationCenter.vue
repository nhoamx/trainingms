<template>
    <div class="relative">
        <!-- Notification Button -->
        <button
            type="button"
            @click="togglePanel"
            class="relative p-2 text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 rounded-lg transition-colors"
            aria-label="Ver notificaciones"
            :aria-expanded="isOpen"
        >
            <BellIcon class="h-6 w-6" aria-hidden="true" />
            
            <!-- Unread Badge -->
            <span
                v-if="unreadCount > 0"
                class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full min-w-[1.25rem]"
                aria-label="`${unreadCount} notificaciones sin leer`"
            >
                {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
        </button>

        <!-- Notification Panel -->
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="isOpen"
                v-click-outside="closePanel"
                class="absolute right-0 mt-2 w-96 max-w-[calc(100vw-2rem)] bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 z-50"
                role="dialog"
                aria-modal="true"
                aria-labelledby="notifications-title"
            >
                <!-- Header -->
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">
                    <h2 id="notifications-title" class="text-lg font-semibold text-gray-900">
                        Notificaciones
                    </h2>
                    
                    <div class="flex items-center gap-2">
                        <button
                            v-if="notifications.length > 0"
                            @click="handleMarkAllAsRead"
                            class="text-sm text-blue-600 hover:text-blue-700 font-medium focus:outline-none focus:underline"
                            :disabled="unreadCount === 0"
                            :class="{ 'opacity-50 cursor-not-allowed': unreadCount === 0 }"
                        >
                            Marcar todas como leídas
                        </button>
                        
                        <button
                            @click="closePanel"
                            class="p-1 text-gray-400 hover:text-gray-500 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            aria-label="Cerrar panel"
                        >
                            <XMarkIcon class="h-5 w-5" aria-hidden="true" />
                        </button>
                    </div>
                </div>

                <!-- Notifications List -->
                <div class="max-h-[32rem] overflow-y-auto">
                    <!-- Loading State -->
                    <div v-if="isLoading && notifications.length === 0" class="p-8 text-center">
                        <div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-blue-600 border-r-transparent"></div>
                        <p class="mt-2 text-sm text-gray-500">Cargando notificaciones...</p>
                    </div>

                    <!-- Empty State -->
                    <div v-else-if="notifications.length === 0" class="p-8 text-center">
                        <BellSlashIcon class="mx-auto h-12 w-12 text-gray-400" aria-hidden="true" />
                        <p class="mt-2 text-sm font-medium text-gray-900">No hay notificaciones</p>
                        <p class="mt-1 text-sm text-gray-500">Aquí aparecerán tus notificaciones</p>
                    </div>

                    <!-- Notifications -->
                    <div v-else class="divide-y divide-gray-100">
                        <div
                            v-for="notification in notifications"
                            :key="notification.id"
                            class="group"
                        >
                            <div
                                class="relative flex gap-3 p-4 transition-colors"
                                :class="[
                                    notification.read_at 
                                        ? 'bg-white hover:bg-gray-50' 
                                        : 'bg-blue-50 hover:bg-blue-100'
                                ]"
                            >
                                <!-- Icon -->
                                <div class="flex-shrink-0">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-full"
                                        :class="[
                                            notification.read_at 
                                                ? 'bg-gray-100 text-gray-500' 
                                                : 'bg-blue-100 text-blue-600'
                                        ]"
                                    >
                                        <CheckCircleIcon class="h-6 w-6" aria-hidden="true" />
                                    </div>
                                </div>

                                <!-- Content -->
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ notification.data.title }}
                                    </p>
                                    <p class="mt-1 text-sm text-gray-600 line-clamp-2">
                                        {{ notification.data.message }}
                                    </p>
                                    <p class="mt-1 text-xs text-gray-500">
                                        {{ formatRelativeTime(notification.created_at) }}
                                    </p>
                                </div>

                                <!-- Actions -->
                                <div class="flex-shrink-0 flex items-start gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button
                                        v-if="!notification.read_at"
                                        @click="handleMarkAsRead(notification.id)"
                                        class="p-1.5 text-blue-600 hover:bg-blue-100 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        title="Marcar como leída"
                                        aria-label="Marcar como leída"
                                    >
                                        <CheckIcon class="h-4 w-4" aria-hidden="true" />
                                    </button>
                                    
                                    <button
                                        @click="handleDelete(notification.id)"
                                        class="p-1.5 text-red-600 hover:bg-red-100 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500"
                                        title="Eliminar"
                                        aria-label="Eliminar notificación"
                                    >
                                        <TrashIcon class="h-4 w-4" aria-hidden="true" />
                                    </button>
                                </div>

                                <!-- Unread indicator -->
                                <div
                                    v-if="!notification.read_at"
                                    class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-12 bg-blue-600 rounded-r"
                                    aria-hidden="true"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <!-- Load More -->
                    <div
                        v-if="pagination && pagination.current_page < pagination.last_page"
                        class="p-4 border-t border-gray-200"
                    >
                        <button
                            @click="handleLoadMore"
                            :disabled="isLoading"
                            class="w-full px-4 py-2 text-sm font-medium text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {{ isLoading ? 'Cargando...' : 'Cargar más' }}
                        </button>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div
                    v-if="notifications.length > 0"
                    class="px-4 py-3 border-t border-gray-200 bg-gray-50"
                >
                    <button
                        @click="handleDeleteAll"
                        class="w-full px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-red-500"
                    >
                        Eliminar todas
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useNotificationCenter } from '@/composables/useNotificationCenter'
import {
    BellIcon,
    BellSlashIcon,
    CheckCircleIcon,
    CheckIcon,
    TrashIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline'

// Get authenticated user
const page = usePage()
const user = computed(() => (page.props.auth as any)?.user)

// Use notification center composable
const {
    notifications,
    unreadCount,
    isLoading,
    isOpen,
    pagination,
    markAsRead,
    markAllAsRead,
    deleteNotification,
    deleteAll,
    togglePanel,
    closePanel,
    loadMore,
} = useNotificationCenter(user.value?.id)

/**
 * Handle mark notification as read
 */
const handleMarkAsRead = async (id: string): Promise<void> => {
    await markAsRead(id)
}

/**
 * Handle mark all as read
 */
const handleMarkAllAsRead = async (): Promise<void> => {
    if (unreadCount.value === 0) return
    await markAllAsRead()
}

/**
 * Handle delete notification
 */
const handleDelete = async (id: string): Promise<void> => {
    if (!confirm('¿Estás seguro de que deseas eliminar esta notificación?')) {
        return
    }
    await deleteNotification(id)
}

/**
 * Handle delete all notifications
 */
const handleDeleteAll = async (): Promise<void> => {
    if (!confirm('¿Estás seguro de que deseas eliminar todas las notificaciones?')) {
        return
    }
    await deleteAll()
    closePanel()
}

/**
 * Handle load more
 */
const handleLoadMore = async (): Promise<void> => {
    await loadMore()
}

/**
 * Format relative time (e.g., "hace 5 minutos")
 */
const formatRelativeTime = (dateString: string): string => {
    const date = new Date(dateString)
    const now = new Date()
    const diffMs = now.getTime() - date.getTime()
    const diffMins = Math.floor(diffMs / 60000)
    const diffHours = Math.floor(diffMins / 60)
    const diffDays = Math.floor(diffHours / 24)

    if (diffMins < 1) {
        return 'Hace un momento'
    } else if (diffMins < 60) {
        return `Hace ${diffMins} minuto${diffMins !== 1 ? 's' : ''}`
    } else if (diffHours < 24) {
        return `Hace ${diffHours} hora${diffHours !== 1 ? 's' : ''}`
    } else if (diffDays < 7) {
        return `Hace ${diffDays} día${diffDays !== 1 ? 's' : ''}`
    } else {
        return date.toLocaleDateString('es-MX', {
            day: 'numeric',
            month: 'short',
            year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined,
        })
    }
}

/**
 * Click outside directive
 */
interface ClickOutsideElement extends HTMLElement {
    clickOutsideEvent?: (event: Event) => void
}

const vClickOutside = {
    mounted(el: ClickOutsideElement, binding: any) {
        el.clickOutsideEvent = (event: Event) => {
            if (!(el === event.target || el.contains(event.target as Node))) {
                binding.value(event)
            }
        }
        document.addEventListener('click', el.clickOutsideEvent)
    },
    unmounted(el: ClickOutsideElement) {
        if (el.clickOutsideEvent) {
            document.removeEventListener('click', el.clickOutsideEvent)
        }
    },
}
</script>
