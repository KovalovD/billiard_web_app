<script lang="ts" setup>
import {computed, ref} from 'vue';
import {
    Button,
    Input,
    Label,
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
    Textarea
} from '@/Components/ui';
import {EditIcon, PlusIcon, TrashIcon} from 'lucide-vue-next';
import {useLocale} from '@/composables/useLocale';

interface Equipment {
    id?: string;
    type: 'cue' | 'case' | 'chalk' | 'glove' | 'other';
    brand: string;
    model?: string;
    description?: string;
}

interface Props {
    modelValue: Equipment[];
    disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: () => [],
    disabled: false
});

const emit = defineEmits<{
    'update:modelValue': [value: Equipment[]];
}>();

const {t} = useLocale();

const equipment = computed({
    get: () => props.modelValue || [],
    set: (value) => emit('update:modelValue', value)
});

const editingIndex = ref<number | null>(null);
const editingItem = ref<Equipment>({
    type: 'cue',
    brand: '',
    model: '',
    description: ''
});

const equipmentTypes = [
    {value: 'cue', label: t('Cue'), icon: '🎱'},
    {value: 'case', label: t('Case'), icon: '💼'},
    {value: 'chalk', label: t('Chalk'), icon: '🟦'},
    {value: 'glove', label: t('Glove'), icon: '🧤'},
    {value: 'other', label: t('Other'), icon: '📦'}
];

const getEquipmentIcon = (type: string) => {
    return equipmentTypes.find(t => t.value === type)?.icon || '📦';
};

const getEquipmentLabel = (type: string) => {
    return equipmentTypes.find(t => t.value === type)?.label || type;
};

const addEquipment = () => {
    if (!editingItem.value.brand.trim()) return;

    const newItem = {
        ...editingItem.value,
        id: Date.now().toString()
    };

    equipment.value = [...equipment.value, newItem];
    resetForm();
};

const editEquipment = (index: number) => {
    editingIndex.value = index;
    editingItem.value = {...equipment.value[index]};
};

const updateEquipment = () => {
    if (editingIndex.value === null || !editingItem.value.brand.trim()) return;

    const updatedList = [...equipment.value];
    updatedList[editingIndex.value] = {...editingItem.value};
    equipment.value = updatedList;
    resetForm();
};

const removeEquipment = (index: number) => {
    equipment.value = equipment.value.filter((_, i) => i !== index);
    if (editingIndex.value === index) {
        resetForm();
    }
};

const resetForm = () => {
    editingIndex.value = null;
    editingItem.value = {
        type: 'cue',
        brand: '',
        model: '',
        description: ''
    };
};

const isFormValid = computed(() => {
    return editingItem.value.brand.trim() !== '';
});
</script>

<template>
    <div class="space-y-4">
        <!-- Equipment List -->
        <div v-if="equipment.length > 0" class="space-y-2">
            <div
                v-for="(item, index) in equipment"
                :key="item.id || index"
                class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg"
            >
                <span class="text-2xl">{{ getEquipmentIcon(item.type) }}</span>
                <div class="flex-1">
                    <div class="font-medium text-gray-900 dark:text-white">
                        {{ item.brand }}
                        <span v-if="item.model" class="text-gray-600 dark:text-gray-400">
                            - {{ item.model }}
                        </span>
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        {{ getEquipmentLabel(item.type) }}
                        <span v-if="item.description" class="ml-2">
                            • {{ item.description }}
                        </span>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Button
                        v-if="editingIndex !== index"
                        size="sm"
                        variant="ghost"
                        :disabled="disabled"
                        @click="editEquipment(index)"
                    >
                        <EditIcon class="h-4 w-4"/>
                    </Button>
                    <Button
                        size="sm"
                        variant="ghost"
                        :disabled="disabled"
                        @click="removeEquipment(index)"
                    >
                        <TrashIcon class="h-4 w-4 text-red-500"/>
                    </Button>
                </div>
            </div>
        </div>

        <!-- Add/Edit Form -->
        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                {{ editingIndex !== null ? t('Edit Equipment') : t('Add Equipment') }}
            </h4>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <Label for="equipment-type">{{ t('Type') }}</Label>
                    <Select
                        v-model="editingItem.type"
                        :disabled="disabled"
                    >
                        <SelectTrigger id="equipment-type">
                            <SelectValue :placeholder="t('Cue')"/>
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="type in equipmentTypes"
                                :key="type.value"
                                :value="type.value"
                            >
                                <span class="flex items-center gap-2">
                                    <span>{{ type.icon }}</span>
                                    {{ type.label }}
                                </span>
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div>
                    <Label for="equipment-brand">{{ t('Brand') }} *</Label>
                    <Input
                        id="equipment-brand"
                        v-model="editingItem.brand"
                        :placeholder="t('e.g., Predator, Mezz')"
                        :disabled="disabled"
                    />
                </div>

                <div>
                    <Label for="equipment-model">{{ t('Model') }}</Label>
                    <Input
                        id="equipment-model"
                        v-model="editingItem.model"
                        :placeholder="t('e.g., P3, EC7')"
                        :disabled="disabled"
                    />
                </div>

                <div class="sm:col-span-2">
                    <Label for="equipment-description">{{ t('Description') }}</Label>
                    <Textarea
                        id="equipment-description"
                        v-model="editingItem.description"
                        :placeholder="t('Additional details about this equipment...')"
                        :disabled="disabled"
                        rows="2"
                    />
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-4">
                <Button
                    v-if="editingIndex !== null"
                    variant="outline"
                    size="sm"
                    :disabled="disabled"
                    @click="resetForm"
                >
                    {{ t('Cancel') }}
                </Button>
                <Button
                    size="sm"
                    :disabled="disabled || !isFormValid"
                    @click="editingIndex !== null ? updateEquipment() : addEquipment()"
                >
                    <PlusIcon v-if="editingIndex === null" class="mr-2 h-4 w-4"/>
                    {{ editingIndex !== null ? t('Update') : t('Add') }}
                </Button>
            </div>
        </div>
    </div>
</template>
