<script lang="ts" setup>
import {onMounted, reactive, ref, watch} from 'vue';
import {apiClient} from '@/lib/apiClient';
import type {ApiError, Game, League, LeaguePayload} from '@/types/api';
import {
    Button,
    Card,
    CardContent,
    CardFooter,
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
    Textarea
} from '@/Components/ui';
import InputError from '@/Components/InputError.vue';
import RatingRuleEditor from '@/Components/RatingRuleEditor.vue';

interface Props {
    league?: League | null; // Passed league for editing
    isEditMode: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    league: null,
    isEditMode: false,
});

const emit = defineEmits(['submitted', 'error']);

// Default rating rules in JSON format
const defaultWinnerRules = JSON.stringify([
    {range: [0, 50], strong: 25, weak: 25},
    {range: [51, 100], strong: 20, weak: 30},
    {range: [101, 200], strong: 15, weak: 35},
    {range: [201, 1000000], strong: 10, weak: 40}
], null, 2);

const defaultLoserRules = JSON.stringify([
    {range: [0, 50], strong: -25, weak: -25},
    {range: [51, 100], strong: -20, weak: -30},
    {range: [101, 200], strong: -15, weak: -35},
    {range: [201, 1000000], strong: -10, weak: -40}
], null, 2);

const form = reactive<LeaguePayload>({
    name: '',
    game_id: null,
    picture: null,
    details: null,
    has_rating: true,
    started_at: null,
    finished_at: null,
    start_rating: 1000, // Default
    rating_change_for_winners_rule: defaultWinnerRules,
    rating_change_for_losers_rule: defaultLoserRules,
    max_players: 0, // 0 means unlimited
    max_score: 9, // Default score for 9-ball
    invite_days_expire: 3, // Default expiration days
});

const games = ref<Game[]>([]);
const isLoading = ref(false);
const formErrors = ref<Record<string, string[]>>({});

// Helper to format date for datetime-local inputs
const formatDateForInput = (dateString: string | null): string | null => {
    if (!dateString) return null;

    try {
        const date = new Date(dateString);
        return date.toISOString().slice(0, 16); // Format as YYYY-MM-DDTHH:MM
        // eslint-disable-next-line
    } catch (e) {
        return null;
    }
};

// Load games for dropdown
async function fetchGames() {
    try {
        // Fetch games from API when endpoint is available
        games.value = await apiClient<Game[]>('/api/games');
// eslint-disable-next-line
    } catch (error) {
        // Fallback to hardcoded data if API fails
        games.value = [
            {id: 1, name: 'Пул 10', type: 'Pool'},
            {id: 2, name: 'Пул 9', type: 'Pool'},
            {id: 3, name: 'Пул 8', type: 'Pool'},
            {id: 5, name: 'Killer pool', type: 'Pool', is_multiplayer: true},
        ];
    }

    // Set default game if not in edit mode
    if (!props.isEditMode && games.value.length > 0 && !form.game_id) {
        form.game_id = games.value[0].id;
    }
}

// Initialize form data when editing
watch(() => props.league, (newLeague) => {
    if (newLeague && props.isEditMode) {
        form.name = newLeague.name;
        form.game_id = newLeague.game_id;
        form.picture = newLeague.picture;
        form.details = newLeague.details;
        form.has_rating = newLeague.has_rating;
        form.start_rating = newLeague.start_rating;
        form.max_players = newLeague.max_players || 0;
        form.max_score = newLeague.max_score || 9;
        form.invite_days_expire = newLeague.invite_days_expire || 3;

        // Format dates if present
        form.started_at = formatDateForInput(newLeague.started_at);
        form.finished_at = formatDateForInput(newLeague.finished_at);

        // Format rating rules as JSON strings for editing
        form.rating_change_for_winners_rule = JSON.stringify(newLeague.rating_change_for_winners_rule || [], null, 2);
        form.rating_change_for_losers_rule = JSON.stringify(newLeague.rating_change_for_losers_rule || [], null, 2);
    }
}, { immediate: true });

onMounted(fetchGames);

