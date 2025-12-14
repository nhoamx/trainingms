<script setup>
import Dashboard from "../../Layouts/Dashboard.vue";
import { useForm } from '@inertiajs/vue3'
import { reactive } from "vue";
import FormInput from "../../Components/FormInput.vue";

const { user } = defineProps({
    user: {
        type: Object,
        required: true,
    },
})

const form = useForm({
    name: user.name,
    email: user.email,
    password: null,
    password_confirmation: null,
})

const errors = reactive({
    name: '',
    email: '',
    password: '',
})

const validate = () => {
    const newErrors = {}

    if (!form.name) {
        newErrors.name = 'El nombre es requerido.'
    }

    if (!form.email) {
        newErrors.email = 'El correo electrónico es requerido.'
    }

    if (form.email && !isValidEmail(form.email)) {
        newErrors.email = 'El correo electrónico debe ser válido.'
    }

    if (form.password && form.password.length < 8) {
        newErrors.password = 'La contraseña debe tener al menos 8 caracteres.'
    }

    if (form.password && form.password !== form.password_confirmation) {
        newErrors.password = 'Las contraseñas no coinciden.'
    }

    Object.assign(errors, newErrors)

    return Object.keys(newErrors).length === 0
}

const isValidEmail = (email) => {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    return re.test(email)
}

const submit = () => {
    if (!validate()) {
        return
    }

    form.post(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('password', 'password_confirmation')
        }
    })
}
</script>

<template>
    <Dashboard>
        <div class="max-w-2xl mx-auto py-8 px-4">
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-8">
                    <h1 class="text-3xl font-bold text-gray-900 mb-8">
                        Mi Perfil
                    </h1>

                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Name Field -->
                        <FormInput
                            v-model="form.name"
                            type="text"
                            label="Nombre"
                            name="name"
                            :error="errors.name || form.errors.name"
                            @update:modelValue="() => errors.name = ''"
                        />

                        <!-- Email Field -->
                        <FormInput
                            v-model="form.email"
                            type="email"
                            label="Correo Electrónico"
                            name="email"
                            :error="errors.email || form.errors.email"
                            @update:modelValue="() => errors.email = ''"
                        />

                        <!-- Password Field -->
                        <FormInput
                            v-model="form.password"
                            type="password"
                            label="Contraseña (dejar en blanco para no cambiar)"
                            name="password"
                            :error="errors.password || form.errors.password"
                            @update:modelValue="() => errors.password = ''"
                        />

                        <!-- Password Confirmation Field -->
                        <FormInput
                            v-if="form.password"
                            v-model="form.password_confirmation"
                            type="password"
                            label="Confirmar Contraseña"
                            name="password_confirmation"
                            :error="form.errors.password_confirmation"
                        />

                        <!-- Form Actions -->
                        <div class="flex gap-4 pt-4">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-6 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
                            >
                                {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                            </button>

                            <button
                                type="button"
                                @click="form.reset()"
                                class="px-6 py-2 bg-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-400 transition"
                            >
                                Cancelar
                            </button>
                        </div>
                    </form>

                    <!-- Success Message -->
                    <div
                        v-if="$page.props.flash?.success"
                        class="mt-6 p-4 bg-green-100 text-green-800 rounded-lg"
                    >
                        {{ $page.props.flash.success }}
                    </div>
                </div>
            </div>
        </div>
    </Dashboard>
</template>
