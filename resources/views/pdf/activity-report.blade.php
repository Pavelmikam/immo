<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
    h1 { color: #1a56db; font-size: 20px; margin-bottom: 4px; }
    h2 { color: #374151; font-size: 14px; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; margin-top: 16px; }
    table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    th { background: #1a56db; color: white; padding: 6px 8px; text-align: left; }
    td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; }
    .footer { margin-top: 20px; font-size: 10px; color: #9ca3af; text-align: center; }
    .kpi { display: inline-block; width: 22%; text-align: center; padding: 8px; background: #f9fafb; border: 1px solid #e5e7eb; margin-right: 2%; }
    .kpi .value { font-size: 22px; font-weight: bold; color: #1a56db; }
    .kpi .label { font-size: 10px; color: #6b7280; }
</style>
</head>
<body>
<h1>ImmoConnect — Rapport d'Activité</h1>
<p style="color:#6b7280; font-size:11px;">Période : {{ $period }} · Généré le {{ $generatedAt }}</p>

<h2>Indicateurs clés</h2>
<div style="margin-top:10px;">
    <div class="kpi"><div class="value">{{ $stats['acceptance_rate'] }}%</div><div class="label">Taux d'acceptation</div></div>
    <div class="kpi"><div class="value">{{ count($stats['top_cities']) }}</div><div class="label">Villes actives</div></div>
    <div class="kpi"><div class="value">{{ count($stats['top_types']) }}</div><div class="label">Types de biens</div></div>
</div>

<h2>Top villes</h2>
<table>
    <tr><th>Ville</th><th>Annonces actives</th><th>Prix moyen (FCFA)</th></tr>
    @foreach($stats['avg_price_by_city'] as $city)
    <tr>
        <td>{{ $city['city'] }}</td>
        <td>{{ $city['count'] }}</td>
        <td>{{ number_format($city['avg_price'], 0, ',', ' ') }}</td>
    </tr>
    @endforeach
</table>

<h2>Types de biens les plus demandés</h2>
<table>
    <tr><th>Type</th><th>Annonces</th><th>Prix moyen (FCFA)</th></tr>
    @foreach($stats['top_types'] as $type)
    <tr>
        <td>{{ $type['type'] }}</td>
        <td>{{ $type['count'] }}</td>
        <td>{{ number_format($type['avg_price'], 0, ',', ' ') }}</td>
    </tr>
    @endforeach
</table>

<div class="footer">ImmoConnect Cameroun · rapport généré automatiquement · {{ $generatedAt }}</div>
</body>
</html>
