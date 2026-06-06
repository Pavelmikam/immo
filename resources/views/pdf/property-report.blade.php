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
    .badge { display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 10px; }
    .active { background: #dcfce7; color: #166534; }
    .pending { background: #fef9c3; color: #854d0e; }
    .footer { margin-top: 20px; font-size: 10px; color: #9ca3af; text-align: center; }
    .kpi { display: inline-block; width: 22%; text-align: center; padding: 8px; background: #f9fafb; border: 1px solid #e5e7eb; margin-right: 2%; }
    .kpi .value { font-size: 22px; font-weight: bold; color: #1a56db; }
    .kpi .label { font-size: 10px; color: #6b7280; }
</style>
</head>
<body>
<h1>ImmoConnect — Rapport d'Annonce</h1>
<p style="color:#6b7280; font-size:11px;">Généré le {{ $generatedAt }}</p>

<h2>Informations sur l'annonce</h2>
<table>
    <tr><td><strong>Titre</strong></td><td>{{ $property->title }}</td></tr>
    <tr><td><strong>Type</strong></td><td>{{ $property->type }}</td></tr>
    <tr><td><strong>Ville</strong></td><td>{{ $property->city }}</td></tr>
    <tr><td><strong>Quartier</strong></td><td>{{ $property->district }}</td></tr>
    <tr><td><strong>Prix</strong></td><td>{{ number_format($property->price, 0, ',', ' ') }} FCFA</td></tr>
    <tr><td><strong>Surface</strong></td><td>{{ $property->surface }} m²</td></tr>
    <tr><td><strong>Statut</strong></td><td>{{ $property->status }}</td></tr>
    <tr><td><strong>Propriétaire</strong></td><td>{{ $property->owner?->name }} ({{ $property->owner?->email }})</td></tr>
    <tr><td><strong>Publiée le</strong></td><td>{{ $property->published_at?->format('d/m/Y') ?? 'Non publiée' }}</td></tr>
</table>

<h2>Statistiques — Période : {{ $stats['period'] }}</h2>
<div style="margin-top:10px;">
    <div class="kpi"><div class="value">{{ $stats['views']['total'] }}</div><div class="label">Vues totales</div></div>
    <div class="kpi"><div class="value">{{ $stats['views']['unique'] }}</div><div class="label">Vues uniques</div></div>
    <div class="kpi"><div class="value">{{ $stats['requests']['total'] }}</div><div class="label">Demandes</div></div>
    <div class="kpi"><div class="value">{{ $stats['conversion_rate'] }}%</div><div class="label">Taux conversion</div></div>
</div>

<h2>Détail des demandes</h2>
<table>
    <tr><th>Statut</th><th>Nombre</th></tr>
    <tr><td>En attente</td><td>{{ $stats['requests']['en_attente'] }}</td></tr>
    <tr><td>Acceptées</td><td>{{ $stats['requests']['acceptees'] }}</td></tr>
    <tr><td>Refusées</td><td>{{ $stats['requests']['refusees'] }}</td></tr>
</table>

<div class="footer">ImmoConnect Cameroun · rapport généré automatiquement</div>
</body>
</html>
