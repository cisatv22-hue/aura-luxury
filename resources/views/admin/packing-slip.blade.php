<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Empaque {{ $order->number }} · AURA</title>
    <style>
        body{font:16px/1.45 system-ui,sans-serif;color:#171717;margin:2rem auto;max-width:760px;padding:0 1rem}header{display:flex;justify-content:space-between;align-items:start;border-bottom:2px solid;padding-bottom:1rem}h1{margin:0;font-size:1.5rem}small{color:#555}section{margin:1.5rem 0}table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:.65rem .4rem;border-bottom:1px solid #ccc}.qty{font-size:1.25rem;font-weight:700;text-align:center}.notes{min-height:5rem;border:1px solid #999;padding:.7rem}.print{padding:.7rem 1rem;background:#111;color:#fff;border:0;border-radius:2rem}@media print{body{margin:0;max-width:none}.print{display:none}}
    </style>
</head>
<body>
    <header><div><small>AURA · HOJA DE EMPAQUE</small><h1>{{ $order->number }}</h1><p>{{ $order->created_at?->format('d/m/Y H:i') }}</p></div><button class="print" onclick="window.print()">Imprimir</button></header>
    <section><h2>Cliente y entrega</h2><p><strong>{{ $order->customer?->name }}</strong><br>{{ $order->customer?->phone }}<br>{{ $order->customer?->city }}</p><p>Método: {{ $order->delivery_method === 'pickup' ? 'Recolección' : 'Envío' }} · Zona: {{ $order->delivery_zone ?: 'Por confirmar' }}</p></section>
    <section><h2>Artículos · {{ $order->items->sum('quantity') }} piezas</h2><table><thead><tr><th>Producto</th><th>SKU</th><th>Color / talla</th><th class="qty">Cant.</th></tr></thead><tbody>@foreach($order->items as $item)<tr><td>{{ $item->name }}</td><td>{{ $item->sku }}</td><td>{{ $item->color }} / {{ $item->size }}</td><td class="qty">{{ $item->quantity }}</td></tr>@endforeach</tbody></table></section>
    <section><h2>Notas</h2><div class="notes">{{ $order->customer_note }}</div></section>
</body>
</html>
