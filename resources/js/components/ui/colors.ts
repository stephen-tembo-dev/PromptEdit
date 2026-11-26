/**
 * Colour token maps shared by the ui components. Every class is written out in full so Tailwind can see it.
 *
 * Buttons and badges recolour themselves by overriding --color-primary / --color-primary-content /
 * --color-primary-fg for their own subtree, so one variant definition works in every colour.
 */

export type UiColor =
    | 'slate'
    | 'neutral'
    | 'zinc'
    | 'stone'
    | 'red'
    | 'orange'
    | 'amber'
    | 'yellow'
    | 'lime'
    | 'green'
    | 'emerald'
    | 'teal'
    | 'cyan'
    | 'sky'
    | 'blue'
    | 'indigo'
    | 'violet'
    | 'purple'
    | 'fuchsia'
    | 'pink'
    | 'rose';

export const buttonColors: Record<UiColor | 'primary', string> = {
    slate: '[--color-primary:var(--color-slate-800)] [--color-primary-content:var(--color-slate-800)] [--color-primary-fg:var(--color-neutral-50)] dark:[--color-primary:var(--color-white)] dark:[--color-primary-content:var(--color-white)] dark:[--color-primary-fg:var(--color-slate-800)]',
    neutral: '[--color-primary:var(--color-neutral-800)] [--color-primary-content:var(--color-neutral-800)] [--color-primary-fg:var(--color-neutral-50)] dark:[--color-primary:var(--color-white)] dark:[--color-primary-content:var(--color-white)] dark:[--color-primary-fg:var(--color-neutral-800)]',
    zinc: '[--color-primary:var(--color-zinc-800)] [--color-primary-content:var(--color-zinc-800)] [--color-primary-fg:var(--color-neutral-50)] dark:[--color-primary:var(--color-white)] dark:[--color-primary-content:var(--color-white)] dark:[--color-primary-fg:var(--color-zinc-800)]',
    stone: '[--color-primary:var(--color-stone-800)] [--color-primary-content:var(--color-stone-800)] [--color-primary-fg:var(--color-neutral-50)] dark:[--color-primary:var(--color-white)] dark:[--color-primary-content:var(--color-white)] dark:[--color-primary-fg:var(--color-stone-800)]',
    red: '[--color-primary:var(--color-red-500)] [--color-primary-content:var(--color-red-700)] [--color-primary-fg:var(--color-neutral-50)] dark:[--color-primary:var(--color-red-500)] dark:[--color-primary-content:var(--color-red-400)] dark:[--color-primary-fg:var(--color-neutral-50)]',
    orange: '[--color-primary:var(--color-orange-500)] [--color-primary-content:var(--color-orange-800)] [--color-primary-fg:var(--color-neutral-50)] dark:[--color-primary:var(--color-orange-400)] dark:[--color-primary-content:var(--color-orange-400)] dark:[--color-primary-fg:var(--color-orange-950)]',
    amber: '[--color-primary:var(--color-amber-400)] [--color-primary-content:var(--color-amber-800)] [--color-primary-fg:var(--color-amber-950)] dark:[--color-primary:var(--color-amber-400)] dark:[--color-primary-content:var(--color-amber-400)] dark:[--color-primary-fg:var(--color-amber-950)]',
    yellow: '[--color-primary:var(--color-yellow-400)] [--color-primary-content:var(--color-yellow-800)] [--color-primary-fg:var(--color-yellow-950)] dark:[--color-primary:var(--color-yellow-400)] dark:[--color-primary-content:var(--color-yellow-400)] dark:[--color-primary-fg:var(--color-yellow-950)]',
    lime: '[--color-primary:var(--color-lime-400)] [--color-primary-content:var(--color-lime-800)] [--color-primary-fg:var(--color-lime-900)] dark:[--color-primary:var(--color-lime-400)] dark:[--color-primary-content:var(--color-lime-400)] dark:[--color-primary-fg:var(--color-lime-950)]',
    green: '[--color-primary:var(--color-green-600)] [--color-primary-content:var(--color-green-700)] [--color-primary-fg:var(--color-neutral-50)] dark:[--color-primary:var(--color-green-600)] dark:[--color-primary-content:var(--color-green-400)] dark:[--color-primary-fg:var(--color-neutral-50)]',
    emerald: '[--color-primary:var(--color-emerald-600)] [--color-primary-content:var(--color-emerald-700)] [--color-primary-fg:var(--color-neutral-50)] dark:[--color-primary:var(--color-emerald-600)] dark:[--color-primary-content:var(--color-emerald-400)] dark:[--color-primary-fg:var(--color-neutral-50)]',
    teal: '[--color-primary:var(--color-teal-600)] [--color-primary-content:var(--color-teal-700)] [--color-primary-fg:var(--color-neutral-50)] dark:[--color-primary:var(--color-teal-600)] dark:[--color-primary-content:var(--color-teal-400)] dark:[--color-primary-fg:var(--color-neutral-50)]',
    cyan: '[--color-primary:var(--color-cyan-600)] [--color-primary-content:var(--color-cyan-700)] [--color-primary-fg:var(--color-neutral-50)] dark:[--color-primary:var(--color-cyan-600)] dark:[--color-primary-content:var(--color-cyan-400)] dark:[--color-primary-fg:var(--color-neutral-50)]',
    sky: '[--color-primary:var(--color-sky-600)] [--color-primary-content:var(--color-sky-700)] [--color-primary-fg:var(--color-neutral-50)] dark:[--color-primary:var(--color-sky-600)] dark:[--color-primary-content:var(--color-sky-400)] dark:[--color-primary-fg:var(--color-neutral-50)]',
    blue: '[--color-primary:var(--color-blue-500)] [--color-primary-content:var(--color-blue-700)] [--color-primary-fg:var(--color-neutral-50)] dark:[--color-primary:var(--color-blue-500)] dark:[--color-primary-content:var(--color-blue-400)] dark:[--color-primary-fg:var(--color-neutral-50)]',
    indigo: '[--color-primary:var(--color-indigo-500)] [--color-primary-content:var(--color-indigo-700)] [--color-primary-fg:var(--color-neutral-50)] dark:[--color-primary:var(--color-indigo-500)] dark:[--color-primary-content:var(--color-indigo-300)] dark:[--color-primary-fg:var(--color-neutral-50)]',
    violet: '[--color-primary:var(--color-violet-500)] [--color-primary-content:var(--color-violet-700)] [--color-primary-fg:var(--color-neutral-50)] dark:[--color-primary:var(--color-violet-500)] dark:[--color-primary-content:var(--color-violet-400)] dark:[--color-primary-fg:var(--color-neutral-50)]',
    purple: '[--color-primary:var(--color-purple-500)] [--color-primary-content:var(--color-purple-700)] [--color-primary-fg:var(--color-neutral-50)] dark:[--color-primary:var(--color-purple-500)] dark:[--color-primary-content:var(--color-purple-300)] dark:[--color-primary-fg:var(--color-neutral-50)]',
    fuchsia: '[--color-primary:var(--color-fuchsia-600)] [--color-primary-content:var(--color-fuchsia-700)] [--color-primary-fg:var(--color-neutral-50)] dark:[--color-primary:var(--color-fuchsia-600)] dark:[--color-primary-content:var(--color-fuchsia-400)] dark:[--color-primary-fg:var(--color-neutral-50)]',
    pink: '[--color-primary:var(--color-pink-600)] [--color-primary-content:var(--color-pink-700)] [--color-primary-fg:var(--color-neutral-50)] dark:[--color-primary:var(--color-pink-600)] dark:[--color-primary-content:var(--color-pink-400)] dark:[--color-primary-fg:var(--color-neutral-50)]',
    rose: '[--color-primary:var(--color-rose-500)] [--color-primary-content:var(--color-rose-700)] [--color-primary-fg:var(--color-neutral-50)] dark:[--color-primary:var(--color-rose-500)] dark:[--color-primary-content:var(--color-rose-400)] dark:[--color-primary-fg:var(--color-neutral-50)]',
    primary: '[--color-primary:var(--color-primary-700)] [--color-primary-content:var(--color-primary-700)] [--color-primary-fg:var(--color-white)] dark:[--color-primary:var(--color-primary-400)] dark:[--color-primary-content:var(--color-primary-400)] dark:[--color-primary-fg:var(--color-primary-950)]',
};

