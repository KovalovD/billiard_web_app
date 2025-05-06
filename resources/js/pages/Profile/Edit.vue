//resources/js/pages/Profile/Edit.vue
<script lang="ts" setup>
import InputError from '@/Components/InputError.vue';
import {
    Button,
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
    Input,
    Label,
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
    Spinner,
} from '@/Components/ui';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {ApiError, City, Club, User} from '@/types/api';
import {Head, Link} from '@inertiajs/vue3';
import {onMounted, ref} from 'vue';

// Phone validation regex - matches formats like +1234567890, (123) 456-7890, 123-456-7890
const phonePattern = /^(\+?\d{1,3}[-\s]?)?\(?(\d{3})\)?[-\s]?(\d{3})[-\s]?(\d{4})$/;

defineOptions({ layout: AuthenticatedLayout });

const user = ref<User | null>(null);
const cities = ref<City[]>([]);
const clubs = ref<Club[]>([]);

const isLoadingUser = ref(true);
const isLoadingCities = ref(false);
const isLoadingClubs = ref(false);

const isPhoneValid = ref(true);

const profileForm = ref({
    firstname: '',
    lastname: '',
    email: '',
    phone: '',
    home_city_id: null as number | string | null,
    home_club_id: null as number | string | null,
});

const passwordForm = ref({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const deleteForm = ref({
    password: '',
});

const profileErrors = ref<Record<string, string[]>>({});
const passwordErrors = ref<Record<string, string[]>>({});
const deleteErrors = ref<Record<string, string[]>>({});

const profileSuccess = ref(false);
const passwordSuccess = ref(false);
const isProcessingProfile = ref(false);
const isProcessingPassword = ref(false);
const isProcessingDelete = ref(false);
const showDeleteModal = ref(false);

const loadUser = async () => {
    try {
        const response = await apiClient<User>('/api/auth/user');
        user.value = response;
        profileForm.value = {
            firstname: response.firstname,
            lastname: response.lastname,
            email: response.email,
            phone: response.phone || '',
            home_city_id: response.home_city?.id || null,
            home_club_id: response.home_club?.id || null,
        };

        // Validate the phone number from the data
        isPhoneValid.value = !profileForm.value.phone || phonePattern.test(profileForm.value.phone);

        // Load clubs if initial city is set
        if (response.home_city?.id) {
            await loadClubs(response.home_city.id);
        }
    } catch (error) {
        console.error('Failed to load user data:', error);
    } finally {
        isLoadingUser.value = false;
    }
};

const loadCities = async () => {
    isLoadingCities.value = true;
    try {
        cities.value = await apiClient<City[]>('/api/cities');
    } catch (error) {
        console.error('Failed to load cities:', error);
    } finally {
        isLoadingCities.value = false;
    }
};

const loadClubs = async (cityId?: number) => {
    isLoadingClubs.value = true;
    clubs.value = []; // Clear clubs
    try {
        if (cityId) {
            const params = `?city_id=${cityId}`;
            clubs.value = await apiClient<Club[]>(`/api/clubs${params}`);
        } else {
            clubs.value = [];
        }
    } catch (error) {
        console.error('Failed to load clubs:', error);
        clubs.value = [];
    } finally {
        isLoadingClubs.value = false;
    }
};

const onCityChange = (value: string | number) => {
    const cityId = value ? Number(value) : null;
    profileForm.value.home_city_id = cityId;
    profileForm.value.home_club_id = null;

    if (cityId) {
        loadClubs(cityId);
    } else {
        clubs.value = [];
    }
};

const onClubChange = (value: string | number) => {
    profileForm.value.home_club_id = value ? Number(value) : null;
};

const validatePhone = () => {
    isPhoneValid.value = !profileForm.value.phone || phonePattern.test(profileForm.value.phone);

    // Add validation error if phone is invalid
    if (!isPhoneValid.value) {
        profileErrors.value.phone = ['Please enter a valid phone number format (e.g., +1234567890, 123-456-7890)'];
    } else {
        // Remove phone error if it exists
        if (profileErrors.value.phone) {
            delete profileErrors.value.phone;
        }
    }

    return isPhoneValid.value;
};

const updateProfile = async () => {
    // Validate phone number first
    if (!validatePhone()) {
        return;
    }

    profileSuccess.value = false;
    profileErrors.value = {};
    isProcessingProfile.value = true;

    try {
        user.value = await apiClient<User>('/api/profile', {
            method: 'put',
            data: profileForm.value,
        });
        profileSuccess.value = true;
        setTimeout(() => {
            profileSuccess.value = false;
        }, 3000);
    } catch (error: any) {
        const apiError = error as ApiError;
        if (apiError.data?.errors) {
            profileErrors.value = apiError.data.errors;
        }
    } finally {
        isProcessingProfile.value = false;
    }
};

const updatePassword = async () => {
    passwordSuccess.value = false;
    passwordErrors.value = {};
    isProcessingPassword.value = true;

    try {
        await apiClient('/api/profile/password', {
            method: 'put',
            data: passwordForm.value,
        });

        passwordForm.value = {
            current_password: '',
            password: '',
            password_confirmation: '',
        };
        passwordSuccess.value = true;
        setTimeout(() => {
            passwordSuccess.value = false;
        }, 3000);
    } catch (error: any) {
        const apiError = error as ApiError;
        if (apiError.data?.errors) {
            passwordErrors.value = apiError.data.errors;
        }
    } finally {
        isProcessingPassword.value = false;
    }
};

const deleteAccount = async () => {
    deleteErrors.value = {};
    isProcessingDelete.value = true;

    try {
        await apiClient('/api/profile', {
            method: 'delete',
            data: deleteForm.value,
        });
        // The backend will logout the user
        window.location.href = '/login';
    } catch (error: any) {
        const apiError = error as ApiError;
        if (apiError.data?.errors) {
            deleteErrors.value = apiError.data.errors;
        }
    } finally {
        isProcessingDelete.value = false;
        showDeleteModal.value = false;
    }
};

onMounted(() => {
    loadUser();
    loadCities();
});
</script>

<template>
    <Head title="Profile Settings"/>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <!-- Profile Navigation -->
            <div class="mb-6 flex space-x-4">
                <Link :href="route('profile.edit')">
                    <Button class="bg-primary text-primary-foreground" variant="outline"> Edit Profile</Button>
                </Link>
                <Link :href="route('profile.stats')">
                    <Button class="bg-gray-100 dark:bg-gray-800" variant="outline"> Statistics</Button>
                </Link>
            </div>

            <!-- Loading state -->
            <div v-if="isLoadingUser" class="flex justify-center py-8">
                <Spinner class="text-primary h-8 w-8"/>
            </div>

            <!-- Update Profile Information -->
            <Card v-else>
                <CardHeader>
                    <CardTitle>Profile Information</CardTitle>
                    <CardDescription>Update your account's profile information and email address.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="updateProfile" class="space-y-4">
                        <div v-if="profileSuccess"
                             class="rounded-md bg-green-50 p-3 text-sm text-green-600 dark:bg-green-900/30 dark:text-green-400">
                            Profile updated successfully.
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="firstname">First Name</Label>
                                <Input id="firstname" v-model="profileForm.firstname" :disabled="isProcessingProfile" required
                                       type="text"/>
                                <InputError :message="profileErrors.firstname?.join(', ')"/>
                            </div>

                            <div class="space-y-2">
                                <Label for="lastname">Last Name</Label>
                                <Input id="lastname" v-model="profileForm.lastname" :disabled="isProcessingProfile" required
                                       type="text"/>
                                <InputError :message="profileErrors.lastname?.join(', ')"/>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="email">Email</Label>
                            <Input id="email" v-model="profileForm.email" :disabled="isProcessingProfile" required
                                   type="email"/>
                            <InputError :message="profileErrors.email?.join(', ')"/>
                        </div>

                        <div class="space-y-2">
                            <Label for="phone">Phone Number</Label>
                            <Input
                                id="phone"
                                v-model="profileForm.phone"
                                type="tel"
                                required
                                :disabled="isProcessingProfile"
                                :class="{ 'border-red-300 focus:border-red-300 focus:ring-red-300': !isPhoneValid }"
                                placeholder="e.g., (123) 456-7890"
                                @blur="validatePhone"
                                @input="validatePhone"
                            />
                            <InputError :message="profileErrors.phone?.join(', ')"/>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="home_city">Hometown</Label>
                                <Select
                                    :disabled="isProcessingProfile || isLoadingCities"
                                    :modelValue="profileForm.home_city_id"
                                    @update:modelValue="onCityChange"
                                >
                                    <SelectTrigger id="home_city">
                                        <SelectValue
                                            :placeholder="isLoadingCities ? 'Loading cities...' : user?.home_city?.name || 'Select city'"/>
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="">None</SelectItem>
                                        <SelectItem v-for="city in cities" :key="city.id" :value="city.id">
                                            {{ city.name }} ({{ city.country.name }})
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="profileErrors.home_city_id?.join(', ')"/>
                            </div>

                            <div class="space-y-2">
                                <Label for="home_club">Home Club</Label>
                                <Select
                                    :disabled="isProcessingProfile || isLoadingClubs || !profileForm.home_city_id"
                                    :modelValue="profileForm.home_club_id"
                                    @update:modelValue="onClubChange"
                                >
                                    <SelectTrigger id="home_club">
                                        <SelectValue
                                            :placeholder="isLoadingClubs ? 'Loading clubs...' : user?.home_club?.name || 'Select club'"/>
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="">None</SelectItem>
                                        <SelectItem v-for="club in clubs" :key="club.id" :value="club.id">
                                            {{ club.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="profileErrors.home_club_id?.join(', ')"/>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <Button :disabled="isProcessingProfile || !isPhoneValid" type="submit">
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
                        <div
                            v-if="passwordSuccess"
                            class="rounded-md bg-green-50 p-3 text-sm text-green-600 dark:bg-green-900/30 dark:text-green-400"
                        >
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
                            <InputError :message="passwordErrors.current_password?.join(', ')"/>
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
                            <InputError :message="passwordErrors.password?.join(', ')"/>
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
                            <InputError :message="passwordErrors.password_confirmation?.join(', ')"/>
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
                    <Button variant="destructive" @click="showDeleteModal = true"> Delete Account</Button>
                </CardContent>
            </Card>
        </div>
    </div>

    <!-- Delete Account Modal -->
    <div v-if="showDeleteModal" class="bg-opacity-50 fixed inset-0 z-50 flex items-center justify-center bg-black p-4">
        <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-lg dark:bg-gray-800">
            <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">Are you sure you want to delete your
                account?</h3>
            <p class="mb-4 text-gray-600 dark:text-gray-400">
                Once your account is deleted, all of its resources and data will be permanently deleted. Please enter
                your password to confirm you
                would like to permanently delete your account.
            </p>

            <form @submit.prevent="deleteAccount">
                <div class="mb-4">
                    <Label for="delete_password">Password</Label>
                    <Input id="delete_password" v-model="deleteForm.password" :disabled="isProcessingDelete" class="mt-1"
                           required type="password"/>
                    <InputError :message="deleteErrors.password?.join(', ')" class="mt-1"/>
                </div>

                <div class="flex justify-end space-x-3">
                    <Button :disabled="isProcessingDelete" variant="outline" @click="showDeleteModal = false"> Cancel
                    </Button>
                    <Button :disabled="isProcessingDelete" type="submit" variant="destructive">
                        <span v-if="isProcessingDelete">Deleting...</span>
                        <span v-else>Delete Account</span>
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
