{{-- Shared look for the admin Credit and Credit payments pages. --}}
<style>
    .crd-toolbar { display: flex; flex-wrap: wrap; gap: 8px; }
    .crd-toolbar .btn { white-space: nowrap; }
    .crd-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 12px; }
    .crd-kpi { display: flex; flex-direction: column; background: #fff; border: 1px solid #ebedf2; border-radius: .5rem; padding: 14px 16px;
        color: inherit; text-decoration: none !important; border-top: 3px solid #2b56a1; transition: box-shadow .15s, transform .15s; min-width: 0; }
    .crd-kpi:hover { box-shadow: 0 6px 18px rgba(16, 24, 40, .08); transform: translateY(-1px); color: inherit; }
    .crd-kpi.is-danger { border-top-color: #e53e3e; }
    .crd-kpi.is-danger .crd-kpi-value { color: #c53030; }
    .crd-kpi.is-warning { border-top-color: #f0a500; }
    .crd-kpi-label { font-size: 11px; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; color: #8a8fa3; }
    .crd-kpi-value { font-size: 20px; font-weight: 700; color: #1f2937; margin-top: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-variant-numeric: tabular-nums; }
    .crd-kpi-sub { font-size: 12px; color: #8a8fa3; }
    .crd-mini-bar { height: 4px; width: 110px; max-width: 100%; border-radius: 2px; background: #edf0f5; overflow: hidden; }
    .crd-mini-bar > span { display: block; height: 100%; background: #2b56a1; }
    .crd-mini-bar.is-high > span { background: #e53e3e; }
    .crd-actions { display: inline-flex; flex-wrap: nowrap; gap: 4px; justify-content: flex-end; }
    .crd-actions .btn { margin: 0 !important; }
    @media (max-width: 767.98px) {
        .crd-actions { display: grid; grid-template-columns: repeat(2, 32px); gap: 4px; }
    }
    @media (max-width: 575.98px) {
        .crd-kpis { grid-template-columns: 1fr 1fr; gap: 8px; }
        .crd-kpi:last-child:nth-child(odd) { grid-column: 1 / -1; }
        .crd-kpi { padding: 10px 12px; }
        .crd-kpi-value { font-size: 16px; }
        .crd-toolbar { width: 100%; }
        .crd-toolbar .btn { flex: 1 1 auto; padding-left: .6rem; padding-right: .6rem; }
    }
</style>
