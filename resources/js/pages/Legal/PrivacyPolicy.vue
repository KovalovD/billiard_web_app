<template>
    <AuthenticatedLayout>
        <Head :title="t('Privacy Policy')"/>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 lg:p-8">
                        <h1 class="text-3xl font-bold text-gray-900 mb-8">{{ t('Privacy Policy') }}</h1>

                        <div class="prose prose-lg max-w-none">
                            <p class="text-gray-600 mb-6">
                                {{ t('Last updated') }}: {{ new Date().toLocaleDateString() }}
                            </p>

                            <section class="mb-8">
                                <h2 class="text-2xl font-semibold text-gray-800 mb-4">{{ t('1. Introduction') }}</h2>
                                <p class="text-gray-700 mb-4">
                                    {{
                                        t('WinnerBreak ("we", "our", or "us") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our billiard league management platform.')
                                    }}
                                </p>
                            </section>

                            <section class="mb-8">
                                <h2 class="text-2xl font-semibold text-gray-800 mb-4">{{
                                        t('2. Information We Collect')
                                    }}</h2>

                                <h3 class="text-xl font-medium text-gray-700 mb-3">{{ t('Personal Information') }}</h3>
                                <ul class="list-disc pl-6 mb-4 space-y-2">
                                    <li>{{ t('Name and contact information (email address, phone number)') }}</li>
                                    <li>{{ t('Profile information (username, avatar, club affiliation)') }}</li>
                                    <li>{{ t('Location data (city, club location)') }}</li>
                                    <li>{{ t('Authentication credentials') }}</li>
                                </ul>

                                <h3 class="text-xl font-medium text-gray-700 mb-3">{{
                                        t('Game and Activity Data')
                                    }}</h3>
                                <ul class="list-disc pl-6 mb-4 space-y-2">
                                    <li>{{ t('Match history and results') }}</li>
                                    <li>{{ t('Player ratings and rankings') }}</li>
                                    <li>{{ t('Tournament participation and performance') }}</li>
                                    <li>{{ t('League statistics and achievements') }}</li>
                                </ul>

                                <h3 class="text-xl font-medium text-gray-700 mb-3">{{ t('Technical Information') }}</h3>
                                <ul class="list-disc pl-6 mb-4 space-y-2">
                                    <li>{{ t('IP address and device information') }}</li>
                                    <li>{{ t('Browser type and version') }}</li>
                                    <li>{{ t('Usage patterns and preferences') }}</li>
                                    <li>{{ t('Session data and cookies') }}</li>
                                </ul>
                            </section>

                            <section class="mb-8">
                                <h2 class="text-2xl font-semibold text-gray-800 mb-4">
                                    {{ t('3. How We Use Your Information') }}</h2>
                                <ul class="list-disc pl-6 mb-4 space-y-2">
                                    <li>{{ t('To provide and maintain our services') }}</li>
                                    <li>{{ t('To manage leagues, tournaments, and match scheduling') }}</li>
                                    <li>{{ t('To calculate and update player ratings and rankings') }}</li>
                                    <li>{{ t('To communicate with you about matches, events, and updates') }}</li>
                                    <li>{{ t('To improve our platform and user experience') }}</li>
                                    <li>{{ t('To ensure fair play and prevent fraud') }}</li>
                                </ul>
                            </section>

                            <section class="mb-8">
                                <h2 class="text-2xl font-semibold text-gray-800 mb-4">
                                    {{ t('4. Information Sharing and Disclosure') }}</h2>
                                <p class="text-gray-700 mb-4">
                                    {{ t('We share your information only in the following circumstances:') }}
                                </p>
                                <ul class="list-disc pl-6 mb-4 space-y-2">
                                    <li>{{
                                            t('With other players in your leagues and tournaments (limited profile data)')
                                        }}
                                    </li>
                                    <li>{{
                                            t('With league administrators and club managers for organization purposes')
                                        }}
                                    </li>
                                    <li>{{
                                            t('Public rankings and match results (as part of the competitive system)')
                                        }}
                                    </li>
                                    <li>{{ t('With your consent or at your direction') }}</li>
                                    <li>{{ t('To comply with legal obligations') }}</li>
                                </ul>
                            </section>

                            <section class="mb-8">
                                <h2 class="text-2xl font-semibold text-gray-800 mb-4">{{ t('5. Data Security') }}</h2>
                                <p class="text-gray-700 mb-4">
                                    {{
                                        t('We implement appropriate technical and organizational measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.')
                                    }}
                                </p>
                            </section>

                            <section class="mb-8">
                                <h2 class="text-2xl font-semibold text-gray-800 mb-4">{{
                                        t('6. Your Rights and Choices')
                                    }}</h2>
                                <ul class="list-disc pl-6 mb-4 space-y-2">
                                    <li>{{ t('Access and update your personal information') }}</li>
                                    <li>{{ t('Request deletion of your account and data') }}</li>
                                    <li>{{ t('Opt-out of non-essential communications') }}</li>
                                    <li>{{ t('Control visibility of your profile information') }}</li>
                                    <li>{{ t('Export your match history and statistics') }}</li>
                                </ul>
                            </section>

                            <section class="mb-8">
                                <h2 class="text-2xl font-semibold text-gray-800 mb-4">{{ t('7. Data Retention') }}</h2>
                                <p class="text-gray-700 mb-4">
                                    {{
                                        t('We retain your information for as long as necessary to provide our services and comply with legal obligations. Match history and rankings are maintained for historical and statistical purposes.')
                                    }}
                                </p>
                            </section>

                            <section class="mb-8">
                                <h2 class="text-2xl font-semibold text-gray-800 mb-4">
                                    {{ t('8. International Data Transfers') }}</h2>
                                <p class="text-gray-700 mb-4">
                                    {{
                                        t('Your information may be transferred to and processed in countries other than your country of residence. We ensure appropriate safeguards are in place for such transfers.')
                                    }}
                                </p>
                            </section>

                            <section class="mb-8">
                                <h2 class="text-2xl font-semibold text-gray-800 mb-4">{{
                                        t('9. Children\'s Privacy')
                                    }}</h2>
                                <p class="text-gray-700 mb-4">
                                    {{
                                        t('Our services are not directed to individuals under 16 years of age. We do not knowingly collect personal information from children under 16.')
                                    }}
                                </p>
                            </section>

                            <section class="mb-8">
                                <h2 class="text-2xl font-semibold text-gray-800 mb-4">{{
                                        t('10. Changes to This Policy')
                                    }}</h2>
                                <p class="text-gray-700 mb-4">
                                    {{
                                        t('We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new policy on this page and updating the "Last updated" date.')
                                    }}
                                </p>
                            </section>

                            <section class="mb-8">
                                <h2 class="text-2xl font-semibold text-gray-800 mb-4">{{ t('11. Contact Us') }}</h2>
                                <p class="text-gray-700 mb-4">
                                    {{
                                        t('If you have questions or concerns about this Privacy Policy, please contact us at:')
                                    }}
                                </p>
                                <address class="text-gray-700 not-italic">
                                    {{ t('Email') }}: privacy@winnerbreak.com<br>
                                    {{ t('Address') }}: Lviv, Ukraine
                                </address>
                            </section>
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
import {onMounted} from "vue";
import {useSeo} from "@/composables/useSeo";

