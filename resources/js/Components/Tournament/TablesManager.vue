<script lang="ts" setup>
import {onMounted, ref} from 'vue'
import {apiClient} from '@/lib/apiClient'
import {useLocale} from '@/composables/useLocale'
import type {ClubTable} from '@/types/api'
import {Button, Card, CardContent, CardHeader, CardTitle, Input, Label, Modal, Spinner} from '@/Components/ui'
import {CheckIcon, CopyIcon, LinkIcon, MonitorIcon, TabletIcon} from 'lucide-vue-next'

interface Props {
    tournamentId: number | string
    show: boolean
}

const props = defineProps<Props>()
const emit = defineEmits(['close'])

const {t} = useLocale()

const tables = ref<ClubTable[]>([])
const isLoading = ref(true)
const isSaving = ref(false)
const error = ref<string | null>(null)
const copiedWidgetTableId = ref<number | null>(null)
const copiedTabletTableId = ref<number | null>(null)

const selectedTable = ref<ClubTable | null>(null)
const streamUrl = ref('')
const getTabletMatchUrl = (tableId: number): string => {
    return `${window.location.origin}/widgets/table-match?tournament=${props.tournamentId}&table=${tableId}`
}

const copyTabletUrl = async (tableId: number) => {
    const url = getTabletMatchUrl(tableId)

    try {
        await navigator.clipboard.writeText(url)
        copiedTabletTableId.value = tableId
        setTimeout(() => {
            copiedTabletTableId.value = null
        }, 2000)
    } catch (err) {
        console.error('Failed to copy:', err)
    }
}
const loadTables = async () => {
    isLoading.value = true
    error.value = null

    try {
        tables.value = await apiClient<ClubTable[]>(`/api/admin/tournaments/${props.tournamentId}/tables`)
    } catch (err: any) {
        error.value = err.message || t('Failed to load tables')
    } finally {
        isLoading.value = false
    }
}

const selectTable = (table: ClubTable) => {
    selectedTable.value = table
    streamUrl.value = table.stream_url || ''
}

const updateStreamUrl = async () => {
    if (!selectedTable.value) return

    isSaving.value = true
    error.value = null

    try {
        await apiClient(`/api/admin/tournaments/${props.tournamentId}/tables/${selectedTable.value.id}/stream-url`, {
            method: 'PUT',
            data: {stream_url: streamUrl.value || null}
        })

        const index = tables.value.findIndex(t => t.id === selectedTable.value!.id)
        if (index !== -1) {
            tables.value[index] = {...tables.value[index], stream_url: streamUrl.value}
        }

        selectedTable.value = null
        streamUrl.value = ''
    } catch (err: any) {
        error.value = err.message || t('Failed to update stream URL')
    } finally {
        isSaving.value = false
    }
}

const getWidgetUrl = (tableId: number): string => {
    return `${window.location.origin}/widgets/table?tournament=${props.tournamentId}&table=${tableId}`
}

const getWidgetUrlWithParams = (tableId: number): string => {
    const baseUrl = getWidgetUrl(tableId)
    return `${baseUrl}&theme=dark&layout=horizontal&refresh=5000&compact=true`
}

const copyWidgetUrl = async (tableId: number) => {
    const url = getWidgetUrlWithParams(tableId)

    try {
        await navigator.clipboard.writeText(url)
        copiedWidgetTableId.value = tableId
        setTimeout(() => {
            copiedWidgetTableId.value = null
        }, 2000)
    } catch (err) {
        console.error('Failed to copy:', err)
    }
}

onMounted(() => {
    loadTables()
})
</script>

