import type { Component } from 'vue';
import {
    ChevronDownIcon,
    ChevronUpDownIcon,
    ChevronUpIcon,
    ClipboardDocumentCheckIcon,
    ClipboardDocumentIcon,
    ExclamationCircleIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import { ChevronRightIcon as ChevronRightMiniIcon } from '@heroicons/vue/20/solid';
import {
    PhCaretDown,
    PhCaretLeft,
    PhCheck,
    PhCheckCircle,
    PhDesktop,
    PhEye,
    PhEyeSlash,
    PhGear,
    PhHouse,
    PhInfo,
    PhList,
    PhMagnifyingGlass,
    PhMinus,
    PhMoon,
    PhQuestion,
    PhSignOut,
    PhSun,
    PhUser,
    PhWarning,
    PhWarningCircle,
    PhX,
} from '@phosphor-icons/vue';
import type { HeroiconSet } from './iconFiles';

/**
 * Icon names work as they did in Blade:
 *   - Phosphor by default: `house`, `caret-down`, with variant thin | light | regular | bold | fill | duotone
 *   - Heroicons with a `hi:` prefix: `hi:x-mark`, with variant outline | solid | mini | micro
 *
 * The icons the ui kit itself uses are bundled so they never flicker in. Every other icon loads on first
 * use from its own small file (see iconFiles.ts), so the whole set is available without shipping it all.
 * When adding an icon here, also add it to the exclusions in iconFiles.ts.
 */
const bundled: Record<string, Component> = {
    'ph:CaretDown': PhCaretDown,
    'ph:CaretLeft': PhCaretLeft,
    'ph:Check': PhCheck,
    'ph:CheckCircle': PhCheckCircle,
    'ph:Desktop': PhDesktop,
    'ph:Eye': PhEye,
    'ph:EyeSlash': PhEyeSlash,
    'ph:Gear': PhGear,
    'ph:House': PhHouse,
    'ph:Info': PhInfo,
    'ph:List': PhList,
    'ph:MagnifyingGlass': PhMagnifyingGlass,
    'ph:Minus': PhMinus,
    'ph:Moon': PhMoon,
    'ph:Question': PhQuestion,
    'ph:SignOut': PhSignOut,
    'ph:Sun': PhSun,
    'ph:User': PhUser,
    'ph:Warning': PhWarning,
    'ph:WarningCircle': PhWarningCircle,
    'ph:X': PhX,
    '24/outline:XMark': XMarkIcon,
    '24/outline:ExclamationCircle': ExclamationCircleIcon,
    '24/outline:ChevronUp': ChevronUpIcon,
    '24/outline:ChevronDown': ChevronDownIcon,
    '24/outline:ChevronUpDown': ChevronUpDownIcon,
    '24/outline:ClipboardDocument': ClipboardDocumentIcon,
    '24/outline:ClipboardDocumentCheck': ClipboardDocumentCheckIcon,
    '20/solid:ChevronRight': ChevronRightMiniIcon,
};

const loaded = new Map<string, Component>(Object.entries(bundled));
const pending = new Map<string, Promise<Component | null>>();

let fileTable: Promise<typeof import('./iconFiles')> | null = null;

const PHOSPHOR_WEIGHTS = ['thin', 'light', 'regular', 'bold', 'fill', 'duotone'] as const;

export type PhosphorWeight = (typeof PHOSPHOR_WEIGHTS)[number];

const pascal = (name: string) =>
    name
        .split(/[-_\s]+/)
        .filter(Boolean)
        .map((part) => part[0].toUpperCase() + part.slice(1))
        .join('');

export interface ResolvedIcon {
    key: string;
    isPhosphor: boolean;
    /** Phosphor draws every weight from one component; heroicons pick a file per variant instead. */
    weight?: PhosphorWeight;
    load: () => Promise<Component | null>;
}

export function resolveIcon(name: string, variant?: string | null): ResolvedIcon {
    const isPhosphor = !name.startsWith('hi:');

    if (isPhosphor) {
        const componentName = pascal(name);
        const key = `ph:${componentName}`;
        const weight = (PHOSPHOR_WEIGHTS as readonly string[]).includes(variant ?? '') ? (variant as PhosphorWeight) : 'regular';
        const file = `/node_modules/@phosphor-icons/vue/dist/icons/Ph${componentName}.vue.mjs`;

        return { key, isPhosphor, weight, load: () => load(key, name, (files) => files.phosphorFiles[file]) };
    }

    const set: HeroiconSet = variant === 'solid' ? '24/solid' : variant === 'mini' ? '20/solid' : variant === 'micro' ? '16/solid' : '24/outline';
    const componentName = pascal(name.slice(3));
    const key = `${set}:${componentName}`;
    const file = `/node_modules/@heroicons/vue/${set}/esm/${componentName}Icon.js`;

    return { key, isPhosphor, load: () => load(key, name, (files) => files.heroiconFiles[set][file]) };
}

export function getLoadedIcon(key: string): Component | undefined {
    return loaded.get(key);
}

type Files = typeof import('./iconFiles');

function load(key: string, name: string, pick: (files: Files) => (() => Promise<{ default: Component }>) | undefined): Promise<Component | null> {
    const cached = loaded.get(key);
    if (cached) return Promise.resolve(cached);

    let promise = pending.get(key);

    if (!promise) {
        fileTable ??= import('./iconFiles');

        promise = fileTable.then(async (files) => {
            const importer = pick(files);

            if (!importer) {
                if (import.meta.env.DEV) console.warn(`[ui/icon] Unknown icon "${name}".`);
                return null;
            }

            const module = await importer();
            loaded.set(key, module.default);
            pending.delete(key);

            return module.default;
        });

        pending.set(key, promise);
    }

    return promise;
}
