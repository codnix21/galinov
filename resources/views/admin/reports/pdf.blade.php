{{-- PDF-версия отчёта. --}}
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отчёт за период {{ $dateFrom }} - {{ $dateTo }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #000;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #000;
        }
        
        .header h1 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 14px;
            color: #666;
        }
        
        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 1px solid #000;
        }
        
        .stats-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        
        .stats-row {
            display: table-row;
        }
        
        .stats-item {
            display: table-cell;
            padding: 10px;
            border: 1px solid #000;
            text-align: center;
            vertical-align: middle;
        }
        
        .stats-item-label {
            font-size: 10px;
            color: #666;
            margin-top: 5px;
        }
        
        .stats-item-value {
            font-size: 20px;
            font-weight: bold;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        table th,
        table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        
        table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        
        table td {
            font-size: 11px;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #000;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        
        .date-range {
            font-size: 14px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .no-data {
            text-align: center;
            color: #999;
            font-style: italic;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Отчёт по системе недвижимости</h1>
        <p>Агентство недвижимости</p>
    </div>
    
    <div class="date-range">
        <strong>Период:</strong> {{ \Carbon\Carbon::parse($dateFrom)->format('d.m.Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d.m.Y') }}
    </div>
    @if(!empty($generatedByFio))
    <div class="date-range" style="font-size: 12px; margin-top: -10px;">
        <strong>Сформировал:</strong> {{ $generatedByFio }}@if(!empty($generatedByEmail)) ({{ $generatedByEmail }})@endif<br>
        <strong>Дата формирования:</strong> {{ $generatedAt->format('d.m.Y H:i:s') }}
    </div>
    @endif
    
    <!-- Статистика по объявлениям -->
    <div class="section">
        <div class="section-title">Статистика по объявлениям</div>
        <div class="stats-grid">
            <div class="stats-row">
                <div class="stats-item">
                    <div class="stats-item-value">{{ $propertiesStats['total'] }}</div>
                    <div class="stats-item-label">Всего объявлений</div>
                </div>
                <div class="stats-item">
                    <div class="stats-item-value">{{ $propertiesStats['active'] }}</div>
                    <div class="stats-item-label">Активных</div>
                </div>
                <div class="stats-item">
                    <div class="stats-item-value">{{ $propertiesStats['pending_review'] ?? 0 }}</div>
                    <div class="stats-item-label">На модерации</div>
                </div>
                <div class="stats-item">
                    <div class="stats-item-value">{{ $propertiesStats['draft'] }}</div>
                    <div class="stats-item-label">Черновиков</div>
                </div>
                <div class="stats-item">
                    <div class="stats-item-value">{{ $propertiesStats['sold'] }}</div>
                    <div class="stats-item-label">Продано</div>
                </div>
                <div class="stats-item">
                    <div class="stats-item-value">{{ $propertiesStats['inactive'] }}</div>
                    <div class="stats-item-label">Неактивных</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Статистика по договорам -->
    <div class="section">
        <div class="section-title">Статистика по договорам</div>
        <div class="stats-grid">
            <div class="stats-row">
                <div class="stats-item">
                    <div class="stats-item-value">{{ $contractsStats['total'] }}</div>
                    <div class="stats-item-label">Всего договоров</div>
                </div>
                <div class="stats-item">
                    <div class="stats-item-value">{{ $contractsStats['active'] }}</div>
                    <div class="stats-item-label">Активных</div>
                </div>
                <div class="stats-item">
                    <div class="stats-item-value">{{ $contractsStats['pending'] }}</div>
                    <div class="stats-item-label">На подтверждении</div>
                </div>
                <div class="stats-item">
                    <div class="stats-item-value">{{ $contractsStats['rejected'] }}</div>
                    <div class="stats-item-label">Отклонено</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Статистика по пользователям -->
    <div class="section">
        <div class="section-title">Статистика по пользователям</div>
        <div class="stats-grid">
            <div class="stats-row">
                <div class="stats-item">
                    <div class="stats-item-value">{{ $usersStats['total'] }}</div>
                    <div class="stats-item-label">Всего пользователей</div>
                </div>
                <div class="stats-item">
                    <div class="stats-item-value">{{ $usersStats['clients'] }}</div>
                    <div class="stats-item-label">Клиентов</div>
                </div>
                <div class="stats-item">
                    <div class="stats-item-value">{{ $usersStats['realtors'] }}</div>
                    <div class="stats-item-label">Риэлторов</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Объявления по дням -->
    @if($dailyProperties->count() > 0)
    <div class="section">
        <div class="section-title">Объявления по дням</div>
        <table>
            <thead>
                <tr>
                    <th>Дата</th>
                    <th>Количество объявлений</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dailyProperties as $day)
                <tr>
                    <td>{{ $day['date'] }}</td>
                    <td style="text-align: center;">{{ $day['count'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    
    <!-- Статистика по типам недвижимости -->
    @if($propertiesByType->count() > 0)
    <div class="section">
        <div class="section-title">Объявления по типам недвижимости</div>
        <table>
            <thead>
                <tr>
                    <th>Тип недвижимости</th>
                    <th>Количество</th>
                </tr>
            </thead>
            <tbody>
                @foreach($propertiesByType as $type)
                <tr>
                    <td>{{ $type['type'] }}</td>
                    <td style="text-align: center;">{{ $type['count'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    
    <!-- Топ пользователей по объявлениям -->
    @if($topUsersByProperties->count() > 0)
    <div class="section">
        <div class="section-title">Топ пользователей по количеству объявлений</div>
        <table>
            <thead>
                <tr>
                    <th>№</th>
                    <th>Пользователь</th>
                    <th>Email</th>
                    <th>Количество объявлений</th>
                </tr>
            </thead>
            <tbody>
                @foreach($topUsersByProperties as $index => $user)
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td>{{ $user->familia }} {{ $user->imya }} {{ $user->otchestvo }}</td>
                    <td>{{ $user->email_polzovatela }}</td>
                    <td style="text-align: center;">{{ $user->properties_count }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    
    <div class="footer">
        <p>Отчёт сформирован: {{ $generatedAt->format('d.m.Y H:i:s') }}</p>
        @if(!empty($generatedByFio))
        <p>Сформировал: {{ $generatedByFio }}@if(!empty($generatedByEmail)) ({{ $generatedByEmail }})@endif</p>
        @endif
    </div>
</body>
</html>






