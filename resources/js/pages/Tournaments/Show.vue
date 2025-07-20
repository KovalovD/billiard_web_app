<!-- resources/js/Pages/Tournaments/Show.vue -->
<script lang="ts" setup>
import {Button, Card, CardContent, CardDescription, CardHeader, CardTitle, Spinner} from '@/Components/ui';
import TournamentApplicationCard from '@/Components/Tournament/TournamentApplicationCard.vue';
import SingleEliminationBracket from '@/Components/Tournament/SingleEliminationBracket.vue';
import DoubleEliminationBracket from '@/Components/Tournament/DoubleEliminationBracket.vue';
import TablesManager from '@/Components/Tournament/TablesManager.vue';
import MatchesList from '@/Components/Tournament/MatchesList.vue';
import {useAuth} from '@/composables/useAuth';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {apiClient} from '@/lib/apiClient';
import type {Tournament, TournamentGroup, TournamentMatch, TournamentPlayer} from '@/types/api';
import {Head, Link} from '@inertiajs/vue3';
import {useLocale} from '@/composables/useLocale';
import TournamentRegistrationModal from '@/Components/Tournament/TournamentRegistrationModal.vue';
import {
    ArrowLeftIcon,
    CalendarIcon,
    ClipboardListIcon,
    GitBranchIcon,
    LayersIcon,
    LogInIcon,
    MapPinIcon,
    MenuIcon,
    MonitorIcon,
    PencilIcon,
    PlayIcon,
    StarIcon,
    TrophyIcon,
    UserCheckIcon,
    UserPlusIcon,
    UsersIcon
} from 'lucide-vue-next';
import {computed, onMounted, ref} from 'vue';
import DataTable from '@/Components/ui/data-table/DataTable.vue';
import StageTransition from "@/Components/Tournament/StageTransition.vue";
import {useSeo} from "@/composables/useSeo";
import UserAvatar from "@/Components/Core/UserAvatar.vue";
import RoundRobinStandings from "@/Components/Tournament/RoundRobinStandings.vue";

defineOptions({layout: AuthenticatedLayout});

const props = defineProps<{
    tournamentId: number | string;
}>();

const showRegistrationModal = ref(false);
const showMobileMenu = ref(false);
const showMobileAdminMenu = ref(false);
const {isAdmin, isAuthenticated, user} = useAuth();
const {t} = useLocale();

const tournament = ref<Tournament | null>(null);
const players = ref<TournamentPlayer[]>([]);
const matches = ref<TournamentMatch[]>([]);
const groups = ref<TournamentGroup[]>([]);
const isLoadingTournament = ref(true);
const isLoadingPlayers = ref(true);
const isLoadingMatches = ref(false);
const isLoadingGroups = ref(false);
const error = ref<string | null>(null);
const showTablesModal = ref(false);
const {
    setSeoMeta,
    generateBreadcrumbJsonLd,
    getAlternateLanguageUrls,
    generateGameJsonLd
} = useSeo();

// Olympic bracket tab state
const activeOlympicTab = ref<'first-stage' | 'olympic-stage'>('first-stage');

// Get initial tab from URL query parameter
const getInitialTab = (): 'info' | 'players' | 'bracket' | 'matches' | 'groups' | 'results' | 'applications' => {
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    const validTabs = ['info', 'players', 'bracket', 'matches', 'groups', 'results', 'applications'];
    return validTabs.includes(tabParam as string) ? tabParam as any : 'info';
};

const activeTab = ref<'info' | 'players' | 'bracket' | 'matches' | 'groups' | 'results' | 'applications'>(getInitialTab());

// Handle tab change and update URL
const switchTab = (tab: 'info' | 'players' | 'bracket' | 'matches' | 'groups' | 'results' | 'applications') => {
    activeTab.value = tab;
    showMobileMenu.value = false;
    // Update URL without page reload
    const url = new URL(window.location.href);
    if (tab === 'info') {
        url.searchParams.delete('tab');
    } else {
        url.searchParams.set('tab', tab);
    }

    window.history.replaceState({}, '', url.toString());
};

// Handle Olympic tab change
const switchOlympicTab = (tab: 'first-stage' | 'olympic-stage') => {
    activeOlympicTab.value = tab;
};

const handleRegistrationSuccess = () => {
    showRegistrationModal.value = false;
    fetchTournament();
    fetchPlayers();
};

// Get current user ID
const currentUserId = user.value?.id;

// Helper function to get birth year from birthdate
const getBirthYear = (birthdate: string | null | undefined): string | null => {
    if (!birthdate) return null;
    const year = new Date(birthdate).getFullYear();
    return !isNaN(year) ? year.toString() : null;
};

const sortedPlayers = computed(() => {
    return [...players.value].sort((a, b) => {
        const statusOrder = {confirmed: 1, applied: 2, rejected: 3};
        const aStatus = statusOrder[a.status as keyof typeof statusOrder] || 4;
        const bStatus = statusOrder[b.status as keyof typeof statusOrder] || 4;

        if (aStatus !== bStatus) {
            return aStatus - bStatus;
        }

        if (a.position !== null && b.position !== null && a.position != undefined && b.position !== undefined) {
            return a.position - b.position;
        }
        if (a.position !== null) return -1;
        if (b.position !== null) return 1;

        return new Date(a.applied_at || a.registered_at || 0).getTime() -
            new Date(b.applied_at || b.registered_at || 0).getTime();
    });
});

// Check if tournament is round robin
const isRoundRobin = computed(() => {
    return tournament.value?.tournament_type === 'round_robin';
});

// Check if we should show round robin standings
const canViewRoundRobin = computed(() => {
    return isRoundRobin.value &&
        (tournament.value?.stage === 'bracket' ||
            tournament.value?.status === 'active' ||
            tournament.value?.status === 'completed');
});

const confirmedPlayers = computed(() =>
    sortedPlayers.value.filter(p => p.is_confirmed)
);

const pendingApplications = computed(() =>
    sortedPlayers.value.filter(p => p.is_pending)
);

const rejectedApplications = computed(() =>
    sortedPlayers.value.filter(p => p.is_rejected)
);

const completedPlayers = computed(() => {
    return sortedPlayers.value.filter(p => p.position !== null);
});

// Computed properties for stage-based navigation
const showSeedingButton = computed(() => {
    return isAuthenticated.value &&
        isAdmin.value &&
        tournament.value?.stage === 'seeding' &&
        tournament.value?.status !== 'completed';
});

const showBracketButton = computed(() => {
    return isAuthenticated.value &&
        isAdmin.value &&
        tournament.value &&
        ['single_elimination', 'double_elimination', 'double_elimination_full', 'groups_playoff', 'team_groups_playoff', 'olympic_double_elimination', 'round_robin'].includes(tournament.value.tournament_type) &&
        (tournament.value.stage === 'bracket' || tournament.value.brackets_generated) &&
        tournament.value.status !== 'completed';
});

const showGroupsButton = computed(() => {
    return isAuthenticated.value &&
        isAdmin.value &&
        tournament.value &&
        ['groups', 'groups_playoff', 'team_groups_playoff'].includes(tournament.value.tournament_type) &&
        tournament.value.stage === 'group' &&
        tournament.value.status !== 'completed';
});

const showMatchesButton = computed(() => {
    return isAuthenticated.value &&
        isAdmin.value &&
        tournament.value &&
        tournament.value.status === 'active';
});

// Check if bracket tab should be shown for non-admins
const canViewBracket = computed(() => {
    return tournament.value &&
        ['single_elimination', 'double_elimination', 'double_elimination_full', 'groups_playoff', 'team_groups_playoff', 'olympic_double_elimination'].includes(tournament.value.tournament_type) &&
        (tournament.value.stage === 'bracket' || tournament.value.brackets_generated || tournament.value.status === 'completed');
});

// Check if groups tab should be shown for non-admins
const canViewGroups = computed(() => {
    return tournament.value &&
        ['groups', 'groups_playoff', 'team_groups_playoff'].includes(tournament.value.tournament_type) &&
        (tournament.value.stage === 'group' || tournament.value.stage === 'bracket' || tournament.value.status === 'completed') &&
        groups.value.length > 0;
});

