<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $reportTitle }}</title>
    @php
        $primary = $company?->primary_color ?? '#2F5FDE';
        $secondary = $company?->secondary_color ?? '#52606D';
        $moneyLabels = \App\Support\ReportMoneyFields::LABELS;
    @endphp
    <style>
        @page { margin: 90px 32px 60px; }
        body { font-family: 'Helvetica', sans-serif; color: #1f2933; font-size: 10.5px; }

        header { position: fixed; top: -70px; left: 0; right: 0; height: 70px; }
        .header-row { width: 100%; border-collapse: collapse; }
        .header-row td { vertical-align: middle; }
        .header-logo { width: 56px; }
        .header-logo img { max-width: 52px; max-height: 52px; }
        .company-name { font-size: 16px; font-weight: bold; color: #1f2933; margin: 0; }
        .report-title { font-size: 12px; font-weight: bold; color: {{ $primary }}; margin: 2px 0 0; }
        .header-rule { border-bottom: 2px solid {{ $primary }}; margin-top: 6px; }

        footer { position: fixed; bottom: -46px; left: 0; right: 0; font-size: 8.5px; color: #9aa5b1; text-align: center; border-top: 1px solid #e4e7eb; padding-top: 6px; }
        .page-num:after { content: counter(page); }
        .page-count:after { content: counter(pages); }

        .meta { width: 100%; border-collapse: collapse; margin: 4px 0 16px; }
        .meta td { padding: 2px 0; font-size: 9.5px; color: #52606d; }
        .meta td.label { width: 90px; font-weight: bold; color: #1f2933; }

        .section-title { font-size: 11.5px; font-weight: bold; color: #1f2933; margin: 0 0 8px; padding-bottom: 4px; border-bottom: 1px solid #e4e7eb; }

        .kpis { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin: 0 0 18px; }
        .kpis td { width: 25%; border: 1px solid #e4e7eb; border-top: 3px solid {{ $primary }}; padding: 8px 9px; border-radius: 3px; }
        .kpi-label { font-size: 8px; color: #52606d; text-transform: uppercase; letter-spacing: 0.03em; }
        .kpi-value { font-size: 14px; font-weight: bold; margin-top: 3px; color: #1f2933; }

        .chart-section { margin-bottom: 18px; }
        .chart-frame { border: 1px solid #e4e7eb; border-radius: 3px; padding: 8px; }

        table.data { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.data caption { text-align: left; font-size: 11px; font-weight: bold; color: #1f2933; padding: 0 0 6px; caption-side: top; }
        table.data thead { display: table-header-group; }
        table.data th, table.data td { border: 1px solid #e4e7eb; padding: 5px 7px; font-size: 9.5px; text-align: left; }
        table.data th { background: {{ $primary }}; color: #ffffff; font-weight: bold; }
        table.data td.numeric, table.data th.numeric { text-align: right; }
        table.data tbody tr:nth-child(even) td { background: #f6f8fa; }
        table.data td.empty { text-align: center; color: #9aa5b1; font-style: italic; }
    </style>
</head>
<body>
    <header>
        <table class="header-row">
            <tr>
                @if ($logoPath)
                    <td class="header-logo"><img src="{{ $logoPath }}" alt="Logo"></td>
                @endif
                <td>
                    <p class="company-name">{{ $company?->name ?? 'Ventia' }}</p>
                    <p class="report-title">{{ $reportTitle }}</p>
                </td>
            </tr>
        </table>
        <div class="header-rule"></div>
    </header>

    <footer>
        Generado por Ventia — {{ $generatedAt }} — Página <span class="page-num"></span> de <span class="page-count"></span>
    </footer>

    <table class="meta">
        <tr>
            <td class="label">Período</td>
            <td>{{ \Illuminate\Support\Carbon::parse($filters['date_from'])->translatedFormat('d M Y') }}
                — {{ \Illuminate\Support\Carbon::parse($filters['date_to'])->translatedFormat('d M Y') }}</td>
            <td class="label">Sucursal</td>
            <td>{{ $branchName ?? 'Todas las sucursales' }}</td>
        </tr>
        <tr>
            <td class="label">Generado</td>
            <td>{{ $generatedAt }}</td>
            <td class="label">Generado por</td>
            <td>{{ $generatedBy }}</td>
        </tr>
    </table>

    @if (count($data['kpis'] ?? []) > 0)
        <p class="section-title">Resumen</p>
        <table class="kpis">
            @foreach (array_chunk($data['kpis'], 4) as $row)
                <tr>
                    @foreach ($row as $kpi)
                        <td>
                            <div class="kpi-label">{{ $kpi['label'] }}</div>
                            <div class="kpi-value">{{ in_array($kpi['label'], $moneyLabels, true) ? '$'.$kpi['value'] : $kpi['value'] }}</div>
                        </td>
                    @endforeach
                    @for ($i = count($row); $i < 4; $i++)
                        <td style="border: none;"></td>
                    @endfor
                </tr>
            @endforeach
        </table>
    @endif

    @if (!empty($chartSvg))
        <div class="chart-section">
            <p class="section-title">Tendencia</p>
            <div class="chart-frame">
                {!! $chartSvg !!}
            </div>
        </div>
    @endif

    @foreach ($data['tables'] ?? [] as $table)
        <table class="data">
            <caption>{{ $table['title'] }}</caption>
            <thead>
                <tr>
                    @foreach ($table['columns'] as $column)
                        <th class="{{ in_array($column, $moneyLabels, true) || in_array($column, ['Cantidad', 'Existencia', 'Tickets', 'Movimientos', 'Cobros', 'Stock mínimo'], true) ? 'numeric' : '' }}">{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($table['rows'] as $row)
                    <tr>
                        @foreach ($row as $index => $cell)
                            @php $column = $table['columns'][$index] ?? ''; $isMoney = in_array($column, $moneyLabels, true); @endphp
                            <td class="{{ $isMoney || in_array($column, ['Cantidad', 'Existencia', 'Tickets', 'Movimientos', 'Cobros', 'Stock mínimo'], true) ? 'numeric' : '' }}">
                                {{ $cell === null || $cell === '' ? '—' : ($isMoney ? '$'.$cell : $cell) }}
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="{{ count($table['columns']) }}">Sin datos en el período seleccionado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endforeach
</body>
</html>
