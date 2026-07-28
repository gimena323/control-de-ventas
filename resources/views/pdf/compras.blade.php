<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Compras - Emma Fashion</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 9px; color: #333; padding: 15px; }

        .header { text-align: center; margin-bottom: 15px; padding-bottom: 12px; border-bottom: 3px solid #e11d48; }
        .header h1 { font-size: 24px; font-weight: 800; color: #e11d48; letter-spacing: 2px; margin-bottom: 2px; }
        .header h2 { font-size: 11px; font-weight: 400; color: #666; margin-bottom: 6px; }
        .header h3 { font-size: 15px; font-weight: 700; color: #333; margin-top: 6px; }

        .meta { display: flex; justify-content: space-between; margin-bottom: 12px; padding: 8px 12px; background: #f8f9fa; border-radius: 4px; font-size: 8px; color: #555; border: 1px solid #e9ecef; }
        .meta strong { color: #333; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { border: 1px solid #dee2e6; padding: 4px 5px; text-align: left; }
        th { background-color: #e11d48; color: white; font-weight: 600; font-size: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        td { font-size: 8px; }
        tr:nth-child(even) { background-color: #f8f9fa; }
        tr:hover { background-color: #fff1f2; }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row { font-weight: bold; background-color: #fce7f3 !important; }
        .total-row td { border-top: 2px solid #e11d48; }

        .section-title { font-size: 12px; font-weight: 700; margin: 14px 0 6px 0; padding-bottom: 4px; border-bottom: 2px solid #e11d48; color: #e11d48; }
        .summary-table th { background-color: #fce7f3; color: #be123c; }

        .footer { margin-top: 20px; padding-top: 8px; border-top: 1px solid #dee2e6; text-align: center; font-size: 7px; color: #999; }
    </style>
</head>
<body>
    <div class="header">
        <h1>EMMA FASHION</h1>
        <h2>Sistema Web de Compras, Ventas e Inventario</h2>
        <h3>Reporte de Compras</h3>
    </div>

    <div class="meta">
        <div>
            <strong>Rango de fechas:</strong> {{ $rangoFechas }}
        </div>
        <div>
            <strong>Generado por:</strong> {{ $usuario->name }}
        </div>
        <div>
            <strong>Fecha y hora:</strong> {{ $fechaGeneracion }}
        </div>
    </div>

    @if(count($reporte['detalles']) > 0)
        <div class="section-title">Detalle Completo de Productos Comprados</div>
        <table>
            <thead>
                <tr>
                    <th class="text-center" style="width:30px;">Nro</th>
                    <th style="width:60px;">Fecha</th>
                    <th>Proveedor</th>
                    <th>Categoría</th>
                    <th>Marca</th>
                    <th>Modelo</th>
                    <th>Descripción</th>
                    <th class="text-center" style="width:45px;">Cant.</th>
                    <th class="text-right" style="width:65px;">P. Unitario</th>
                    <th class="text-right" style="width:70px;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reporte['detalles'] as $i => $detalle)
                    <tr>
                        <td class="text-center">{{ $i + 1 }}</td>
                        <td>{{ \Carbon\Carbon::parse($detalle['fecha'])->format('d/m/Y') }}</td>
                        <td>{{ $detalle['proveedor'] }}</td>
                        <td>{{ $detalle['categoria'] }}</td>
                        <td>{{ $detalle['marca'] }}</td>
                        <td>{{ $detalle['modelo'] }}</td>
                        <td>{{ $detalle['descripcion'] }}</td>
                        <td class="text-center">{{ $detalle['cantidad'] }}</td>
                        <td class="text-right">Bs {{ number_format($detalle['precio_unitario'], 2, ',', '.') }}</td>
                        <td class="text-right"><strong>Bs {{ number_format($detalle['subtotal'], 2, ',', '.') }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="section-title">Resumen de Compras por Proveedor</div>
        <table class="summary-table">
            <thead>
                <tr>
                    <th class="text-center" style="width:30px;">Nro</th>
                    <th>Proveedor</th>
                    <th class="text-center" style="width:80px;">Total Productos</th>
                    <th class="text-right" style="width:90px;">Total Compra</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reporte['resumen_proveedor'] as $i => $resumen)
                    <tr>
                        <td class="text-center">{{ $i + 1 }}</td>
                        <td><strong>{{ $resumen['proveedor'] }}</strong></td>
                        <td class="text-center">{{ $resumen['total_productos'] }}</td>
                        <td class="text-right"><strong>Bs {{ number_format($resumen['total_compra'], 2, ',', '.') }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="section-title">Totales Generales</div>
        <table>
            <tbody>
                <tr>
                    <td style="width:200px;"><strong>Total de Compras:</strong></td>
                    <td class="text-right"><strong>{{ $reporte['total_compras'] }}</strong></td>
                </tr>
                <tr>
                    <td><strong>Total de Productos Comprados:</strong></td>
                    <td class="text-right"><strong>{{ number_format($reporte['total_productos']) }} productos</strong></td>
                </tr>
                <tr class="total-row">
                    <td><strong>Monto Total Comprado:</strong></td>
                    <td class="text-right"><strong>Bs {{ number_format($reporte['monto_total'], 2, ',', '.') }}</strong></td>
                </tr>
            </tbody>
        </table>
    @else
        <div style="text-align: center; padding: 40px; color: #999;">
            <p style="font-size: 12px; margin-bottom: 5px;">No se encontraron compras.</p>
            <p style="font-size: 10px;">Intenta ajustar los filtros de búsqueda.</p>
        </div>
    @endif

    <div class="footer">
        Emma Fashion &mdash; Sistema Web de Compras, Ventas e Inventario &mdash; Generado el {{ $fechaGeneracion }}
    </div>
</body>
</html>