<template>
    <Modal :show="show" :title="t('Tournament Tables - OBS Widgets')" max-width="3xl" @close="emit('close')">
        <div class="space-y-6">
            <!-- Instructions -->
            <div class="rounded-lg bg-blue-50 dark:bg-blue-900/20 p-4">
                <h3 class="font-semibold text-blue-900 dark:text-blue-100 mb-2">{{ t('Setup Instructions') }}</h3>
                <div class="grid md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <h4 class="font-medium text-blue-800 dark:text-blue-200 mb-1">{{ t('OBS Browser Source') }}</h4>
                        <ol class="text-blue-700 dark:text-blue-300 space-y-0.5 list-decimal list-inside">
                            <li>{{ t('Copy the OBS widget URL') }}</li>
                            <li>{{ t('Add Browser Source in OBS') }}</li>
                            <li>{{ t('Set size: 800x100-200px') }}</li>
                        </ol>
                    </div>
                    <div>
                        <h4 class="font-medium text-blue-800 dark:text-blue-200 mb-1">{{
                                t('Tablet Score Control')
                            }}</h4>
                        <ol class="text-blue-700 dark:text-blue-300 space-y-0.5 list-decimal list-inside">
                            <li>{{ t('Copy the tablet URL') }}</li>
                            <li>{{ t('Open on tablet near table') }}</li>
                            <li>{{ t('Players update scores with +/-') }}</li>
                        </ol>
                    </div>
                </div>
            </div>

            <!-- Error Display -->
            <div v-if="error" class="rounded bg-red-100 p-4 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                {{ error }}
            </div>

            <!-- Loading -->
            <div v-if="isLoading" class="flex justify-center py-8">
                <Spinner class="h-8 w-8"/>
            </div>

            <!-- Tables Grid -->
            <div v-else-if="tables.length > 0" class="grid gap-4 md:grid-cols-2">
                <Card v-for="table in tables" :key="table.id">
                    <CardHeader>
                        <CardTitle class="flex items-center justify-between">
                            <span class="flex items-center gap-2">
                                <MonitorIcon class="h-5 w-5"/>
                                {{ table.name }}
                            </span>
                            <Button
                                size="sm"
                                variant="outline"
                                @click="selectTable(table)"
                            >
                                <LinkIcon class="mr-2 h-4 w-4"/>
                                {{ t('Stream URL') }}
                            </Button>
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <!-- Stream URL Status -->
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">{{ t('Stream URL') }}:</span>
                                <span v-if="table.stream_url" class="text-green-600 dark:text-green-400">
                                    ✓ {{ t('Configured') }}
                                </span>
                                <span v-else class="text-gray-500">
                                    {{ t('Not set') }}
                                </span>
                            </div>

                            <!-- Widget Links Section -->
                            <div class="space-y-2">
                                <!-- OBS Widget -->
                                <Button
                                    class="w-full"
                                    size="sm"
                                    variant="outline"
                                    @click="copyWidgetUrl(table.id)"
                                >
                                    <template v-if="copiedWidgetTableId === table.id">
                                        <CheckIcon class="mr-2 h-4 w-4 text-green-600"/>
                                        {{ t('Copied!') }}
                                    </template>
                                    <template v-else>
                                        <CopyIcon class="mr-2 h-4 w-4"/>
                                        {{ t('Copy OBS Widget URL') }}
                                    </template>
                                </Button>

                                <!-- Tablet Match Control -->
                                <Button
                                    class="w-full"
                                    size="sm"
                                    variant="outline"
                                    @click="copyTabletUrl(table.id)"
                                >
                                    <template v-if="copiedTabletTableId === table.id">
                                        <CheckIcon class="mr-2 h-4 w-4 text-green-600"/>
                                        {{ t('Copied!') }}
                                    </template>
                                    <template v-else>
                                        <TabletIcon class="mr-2 h-4 w-4"/>
                                        {{ t('Copy Tablet Control URL') }}
                                    </template>
                                </Button>
                            </div>

                            <!-- Links Preview -->
                            <div class="space-y-2 text-xs">
                                <details class="cursor-pointer">
                                    <summary
                                        class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                                        {{ t('View URLs') }}
                                    </summary>
                                    <div class="mt-2 space-y-2">
                                        <div>
                                            <p class="font-medium text-gray-700 dark:text-gray-300">{{
                                                    t('OBS Widget:')
                                                }}</p>
                                            <div class="rounded bg-gray-100 p-1 dark:bg-gray-800">
                                                <code class="text-xs break-all">{{ getWidgetUrl(table.id) }}</code>
                                            </div>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-700 dark:text-gray-300">
                                                {{ t('Tablet Control:') }}</p>
                                            <div class="rounded bg-gray-100 p-1 dark:bg-gray-800">
                                                <code class="text-xs break-all">{{ getTabletMatchUrl(table.id) }}</code>
                                            </div>
                                        </div>
                                    </div>
                                </details>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
            <!-- No Tables -->
            <div v-else class="text-center py-8 text-gray-500">
                {{ t('No tables available for this tournament') }}
            </div>
        </div>

        <template #footer>
            <Button variant="outline" @click="emit('close')">
                {{ t('Close') }}
            </Button>
        </template>
    </Modal>

    <!-- Edit Stream URL Modal -->
    <Modal
        :show="selectedTable !== null"
        :title="selectedTable ? `${t('Edit Stream URL')}: ${selectedTable.name}` : ''"
        @close="selectedTable = null"
    >
        <div class="space-y-4">
            <div>
                <Label for="stream_url">{{ t('Stream URL') }}</Label>
                <Input
                    id="stream_url"
                    v-model="streamUrl"
                    :placeholder="t('https://...')"
                    type="url"
                />
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    {{ t('Optional: URL for the stream associated with this table') }}
                </p>
            </div>
        </div>

        <template #footer>
            <Button variant="outline" @click="selectedTable = null">
                {{ t('Cancel') }}
            </Button>
            <Button :disabled="isSaving" @click="updateStreamUrl">
                <Spinner v-if="isSaving" class="mr-2 h-4 w-4"/>
                {{ t('Save') }}
            </Button>
        </template>
    </Modal>
</template>
