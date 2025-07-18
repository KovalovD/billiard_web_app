<script lang="ts" setup>
import {
    Alert,
    AlertDescription,
    Badge,
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
    Switch,
    Textarea,
} from '@/Components/ui';
import InputError from '@/Components/ui/form/InputError.vue';
import PictureUpload from '@/Components/profile/PictureUpload.vue';
import EquipmentManager from '@/Components/profile/EquipmentManager.vue';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {ApiError, City, Club, User} from '@/types/api';
import {Head, router} from '@inertiajs/vue3';
import {computed, onMounted, ref, watch} from 'vue';
import {useLocale} from '@/composables/useLocale';
import {
    ArrowLeftIcon,
    CalendarIcon,
    CheckCircleIcon,
    KeyIcon,
    MailIcon,
    MapPinIcon,
    PackageIcon,
    PhoneIcon,
    RefreshCwIcon,
    SaveIcon,
    ShieldIcon,
    UserCheckIcon,
    UserIcon,
} from 'lucide-vue-next';

defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    playerId: number;
    playerSlug: string;
}>();

const {t} = useLocale();

// State
const player = ref<User | null>(null);
const cities = ref<City[]>([]);
const clubs = ref<Club[]>([]);

const isLoading = ref(true);
const isLoadingCities = ref(false);
const isLoadingClubs = ref(false);

// Forms
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
    is_active: true,
    is_admin: false,
    email_verified: false,
});

const equipment = ref<any[]>([]);
const hasEquipmentChanges = ref(false);

const pictureFile = ref<File | null>(null);
const tournamentPictureFile = ref<File | null>(null);

const passwordForm = ref({
    password: '',
    password_confirmation: '',
});

// Errors and success states
const profileErrors = ref<Record<string, string[]>>({});
const passwordErrors = ref<Record<string, string[]>>({});

const profileSuccess = ref(false);
const equipmentSuccess = ref(false);
const passwordSuccess = ref(false);

const isProcessingProfile = ref(false);
const isProcessingEquipment = ref(false);
const isProcessingPassword = ref(false);
const isProcessingToggle = ref(false);

const showPasswordModal = ref(false);

const sexOptions = [
    {value: 'M', label: t('Male'), icon: '👨'},
    {value: 'F', label: t('Female'), icon: '👩'},
    {value: 'N', label: t('Non-binary'), icon: '🧑'},
];

// Computed
const maxBirthdate = computed(() => {
    const date = new Date();
    date.setFullYear(date.getFullYear() - 5);
    return date.toISOString().split('T')[0];
});

const minBirthdate = computed(() => {
    const date = new Date();
    date.setFullYear(date.getFullYear() - 120);
    return date.toISOString().split('T')[0];
});

const hasProfileChanges = computed(() => {
    if (!player.value) return false;

    return (
        profileForm.value.firstname !== player.value.firstname ||
        profileForm.value.lastname !== player.value.lastname ||
        profileForm.value.email !== player.value.email ||
        profileForm.value.phone !== (player.value.phone || '') ||
        profileForm.value.sex !== player.value.sex ||
        profileForm.value.birthdate !== (player.value.birthdate ? new Date(player.value.birthdate).toISOString().split('T')[0] : null) ||
        profileForm.value.home_city_id !== (player.value.home_city?.id || null) ||
        profileForm.value.home_club_id !== (player.value.home_club?.id || null) ||
        profileForm.value.description !== (player.value.description || '') ||
        profileForm.value.is_active !== player.value.is_active ||
        profileForm.value.is_admin !== player.value.is_admin ||
        profileForm.value.email_verified !== !!player.value.email_verified_at ||
        pictureFile.value !== null ||
        tournamentPictureFile.value !== null
    );
});