/** Same token vocabulary as the button; --badge-ink is the darker shade soft and outline badges write in (4.5:1). */
export const badgeColors: Record<UiColor, string> = {
    slate: '[--color-primary:var(--color-slate-800)] [--color-primary-fg:var(--color-neutral-50)] dark:[--color-primary:var(--color-slate-300)] dark:[--color-primary-fg:var(--color-slate-900)] [--badge-ink:var(--color-slate-700)] dark:[--badge-ink:var(--color-slate-300)]',
    neutral: '[--color-primary:var(--color-neutral-800)] [--color-primary-fg:var(--color-neutral-50)] dark:[--color-primary:var(--color-neutral-300)] dark:[--color-primary-fg:var(--color-neutral-900)] [--badge-ink:var(--color-neutral-700)] dark:[--badge-ink:var(--color-neutral-300)]',
    zinc: '[--color-primary:var(--color-zinc-800)] [--color-primary-fg:var(--color-neutral-50)] dark:[--color-primary:var(--color-zinc-300)] dark:[--color-primary-fg:var(--color-zinc-900)] [--badge-ink:var(--color-zinc-700)] dark:[--badge-ink:var(--color-zinc-300)]',
    stone: '[--color-primary:var(--color-stone-800)] [--color-primary-fg:var(--color-neutral-50)] dark:[--color-primary:var(--color-stone-300)] dark:[--color-primary-fg:var(--color-stone-900)] [--badge-ink:var(--color-stone-700)] dark:[--badge-ink:var(--color-stone-300)]',
    red: '[--color-primary:var(--color-red-600)] [--color-primary-fg:var(--color-neutral-50)] dark:[--color-primary:var(--color-red-400)] dark:[--color-primary-fg:var(--color-red-950)] [--badge-ink:var(--color-red-700)] dark:[--badge-ink:var(--color-red-300)]',
    orange: '[--color-primary:var(--color-orange-600)] [--color-primary-fg:var(--color-neutral-50)] dark:[--color-primary:var(--color-orange-400)] dark:[--color-primary-fg:var(--color-orange-950)] [--badge-ink:var(--color-orange-800)] dark:[--badge-ink:var(--color-orange-300)]',
    amber: '[--color-primary:var(--color-amber-500)] [--color-primary-fg:var(--color-amber-950)] dark:[--color-primary:var(--color-amber-400)] dark:[--color-primary-fg:var(--color-amber-950)] [--badge-ink:var(--color-amber-800)] dark:[--badge-ink:var(--color-amber-300)]',
    yellow: '[--color-primary:var(--color-yellow-500)] [--color-primary-fg:var(--color-yellow-950)] dark:[--color-primary:var(--color-yellow-400)] dark:[--color-primary-fg:var(--color-yellow-950)] [--badge-ink:var(--color-yellow-800)] dark:[--badge-ink:var(--color-yellow-300)]',
    lime: '[--color-primary:var(--color-lime-500)] [--color-primary-fg:var(--color-lime-950)] dark:[--color-primary:var(--color-lime-400)] dark:[--color-primary-fg:var(--color-lime-950)] [--badge-ink:var(--color-lime-800)] dark:[--badge-ink:var(--color-lime-300)]',
    green: '[--color-primary:var(--color-green-600)] [--color-primary-fg:var(--color-neutral-50)] dark:[--color-primary:var(--color-green-400)] dark:[--color-primary-fg:var(--color-green-950)] [--badge-ink:var(--color-green-700)] dark:[--badge-ink:var(--color-green-300)]',
    emerald: '[--color-primary:var(--color-emerald-600)] [--color-primary-fg:var(--color-neutral-50)] dark:[--color-primary:var(--color-emerald-400)] dark:[--color-primary-fg:var(--color-emerald-950)] [--badge-ink:var(--color-emerald-700)] dark:[--badge-ink:var(--color-emerald-300)]',
    teal: '[--color-primary:var(--color-teal-600)] [--color-primary-fg:var(--color-neutral-50)] dark:[--color-primary:var(--color-teal-400)] dark:[--color-primary-fg:var(--color-teal-950)] [--badge-ink:var(--color-teal-700)] dark:[--badge-ink:var(--color-teal-300)]',
    cyan: '[--color-primary:var(--color-cyan-600)] [--color-primary-fg:var(--color-neutral-50)] dark:[--color-primary:var(--color-cyan-400)] dark:[--color-primary-fg:var(--color-cyan-950)] [--badge-ink:var(--color-cyan-700)] dark:[--badge-ink:var(--color-cyan-300)]',
    sky: '[--color-primary:var(--color-sky-600)] [--color-primary-fg:var(--color-neutral-50)] dark:[--color-primary:var(--color-sky-400)] dark:[--color-primary-fg:var(--color-sky-950)] [--badge-ink:var(--color-sky-700)] dark:[--badge-ink:var(--color-sky-300)]',
    blue: '[--color-primary:var(--color-blue-600)] [--color-primary-fg:var(--color-neutral-50)] dark:[--color-primary:var(--color-blue-400)] dark:[--color-primary-fg:var(--color-blue-950)] [--badge-ink:var(--color-blue-700)] dark:[--badge-ink:var(--color-blue-300)]',
    indigo: '[--color-primary:var(--color-indigo-600)] [--color-primary-fg:var(--color-neutral-50)] dark:[--color-primary:var(--color-indigo-400)] dark:[--color-primary-fg:var(--color-indigo-950)] [--badge-ink:var(--color-indigo-700)] dark:[--badge-ink:var(--color-indigo-300)]',
    violet: '[--color-primary:var(--color-violet-600)] [--color-primary-fg:var(--color-neutral-50)] dark:[--color-primary:var(--color-violet-400)] dark:[--color-primary-fg:var(--color-violet-950)] [--badge-ink:var(--color-violet-700)] dark:[--badge-ink:var(--color-violet-300)]',
    purple: '[--color-primary:var(--color-purple-600)] [--color-primary-fg:var(--color-neutral-50)] dark:[--color-primary:var(--color-purple-400)] dark:[--color-primary-fg:var(--color-purple-950)] [--badge-ink:var(--color-purple-700)] dark:[--badge-ink:var(--color-purple-300)]',
    fuchsia: '[--color-primary:var(--color-fuchsia-600)] [--color-primary-fg:var(--color-neutral-50)] dark:[--color-primary:var(--color-fuchsia-400)] dark:[--color-primary-fg:var(--color-fuchsia-950)] [--badge-ink:var(--color-fuchsia-700)] dark:[--badge-ink:var(--color-fuchsia-300)]',
    pink: '[--color-primary:var(--color-pink-600)] [--color-primary-fg:var(--color-neutral-50)] dark:[--color-primary:var(--color-pink-400)] dark:[--color-primary-fg:var(--color-pink-950)] [--badge-ink:var(--color-pink-700)] dark:[--badge-ink:var(--color-pink-300)]',
    rose: '[--color-primary:var(--color-rose-600)] [--color-primary-fg:var(--color-neutral-50)] dark:[--color-primary:var(--color-rose-400)] dark:[--color-primary-fg:var(--color-rose-950)] [--badge-ink:var(--color-rose-700)] dark:[--badge-ink:var(--color-rose-300)]',
};

