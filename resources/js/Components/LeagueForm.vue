<script setup lang="ts">
import { ref, reactive, onMounted, watch } from 'vue';
import { apiClient } from '@/lib/apiClient';
import type { LeaguePayload, League, Game, ApiError } from '@/Types/api';
import { Button, Input, Label, Select, Textarea, Card, CardHeader, CardTitle, CardContent, CardFooter } from '@/Components/ui';
import InputError from '@/Components/InputError.vue';

interface Props {
    league?: League | null; // Передаем существующую лигу для редактирования
    isEditMode: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    league: null,
    isEditMode: false,
});

const emit = defineEmits(['submitted', 'error']);

const form = reactive<LeaguePayload>({
    name: '',
    game_id: null,
    details: null,
    has_rating: true,
    started_at: null,
    finished_at: null,
    start_rating: 1000, // Дефолт из factory
    // Правила храним как JSON строки для простоты Textarea
    rating_change_for_winners_rule: props.isEditMode ? JSON.stringify(props.league?.rating_change_for_winners_rule || [], null, 2) : JSON.stringify([
        { range: [0, 50], strong: 25, weak: 25 }, { range: [51, 100], strong: 20, weak: 30 },
        { range: [101, 200], strong: 15, weak: 35 }, { range: [201, 1000000], strong: 10, weak: 40 }
    ], null, 2),
    rating_change_for_losers_rule: props.isEditMode ? JSON.stringify(props.league?.rating_change_for_losers_rule || [], null, 2) : JSON.stringify([
        { range: [0, 50], strong: -25, weak: -25 }, { range: [51, 100], strong: -20, weak: -30 },
        { range: [101, 200], strong: -15, weak: -35 }, { range: [201, 1000000], strong: -10, weak: -40 }
    ], null, 2),
    picture: null, // Поле picture требует отдельной обработки (загрузка файла)
});

const games = ref<Game[]>([]);
const isLoading = ref(false);
const formErrors = ref<Record<string, string[]>>({}); // Ошибки валидации

// Загружаем список игр для выпадающего списка
async function fetchGames() {
    try {
        // TODO: Нужен API эндпоинт для получения списка игр, например GET /api/games
        // Пока что оставим пустым или захардкодим
        // games.value = await apiClient<Game[]>('/games');
        console.warn('API endpoint for fetching games is missing. Using hardcoded data.');
        games.value = [ // Пример данных
            { id: 1, name: 'Пул 10', type: 'Pool' },
            { id: 2, name: 'Пул 9', type: 'Pool' },
            { id: 3, name: 'Пул 8', type: 'Pool' },
            { id: 5, name: 'Killer pool', type: 'Pool', is_multiplayer: true },
        ];
        // Устанавливаем дефолтную игру, если не режим редактирования
        if (!props.isEditMode && games.value.length > 0) {
            form.game_id = games.value[0].id;
        }

    } catch (error) {
        console.error("Failed to fetch games:", error);
    }
}

// Заполняем форму данными лиги при редактировании
watch(() => props.league, (newLeague) => {
    if (newLeague && props.isEditMode) {
        form.name = newLeague.name;
        form.game_id = newLeague.game_id;
        form.details = newLeague.details;
        form.has_rating = newLeague.has_rating;
        form.start_rating = newLeague.start_rating;
        // TODO: Преобразовать даты в нужный формат для input[type=datetime-local]
        form.started_at = newLeague.started_at;
        form.finished_at = newLeague.finished_at;
        form.rating_change_for_winners_rule = JSON.stringify(newLeague.rating_change_for_winners_rule || [], null, 2);
        form.rating_change_for_losers_rule = JSON.stringify(newLeague.rating_change_for_losers_rule || [], null, 2);
        // form.picture = newLeague.picture; // Обработка картинки сложнее
    }
}, { immediate: true });

onMounted(fetchGames);