const submit = async () => {
    isLoading.value = true;
    formErrors.value = {};

    try {
        // Prepare the payload
        const payload = {...form};

        // Validate JSON before submitting
        try {
            JSON.parse(payload.rating_change_for_winners_rule as string || '[]');
            JSON.parse(payload.rating_change_for_losers_rule as string || '[]');
// eslint-disable-next-line
        } catch (e) {
            formErrors.value = {
                rating_rules: ["Invalid JSON format in rating rules."]
            };
            throw new Error("Invalid JSON format");
        }

        let response: League;

        if (props.isEditMode && props.league) {
            // Update existing league
            response = await apiClient<League>(`/api/leagues/${props.league.id}`, {
                method: 'PUT',
                data: payload,
            });
        } else {
            // Create new league
            response = await apiClient<League>('/api/leagues', {
                method: 'POST',
                data: payload,
            });
        }

        emit('submitted', response);
    } catch (error) {
        const apiError = error as ApiError;

        if (apiError.data?.errors) {
            formErrors.value = apiError.data.errors;
        } else {
            formErrors.value = {
                form: [apiError.message || 'An unknown error occurred.']
            };
        }

        emit('error', apiError);
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>{{ isEditMode ? 'Edit League' : 'Create New League' }}</CardTitle>
        </CardHeader>
        <form @submit.prevent="submit">
            <CardContent class="space-y-4">
                <div v-if="formErrors.form" class="text-red-600 text-sm bg-red-100 p-3 rounded dark:bg-red-900/30 dark:text-red-400">
                    {{ formErrors.form.join(', ') }}
                </div>

                <div>
                    <Label for="name">League Name</Label>
                    <Input id="name" v-model="form.name" :disabled="isLoading" required />
                    <InputError :message="formErrors.name?.join(', ')" />
                </div>

                <div>
                    <Label for="game_id">Game</Label>
                    <Select
                        id="game_id"
                        v-model="form.game_id"
                        :disabled="isLoading"
                        required
                    >
                        <SelectTrigger id="game_id">
                            <SelectValue
                                :placeholder="league?.game || '-- Select Game --'"
                            />

                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">-- Select Game --</SelectItem>
                            <SelectItem v-for="game in games" :key="game.id" :value="game.id">
                                {{ game.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="formErrors.game_id?.join(', ')" />
                </div>

                <div>
                    <Label for="picture">Picture URL</Label>
                    <Input id="picture" v-model="form.picture" :disabled="isLoading" placeholder="https://" type="url"/>
                    <InputError :message="formErrors.picture?.join(', ')"/>
                </div>

                <div>
                    <Label for="start_rating">Starting Rating</Label>
                    <Input id="start_rating" v-model.number="form.start_rating" :disabled="isLoading" required type="number" />
                    <InputError :message="formErrors.start_rating?.join(', ')" />
                </div>

                <div class="flex items-center space-x-2">
                    <input
                        id="has_rating"
                        v-model="form.has_rating"
                        :disabled="isLoading"
                        class="rounded border-gray-300 text-primary shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50"
                        type="checkbox"
                    />
                    <Label for="has_rating">Enable Rating System</Label>
                    <InputError :message="formErrors.has_rating?.join(', ')" />
                </div>

                <div>
                    <Label for="details">Details (Optional)</Label>
                    <Textarea id="details" v-model="form.details" :disabled="isLoading" />
                    <InputError :message="formErrors.details?.join(', ')" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <Label for="max_players">Maximum Players</Label>
                        <Input id="max_players" v-model.number="form.max_players" :disabled="isLoading" min="0" required
                               type="number"/>
                        <InputError :message="formErrors.max_players?.join(', ')"/>
                        <p class="text-xs text-gray-500 mt-1">0 = unlimited</p>
                    </div>

                    <div>
                        <Label for="max_score">Maximum Score</Label>
                        <Input id="max_score" v-model.number="form.max_score" :disabled="isLoading" min="1" required
                               type="number"/>
                        <InputError :message="formErrors.max_score?.join(', ')"/>
                    </div>

                    <div>
                        <Label for="invite_days_expire">Invite Expiry (Days)</Label>
                        <Input id="invite_days_expire" v-model.number="form.invite_days_expire" :disabled="isLoading"
                               min="1" required type="number"/>
                        <InputError :message="formErrors.invite_days_expire?.join(', ')"/>
                    </div>
                </div>

                <div>
                    <Label for="started_at">Start Date (Optional)</Label>
                    <Input id="started_at" v-model="form.started_at" :disabled="isLoading" type="datetime-local" />
                    <InputError :message="formErrors.started_at?.join(', ')" />
                </div>

                <div>
                    <Label for="finished_at">End Date (Optional)</Label>
                    <Input id="finished_at" v-model="form.finished_at" :disabled="isLoading" type="datetime-local" />
                    <InputError :message="formErrors.finished_at?.join(', ')" />
                </div>

                <div v-if="form.has_rating" class="space-y-6">
                    <RatingRuleEditor
                        v-model="form.rating_change_for_winners_rule"
                        :disabled="isLoading"
                        :is-winners="true"
                    />

                    <RatingRuleEditor
                        v-model="form.rating_change_for_losers_rule"
                        :disabled="isLoading"
                        :is-winners="false"
                    />
                </div>

                <!-- Show error messages for rating rules -->
                <InputError
                    v-if="formErrors.rating_change_for_winners_rule || formErrors.rating_change_for_losers_rule || formErrors.rating_rules"
                    :message="(formErrors.rating_change_for_winners_rule || formErrors.rating_change_for_losers_rule || formErrors.rating_rules)?.join(', ')"/>
            </CardContent>

            <CardFooter>
                <Button :disabled="isLoading" type="submit">
                    <Spinner v-if="isLoading" class="w-4 h-4 mr-2" />
                    {{ isEditMode ? 'Save Changes' : 'Create League' }}
                </Button>
            </CardFooter>
        </form>
    </Card>
</template>