/** Alert surfaces: background, border, text, heading and icon shades per colour. */
export const alertColors: Record<UiColor | 'gray', string> = {
    zinc: '[--bg-color:var(--color-zinc-50)] [--border-color:var(--color-zinc-200)] [--text-color:var(--color-zinc-700)] [--heading-color:var(--color-zinc-900)] [--icon-color:var(--color-zinc-600)] dark:[--bg-color:var(--color-zinc-500)] dark:[--border-color:var(--color-zinc-500)] dark:[--text-color:var(--color-zinc-300)] dark:[--heading-color:var(--color-zinc-100)] dark:[--icon-color:var(--color-zinc-400)]',
    red: '[--bg-color:var(--color-red-50)] [--border-color:var(--color-red-200)] [--text-color:var(--color-red-700)] [--heading-color:var(--color-red-900)] [--icon-color:var(--color-red-600)] dark:[--bg-color:var(--color-red-500)] dark:[--border-color:var(--color-red-500)] dark:[--text-color:var(--color-red-300)] dark:[--heading-color:var(--color-red-100)] dark:[--icon-color:var(--color-red-400)]',
    orange: '[--bg-color:var(--color-orange-50)] [--border-color:var(--color-orange-200)] [--text-color:var(--color-orange-700)] [--heading-color:var(--color-orange-900)] [--icon-color:var(--color-orange-600)] dark:[--bg-color:var(--color-orange-500)] dark:[--border-color:var(--color-orange-500)] dark:[--text-color:var(--color-orange-300)] dark:[--heading-color:var(--color-orange-100)] dark:[--icon-color:var(--color-orange-400)]',
    amber: '[--bg-color:var(--color-amber-50)] [--border-color:var(--color-amber-200)] [--text-color:var(--color-amber-800)] [--heading-color:var(--color-amber-900)] [--icon-color:var(--color-amber-600)] dark:[--bg-color:var(--color-amber-500)] dark:[--border-color:var(--color-amber-500)] dark:[--text-color:var(--color-amber-200)] dark:[--heading-color:var(--color-amber-100)] dark:[--icon-color:var(--color-amber-400)]',
    yellow: '[--bg-color:var(--color-yellow-50)] [--border-color:var(--color-yellow-300)] [--text-color:var(--color-yellow-800)] [--heading-color:var(--color-yellow-900)] [--icon-color:var(--color-yellow-600)] dark:[--bg-color:var(--color-yellow-500)] dark:[--border-color:var(--color-yellow-500)] dark:[--text-color:var(--color-yellow-200)] dark:[--heading-color:var(--color-yellow-100)] dark:[--icon-color:var(--color-yellow-400)]',
    lime: '[--bg-color:var(--color-lime-50)] [--border-color:var(--color-lime-200)] [--text-color:var(--color-lime-700)] [--heading-color:var(--color-lime-900)] [--icon-color:var(--color-lime-600)] dark:[--bg-color:var(--color-lime-500)] dark:[--border-color:var(--color-lime-500)] dark:[--text-color:var(--color-lime-300)] dark:[--heading-color:var(--color-lime-100)] dark:[--icon-color:var(--color-lime-400)]',
    green: '[--bg-color:var(--color-green-50)] [--border-color:var(--color-green-200)] [--text-color:var(--color-green-700)] [--heading-color:var(--color-green-900)] [--icon-color:var(--color-green-600)] dark:[--bg-color:var(--color-green-500)] dark:[--border-color:var(--color-green-500)] dark:[--text-color:var(--color-green-300)] dark:[--heading-color:var(--color-green-100)] dark:[--icon-color:var(--color-green-400)]',
    emerald: '[--bg-color:var(--color-emerald-50)] [--border-color:var(--color-emerald-200)] [--text-color:var(--color-emerald-700)] [--heading-color:var(--color-emerald-900)] [--icon-color:var(--color-emerald-600)] dark:[--bg-color:var(--color-emerald-500)] dark:[--border-color:var(--color-emerald-500)] dark:[--text-color:var(--color-emerald-300)] dark:[--heading-color:var(--color-emerald-100)] dark:[--icon-color:var(--color-emerald-400)]',
    teal: '[--bg-color:var(--color-teal-50)] [--border-color:var(--color-teal-200)] [--text-color:var(--color-teal-700)] [--heading-color:var(--color-teal-900)] [--icon-color:var(--color-teal-600)] dark:[--bg-color:var(--color-teal-500)] dark:[--border-color:var(--color-teal-500)] dark:[--text-color:var(--color-teal-300)] dark:[--heading-color:var(--color-teal-100)] dark:[--icon-color:var(--color-teal-400)]',
    cyan: '[--bg-color:var(--color-cyan-50)] [--border-color:var(--color-cyan-200)] [--text-color:var(--color-cyan-700)] [--heading-color:var(--color-cyan-900)] [--icon-color:var(--color-cyan-600)] dark:[--bg-color:var(--color-cyan-500)] dark:[--border-color:var(--color-cyan-500)] dark:[--text-color:var(--color-cyan-300)] dark:[--heading-color:var(--color-cyan-100)] dark:[--icon-color:var(--color-cyan-400)]',
    sky: '[--bg-color:var(--color-sky-50)] [--border-color:var(--color-sky-200)] [--text-color:var(--color-sky-700)] [--heading-color:var(--color-sky-900)] [--icon-color:var(--color-sky-600)] dark:[--bg-color:var(--color-sky-500)] dark:[--border-color:var(--color-sky-500)] dark:[--text-color:var(--color-sky-300)] dark:[--heading-color:var(--color-sky-100)] dark:[--icon-color:var(--color-sky-400)]',
    blue: '[--bg-color:var(--color-blue-50)] [--border-color:var(--color-blue-200)] [--text-color:var(--color-blue-700)] [--heading-color:var(--color-blue-900)] [--icon-color:var(--color-blue-600)] dark:[--bg-color:var(--color-blue-500)] dark:[--border-color:var(--color-blue-500)] dark:[--text-color:var(--color-blue-300)] dark:[--heading-color:var(--color-blue-100)] dark:[--icon-color:var(--color-blue-400)]',
    indigo: '[--bg-color:var(--color-indigo-50)] [--border-color:var(--color-indigo-200)] [--text-color:var(--color-indigo-700)] [--heading-color:var(--color-indigo-900)] [--icon-color:var(--color-indigo-600)] dark:[--bg-color:var(--color-indigo-500)] dark:[--border-color:var(--color-indigo-500)] dark:[--text-color:var(--color-indigo-300)] dark:[--heading-color:var(--color-indigo-100)] dark:[--icon-color:var(--color-indigo-400)]',
    violet: '[--bg-color:var(--color-violet-50)] [--border-color:var(--color-violet-200)] [--text-color:var(--color-violet-700)] [--heading-color:var(--color-violet-900)] [--icon-color:var(--color-violet-600)] dark:[--bg-color:var(--color-violet-500)] dark:[--border-color:var(--color-violet-500)] dark:[--text-color:var(--color-violet-300)] dark:[--heading-color:var(--color-violet-100)] dark:[--icon-color:var(--color-violet-400)]',
    purple: '[--bg-color:var(--color-purple-50)] [--border-color:var(--color-purple-200)] [--text-color:var(--color-purple-700)] [--heading-color:var(--color-purple-900)] [--icon-color:var(--color-purple-600)] dark:[--bg-color:var(--color-purple-500)] dark:[--border-color:var(--color-purple-500)] dark:[--text-color:var(--color-purple-300)] dark:[--heading-color:var(--color-purple-100)] dark:[--icon-color:var(--color-purple-400)]',
    fuchsia: '[--bg-color:var(--color-fuchsia-50)] [--border-color:var(--color-fuchsia-200)] [--text-color:var(--color-fuchsia-700)] [--heading-color:var(--color-fuchsia-900)] [--icon-color:var(--color-fuchsia-600)] dark:[--bg-color:var(--color-fuchsia-500)] dark:[--border-color:var(--color-fuchsia-500)] dark:[--text-color:var(--color-fuchsia-300)] dark:[--heading-color:var(--color-fuchsia-100)] dark:[--icon-color:var(--color-fuchsia-400)]',
    pink: '[--bg-color:var(--color-pink-50)] [--border-color:var(--color-pink-200)] [--text-color:var(--color-pink-700)] [--heading-color:var(--color-pink-900)] [--icon-color:var(--color-pink-600)] dark:[--bg-color:var(--color-pink-500)] dark:[--border-color:var(--color-pink-500)] dark:[--text-color:var(--color-pink-300)] dark:[--heading-color:var(--color-pink-100)] dark:[--icon-color:var(--color-pink-400)]',
    rose: '[--bg-color:var(--color-rose-50)] [--border-color:var(--color-rose-200)] [--text-color:var(--color-rose-700)] [--heading-color:var(--color-rose-900)] [--icon-color:var(--color-rose-600)] dark:[--bg-color:var(--color-rose-500)] dark:[--border-color:var(--color-rose-500)] dark:[--text-color:var(--color-rose-300)] dark:[--heading-color:var(--color-rose-100)] dark:[--icon-color:var(--color-rose-400)]',
    slate: '[--bg-color:var(--color-slate-50)] [--border-color:var(--color-slate-200)] [--text-color:var(--color-slate-700)] [--heading-color:var(--color-slate-900)] [--icon-color:var(--color-slate-600)] dark:[--bg-color:var(--color-slate-500)] dark:[--border-color:var(--color-slate-500)] dark:[--text-color:var(--color-slate-300)] dark:[--heading-color:var(--color-slate-100)] dark:[--icon-color:var(--color-slate-400)]',
    gray: '[--bg-color:var(--color-gray-50)] [--border-color:var(--color-gray-200)] [--text-color:var(--color-gray-700)] [--heading-color:var(--color-gray-900)] [--icon-color:var(--color-gray-600)] dark:[--bg-color:var(--color-gray-500)] dark:[--border-color:var(--color-gray-500)] dark:[--text-color:var(--color-gray-300)] dark:[--heading-color:var(--color-gray-100)] dark:[--icon-color:var(--color-gray-400)]',
    neutral: '[--bg-color:var(--color-neutral-50)] [--border-color:var(--color-neutral-200)] [--text-color:var(--color-neutral-700)] [--heading-color:var(--color-neutral-900)] [--icon-color:var(--color-neutral-600)] dark:[--bg-color:var(--color-neutral-500)] dark:[--border-color:var(--color-neutral-500)] dark:[--text-color:var(--color-neutral-300)] dark:[--heading-color:var(--color-neutral-100)] dark:[--icon-color:var(--color-neutral-400)]',
    stone: '[--bg-color:var(--color-stone-50)] [--border-color:var(--color-stone-200)] [--text-color:var(--color-stone-700)] [--heading-color:var(--color-stone-900)] [--icon-color:var(--color-stone-600)] dark:[--bg-color:var(--color-stone-500)] dark:[--border-color:var(--color-stone-500)] dark:[--text-color:var(--color-stone-300)] dark:[--heading-color:var(--color-stone-100)] dark:[--icon-color:var(--color-stone-400)]',
};

