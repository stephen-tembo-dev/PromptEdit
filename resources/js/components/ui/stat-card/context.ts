import type { ComputedRef, InjectionKey } from 'vue';

export const statAlignKey: InjectionKey<ComputedRef<'start' | 'center' | 'end'>> = Symbol('ui-stat-align');
