<script lang="ts" setup>
import {Button, Input, Label, Modal, Spinner} from '@/Components/ui/index';
import {apiClient} from '@/lib/apiClient';
import type {ApiError, Tournament} from '@/types/api';
import {CheckCircleIcon, UserPlusIcon} from 'lucide-vue-next';
import {computed, ref, watch} from 'vue';
import {useLocale} from '@/composables/useLocale';

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

const checkEmail = async () => {
    if (!form.value.email || !form.value.email.includes('@')) {
        emailCheckResult.value = null;
        return;
    }

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
    }
};

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
        if (apiError.data?.errors) {
            errors.value = Object.entries(apiError.data.errors).reduce((acc, [key, messages]) => {
                acc[key] = Array.isArray(messages) ? messages[0] : messages;
                return acc;
            }, {} as Record<string, string>);
        } else {
            errors.value.general = apiError.message || t('An error occurred during registration.');
        }
    } finally {
        isSubmitting.value = false;
    }
};

const handleClose = () => {
    resetForm();
    emit('close');
};

// Watchers
watch(() => form.value.email, () => {
    checkEmail();
});

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
                </p>
            </div>

            <!-- General Error -->
            <div v-if="errors.general" class="rounded-md bg-red-50 p-4 dark:bg-red-900/20">
                <p class="text-sm text-red-800 dark:text-red-300">{{ errors.general }}</p>
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
                        <Input
                            id="email"
                            v-model="form.email"
                            :class="{'border-red-500': errors.email}"
                            :placeholder="t('email@example.com')"
                            type="email"
                        />
                        <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email }}</p>

                        <!-- Email Check Result -->
                        <div v-if="emailCheckResult && emailCheckResult.exists" class="mt-2">
                            <p :class="[
                                'text-sm',
                                emailCheckResult.can_apply ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400'
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
                    </div>

                    <div>
                        <Label for="password_confirmation">{{ t('Confirm Password') }} *</Label>
                        <Input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            :class="{'border-red-500': form.password && form.password_confirmation && form.password !== form.password_confirmation}"
                            :placeholder="t('Confirm your password')"
                            type="password"
                        />
                        <p v-if="form.password && form.password_confirmation && form.password !== form.password_confirmation"
                           class="mt-1 text-sm text-red-600">
                            {{ t('Passwords do not match') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Info Messages -->
            <div class="space-y-2 rounded-md bg-gray-50 p-4 text-sm text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                <p>• {{ t('After registration, your tournament application will be pending admin approval.') }}</p>
                <p>• {{ t('You will receive an email with your login credentials.') }}</p>
                <p v-if="tournament.entry_fee > 0">
                    • {{ t('Entry fee: :amount', {amount: tournament.entry_fee}) }}
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
