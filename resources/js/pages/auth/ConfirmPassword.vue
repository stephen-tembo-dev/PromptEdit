<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Button, Field, FieldError, Input, Label } from '@/components/ui';
import AuthLayout from '@/layouts/AuthLayout.vue';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post('/user/confirm-password', {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <AuthLayout title="Confirm password" description="This is a secure area. Please confirm your password to continue.">
        <form class="space-y-4" @submit.prevent="submit">
            <Field>
                <Label for="password">Password</Label>
                <Input id="password" v-model="form.password" name="password" type="password" autocomplete="current-password" revealable required autofocus />
                <FieldError name="password" />
            </Field>

            <Button type="submit" class="w-full" :loading="form.processing">Confirm</Button>
        </form>
    </AuthLayout>
</template>
