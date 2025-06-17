// resources/js/Components/ui/DialogContent.vue
<script lang="ts" setup>
import {XIcon} from 'lucide-vue-next';
import {Button} from "@/Components/ui";

interface Props {
  showCloseButton?: boolean;
  class?: string;
}

withDefaults(defineProps<Props>(), {
  showCloseButton: true,
});

const emit = defineEmits<{
  close: [];
}>();

const handleClose = () => {
  emit('close');
};
</script>

<template>
  <div :class="['relative flex flex-col', $props.class]">
    <!-- Close button -->
    <Button
        v-if="showCloseButton"
        class="absolute right-4 top-4 z-10 h-6 w-6 p-0"
        size="sm"
        variant="ghost"
        @click="handleClose"
    >
      <XIcon class="h-4 w-4"/>
      <span class="sr-only">Close</span>
    </Button>

    <!-- Content -->
    <div class="flex-1 overflow-y-auto p-6">
      <slot/>
    </div>
  </div>
</template>
