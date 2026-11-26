/**
 * Turns the stats engine's chart description (app/figures.py) into an ECharts option.
 *
 * One builder per kind. Colours come from the validated categorical palette in fixed order,
 * with separately chosen steps for dark mode; text is always ink, never a series colour.
 * Variable names come from students' files, so every label that reaches tooltip HTML is escaped.
 */

export const THEMES = {
    light: {
        series: ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#6250d6', '#e34948'],
        diverging: ['#184f95', '#6da7ec', '#f0efec', '#ee9590', '#b3302f'],
        boxFill: '#cde2fb',
        surface: '#ffffff',
        ink: '#262626',
        secondary: '#525252',
        muted: '#737373',
        axis: '#a3a3a3',
        grid: 'rgba(0, 0, 0, 0.07)',
        tooltip: '#ffffff',
        tooltipBorder: 'rgba(0, 0, 0, 0.1)',
        highlight: 'rgba(42, 120, 214, 0.08)',
    },
    dark: {
        series: ['#3987e5', '#d95926', '#199e70', '#c98500', '#d55181', '#008300', '#9085e9', '#e66767'],
        diverging: ['#184f95', '#3987e5', '#383835', '#e66767', '#b3302f'],
        boxFill: 'rgba(57, 135, 229, 0.22)',
        surface: '#262626',
        ink: '#e5e5e5',
        secondary: '#a3a3a3',
        muted: '#8a8a8a',
        axis: '#525252',
        grid: 'rgba(255, 255, 255, 0.07)',
        tooltip: '#171717',
        tooltipBorder: 'rgba(255, 255, 255, 0.12)',
        highlight: 'rgba(57, 135, 229, 0.12)',
    },
};

const formatter = new Intl.NumberFormat(undefined, { maximumFractionDigits: 3 });

export const fmt = (value) => (value === null || value === undefined || Number.isNaN(value) ? '—' : formatter.format(value));

