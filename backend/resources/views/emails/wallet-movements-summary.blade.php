@component('mail::message')
# Resumen semanal de movimientos de billetera

Período: {{ $periodLabel }}

- ✅ Aprobados: **{{ $approvedCount }}**
- ❌ Rechazados: **{{ $rejectedCount }}**
- 📈 Tasa de rechazo: **{{ $rejectionRate }}%**
- ⏳ Pendientes actuales: **{{ $pendingCount }}**

@if(count($amountByCurrency) > 0)
## Monto aprobado por moneda

@component('mail::table')
| Moneda | Monto |
| :----- | ----: |
@foreach($amountByCurrency as $row)
| {{ $row['iso_code'] }} | {{ $row['symbol'] }} {{ number_format($row['total'], 2) }} |
@endforeach
@endcomponent
@endif

@if(count($oldestPending) > 0)
## Top {{ count($oldestPending) }} pendientes más antiguos

@component('mail::table')
| Usuario | Tipo | Monto | Creado |
| :------ | :--- | ----: | :----- |
@foreach($oldestPending as $row)
| {{ $row['user'] }} | {{ $row['type'] }} | {{ $row['amount'] }} | {{ $row['created_at'] }} |
@endforeach
@endcomponent
@endif

El detalle completo del período va adjunto en el CSV.

@component('mail::button', ['url' => $url])
Ver movimientos de billetera
@endcomponent

Saludos,<br>
{{ config('app.name') }}
@endcomponent
