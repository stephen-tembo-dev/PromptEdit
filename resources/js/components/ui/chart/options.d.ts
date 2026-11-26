/**
 * A chart description: `kind` picks the builder (bar, line, histogram, box, estimates, xy, heatmap…),
 * the rest is that kind's data. See options.js for each kind's fields.
 */
export interface ChartSpec {
    kind: string;
    [key: string]: unknown;
}

export type ChartMode = 'light' | 'dark';

export declare const THEMES: Record<ChartMode, Record<string, unknown>>;
export declare const fmt: (value: number | null | undefined) => string;
export declare const esc: (text: unknown) => string;
export declare function buildOption(spec: ChartSpec, mode: ChartMode): Record<string, unknown> | null;
export declare function tableFor(spec: ChartSpec): { columns: string[]; rows: (string | number | null)[][]; note?: string };
