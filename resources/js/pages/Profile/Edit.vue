//resources/js/pages/Profile/Edit.vue
<script lang="ts" setup>
import InputError from '@/Components/ui/form/InputError.vue';
import {
    Button,
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
    Input,
    Label,
    Modal,
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
import {useLocale} from '@/composables/useLocale';
import {
    ArrowLeftIcon,
    CheckCircleIcon,
    EditIcon,
    KeyIcon,
    MapPinIcon,
    PhoneIcon,
    Trash2Icon,
    UserIcon,
    UsersIcon,
} from 'lucide-vue-next';

// Phone validation regex - matches formats like +1234567890, (123) 456-7890, 123-456-7890
const phonePattern = /^(\+?\d{1,3}[-\s]?)?\(?(\d{3})\)?[-\s]?(\d{3})[-\s]?(\d{4})$/;

defineOptions({layout: AuthenticatedLayout});

const user = ref<User | null>(null);
const cities = ref<City[]>([]);
const clubs = ref<Club[]>([]);

const isLoadingUser = ref(true);
const isLoadingCities = ref(false);
const isLoadingClubs = ref(false);

const isPhoneValid = ref(true);

const {t} = useLocale();

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

const onCityChange = (value: string | number | null) => {
    const cityId = value ? Number(value) : null;
    profileForm.value.home_city_id = cityId;
    profileForm.value.home_club_id = null;

    if (cityId) {
        loadClubs(cityId);
    } else {
        clubs.value = [];
    }
};

const onClubChange = (value: string | number | null) => {
    profileForm.value.home_club_id = value ? Number(value) : null;
};

const validatePhone = () => {
    isPhoneValid.value = !profileForm.value.phone || phonePattern.test(profileForm.value.phone);

    // Add validation error if phone is invalid
    if (!isPhoneValid.value) {
        profileErrors.value.phone = [t('Please enter a valid phone number format (e.g., +1234567890, 123-456-7890)')];
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
    <Head :title="t('Profile Settings')"/>

    <div class="py-6 sm:py-8 lg:py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Header with navigation -->
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <Link :href="route('dashboard')">
                        <Button variant="outline" size="sm">
                            <ArrowLeftIcon class="mr-2 h-4 w-4"/>
                            <span class="hidden sm:inline">{{ t('Back to Dashboard') }}</span>
                            <span class="sm:hidden">{{ t('Back') }}</span>
                        </Button>
                    </Link>
                </div>

                <!-- Profile Navigation -->
                <div class="flex space-x-2">
                    <Link :href="route('profile.edit')">
                        <Button class="bg-indigo-600 text-white hover:bg-indigo-700" variant="outline">
                            <EditIcon class="mr-2 h-4 w-4"/>
                            <span class="hidden sm:inline">{{ t('Edit Profile') }}</span>
                            <span class="sm:hidden">{{ t('Edit') }}</span>
                        </Button>
                    </Link>
                    <Link :href="route('profile.stats')">
                        <Button class="bg-gray-100 dark:bg-gray-800" variant="outline">
                            <UsersIcon class="mr-2 h-4 w-4"/>
                            <span class="hidden sm:inline">{{ t('Statistics') }}</span>
                            <span class="sm:hidden">{{ t('Stats') }}</span>
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Loading state -->
            <div v-if="isLoadingUser" class="flex justify-center items-center py-12">
                <div class="text-center">
                    <Spinner class="mx-auto h-8 w-8 text-indigo-600"/>
                    <p class="mt-2 text-gray-500">{{ t('Loading profile information...') }}</p>
                </div>
            </div>

            <!-- Profile Content -->
            <div v-else class="space-y-8">
                <!-- Profile Information Card -->
                <Card class="shadow-lg overflow-hidden">
                    <div
                        class="bg-gradient-to-r from-gray-50 to-indigo-50 dark:from-gray-800 dark:to-gray-700 p-6 sm:p-8">
                        <div class="flex items-center gap-3 mb-4">
                            <div
                                class="h-12 w-12 rounded-full bg-indigo-600 flex items-center justify-center shadow-md">
                                <UserIcon class="h-6 w-6 text-white"/>
                            </div>
                            <div>
                                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">
                                    {{ t('Profile Information') }}
                                </h1>
                                <p class="text-gray-600 dark:text-gray-400 mt-1">
                                    {{ t("Update your account's profile information and email address.") }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <CardContent class="p-6 sm:p-8">
                        <form class="space-y-6" @submit.prevent="updateProfile">
                            <!-- Success message -->
                            <div v-if="profileSuccess"
                                 class="rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4">
                                <div class="flex items-center">
                                    <CheckCircleIcon class="h-5 w-5 text-green-600 dark:text-green-400 mr-2"/>
                                    <p class="text-green-800 dark:text-green-300 font-medium">
                                        {{ t('Profile updated successfully.') }}
                                    </p>
                                </div>
                            </div>

                            <!-- Personal Information -->
                            <div class="space-y-6">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <Label for="firstname"
                                               class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {{ t('First Name') }}
                                        </Label>
                                        <Input
                                            id="firstname"
                                            v-model="profileForm.firstname"
                                            :disabled="isProcessingProfile"
                                            class="h-11"
                                            required
                                            type="text"
                                        />
                                        <InputError :message="profileErrors.firstname?.join(', ')"/>
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="lastname"
                                               class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {{ t('Last Name') }}
                                        </Label>
                                        <Input
                                            id="lastname"
                                            v-model="profileForm.lastname"
                                            :disabled="isProcessingProfile"
                                            class="h-11"
                                            required
                                            type="text"
                                        />
                                        <InputError :message="profileErrors.lastname?.join(', ')"/>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <Label for="email" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ t('Email Address') }}
                                    </Label>
                                    <Input
                                        id="email"
                                        v-model="profileForm.email"
                                        :disabled="isProcessingProfile"
                                        class="h-11"
                                        required
                                        type="email"
                                    />
                                    <InputError :message="profileErrors.email?.join(', ')"/>
                                </div>

                                <div class="space-y-2">
                                    <Label for="phone" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        <PhoneIcon class="inline h-4 w-4 mr-1"/>
                                        {{ t('Phone Number') }}
                                    </Label>
                                    <Input
                                        id="phone"
                                        v-model="profileForm.phone"
                                        :class="{ 'border-red-300 focus:border-red-300 focus:ring-red-300': !isPhoneValid }"
                                        :disabled="isProcessingProfile"
                                        :placeholder="t('e.g., (123) 456-7890')"
                                        class="h-11"
                                        required
                                        type="tel"
                                        @blur="validatePhone"
                                        @input="validatePhone"
                                    />
                                    <InputError :message="profileErrors.phone?.join(', ')"/>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <Label for="home_city"
                                               class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            <MapPinIcon class="inline h-4 w-4 mr-1"/>
                                            {{ t('Hometown') }}
                                        </Label>
                                        <Select
                                            :disabled="isProcessingProfile || isLoadingCities"
                                            :modelValue="profileForm.home_city_id"
                                            @update:modelValue="onCityChange"
                                        >
                                            <SelectTrigger id="home_city" class="h-11">
                                                <SelectValue
                                                    :placeholder="isLoadingCities ? t('Loading cities...') : user?.home_city?.name || t('Select city')"/>
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="">{{ t('None') }}</SelectItem>
                                                <SelectItem v-for="city in cities" :key="city.id" :value="city.id">
                                                    {{ city.name }} ({{ city.country.name }})
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError :message="profileErrors.home_city_id?.join(', ')"/>
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="home_club"
                                               class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            <UsersIcon class="inline h-4 w-4 mr-1"/>
                                            {{ t('Home Club') }}
                                        </Label>
                                        <Select
                                            :disabled="isProcessingProfile || isLoadingClubs || !profileForm.home_city_id"
                                            :modelValue="profileForm.home_club_id"
                                            @update:modelValue="onClubChange"
                                        >
                                            <SelectTrigger id="home_club" class="h-11">
                                                <SelectValue
                                                    :placeholder="isLoadingClubs ? t('Loading clubs...') : user?.home_club?.name || t('Select club')"/>
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="">{{ t('None') }}</SelectItem>
                                                <SelectItem v-for="club in clubs" :key="club.id" :value="club.id">
                                                    {{ club.name }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError :message="profileErrors.home_club_id?.join(', ')"/>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
                                <Button
                                    :disabled="isProcessingProfile || !isPhoneValid"
                                    class="px-6 h-11"
                                    type="submit"
                                >
                                    <Spinner v-if="isProcessingProfile" class="mr-2 h-4 w-4"/>
                                    <span v-if="isProcessingProfile">{{ t('Saving...') }}</span>
                                    <span v-else>{{ t('Save Changes') }}</span>
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <!-- Update Password Card -->
                <Card class="shadow-lg">
                    <CardHeader class="bg-gray-50 dark:bg-gray-700/50">
                        <CardTitle class="flex items-center gap-2">
                            <KeyIcon class="h-5 w-5 text-indigo-600 dark:text-indigo-400"/>
                            {{ t('Update Password') }}
                        </CardTitle>
                        <CardDescription>{{ t('Ensure your account is using a secure password.') }}</CardDescription>
                    </CardHeader>
                    <CardContent class="p-6 sm:p-8">
                        <form class="space-y-6" @submit.prevent="updatePassword">
                            <!-- Success message -->
                            <div v-if="passwordSuccess"
                                 class="rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4">
                                <div class="flex items-center">
                                    <CheckCircleIcon class="h-5 w-5 text-green-600 dark:text-green-400 mr-2"/>
                                    <p class="text-green-800 dark:text-green-300 font-medium">
                                        {{ t('Password updated successfully.') }}
                                    </p>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div class="space-y-2">
                                    <Label for="current_password"
                                           class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ t('Current Password') }}
                                    </Label>
                                    <Input
                                        id="current_password"
                                        v-model="passwordForm.current_password"
                                        :disabled="isProcessingPassword"
                                        class="h-11"
                                        autocomplete="current-password"
                                        required
                                        type="password"
                                    />
                                    <InputError :message="passwordErrors.current_password?.join(', ')"/>
                                </div>

                                <div class="space-y-2">
                                    <Label for="password" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ t('New Password') }}
                                    </Label>
                                    <Input
                                        id="password"
                                        v-model="passwordForm.password"
                                        :disabled="isProcessingPassword"
                                        class="h-11"
                                        autocomplete="new-password"
                                        required
                                        type="password"
                                    />
                                    <InputError :message="passwordErrors.password?.join(', ')"/>
                                </div>

                                <div class="space-y-2">
                                    <Label for="password_confirmation"
                                           class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ t('Confirm Password') }}
                                    </Label>
                                    <Input
                                        id="password_confirmation"
                                        v-model="passwordForm.password_confirmation"
                                        :disabled="isProcessingPassword"
                                        class="h-11"
                                        autocomplete="new-password"
                                        required
                                        type="password"
                                    />
                                    <InputError :message="passwordErrors.password_confirmation?.join(', ')"/>
                                </div>
                            </div>

                            <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
                                <Button
                                    :disabled="isProcessingPassword"
                                    class="px-6 h-11"
                                    type="submit"
                                >
                                    <Spinner v-if="isProcessingPassword" class="mr-2 h-4 w-4"/>
                                    <span v-if="isProcessingPassword">{{ t('Updating...') }}</span>
                                    <span v-else>{{ t('Update Password') }}</span>
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <!-- Delete Account Card -->
                <Card class="shadow-lg border-red-200 dark:border-red-800">
                    <CardHeader class="bg-red-50 dark:bg-red-900/20">
                        <CardTitle class="flex items-center gap-2 text-red-800 dark:text-red-300">
                            <Trash2Icon class="h-5 w-5"/>
                            {{ t('Delete Account') }}
                        </CardTitle>
                        <CardDescription class="text-red-600 dark:text-red-400">
                            {{
                                t('Once your account is deleted, all of its resources and data will be permanently deleted.')
                            }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="p-6 sm:p-8">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div class="flex-1">
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ t('This action cannot be undone. Please be certain.') }}
                                </p>
                            </div>
                            <Button
                                variant="destructive"
                                class="px-6 h-11"
                                @click="showDeleteModal = true"
                            >
                                <Trash2Icon class="mr-2 h-4 w-4"/>
                                {{ t('Delete Account') }}
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>

    <!-- Delete Account Modal -->
    <Modal :show="showDeleteModal" @close="showDeleteModal = false">
        <div class="p-6 sm:p-8">
            <div class="flex items-center gap-3 mb-4">
                <div class="h-10 w-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                    <Trash2Icon class="h-5 w-5 text-red-600 dark:text-red-400"/>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        {{ t('Delete Account') }}
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ t('This action cannot be undone.') }}
                    </p>
                </div>
            </div>

            <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                <p class="text-sm text-red-800 dark:text-red-300">
                    {{
                        t('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.')
                    }}
                </p>
            </div>

            <form @submit.prevent="deleteAccount">
                <div class="space-y-4">
                    <div class="space-y-2">
                        <Label for="delete_password" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ t('Password') }}
                        </Label>
                        <Input
                            id="delete_password"
                            v-model="deleteForm.password"
                            :disabled="isProcessingDelete"
                            class="h-11"
                            required
                            type="password"
                        />
                        <InputError :message="deleteErrors.password?.join(', ')"/>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <Button
                        :disabled="isProcessingDelete"
                        variant="outline"
                        class="px-6 h-11"
                        @click="showDeleteModal = false"
                    >
                        {{ t('Cancel') }}
                    </Button>
                    <Button
                        :disabled="isProcessingDelete"
                        type="submit"
                        variant="destructive"
                        class="px-6 h-11"
                    >
                        <Spinner v-if="isProcessingDelete" class="mr-2 h-4 w-4"/>
                        <span v-if="isProcessingDelete">{{ t('Deleting...') }}</span>
                        <span v-else>{{ t('Delete Account') }}</span>
                    </Button>
                </div>
            </form>
        </div>
    </Modal>
</template>
