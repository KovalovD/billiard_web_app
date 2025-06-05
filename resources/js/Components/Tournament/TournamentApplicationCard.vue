<script lang="ts" setup>
import {Button, Card, CardContent, CardDescription, CardHeader, CardTitle, Spinner} from '@/Components/ui';
import {useAuth} from '@/composables/useAuth';
import {apiClient} from '@/lib/apiClient';
import type {Tournament, TournamentPlayer} from '@/types/api';
import {CalendarIcon, CheckCircleIcon, ClockIcon, XCircleIcon} from 'lucide-vue-next';
import {computed, onMounted, ref} from 'vue';
import {useLocale} from '@/composables/useLocale';

const props = defineProps<{
    tournament: Tournament;
}>();

const emit = defineEmits<{
    applicationUpdated: [application: TournamentPlayer | null];
}>();

const {user, isAuthenticated} = useAuth();
const { t } = useLocale();

// State
const application = ref<TournamentPlayer | null>(null);
const applicationMessage = ref('');
const isLoading = ref(false);
const isSubmitting = ref(false);
const error = ref<string | null>(null);

// Computed
const canApply = computed(() => {
    if (!isAuthenticated.value || !user.value) return false;
    if (application.value) return false;
    return props.tournament.can_accept_applications;
});

const canCancelApplication = computed(() => {
    return application.value?.is_pending || false;
});

const applicationDeadline = computed(() => {
    if (props.tournament.application_deadline) {
        return new Date(props.tournament.application_deadline);
    }
    return new Date(props.tournament.start_date);
});

const isDeadlinePassed = computed(() => {
    return applicationDeadline.value < new Date();
});

const getStatusInfo = computed(() => {
    if (!application.value) {
        return {
            status: 'none',
            message: 'Not applied',
            color: 'text-gray-500',
            bgColor: 'bg-gray-100 dark:bg-gray-700',
            icon: ClockIcon
        };
    }

    switch (application.value.status) {
        case 'applied':
            return {
                status: 'pending',
                message: 'Application pending approval',
                color: 'text-yellow-600 dark:text-yellow-400',
                bgColor: 'bg-yellow-100 dark:bg-yellow-900/30',
                icon: ClockIcon
            };
        case 'confirmed':
            return {
                status: 'confirmed',
                message: 'Application confirmed',
                color: 'text-green-600 dark:text-green-400',
                bgColor: 'bg-green-100 dark:bg-green-900/30',
                icon: CheckCircleIcon
            };
        case 'rejected':
            return {
                status: 'rejected',
                message: 'Application rejected',
                color: 'text-red-600 dark:text-red-400',
                bgColor: 'bg-red-100 dark:bg-red-900/30',
                icon: XCircleIcon
            };
        default:
            return {
                status: 'unknown',
                message: 'Unknown status',
                color: 'text-gray-500',
                bgColor: 'bg-gray-100 dark:bg-gray-700',
                icon: ClockIcon
            };
    }
});

// Methods
const fetchApplicationStatus = async () => {
    if (!isAuthenticated.value || !user.value) return;

    isLoading.value = true;
    error.value = null;

    try {
        const response = await apiClient<{
            has_application: boolean;
            application?: TournamentPlayer;
            can_apply: boolean;
        }>(`/api/tournaments/${props.tournament.id}/application-status`);

        application.value = response.application || null;
    } catch (err: any) {
        error.value = err.message || 'Failed to load application status';
    } finally {
        isLoading.value = false;
    }
};

const submitApplication = async () => {
    if (!canApply.value) return;

    isSubmitting.value = true;
    error.value = null;

    try {
        const response = await apiClient<{
            success: boolean;
            application: TournamentPlayer;
            message: string;
        }>(`/api/tournaments/${props.tournament.id}/apply`, {
            method: 'POST',
            data: {
                message: applicationMessage.value || null
            },
        });

        if (response.success) {
            application.value = response.application;
            applicationMessage.value = '';
            emit('applicationUpdated', application.value);
        }
    } catch (err: any) {
        error.value = err.message || 'Failed to submit application';
    } finally {
        isSubmitting.value = false;
    }
};

const cancelApplication = async () => {
    if (!canCancelApplication.value) return;

    if (!confirm('Are you sure you want to cancel your application?')) {
        return;
    }

    isSubmitting.value = true;
    error.value = null;

    try {
        await apiClient(`/api/tournaments/${props.tournament.id}/cancel-application`, {
            method: 'DELETE'
        });

        application.value = null;
        emit('applicationUpdated', null);
    } catch (err: any) {
        error.value = err.message || 'Failed to cancel application';
    } finally {
        isSubmitting.value = false;
    }
};