export const esc = (text) => String(text ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

/** One tooltip row: a short stroke of the series colour, the value strong, the label after it. */
function row(color, value, label) {
    const key = color ? `<span style="display:inline-block;width:10px;height:2px;border-radius:1px;background:${color};margin-right:8px;vertical-align:middle"></span>` : '';

    return `<div style="display:flex;align-items:center;gap:6px;line-height:1.6">${key}<strong style="font-weight:600">${value}</strong><span style="opacity:.7">${esc(label)}</span></div>`;
}

function heading(text) {
    return `<div style="font-weight:600;margin-bottom:2px">${esc(text)}</div>`;
}

function base(theme, spec, { legend = false } = {}) {
    return {
        color: theme.series,
        animationDuration: 350,
        animationEasing: 'cubicOut',
        textStyle: { fontFamily: 'Figtree, ui-sans-serif, system-ui, sans-serif', color: theme.ink },
        grid: { left: 64, right: 24, top: legend ? 44 : 20, bottom: 56, containLabel: false },
        tooltip: {
            backgroundColor: theme.tooltip,
            borderColor: theme.tooltipBorder,
            borderWidth: 1,
            padding: [8, 12],
            textStyle: { color: theme.ink, fontSize: 12 },
            extraCssText: 'box-shadow: 0 4px 16px -4px rgba(0,0,0,.18); border-radius: 8px;',
            confine: true,
        },
        legend: legend
            ? {
                top: 0,
                left: 0,
                icon: 'roundRect',
                itemWidth: 12,
                itemHeight: 12,
                itemGap: 16,
                textStyle: { color: theme.secondary, fontSize: 12 },
                inactiveColor: theme.axis,
            }
            : undefined,
    };
}

function axis(theme, name, extra = {}) {
    return {
        name,
        nameLocation: 'middle',
        nameGap: extra.type === 'category' ? 32 : 44,
        nameTextStyle: { color: theme.secondary, fontSize: 12 },
        axisLine: { show: true, lineStyle: { color: theme.axis } },
        axisTick: { show: false },
        axisLabel: { color: theme.muted, fontSize: 11, hideOverlap: true },
        splitLine: { show: extra.type !== 'category', lineStyle: { color: theme.grid } },
        ...extra,
    };
}

/** Vertical error bars drawn as a custom series: a 1px stem with short caps. */
function errorBars(theme, data, color, offset = 0) {
    return {
        type: 'custom',
        silent: true,
        z: 1,
        renderItem(params, api) {
            const x = api.value(0);
            const low = api.coord([x, api.value(1)]);
            const high = api.coord([x, api.value(2)]);
            const cx = low[0] + offset;
            const style = { stroke: color ?? theme.secondary, lineWidth: 1.5 };

            return {
                type: 'group',
                children: [
                    { type: 'line', shape: { x1: cx, y1: low[1], x2: cx, y2: high[1] }, style },
                    { type: 'line', shape: { x1: cx - 6, y1: low[1], x2: cx + 6, y2: low[1] }, style },
                    { type: 'line', shape: { x1: cx - 6, y1: high[1], x2: cx + 6, y2: high[1] }, style },
                ],
            };
        },
        encode: { x: 0, y: [1, 2] },
        data,
    };
}

const builders = {
    histogram(spec, theme) {
        const option = base(theme, spec, { legend: spec.curve?.length > 0 });
        const first = spec.bins[0]?.from ?? 0;
        const last = spec.bins.at(-1)?.to ?? 1;

        return {
            ...option,
            tooltip: {
                ...option.tooltip,
                trigger: 'item',
                formatter: (p) => p.seriesType === 'custom'
                    ? heading(`${fmt(p.value[0])} to ${fmt(p.value[1])}`) + row(theme.series[0], fmt(p.value[2]), p.value[2] === 1 ? 'case' : 'cases')
                    : heading(`${spec.x_label} ${fmt(p.value[0])}`) + row(theme.ink, fmt(p.value[1]), 'expected if normal'),
            },
            xAxis: axis(theme, spec.x_label, { type: 'value', min: first, max: last, scale: true }),
            yAxis: axis(theme, spec.y_label, { type: 'value', minInterval: 1 }),
            series: [
                {
                    name: 'Cases',
                    type: 'custom',
                    renderItem(params, api) {
                        const start = api.coord([api.value(0), api.value(2)]);
                        const end = api.coord([api.value(1), 0]);
                        const gap = 1;

                        return {
                            type: 'rect',
                            shape: { x: start[0] + gap, y: start[1], width: Math.max(1, end[0] - start[0] - gap * 2), height: end[1] - start[1], r: [3, 3, 0, 0] },
                            style: { fill: theme.series[0] },
                            emphasis: { style: { fill: theme.series[0], opacity: 0.8 } },
                        };
                    },
                    encode: { x: [0, 1], y: 2, tooltip: [0, 1, 2] },
                    itemStyle: { color: theme.series[0] },
                    data: spec.bins.map((b) => [b.from, b.to, b.count]),
                },
                ...(spec.curve?.length
                    ? [{ name: 'Normal curve', type: 'line', data: spec.curve, showSymbol: false, smooth: true, lineStyle: { width: 2, color: theme.ink }, itemStyle: { color: theme.ink } }]
                    : []),
                ...(spec.reference_x !== null && spec.reference_x !== undefined
                    ? [{ type: 'line', data: [], markLine: { silent: true, symbol: 'none', lineStyle: { color: theme.secondary, type: 'dashed' }, label: { formatter: `Test value ${fmt(spec.reference_x)}`, color: theme.secondary }, data: [{ xAxis: spec.reference_x }] } }]
                    : []),
            ],
        };
    },

    estimates(spec, theme) {
        const option = base(theme, spec);
        const labels = spec.items.map((i) => i.label);

        return {
            ...option,
            tooltip: {
                ...option.tooltip,
                trigger: 'axis',
                axisPointer: { type: 'shadow', shadowStyle: { color: theme.highlight } },
                formatter: (params) => {
                    const item = spec.items[params[0].dataIndex];

                    return heading(item.label) + row(theme.series[0], fmt(item.value), spec.y_label) + row(null, `[${fmt(item.low)}, ${fmt(item.high)}]`, '95% CI');
                },
            },
            xAxis: axis(theme, spec.x_label, { type: 'category', data: labels }),
            yAxis: axis(theme, spec.y_label, { type: 'value', scale: true }),
            series: [
                errorBars(theme, spec.items.map((i, index) => [index, i.low, i.high])),
                { type: 'scatter', data: spec.items.map((i) => i.value), symbolSize: 11, itemStyle: { color: theme.series[0], borderColor: theme.surface, borderWidth: 2 }, z: 2 },
            ],
        };
    },

    box(spec, theme) {
        const option = base(theme, spec);
        const outliers = spec.groups.flatMap((g, index) => g.outliers.map((v) => [index, v]));

        return {
            ...option,
            tooltip: {
                ...option.tooltip,
                trigger: 'item',
                formatter: (p) => {
                    const group = spec.groups[p.seriesType === 'boxplot' ? p.dataIndex : p.value[0]];
                    if (p.seriesType !== 'boxplot') {
                        return heading(group.label) + row(theme.series[0], fmt(p.value[1]), 'outlier');
                    }

                    return heading(`${group.label} (n = ${group.n})`)
                        + row(null, fmt(group.high), 'upper whisker')
                        + row(null, fmt(group.q3), 'third quartile')
                        + row(theme.series[0], fmt(group.median), 'median')
                        + row(null, fmt(group.q1), 'first quartile')
                        + row(null, fmt(group.low), 'lower whisker');
                },
            },
            xAxis: axis(theme, spec.x_label, { type: 'category', data: spec.groups.map((g) => g.label) }),
            yAxis: axis(theme, spec.y_label, { type: 'value', scale: true }),
            series: [
                {
                    type: 'boxplot',
                    data: spec.groups.map((g) => [g.low, g.q1, g.median, g.q3, g.high]),
                    boxWidth: ['20%', '45%'],
                    itemStyle: { color: theme.boxFill, borderColor: theme.series[0], borderWidth: 1.5 },
                    emphasis: { itemStyle: { borderWidth: 2.5 } },
                },
                { type: 'scatter', data: outliers, symbolSize: 8, itemStyle: { color: theme.surface, borderColor: theme.secondary, borderWidth: 1.5 } },
            ],
        };
    },

    bar(spec, theme) {
        const many = spec.series.length > 1;
        const option = base(theme, spec, { legend: many || Boolean(spec.overlay) });
        // Horizontal bars suit long category names (schools, kinds); stacked bars show parts of a whole.
        const horizontal = Boolean(spec.horizontal);
        const lastIndex = spec.series.length - 1;
        const radius = (index) => {
            if (spec.stacked && index !== lastIndex) {
                return 0;
            }

            return horizontal ? [0, 4, 4, 0] : [4, 4, 0, 0];
        };
        const categoryAxis = horizontal
            ? axis(theme, '', { type: 'category', data: spec.categories, inverse: true, axisLabel: { color: theme.secondary, fontSize: 12, width: 140, overflow: 'truncate' } })
            : axis(theme, spec.x_label, { type: 'category', data: spec.categories, axisLabel: { color: theme.muted, fontSize: 11, interval: 0, rotate: spec.categories.length > 6 ? 30 : 0, hideOverlap: true } });
        const valueAxis = axis(theme, horizontal ? '' : spec.y_label, { type: 'value', minInterval: spec.integer ? 1 : undefined });

        return {
            ...option,
            grid: horizontal ? { left: 8, right: 24, top: option.grid.top, bottom: 8, containLabel: true } : option.grid,
            tooltip: {
                ...option.tooltip,
                trigger: 'axis',
                axisPointer: { type: 'shadow', shadowStyle: { color: theme.highlight } },
                formatter: (params) => heading(spec.categories[params[0].dataIndex])
                    + params.map((p) => row(p.seriesName === spec.overlay?.name ? theme.ink : theme.series[p.seriesIndex], fmt(p.value), p.seriesName)).join(''),
            },
            xAxis: horizontal ? valueAxis : categoryAxis,
            yAxis: horizontal ? categoryAxis : valueAxis,
            series: [
                ...spec.series.map((s, index) => ({
                    name: s.name,
                    type: 'bar',
                    data: s.values,
                    stack: spec.stacked ? 'total' : undefined,
                    barMaxWidth: horizontal ? 22 : 56,
                    barGap: '8%',
                    itemStyle: { color: theme.series[index], borderRadius: radius(index), borderColor: spec.stacked ? theme.surface : undefined, borderWidth: spec.stacked ? 1 : 0 },
                    // Direct value labels, for single-series report charts where every number matters.
                    label: spec.labels && !many
                        ? { show: true, position: horizontal ? 'right' : 'top', color: theme.secondary, fontSize: 11, formatter: (p) => fmt(p.value) }
                        : undefined,
                    emphasis: { itemStyle: { opacity: 0.85 } },
                })),
                ...(spec.overlay
                    ? [{ name: spec.overlay.name, type: 'scatter', data: spec.overlay.values, symbol: 'rect', symbolSize: [36, 3], itemStyle: { color: theme.ink }, z: 3 }]
                    : []),
            ],
        };
    },

    line(spec, theme) {
        const many = spec.series.length > 1;
        const option = base(theme, spec, { legend: many });
        const offsets = spec.series.map((_, index) => (index - (spec.series.length - 1) / 2) * 6);
        // A scree plot marks the retained components as filled points and the rest as hollow ones.
        const point = (value, i, color) => {
            if (!spec.highlight_first) {
                return value;
            }

            return i >= spec.highlight_first
                ? { value, itemStyle: { color: theme.surface, borderColor: color, borderWidth: 2 } }
                : { value, symbolSize: 12 };
        };
        const reference = spec.reference_y !== undefined && spec.reference_y !== null
            ? { silent: true, symbol: 'none', lineStyle: { color: theme.secondary, type: 'dashed' }, label: { formatter: spec.reference_label ?? fmt(spec.reference_y), color: theme.secondary, position: 'insideEndTop' }, data: [{ yAxis: spec.reference_y }] }
            : undefined;

        return {
            ...option,
            tooltip: {
                ...option.tooltip,
                trigger: 'axis',
                axisPointer: { type: 'line', lineStyle: { color: theme.axis } },
                formatter: (params) => heading(`${spec.x_label} ${spec.categories[params[0].dataIndex]}`)
                    + params.filter((p) => p.seriesType === 'line').map((p) => {
                        const s = spec.series.find((x) => x.name === p.seriesName);
                        const ci = s?.low ? ` [${fmt(s.low[p.dataIndex])}, ${fmt(s.high[p.dataIndex])}]` : '';

                        return row(p.color, fmt(p.value) + ci, p.seriesName);
                    }).join(''),
            },
            xAxis: axis(theme, spec.x_label, { type: 'category', data: spec.categories, boundaryGap: true }),
            // A running total reads from zero, and stretches to show its target line.
            yAxis: axis(theme, spec.y_label, spec.from_zero
                ? { type: 'value', min: 0, minInterval: 1, max: (extent) => Math.max(extent.max, spec.reference_y ?? 0) }
                : { type: 'value', scale: true }),
            series: spec.series.flatMap((s, index) => [
                ...(s.low ? [errorBars(theme, s.values.map((_, i) => [i, s.low[i], s.high[i]]), theme.series[index], offsets[index])] : []),
                {
                    name: s.name,
                    type: 'line',
                    data: s.values.map((value, i) => point(value, i, theme.series[index])),
                    symbol: 'circle',
                    symbolSize: 9,
                    lineStyle: { width: 2, color: theme.series[index] },
                    itemStyle: { color: theme.series[index], borderColor: theme.surface, borderWidth: 2 },
                    markLine: index === 0 ? reference : undefined,
                },
            ]),
        };
    },

    xy(spec, theme) {
        const series = spec.series ?? [];
        const lines = spec.lines ?? [];
        const named = series.length > 1 || lines.filter((l) => l.series !== undefined && l.series !== null).length > 1;
        const option = base(theme, spec, { legend: named });
        const colorOf = (index) => (index === null || index === undefined ? theme.ink : theme.series[index]);
        const domain = spec.domain ? { min: spec.domain[0], max: spec.domain[1] } : { scale: true };

        return {
            ...option,
            grid: { ...option.grid, bottom: 64 },
            tooltip: {
                ...option.tooltip,
                trigger: 'item',
                formatter: (p) => heading(p.seriesName)
                    + row(null, fmt(p.value[0]), spec.x_label)
                    + row(p.color, fmt(p.value[1]), spec.y_label),
            },
            // Scroll or pinch to zoom into a dense cloud; drag to pan.
            dataZoom: [{ type: 'inside', xAxisIndex: 0, filterMode: 'none' }, { type: 'inside', yAxisIndex: 0, filterMode: 'none' }],
            xAxis: axis(theme, spec.x_label, { type: 'value', ...domain }),
            yAxis: axis(theme, spec.y_label, { type: 'value', ...domain }),
            series: [
                ...series.map((s, index) => ({
                    name: s.name,
                    type: 'scatter',
                    data: s.points,
                    symbolSize: 8,
                    large: s.points.length > 2000,
                    itemStyle: { color: theme.series[index], borderColor: theme.surface, borderWidth: 1, opacity: 0.9 },
                    emphasis: { scale: 1.6, itemStyle: { borderWidth: 2 } },
                    markLine: index === 0 && spec.reference_y !== undefined && spec.reference_y !== null
                        ? { silent: true, symbol: 'none', lineStyle: { color: theme.ink, width: 1.5 }, label: { show: false }, data: [{ yAxis: spec.reference_y }] }
                        : undefined,
                })),
                ...lines.map((l) => ({
                    name: l.series !== undefined && l.series !== null && series[l.series] ? series[l.series].name : l.name,
                    type: 'line',
                    data: l.points,
                    showSymbol: false,
                    lineStyle: { width: 2, color: l.dashed ? theme.muted : colorOf(l.series), type: l.dashed ? 'dashed' : 'solid' },
                    itemStyle: { color: l.dashed ? theme.muted : colorOf(l.series) },
                    tooltip: { show: !l.dashed },
                    z: 1,
                })),
            ],
        };
    },

    heatmap(spec, theme) {
        const option = base(theme, spec);
        const n = spec.labels.length;
        const cells = spec.values.flatMap((r, i) => r.map((v, j) => [j, n - 1 - i, v]));

        return {
            ...option,
            grid: { left: 120, right: 72, top: 12, bottom: 96 },
            tooltip: {
                ...option.tooltip,
                trigger: 'item',
                formatter: (p) => heading(`${spec.labels[n - 1 - p.value[1]]} × ${spec.labels[p.value[0]]}`) + row(null, fmt(p.value[2]), spec.symbol),
            },
            xAxis: axis(theme, '', { type: 'category', data: spec.labels, axisLine: { show: false }, axisLabel: { color: theme.muted, fontSize: 11, rotate: 40, interval: 0 } }),
            yAxis: axis(theme, '', { type: 'category', data: [...spec.labels].reverse(), axisLine: { show: false }, axisLabel: { color: theme.muted, fontSize: 11, interval: 0 } }),
            visualMap: {
                min: -1,
                max: 1,
                calculable: false,
                orient: 'vertical',
                right: 0,
                top: 'middle',
                itemHeight: 160,
                itemWidth: 12,
                text: ['+1', '−1'],
                textStyle: { color: theme.muted, fontSize: 11 },
                inRange: { color: theme.diverging },
            },
            series: [{
                type: 'heatmap',
                data: cells,
                label: {
                    show: n <= 12,
                    fontSize: 11,
                    formatter: (p) => (p.value[2] === null ? '' : p.value[2].toFixed(2)),
                    color: theme.ink,
                },
                itemStyle: { borderColor: theme.surface, borderWidth: 2, borderRadius: 3 },
                emphasis: { itemStyle: { borderColor: theme.ink, borderWidth: 1.5 } },
            }],
        };
    },
};

export function buildOption(spec, mode) {
    const theme = THEMES[mode] ?? THEMES.light;
    const builder = builders[spec?.kind];

    return builder ? builder(spec, theme) : null;
}

/**
 * The chart's numbers as rows for the table view, so every value is reachable without a pointer.
 *
 * @returns {{ columns: string[], rows: (string|number|null)[][], note?: string }}
 */
export function tableFor(spec) {
    const LIMIT = 300;

    switch (spec.kind) {
        case 'histogram':
            return { columns: [spec.x_label, 'Cases'], rows: spec.bins.map((b) => [`${fmt(b.from)} to ${fmt(b.to)}`, b.count]) };
        case 'estimates':
            return { columns: [spec.x_label, spec.y_label, '95% CI lower', '95% CI upper'], rows: spec.items.map((i) => [i.label, i.value, i.low, i.high]) };
        case 'box':
            return {
                columns: [spec.x_label || 'Group', 'n', 'Lower whisker', 'Q1', 'Median', 'Q3', 'Upper whisker', 'Outliers'],
                rows: spec.groups.map((g) => [g.label, g.n, g.low, g.q1, g.median, g.q3, g.high, g.outliers.length]),
            };
        case 'bar':
            return {
                columns: [spec.x_label, ...spec.series.map((s) => s.name), ...(spec.overlay ? [spec.overlay.name] : [])],
                rows: spec.categories.map((c, i) => [c, ...spec.series.map((s) => s.values[i]), ...(spec.overlay ? [spec.overlay.values[i]] : [])]),
            };
        case 'line':
            return {
                columns: [spec.x_label, ...spec.series.flatMap((s) => (s.low ? [s.name, '95% CI'] : [s.name]))],
                rows: spec.categories.map((c, i) => [c, ...spec.series.flatMap((s) => (s.low ? [s.values[i], `${fmt(s.low[i])} to ${fmt(s.high[i])}`] : [s.values[i]]))]),
            };
        case 'xy': {
            const rows = (spec.series ?? []).flatMap((s) => s.points.map(([x, y]) => [s.name, x, y]));
            const lines = (spec.lines ?? []).filter((l) => !l.dashed).flatMap((l) => l.points.map(([x, y]) => [l.name, x, y]));
            const all = rows.length ? rows : lines;

            return {
                columns: ['Series', spec.x_label, spec.y_label],
                rows: all.slice(0, LIMIT),
                note: all.length > LIMIT ? `The first ${LIMIT} of ${all.length} points. The full data are in the dataset.` : spec.note,
            };
        }
        case 'heatmap':
            return { columns: ['', ...spec.labels], rows: spec.values.map((r, i) => [spec.labels[i], ...r]) };
        default:
            return { columns: [], rows: [] };
    }
}
