import type { Component } from 'vue';

/**
 * Where every icon file lives, one lazy import per icon. This table alone lists ~2,700 files, so it is
 * itself loaded on demand: only when a page first uses an icon outside the bundled set in icons.ts.
 */
type IconModule = { default: Component };

export type HeroiconSet = '24/outline' | '24/solid' | '20/solid' | '16/solid';

// The icons bundled in icons.ts are left out (they are already in the main chunk).
export const phosphorFiles = import.meta.glob<IconModule>([
    '/node_modules/@phosphor-icons/vue/dist/icons/Ph*.vue.mjs',
    '!**/Ph{CaretDown,CaretLeft,Check,CheckCircle,Desktop,Eye,EyeSlash,Gear,House,Info,List,MagnifyingGlass,Minus,Moon,Question,SignOut,Sun,User,Warning,WarningCircle,X}.vue.mjs',
]);

export const heroiconFiles: Record<HeroiconSet, Record<string, () => Promise<IconModule>>> = {
    '24/outline': import.meta.glob<IconModule>([
        '/node_modules/@heroicons/vue/24/outline/esm/*Icon.js',
        '!**/{XMark,ExclamationCircle,ChevronUp,ChevronDown,ChevronUpDown,ClipboardDocument,ClipboardDocumentCheck}Icon.js',
    ]),
    '24/solid': import.meta.glob<IconModule>('/node_modules/@heroicons/vue/24/solid/esm/*Icon.js'),
    '20/solid': import.meta.glob<IconModule>(['/node_modules/@heroicons/vue/20/solid/esm/*Icon.js', '!**/ChevronRightIcon.js']),
    '16/solid': import.meta.glob<IconModule>('/node_modules/@heroicons/vue/16/solid/esm/*Icon.js'),
};
