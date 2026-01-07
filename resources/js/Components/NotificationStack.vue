<template>
    <div aria-live="assertive" class="pointer-events-none fixed inset-0 z-50 flex flex-col items-end px-4 py-6 space-y-4 sm:p-6">
        <TransitionGroup
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
            tag="div"
            class="flex flex-col items-end space-y-4 w-full max-w-sm ml-auto"
        >
            <div
                v-for="notification in notifications"
                :key="notification.id"
                class="pointer-events-auto w-full overflow-hidden rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5"
            >
                <div class="p-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <CheckCircleIcon
                                v-if="notification.type === 'success'"
                                class="h-6 w-6 text-green-400"
                                aria-hidden="true"
                            />
                            <ExclamationCircleIcon
                                v-else-if="notification.type === 'error'"
                                class="h-6 w-6 text-red-400"
                                aria-hidden="true"
                            />
                            <ExclamationTriangleIcon
                                v-else-if="notification.type === 'warning'"
                                class="h-6 w-6 text-yellow-400"
                                aria-hidden="true"
                            />
                            <InformationCircleIcon
                                v-else-if="notification.type === 'info'"
                                class="h-6 w-6 text-blue-400"
                                aria-hidden="true"
                            />
                            <ArrowPathIcon
                                v-else-if="notification.type === 'processing'"
                                class="h-6 w-6 text-blue-400 animate-spin"
                                aria-hidden="true"
                            />
                        </div>
                        <div class="ml-3 w-0 flex-1 pt-0.5">
                            <p
                                class="text-sm font-medium"
                                :class="{
                                    'text-green-900': notification.type === 'success',
                                    'text-red-900': notification.type === 'error',
                                    'text-yellow-900': notification.type === 'warning',
                                    'text-blue-900': notification.type === 'info' || notification.type === 'processing'
                                }"
                            >
                                {{ notification.title }}
                            </p>
                            <p class="mt-1 text-sm text-gray-500">{{ notification.message }}</p>
                        </div>
                        <div class="ml-4 flex flex-shrink-0">
                            <button
                                type="button"
                                @click="removeNotification(notification.id)"
                                class="inline-flex rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                <span class="sr-only">Cerrar</span>
                                <XMarkIcon class="h-5 w-5" aria-hidden="true" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </TransitionGroup>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted } from 'vue'
import {
    CheckCircleIcon,
    ExclamationCircleIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    ArrowPathIcon
} from '@heroicons/vue/24/outline'
import { XMarkIcon } from '@heroicons/vue/20/solid'
import { useNotifications } from '@/Composables/useNotifications'

const props = defineProps<{
    userId?: string
}>()

const { notifications, removeNotification, processing, updateNotification, error: notifyError } = useNotifications()

let channel: any = null
let currentProcessingId: string | null = null

onMounted(() => {
    // Subscribe to Laravel Echo for real-time evaluation processing updates
    if (window.Echo) {
        const channelName = props.userId
            ? `evaluation-processing.${props.userId}`
            : 'evaluation-processing'

        const echoInstance = props.userId && window.Echo.private
            ? window.Echo.private(channelName)
            : window.Echo.channel(channelName)

        channel = echoInstance.listen('.evaluation.status', (event: any) => {
            handleEvaluationStatusUpdate(event)
        })
    }
})

onUnmounted(() => {
    // Clean up Echo channel subscription
    if (channel && window.Echo) {
        try {
            const channelName = props.userId
                ? `evaluation-processing.${props.userId}`
                : 'evaluation-processing'
            
            window.Echo.leaveChannel(channelName)
        } catch (err) {
            console.error('Error leaving channel:', err)
        }
    }
})

/**
 * Handle evaluation processing status updates from Laravel Echo
 */
function handleEvaluationStatusUpdate(event: any): void {
    const { status, message, finished } = event

    if (status === 'running') {
        // Create or update processing notification
        if (!currentProcessingId) {
            currentProcessingId = processing('Procesando evaluación', message)
        } else {
            updateNotification(currentProcessingId, {
                message,
                type: 'processing'
            })
        }
    } else if (status === 'finished') {
        // Update to success notification
        if (currentProcessingId) {
            updateNotification(currentProcessingId, {
                type: 'success',
                title: 'Proceso completado',
                message,
                persistent: false,
                timeout: 5000
            })
            
            // Clear reference after timeout
            setTimeout(() => {
                currentProcessingId = null
            }, 5000)
        }
    } else if (status === 'error') {
        // Update to error notification
        if (currentProcessingId) {
            updateNotification(currentProcessingId, {
                type: 'error',
                title: 'Error en el proceso',
                message,
                persistent: true,
                timeout: 0
            })
            currentProcessingId = null
        } else {
            // Create new error notification if no processing notification exists
            notifyError('Error en el proceso', message, true)
        }
    }
}
</script>
