<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { useAuth } from '@/composables/useAuth';
import { useLeagues } from '@/composables/useLeagues';
import { onMounted, ref } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui';

defineOptions({
    layout: AuthenticatedLayout
});

const { user } = useAuth();
const leagues = useLeagues();
const recentLeagues = ref([]);
const yourLeagues = ref([]);
const isLoadingLeagues = ref(false);

onMounted(async () => {
    isLoadingLeagues.value = true;
    try {
        const { data, execute } = leagues.fetchLeagues();
        await execute();

        if (data.value) {
            recentLeagues.value = data.value.slice(0, 5); // Get 5 most recent leagues

            // If user is authenticated, filter their leagues
            if (user.value?.id) {
                // This is a placeholder - in reality, you'd need a dedicated API endpoint
                // to fetch leagues the user is a member of
                yourLeagues.value = data.value.filter(league =>
                    // This is a placeholder condition - would need proper implementation
                    league.player_ids?.includes(user.value.id)
                ).slice(0, 3);
            }
        }
    } catch (error) {
        console.error("Failed to load leagues:", error);
    } finally {
        isLoadingLeagues.value = false;
    }
});
</script>

<template>
    <Head title="Dashboard" />

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Welcome Section -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg mb-6 dark:bg-gray-800">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h1 class="text-2xl font-semibold mb-4">Welcome back, {{ user?.firstname || 'User' }}!</h1>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
                        <div class="bg-indigo-50 rounded-lg p-6 dark:bg-indigo-900/30">
                            <h3 class="text-lg font-medium text-indigo-800 dark:text-indigo-300 mb-2">Join Leagues</h3>
                            <p class="text-indigo-600 dark:text-indigo-400 mb-4">Find and join billiard leagues that match your skill level and game preferences.</p>
                            <Link href="/leagues" class="text-indigo-700 dark:text-indigo-300 font-medium hover:underline">Browse Leagues →</Link>
                        </div>
                        <div class="bg-emerald-50 rounded-lg p-6 dark:bg-emerald-900/30">
                            <h3 class="text-lg font-medium text-emerald-800 dark:text-emerald-300 mb-2">Challenge Players</h3>
                            <p class="text-emerald-600 dark:text-emerald-400 mb-4">Challenge other players to matches and improve your rating and ranking.</p>
                            <Link href="/leagues" class="text-emerald-700 dark:text-emerald-300 font-medium hover:underline">Find Opponents →</Link>
                        </div>
                        <div class="bg-amber-50 rounded-lg p-6 dark:bg-amber-900/30">
                            <h3 class="text-lg font-medium text-amber-800 dark:text-amber-300 mb-2">Track Progress</h3>
                            <p class="text-amber-600 dark:text-amber-400 mb-4">Monitor your performance, rating changes, and match history over time.</p>
                            <Link href="/profile" class="text-amber-700 dark:text-amber-300 font-medium hover:underline">View Profile →</Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Leagues -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Recent Leagues</CardTitle>
                        <CardDescription>New and active leagues you might be interested in</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="isLoadingLeagues" class="py-4 text-center text-gray-500 dark:text-gray-400">
                            Loading leagues...
                        </div>
                        <div v-else-if="recentLeagues.length === 0" class="py-4 text-center text-gray-500 dark:text-gray-400">
                            No leagues found. Check back later.
                        </div>
                        <ul v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                            <li v-for="league in recentLeagues" :key="league.id" class="py-3">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <h4 class="font-medium text-gray-900 dark:text-white">{{ league.name }}</h4>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Game: {{ league.game }}</p>
                                    </div>
                                    <Link :href="`/leagues/${league.id}`" class="text-sm text-blue-600 hover:underline dark:text-blue-400">
                                        View
                                    </Link>
                                </div>
                            </li>
                        </ul>
                    </CardContent>
                </Card>

                <!-- Your Leagues -->
                <Card>
                    <CardHeader>
                        <CardTitle>Your Leagues</CardTitle>
                        <CardDescription>Leagues you've joined and are active in</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="isLoadingLeagues" class="py-4 text-center text-gray-500 dark:text-gray-400">
                            Loading your leagues...
                        </div>
                        <div v-else-if="yourLeagues.length === 0" class="py-4 text-center text-gray-500 dark:text-gray-400">
                            You haven't joined any leagues yet.
                            <Link href="/leagues" class="block mt-2 text-blue-600 dark:text-blue-400 hover:underline">Browse leagues to join</Link>
                        </div>
                        <ul v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                            <li v-for="league in yourLeagues" :key="league.id" class="py-3">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <h4 class="font-medium text-gray-900 dark:text-white">{{ league.name }}</h4>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            Your Rating: <span class="font-semibold">{{ league.your_rating || 'N/A' }}</span>
                                        </p>
                                    </div>
                                    <Link :href="`/leagues/${league.id}`" class="text-sm text-blue-600 hover:underline dark:text-blue-400">
                                        View
                                    </Link>
                                </div>
                            </li>
                        </ul>
                    </CardContent>
                </Card>
            </div>

            <!-- Admin Actions (if admin) -->
            <Card v-if="user?.is_admin">
                <CardHeader>
                    <CardTitle>Admin Actions</CardTitle>
                    <CardDescription>Manage leagues and system settings</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex flex-wrap gap-4">
                        <Link href="/leagues/create" class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700 active:bg-purple-800 focus:outline-none focus:border-purple-800 focus:ring ring-purple-300 disabled:opacity-25 transition">
                            Create New League
                        </Link>
                        <Link href="/admin/users" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition">
                            Manage Users
                        </Link>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
