<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Resumen de movimientos de billetera</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1a1a1a; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .meta { color: #666; font-size: 11px; margin-bottom: 16px; }
        .totals { display: table; width: 100%; margin-bottom: 16px; }
        .totals .cell {
            display: table-cell;
            width: 25%;
            padding: 8px;
            border: 1px solid #ddd;
            text-align: center;
        }
        .totals .cell .label { font-size: 10px; color: #666; text-transform: uppercase; }
        .totals .cell .value { font-size: 16px; font-weight: bold; margin-top: 4px; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        table.data th, table.data td { border: 1px solid #ddd; padding: 4px 6px; font-size: 10px; text-align: left; }
        table.data th { background: #f5f5f5; }
        .text-right { text-align: right; }
        .section-title { font-size: 13px; font-weight: bold; margin: 16px 0 6px; }
    </style>
</head>
<body>
    <h1>Resumen de movimientos de billetera</h1>
    <p class="meta">Generado el {{ $generatedAt->format('d/m/Y H:i') }}</p>

    <div class="totals">
        <div class="cell">
            <div class="label">Aprobados</div>
            <div class="value">{{ $totals['approved'] }}</div>
        </div>
        <div class="cell">
            <div class="label">Rechazados</div>
            <div class="value">{{ $totals['rejected'] }}</div>
        </div>
        <div class="cell">
            <div class="label">Pendientes</div>
            <div class="value">{{ $totals['pending'] }}</div>
        </div>
        <div class="cell">
            <div class="label">Tasa de rechazo</div>
            <div class="value">{{ $totals['rejection_rate'] }}%</div>
        </div>
    </div>

    @if($amountByCurrency->isNotEmpty())
        <p class="section-title">Monto aprobado por moneda</p>
        <table class="data">
            <thead>
                <tr><th>Moneda</th><th class="text-right">Monto</th></tr>
            </thead>
            <tbody>
                @foreach($amountByCurrency as $row)
                    <tr>
                        <td>{{ $row->iso_code }}</td>
                        <td class="text-right">{{ $row->symbol }} {{ number_format((float) $row->total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($rows->isNotEmpty())
        <p class="section-title">Pendientes más antiguos ({{ $rows->count() }})</p>
        <table class="data">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Email</th>
                    <th>Tipo</th>
                    <th class="text-right">Monto</th>
                    <th>Creado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $m)
                    <tr>
                        <td>{{ trim(($m->user?->first_name ?? '') . ' ' . ($m->user?->last_name ?? '')) }}</td>
                        <td>{{ $m->user?->email }}</td>
                        <td>{{ $m->type }}</td>
                        <td class="text-right">{{ number_format((float) $m->amount, 2) }}</td>
                        <td>{{ $m->created_at?->format('d/m/Y H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
