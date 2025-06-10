<script lang="ts" setup>
import {Card, CardContent, CardHeader, CardTitle} from '@/Components/ui';
import type {TournamentGroup} from '@/types/tournament';

const props = defineProps<{ groups: TournamentGroup[] }>();
</script>

<template>
    <div class="space-y-6">
        <Card v-for="group in props.groups" :key="group.id">
            <CardHeader>
                <CardTitle>{{ group.display_name || group.name }}</CardTitle>
            </CardHeader>
            <CardContent>
                <table v-if="group.standings" class="min-w-full text-sm">
                    <thead>
                    <tr class="border-b dark:border-gray-700 text-left">
                        <th class="px-2 py-1">#</th>
                        <th class="px-2 py-1">{{ $t('Player') }}</th>
                        <th class="px-2 py-1 text-center">W</th>
                        <th class="px-2 py-1 text-center">L</th>
                        <th class="px-2 py-1 text-center">±</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="row in group.standings" :key="row.participant_id" class="border-b dark:border-gray-700">
                        <td class="px-2 py-1">{{ row.position }}</td>
                        <td class="px-2 py-1">{{ row.participant_name }}</td>
                        <td class="px-2 py-1 text-center">{{ row.wins }}</td>
                        <td class="px-2 py-1 text-center">{{ row.losses }}</td>
                        <td class="px-2 py-1 text-center">{{ row.games_difference }}</td>
                    </tr>
                    </tbody>
                </table>
                <div v-else class="text-center text-gray-500 py-4">{{ $t('No standings yet') }}</div>
            </CardContent>
        </Card>
    </div>
</template>
