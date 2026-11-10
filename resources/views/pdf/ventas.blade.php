<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Ventas - Emma Fashion</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 8px; color: #333; padding: 10px; }

        .header { text-align: center; margin-bottom: 10px; padding-bottom: 8px; border-bottom: 2px solid #e11d48; }
        .header h1 { font-size: 18px; font-weight: 800; color: #e11d48; letter-spacing: 1px; margin-bottom: 1px; }
        .header h2 { font-size: 9px; font-weight: 400; color: #666; margin-bottom: 4px; }
        .header h3 { font-size: 11px; font-weight: 700; color: #333; margin-top: 4px; }

        .meta { display: flex; justify-content: space-between; margin-bottom: 8px; padding: 5px 8px; background: #f8f9fa; border-radius: 3px; font-size: 7px; color: #555; border: 1px solid #e9ecef; }
        .meta strong { color: #333; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { border: 1px solid #dee2e6; padding: 2px 3px; text-align: left; }
        th { background-color: #e11d48; color: white; font-weight: 600; font-size: 7px; text-transform: uppercase; letter-spacing: 0.3px; }
        td { font-size: 7px; }
        tr:nth-child(even) { background-color: #f8f9fa; }
        tr:hover { background-color: #fff1f2; }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row { font-weight: bold; background-color: #fce7f3 !important; }
        .total-row td { border-top: 2px solid #e11d48; }

        .section-title { font-size: 10px; font-weight: 700; margin: 10px 0 4px 0; padding-bottom: 3px; border-bottom: 1px solid #e11d48; color: #e11d48; }
        .summary-table th { background-color: #fce7f3; color: #be123c; }

        .footer { margin-top: 20px; padding-top: 8px; border-top: 1px solid #dee2e6; text-align: center; font-size: 7px; color: #999; }
    </style>
</head>
<body>
    <div class="header">
        <h1>EMMA FASHION</h1>
        <h2>Sistema Web de Compras, Ventas e Inventario</h2>
        <h3>Reporte de Ventas</h3>
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
        <div class="section-title">Detalle de Productos Vendidos</div>
        <table>
            <thead>
                <tr>
                    <th class="text-center" style="width:24px;">Nro</th>
                    <th>Descripción</th>
                    <th>Categoría</th>
                    <th>Marca</th>
                    <th>Género</th>
                    <th class="text-center" style="width:38px;">Cant.</th>
                    <th class="text-center" style="width:38px;">Stock</th>
                    <th class="text-right" style="width:60px;">P. Unit.</th>
                    <th class="text-right" style="width:65px;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reporte['resumen_producto'] as $i => $producto)
                    <tr>
                        <td class="text-center">{{ $i + 1 }}</td>
                        <td>{{ $producto['descripcion'] }}</td>
                        <td>{{ $producto['categoria'] }}</td>
                        <td>{{ $producto['marca'] }}</td>
                        <td>{{ $producto['genero'] ?? '---' }}</td>
                        <td class="text-center">{{ $producto['cantidad'] }}</td>
                        <td class="text-center">{{ $producto['stock'] }}</td>
                        <td class="text-right">Bs {{ number_format($producto['precio_unitario'], 2, ',', '.') }}</td>
                        <td class="text-right"><strong>Bs {{ number_format($producto['subtotal'], 2, ',', '.') }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="section-title">Resumen de Ventas por Cliente</div>
        <table class="summary-table">
            <thead>
                <tr>
                    <th class="text-center" style="width:30px;">Nro</th>
                    <th>Cliente</th>
                    <th class="text-center" style="width:80px;">Total Productos</th>
                    <th class="text-right" style="width:90px;">Total Venta</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reporte['resumen_cliente'] as $i => $resumen)
                    <tr>
                        <td class="text-center">{{ $i + 1 }}</td>
                        <td><strong>{{ $resumen['cliente'] }}</strong></td>
                        <td class="text-center">{{ $resumen['total_productos'] }}</td>
                        <td class="text-right"><strong>Bs {{ number_format($resumen['total_venta'], 2, ',', '.') }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="section-title">Totales Generales</div>
        <table>
            <tbody>
                <tr>
                    <td style="width:200px;"><strong>Total de Ventas:</strong></td>
                    <td class="text-right"><strong>{{ $reporte['total_ventas'] }}</strong></td>
                </tr>
                <tr>
                    <td><strong>Total de Productos Vendidos:</strong></td>
                    <td class="text-right"><strong>{{ number_format($reporte['total_productos']) }} productos</strong></td>
                </tr>
                <tr class="total-row">
                    <td><strong>Monto Total Vendido:</strong></td>
                    <td class="text-right"><strong>Bs {{ number_format($reporte['monto_total'], 2, ',', '.') }}</strong></td>
                </tr>
            </tbody>
        </table>
    @else
        <div style="text-align: center; padding: 40px; color: #999;">
            <p style="font-size: 12px; margin-bottom: 5px;">No se encontraron ventas.</p>
            <p style="font-size: 10px;">Intenta ajustar los filtros de búsqueda.</p>
        </div>
    @endif

    <div class="footer">
        Emma Fashion &mdash; Sistema Web de Compras, Ventas e Inventario &mdash; Generado el {{ $fechaGeneracion }}
    </div>
</body>
</html>
