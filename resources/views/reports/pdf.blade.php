<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $reportTitle }}</title>
    <style>
        @page { margin: 28px 32px; }
        body { font-family: 'Helvetica', sans-serif; color: #1f2933; font-size: 11px; }
        h1 { font-size: 18px; margin: 0 0 2px; }
        .subtitle { color: #52606d; font-size: 11px; margin: 0 0 16px; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        .meta td { padding: 2px 0; font-size: 10px; color: #52606d; }
        .meta td.label { width: 110px; font-weight: bold; color: #1f2933; }
        .kpis { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .kpis td { width: 25%; border: 1px solid #d9e2ec; padding: 8px; }
        .kpi-label { font-size: 9px; color: #52606d; text-transform: uppercase; }
        .kpi-value { font-size: 15px; font-weight: bold; margin-top: 2px; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        table.data caption { text-align: left; font-size: 12px; font-weight: bold; padding: 6px 0; }
        table.data th, table.data td { border: 1px solid #d9e2ec; padding: 5px 6px; font-size: 10px; text-align: left; }
        table.data th { background: #f0f4f8; }
        .chart { margin-bottom: 20px; }
        .footer { position: fixed; bottom: -18px; left: 0; right: 0; font-size: 9px; color: #9aa5b1; text-align: center; }
    </style>
</head>
<body>
    <h1>{{ $company->name ?? 'Ventia' }}</h1>
    <p class="subtitle">{{ $reportTitle }}</p>

    <table class="meta">
        <tr>
            <td class="label">Período</td>
            <td>{{ $filters['date_from'] }} — {{ $filters['date_to'] }}</td>
        </tr>
        <tr>
            <td class="label">Sucursal</td>
            <td>{{ $branchName ?? 'Todas las sucursales' }}</td>
        </tr>
        <tr>
            <td class="label">Generado</td>
            <td>{{ $generatedAt }} por {{ $generatedBy }}</td>
        </tr>
    </table>

    @if (count($data['kpis'] ?? []) > 0)
        <table class="kpis">
            @foreach (array_chunk($data['kpis'], 4) as $row)
                <tr>
                    @foreach ($row as $kpi)
                        <td>
                            <div class="kpi-label">{{ $kpi['label'] }}</div>
                            <div class="kpi-value">{{ $kpi['value'] }}</div>
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
        <div class="chart">
            {!! $chartSvg !!}
        </div>
    @endif

    @foreach ($data['tables'] ?? [] as $table)
        <table class="data">
            <caption>{{ $table['title'] }}</caption>
            <thead>
                <tr>
                    @foreach ($table['columns'] as $column)
                        <th>{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($table['rows'] as $row)
                    <tr>
                        @foreach ($row as $cell)
                            <td>{{ $cell ?? '—' }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($table['columns']) }}">Sin datos en el período seleccionado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endforeach

    <div class="footer">Ventia — Generado el {{ $generatedAt }}</div>
</body>
</html>
