// resources/js/Components/ui/UserAvatar.vue

<script lang="ts" setup>
import {computed} from 'vue';
import {User} from '@/types/api';
import {UserIcon} from 'lucide-vue-next';
import {useLocale} from '@/composables/useLocale';

interface Props {
    user: User | null;
    size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl';
    showFallback?: boolean;
    priority?: 'avatar' | 'picture' | 'tournament_picture';
    className?: string;
    exclusivePriority?: boolean; // When true, only shows the priority image, no fallbacks
}

const props = withDefaults(defineProps<Props>(), {
    size: 'md',
    showFallback: true,
    priority: 'avatar',
    className: '',
    exclusivePriority: false
});

const {t} = useLocale();

const sizeClasses = {
    xs: 'h-6 w-6 text-xs',
    sm: 'h-8 w-8 text-sm',
    md: 'h-10 w-10 text-base',
    lg: 'h-12 w-12 text-lg',
    xl: 'h-16 w-16 text-xl'
};

const iconSizes = {
    xs: 'h-3 w-3',
    sm: 'h-4 w-4',
    md: 'h-5 w-5',
    lg: 'h-6 w-6',
    xl: 'h-8 w-8'
};

const imageUrl = computed(() => {
    if (!props.user) return null;

    // Helper function to get full URL
    const getFullUrl = (path: string | null | undefined) => {
        return path;
    };

    // If exclusive priority is set, only use the specified priority image
    if (props.exclusivePriority) {
        switch (props.priority) {
            case 'avatar':
                return getFullUrl(props.user.avatar);
            case 'picture':
                return getFullUrl(props.user.picture);
            case 'tournament_picture':
                return getFullUrl(props.user.tournament_picture);
            default:
                return null;
        }
    }

    // Otherwise use the priority fallback system
    const priorities = {
        avatar: [getFullUrl(props.user.avatar), getFullUrl(props.user.picture), getFullUrl(props.user.tournament_picture)],
        picture: [getFullUrl(props.user.picture), getFullUrl(props.user.avatar), getFullUrl(props.user.tournament_picture)],
        tournament_picture: [getFullUrl(props.user.tournament_picture), getFullUrl(props.user.picture), getFullUrl(props.user.avatar)]
    };

    const urls = priorities[props.priority] || priorities.avatar;
    return urls.find(url => url && url.trim() !== '') || null;
});

const initials = computed(() => {
    if (!props.user) return 'U';

    if (props.user.firstname && props.user.lastname) {
        return `${props.user.firstname[0]}${props.user.lastname[0]}`.toUpperCase();
    }

    if (props.user.firstname) {
        return props.user.firstname.slice(0, 2).toUpperCase();
    }

    if (props.user.name) {
        const parts = props.user.name.split(' ');
        if (parts.length > 1) {
            return `${parts[0][0]}${parts[1][0]}`.toUpperCase();
        }
        return props.user.name.slice(0, 2).toUpperCase();
    }

    if (props.user.email) {
        return props.user.email[0].toUpperCase();
    }

    return 'U';
});

const backgroundColor = computed(() => {
    if (!props.user) return 'from-gray-400 to-gray-600';

    const colors = [
        'from-indigo-500 to-purple-600',
        'from-blue-500 to-indigo-600',
        'from-purple-500 to-pink-600',
        'from-green-500 to-teal-600',
        'from-yellow-500 to-orange-600',
        'from-red-500 to-pink-600',
        'from-teal-500 to-cyan-600',
        'from-cyan-500 to-blue-600',
        'from-pink-500 to-rose-600',
        'from-emerald-500 to-green-600',
    ];

    // Generate a more stable hash based on user data
    let hash = 0;
    const str = `${props.user.id || 0}${props.user.email || ''}${props.user.firstname || ''}`;
    for (let i = 0; i < str.length; i++) {
        const char = str.charCodeAt(i);
        hash = ((hash << 5) - hash) + char;
        hash = hash & hash; // Convert to 32bit integer
    }

    return colors[Math.abs(hash) % colors.length];
});

const handleImageError = (event: Event) => {
    const target = event.target as HTMLImageElement;
    // Hide the broken image
    target.style.display = 'none';
    // Remove src to prevent further error events
    target.removeAttribute('src');
};
</script>

<template>
    <div :class="[
        sizeClasses[size],
        className,
        'relative rounded-full overflow-hidden flex-shrink-0 bg-gray-100 dark:bg-gray-800'
    ]">
        <!-- Image Avatar -->
        <img
            v-if="imageUrl"
            :src="imageUrl"
            :alt="user?.firstname || user?.name || t('User avatar')"
            class="h-full w-full object-cover"
            loading="lazy"
            @error="handleImageError"
        />

        <!-- Fallback Avatar -->
        <div
            v-if="showFallback && (!imageUrl || !user)"
            :class="[
                'absolute inset-0 flex items-center justify-center font-semibold text-white bg-gradient-to-br shadow-inner',
                backgroundColor
            ]"
        >
            <span v-if="user" class="select-none">{{ initials }}</span>
            <UserIcon v-else :class="[iconSizes[size], 'opacity-80']"/>
        </div>

        <!-- Loading state overlay -->
        <div
            v-if="imageUrl && !user?.avatar && !user?.picture && !user?.tournament_picture"
            class="absolute inset-0 flex items-center justify-center bg-gray-200 dark:bg-gray-700 animate-pulse"
        >
            <UserIcon :class="[iconSizes[size], 'text-gray-400 dark:text-gray-500']"/>
        </div>
    </div>
</template>
