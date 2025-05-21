<!-- resources/js/Components/AddPlayerModal.vue -->
<script lang="ts" setup>
import {Button, Input, Label, Modal, Spinner} from '@/Components/ui';
import {computed, ref} from 'vue';
import {apiClient} from '@/lib/apiClient';
import type {ApiError} from '@/types/api';

const props = defineProps<{
    show: boolean;
    entityType: 'league' | 'match';
    entityId: number | string;
    leagueId?: number | string; // Only needed for matches
}>();

const emit = defineEmits(['close', 'added']);

// Form data
const firstName = ref('');
const lastName = ref('');
const email = ref('');
const phone = ref('');
const isProcessing = ref(false);
const errorMessage = ref<string | null>(null);
const successMessage = ref<string | null>(null);

// Validation
const isFormValid = computed(() => {
    return firstName.value.trim() !== '' &&
        lastName.value.trim() !== '' &&
        email.value.trim() !== '' &&
        isEmailValid.value &&
        phone.value.trim() !== '' &&
        isPhoneValid.value;
});

const isEmailValid = computed(() => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return email.value.trim() !== '' && emailRegex.test(email.value);
});

const isPhoneValid = computed(() => {
    // Basic phone validation - allows formats like: +1234567890, 123-456-7890, (123) 456-7890
    const phonePattern = /^(\+?\d{1,3}[-\s]?)?\(?(\d{3})\)?[-\s]?(\d{3})[-\s]?(\d{4})$/;
    return phone.value.trim() !== '' && phonePattern.test(phone.value);
});

// Reset form
const resetForm = () => {
    firstName.value = '';
    lastName.value = '';
    email.value = '';
    phone.value = '';
    errorMessage.value = null;
    successMessage.value = null;
};

// Close modal
const closeModal = () => {
    resetForm();
    emit('close');
};

// Generate a random password
const generatePassword = () => {
    const length = 10;
    const charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let password = '';
    for (let i = 0; i < length; i++) {
        const randomIndex = Math.floor(Math.random() * charset.length);
        password += charset[randomIndex];
    }
    return password;
};

// Add player
const addPlayer = async () => {
    if (!isFormValid.value || isProcessing.value) return;

    isProcessing.value = true;
    errorMessage.value = null;
    successMessage.value = null;

    try {
        const password = generatePassword();
        let endpoint = '';

        if (props.entityType === 'league') {
            endpoint = `/api/admin/${props.entityType}s/${props.entityId}/add-player`;
        } else if (props.entityType === 'match' && props.leagueId) {
            endpoint = `/api/admin/leagues/${props.leagueId}/multiplayer-games/${props.entityId}/add-player`;
        } else {
            throw new Error('Invalid entity type or missing leagueId');
        }

        const response = await apiClient(endpoint, {
            method: 'post',
            data: {
                firstname: firstName.value,
                lastname: lastName.value,
                email: email.value,
                phone: phone.value,
                password,
            },
        });

        successMessage.value = `Player ${firstName.value} ${lastName.value} added successfully with temporary password: ${password}`;

        // Clear form after success
        firstName.value = '';
        lastName.value = '';
        email.value = '';
        phone.value = '';

        // Notify parent component
        emit('added', response);
    } catch (err) {
        const error = err as ApiError;
        errorMessage.value = error.message || 'Failed to add player';
        if (error.data?.errors) {
            // Format validation errors
            errorMessage.value = Object.entries(error.data.errors)
                .map(([field, messages]) => `${field}: ${Array.isArray(messages) ? messages.join(', ') : messages}`)
                .join('; ');
        }
    } finally {
        isProcessing.value = false;
    }
};
</script>

<template>
    <Modal :show="show" maxWidth="md" @close="closeModal">
        <div class="p-6">
            <h2 class="text-lg font-medium">Add New Player to {{ entityType === 'league' ? 'League' : 'Match' }}</h2>

            <div v-if="errorMessage"
                 class="mt-4 rounded-md bg-red-50 p-4 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                {{ errorMessage }}
            </div>

            <div v-if="successMessage"
                 class="mt-4 rounded-md bg-green-50 p-4 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                {{ successMessage }}
            </div>

            <form class="mt-6 space-y-4" @submit.prevent="addPlayer">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="firstName">First Name</Label>
                        <Input
                            id="firstName"
                            v-model="firstName"
                            :disabled="isProcessing"
                            required
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="lastName">Last Name</Label>
                        <Input
                            id="lastName"
                            v-model="lastName"
                            :disabled="isProcessing"
                            required
                        />
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="email">Email</Label>
                    <Input
                        id="email_add"
                        v-model="email"
                        :class="{ 'border-red-300': !isEmailValid && email !== '' }"
                        :disabled="isProcessing"
                        required
                        type="email"
                    />
                    <p v-if="!isEmailValid && email !== ''" class="mt-1 text-sm text-red-600">
                        Please enter a valid email address
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="phone">Phone</Label>
                    <Input
                        id="phone_add"
                        v-model="phone"
                        :class="{ 'border-red-300': !isPhoneValid && phone !== '' }"
                        :disabled="isProcessing"
                        placeholder="e.g., +1234567890, 123-456-7890"
                        required
                        type="tel"
                    />
                    <p v-if="!isPhoneValid && phone !== ''" class="mt-1 text-sm text-red-600">
                        Please enter a valid phone number
                    </p>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <Button variant="outline" @click="closeModal">Cancel</Button>
                    <Button :disabled="!isFormValid || isProcessing" type="submit">
                        <Spinner v-if="isProcessing" class="mr-2 h-4 w-4"/>
                        Add Player
                    </Button>
                </div>
            </form>
        </div>
    </Modal>
</template>
