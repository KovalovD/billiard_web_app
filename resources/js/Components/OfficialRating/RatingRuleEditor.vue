<script lang="ts" setup>
import {Button, Card, CardContent, CardHeader, CardTitle, Input, Label} from '@/Components/ui';
import type {RatingRuleItem} from '@/types/api';
import {MinusIcon, PlusIcon} from 'lucide-vue-next';
import {computed, ref, watch} from 'vue';

interface Props {
    modelValue: string;
    disabled?: boolean;
    isWinners?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    disabled: false,
    isWinners: true,
});

const emit = defineEmits(['update:modelValue']);

// Parse JSON to rule items
const rules = ref<RatingRuleItem[]>([]);

// Parse initial value
const parseRules = (jsonString: string) => {
    try {
        const parsed = JSON.parse(jsonString);
        rules.value = Array.isArray(parsed) ? parsed : [];
        // eslint-disable-next-line
    } catch (e) {
        // Default rule
        rules.value = [{range: [0, 50], strong: props.isWinners ? 25 : -25, weak: props.isWinners ? 25 : -25}];
    }
};

// Watch for changes in modelValue
watch(
    () => props.modelValue,
    (newValue) => {
        parseRules(newValue);
    },
    {immediate: true},
);

// Update parent when rules change
watch(
    rules,
    (newRules) => {
        emit('update:modelValue', JSON.stringify(newRules, null, 2));
    },
    {deep: true},
);

const addRule = () => {
    const lastRule = rules.value[rules.value.length - 1];
    const newRange: [number, number] = [lastRule ? lastRule.range[1] + 1 : 0, 1000000];

    rules.value.push({
        range: newRange,
        strong: props.isWinners ? 10 : -10,
        weak: props.isWinners ? 40 : -40,
    });
};

const removeRule = (index: number) => {
    if (rules.value.length > 1) {
        rules.value.splice(index, 1);
    }
};

// Ensure ranges are continuous
const updateRange = (index: number, field: 'min' | 'max', value: number) => {
    const rule = rules.value[index];

    if (field === 'min') {
        rule.range[0] = value;
        // Update previous rule's max if exists
        if (index > 0 && value > 0) {
            rules.value[index - 1].range[1] = value - 1;
        }
    } else {
        rule.range[1] = value;
        // Update next rule's min if exists
        if (index < rules.value.length - 1) {
            rules.value[index + 1].range[0] = value + 1;
        }
    }
};

const ruleTypeText = computed(() => (props.isWinners ? 'Winners' : 'Losers'));
const pointsTypeText = computed(() => (props.isWinners ? 'Gained' : 'Lost'));
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>{{ ruleTypeText }} Rating Rules</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="space-y-4">
                <div v-for="(rule, index) in rules" :key="index"
                     class="flex flex-wrap items-center gap-4 rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                    <div class="w-full sm:w-auto">
                        <Label :for="`range-${index}`" class="text-sm">Rating Range</Label>
                        <div class="flex items-center gap-2">
                            <Input
                                :id="`range-${index}-min`"
                                :disabled="disabled"
                                :modelValue="rule.range[0]"
                                class="w-24"
                                min="0"
                                type="number"
                                @update:modelValue="(val: string) => updateRange(index, 'min', parseInt(val))"
                            />
                            <span class="text-gray-500">to</span>
                            <Input
                                :id="`range-${index}-max`"
                                :disabled="disabled"
                                :modelValue="rule.range[1]"
                                class="w-24"
                                min="0"
                                type="number"
                                @update:modelValue="(val: string) => updateRange(index, 'max', parseInt(val))"
                            />
                        </div>
                    </div>

                    <div class="w-full sm:w-auto">
                        <Label :for="`strong-${index}`" class="text-sm">Stronger Player {{ pointsTypeText }}</Label>
                        <Input :id="`strong-${index}`" v-model.number="rule.strong" :disabled="disabled" class="w-24"
                               type="number"/>
                    </div>

                    <div class="w-full sm:w-auto">
                        <Label :for="`weak-${index}`" class="text-sm">Weaker Player {{ pointsTypeText }}</Label>
                        <Input :id="`weak-${index}`" v-model.number="rule.weak" :disabled="disabled" class="w-24"
                               type="number"/>
                    </div>

                    <div class="flex w-full items-end pb-6 sm:w-auto">
                        <Button
                            v-if="rules.length > 1"
                            :disabled="disabled"
                            size="icon"
                            title="Remove Rule"
                            variant="outline"
                            @click="removeRule(index)"
                        >
                            <MinusIcon class="h-4 w-4"/>
                        </Button>
                    </div>
                </div>

                <Button v-if="!disabled" class="w-full" variant="outline" @click="addRule">
                    <PlusIcon class="mr-2 h-4 w-4"/>
                    Add Range
                </Button>
            </div>

            <p class="mt-4 text-xs text-gray-500">
                Rating ranges should be continuous. Adjusting one range will automatically update adjacent ranges.
            </p>
        </CardContent>
    </Card>
</template>