export const avatarColors: Record<string, string> = {
    red: 'bg-red-400 text-red-800',
    orange: 'bg-orange-200 text-orange-800',
    amber: 'bg-amber-200 text-amber-800',
    yellow: 'bg-yellow-200 text-yellow-800',
    lime: 'bg-lime-200 text-lime-800',
    green: 'bg-green-200 text-green-800',
    emerald: 'bg-emerald-200 text-emerald-800',
    teal: 'bg-teal-200 text-teal-800',
    cyan: 'bg-cyan-200 text-cyan-800',
    sky: 'bg-sky-200 text-sky-800',
    blue: 'bg-blue-200 text-blue-800',
    indigo: 'bg-indigo-200 text-indigo-800',
    violet: 'bg-violet-200 text-violet-800',
    purple: 'bg-purple-200 text-purple-800',
    fuchsia: 'bg-fuchsia-200 text-fuchsia-800',
    pink: 'bg-pink-200 text-pink-800',
    rose: 'bg-rose-200 text-rose-800',
};

export const avatarBadgeColors: Record<string, string> = {
    red: 'bg-red-500 dark:bg-red-400',
    orange: 'bg-orange-500 dark:bg-orange-400',
    amber: 'bg-amber-500 dark:bg-amber-400',
    yellow: 'bg-yellow-500 dark:bg-yellow-400',
    lime: 'bg-lime-500 dark:bg-lime-400',
    green: 'bg-green-500 dark:bg-green-400',
    emerald: 'bg-emerald-500 dark:bg-emerald-400',
    teal: 'bg-teal-500 dark:bg-teal-400',
    cyan: 'bg-cyan-500 dark:bg-cyan-400',
    sky: 'bg-sky-500 dark:bg-sky-400',
    blue: 'bg-blue-500 dark:bg-blue-400',
    indigo: 'bg-indigo-500 dark:bg-indigo-400',
    violet: 'bg-violet-500 dark:bg-violet-400',
    purple: 'bg-purple-500 dark:bg-purple-400',
    fuchsia: 'bg-fuchsia-500 dark:bg-fuchsia-400',
    pink: 'bg-pink-500 dark:bg-pink-400',
    rose: 'bg-rose-500 dark:bg-rose-400',
    zinc: 'bg-zinc-400 dark:bg-zinc-300',
    gray: 'bg-neutral-400 dark:bg-neutral-300',
};

