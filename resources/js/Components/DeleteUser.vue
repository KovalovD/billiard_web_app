<script lang="ts" setup>
import {useForm} from '@inertiajs/vue3';
import {ref} from 'vue';

// Components
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import {Button} from '@/components/ui/button';
import {useLocale} from '@/composables/useLocale';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from './ui/dialog/Dialog.vue';
import {Input} from '@/components/ui/input';
import {Label} from '@/components/ui/label';

const passwordInput = ref<HTMLInputElement | null>(null);
const { t } = useLocale();

const form = useForm({
    password: '',
});

const deleteUser = (e: Event) => {
    e.preventDefault();

    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value?.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    form.clearErrors();
    form.reset();
};
</script>

<template>
    <div class="space-y-6">
      <HeadingSmall :description="t('Delete your account and all of its resources')" :title="t('Delete account')"/>
        <div class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
            <div class="relative space-y-0.5 text-red-600 dark:text-red-100">
                <p class="font-medium">{{ t('Warning') }}</p>
                <p class="text-sm">{{ t('Please proceed with caution, this cannot be undone.') }}</p>
            </div>
            <Dialog>
                <DialogTrigger as-child>
                    <Button variant="destructive">{{ t('Delete account') }}</Button>
                </DialogTrigger>
                <DialogContent>
                    <form class="space-y-6" @submit="deleteUser">
                        <DialogHeader class="space-y-3">
                            <DialogTitle>{{ t('Are you sure you want to delete your account?') }}</DialogTitle>
                            <DialogDescription>
                              {{ t('Once your account is deleted, all of its resources and data will also be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label class="sr-only" for="password">{{ t('Password') }}</Label>
                          <Input id="password" ref="passwordInput" v-model="form.password" name="password"
                                 :placeholder="t('Password')" type="password"/>
                          <InputError :message="form.errors.password"/>
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button variant="secondary" @click="closeModal"> {{ t('Cancel') }}</Button>
                            </DialogClose>

                            <Button :disabled="form.processing" variant="destructive">
                                <button type="submit">{{ t('Delete account') }}</button>
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>
