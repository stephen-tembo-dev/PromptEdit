<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Button, Field, FieldError, Input, Label, TextLink } from '@/components/ui';
import AuthLayout from '@/layouts/AuthLayout.vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthLayout title="Create an account" description="Enter your details to get started.">
        <form class="space-y-4" @submit.prevent="submit">
            <Field required>
                <Label for="name">Name</Label>
                <Input id="name" v-model="form.name" name="name" type="text" autocomplete="name" required autofocus />
                <FieldError name="name" />
            </Field>

            <Field required>
                <Label for="email">Email</Label>
                <Input id="email" v-model="form.email" name="email" type="email" autocomplete="email" placeholder="name@example.com" required />
                <FieldError name="email" />
            </Field>

            <Field required>
                <Label for="password">Password</Label>
                <Input id="password" v-model="form.password" name="password" type="password" autocomplete="new-password" revealable required />
                <FieldError name="password" />
            </Field>

            <Field required>
                <Label for="password_confirmation">Confirm password</Label>
                <Input id="password_confirmation" v-model="form.password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" revealable required />
                <FieldError name="password_confirmation" />
            </Field>

            <Button type="submit" class="w-full" :loading="form.processing">Create account</Button>
        </form>

        <template #footer>
            Already have an account?
            <TextLink href="/login">Log in</TextLink>
        </template>
    </AuthLayout>
</template>
