<script setup lang="ts">
import { useTheme } from '@/composables/useTheme';
import Button from '../button/Button.vue';
import Dropdown from '../dropdown/Dropdown.vue';
import DropdownItem from '../dropdown/DropdownItem.vue';
import Icon from '../icon/Icon.vue';

/**
 *   dropdown — an icon that opens light / dark / system
 *   stacked  — three buttons side by side
 *   inline   — one button that toggles light and dark
 */
withDefaults(
    defineProps<{
        variant?: 'dropdown' | 'stacked' | 'inline';
        darkIcon?: string;
        lightIcon?: string;
        systemIcon?: string;
        iconVariant?: string;
    }>(),
    { variant: 'dropdown', darkIcon: 'moon', lightIcon: 'sun', systemIcon: 'desktop', iconVariant: 'regular' },
);

const theme = useTheme();
</script>

<template>
    <div class="flex items-center">
        <Dropdown v-if="variant === 'dropdown'" position="bottom-end">
            <template #button>
                <button type="button" class="inline-flex size-9 cursor-pointer items-center justify-center rounded-full transition hover:bg-neutral-100 dark:hover:bg-neutral-700" aria-label="Theme">
                    <Icon :name="darkIcon" :variant="iconVariant" class="inline-flex dark:hidden" />
                    <Icon :name="lightIcon" :variant="iconVariant" class="hidden dark:inline-flex" />
                </button>
            </template>
            <template #menu>
                <DropdownItem :icon="lightIcon" :icon-variant="iconVariant" :class="theme.isLight.value ? 'bg-black/5 dark:bg-white/5' : ''" @click="theme.setLight()">Light</DropdownItem>
                <DropdownItem :icon="darkIcon" :icon-variant="iconVariant" :class="theme.isDark.value ? 'bg-black/5 dark:bg-white/5' : ''" @click="theme.setDark()">Dark</DropdownItem>
                <DropdownItem :icon="systemIcon" :icon-variant="iconVariant" :class="theme.isSystem.value ? 'bg-black/5 dark:bg-white/5' : ''" @click="theme.setSystem()">System</DropdownItem>
            </template>
        </Dropdown>

        <div v-else-if="variant === 'stacked'" class="flex items-center overflow-hidden rounded-box border border-black/10 transition-all duration-200 dark:border-white/10">
            <Button
                :icon="lightIcon"
                :icon-variant="iconVariant"
                variant="none"
                :class="['hover:bg-black/5 dark:hover:bg-white/10', theme.isLight.value ? '!bg-black/10 dark:!bg-white/5' : '']"
                :aria-pressed="theme.isLight.value ? 'true' : 'false'"
                aria-label="Light theme"
                @click="theme.setLight()"
            />
            <Button
                :icon="darkIcon"
                :icon-variant="iconVariant"
                variant="none"
                :class="['hover:bg-black/5 dark:hover:bg-white/10', theme.isDark.value ? '!bg-black/10 dark:!bg-white/5' : '']"
                :aria-pressed="theme.isDark.value ? 'true' : 'false'"
                aria-label="Dark theme"
                @click="theme.setDark()"
            />
            <Button
                :icon="systemIcon"
                :icon-variant="iconVariant"
                variant="none"
                :class="['hover:bg-black/5 dark:hover:bg-white/10', theme.isSystem.value ? '!bg-black/10 dark:!bg-white/5' : '']"
                :aria-pressed="theme.isSystem.value ? 'true' : 'false'"
                aria-label="System theme"
                @click="theme.setSystem()"
            />
        </div>

        <Button
            v-else
            :icon="theme.isResolvedToDark.value ? darkIcon : lightIcon"
            :icon-variant="iconVariant"
            variant="none"
            class="size-10 transition hover:opacity-80"
            :aria-label="theme.isResolvedToDark.value ? 'Switch to light theme' : 'Switch to dark theme'"
            @click="theme.toggle()"
        />
    </div>
</template>