// Check if matches tab should be shown for non-admins
const canViewMatches = computed(() => {
    return tournament.value &&
        (tournament.value.status === 'active' || tournament.value.status === 'completed') &&
        matches.value.length > 0;
});

// Check if tournament is double elimination
const isDoubleElimination = computed(() => {
    return tournament.value?.tournament_type === 'double_elimination' ||
        tournament.value?.tournament_type === 'double_elimination_full';
});

// Check if tournament is Olympic double elimination
const isOlympicDoubleElimination = computed(() => {
    return tournament.value?.tournament_type === 'olympic_double_elimination';
});

// Split matches by Olympic stage
const firstStageMatches = computed(() => {
    if (!isOlympicDoubleElimination.value) return matches.value;
    return matches.value.filter(m => m.metadata?.olympic_stage === 'first' || m.match_code?.startsWith('FS_'));
});

const olympicStageMatches = computed(() => {
    if (!isOlympicDoubleElimination.value) return [];
    return matches.value.filter(m => m.metadata?.olympic_stage === 'second' || m.match_code?.startsWith('OS_'));
});

// Filter and sort matches for display
const displayMatches = computed(() => {
    return [...matches.value].sort((a, b) => {
        // Sort by status priority
        const statusOrder = ['in_progress', 'verification', 'ready', 'pending', 'completed'];
        const statusDiff = statusOrder.indexOf(a.status) - statusOrder.indexOf(b.status);
        if (statusDiff !== 0) return statusDiff;

        // Then by scheduled time
        if (a.scheduled_at && b.scheduled_at) {
            return new Date(a.scheduled_at).getTime() - new Date(b.scheduled_at).getTime();
        }

        // Then by match code
        return (a.match_code || '').localeCompare(b.match_code || '');
    });
});

// Group matches by group code
const matchesByGroup = computed(() => {
    const grouped: Record<string, TournamentMatch[]> = {};

    matches.value.filter(m => m.stage === 'group').forEach(match => {
        // Find group for this match
        const group = groups.value.find(g => {
            const groupPlayers = players.value.filter(p => p.group_code === g.group_code);
            return groupPlayers.some(p => p.id === match.player1_id || p.id === match.player2_id);
        });

        if (group) {
            if (!grouped[group.group_code]) {
                grouped[group.group_code] = [];
            }
            grouped[group.group_code].push(match);
        }
    });

    return grouped;
});