const getSeo = useSeo();
const {setSeoMeta} = useSeo();

const {t} = useLocale();
onMounted(() => {
    setSeoMeta({
        title: t('Privacy Policy - WinnerBreak | Data Protection & User Privacy'),
        description: t('WinnerBreak privacy policy. Learn how we collect, use, and protect your personal data. GDPR compliant. Your privacy is our priority. Last updated: :date', {date: new Date().toLocaleDateString()}),
        keywords: [
            'privacy policy', 'политика конфиденциальности',
            'data protection', 'защита данных',
            'GDPR compliance', 'соответствие GDPR',
            'user privacy', 'конфиденциальность пользователей',
            'personal data', 'персональные данные',
            'WinnerBreak privacy', 'конфиденциальность ВиннерБрейк'
        ],
        ogType: 'article',
        canonicalUrl: `${window.location.origin}/privacy-policy`,
        robots: 'index, follow',
        alternateLanguages: getSeo.getAlternateLanguageUrls('/privacy-policy'),
        jsonLd: {
            "@context": "https://schema.org",
            "@type": "WebPage",
            "name": t('Privacy Policy'),
            "description": t('WinnerBreak privacy policy and data protection information'),
            "url": `${window.location.origin}/privacy-policy`,
            "inLanguage": document.documentElement.lang || 'en',
            "isPartOf": {
                "@type": "WebSite",
                "name": "WinnerBreak"
            },
            "datePublished": "2023-01-01",
            "dateModified": new Date().toISOString()
        }
    });
});
</script>
