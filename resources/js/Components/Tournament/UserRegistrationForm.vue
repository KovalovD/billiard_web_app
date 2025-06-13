<!-- resources/js/Components/Tournament/UserRegistrationForm.vue -->
<script lang="ts" setup>
import {ref, reactive, computed} from 'vue';
import {
    Button,
    Input,
    Label,
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue
} from '@/Components/ui';
import {useLocale} from '@/composables/useLocale';
import {useProfileApi} from '@/composables/useProfileApi';

interface Props {
    disabled?: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    registered: [user: any];
}>();

const {t} = useLocale();
const {fetchCities, fetchClubs} = useProfileApi();

// Form data
const form = reactive({
    firstname: '',
    lastname: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    home_city_id: null as number | null,
    home_club_id: null as number | null,
    skill_level: 'intermediate' as 'beginner' | 'intermediate' | 'advanced' | 'expert'
});

// Data
const cities = ref<any[]>([]);
const clubs = ref<any[]>([]);
const filteredClubs = ref<any[]>([]);

// State
const isSubmitting = ref(false);
const errors = ref<Record<string, string>>({});

// Computed
const isFormValid = computed(() => {
    return form.firstname.trim() !== '' &&
        form.lastname.trim() !== '' &&
        form.email.trim() !== '' &&
        form.phone.trim() !== '' &&
        form.password.length >= 8 &&
        form.password === form.password_confirmation &&
        validateEmail(form.email) &&
        Object.keys(errors.value).length === 0;
});

const skillLevelOptions = [
    {value: 'beginner', label: t('Beginner'), description: t('New to the game')},
    {value: 'intermediate', label: t('Intermediate'), description: t('Some tournament experience')},
    {value: 'advanced', label: t('Advanced'), description: t('Regular competitor')},
    {value: 'expert', label: t('Expert'), description: t('Professional level')}
];

// Methods
const validateEmail = (email: string): boolean => {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
};

const validateForm = () => {
    errors.value = {};

    if (!form.firstname.trim()) {
        errors.value.firstname = t('First name is required');
    }

    if (!form.lastname.trim()) {
        errors.value.lastname = t('Last name is required');
    }

    if (!form.email.trim()) {
        errors.value.email = t('Email is required');
    } else if (!validateEmail(form.email)) {
        errors.value.email = t('Please enter a valid email address');
    }

    if (!form.phone.trim()) {
        errors.value.phone = t('Phone number is required');
    }

    if (!form.password) {
        errors.value.password = t('Password is required');
    } else if (form.password.length < 8) {
        errors.value.password = t('Password must be at least 8 characters');
    }

    if (form.password !== form.password_confirmation) {
        errors.value.password_confirmation = t('Passwords do not match');
    }
};

const loadCitiesAndClubs = async () => {
    try {
        const [citiesResponse, clubsResponse] = await Promise.all([
            fetchCities(),
            fetchClubs()
        ]);

        await citiesResponse.execute();
        await clubsResponse.execute();

        if (citiesResponse.data.value) cities.value = citiesResponse.data.value;
        if (clubsResponse.data.value) clubs.value = clubsResponse.data.value;
    } catch (error) {
        console.error('Failed to fetch cities and clubs:', error);
    }
};

const updateFilteredClubs = () => {
    if (form.home_city_id) {
        const selectedCity = cities.value.find(c => c.id === form.home_city_id);
        if (selectedCity) {
            filteredClubs.value = clubs.value.filter(club =>
                club.city === selectedCity.name
            );
        }
    } else {
        filteredClubs.value = [];
        form.home_club_id = null;
    }
};

const handleCityChange = () => {
    form.home_club_id = null;
    updateFilteredClubs();
};

const handleSubmit = async () => {
    validateForm();

    if (!isFormValid.value || props.disabled) {
        return;
    }

    isSubmitting.value = true;

    try {
        // Mock registration - replace with actual API call
        await new Promise(resolve => setTimeout(resolve, 1000));

        const newUser = {
            id: Date.now(),
            firstname: form.firstname,
            lastname: form.lastname,
            email: form.email,
            phone: form.phone,
            home_city_id: form.home_city_id,
            home_club_id: form.home_club_id,
            skill_level: form.skill_level,
            rating: getInitialRating(form.skill_level),
            created_at: new Date().toISOString()
        };

        emit('registered', newUser);
        resetForm();
    } catch (error: any) {
        console.error('Registration failed:', error);
        errors.value.submit = error.message || t('Registration failed. Please try again.');
    } finally {
        isSubmitting.value = false;
    }
};

const getInitialRating = (skillLevel: string): number => {
    switch (skillLevel) {
        case 'beginner':
            return 1200;
        case 'intermediate':
            return 1500;
        case 'advanced':
            return 1800;
        case 'expert':
            return 2100;
        default:
            return 1500;
    }
};