const submit = async () => {
    isLoading.value = true;
    formErrors.value = {};
    try {
        let response: League;
        const payload = { ...form };

        // Проверяем валидность JSON перед отправкой
        try {
            JSON.parse(payload.rating_change_for_winners_rule || '[]');
            JSON.parse(payload.rating_change_for_losers_rule || '[]');
        } catch(e) {
            formErrors.value = { rating_rules: ["Invalid JSON format in rating rules."] };
            throw new Error("Invalid JSON format");
        }

        if (props.isEditMode && props.league) {
            // Путь: /api/leagues/{league_id} (PUT)
            response = await apiClient<League>(`/leagues/${props.league.id}`, {
                method: 'PUT',
                body: JSON.stringify(payload),
            });
        } else {
            // Путь: /api/leagues (POST)
            response = await apiClient<League>('/leagues', {
                method: 'POST',
                body: JSON.stringify(payload),
            });
        }
        emit('submitted', response); // Успешная отправка
    } catch (error) {
        const apiError = error as ApiError;
        console.error("League form submission error:", apiError);
        if (apiError.data?.errors) {
            formErrors.value = apiError.data.errors; // Ошибки валидации от Laravel
        } else {
            // Общая ошибка
            formErrors.value = { form: [apiError.message || 'An unknown error occurred.'] };
        }
        emit('error', apiError); // Сообщаем об ошибке
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
                <div v-if="formErrors.form" class="text-red-600 text-sm bg-red-100 p-3 rounded">
                    {{ formErrors.form.join(', ') }}
                </div>

                <div>
                    <Label for="name">League Name</Label>
                    <Input id="name" v-model="form.name" required :disabled="isLoading" />
                    <InputError :message="formErrors.name?.join(', ')" />
                </div>

                <div>
                    <Label for="game_id">Game</Label>
                    <Select id="game_id" v-model="form.game_id" required :disabled="isLoading">
                        <option :value="null" disabled>-- Select Game --</option>
                        <option v-for="game in games" :key="game.id" :value="game.id">{{ game.name }}</option>
                    </Select>
                    <InputError :message="formErrors.game_id?.join(', ')" />
                </div>

                <div>
                    <Label for="start_rating">Starting Rating</Label>
                    <Input id="start_rating" type="number" v-model.number="form.start_rating" required :disabled="isLoading" />
                    <InputError :message="formErrors.start_rating?.join(', ')" />
                </div>

                <div class="flex items-center space-x-2">
                    <input type="checkbox" id="has_rating" v-model="form.has_rating" :disabled="isLoading" class="rounded border-gray-300 text-primary shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50"/>
                    <Label for="has_rating">Enable Rating System</Label>
                    <InputError :message="formErrors.has_rating?.join(', ')" />
                </div>

                <div>
                    <Label for="details">Details (Optional)</Label>
                    <Textarea id="details" v-model="form.details" :disabled="isLoading" />
                    <InputError :message="formErrors.details?.join(', ')" />
                </div>

                <div>
                    <Label for="started_at">Start Date (Optional)</Label>
                    <Input id="started_at" type="datetime-local" v-model="form.started_at" :disabled="isLoading" />
                    <InputError :message="formErrors.started_at?.join(', ')" />
                </div>
                <div>
                    <Label for="finished_at">End Date (Optional)</Label>
                    <Input id="finished_at" type="datetime-local" v-model="form.finished_at" :disabled="isLoading" />
                    <InputError :message="formErrors.finished_at?.join(', ')" />
                </div>


                <div>
                    <Label for="winners_rule">Rating Rule for Winners (JSON)</Label>
                    <Textarea id="winners_rule" v-model="form.rating_change_for_winners_rule" rows="6" :disabled="isLoading || !form.has_rating" />
                    <InputError :message="(formErrors.rating_change_for_winners_rule || formErrors.rating_rules)?.join(', ')" />
                    <p class="text-xs text-gray-500 mt-1">Format: [{"range":[min, max],"strong":points,"weak":points}, ...]</p>
                </div>
                <div>
                    <Label for="losers_rule">Rating Rule for Losers (JSON)</Label>
                    <Textarea id="losers_rule" v-model="form.rating_change_for_losers_rule" rows="6" :disabled="isLoading || !form.has_rating" />
                    <InputError :message="(formErrors.rating_change_for_losers_rule || formErrors.rating_rules)?.join(', ')" />
                    <p class="text-xs text-gray-500 mt-1">Format: [{"range":[min, max],"strong":points,"weak":points}, ...]</p>
                </div>

            </CardContent>
            <CardFooter>
                <Button type="submit" :disabled="isLoading">
                    <Spinner v-if="isLoading" class="w-4 h-4 mr-2" />
                    {{ isEditMode ? 'Save Changes' : 'Create League' }}
                </Button>
            </CardFooter>
        </form>
    </Card>
</template>
