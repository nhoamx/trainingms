<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 15px;
        }
        
        .header h1 {
            margin: 0;
            color: #2563eb;
            font-size: 24px;
            font-weight: bold;
        }
        
        .header .subtitle {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .meta-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 10px;
            color: #666;
        }
        
        .section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 15px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
        }
        
        .chart-container {
            margin-bottom: 20px;
            border: 1px solid #e5e7eb;
            border-radius: 5px;
            padding: 15px;
        }
        
        .chart-title {
            font-weight: bold;
            margin-bottom: 10px;
            color: #374151;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .data-table th,
        .data-table td {
            border: 1px solid #d1d5db;
            padding: 8px;
            text-align: left;
        }
        
        .data-table th {
            background-color: #f3f4f6;
            font-weight: bold;
            color: #374151;
        }
        
        .data-table tr:nth-child(even) {
            background-color: #f9fafb;
        }
        
        .qualification-bar {
            height: 20px;
            border-radius: 3px;
            margin: 2px 0;
            position: relative;
            overflow: hidden;
        }
        
        .qualification-bar.nulo { background-color: #22c55e; }
        .qualification-bar.bajo { background-color: #84cc16; }
        .qualification-bar.medio { background-color: #eab308; }
        .qualification-bar.alto { background-color: #f97316; }
        .qualification-bar.muy-alto { background-color: #dc2626; }
        
        .qualification-label {
            position: absolute;
            left: 5px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 10px;
            font-weight: bold;
            color: white;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 15px;
        }
        
        .stat-card {
            border: 1px solid #e5e7eb;
            border-radius: 5px;
            padding: 10px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 18px;
            font-weight: bold;
            color: #2563eb;
        }
        
        .stat-label {
            font-size: 10px;
            color: #666;
            margin-top: 2px;
        }
        
        .footer {
            position: fixed;
            bottom: 20px;
            left: 20px;
            right: 20px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }
        
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>@yield('title')</h1>
        <div class="subtitle">{{ $organization }}</div>
    </div>
    
    <div class="meta-info">
        <div>Generado el: {{ $date }}</div>
        <div>Usuario: {{ $user->name ?? 'Sistema' }}</div>
    </div>
    
    @yield('content')
    
    <div class="footer">
        Sistema de Gestión de Evaluaciones - {{ $organization }} - Página <span class="pagenum"></span>
    </div>
</body>
</html>