const getStatusBadgeClass = (status: string): string => {
    switch (status) {
        case 'upcoming':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
        case 'active':
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
        case 'completed':
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
        case 'cancelled':
            return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
};

const getPlayerStatusBadgeClass = (status: string): string => {
    switch (status) {
        case 'applied':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300';
        case 'confirmed':
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
        case 'rejected':
            return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
        case 'eliminated':
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
        default:
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
    }
};

const getPositionBadgeClass = (position: number): string => {
    switch (position) {
        case 1:
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300';
        case 2:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200';
        case 3:
            return 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300';
        default:
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
    }
};

const getMatchStatusBadgeClass = (status: string): string => {
    switch (status) {
        case 'completed':
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
        case 'in_progress':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300';
        case 'verification':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300';
        case 'ready':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300';
    }
};

const formatDateTime = (dateString: string | undefined): string => {
    if (!dateString) {
        return ''
    }

    const date = new Date(dateString);
    const isMobile = window.innerWidth < 640;

    if (isMobile) {
        return date.toLocaleString('uk-UK', {
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    return date.toLocaleString('uk-UK', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatDate = (dateString: string | undefined): string => {
    if (!dateString) {
        return ''
    }
    const date = new Date(dateString);
    const isMobile = window.innerWidth < 640;

    if (isMobile) {
        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: '2-digit'
        });
    }

    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

const formatCurrency = (amount: number): string => {
    return amount.toLocaleString('uk-UA', {
        style: 'currency',
        currency: 'UAH'
    }).replace('UAH', '₴').replace('грн', '₴');
};

const fetchTournament = async () => {
    isLoadingTournament.value = true;
    error.value = null;

    try {
        tournament.value = await apiClient<Tournament>(`/api/tournaments/${props.tournamentId}`);
    } catch (err: any) {
        error.value = err.message || t('Failed to load tournament');
    } finally {
        isLoadingTournament.value = false;
    }
};

const fetchPlayers = async () => {
    isLoadingPlayers.value = true;

    try {
        players.value = await apiClient<TournamentPlayer[]>(`/api/tournaments/${props.tournamentId}/players`);
    } catch (err: any) {
        console.error('Failed to load players:', err);
    } finally {
        isLoadingPlayers.value = false;
    }
};

const fetchMatches = async () => {
    isLoadingMatches.value = true;
    try {
        // Use the public endpoint for non-admins
        const endpoint = isAdmin.value
            ? `/api/admin/tournaments/${props.tournamentId}/matches`
            : `/api/tournaments/${props.tournamentId}/matches`;
        matches.value = await apiClient<TournamentMatch[]>(endpoint);
    } catch (err: any) {
        console.error('Failed to load matches:', err);
        matches.value = [];
    } finally {
        isLoadingMatches.value = false;
    }
};

const fetchGroups = async () => {
    if (!canViewGroups.value) return;

    isLoadingGroups.value = true;
    try {
        groups.value = await apiClient<TournamentGroup[]>(`/api/tournaments/${props.tournamentId}/groups`);
    } catch (err: any) {
        console.error('Failed to load groups:', err);
        groups.value = [];
    } finally {
        isLoadingGroups.value = false;
    }
};

const handleApplicationUpdated = () => {
    fetchTournament();
    fetchPlayers();
};

// Get players in a specific group
const getPlayersInGroup = (groupCode: string) => {
    return players.value.filter(p => p.group_code === groupCode)
        .sort((a, b) => {
            // Sort by group position if available
            if (a.group_position && b.group_position) {
                return a.group_position - b.group_position;
            }
            // Otherwise sort by wins/losses
            const aDiff = a.group_wins - a.group_losses;
            const bDiff = b.group_wins - b.group_losses;
            if (aDiff !== bDiff) return bDiff - aDiff;

            // Then by games difference
            return b.group_games_diff - a.group_games_diff;
        });
};

// Add columns definition before the template
const columns = computed(() => [
    {
        key: 'position',
        label: t('Position'),
        align: 'left' as const,
        render: (player: TournamentPlayer) => ({
            position: player.position,
            isWinner: player.is_winner
        })
    },
    {
        key: 'player',
        label: t('Player'),
        align: 'left' as const,
        render: (player: TournamentPlayer) => ({
            name: `${player.user?.firstname} ${player.user?.lastname}`,
            isWinner: player.is_winner
        })
    },
    {
        key: 'rating',
        label: t('Rating Points'),
        align: 'center' as const,
        render: (player: TournamentPlayer) => ({
            points: player.rating_points
        })
    },
    {
        key: 'bonus',
        label: t('Bonus'),
        align: 'right' as const,
        render: (player: TournamentPlayer) => ({
            amount: player.bonus_amount
        })
    },
    {
        key: 'prize',
        label: t('Prize'),
        align: 'right' as const,
        render: (player: TournamentPlayer) => ({
            amount: player.prize_amount
        })
    },
    {
        key: 'achievement',
        label: t('Achievement'),
        align: 'right' as const,
        render: (player: TournamentPlayer) => ({
            amount: player.achievement_amount
        })
    },
    {
        key: 'total',
        label: t('Total'),
        align: 'right' as const,
        render: (player: TournamentPlayer) => ({
            amount: player.total_amount
        })
    }
]);

// Update the playerColumns computed property to add birth year and location
const playerColumns = computed(() => [
    {
        key: 'seed',
        label: t('#seed'),
        align: 'center' as const,
        width: '60px',
        sortable: true,
        sortKey: 'seed_number',
        sortFn: (a: TournamentPlayer, b: TournamentPlayer) => {
            const aSeed = Number(a.seed_number) || 999;
            const bSeed = Number(b.seed_number) || 999;
            return aSeed - bSeed;
        },
        render: (player: TournamentPlayer) => ({
            seed: player.seed_number
        })
    },
    {
        key: 'player',
        label: t('Player'),
        align: 'left' as const,
        sortable: true,
        sortFn: (a: TournamentPlayer, b: TournamentPlayer) => {
            const aName = `${a.user?.lastname} ${a.user?.firstname}`;
            const bName = `${b.user?.lastname} ${b.user?.firstname}`;
            return aName.localeCompare(bName);
        },
        render: (player: TournamentPlayer) => ({
            name: `${player.user?.firstname} ${player.user?.lastname}`,
            birthYear: getBirthYear(player.user?.birthdate),
            location: player.user?.home_city ?
                `${player.user.home_city.name}${player.user.home_city.country ? ', ' + player.user.home_city.country.name : ''}`
                : null
        })
    },
    {
        key: 'rating',
        label: t('Rating'),
        align: 'center' as const,
        width: '100px',
        sortable: true,
        sortFn: (a: TournamentPlayer, b: TournamentPlayer) => {
            // Convert to numbers for proper numeric sorting
            const aRating = Number(a.rating) || 0;
            const bRating = Number(b.rating) || 0;
            return aRating - bRating;
        },
        render: (player: TournamentPlayer) => ({
            rating: player.rating || null,
            position: null
        })
    },
    {
        key: 'club',
        label: t('Club'),
        align: 'left' as const,
        sortable: true,
        sortFn: (a: TournamentPlayer, b: TournamentPlayer) => {
            const aClub = a.user?.home_club?.name || '';
            const bClub = b.user?.home_club?.name || '';
            return aClub.localeCompare(bClub);
        },
        render: (player: TournamentPlayer) => ({
            name: player.user?.home_club?.name || null
        })
    },
    {
        key: 'status',
        label: t('Status'),
        align: 'center' as const,
        width: '100px',
        sortable: true,
        render: (player: TournamentPlayer) => ({
            status: player.status,
            status_display: player.status_display
        })
    },
    {
        key: 'registered',
        label: t('Registered'),
        align: 'center' as const,
        width: '120px',
        sortable: true,
        sortFn: (a: TournamentPlayer, b: TournamentPlayer) => {
            const aDate = new Date(a.confirmed_at || a.registered_at || 0).getTime();
            const bDate = new Date(b.confirmed_at || b.registered_at || 0).getTime();
            return aDate - bDate;
        },
        render: (player: TournamentPlayer) => ({
            date: formatDate(player.confirmed_at || player.registered_at)
        })
    }
]);

const getPlayerRowAttributes = (player: TournamentPlayer): Record<string, any> => {
    const playerSlug = player.user?.slug || '';
    const openPlayer = () => window.open(`/players/${playerSlug}`, '_blank', 'noopener');

    return {
        'data-player-slug': playerSlug,
        role: 'button',
        tabindex: '0',
        'aria-label': `${t('View')} ${player.user?.firstname} ${player.user?.lastname} ${t('profile')}`,
        onClick: (e: MouseEvent) => {
            const target = e.target as HTMLElement;
            if (target.closest('button,a,input,select,textarea')) return;
            openPlayer();
        },
        onKeydown: (e: KeyboardEvent) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openPlayer();
            }
        }
    };
};

// Get row class with click handling
const getPlayerRowClass = (): string => {
    return 'cursor-pointer transition-all duration-200 hover:bg-gray-50 dark:hover:bg-gray-700/50';
};

onMounted(() => {
    fetchTournament().then(() => {
        if (tournament.value) {
            const currentPath = window.location.pathname;
            const prizeAmount = tournament.value.prize_pool || 0;

            setSeoMeta({
                title: t(':name - :type Tournament | :date | Prize Pool :prize', {
                    name: tournament.value.name,
                    type: tournament.value.tournament_type_display,
                    date: formatDate(tournament.value.start_date),
                    prize: formatCurrency(prizeAmount)
                }),
                description: t('tournament_meta_description', {
                    name: tournament.value.name,
                    type: tournament.value.tournament_type_display,
                    city: tournament.value.city ? `${tournament.value.city.name}, ${tournament.value.city.country?.name}` : t('Multiple locations'),
                    prize: formatCurrency(prizeAmount),
                    players: tournament.value.confirmed_players_count,
                    fee: tournament.value.entry_fee ? formatCurrency(tournament.value.entry_fee) : t('Free'),
                    dates: `${formatDate(tournament.value.start_date)} - ${formatDate(tournament.value.end_date)}`,
                    details: tournament.value.details ? tournament.value.details.substring(0, 80) + '... ' : ''
                }),
                keywords: [
                    tournament.value.name,
                    'billiard tournament', 'бильярдный турнир',
                    tournament.value.game?.name || 'pool tournament',
                    tournament.value.tournament_type_display,
                    tournament.value.city?.name || '',
                    tournament.value.city?.country?.name || '',
                    'prize pool ' + formatCurrency(prizeAmount),
                    'tournament registration', 'регистрация на турнир',
                    'professional billiards', 'профессиональный бильярд',
                    'cash prizes', 'денежные призы',
                    'ranking points', 'рейтинговые очки',
                    'billiard championship', 'чемпионат по бильярду',
                    'pool competition', 'соревнование по пулу',
                    tournament.value.club?.name || '',
                    'WinnerBreak tournament', 'турнир ВиннерБрейк'
                ].filter(k => k),
                ogType: 'event',
                ogImage: tournament.value.picture || '/images/tournament-default.jpg',
                ogTitle: tournament.value.name,
                ogDescription: tournament.value.short_description || tournament.value.details,
                canonicalUrl: `${window.location.origin}${currentPath}`,
                robots: 'index, follow',
                author: tournament.value.organizer || 'WinnerBreak',
                alternateLanguages: getAlternateLanguageUrls(currentPath),
                additionalMeta: [
                    {property: 'event:start_time', content: tournament.value.start_date},
                    {property: 'event:end_time', content: tournament.value.end_date},
                    {property: 'place:location:latitude', content: '49.839683'},
                    {property: 'place:location:longitude', content: '24.029717'},
                    {name: 'price', content: tournament.value.entry_fee?.toString() || '0'},
                    {name: 'priceCurrency', content: 'UAH'}
                ],
                jsonLd: {
                    "@context": "https://schema.org",
                    "@graph": [
                        generateBreadcrumbJsonLd([
                            {name: t('Home'), url: window.location.origin},
                            {name: t('Tournaments'), url: `${window.location.origin}/tournaments`},
                            {
                                name: tournament.value.name,
                                url: `${window.location.origin}/tournaments/${tournament.value.slug}`
                            }
                        ]),
                        {
                            "@type": "SportsEvent",
                            "@id": `${window.location.origin}/tournaments/${tournament.value.slug}#event`,
                            "name": tournament.value.name,
                            "description": tournament.value.details || t('Professional billiard tournament'),
                            "startDate": tournament.value.start_date,
                            "endDate": tournament.value.end_date,
                            "eventStatus": "https://schema.org/EventScheduled",
                            "eventAttendanceMode": "https://schema.org/OfflineEventAttendanceMode",
                            "location": tournament.value.city ? {
                                "@type": "Place",
                                "name": tournament.value.club?.name || tournament.value.city.name,
                                "address": {
                                    "@type": "PostalAddress",
                                    "streetAddress": tournament.value.club?.address || "",
                                    "addressLocality": tournament.value.city.name,
                                    "addressCountry": tournament.value.city.country?.name
                                }
                            } : undefined,
                            "organizer": {
                                "@type": "Organization",
                                "name": tournament.value.organizer || 'WinnerBreak',
                                "url": window.location.origin
                            },
                            "performer": players.value?.map(p => ({
                                "@type": "Person",
                                "name": `${p.user?.firstname} ${p.user?.lastname}`
                            })) || [],
                            "offers": {
                                "@type": "Offer",
                                "url": `${window.location.origin}/tournaments/${tournament.value.slug}`,
                                "price": tournament.value.entry_fee || 0,
                                "priceCurrency": "UAH",
                                "availability": tournament.value.status === 'upcoming' ? "https://schema.org/InStock" : "https://schema.org/SoldOut",
                                "validFrom": tournament.value.created_at
                            },
                            "sport": tournament.value.game?.name || "Billiards",
                            "maximumAttendeeCapacity": tournament.value.max_participants || 100,
                            "remainingAttendeeCapacity": tournament.value.max_participants ? tournament.value.max_participants - tournament.value.confirmed_players_count : 100
                        },
                        generateGameJsonLd({
                            name: tournament.value.game?.name || "Billiards",
                            description: tournament.value.tournament_type_display,
                            minPlayers: 2,
                            maxPlayers: tournament.value.max_participants || 64
                        })
                    ]
                }
            });
        }
    });
    fetchPlayers()
    fetchMatches();
    fetchGroups();
});

</script>

<template>
    <Head :title="tournament ? t('Tournament: :name', {name: tournament.name}) : t('Tournament')"/>

    <div class="py-4 sm:py-6 lg:py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <Link href="/tournaments">
                    <Button variant="outline" size="sm">
                        <ArrowLeftIcon class="mr-1.5 h-3.5 w-3.5"/>
                        <span class="hidden sm:inline">{{ t('Back to Tournaments') }}</span>
                        <span class="sm:hidden">{{ t('Back') }}</span>
                    </Button>
                </Link>

                <!-- Admin controls - Desktop -->
                <div v-if="isAuthenticated && isAdmin && tournament" class="hidden sm:flex flex-wrap gap-1.5">
                    <Link :href="`/admin/tournaments/${tournament.slug}/edit`">
                        <Button size="sm" variant="secondary">
                            <PencilIcon class="mr-1.5 h-3.5 w-3.5"/>
                            {{ t('Edit') }}
                        </Button>
                    </Link>

                    <Link :href="`/admin/tournaments/${tournament.slug}/players`">
                        <Button size="sm" variant="secondary">
                            <UserPlusIcon class="mr-1.5 h-3.5 w-3.5"/>
                            {{ t('Players') }}
                            <span v-if="tournament.pending_applications_count > 0"
                                  class="ml-1.5 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-white bg-red-600 rounded-full">
                                {{ tournament.pending_applications_count }}
                            </span>
                        </Button>
                    </Link>

                    <!-- Stage-based buttons -->
                    <Link v-if="showSeedingButton" :href="`/admin/tournaments/${tournament.slug}/seeding`">
                        <Button size="sm" variant="secondary">
                            <StarIcon class="mr-1.5 h-3.5 w-3.5"/>
                            {{ t('Seeding') }}
                        </Button>
                    </Link>

                    <Link v-if="showGroupsButton" :href="`/admin/tournaments/${tournament.slug}/groups`">
                        <Button size="sm" variant="secondary">
                            <LayersIcon class="mr-1.5 h-3.5 w-3.5"/>
                            {{ t('Groups') }}
                        </Button>
                    </Link>

                    <Link v-if="showBracketButton" :href="`/admin/tournaments/${tournament.slug}/bracket`">
                        <Button size="sm" variant="secondary">
                            <GitBranchIcon class="mr-1.5 h-3.5 w-3.5"/>
                            {{ t('Bracket') }}
                        </Button>
                    </Link>

                    <Link v-if="showMatchesButton" :href="`/admin/tournaments/${tournament.slug}/matches`">
                        <Button size="sm" variant="secondary">
                            <PlayIcon class="mr-1.5 h-3.5 w-3.5"/>
                            {{ t('Matches') }}
                        </Button>
                    </Link>

                    <Link :href="`/admin/tournaments/${tournament.slug}/results`">
                        <Button size="sm" variant="secondary">
                            <TrophyIcon class="mr-1.5 h-3.5 w-3.5"/>
                            {{ t('Results') }}
                        </Button>
                    </Link>

                    <Button
                        v-if="tournament.club"
                        size="sm"
                        variant="secondary"
                        @click="showTablesModal = true"
                    >
                        <MonitorIcon class="mr-1.5 h-3.5 w-3.5"/>
                        {{ t('Tables') }}
                    </Button>
                </div>

                <!-- Admin controls - Mobile Menu Button -->
                <Button
                    v-if="isAuthenticated && isAdmin && tournament"
                    size="sm"
                    variant="secondary"
                    class="sm:hidden"
                    @click="showMobileAdminMenu = !showMobileAdminMenu"
                >
                    <MenuIcon class="h-4 w-4"/>
                </Button>

                <!-- Login prompt for guests -->
                <div v-else-if="!isAuthenticated && tournament">
                    <Link :href="route('login')"
                          class="text-sm text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">
                        <LogInIcon class="mr-1 inline h-3.5 w-3.5"/>
                        {{ t('Login to participate') }}
                    </Link>
                </div>
            </div>

            <!-- Mobile Admin Menu -->
            <div
                v-if="isAuthenticated && isAdmin && tournament && showMobileAdminMenu"
                class="sm:hidden mb-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700"
            >
                <div class="grid grid-cols-2 gap-1.5">
                    <Link :href="`/admin/tournaments/${tournament.slug}/edit`">
                        <Button size="sm" variant="secondary" class="w-full">
                            <PencilIcon class="mr-1 h-3 w-3"/>
                            {{ t('Edit') }}
                        </Button>
                    </Link>

                    <Link :href="`/admin/tournaments/${tournament.slug}/players`">
                        <Button size="sm" variant="secondary" class="w-full">
                            <UserPlusIcon class="mr-1 h-3 w-3"/>
                            {{ t('Players') }}
                            <span v-if="tournament.pending_applications_count > 0"
                                  class="ml-1 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-white bg-red-600 rounded-full">
                                {{ tournament.pending_applications_count }}
                            </span>
                        </Button>
                    </Link>

                    <Link v-if="showSeedingButton" :href="`/admin/tournaments/${tournament.slug}/seeding`">
                        <Button size="sm" variant="secondary" class="w-full">
                            <StarIcon class="mr-1 h-3 w-3"/>
                            {{ t('Seeding') }}
                        </Button>
                    </Link>

                    <Link v-if="showGroupsButton" :href="`/admin/tournaments/${tournament.slug}/groups`">
                        <Button size="sm" variant="secondary" class="w-full">
                            <LayersIcon class="mr-1 h-3 w-3"/>
                            {{ t('Groups') }}
                        </Button>
                    </Link>

                    <Link v-if="showBracketButton" :href="`/admin/tournaments/${tournament.slug}/bracket`">
                        <Button size="sm" variant="secondary" class="w-full">
                            <GitBranchIcon class="mr-1 h-3 w-3"/>
                            {{ t('Bracket') }}
                        </Button>
                    </Link>

                    <Link v-if="showMatchesButton" :href="`/admin/tournaments/${tournament.slug}/matches`">
                        <Button size="sm" variant="secondary" class="w-full">
                            <PlayIcon class="mr-1 h-3 w-3"/>
                            {{ t('Matches') }}
                        </Button>
                    </Link>

                    <Link :href="`/admin/tournaments/${tournament.slug}/results`">
                        <Button size="sm" variant="secondary" class="w-full">
                            <TrophyIcon class="mr-1 h-3 w-3"/>
                            {{ t('Results') }}
                        </Button>
                    </Link>

                    <Button
                        v-if="tournament.club"
                        size="sm"
                        variant="secondary"
                        @click="showTablesModal = true; showMobileAdminMenu = false"
                    >
                        <MonitorIcon class="mr-1 h-3 w-3"/>
                        {{ t('Tables') }}
                    </Button>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="isLoadingTournament" class="flex justify-center py-8">
                <div class="text-center">
                    <Spinner class="mx-auto h-6 w-6 text-indigo-600"/>
                    <p class="mt-1.5 text-sm text-gray-500">{{ t('Loading tournament...') }}</p>
                </div>
            </div>

            <!-- Error State -->
            <div v-else-if="error"
                 class="mb-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-3">
                <p class="text-sm text-red-600 dark:text-red-400">{{
                        t('Error loading tournament: :error', {error})
                    }}</p>
            </div>

            <!-- Tournament Content -->
            <template v-else-if="tournament">
                <!-- Tournament Header Card -->
                <Card class="mb-6 shadow-lg">
                    <div
                        class="bg-gradient-to-r from-gray-50 to-indigo-50 dark:from-gray-800 dark:to-gray-700 p-4 sm:p-6">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                            <div class="flex-1">
                                <div class="flex items-center gap-2.5 mb-2">
                                    <div
                                        class="h-10 w-10 rounded-full bg-indigo-600 flex items-center justify-center shadow-md">
                                        <TrophyIcon class="h-5 w-5 text-white"/>
                                    </div>
                                    <div>
                                        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2.5">
                                            {{ tournament.name }}
                                            <span
                                                :class="[
                                                'inline-flex px-2.5 py-0.5 text-xs font-medium rounded-full',
                                                getStatusBadgeClass(tournament.status)
                                            ]"
                                            >
                                            {{ tournament.status_display }}
                                        </span>
                                        </h1>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ tournament.game?.name || 'N/A' }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Tournament Info -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mt-3">
                                    <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                        <CalendarIcon class="h-3.5 w-3.5 mr-1.5"/>
                                        <span>{{ formatDateTime(tournament.start_date) }}</span>
                                    </div>
                                    <div v-if="tournament.city"
                                         class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                        <MapPinIcon class="h-3.5 w-3.5 mr-1.5"/>
                                        <span>{{ tournament.city.name }}, {{ tournament.city.country?.name }}</span>
                                    </div>
                                </div>

                                <!-- Stats Grid -->
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mt-4">
                                    <div class="text-center sm:text-left">
                                        <div class="flex items-center gap-1.5 justify-center sm:justify-start">
                                            <UsersIcon class="h-3.5 w-3.5 text-gray-400"/>
                                            <span class="text-xl font-bold text-gray-900 dark:text-white">{{
                                                    tournament.confirmed_players_count
                                                }}</span>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('players') }}</p>
                                    </div>
                                    <div v-if="tournament.prize_pool" class="text-center sm:text-left">
                                        <div class="text-xl font-bold text-green-600 dark:text-green-400">
                                            {{ tournament.prize_pool }}
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('Prize Pool') }}</p>
                                    </div>
                                    <div v-if="tournament.stage" class="text-center sm:text-left">
                                        <div class="text-base font-semibold text-gray-900 dark:text-white">
                                            {{ tournament.stage_display }}
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('Stage') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </Card>

                <StageTransition
                    v-if="isAuthenticated && isAdmin && tournament && tournament.status !== 'completed'"
                    :tournament="tournament"
                    @updated="fetchTournament"
                />

                <!-- Tournament Application Card - Only show to authenticated users -->
                <div v-if="isAuthenticated && tournament.requires_application && tournament.status === 'upcoming'"
                     class="mb-6">
                    <TournamentApplicationCard
                        :tournament="tournament"
                        @application-updated="handleApplicationUpdated"
                    />
                </div>

                <!-- Guest application prompt -->
                <div v-else-if="!isAuthenticated && tournament.requires_application && tournament.status === 'upcoming'"
                     class="mb-6">
                    <Card class="border-l-4 border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20">
                        <CardContent class="p-4">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <div>
                                    <h3 class="text-base font-medium text-indigo-800 dark:text-indigo-300">
                                        {{ t('Tournament Registration') }}
                                    </h3>
                                    <p class="text-sm text-indigo-600 dark:text-indigo-400">
                                        {{ t('Register now to participate in this tournament.') }}
                                    </p>
                                </div>
                                <div class="flex gap-2">
                                    <Link :href="route('login')">
                                        <Button variant="outline" size="sm">
                                            <LogInIcon class="mr-1.5 h-3.5 w-3.5"/>
                                            {{ t('Login') }}
                                        </Button>
                                    </Link>
                                    <Button @click="showRegistrationModal = true" size="sm">
                                        <UserPlusIcon class="mr-1.5 h-3.5 w-3.5"/>
                                        {{ t('Register & Apply') }}
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Tab Navigation -->
                <nav class="mb-4 border-b border-gray-200 dark:border-gray-700 overflow-x-auto" role="navigation"
                     aria-label="Tournament tabs">
                    <div class="-mb-px flex space-x-4 sm:space-x-6 min-w-max">
                        <button
                            id="tab-info"
                            :class="[
                                'py-3 px-1 text-sm font-medium border-b-2 transition-colors whitespace-nowrap',
                                activeTab === 'info'
                                    ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                            :aria-selected="activeTab === 'info'"
                            role="tab"
                            @click="switchTab('info')"
                        >
                            <span class="flex items-center gap-1.5">
                                <ClipboardListIcon class="h-3.5 w-3.5"/>
                                {{ t('Info') }}
                            </span>
                        </button>
                        <button
                            id="tab-players"
                            :class="[
                                'py-3 px-1 text-sm font-medium border-b-2 transition-colors whitespace-nowrap',
                                activeTab === 'players'
                                    ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                            :aria-selected="activeTab === 'players'"
                            role="tab"
                            @click="switchTab('players')"
                        >
                            <span class="flex items-center gap-1.5">
                                <UsersIcon class="h-3.5 w-3.5"/>
                                {{ t('Players') }}
                                <span class="text-xs bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded-full">
                                    {{ tournament.confirmed_players_count }}
                                </span>
                            </span>
                        </button>
                        <button
                            v-if="canViewGroups"
                            id="tab-groups"
                            :class="[
                                'py-3 px-1 text-sm font-medium border-b-2 transition-colors whitespace-nowrap',
                                activeTab === 'groups'
                                    ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                            :aria-selected="activeTab === 'groups'"
                            role="tab"
                            @click="switchTab('groups')"
                        >
                            <span class="flex items-center gap-1.5">
                                <LayersIcon class="h-3.5 w-3.5"/>
                                {{ t('Groups') }}
                                <span class="text-xs bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded-full">
                                    {{ groups.length }}
                                </span>
                            </span>
                        </button>
                        <button
                            v-if="canViewBracket || canViewRoundRobin"
                            id="tab-bracket"
                            :class="[
                                'py-3 px-1 text-sm font-medium border-b-2 transition-colors whitespace-nowrap',
                                activeTab === 'bracket'
                                    ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                            :aria-selected="activeTab === 'bracket'"
                            role="tab"
                            @click="switchTab('bracket')"
                        >
                            <span class="flex items-center gap-1.5">
                                <GitBranchIcon v-if="!isRoundRobin" class="h-3.5 w-3.5"/>
                                <TrophyIcon v-else class="h-3.5 w-3.5"/>
                                {{ isRoundRobin ? t('Standings') : t('Bracket') }}
                            </span>
                        </button>
                        <button
                            v-if="canViewMatches"
                            id="tab-matches"
                            :class="[
                                'py-3 px-1 text-sm font-medium border-b-2 transition-colors whitespace-nowrap',
                                activeTab === 'matches'
                                    ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                            :aria-selected="activeTab === 'matches'"
                            role="tab"
                            @click="switchTab('matches')"
                        >
                            <span class="flex items-center gap-1.5">
                                <PlayIcon class="h-3.5 w-3.5"/>
                                {{ t('Matches') }}
                                <span class="text-xs bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded-full">
                                    {{ matches.length }}
                                </span>
                            </span>
                        </button>
                        <button
                            v-if="tournament.status === 'completed'"
                            id="tab-results"
                            :class="[
                                'py-3 px-1 text-sm font-medium border-b-2 transition-colors whitespace-nowrap',
                                activeTab === 'results'
                                    ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                            ]"
                            :aria-selected="activeTab === 'results'"
                            role="tab"
                            @click="switchTab('results')"
                        >
                            <span class="flex items-center gap-1.5">
                                <TrophyIcon class="h-3.5 w-3.5"/>
                                {{ t('Results') }}
                                <span class="text-xs bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded-full">
                                    {{ completedPlayers.length }}
                                </span>
                            </span>
                        </button>
                    </div>
                </nav>

                <!-- Tab Content -->
                <main role="tabpanel">
                    <!-- Tournament Information Tab -->
                    <div v-if="activeTab === 'info'" class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                            <!-- Details Card -->
                            <Card class="shadow-lg">
                                <CardHeader class="bg-gray-50 dark:bg-gray-700/50 py-3">
                                    <CardTitle class="text-base">{{ t('Tournament Details') }}</CardTitle>
                                </CardHeader>
                                <CardContent class="p-4">
                                    <div class="space-y-3">
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{
                                                    t('Tournament Type')
                                                }}</h4>
                                            <p class="mt-0.5 text-sm text-gray-600 dark:text-gray-400">
                                                {{ tournament.tournament_type_display }}
                                                <span v-if="tournament.races_to">
                                                ({{ t('Race to') }} {{ tournament.races_to }})
                                            </span>
                                            </p>
                                        </div>

                                        <div v-if="tournament.details">
                                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{
                                                    t('Description')
                                                }}</h4>
                                            <p class="mt-0.5 text-sm text-gray-600 dark:text-gray-400 whitespace-pre-wrap">
                                                {{ tournament.details }}</p>
                                        </div>

                                        <div v-if="tournament.regulation">
                                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{
                                                    t('Regulation')
                                                }}</h4>
                                            <p class="mt-0.5 text-sm text-gray-600 dark:text-gray-400 whitespace-pre-wrap">
                                                {{ tournament.regulation }}</p>
                                        </div>

                                        <div v-if="tournament.format">
                                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ t('Format') }}</h4>
                                            <p class="mt-0.5 text-sm text-gray-600 dark:text-gray-400">
                                                {{ tournament.format }}</p>
                                        </div>

                                        <div v-if="tournament.organizer">
                                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{
                                                    t('Organizer')
                                                }}</h4>
                                            <p class="mt-0.5 text-sm text-gray-600 dark:text-gray-400">
                                                {{ tournament.organizer }}</p>
                                        </div>

                                        <div v-if="tournament.application_deadline">
                                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ t('Application Deadline') }}</h4>
                                            <p class="mt-0.5 text-sm text-gray-600 dark:text-gray-400">
                                                {{ formatDateTime(tournament.application_deadline) }}</p>
                                        </div>

                                        <div
                                            v-if="tournament.official_ratings && tournament.official_ratings.length > 0">
                                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ t('Official Rating') }}</h4>
                                            <p class="mt-0.5 text-sm text-gray-600 dark:text-gray-400">
                                                {{ tournament.official_ratings[0].name }}
                                                <span class="text-xs text-gray-500">(×{{
                                                        tournament.official_ratings[0].rating_coefficient
                                                    }})</span>
                                            </p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Stats Card -->
                            <Card class="shadow-lg">
                                <CardHeader class="bg-gray-50 dark:bg-gray-700/50 py-3">
                                    <CardTitle class="text-base">{{ t('Tournament Stats') }}</CardTitle>
                                </CardHeader>
                                <CardContent class="p-4">
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="text-center p-3 bg-gray-50 rounded-lg dark:bg-gray-800">
                                            <div class="text-xl font-bold text-green-600 dark:text-green-400">
                                                {{ tournament.confirmed_players_count }}
                                            </div>
                                            <div class="text-xs text-gray-600 dark:text-gray-400">
                                                {{ t('Confirmed Players') }}
                                            </div>
                                        </div>

                                        <div v-if="tournament.pending_applications_count > 0"
                                             class="text-center p-3 bg-gray-50 rounded-lg dark:bg-gray-800">
                                            <div class="text-xl font-bold text-yellow-600 dark:text-yellow-400">
                                                {{ tournament.pending_applications_count }}
                                            </div>
                                            <div class="text-xs text-gray-600 dark:text-gray-400">
                                                {{ t('Pending Applications') }}
                                            </div>
                                        </div>

                                        <div class="text-center p-3 bg-gray-50 rounded-lg dark:bg-gray-800">
                                            <div class="text-xl font-bold text-blue-600 dark:text-blue-400">
                                                {{ tournament.max_participants || '∞' }}
                                            </div>
                                            <div class="text-xs text-gray-600 dark:text-gray-400">
                                                {{ t('Max Participants') }}
                                            </div>
                                        </div>

                                        <div v-if="tournament.entry_fee > 0"
                                             class="text-center p-3 bg-gray-50 rounded-lg dark:bg-gray-800">
                                            <div class="text-xl font-bold text-green-600 dark:text-green-400">
                                                {{ formatCurrency(tournament.entry_fee) }}
                                            </div>
                                            <div class="text-xs text-gray-600 dark:text-gray-400">
                                                {{ t('Entry Fee') }}
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>

                    <!-- Players Tab -->
                    <div v-if="activeTab === 'players'">
                        <Card class="shadow-lg">
                            <CardHeader class="bg-gray-50 dark:bg-gray-700/50 py-3">
                                <CardTitle class="flex items-center gap-1.5 text-base">
                                    <UsersIcon class="h-4 w-4 text-indigo-600 dark:text-indigo-400"/>
                                    {{ t('Tournament Players') }}
                                </CardTitle>
                                <CardDescription>
                                    <span class="text-xs font-medium">
                                        {{ tournament.confirmed_players_count }} {{ t('confirmed') }}
                                        <span v-if="tournament.max_participants" class="text-gray-500">
                                            / {{ tournament.max_participants }} {{ t('max') }}
                                        </span>
                                    </span>
                                </CardDescription>
                            </CardHeader>
                            <CardContent class="p-0">
                                <div v-if="isLoadingPlayers" class="flex justify-center py-8">
                                    <Spinner class="h-5 w-5 text-indigo-600"/>
                                </div>
                                <div v-else-if="confirmedPlayers.length === 0"
                                     class="py-8 text-center text-sm text-gray-500">
                                    {{ t('No confirmed players yet.') }}
                                </div>
                                <div v-else data-players-table>
                                    <DataTable
                                        :columns="playerColumns"
                                        :data="confirmedPlayers"
                                        :compact-mode="true"
                                        :mobile-card-mode="false"
                                        :empty-message="t('No players found.')"
                                        :show-header="true"
                                        class="border-0"
                                        :row-class="getPlayerRowClass"
                                        :row-attributes="getPlayerRowAttributes"
                                    >
                                        <template #cell-seed="{ value }">
                                            <span v-if="value.seed" class="font-mono text-xs text-gray-500">
                                                #{{ value.seed }}
                                            </span>
                                            <span v-else class="text-gray-400">—</span>
                                        </template>

                                        <template #cell-player="{ value, item }">
                                            <div class="flex items-center gap-2.5 py-1.5">
                                                <UserAvatar
                                                    :user="item.user"
                                                    size="sm"
                                                    priority="tournament_picture"
                                                    :exclusive-priority="true"
                                                />
                                                <div class="min-w-0">
                                                    <div class="flex items-center gap-2">
                                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                            {{ value.name }}
                                                        </p>
                                                        <span v-if="value.birthYear" class="text-xs text-gray-500 dark:text-gray-400">
                                                            ({{ value.birthYear }})
                                                        </span>
                                                    </div>
                                                    <p v-if="value.location"
                                                       class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                        {{ value.location }}
                                                    </p>
                                                </div>
                                            </div>
                                        </template>

                                        <template #cell-rating="{ value }">
                                            <div v-if="value.rating" class="text-center">
                                                <span class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ value.rating }}
                                                </span>
                                                <span v-if="value.position" class="text-xs text-gray-500 block">
                                                    #{{ value.position }}
                                                </span>
                                            </div>
                                            <span v-else class="text-gray-400 text-center block">—</span>
                                        </template>

                                        <template #cell-club="{ value }">
                                            <span v-if="value.name"
                                                  class="text-xs text-gray-600 dark:text-gray-400 truncate block max-w-[150px]">
                                                {{ value.name }}
                                            </span>
                                            <span v-else class="text-gray-400">—</span>
                                        </template>

                                        <template #cell-status="{ value }">
                                            <span :class="[
                                                'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium',
                                                getPlayerStatusBadgeClass(value.status)
                                            ]">
                                                {{ value.status_display }}
                                            </span>
                                        </template>

                                        <template #cell-registered="{ value }">
                                            <span class="text-xs text-gray-500 whitespace-nowrap">
                                                {{ value.date }}
                                            </span>
                                        </template>
                                    </DataTable>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Groups Tab -->
                    <div v-if="activeTab === 'groups' && canViewGroups">
                        <div class="space-y-4">
                            <Card v-if="isLoadingGroups" class="shadow-lg">
                                <CardContent class="py-8">
                                    <div class="flex justify-center">
                                        <Spinner class="h-6 w-6 text-indigo-600"/>
                                    </div>
                                </CardContent>
                            </Card>

                            <div v-else-if="groups.length > 0"
                                 class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                                <Card v-for="group in groups" :key="group.id" class="shadow-lg">
                                    <CardHeader class="bg-gray-50 dark:bg-gray-700/50 py-3">
                                        <CardTitle class="text-base">
                                            {{ t('Group :code', {code: group.group_code}) }}
                                        </CardTitle>
                                        <CardDescription class="text-xs">
                                            {{ t(':count players', {count: group.group_size}) }}
                                            <span v-if="group.advance_count"> • {{
                                                    t(':count advance', {count: group.advance_count})
                                                }}</span>
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent class="p-4">
                                        <div class="space-y-1.5">
                                            <div
                                                v-for="(player, index) in getPlayersInGroup(group.group_code)"
                                                :key="player.id"
                                                :class="[
                                                'flex items-center justify-between p-1.5 rounded text-xs',
                                                index < group.advance_count ? 'bg-green-50 dark:bg-green-900/20' : 'hover:bg-gray-50 dark:hover:bg-gray-800'
                                            ]"
                                            >
                                                <div class="flex items-center gap-1.5">
                                                <span class="font-medium text-gray-500">
                                                    {{ index + 1 }}
                                                </span>
                                                    <span>{{
                                                            player.user?.firstname
                                                        }} {{ player.user?.lastname }}</span>
                                                </div>
                                                <div class="text-gray-600 dark:text-gray-400">
                                                <span v-if="player.group_wins > 0 || player.group_losses > 0">
                                                    {{ player.group_wins }}W - {{ player.group_losses }}L
                                                </span>
                                                    <span v-else>{{ t('Not played') }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Group Matches -->
                                        <div v-if="matchesByGroup[group.group_code]?.length > 0"
                                             class="mt-3 pt-3 border-t">
                                            <h4 class="text-xs font-medium mb-1.5">{{ t('Matches') }}</h4>
                                            <div class="space-y-1">
                                                <div
                                                    v-for="match in matchesByGroup[group.group_code]"
                                                    :key="match.id"
                                                    class="text-xs"
                                                >
                                                    <div class="flex items-center justify-between">
                                                        <div class="flex-1">
                                                        <span
                                                            :class="{'font-bold': match.winner_id === match.player1_id}">
                                                            {{
                                                                match.player1?.firstname?.substring(0, 1)
                                                            }}. {{ match.player1?.lastname }}
                                                        </span>
                                                            <span class="mx-1">vs</span>
                                                            <span
                                                                :class="{'font-bold': match.winner_id === match.player2_id}">
                                                            {{
                                                                    match.player2?.firstname?.substring(0, 1)
                                                                }}. {{ match.player2?.lastname }}
                                                        </span>
                                                        </div>
                                                        <div class="ml-1.5">
                                                        <span v-if="match.status === 'completed'" class="font-medium">
                                                            {{ match.player1_score }} - {{ match.player2_score }}
                                                        </span>
                                                            <span v-else
                                                                  :class="['px-1.5 py-0.5 text-xs rounded-full', getMatchStatusBadgeClass(match.status)]">
                                                            {{ match.status_display }}
                                                        </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        </div>
                    </div>

                    <!-- Bracket Tab -->
                    <div v-if="activeTab === 'bracket' && (canViewBracket || canViewRoundRobin)">
                        <!-- Round Robin Standings -->
                        <template v-if="isRoundRobin">
                            <RoundRobinStandings
                                :tournament-id="tournamentId"
                            />

                            <!-- Round Robin Matches List -->
                            <Card v-if="matches.length > 0" class="mt-4 shadow-lg">
                                <CardHeader class="bg-gray-50 dark:bg-gray-700/50 py-3">
                                    <CardTitle class="flex items-center gap-1.5 text-base">
                                        <PlayIcon class="h-4 w-4 text-indigo-600 dark:text-indigo-400"/>
                                        {{ t('All Matches') }}
                                    </CardTitle>
                                    <CardDescription class="text-xs">
                                        {{ t('Head-to-head matches between all players') }}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent class="p-4">
                                    <MatchesList
                                        :matches="displayMatches"
                                        :is-loading="isLoadingMatches"
                                        :show-table="true"
                                        :show-scheduled-time="true"
                                        :show-completed-time="true"
                                    />
                                </CardContent>
                            </Card>
                        </template>
                        <template v-else>
                            <!-- Olympic Tournament Tab Navigation -->
                            <nav v-if="isOlympicDoubleElimination"
                                 class="mb-4 border-b border-gray-200 dark:border-gray-700 overflow-x-auto"
                                 role="navigation"
                                 aria-label="Olympic bracket tabs">
                                <div class="-mb-px flex space-x-4 sm:space-x-6 min-w-max">
                                    <button
                                        id="tab-first-stage"
                                        :class="[
                                            'py-3 px-1 text-sm font-medium border-b-2 transition-colors whitespace-nowrap',
                                            activeOlympicTab === 'first-stage'
                                                ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                                        ]"
                                        :aria-selected="activeOlympicTab === 'first-stage'"
                                        role="tab"
                                        @click="switchOlympicTab('first-stage')"
                                    >
                                        <span class="flex items-center gap-1.5">
                                            <GitBranchIcon class="h-3.5 w-3.5"/>
                                            {{ t('First Stage') }}
                                            <span
                                                class="text-xs bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded-full">
                                                {{ t('Double Elimination') }}
                                            </span>
                                        </span>
                                    </button>
                                    <button
                                        id="tab-olympic-stage"
                                        :class="[
                                            'py-3 px-1 text-sm font-medium border-b-2 transition-colors whitespace-nowrap',
                                            activeOlympicTab === 'olympic-stage'
                                                ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                                        ]"
                                        :aria-selected="activeOlympicTab === 'olympic-stage'"
                                        role="tab"
                                        @click="switchOlympicTab('olympic-stage')"
                                    >
                                        <span class="flex items-center gap-1.5">
                                            <LayersIcon class="h-3.5 w-3.5"/>
                                            {{ t('Olympic Stage') }}
                                            <span
                                                class="text-xs bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded-full">
                                                {{ t('Single Elimination') }}
                                            </span>
                                        </span>
                                    </button>
                                </div>
                            </nav>

                            <div v-if="isLoadingMatches" class="flex justify-center py-8">
                                <Spinner class="h-6 w-6 text-indigo-600"/>
                            </div>
                            <div v-else-if="matches.length === 0" class="text-center py-8">
                                <p class="text-sm text-gray-500">{{ t('Bracket has not been generated yet.') }}</p>
                            </div>

                            <!-- Olympic Tournament Tab Content -->
                            <template v-else-if="isOlympicDoubleElimination">
                                <!-- First Stage Tab -->
                                <div v-if="activeOlympicTab === 'first-stage'">
                                    <DoubleEliminationBracket
                                        :can-edit="false"
                                        :current-user-id="currentUserId"
                                        :matches="firstStageMatches"
                                        :tournament="tournament!"
                                    />
                                </div>

                                <!-- Olympic Stage Tab -->
                                <div v-if="activeOlympicTab === 'olympic-stage'">
                                    <SingleEliminationBracket
                                        :can-edit="false"
                                        :current-user-id="currentUserId"
                                        :matches="olympicStageMatches"
                                        :tournament="tournament!"
                                    />
                                </div>
                            </template>

                            <!-- Regular Tournament Bracket (non-Olympic) -->
                            <template v-else>
                                <SingleEliminationBracket
                                    v-if="!isDoubleElimination"
                                    :can-edit="false"
                                    :current-user-id="currentUserId"
                                    :matches="matches"
                                    :tournament="tournament!"
                                />

                                <DoubleEliminationBracket
                                    v-else
                                    :can-edit="false"
                                    :current-user-id="currentUserId"
                                    :matches="matches"
                                    :tournament="tournament!"
                                />
                            </template>
                        </template>
                    </div>

                    <!-- Matches Tab -->
                    <div v-if="activeTab === 'matches' && canViewMatches">
                        <MatchesList
                            :matches="displayMatches"
                            :is-loading="isLoadingMatches"
                            :show-table="true"
                            :show-scheduled-time="true"
                            :show-completed-time="true"
                        />
                    </div>

                    <!-- Applications Tab -->
                    <div v-if="activeTab === 'applications'">
                        <div class="space-y-4">
                            <!-- Pending Applications -->
                            <Card v-if="pendingApplications.length > 0" class="shadow-lg">
                                <CardHeader class="bg-gray-50 dark:bg-gray-700/50 py-3">
                                    <CardTitle class="flex items-center gap-1.5 text-base">
                                        <ClipboardListIcon class="h-4 w-4 text-yellow-600"/>
                                        {{ t('Pending Applications') }} ({{ pendingApplications.length }})
                                    </CardTitle>
                                    <CardDescription class="text-xs">{{
                                            t('Applications waiting for approval')
                                        }}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent class="p-4">
                                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                        <div
                                            v-for="application in pendingApplications"
                                            :key="application.id"
                                            class="flex items-center justify-between p-2.5 bg-yellow-50 rounded-lg dark:bg-yellow-900/20"
                                        >
                                            <div>
                                                <p class="text-sm font-medium">{{
                                                        application.user?.firstname
                                                    }}
                                                    {{ application.user?.lastname }}</p>
                                                <p class="text-xs text-gray-600 dark:text-gray-400">
                                                    {{ t('Applied:') }} {{ formatDate(application.applied_at) }}
                                                </p>
                                            </div>
                                            <span
                                                :class="['px-1.5 py-0.5 text-xs font-semibold rounded-full', getPlayerStatusBadgeClass(application.status)]">
                                           {{ application.status_display }}
                                       </span>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Rejected Applications -->
                            <Card v-if="rejectedApplications.length > 0" class="shadow-lg">
                                <CardHeader class="bg-gray-50 dark:bg-gray-700/50 py-3">
                                    <CardTitle class="flex items-center gap-1.5 text-base">
                                        <UserCheckIcon class="h-4 w-4 text-red-600"/>
                                        {{ t('Rejected Applications') }} ({{ rejectedApplications.length }})
                                    </CardTitle>
                                    <CardDescription class="text-xs">{{
                                            t('Applications that were not accepted')
                                        }}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent class="p-4">
                                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                        <div
                                            v-for="application in rejectedApplications"
                                            :key="application.id"
                                            class="flex items-center justify-between p-2.5 bg-red-50 rounded-lg dark:bg-red-900/20"
                                        >
                                            <div>
                                                <p class="text-sm font-medium">{{
                                                        application.user?.firstname
                                                    }}
                                                    {{ application.user?.lastname }}</p>
                                                <p class="text-xs text-gray-600 dark:text-gray-400">
                                                    {{ t('Rejected:') }} {{ formatDate(application.rejected_at) }}
                                                </p>
                                            </div>
                                            <span
                                                :class="['px-1.5 py-0.5 text-xs font-semibold rounded-full', getPlayerStatusBadgeClass(application.status)]">
                                           {{ application.status_display }}
                                       </span>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Empty State -->
                            <Card v-if="pendingApplications.length === 0 && rejectedApplications.length === 0"
                                  class="shadow-lg">
                                <CardContent class="py-8 text-center">
                                    <ClipboardListIcon class="mx-auto h-10 w-10 text-gray-400"/>
                                    <p class="mt-3 text-base font-medium text-gray-900 dark:text-gray-100">
                                        {{ t('No Applications') }}</p>
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                        {{ t('There are no applications to display.') }}
                                    </p>
                                </CardContent>
                            </Card>
                        </div>
                    </div>

                    <!-- Results Tab -->
                    <div v-if="activeTab === 'results' && tournament.status === 'completed'">
                        <Card class="shadow-lg">
                            <CardHeader class="bg-gray-50 dark:bg-gray-700/50 py-3">
                                <CardTitle class="flex items-center gap-1.5 text-base">
                                    <TrophyIcon class="h-4 w-4 text-indigo-600 dark:text-indigo-400"/>
                                    {{ t('Tournament Results') }}
                                </CardTitle>
                                <CardDescription class="text-xs">{{
                                        t('Final standings and prizes')
                                    }}
                                </CardDescription>
                            </CardHeader>
                            <CardContent class="p-4">
                                <div v-if="completedPlayers.length === 0"
                                     class="py-6 text-center text-sm text-gray-500">
                                    {{ t('No results available yet.') }}
                                </div>
                                <div v-else>
                                    <DataTable
                                        :columns="columns"
                                        :compact-mode="true"
                                        :data="completedPlayers"
                                        :empty-message="t('No results available yet.')"
                                        :mobile-card-mode="true"
                                    >
                                        <template #cell-position="{ value }">
                            <span
                                :class="[
                                    'inline-flex h-7 w-7 items-center justify-center rounded-full text-xs font-medium',
                                    getPositionBadgeClass(value.position)
                                ]"
                            >
                                {{ value.position }}
                            </span>
                                        </template>

                                        <template #cell-player="{ value, item }">
                                            <div class="flex items-center gap-2.5">
                                                <UserAvatar
                                                    :user="item.user"
                                                    size="sm"
                                                    priority="tournament_picture"
                                                    :exclusive-priority="true"
                                                />
                                                <div>
                                                    <p class="text-sm font-medium">{{ value.name }}</p>
                                                    <p v-if="value.isWinner"
                                                       class="text-xs text-yellow-600 dark:text-yellow-400">🏆 {{
                                                            t('Winner')
                                                        }}</p>
                                                </div>
                                            </div>
                                        </template>

                                        <template #cell-rating="{ value }">
                            <span
                                v-if="value.points > 0"
                                class="rounded-full bg-blue-100 px-1.5 py-0.5 text-xs text-blue-800 dark:bg-blue-900/30 dark:text-blue-300"
                            >
                                +{{ value.points }}
                            </span>
                                            <span v-else class="text-gray-400">—</span>
                                        </template>

                                        <template #cell-bonus="{ value }">
                            <span v-if="value.amount > 0"
                                  class="text-sm font-medium text-orange-600 dark:text-orange-400">
                                {{ value.amount }}
                            </span>
                                            <span v-else class="text-gray-400">—</span>
                                        </template>

                                        <template #cell-prize="{ value }">
                            <span v-if="value.amount > 0"
                                  class="text-sm font-medium text-green-600 dark:text-green-400">
                                {{ formatCurrency(value.amount) }}
                            </span>
                                            <span v-else class="text-gray-400">—</span>
                                        </template>

                                        <template #cell-achievement="{ value }">
                            <span v-if="value.amount > 0"
                                  class="text-sm font-medium text-purple-600 dark:text-purple-400">
                                {{ formatCurrency(value.amount) }}
                            </span>
                                            <span v-else class="text-gray-400">—</span>
                                        </template>

                                        <template #cell-total="{ value }">
                            <span v-if="value.amount > 0"
                                  class="text-sm font-bold text-indigo-600 dark:text-indigo-400">
                                {{ formatCurrency(value.amount) }}
                            </span>
                                            <span v-else class="text-gray-400">—</span>
                                        </template>

                                        <!-- Mobile card primary info -->
                                        <template #mobile-primary="{ item }">
                                            <div class="flex items-center justify-between mb-2.5">
                                                <div class="flex items-center gap-2.5">
                                    <span :class="[
                                        'inline-flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold',
                                        getPositionBadgeClass(item.position)
                                    ]">
                                        {{ item.position }}
                                    </span>
                                                    <UserAvatar
                                                        :user="item.user"
                                                        size="md"
                                                        priority="tournament_picture"
                                                        :exclusive-priority="true"
                                                    />
                                                    <div>
                                                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                                                            {{ item.user?.firstname }} {{ item.user?.lastname }}
                                                        </h3>
                                                        <p v-if="item.is_winner"
                                                           class="text-xs text-yellow-600 dark:text-yellow-400">
                                                            🏆 {{ t('Winner') }}
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <p v-if="item.total_amount > 0"
                                                       class="text-base font-bold text-green-600 dark:text-green-400">
                                                        {{ formatCurrency(item.total_amount) }}
                                                    </p>
                                                    <p v-if="item.rating_points > 0"
                                                       class="text-xs text-gray-500 dark:text-gray-400">
                                                        +{{ item.rating_points }} {{ t('pts') }}
                                                    </p>
                                                </div>
                                            </div>
                                        </template>
                                    </DataTable>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </main>
            </template>
        </div>
        <TablesManager
            v-if="tournament && isAdmin"
            :show="showTablesModal"
            :tournament-id="tournament.id"
            @close="showTablesModal = false"
        />
        <TournamentRegistrationModal
            v-if="tournament"
            :show="showRegistrationModal"
            :tournament="tournament"
            @close="showRegistrationModal = false"
            @success="handleRegistrationSuccess"
        />
    </div>
</template>
