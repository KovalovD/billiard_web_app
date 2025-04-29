<script setup lang="ts">
import { ref } from 'vue';
import { useAuth } from '@/composables/useAuth';
import GuestLayout from '@/Layouts/GuestLayout.vue'; // Простой лейаут для гостевых страниц
import Button from '@/Components/ui/Button.vue'; // Предполагаем наличие UI компонентов
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import InputError from '@/Components/InputError.vue'; // Компонент для отображения ошибок полей

defineOptions({ layout: GuestLayout }); // Указываем лейаут для этой страницы

const { login, isLoading, error: authError } = useAuth(); // Получаем login и состояние из composable

const form = ref({
    email: '',
    password: '',
});

// Ошибка конкретно для этой формы (например, не прошла валидация на фронте)
const formError = ref<string | null>(null);

const submit = async () => {
    formError.value = null; // Сбрасываем локальную ошибку формы
    try {
        await login({ email: form.value.email, password: form.value.password });
        // Редирект произойдет внутри функции login() в useAuth
    } catch (err: any) {
        // Ошибка уже записана в authError в useAuth, но можно добавить специфичную логику
        console.error('Login component caught error:', err);
        // Можно проверить детали ошибки, если API возвращает ошибки валидации
        if (err.data?.errors) {
            // Здесь можно обработать ошибки валидации Laravel, если они есть в err.data.errors
            formError.value = "Validation errors occurred."; // Общее сообщение
            // Или передать ошибки в компонент InputError, если он это поддерживает
        } else {
            // Используем общую ошибку из useAuth
            formError.value = authError.value;
        }
    }
};
</script>

<template>
    <div class="flex items-center justify-center min-h-screen">
        <div class="w-full max-w-md p-8 space-y-6 bg-white rounded shadow-md">
            <h2 class="text-2xl font-bold text-center">Login to B2B League</h2>
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <Label for="email">Email</Label>
                    <Input
                        id="email"
                        type="email"
                        v-model="form.email"
                        required
                        autofocus
                        placeholder="your@email.com"
                        :disabled="isLoading"
                        class="mt-1 block w-full"
                    />
                </div>

                <div>
                    <Label for="password">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        v-model="form.password"
                        required
                        placeholder="Your password"
                        :disabled="isLoading"
                        class="mt-1 block w-full"
                    />
                </div>

                <div v-if="formError" class="text-sm text-red-600">
                    {{ formError }}
                </div>
                <div v-else-if="authError && !formError" class="text-sm text-red-600">
                    {{ authError }}
                </div>


                <div>
                    <Button type="submit" class="w-full justify-center" :disabled="isLoading">
                        <span v-if="isLoading">Logging in...</span>
                        <span v-else>Login</span>
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
