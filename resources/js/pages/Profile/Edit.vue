<script lang="ts" setup>
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {Head, useForm} from '@inertiajs/vue3';
import {useAuth} from '@/composables/useAuth';
import {ref} from 'vue';
import {Button, Card, CardContent, CardDescription, CardHeader, CardTitle, Input, Label} from '@/Components/ui';
import InputError from '@/Components/InputError.vue';

defineOptions({ layout: AuthenticatedLayout });
defineProps<{ header?: string }>();

const { user } = useAuth();

// Form for profile information
const profileForm = useForm({
    firstname: user.value?.firstname || '',
    lastname: user.value?.lastname || '',
    email: user.value?.email || '',
    phone: user.value?.phone || '',
});

// Form for password update
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

// Form for account deletion
const deleteAccountForm = useForm({
    password: '',
});

const profileSuccess = ref(false);
const passwordSuccess = ref(false);
const isProcessingProfile = ref(false);
const isProcessingPassword = ref(false);
const isProcessingDelete = ref(false);
const showDeleteModal = ref(false);

// Update profile information
function updateProfile() {
    profileSuccess.value = false;
    isProcessingProfile.value = true;

    profileForm.patch(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            profileSuccess.value = true;
            setTimeout(() => {
                profileSuccess.value = false;
            }, 3000);
        },
        onFinish: () => {
            isProcessingProfile.value = false;
        },
    });
}

// Update password
function updatePassword() {
    passwordSuccess.value = false;
    isProcessingPassword.value = true;

    passwordForm.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
            passwordSuccess.value = true;
            setTimeout(() => {
                passwordSuccess.value = false;
            }, 3000);
        },
        onFinish: () => {
            isProcessingPassword.value = false;
        },
    });
}

// Delete account
function deleteAccount() {
    isProcessingDelete.value = true;

    deleteAccountForm.delete(route('profile.destroy'), {
        preserveScroll: true,
        onFinish: () => {
            isProcessingDelete.value = false;
            showDeleteModal.value = false;
        },
    });
}
</script>

<template>
    <Head title="Profile" />

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Update Profile Information -->
            <Card>
                <CardHeader>
                    <CardTitle>Profile Information</CardTitle>
                    <CardDescription>Update your account's profile information and email address.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="updateProfile" class="space-y-4">
                        <div v-if="profileSuccess" class="p-3 rounded-md bg-green-50 text-green-600 text-sm dark:bg-green-900/30 dark:text-green-400">
                            Profile updated successfully.
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="firstname">First Name</Label>
                                <Input
                                    id="firstname"
                                    v-model="profileForm.firstname"
                                    type="text"
                                    required
                                    :disabled="isProcessingProfile"
                                />
                                <InputError :message="profileForm.errors.firstname" />
                            </div>

                            <div class="space-y-2">
                                <Label for="lastname">Last Name</Label>
                                <Input
                                    id="lastname"
                                    v-model="profileForm.lastname"
                                    type="text"
                                    required
                                    :disabled="isProcessingProfile"
                                />
                                <InputError :message="profileForm.errors.lastname" />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="email">Email</Label>
                            <Input
                                id="email"
                                v-model="profileForm.email"
                                type="email"
                                required
                                :disabled="isProcessingProfile"
                            />
                            <InputError :message="profileForm.errors.email" />
                        </div>

                        <div class="space-y-2">
                            <Label for="phone">Phone Number</Label>
                            <Input
                                id="phone"
                                v-model="profileForm.phone"
                                type="tel"
                                required
                                :disabled="isProcessingProfile"
                            />
                            <InputError :message="profileForm.errors.phone" />
                        </div>

                        <div class="flex justify-end">
                            <Button :disabled="isProcessingProfile" type="submit">
                                <span v-if="isProcessingProfile">Saving...</span>
                                <span v-else>Save</span>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- Update Password -->
            <Card>
                <CardHeader>
                    <CardTitle>Update Password</CardTitle>
                    <CardDescription>Ensure your account is using a secure password.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="updatePassword" class="space-y-4">
                        <div v-if="passwordSuccess" class="p-3 rounded-md bg-green-50 text-green-600 text-sm dark:bg-green-900/30 dark:text-green-400">
                            Password updated successfully.
                        </div>

                        <div class="space-y-2">
                            <Label for="current_password">Current Password</Label>
                            <Input
                                id="current_password"
                                v-model="passwordForm.current_password"
                                type="password"
                                autocomplete="current-password"
                                required
                                :disabled="isProcessingPassword"
                            />
                            <InputError :message="passwordForm.errors.current_password" />
                        </div>

                        <div class="space-y-2">
                            <Label for="password">New Password</Label>
                            <Input
                                id="password"
                                v-model="passwordForm.password"
                                type="password"
                                autocomplete="new-password"
                                required
                                :disabled="isProcessingPassword"
                            />
                            <InputError :message="passwordForm.errors.password" />
                        </div>

                        <div class="space-y-2">
                            <Label for="password_confirmation">Confirm Password</Label>
                            <Input
                                id="password_confirmation"
                                v-model="passwordForm.password_confirmation"
                                type="password"
                                autocomplete="new-password"
                                required
                                :disabled="isProcessingPassword"
                            />
                            <InputError :message="passwordForm.errors.password_confirmation" />
                        </div>

                        <div class="flex justify-end">
                            <Button :disabled="isProcessingPassword" type="submit">
                                <span v-if="isProcessingPassword">Updating...</span>
                                <span v-else>Update Password</span>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- Delete Account -->
            <Card>
                <CardHeader>
                    <CardTitle>Delete Account</CardTitle>
                    <CardDescription class="text-red-500">
                        Once your account is deleted, all of its resources and data will be permanently deleted.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Button variant="destructive" @click="showDeleteModal = true">
                        Delete Account
                    </Button>
                </CardContent>
            </Card>
        </div>
    </div>

    <!-- Delete Account Modal -->
    <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg max-w-md w-full p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                Are you sure you want to delete your account?
            </h3>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.
            </p>

            <form @submit.prevent="deleteAccount">
                <div class="mb-4">
                    <Label for="delete_password">Password</Label>
                    <Input
                        id="delete_password"
                        v-model="deleteAccountForm.password"
                        type="password"
                        required
                        :disabled="isProcessingDelete"
                        class="mt-1"
                    />
                    <InputError :message="deleteAccountForm.errors.password" class="mt-1" />
                </div>

                <div class="flex justify-end space-x-3">
                    <Button
                        variant="outline"
                        :disabled="isProcessingDelete"
                        @click="showDeleteModal = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        variant="destructive"
                        :disabled="isProcessingDelete"
                        type="submit"
                    >
                        <span v-if="isProcessingDelete">Deleting...</span>
                        <span v-else>Delete Account</span>
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
