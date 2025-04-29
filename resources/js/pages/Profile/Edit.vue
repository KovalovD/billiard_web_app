<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {Head} from '@inertiajs/vue3';
import {useAuth} from '@/composables/useAuth';
import {Card, CardContent, CardHeader, CardTitle} from '@/Components/ui';


defineOptions({layout: AuthenticatedLayout});
defineProps<{ header?: string }>();
const {user} = useAuth();
</script>

<template>
    <Head title="Profile"/>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>Update Profile Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <p>Your Name: {{ user?.firstname }} {{ user?.lastname }}</p>
                    <p>Your Email: {{ user?.email }}</p>
                    <p>Your Phone: {{ user?.phone ?? 'Not provided' }}</p>
                    <p class="mt-4 italic text-gray-500">(Profile editing form goes here)</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Update Password</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="italic text-gray-500">(Password update form goes here)</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Delete Account</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="italic text-gray-500">(Account deletion section goes here)</p>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
