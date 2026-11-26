<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Alert, Button } from '@/components/ui';
import AuthLayout from '@/layouts/AuthLayout.vue';

defineProps<{
    status?: string;
}>();

const form = useForm({});

const submit = () => {
    form.post('/email/verification-notification');
};
</script>

<template>
    <AuthLayout title="Verify your email" description="Click the link we just emailed you to verify your address.">
        <Alert v-if="status === 'verification-link-sent'" variant="success">A new verification link has been sent to your email address.</Alert>

        <form @submit.prevent="submit">
            <Button type="submit" class="w-full" :loading="form.processing">Resend verification email</Button>
        </form>

        <template #footer>
            <Button href="/logout" method="post" as="button" variant="soft" size="sm">Log out</Button>
        </template>
    </AuthLayout>
</template>
