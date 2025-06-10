<script lang="ts" setup>
import {Card, CardContent, CardHeader, CardTitle} from '@/Components/ui';
import type {TournamentBracket, TournamentMatch} from '@/types/tournament';

const props = defineProps<{ brackets: TournamentBracket[] }>();

const roundsForBracket = (bracket: TournamentBracket) => {
    const map = new Map<number, TournamentMatch[]>();
    bracket.matches?.forEach(m => {
        if (!map.has(m.round_number)) map.set(m.round_number, []);
        map.get(m.round_number)!.push(m);
    });
    return Array.from(map.entries()).sort((a,b) => a[0]-b[0]);
};
</script>

<template>
    <div class="space-y-6">
        <Card v-for="bracket in props.brackets" :key="bracket.id">
            <CardHeader>
                <CardTitle>{{ bracket.bracket_type_display }}</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="flex space-x-6 overflow-auto">
                    <div
                        v-for="([round, matches]) in roundsForBracket(bracket)"
                        :key="round"
                        class="space-y-4"
                    >
                        <div class="font-semibold text-center">{{ $t('Round') }} {{ round }}</div>
                        <div
                            v-for="match in matches"
                            :key="match.id"
                            class="w-48 rounded border p-2 text-sm"
                        >
                            <div>{{ match.participant_1_name || 'TBD' }} vs {{ match.participant_2_name || 'TBD' }}</div>
                            <div class="text-xs text-gray-500">{{ match.status_display }}</div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
