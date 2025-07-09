import {ref} from 'vue';
import {apiClient} from '@/lib/apiClient';
import type {City, Club, ClubTable, Country, Game} from '@/types/api';

export function useAdminSettings() {
    const isLoading = ref(false);
    const error = ref<string | null>(null);

    // Countries
    const fetchCountries = async (params?: { search?: string; page?: number; per_page?: number }) => {
        isLoading.value = true;
        error.value = null;
        try {
            const queryParams = new URLSearchParams(params as any).toString();
            return await apiClient<{
                data: Country[];
                meta: any
            }>(`/api/admin/countries${queryParams ? `?${queryParams}` : ''}`);
        } catch (err: any) {
            error.value = err.message || 'Failed to fetch countries';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    const fetchCountry = async (id: number) => {
        isLoading.value = true;
        error.value = null;
        try {
            return await apiClient<Country>(`/api/admin/countries/${id}`);
        } catch (err: any) {
            error.value = err.message || 'Failed to fetch country';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    const createCountry = async (data: Partial<Country>) => {
        isLoading.value = true;
        error.value = null;
        try {
            return await apiClient<{
                success: boolean;
                country: Country;
                message: string
            }>('/api/admin/countries', {
                method: 'POST',
                data
            });
        } catch (err: any) {
            error.value = err.message || 'Failed to create country';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    const updateCountry = async (id: number, data: Partial<Country>) => {
        isLoading.value = true;
        error.value = null;
        try {
            return await apiClient<{
                success: boolean;
                country: Country;
                message: string
            }>(`/api/admin/countries/${id}`, {
                method: 'PUT',
                data
            });
        } catch (err: any) {
            error.value = err.message || 'Failed to update country';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    const deleteCountry = async (id: number) => {
        isLoading.value = true;
        error.value = null;
        try {
            return await apiClient<{ success: boolean; message: string }>(`/api/admin/countries/${id}`, {
                method: 'DELETE'
            });
        } catch (err: any) {
            error.value = err.message || 'Failed to delete country';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    // Cities
    const fetchCities = async (params?: { search?: string; country_id?: number; page?: number; per_page?: number }) => {
        isLoading.value = true;
        error.value = null;
        try {
            const queryParams = new URLSearchParams(params as any).toString();
            return await apiClient<{
                data: City[];
                meta: any
            }>(`/api/admin/cities${queryParams ? `?${queryParams}` : ''}`);
        } catch (err: any) {
            error.value = err.message || 'Failed to fetch cities';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    const fetchCity = async (id: number) => {
        isLoading.value = true;
        error.value = null;
        try {
            return await apiClient<City>(`/api/admin/cities/${id}`);
        } catch (err: any) {
            error.value = err.message || 'Failed to fetch city';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    const createCity = async (data: { name: string; country_id: number }) => {
        isLoading.value = true;
        error.value = null;
        try {
            return await apiClient<{ success: boolean; city: City; message: string }>('/api/admin/cities', {
                method: 'POST',
                data
            });
        } catch (err: any) {
            error.value = err.message || 'Failed to create city';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    const updateCity = async (id: number, data: { name: string; country_id: number }) => {
        isLoading.value = true;
        error.value = null;
        try {
            return await apiClient<{
                success: boolean;
                city: City;
                message: string
            }>(`/api/admin/cities/${id}`, {
                method: 'PUT',
                data
            });
        } catch (err: any) {
            error.value = err.message || 'Failed to update city';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    const deleteCity = async (id: number) => {
        isLoading.value = true;
        error.value = null;
        try {
            return await apiClient<{ success: boolean; message: string }>(`/api/admin/cities/${id}`, {
                method: 'DELETE'
            });
        } catch (err: any) {
            error.value = err.message || 'Failed to delete city';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    // Clubs
    const fetchClubs = async (params?: { search?: string; city_id?: number; page?: number; per_page?: number }) => {
        isLoading.value = true;
        error.value = null;
        try {
            const queryParams = new URLSearchParams(params as any).toString();
            return await apiClient<{
                data: Club[];
                meta: any
            }>(`/api/admin/clubs${queryParams ? `?${queryParams}` : ''}`);
        } catch (err: any) {
            error.value = err.message || 'Failed to fetch clubs';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    const fetchClub = async (id: number) => {
        isLoading.value = true;
        error.value = null;
        try {
            return await apiClient<Club>(`/api/admin/clubs/${id}`);
        } catch (err: any) {
            error.value = err.message || 'Failed to fetch club';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    const createClub = async (data: { name: string; city_id: number }) => {
        isLoading.value = true;
        error.value = null;
        try {
            return await apiClient<{ success: boolean; club: Club; message: string }>('/api/admin/clubs', {
                method: 'POST',
                data
            });
        } catch (err: any) {
            error.value = err.message || 'Failed to create club';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    const updateClub = async (id: number, data: { name: string; city_id: number }) => {
        isLoading.value = true;
        error.value = null;
        try {
            return await apiClient<{
                success: boolean;
                club: Club;
                message: string
            }>(`/api/admin/clubs/${id}`, {
                method: 'PUT',
                data
            });
        } catch (err: any) {
            error.value = err.message || 'Failed to update club';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    const deleteClub = async (id: number) => {
        isLoading.value = true;
        error.value = null;
        try {
            return await apiClient<{ success: boolean; message: string }>(`/api/admin/clubs/${id}`, {
                method: 'DELETE'
            });
        } catch (err: any) {
            error.value = err.message || 'Failed to delete club';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    // Club Tables
    const fetchClubTables = async (clubId: number) => {
        isLoading.value = true;
        error.value = null;
        try {
            return await apiClient<ClubTable[]>(`/api/admin/clubs/${clubId}/tables`);
        } catch (err: any) {
            error.value = err.message || 'Failed to fetch club tables';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    const createClubTable = async (clubId: number, data: Partial<ClubTable>) => {
        isLoading.value = true;
        error.value = null;
        try {
            return await apiClient<{
                success: boolean;
                table: ClubTable;
                message: string
            }>(`/api/admin/clubs/${clubId}/tables`, {
                method: 'POST',
                data
            });
        } catch (err: any) {
            error.value = err.message || 'Failed to create table';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    const updateClubTable = async (clubId: number, tableId: number, data: Partial<ClubTable>) => {
        isLoading.value = true;
        error.value = null;
        try {
            return await apiClient<{
                success: boolean;
                table: ClubTable;
                message: string
            }>(`/api/admin/clubs/${clubId}/tables/${tableId}`, {
                method: 'PUT',
                data
            });
        } catch (err: any) {
            error.value = err.message || 'Failed to update table';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    const deleteClubTable = async (clubId: number, tableId: number) => {
        isLoading.value = true;
        error.value = null;
        try {
            return await apiClient<{
                success: boolean;
                message: string
            }>(`/api/admin/clubs/${clubId}/tables/${tableId}`, {
                method: 'DELETE'
            });
        } catch (err: any) {
            error.value = err.message || 'Failed to delete table';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    const reorderClubTables = async (clubId: number, tables: { id: number; sort_order: number }[]) => {
        isLoading.value = true;
        error.value = null;
        try {
            return await apiClient<{ success: boolean; message: string }>(`/api/admin/clubs/${clubId}/tables/reorder`, {
                method: 'POST',
                data: {tables}
            });
        } catch (err: any) {
            error.value = err.message || 'Failed to reorder tables';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    // Games
    const fetchGames = async (params?: {
        search?: string;
        type?: string;
        is_multiplayer?: boolean;
        page?: number;
        per_page?: number
    }) => {
        isLoading.value = true;
        error.value = null;
        try {
            const queryParams = new URLSearchParams(params as any).toString();
            return await apiClient<{
                data: Game[];
                meta: any
            }>(`/api/admin/games${queryParams ? `?${queryParams}` : ''}`);
        } catch (err: any) {
            error.value = err.message || 'Failed to fetch games';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    const fetchGame = async (id: number) => {
        isLoading.value = true;
        error.value = null;
        try {
            return await apiClient<Game>(`/api/admin/games/${id}`);
        } catch (err: any) {
            error.value = err.message || 'Failed to fetch game';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    const createGame = async (data: { name: string; type: string; rules?: string; is_multiplayer?: boolean }) => {
        isLoading.value = true;
        error.value = null;
        try {
            return await apiClient<{ success: boolean; game: Game; message: string }>('/api/admin/games', {
                method: 'POST',
                data
            });
        } catch (err: any) {
            error.value = err.message || 'Failed to create game';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    const updateGame = async (id: number, data: {
        name: string;
        type: string;
        rules?: string;
        is_multiplayer?: boolean
    }) => {
        isLoading.value = true;
        error.value = null;
        try {
            return await apiClient<{ success: boolean; game: Game; message: string }>(`/api/admin/games/${id}`, {
                method: 'PUT',
                data
            });
        } catch (err: any) {
            error.value = err.message || 'Failed to update game';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    const deleteGame = async (id: number) => {
        isLoading.value = true;
        error.value = null;
        try {
            return await apiClient<{ success: boolean; message: string }>(`/api/admin/games/${id}`, {
                method: 'DELETE'
            });
        } catch (err: any) {
            error.value = err.message || 'Failed to delete game';
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    return {
        isLoading,
        error,
        // Countries
        fetchCountries,
        fetchCountry,
        createCountry,
        updateCountry,
        deleteCountry,
        // Cities
        fetchCities,
        fetchCity,
        createCity,
        updateCity,
        deleteCity,
        // Clubs
        fetchClubs,
        fetchClub,
        createClub,
        updateClub,
        deleteClub,
        // Club Tables
        fetchClubTables,
        createClubTable,
        updateClubTable,
        deleteClubTable,
        reorderClubTables,
        // Games
        fetchGames,
        fetchGame,
        createGame,
        updateGame,
        deleteGame,
    };
}
