<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Button, Field, FieldError, Input, Label } from '@/components/ui';
import AuthLayout from '@/layouts/AuthLayout.vue';

const props = defineProps<{
    email: string;
    token: string;
}>();

const form = useForm({
    token: props.token,
    email: props.email ?? '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/reset-password', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthLayout title="Reset password" description="Choose a new password for your account.">
        <form class="space-y-4" @submit.prevent="submit">
            <Field>
                <Label for="email">Email</Label>
                <Input id="email" v-model="form.email" name="email" type="email" autocomplete="email" required />
                <FieldError name="email" />
            </Field>

            <Field>
                <Label for="password">New password</Label>
                <Input id="password" v-model="form.password" name="password" type="password" autocomplete="new-password" revealable required autofocus />
                <FieldError name="password" />
            </Field>

            <Field>
                <Label for="password_confirmation">Confirm new password</Label>
                <Input id="password_confirmation" v-model="form.password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" revealable required />
                <FieldError name="password_confirmation" />
            </Field>

            <Button type="submit" class="w-full" :loading="form.processing">Reset password</Button>
        </form>
    </AuthLayout>
</template>