const formatDateTime = (dateString: string | undefined): string => {
    if (!dateString) return '';

    return new Date(dateString).toLocaleString('uk-UK', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

onMounted(() => {
    if (isAuthenticated.value && user.value) {
        fetchApplicationStatus();
    }
});
</script>

<template>
    <Card :class="getStatusInfo.bgColor" class="border-l-4">
        <CardHeader>
            <CardTitle :class="getStatusInfo.color" class="flex items-center gap-2">
                <component :is="getStatusInfo.icon" class="h-5 w-5"/>
                {{ t('Tournament Registration') }}
            </CardTitle>
            <CardDescription>
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-sm">
                        <CalendarIcon class="h-4 w-4"/>
                        {{ t('Application deadline: :date', {date: formatDateTime(applicationDeadline.toISOString())}) }}
                    </div>
                    <div v-if="props.tournament.max_participants" class="text-sm">
                        {{ props.tournament.confirmed_players_count }} / {{ props.tournament.max_participants }}
                        {{ t('confirmed players') }}
                    </div>
                </div>
            </CardDescription>
        </CardHeader>
        <CardContent>
            <!-- Loading State -->
            <div v-if="isLoading" class="flex items-center justify-center py-4">
                <Spinner class="h-6 w-6"/>
                <span class="ml-2 text-sm text-gray-500">{{ t('Loading application status...') }}</span>
            </div>

            <!-- Not Authenticated -->
            <div v-else-if="!isAuthenticated" class="text-center py-4">
                <p class="text-gray-600 dark:text-gray-400">
                    {{ t('Please log in to apply for this tournament.') }}
                </p>
            </div>

            <!-- Application Status -->
            <div v-else class="space-y-4">
                <!-- Current Status -->
                <div class="flex items-center gap-2">
                    <component :is="getStatusInfo.icon" :class="getStatusInfo.color" class="h-5 w-5"/>
                    <span :class="getStatusInfo.color" class="font-medium">
                        {{ getStatusInfo.message }}
                    </span>
                </div>

                <!-- Application Details -->
                <div v-if="application" class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                    <div>Applied: {{ formatDateTime(application.applied_at) }}</div>
                    <div v-if="application.confirmed_at">
                        Confirmed: {{ formatDateTime(application.confirmed_at) }}
                    </div>
                    <div v-else-if="application.rejected_at">
                        Rejected: {{ formatDateTime(application.rejected_at) }}
                    </div>
                </div>

                <!-- Application Form -->
                <div v-if="canApply && !isDeadlinePassed" class="space-y-4">
                    <Button
                        :disabled="isSubmitting"
                        class="w-full"
                        @click="submitApplication"
                    >
                        <Spinner v-if="isSubmitting" class="mr-2 h-4 w-4"/>
                        {{ isSubmitting ? t('Submitting...') : t('Submit Application') }}
                    </Button>
                </div>

                <!-- Cancel Application -->
                <div v-if="canCancelApplication" class="space-y-2">
                    <Button
                        :disabled="isSubmitting"
                        class="w-full"
                        variant="outline"
                        @click="cancelApplication"
                    >
                        <Spinner v-if="isSubmitting" class="mr-2 h-4 w-4"/>
                        {{ isSubmitting ? t('Cancelling...') : t('Cancel Application') }}
                    </Button>
                </div>

                <!-- Information Messages -->
                <div v-if="isDeadlinePassed" class="text-sm text-red-600 dark:text-red-400">
                    {{ t('Application deadline has passed.') }}
                </div>

                <div v-if="!props.tournament.can_accept_applications && !isDeadlinePassed"
                     class="text-sm text-gray-600 dark:text-gray-400">
                    {{ t('This tournament is not accepting applications at this time.') }}
                </div>

                <div
                    v-if="props.tournament.max_participants && props.tournament.confirmed_players_count >= props.tournament.max_participants"
                    class="text-sm text-orange-600 dark:text-orange-400">
                    {{ t('This tournament has reached its maximum number of participants.') }}
                </div>

                <!-- Error Display -->
                <div v-if="error"
                     class="text-sm text-red-600 dark:text-red-400 bg-red-100 dark:bg-red-900/30 p-3 rounded">
                    {{ error }}
                </div>

                <!-- Success Messages -->
                <div v-if="application?.is_pending"
                     class="text-sm text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/30 p-3 rounded">
                    {{ t('Your application has been submitted successfully. Please wait for admin approval.') }}
                </div>

                <div v-if="application?.is_confirmed"
                     class="text-sm text-green-600 dark:text-green-400 bg-green-100 dark:bg-green-900/30 p-3 rounded">
                    {{ t('Congratulations! Your application has been confirmed. You are registered for this tournament.') }}
                </div>
            </div>
        </CardContent>
    </Card>
</template>
