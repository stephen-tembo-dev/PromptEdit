<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Alert, Button, Checkbox, Field, FieldError, Input, Label, TextLink } from '@/components/ui';
import AuthLayout from '@/layouts/AuthLayout.vue';

defineProps<{
    canRegister: boolean;
    canResetPassword: boolean;
    status?: string;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <AuthLayout title="Log in" description="Enter your email and password to continue.">
        <Alert v-if="status" variant="success">{{ status }}</Alert>

        <form class="space-y-4" @submit.prevent="submit">
            <Field>
                <Label for="email">Email</Label>
                <Input id="email" v-model="form.email" name="email" type="email" autocomplete="username" placeholder="name@example.com" required autofocus />
                <FieldError name="email" />
            </Field>

            <Field>
                <div class="mb-2 flex items-baseline justify-between">
                    <Label for="password">Password</Label>
                    <TextLink v-if="canResetPassword" href="/forgot-password" variant="soft" class="text-sm">Forgot?</TextLink>
                </div>
                <Input id="password" v-model="form.password" name="password" type="password" autocomplete="current-password" placeholder="••••••••" revealable required />
                <FieldError name="password" />
            </Field>

            <Checkbox v-model="form.remember" name="remember" label="Keep me signed in" size="sm" />

            <Button type="submit" class="w-full" :loading="form.processing">Log in</Button>
        </form>

        <template v-if="canRegister" #footer>
            Don't have an account?
            <TextLink href="/register">Sign up</TextLink>
        </template>
    </AuthLayout>
</template>