const resetForm = () => {
    Object.assign(form, {
        firstname: '',
        lastname: '',
        email: '',
        phone: '',
        password: '',
        password_confirmation: '',
        home_city_id: null,
        home_club_id: null,
        skill_level: 'intermediate'
    });
    errors.value = {};
};

// Initialize
loadCitiesAndClubs();
</script>

<template>
    <form class="space-y-6" @submit.prevent="handleSubmit">
        <!-- Personal Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
                <Label for="firstname">{{ t('First Name') }} *</Label>
                <Input
                    id="firstname"
                    v-model="form.firstname"
                    :class="{ 'border-red-500': errors.firstname }"
                    :disabled="disabled"
                    :placeholder="t('Enter first name')"
                    required
                />
                <p v-if="errors.firstname" class="text-sm text-red-600">{{ errors.firstname }}</p>
            </div>

            <div class="space-y-2">
                <Label for="lastname">{{ t('Last Name') }} *</Label>
                <Input
                    id="lastname"
                    v-model="form.lastname"
                    :class="{ 'border-red-500': errors.lastname }"
                    :disabled="disabled"
                    :placeholder="t('Enter last name')"
                    required
                />
                <p v-if="errors.lastname" class="text-sm text-red-600">{{ errors.lastname }}</p>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
                <Label for="email">{{ t('Email') }} *</Label>
                <Input
                    id="email"
                    v-model="form.email"
                    :class="{ 'border-red-500': errors.email }"
                    :disabled="disabled"
                    :placeholder="t('Enter email address')"
                    required
                    type="email"
                />
                <p v-if="errors.email" class="text-sm text-red-600">{{ errors.email }}</p>
            </div>

            <div class="space-y-2">
                <Label for="phone">{{ t('Phone') }} *</Label>
                <Input
                    id="phone"
                    v-model="form.phone"
                    :class="{ 'border-red-500': errors.phone }"
                    :disabled="disabled"
                    :placeholder="t('Enter phone number')"
                    required
                    type="tel"
                />
                <p v-if="errors.phone" class="text-sm text-red-600">{{ errors.phone }}</p>
            </div>
        </div>

        <!-- Password -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
                <Label for="password">{{ t('Password') }} *</Label>
                <Input
                    id="password"
                    v-model="form.password"
                    :class="{ 'border-red-500': errors.password }"
                    :disabled="disabled"
                    :placeholder="t('Enter password (min 8 characters)')"
                    required
                    type="password"
                />
                <p v-if="errors.password" class="text-sm text-red-600">{{ errors.password }}</p>
            </div>

            <div class="space-y-2">
                <Label for="password_confirmation">{{ t('Confirm Password') }} *</Label>
                <Input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    :class="{ 'border-red-500': errors.password_confirmation }"
                    :disabled="disabled"
                    :placeholder="t('Confirm password')"
                    required
                    type="password"
                />
                <p v-if="errors.password_confirmation" class="text-sm text-red-600">{{
                        errors.password_confirmation
                    }}</p>
            </div>
        </div>

        <!-- Skill Level -->
        <div class="space-y-2">
            <Label for="skill_level">{{ t('Skill Level') }} *</Label>
            <Select v-model="form.skill_level" :disabled="disabled">
                <SelectTrigger>
                    <SelectValue/>
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="option in skillLevelOptions"
                        :key="option.value"
                        :value="option.value"
                    >
                        <div class="flex flex-col">
                            <span class="font-medium">{{ option.label }}</span>
                            <span class="text-xs text-gray-500">{{ option.description }}</span>
                        </div>
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <!-- Location (Optional) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
                <Label for="home_city">{{ t('Home City') }} ({{ t('Optional') }})</Label>
                <Select v-model="form.home_city_id" :disabled="disabled" @update:model-value="handleCityChange">
                    <SelectTrigger>
                        <SelectValue :placeholder="t('Select city')"/>
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="city in cities" :key="city.id" :value="city.id">
                            {{ city.name }}, {{ city.country.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div class="space-y-2">
                <Label for="home_club">{{ t('Home Club') }} ({{ t('Optional') }})</Label>
                <Select v-model="form.home_club_id" :disabled="disabled || !form.home_city_id">
                    <SelectTrigger>
                        <SelectValue :placeholder="t('Select club')"/>
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="club in filteredClubs" :key="club.id" :value="club.id">
                            {{ club.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end">
            <Button
                :class="{ 'opacity-50 cursor-not-allowed': !isFormValid || isSubmitting || disabled }"
                :disabled="!isFormValid || isSubmitting || disabled"
                type="submit"
            >
        <span v-if="isSubmitting" class="mr-2">
          <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" fill="none" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                  fill="currentColor"/>
          </svg>
        </span>
                {{ isSubmitting ? t('Registering...') : t('Register & Add to Tournament') }}
            </Button>
        </div>

        <!-- Error Display -->
        <div v-if="errors.submit" class="rounded-md bg-red-50 p-4 border border-red-200">
            <p class="text-sm text-red-600">{{ errors.submit }}</p>
        </div>
    </form>
</template>
