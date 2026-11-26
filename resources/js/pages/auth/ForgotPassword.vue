<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Alert, Button, Field, FieldError, Input, Label, TextLink } from '@/components/ui';
import AuthLayout from '@/layouts/AuthLayout.vue';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post('/forgot-password');
};
</script>

<template>
    <AuthLayout title="Forgot password" description="Enter your email and we'll send you a reset link.">
        <Alert v-if="status" variant="success">{{ status }}</Alert>

        <form class="space-y-4" @submit.prevent="submit">
            <Field>
                <Label for="email">Email</Label>
                <Input id="email" v-model="form.email" name="email" type="email" autocomplete="email" placeholder="name@example.com" required autofocus />
                <FieldError name="email" />
            </Field>

            <Button type="submit" class="w-full" :loading="form.processing">Email reset link</Button>
        </form>

        <template #footer>
            Remembered it?
            <TextLink href="/login">Back to log in</TextLink>
        </template>
    </AuthLayout>
</template>