// Methods
const loadPlayer = async () => {
    try {
        const response = await apiClient<User>(`/api/admin/players/${props.playerId}`);
        player.value = response;

        // Initialize form with player data
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
            is_active: response.is_active,
            is_admin: response.is_admin,
            email_verified: !!response.email_verified_at,
        };

        equipment.value = response.equipment || [];

        // Load clubs if city is set
        if (response.home_city?.id) {
            await loadClubs(response.home_city.id);
        }
    } catch (error) {
        console.error('Failed to load player data:', error);
    } finally {
        isLoading.value = false;
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
    try {
        if (cityId) {
            clubs.value = await apiClient<Club[]>(`/api/clubs?city_id=${cityId}`);
        } else {
            clubs.value = [];
        }
    } catch (error) {
        console.error('Failed to load clubs:', error);
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

const updateProfile = async () => {
    profileErrors.value = {};
    profileSuccess.value = false;
    isProcessingProfile.value = true;

    try {
        const formData = new FormData();
        formData.append('_method', 'PUT');

        // Add all fields
        Object.entries(profileForm.value).forEach(([key, value]) => {
            if (value !== null && value !== undefined && value !== '') {
                // Handle boolean values specially
                if (typeof value === 'boolean') {
                    formData.append(key, value ? '1' : '0');
                } else {
                    formData.append(key, value.toString());
                }
            }
        });

        // Add picture files
        if (pictureFile.value) {
            formData.append('picture', pictureFile.value);
        }
        if (tournamentPictureFile.value) {
            formData.append('tournament_picture', tournamentPictureFile.value);
        }

        const response = await apiClient<any>(`/api/admin/players/${props.playerId}`, {
            method: 'post',
            data: formData,
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        player.value = response.player;
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
        const response = await apiClient<any>(`/api/admin/players/${props.playerId}/equipment`, {
            method: 'put',
            data: {equipment: equipment.value},
        });

        player.value = response.player;
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

const resetPassword = async () => {
    passwordSuccess.value = false;
    passwordErrors.value = {};
    isProcessingPassword.value = true;

    try {
        await apiClient(`/api/admin/players/${props.playerId}/reset-password`, {
            method: 'post',
            data: passwordForm.value,
        });

        passwordForm.value = {
            password: '',
            password_confirmation: '',
        };
        passwordSuccess.value = true;
        showPasswordModal.value = false;

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

const toggleActive = async () => {
    isProcessingToggle.value = true;
    try {
        const response = await apiClient<any>(`/api/admin/players/${props.playerId}/toggle-active`, {
            method: 'post',
        });

        if (player.value) {
            player.value.is_active = response.is_active;
            profileForm.value.is_active = response.is_active;
        }
    } catch (error) {
        console.error('Failed to toggle active status:', error);
    } finally {
        isProcessingToggle.value = false;
    }
};

const toggleAdmin = async () => {
    isProcessingToggle.value = true;
    try {
        const response = await apiClient<any>(`/api/admin/players/${props.playerId}/toggle-admin`, {
            method: 'post',
        });

        if (player.value) {
            player.value.is_admin = response.is_admin;
            profileForm.value.is_admin = response.is_admin;
        }
    } catch (error) {
        console.error('Failed to toggle admin status:', error);
    } finally {
        isProcessingToggle.value = false;
    }
};

const deletePicture = async (type: string) => {
    try {
        await apiClient(`/api/admin/players/${props.playerId}/picture`, {
            method: 'delete',
            params: {type}
        });

        if (player.value) {
            if (type === 'profile') {
                player.value.picture = null;
            } else {
                player.value.tournament_picture = null;
            }
        }
    } catch (error) {
        console.error('Failed to delete picture:', error);
    }
};

const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleDateString('uk-UK', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Watch equipment changes
watch(equipment, (newVal) => {
    if (player.value && player.value.equipment) {
        hasEquipmentChanges.value = JSON.stringify(newVal) !== JSON.stringify(player.value.equipment);
    }
}, {deep: true});

onMounted(() => {
    loadPlayer();
    loadCities();
});
</script>

<template>
    <Head :title="t('Edit Player')"/>

    <div class="py-6 sm:py-8 lg:py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <Button variant="outline" size="sm" @click="router.visit('/players')">
                        <ArrowLeftIcon class="mr-2 h-4 w-4"/>
                        {{ t('Back to Players') }}
                    </Button>
                </div>

                <div class="flex items-center gap-3">
                    <Button
                        variant="outline"
                        size="sm"
                        @click="router.visit(`/players/${props.playerSlug}`)"
                    >
                        <UserIcon class="mr-2 h-4 w-4"/>
                        {{ t('View Profile') }}
                    </Button>
                </div>
            </div>

            <!-- Loading state -->
            <div v-if="isLoading" class="flex justify-center items-center py-12">
                <div class="text-center">
                    <Spinner class="mx-auto h-8 w-8 text-indigo-600"/>
                    <p class="mt-2 text-gray-500">{{ t('Loading player information...') }}</p>
                </div>
            </div>

            <!-- Content -->
            <div v-else-if="player" class="space-y-8">
                <!-- Player Header Card -->
                <Card class="shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-gray-50 to-indigo-50 dark:from-gray-800 dark:to-gray-700 p-6 sm:p-8">
                        <div class="flex items-center justify-between">
                            <div>
                                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">
                                    {{ t('Edit Player: :name', {name: player.name}) }}
                                </h1>
                                <div class="flex items-center gap-4 mt-3">
                                    <Badge v-if="player.is_admin" variant="secondary" class="flex items-center gap-1">
                                        <ShieldIcon class="h-3 w-3"/>
                                        {{ t('Admin') }}
                                    </Badge>
                                    <Badge v-if="!player.is_active" variant="destructive">
                                        {{ t('Inactive') }}
                                    </Badge>
                                    <Badge v-if="player.email_verified_at" variant="outline" class="flex items-center gap-1">
                                        <MailIcon class="h-3 w-3"/>
                                        {{ t('Email Verified') }}
                                    </Badge>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                    {{ t('Member since') }} {{ formatDate(player.created_at) }}
                                </p>
                            </div>

                            <!-- Quick Actions -->
                            <div class="flex flex-col gap-2">
                                <Button
                                    size="sm"
                                    :variant="player.is_active ? 'destructive' : 'default'"
                                    :disabled="isProcessingToggle"
                                    @click="toggleActive"
                                >
                                    <UserCheckIcon class="mr-2 h-4 w-4"/>
                                    {{ player.is_active ? t('Deactivate') : t('Activate') }}
                                </Button>
                                <Button
                                    size="sm"
                                    :variant="player.is_admin ? 'destructive' : 'secondary'"
                                    :disabled="isProcessingToggle"
                                    @click="toggleAdmin"
                                >
                                    <ShieldIcon class="mr-2 h-4 w-4"/>
                                    {{ player.is_admin ? t('Remove Admin') : t('Make Admin') }}
                                </Button>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="showPasswordModal = true"
                                >
                                    <KeyIcon class="mr-2 h-4 w-4"/>
                                    {{ t('Reset Password') }}
                                </Button>
                            </div>
                        </div>
                    </div>
                </Card>

                <!-- Profile Information Card -->
                <Card class="shadow-lg">
                    <CardHeader class="bg-gray-50 dark:bg-gray-700/50">
                        <CardTitle class="flex items-center gap-2">
                            <UserIcon class="h-5 w-5 text-indigo-600 dark:text-indigo-400"/>
                            {{ t('Profile Information') }}
                        </CardTitle>
                        <CardDescription>{{ t('Update player profile information and settings') }}</CardDescription>
                    </CardHeader>

                    <CardContent class="p-6 sm:p-8">
                        <form @submit.prevent="updateProfile" class="space-y-6">
                            <!-- Success/Error messages -->
                            <Alert v-if="profileSuccess" variant="success">
                                <CheckCircleIcon class="h-4 w-4"/>
                                <AlertDescription>{{ t('Profile updated successfully.') }}</AlertDescription>
                            </Alert>

                            <!-- Status Controls -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <Label class="text-sm font-medium">{{ t('Active Account') }}</Label>
                                    <Switch
                                        v-model="profileForm.is_active"
                                        :disabled="isProcessingProfile"
                                    />
                                </div>
                                <div class="flex items-center justify-between">
                                    <Label class="text-sm font-medium">{{ t('Admin Access') }}</Label>
                                    <Switch
                                        v-model="profileForm.is_admin"
                                        :disabled="isProcessingProfile"
                                    />
                                </div>
                                <div class="flex items-center justify-between">
                                    <Label class="text-sm font-medium">{{ t('Email Verified') }}</Label>
                                    <Switch
                                        v-model="profileForm.email_verified"
                                        :disabled="isProcessingProfile"
                                    />
                                </div>
                            </div>

                            <!-- Profile Pictures -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <PictureUpload
                                    v-model="pictureFile"
                                    :current-picture="player?.picture"
                                    :label="t('Profile Picture')"
                                    :disabled="isProcessingProfile"
                                    @delete="deletePicture('profile')"
                                />

                                <PictureUpload
                                    v-model="tournamentPictureFile"
                                    :current-picture="player?.tournament_picture"
                                    :label="t('Tournament Picture')"
                                    :disabled="isProcessingProfile"
                                    @delete="deletePicture('tournament')"
                                />
                            </div>

                            <!-- Personal Information -->
                            <div class="space-y-6">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <Label for="firstname">{{ t('First Name') }}</Label>
                                        <Input
                                            id="firstname"
                                            v-model="profileForm.firstname"
                                            :disabled="isProcessingProfile"
                                            required
                                        />
                                        <InputError :message="profileErrors.firstname?.join(', ')"/>
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="lastname">{{ t('Last Name') }}</Label>
                                        <Input
                                            id="lastname"
                                            v-model="profileForm.lastname"
                                            :disabled="isProcessingProfile"
                                            required
                                        />
                                        <InputError :message="profileErrors.lastname?.join(', ')"/>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <Label for="email">{{ t('Email Address') }}</Label>
                                        <Input
                                            id="email"
                                            v-model="profileForm.email"
                                            type="email"
                                            :disabled="isProcessingProfile"
                                            required
                                        />
                                        <InputError :message="profileErrors.email?.join(', ')"/>
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="phone">
                                            <PhoneIcon class="inline h-4 w-4 mr-1"/>
                                            {{ t('Phone Number') }}
                                        </Label>
                                        <Input
                                            id="phone"
                                            v-model="profileForm.phone"
                                            type="tel"
                                            :disabled="isProcessingProfile"
                                            required
                                        />
                                        <InputError :message="profileErrors.phone?.join(', ')"/>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <Label for="sex">{{ t('Sex') }}</Label>
                                        <Select
                                            :disabled="isProcessingProfile"
                                            :modelValue="profileForm.sex"
                                            @update:modelValue="profileForm.sex = $event"
                                        >
                                            <SelectTrigger id="sex">
                                                <SelectValue :placeholder="t('Select sex')"/>
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="option in sexOptions" :key="option.value" :value="option.value">
                                                    {{ option.label }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError :message="profileErrors.sex?.join(', ')"/>
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="birthdate">
                                            <CalendarIcon class="inline h-4 w-4 mr-1"/>
                                            {{ t('Birthdate') }}
                                        </Label>
                                        <Input
                                            id="birthdate"
                                            v-model="profileForm.birthdate"
                                            type="date"
                                            :disabled="isProcessingProfile"
                                            :max="maxBirthdate"
                                            :min="minBirthdate"
                                        />
                                        <InputError :message="profileErrors.birthdate?.join(', ')"/>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <Label for="home_city">
                                            <MapPinIcon class="inline h-4 w-4 mr-1"/>
                                            {{ t('Hometown') }}
                                        </Label>
                                        <Select
                                            :disabled="isProcessingProfile || isLoadingCities"
                                            :modelValue="profileForm.home_city_id"
                                            @update:modelValue="onCityChange"
                                        >
                                            <SelectTrigger id="home_city">
                                                <SelectValue :placeholder="t('Select city')"/>
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
                                        <Label for="home_club">{{ t('Home Club') }}</Label>
                                        <Select
                                            :disabled="isProcessingProfile || isLoadingClubs || !profileForm.home_city_id"
                                            :modelValue="profileForm.home_club_id"
                                            @update:modelValue="profileForm.home_club_id = $event"
                                        >
                                            <SelectTrigger id="home_club">
                                                <SelectValue :placeholder="t('Select club')"/>
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
                                    <Label for="description">{{ t('About') }}</Label>
                                    <Textarea
                                        id="description"
                                        v-model="profileForm.description"
                                        :disabled="isProcessingProfile"
                                        rows="4"
                                        maxlength="1000"
                                        :placeholder="t('Player description...')"
                                    />
                                    <p class="text-xs text-gray-500 text-right">
                                        {{ profileForm.description.length }}/1000
                                    </p>
                                    <InputError :message="profileErrors.description?.join(', ')"/>
                                </div>
                            </div>

                            <div class="flex justify-end pt-4 border-t">
                                <Button
                                    type="submit"
                                    :disabled="isProcessingProfile || !hasProfileChanges"
                                >
                                    <Spinner v-if="isProcessingProfile" class="mr-2 h-4 w-4"/>
                                    <SaveIcon v-else class="mr-2 h-4 w-4"/>
                                    {{ isProcessingProfile ? t('Saving...') : t('Save Changes') }}
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
                            {{ t('Equipment') }}
                        </CardTitle>
                        <CardDescription>{{ t('Manage player equipment') }}</CardDescription>
                    </CardHeader>
                    <CardContent class="p-6 sm:p-8">
                        <Alert v-if="equipmentSuccess" variant="success" class="mb-4">
                            <CheckCircleIcon class="h-4 w-4"/>
                            <AlertDescription>{{ t('Equipment updated successfully.') }}</AlertDescription>
                        </Alert>

                        <EquipmentManager
                            v-model="equipment"
                            :disabled="isProcessingEquipment"
                        />

                        <div class="flex justify-end pt-4 mt-4 border-t">
                            <Button
                                :disabled="isProcessingEquipment || !hasEquipmentChanges"
                                @click="updateEquipment"
                            >
                                <Spinner v-if="isProcessingEquipment" class="mr-2 h-4 w-4"/>
                                <SaveIcon v-else class="mr-2 h-4 w-4"/>
                                {{ isProcessingEquipment ? t('Saving...') : t('Save Equipment') }}
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <Modal :show="showPasswordModal" @close="showPasswordModal = false">
        <div class="p-6 sm:p-8">
            <div class="flex items-center gap-3 mb-4">
                <div class="h-10 w-10 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                    <KeyIcon class="h-5 w-5 text-indigo-600 dark:text-indigo-400"/>
                </div>
                <div>
                    <h3 class="text-lg font-semibold">{{ t('Reset Player Password') }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ t('Set a new password for :name', {name: player?.name}) }}
                    </p>
                </div>
            </div>

            <Alert v-if="passwordSuccess" variant="success" class="mb-4">
                <CheckCircleIcon class="h-4 w-4"/>
                <AlertDescription>{{ t('Password reset successfully.') }}</AlertDescription>
            </Alert>

            <form @submit.prevent="resetPassword" class="space-y-4">
                <div class="space-y-2">
                    <Label for="password">{{ t('New Password') }}</Label>
                    <Input
                        id="password"
                        v-model="passwordForm.password"
                        type="password"
                        :disabled="isProcessingPassword"
                        required
                    />
                    <InputError :message="passwordErrors.password?.join(', ')"/>
                </div>

                <div class="space-y-2">
                    <Label for="password_confirmation">{{ t('Confirm Password') }}</Label>
                    <Input
                        id="password_confirmation"
                        v-model="passwordForm.password_confirmation"
                        type="password"
                        :disabled="isProcessingPassword"
                        required
                    />
                    <InputError :message="passwordErrors.password_confirmation?.join(', ')"/>
                </div>

                <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
                    <Button
                        variant="outline"
                        :disabled="isProcessingPassword"
                        @click="showPasswordModal = false"
                    >
                        {{ t('Cancel') }}
                    </Button>
                    <Button
                        type="submit"
                        :disabled="isProcessingPassword"
                    >
                        <Spinner v-if="isProcessingPassword" class="mr-2 h-4 w-4"/>
                        <RefreshCwIcon v-else class="mr-2 h-4 w-4"/>
                        {{ isProcessingPassword ? t('Resetting...') : t('Reset Password') }}
                    </Button>
                </div>
            </form>
        </div>
    </Modal>
</template>
