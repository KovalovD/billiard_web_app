<template>
    <AuthenticatedLayout>
        <Head :title="t('Service Agreement')"/>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 lg:p-8">
                        <h1 class="text-3xl font-bold text-gray-900 mb-8">{{ t('Service Agreement') }}</h1>

                        <div class="prose prose-lg max-w-none">
                            <p class="text-gray-600 mb-6">
                                {{ t('Effective Date') }}: {{ new Date().toLocaleDateString() }}
                            </p>

                            <section class="mb-8">
                                <h2 class="text-2xl font-semibold text-gray-800 mb-4">{{
                                        t('1. Agreement to Terms')
                                    }}</h2>
                                <p class="text-gray-700 mb-4">
                                    {{
                                        t('By accessing or using the WinnerBreak platform ("Service"), you agree to be bound by these Terms of Service ("Terms"). If you disagree with any part of these terms, you may not access the Service.')
                                    }}
                                </p>
                            </section>

                            <section class="mb-8">
                                <h2 class="text-2xl font-semibold text-gray-800 mb-4">{{
                                        t('2. Description of Service')
                                    }}</h2>
                                <p class="text-gray-700 mb-4">
                                    {{
                                        t('WinnerBreak provides a comprehensive billiard league management platform including:')
                                    }}
                                </p>
                                <ul class="list-disc pl-6 mb-4 space-y-2">
                                    <li>{{ t('League and tournament organization tools') }}</li>
                                    <li>{{ t('Player rating and ranking systems') }}</li>
                                    <li>{{ t('Match scheduling and result tracking') }}</li>
                                    <li>{{ t('Statistical analysis and player profiles') }}</li>
                                    <li>{{ t('Club and community management features') }}</li>
                                </ul>
                            </section>

                            <section class="mb-8">
                                <h2 class="text-2xl font-semibold text-gray-800 mb-4">{{ t('3. User Accounts') }}</h2>

                                <h3 class="text-xl font-medium text-gray-700 mb-3">{{ t('Account Creation') }}</h3>
                                <ul class="list-disc pl-6 mb-4 space-y-2">
                                    <li>{{ t('You must provide accurate and complete information') }}</li>
                                    <li>{{ t('You are responsible for maintaining account security') }}</li>
                                    <li>{{ t('One account per person is allowed') }}</li>
                                    <li>{{ t('You must be at least 16 years old to use the Service') }}</li>
                                </ul>

                                <h3 class="text-xl font-medium text-gray-700 mb-3">{{
                                        t('Account Responsibilities')
                                    }}</h3>
                                <ul class="list-disc pl-6 mb-4 space-y-2">
                                    <li>{{ t('Keep your password confidential') }}</li>
                                    <li>{{ t('Notify us immediately of any unauthorized access') }}</li>
                                    <li>{{ t('You are liable for all activities under your account') }}</li>
                                </ul>
                            </section>

                            <section class="mb-8">
                                <h2 class="text-2xl font-semibold text-gray-800 mb-4">
                                    {{ t('4. League and Tournament Rules') }}</h2>

                                <h3 class="text-xl font-medium text-gray-700 mb-3">{{ t('Fair Play') }}</h3>
                                <ul class="list-disc pl-6 mb-4 space-y-2">
                                    <li>{{ t('All matches must be played fairly and honestly') }}</li>
                                    <li>{{ t('Match results must be reported accurately') }}</li>
                                    <li>{{ t('Cheating, match-fixing, or rating manipulation is prohibited') }}</li>
                                    <li>{{ t('Respect the challenge system limitations (±10 positions)') }}</li>
                                </ul>

                                <h3 class="text-xl font-medium text-gray-700 mb-3">{{ t('Player Conduct') }}</h3>
                                <ul class="list-disc pl-6 mb-4 space-y-2">
                                    <li>{{ t('Treat all players with respect') }}</li>
                                    <li>{{ t('No harassment, abuse, or discriminatory behavior') }}</li>
                                    <li>{{ t('Follow club rules and regulations') }}</li>
                                    <li>{{ t('Honor scheduled matches and commitments') }}</li>
                                </ul>
                            </section>

                            <section class="mb-8">
                                <h2 class="text-2xl font-semibold text-gray-800 mb-4">{{
                                        t('5. Fees and Payments')
                                    }}</h2>
                                <ul class="list-disc pl-6 mb-4 space-y-2">
                                    <li>{{ t('League and tournament entry fees are set by organizers') }}</li>
                                    <li>{{ t('Time fund contributions for table reservations') }}</li>
                                    <li>{{ t('Prize distributions according to tournament rules') }}</li>
                                    <li>{{ t('Penalty fees for no-shows or rule violations') }}</li>
                                </ul>
                            </section>

                            <section class="mb-8">
                                <h2 class="text-2xl font-semibold text-gray-800 mb-4">{{
                                        t('6. Intellectual Property')
                                    }}</h2>
                                <p class="text-gray-700 mb-4">
                                    {{
                                        t('The Service and its original content, features, and functionality are owned by WinnerBreak and are protected by international copyright, trademark, and other intellectual property laws.')
                                    }}
                                </p>
                            </section>

                            <section class="mb-8">
                                <h2 class="text-2xl font-semibold text-gray-800 mb-4">{{ t('7. User Content') }}</h2>
                                <ul class="list-disc pl-6 mb-4 space-y-2">
                                    <li>{{ t('You retain rights to your profile content') }}</li>
                                    <li>{{
                                            t('You grant us license to use match data for ratings and statistics')
                                        }}
                                    </li>
                                    <li>{{ t('Public match results and rankings may be displayed') }}</li>
                                    <li>{{ t('We may use anonymized data for improving the Service') }}</li>
                                </ul>
                            </section>

                            <section class="mb-8">
                                <h2 class="text-2xl font-semibold text-gray-800 mb-4">{{ t('8. Prohibited Uses') }}</h2>
                                <p class="text-gray-700 mb-4">{{ t('You may not:') }}</p>
                                <ul class="list-disc pl-6 mb-4 space-y-2">
                                    <li>{{ t('Use the Service for any illegal purposes') }}</li>
                                    <li>{{ t('Attempt to gain unauthorized access to any part of the Service') }}</li>
                                    <li>{{ t('Interfere with or disrupt the Service') }}</li>
                                    <li>{{ t('Create multiple accounts or impersonate others') }}</li>
                                    <li>{{ t('Use automated systems to access the Service') }}</li>
                                    <li>{{ t('Sell or transfer your account') }}</li>
                                </ul>
                            </section>

                            <section class="mb-8">
                                <h2 class="text-2xl font-semibold text-gray-800 mb-4">{{ t('9. Termination') }}</h2>
                                <ul class="list-disc pl-6 mb-4 space-y-2">
                                    <li>{{
                                            t('We may terminate or suspend your account for violations of these Terms')
                                        }}
                                    </li>
                                    <li>{{ t('You may delete your account at any time') }}</li>
                                    <li>{{ t('Upon termination, your right to use the Service will cease') }}</li>
                                    <li>{{
                                            t('Some information may be retained for legal or statistical purposes')
                                        }}
                                    </li>
                                </ul>
                            </section>

                            <section class="mb-8">
                                <h2 class="text-2xl font-semibold text-gray-800 mb-4">{{ t('10. Disclaimers') }}</h2>
                                <ul class="list-disc pl-6 mb-4 space-y-2">
                                    <li>{{ t('The Service is provided "as is" without warranties') }}</li>
                                    <li>{{ t('We do not guarantee continuous, error-free operation') }}</li>
                                    <li>{{ t('We are not responsible for user conduct or content') }}</li>
                                    <li>{{ t('Rankings and ratings are calculated algorithmically') }}</li>
                                </ul>
                            </section>

                            <section class="mb-8">
                                <h2 class="text-2xl font-semibold text-gray-800 mb-4">
                                    {{ t('11. Limitation of Liability') }}</h2>
                                <p class="text-gray-700 mb-4">
                                    {{
                                        t('To the maximum extent permitted by law, WinnerBreak shall not be liable for any indirect, incidental, special, consequential, or punitive damages resulting from your use of the Service.')
                                    }}
                                </p>
                            </section>

                            <section class="mb-8">
                                <h2 class="text-2xl font-semibold text-gray-800 mb-4">{{
                                        t('12. Dispute Resolution')
                                    }}</h2>
                                <ul class="list-disc pl-6 mb-4 space-y-2">
                                    <li>{{ t('Match disputes should first be resolved between players') }}</li>
                                    <li>{{ t('League administrators have authority over league disputes') }}</li>
                                    <li>{{ t('Platform-level disputes will be handled by WinnerBreak support') }}</li>
                                    <li>{{ t('All disputes shall be governed by Ukrainian law') }}</li>
                                </ul>
                            </section>

                            <section class="mb-8">
                                <h2 class="text-2xl font-semibold text-gray-800 mb-4">{{
                                        t('13. Changes to Terms')
                                    }}</h2>
                                <p class="text-gray-700 mb-4">
                                    {{
                                        t('We reserve the right to modify these Terms at any time. We will notify users of any material changes. Your continued use of the Service after changes constitutes acceptance of the new Terms.')
                                    }}
                                </p>
                            </section>

                            <section class="mb-8">
                                <h2 class="text-2xl font-semibold text-gray-800 mb-4">{{
                                        t('14. Contact Information')
                                    }}</h2>
                                <p class="text-gray-700 mb-4">
                                    {{ t('For questions about these Terms, please contact us at:') }}
                                </p>
                                <address class="text-gray-700 not-italic">
                                    {{ t('Email') }}: support@winnerbreak.com<br>
                                    {{ t('Address') }}: Lviv, Ukraine
                                </address>
                            </section>

                            <div class="mt-12 p-6 bg-gray-100 rounded-lg">
                                <p class="text-sm text-gray-600">
                                    {{
                                        t('By using WinnerBreak, you acknowledge that you have read, understood, and agree to be bound by these Terms of Service.')
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup lang="ts">
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import {Head} from '@inertiajs/vue3';
import {useLocale} from "@/composables/useLocale";

const {t} = useLocale();
</script>
