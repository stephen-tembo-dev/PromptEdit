/**
 * The parts of ECharts the charts use, and nothing else, so the chunk stays small.
 * Imported on demand by <Chart>.
 */
import { BarChart, BoxplotChart, CustomChart, HeatmapChart, LineChart, ScatterChart } from 'echarts/charts';
import { DataZoomComponent, GridComponent, LegendComponent, MarkLineComponent, TooltipComponent, VisualMapComponent } from 'echarts/components';
import * as echarts from 'echarts/core';
import { SVGRenderer } from 'echarts/renderers';

echarts.use([
    BarChart,
    BoxplotChart,
    CustomChart,
    HeatmapChart,
    LineChart,
    ScatterChart,
    DataZoomComponent,
    GridComponent,
    LegendComponent,
    MarkLineComponent,
    TooltipComponent,
    VisualMapComponent,
    SVGRenderer,
]);

export default echarts;
