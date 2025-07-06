// resources/js/Pages/Profile/Edit.vue
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
    Textarea,
} from '@/Components/ui';
import PictureUpload from '@/Components/profile/PictureUpload.vue';
import EquipmentManager from '@/Components/profile/EquipmentManager.vue';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {ApiError, City, Club, User} from '@/types/api';
import {Head, Link} from '@inertiajs/vue3';
import {computed, onMounted, ref, watch} from 'vue';
import {useLocale} from '@/composables/useLocale';
import {
    ArrowLeftIcon,
    CalendarIcon,
    CheckCircleIcon,
    EditIcon,
    KeyIcon,
    MapPinIcon,
    PackageIcon,
    PhoneIcon,
    SaveIcon,
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

const {t} = useLocale();

// Separate forms for profile and equipment
const profileForm = ref({
    firstname: '',
    lastname: '',
    email: '',
    phone: '',
    sex: null as string | null,
    birthdate: null as string | null,
    home_city_id: null as number | string | null,
    home_club_id: null as number | string | null,
    description: '',
});

const equipment = ref<any[]>([]);
const hasEquipmentChanges = ref(false);

const pictureFile = ref<File | null>(null);
const tournamentPictureFile = ref<File | null>(null);

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
const equipmentSuccess = ref(false);
const passwordSuccess = ref(false);
const isProcessingProfile = ref(false);
const isProcessingEquipment = ref(false);
const isProcessingPassword = ref(false);
const isProcessingDelete = ref(false);
const showDeleteModal = ref(false);

const sexOptions = [
    {value: 'M', label: t('Male'), icon: '👨'},
    {value: 'F', label: t('Female'), icon: '👩'},
    {value: 'N', label: t('Non-binary'), icon: '🧑'},
];

// Compute maximum birthdate (must be at least 5 years old)
const maxBirthdate = computed(() => {
    const date = new Date();
    date.setFullYear(date.getFullYear() - 5);
    return date.toISOString().split('T')[0];
});

// Compute minimum birthdate (not older than 120 years)
const minBirthdate = computed(() => {
    const date = new Date();
    date.setFullYear(date.getFullYear() - 120);
    return date.toISOString().split('T')[0];
});

// Track if profile form has changes
const hasProfileChanges = computed(() => {
    if (!user.value) return false;

    return (
        profileForm.value.firstname !== user.value.firstname ||
        profileForm.value.lastname !== user.value.lastname ||
        profileForm.value.email !== user.value.email ||
        profileForm.value.phone !== (user.value.phone || '') ||
        profileForm.value.sex !== user.value.sex ||
        profileForm.value.birthdate !== (user.value.birthdate ? new Date(user.value.birthdate).toISOString().split('T')[0] : null) ||
        profileForm.value.home_city_id !== (user.value.home_city?.id || null) ||
        profileForm.value.home_club_id !== (user.value.home_club?.id || null) ||
        profileForm.value.description !== (user.value.description || '') ||
        pictureFile.value !== null ||
        tournamentPictureFile.value !== null
    );
});

// Phone validation state
const phoneValidationState = computed(() => {
    if (!profileForm.value.phone) return {valid: true, message: ''};

    const isValid = phonePattern.test(profileForm.value.phone);
    return {
        valid: isValid,
        message: isValid ? '' : t('Please enter a valid phone number format (e.g., +1234567890, 123-456-7890)')
    };
});

// Watch equipment changes
watch(equipment, (newVal) => {
    if (user.value && user.value.equipment) {
        hasEquipmentChanges.value = JSON.stringify(newVal) !== JSON.stringify(user.value.equipment);
    }
}, {deep: true});

const loadUser = async () => {
    try {
        const response = await apiClient<User>('/api/auth/user');
        user.value = response;

        // Initialize forms with user data
        profileForm.value = {
            firstname: response.firstname,
            lastname: response.lastname,
            email: response.email,
            phone: response.phone || '',
            sex: response.sex || null,
            birthdate: response.birthdate ? new Date(response.birthdate).toISOString().split('T')[0] : null,
            home_city_id: response.home_city?.id || null,
            home_club_id: response.home_club?.id || null,
            description: response.description || '',
        };

        equipment.value = response.equipment || [];

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

const clearFieldError = (field: string) => {
    if (profileErrors.value[field]) {
        delete profileErrors.value[field];
    }
};

const updateProfile = async () => {
    // Clear previous errors
    profileErrors.value = {};
    profileSuccess.value = false;

    // Validate phone on submit
    if (!phoneValidationState.value.valid) {
        profileErrors.value.phone = [phoneValidationState.value.message];
        return;
    }

    isProcessingProfile.value = true;

    try {
        // Create FormData for file uploads
        const formData = new FormData();

        // Add method spoofing for Laravel
        formData.append('_method', 'PUT');

        // Add all text fields
        Object.entries(profileForm.value).forEach(([key, value]) => {
            if (value !== null && value !== undefined && value !== '') {
                formData.append(key, value.toString());
            }
        });

        // Add picture files if selected
        if (pictureFile.value) {
            formData.append('picture', pictureFile.value);
        }
        if (tournamentPictureFile.value) {
            formData.append('tournament_picture', tournamentPictureFile.value);
        }

        // Update user data
        user.value = await apiClient<User>('/api/profile', {
            method: 'post', // Use POST with _method field for Laravel
            data: formData,
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        // Clear file selections after successful upload
        pictureFile.value = null;
        tournamentPictureFile.value = null;

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

const updateEquipment = async () => {
    equipmentSuccess.value = false;
    isProcessingEquipment.value = true;

    try {
        user.value = await apiClient<User>('/api/profile/equipment', {
            method: 'put',
            data: {equipment: equipment.value},
        });
        hasEquipmentChanges.value = false;
        equipmentSuccess.value = true;

        setTimeout(() => {
            equipmentSuccess.value = false;
        }, 3000);
    } catch (error: any) {
        console.error('Failed to update equipment:', error);
    } finally {
        isProcessingEquipment.value = false;
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

const deletePicture = async (type: string) => {
    try {
        await apiClient('/api/profile/picture', {
            method: 'delete',
            params: {type}
        });

        if (type === 'profile') {
            user.value!.picture = null;
        } else {
            user.value!.tournament_picture = null;
        }
    } catch (error) {
        console.error('Failed to delete picture:', error);
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

                            <!-- Profile Pictures Section -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <PictureUpload
                                    v-model="pictureFile"
                                    :current-picture="user?.picture"
                                    :label="t('Profile Picture')"
                                    :disabled="isProcessingProfile"
                                    @delete="deletePicture('profile')"
                                />

                                <PictureUpload
                                    v-model="tournamentPictureFile"
                                    :current-picture="user?.tournament_picture"
                                    :label="t('Tournament Picture')"
                                    :disabled="isProcessingProfile"
                                    @delete="deletePicture('tournament')"
                                />
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
                                            @input="clearFieldError('firstname')"
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
                                            @input="clearFieldError('lastname')"
                                        />
                                        <InputError :message="profileErrors.lastname?.join(', ')"/>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <Label for="sex" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {{ t('Sex') }}
                                        </Label>
                                        <Select
                                            :disabled="isProcessingProfile"
                                            :modelValue="profileForm.sex"
                                            @update:modelValue="profileForm.sex = $event; clearFieldError('sex')"
                                        >
                                            <SelectTrigger id="sex" class="h-11">
                                                <SelectValue :placeholder="user?.sex_value || t('Select sex')"/>
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="option in sexOptions" :key="option.value"
                                                            :value="option.value">
                                                    <span class="flex items-center gap-2">
                                                        {{ option.label }}
                                                    </span>
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError :message="profileErrors.sex?.join(', ')"/>
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="birthdate"
                                               class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            <CalendarIcon class="inline h-4 w-4 mr-1"/>
                                            {{ t('Birthdate') }}
                                        </Label>
                                        <Input
                                            id="birthdate"
                                            v-model="profileForm.birthdate"
                                            :disabled="isProcessingProfile"
                                            :max="maxBirthdate"
                                            :min="minBirthdate"
                                            class="h-11"
                                            type="date"
                                            @input="clearFieldError('birthdate')"
                                        />
                                        <InputError :message="profileErrors.birthdate?.join(', ')"/>
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
                                        @input="clearFieldError('email')"
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
                                        :class="{ 'border-red-300 focus:border-red-300 focus:ring-red-300': !phoneValidationState.valid && profileForm.phone }"
                                        :disabled="isProcessingProfile"
                                        :placeholder="t('e.g., (123) 456-7890')"
                                        class="h-11"
                                        required
                                        type="tel"
                                        @input="clearFieldError('phone')"
                                    />
                                    <p v-if="!phoneValidationState.valid && profileForm.phone"
                                       class="text-xs text-red-600 dark:text-red-400 mt-1">
                                        {{ phoneValidationState.message }}
                                    </p>
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

                                <div class="space-y-2">
                                    <Label for="description"
                                           class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ t('About Me') }}
                                    </Label>
                                    <Textarea
                                        id="description"
                                        v-model="profileForm.description"
                                        :disabled="isProcessingProfile"
                                        :placeholder="t('Tell us about yourself, your playing style, achievements...')"
                                        rows="4"
                                        maxlength="1000"
                                        @input="clearFieldError('description')"
                                    />
                                    <p class="text-xs text-gray-500 dark:text-gray-400 text-right">
                                        {{ profileForm.description.length }}/1000
                                    </p>
                                    <InputError :message="profileErrors.description?.join(', ')"/>
                                </div>
                            </div>

                            <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
                                <Button
                                    :disabled="isProcessingProfile || !phoneValidationState.valid || !hasProfileChanges"
                                    class="px-6 h-11"
                                    type="submit"
                                >
                                    <Spinner v-if="isProcessingProfile" class="mr-2 h-4 w-4"/>
                                    <SaveIcon v-else class="mr-2 h-4 w-4"/>
                                    <span v-if="isProcessingProfile">{{ t('Saving...') }}</span>
                                    <span v-else>{{ t('Save Changes') }}</span>
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <!-- Equipment Card -->
                <Card class="shadow-lg">
                    <CardHeader class="bg-gray-50 dark:bg-gray-700/50">
                        <CardTitle class="flex items-center gap-2">
                            <PackageIcon class="h-5 w-5 text-indigo-600 dark:text-indigo-400"/>
                            {{ t('My Equipment') }}
                        </CardTitle>
                        <CardDescription>{{
                                t('Manage your cues, cases, and other billiard equipment')
                            }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="p-6 sm:p-8">
                        <!-- Success message -->
                        <div v-if="equipmentSuccess"
                             class="rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4 mb-4">
                            <div class="flex items-center">
                                <CheckCircleIcon class="h-5 w-5 text-green-600 dark:text-green-400 mr-2"/>
                                <p class="text-green-800 dark:text-green-300 font-medium">
                                    {{ t('Equipment updated successfully.') }}
                                </p>
                            </div>
                        </div>

                        <EquipmentManager
                            v-model="equipment"
                            :disabled="isProcessingEquipment"
                        />

                        <div class="flex justify-end pt-4 mt-4 border-t border-gray-200 dark:border-gray-700">
                            <Button
                                :disabled="isProcessingEquipment"
                                class="px-6 h-11"
                                @click="updateEquipment"
                            >
                                <Spinner v-if="isProcessingEquipment" class="mr-2 h-4 w-4"/>
                                <SaveIcon v-else class="mr-2 h-4 w-4"/>
                                <span v-if="isProcessingEquipment">{{ t('Saving...') }}</span>
                                <span v-else>{{ t('Save Equipment') }}</span>
                            </Button>
                        </div>
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
