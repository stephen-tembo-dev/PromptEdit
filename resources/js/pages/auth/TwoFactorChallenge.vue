<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button, Field, FieldError, Input, Label } from '@/components/ui';
import AuthLayout from '@/layouts/AuthLayout.vue';

const useRecoveryCode = ref(false);

const form = useForm({
    code: '',
    recovery_code: '',
});

const toggle = () => {
    useRecoveryCode.value = !useRecoveryCode.value;
    form.reset();
    form.clearErrors();
};

const submit = () => {
    form.transform((data) => (useRecoveryCode.value ? { recovery_code: data.recovery_code } : { code: data.code })).post('/two-factor-challenge');
};
</script>

<template>
    <AuthLayout
        title="Two-factor authentication"
        :description="useRecoveryCode ? 'Enter one of your emergency recovery codes.' : 'Enter the 6-digit code from your authenticator app.'"
    >
        <form class="space-y-4" @submit.prevent="submit">
            <Field v-if="!useRecoveryCode">
                <Label for="code">Code</Label>
                <Input id="code" v-model="form.code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" required autofocus />
                <FieldError name="code" />
            </Field>

            <Field v-else>
                <Label for="recovery_code">Recovery code</Label>
                <Input id="recovery_code" v-model="form.recovery_code" name="recovery_code" type="text" autocomplete="one-time-code" required autofocus />
                <FieldError name="recovery_code" />
            </Field>

            <Button type="submit" class="w-full" :loading="form.processing">Continue</Button>
        </form>

        <template #footer>
            <Button variant="soft" size="sm" @click="toggle">
                {{ useRecoveryCode ? 'Use an authentication code' : 'Use a recovery code' }}
            </Button>
        </template>
    </AuthLayout>
</template>
