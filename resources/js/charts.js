import ApexCharts from "apexcharts";

/**
 * Grafik dari atribut data: <div data-chart='{"type":"area","categories":[...],"series":[{"name":"..","data":[..]}],"currency":false}'>
 * type: area | bar | donut
 */
export default function initCharts() {
    const dark = () => document.documentElement.classList.contains("dark");
    document.querySelectorAll("[data-chart]").forEach((el) => {
        let cfg;
        try { cfg = JSON.parse(el.dataset.chart); } catch (e) { return; }
        const fmt = (v) => (cfg.currency ? "Rp" + Number(v).toLocaleString("id-ID") : Number(v).toLocaleString("id-ID"));
        const base = {
            chart: { type: cfg.type, height: cfg.height || 280, toolbar: { show: false }, fontFamily: "Outfit, sans-serif", background: "transparent" },
            colors: cfg.colors || ["#465fff", "#12b76a", "#f79009", "#f04438"],
            theme: { mode: dark() ? "dark" : "light" },
            dataLabels: { enabled: false },
            grid: { borderColor: dark() ? "#1d2939" : "#eaecf0" },
            tooltip: { y: { formatter: fmt } },
            legend: { position: "top", horizontalAlign: "left" },
        };
        let opts;
        if (cfg.type === "donut") {
            opts = { ...base, series: cfg.series, labels: cfg.labels, legend: { position: "bottom" }, plotOptions: { pie: { donut: { size: "70%" } } } };
        } else {
            const max = Math.max(0, ...cfg.series.flatMap((x) => x.data));
            opts = {
                ...base,
                series: cfg.series,
                xaxis: { categories: cfg.categories, axisBorder: { show: false }, axisTicks: { show: false }, labels: { rotate: 0, hideOverlappingLabels: true } },
                yaxis: { min: 0, forceNiceScale: true, ...(max < 5 && !cfg.currency ? { max: Math.max(1, max), tickAmount: Math.max(1, max) } : {}), labels: { formatter: (v) => (cfg.currency && v >= 1000 ? Math.round(v / 1000) + "rb" : Math.round(v)) } },
                stroke: { curve: "smooth", width: cfg.type === "area" ? 2 : 0 },
                fill: cfg.type === "area" ? { type: "gradient", gradient: { opacityFrom: 0.35, opacityTo: 0 } } : { opacity: 1 },
                plotOptions: { bar: { borderRadius: 5, columnWidth: "45%" } },
            };
        }
        const chart = new ApexCharts(el, opts);
        chart.render();
        window.addEventListener("theme-changed", () => chart.updateOptions({ theme: { mode: dark() ? "dark" : "light" }, grid: { borderColor: dark() ? "#1d2939" : "#eaecf0" } }));
    });
}