/**
 * Stat card icon chips. The Blade version built `bg-{$color}-100` at runtime, which Tailwind cannot see,
 * so the classes are spelled out here for each colour.
 */
export const statColors: Record<string, { chip: string; icon: string }> = {
    primary: { chip: 'bg-primary-100 text-primary-600 dark:bg-primary-500/20 dark:text-primary-500', icon: 'text-primary-600 dark:text-primary-500' },
    neutral: { chip: 'bg-neutral-100 text-neutral-600 dark:bg-neutral-500/20 dark:text-neutral-400', icon: 'text-neutral-600 dark:text-neutral-400' },
    red: { chip: 'bg-red-100 text-red-600 dark:bg-red-500/20 dark:text-red-500', icon: 'text-red-600 dark:text-red-500' },
    orange: { chip: 'bg-orange-100 text-orange-600 dark:bg-orange-500/20 dark:text-orange-500', icon: 'text-orange-600 dark:text-orange-500' },
    amber: { chip: 'bg-amber-100 text-amber-600 dark:bg-amber-500/20 dark:text-amber-500', icon: 'text-amber-600 dark:text-amber-500' },
    yellow: { chip: 'bg-yellow-100 text-yellow-600 dark:bg-yellow-500/20 dark:text-yellow-500', icon: 'text-yellow-600 dark:text-yellow-500' },
    lime: { chip: 'bg-lime-100 text-lime-600 dark:bg-lime-500/20 dark:text-lime-500', icon: 'text-lime-600 dark:text-lime-500' },
    green: { chip: 'bg-green-100 text-green-600 dark:bg-green-500/20 dark:text-green-500', icon: 'text-green-600 dark:text-green-500' },
    emerald: { chip: 'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-500', icon: 'text-emerald-600 dark:text-emerald-500' },
    teal: { chip: 'bg-teal-100 text-teal-600 dark:bg-teal-500/20 dark:text-teal-500', icon: 'text-teal-600 dark:text-teal-500' },
    cyan: { chip: 'bg-cyan-100 text-cyan-600 dark:bg-cyan-500/20 dark:text-cyan-500', icon: 'text-cyan-600 dark:text-cyan-500' },
    sky: { chip: 'bg-sky-100 text-sky-600 dark:bg-sky-500/20 dark:text-sky-500', icon: 'text-sky-600 dark:text-sky-500' },
    blue: { chip: 'bg-blue-100 text-blue-600 dark:bg-blue-500/20 dark:text-blue-500', icon: 'text-blue-600 dark:text-blue-500' },
    indigo: { chip: 'bg-indigo-100 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-500', icon: 'text-indigo-600 dark:text-indigo-500' },
    violet: { chip: 'bg-violet-100 text-violet-600 dark:bg-violet-500/20 dark:text-violet-500', icon: 'text-violet-600 dark:text-violet-500' },
    purple: { chip: 'bg-purple-100 text-purple-600 dark:bg-purple-500/20 dark:text-purple-500', icon: 'text-purple-600 dark:text-purple-500' },
    fuchsia: { chip: 'bg-fuchsia-100 text-fuchsia-600 dark:bg-fuchsia-500/20 dark:text-fuchsia-500', icon: 'text-fuchsia-600 dark:text-fuchsia-500' },
    pink: { chip: 'bg-pink-100 text-pink-600 dark:bg-pink-500/20 dark:text-pink-500', icon: 'text-pink-600 dark:text-pink-500' },
    rose: { chip: 'bg-rose-100 text-rose-600 dark:bg-rose-500/20 dark:text-rose-500', icon: 'text-rose-600 dark:text-rose-500' },
};

export const maxWidths: Record<string, string> = {
    xs: 'max-w-xs',
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-lg',
    xl: 'max-w-xl',
    '2xl': 'max-w-2xl',
    '3xl': 'max-w-3xl',
    '4xl': 'max-w-4xl',
    '5xl': 'max-w-5xl',
    '6xl': 'max-w-6xl',
    '7xl': 'max-w-7xl',
    half: 'w-1/2',
    full: 'max-w-full',
};
