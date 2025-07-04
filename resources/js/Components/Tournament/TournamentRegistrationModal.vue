<script lang="ts" setup>
import {Button, Input, Label, Modal, Spinner} from '@/Components/ui/index';
import {apiClient} from '@/lib/apiClient';
import type {ApiError, Tournament} from '@/types/api';
import {AlertCircleIcon, CheckCircleIcon, UserPlusIcon} from 'lucide-vue-next';
import {computed, ref, watch} from 'vue';
import {useLocale} from '@/composables/useLocale';
import {debounce} from 'lodash';

interface Props {
    show: boolean;
    tournament: Tournament;
}

interface Emits {
    (e: 'close'): void;
    (e: 'success'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();
const {t} = useLocale();

// Form state
const form = ref({
    firstname: '',
    lastname: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: ''
});

const isSubmitting = ref(false);
const isCheckingEmail = ref(false);
const errors = ref<Record<string, string>>({});
const emailCheckResult = ref<{
    exists: boolean;
    can_apply: boolean;
    has_application: boolean;
    message?: string;
} | null>(null);
const registrationSuccess = ref(false);
const successMessage = ref('');

// Computed
const isFormValid = computed(() => {
    return form.value.firstname.trim() !== '' &&
        form.value.lastname.trim() !== '' &&
        form.value.email.trim() !== '' &&
        form.value.phone.trim() !== '' &&
        form.value.password.length >= 8 &&
        form.value.password === form.value.password_confirmation;
});

const canSubmit = computed(() => {
    return isFormValid.value &&
        !isSubmitting.value &&
        !isCheckingEmail.value &&
        (!emailCheckResult.value || emailCheckResult.value.can_apply);
});

// Methods
const resetForm = () => {
    form.value = {
        firstname: '',
        lastname: '',
        email: '',
        phone: '',
        password: '',
        password_confirmation: ''
    };
    errors.value = {};
    emailCheckResult.value = null;
    registrationSuccess.value = false;
    successMessage.value = '';
};

// Debounced email check
const checkEmail = debounce(async () => {
    if (!form.value.email || !form.value.email.includes('@')) {
        emailCheckResult.value = null;
        isCheckingEmail.value = false;
        return;
    }

    isCheckingEmail.value = true;
    try {
        emailCheckResult.value = await apiClient<{
            exists: boolean;
            can_apply: boolean;
            has_application: boolean;
            message?: string;
        }>(`/api/tournaments/${props.tournament.id}/check-email/${encodeURIComponent(form.value.email)}`);
    } catch (error) {
        console.error('Email check failed:', error);
        emailCheckResult.value = null;
    } finally {
        isCheckingEmail.value = false;
    }
}, 500);

const handleSubmit = async () => {
    if (!canSubmit.value) return;

    isSubmitting.value = true;
    errors.value = {};

    try {
        const response = await apiClient(`/api/tournaments/${props.tournament.id}/register`, {
            method: 'post',
            data: form.value
        });

        if (response.success) {
            registrationSuccess.value = true;
            successMessage.value = response.message || t('Registration successful! Your application is pending approval.');

            // Clear form after 3 seconds and close
            setTimeout(() => {
                emit('success');
                handleClose();
            }, 3000);
        } else {
            errors.value.general = response.message || t('Registration failed. Please try again.');
        }
    } catch (error: any) {
        const apiError = error as ApiError;

        // Handle validation errors from the API
        if (apiError.status === 422) {
            // Check for errors in different possible locations
            const validationErrors =
                apiError.data?.error?.extras?.errors || // Format: { error: { extras: { errors: {} } } }
                apiError.data?.errors || // Format: { errors: {} }
                apiError.response?.data?.error?.extras?.errors || // Format in response.data
                apiError.response?.data?.errors || // Alternative format
                null;

            if (validationErrors) {
                // Map backend errors to form fields
                Object.entries(validationErrors).forEach(([key, messages]) => {
                    errors.value[key] = Array.isArray(messages) ? messages[0] : messages;
                });
            } else {
                // Fallback for validation errors without field details
                errors.value.general = apiError.data?.error?.message ||
                    apiError.data?.message ||
                    t('Validation failed. Please check your input.');
            }
        } else {
            // Handle other types of errors
            errors.value.general = apiError.data?.error?.message ||
                apiError.data?.message ||
                apiError.message ||
                t('An error occurred during registration.');
        }
    } finally {
        isSubmitting.value = false;
    }
};
const handleClose = () => {
    resetForm();
    emit('close');
};

// Clear field error when user starts typing
const clearFieldError = (field: string) => {
    if (errors.value[field]) {
        delete errors.value[field];
    }
};

// Watchers
watch(() => form.value.email, () => {
    clearFieldError('email');
    checkEmail();
});

watch(() => form.value.firstname, () => clearFieldError('firstname'));
watch(() => form.value.lastname, () => clearFieldError('lastname'));
watch(() => form.value.phone, () => clearFieldError('phone'));
watch(() => form.value.password, () => clearFieldError('password'));
watch(() => form.value.password_confirmation, () => clearFieldError('password_confirmation'));

watch(() => props.show, (show) => {
    if (show) {
        resetForm();
    }
});
</script>

<template>
    <Modal :show="show" :title="t('Tournament Registration')" size="large" @close="handleClose">
        <!-- Success State -->
        <div v-if="registrationSuccess" class="py-8 text-center">
            <CheckCircleIcon class="mx-auto h-16 w-16 text-green-500"/>
            <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ t('Registration Successful!') }}
            </h3>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                {{ successMessage }}
            </p>
        </div>

        <!-- Registration Form -->
        <div v-else class="space-y-6">
            <!-- Tournament Info -->
            <div class="rounded-lg bg-blue-50 p-4 dark:bg-blue-900/20">
                <h4 class="text-sm font-medium text-blue-800 dark:text-blue-300">
                    {{ t('Registering for:') }} {{ tournament.name }}
                </h4>
                <p class="mt-1 text-sm text-blue-600 dark:text-blue-400">
                    <span v-if="tournament.start_date">
                        {{ t('Starts:') }} {{ new Date(tournament.start_date).toLocaleDateString() }}
                    </span>
                    <span v-if="tournament.city" class="ml-4">
                        {{ t('Location:') }} {{ tournament.city.name }}
                    </span>
                    <span v-if="tournament.entry_fee > 0" class="ml-4">
                        {{ t('Entry fee:') }} {{ tournament.entry_fee }}₴
                    </span>
                </p>
            </div>

            <!-- General Error -->
            <div v-if="errors.general" class="rounded-md bg-red-50 p-4 dark:bg-red-900/20">
                <div class="flex">
                    <AlertCircleIcon class="h-5 w-5 text-red-400"/>
                    <p class="ml-3 text-sm text-red-800 dark:text-red-300">{{ errors.general }}</p>
                </div>
            </div>

            <!-- Personal Information -->
            <div>
                <h3 class="mb-4 text-lg font-medium">{{ t('Personal Information') }}</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <Label for="firstname">{{ t('First Name') }} *</Label>
                        <Input
                            id="firstname"
                            v-model="form.firstname"
                            :class="{'border-red-500': errors.firstname}"
                            :placeholder="t('First name')"
                            type="text"
                        />
                        <p v-if="errors.firstname" class="mt-1 text-sm text-red-600">{{ errors.firstname }}</p>
                    </div>

                    <div>
                        <Label for="lastname">{{ t('Last Name') }} *</Label>
                        <Input
                            id="lastname"
                            v-model="form.lastname"
                            :class="{'border-red-500': errors.lastname}"
                            :placeholder="t('Last name')"
                            type="text"
                        />
                        <p v-if="errors.lastname" class="mt-1 text-sm text-red-600">{{ errors.lastname }}</p>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div>
                <h3 class="mb-4 text-lg font-medium">{{ t('Contact Information') }}</h3>
                <div class="space-y-4">
                    <div>
                        <Label for="email">{{ t('Email') }} *</Label>
                        <div class="relative">
                            <Input
                                id="email"
                                v-model="form.email"
                                :class="{'border-red-500': errors.email}"
                                :placeholder="t('email@example.com')"
                                type="email"
                            />
                            <Spinner v-if="isCheckingEmail" class="absolute right-3 top-3 h-4 w-4 text-gray-400"/>
                        </div>
                        <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email }}</p>

                        <!-- Email Check Result -->
                        <div v-if="emailCheckResult && !errors.email" class="mt-2">
                            <p :class="[
                                'text-sm',
                                emailCheckResult.can_apply ? 'text-blue-600 dark:text-blue-400' : 'text-red-600 dark:text-red-400'
                            ]">
                                {{ emailCheckResult.message }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <Label for="phone">{{ t('Phone') }} *</Label>
                        <Input
                            id="phone"
                            v-model="form.phone"
                            :class="{'border-red-500': errors.phone}"
                            :placeholder="t('+380123456789')"
                            type="tel"
                        />
                        <p v-if="errors.phone" class="mt-1 text-sm text-red-600">{{ errors.phone }}</p>
                    </div>
                </div>
            </div>

            <!-- Password -->
            <div>
                <h3 class="mb-4 text-lg font-medium">{{ t('Create Password') }}</h3>
                <div class="space-y-4">
                    <div>
                        <Label for="password">{{ t('Password') }} *</Label>
                        <Input
                            id="password"
                            v-model="form.password"
                            :class="{'border-red-500': errors.password}"
                            :placeholder="t('Minimum 8 characters')"
                            type="password"
                        />
                        <p v-if="errors.password" class="mt-1 text-sm text-red-600">{{ errors.password }}</p>
                        <p v-else class="mt-1 text-xs text-gray-500">{{ t('Minimum 8 characters') }}</p>
                    </div>

                    <div>
                        <Label for="password_confirmation">{{ t('Confirm Password') }} *</Label>
                        <Input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            :class="{'border-red-500': errors.password_confirmation}"
                            :placeholder="t('Confirm your password')"
                            type="password"
                        />
                        <p v-if="errors.password_confirmation" class="mt-1 text-sm text-red-600">
                            {{ errors.password_confirmation }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Info Messages -->
            <div class="space-y-2 rounded-md bg-gray-50 p-4 text-sm text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                <p>• {{ t('After registration, your tournament application will be pending admin approval.') }}</p>
                <p>• {{ t('You will receive an email with your login credentials.') }}</p>
                <p v-if="tournament.application_deadline">
                    • {{ t('Application deadline:') }} {{
                        new Date(tournament.application_deadline).toLocaleDateString()
                    }}
                </p>
            </div>
        </div>

        <template v-if="!registrationSuccess" #footer>
            <Button variant="outline" @click="handleClose">
                {{ t('Cancel') }}
            </Button>
            <Button
                :disabled="!canSubmit"
                @click="handleSubmit"
            >
                <Spinner v-if="isSubmitting" class="mr-2 h-4 w-4"/>
                <UserPlusIcon v-else class="mr-2 h-4 w-4"/>
                {{ isSubmitting ? t('Registering...') : t('Register & Apply') }}
            </Button>
        </template>
    </Modal>
</template>
