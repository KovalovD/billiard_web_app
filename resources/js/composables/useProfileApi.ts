import {useApi, useApiAction} from '@/composables/useApi';
import {apiClient} from '@/lib/apiClient';
import type {City, Club, User} from '@/types/api';

export interface UpdateProfilePayload {
    firstname: string;
    lastname: string;
    email: string;
    phone: string;
    home_city_id?: number | null;
    home_club_id?: number | null;
}

export interface UpdatePasswordPayload {
    current_password: string;
    password: string;
    password_confirmation: string;
}

export function useProfileApi() {
    const fetchCities = () => {
        return useApi<City[]>(() => apiClient('/api/cities'));
    };

    const fetchClubs = (cityId?: number) => {
        return useApi<Club[]>(() => {
            const params = cityId ? `?city_id=${cityId}` : '';
            return apiClient(`/api/clubs${params}`);
        });
    };

    const updateProfile = () => {
        return useApiAction((payload: UpdateProfilePayload) =>
            apiClient<User>('/api/profile', {
                method: 'put',
                data: payload,
            }),
        );
    };

    const updatePassword = () => {
        return useApiAction((payload: UpdatePasswordPayload) =>
            apiClient('/api/profile/password', {
                method: 'put',
                data: payload,
            }),
        );
    };

    const deleteAccount = () => {
        return useApiAction((password: string) =>
            apiClient('/api/profile', {
                method: 'delete',
                data: {password},
            }),
        );
    };

    return {
        fetchCities,
        fetchClubs,
        updateProfile,
        updatePassword,
        deleteAccount,
    };
}
