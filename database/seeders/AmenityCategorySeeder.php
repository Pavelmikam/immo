<?php

namespace Database\Seeders;

use App\Models\AmenityCategory;
use Illuminate\Database\Seeder;

class AmenityCategorySeeder extends Seeder
{
    public function run(): void
    {
        $propertyTypes = [
            ['value' => 'chambre_simple',    'label' => 'Chambre simple',     'sort_order' => 1],
            ['value' => 'studio',            'label' => 'Studio',             'sort_order' => 2],
            ['value' => 'appartement',       'label' => 'Appartement',        'sort_order' => 3],
            ['value' => 'maison',            'label' => 'Maison',             'sort_order' => 4],
            ['value' => 'mini_cite',         'label' => 'Mini-cité',          'sort_order' => 5],
            ['value' => 'local_commercial',  'label' => 'Local commercial',   'sort_order' => 6],
            ['value' => 'chambre_etudiante', 'label' => 'Chambre étudiante',  'sort_order' => 7],
            ['value' => 'logement_meuble',   'label' => 'Logement meublé',    'sort_order' => 8],
        ];

        $amenities = [
            ['value' => 'eau_courante',       'label' => 'Eau courante',        'sort_order' => 1],
            ['value' => 'electricite',        'label' => 'Électricité',         'sort_order' => 2],
            ['value' => 'climatisation',      'label' => 'Climatisation',       'sort_order' => 3],
            ['value' => 'groupe_electrogene', 'label' => 'Groupe électrogène',  'sort_order' => 4],
            ['value' => 'internet_wifi',      'label' => 'Internet / Wi-Fi',    'sort_order' => 5],
            ['value' => 'parking',            'label' => 'Parking',             'sort_order' => 6],
            ['value' => 'cloture',            'label' => 'Clôture',             'sort_order' => 7],
            ['value' => 'gardien',            'label' => 'Gardien',             'sort_order' => 8],
            ['value' => 'meuble',             'label' => 'Meublé',              'sort_order' => 9],
            ['value' => 'cuisine_equipee',    'label' => 'Cuisine équipée',     'sort_order' => 10],
        ];

        $charges = [
            ['value' => 'eau',         'label' => 'Eau incluse',         'sort_order' => 1],
            ['value' => 'electricite', 'label' => 'Électricité incluse', 'sort_order' => 2],
            ['value' => 'gardien',     'label' => 'Gardien inclus',      'sort_order' => 3],
            ['value' => 'ordures',     'label' => 'Collecte des ordures', 'sort_order' => 4],
        ];

        foreach ($propertyTypes as $type) {
            AmenityCategory::updateOrCreate(
                ['category' => 'property_type', 'value' => $type['value']],
                ['label' => $type['label'], 'sort_order' => $type['sort_order'], 'is_active' => true]
            );
        }
        foreach ($amenities as $amenity) {
            AmenityCategory::updateOrCreate(
                ['category' => 'amenity', 'value' => $amenity['value']],
                ['label' => $amenity['label'], 'sort_order' => $amenity['sort_order'], 'is_active' => true]
            );
        }
        foreach ($charges as $charge) {
            AmenityCategory::updateOrCreate(
                ['category' => 'charge', 'value' => $charge['value']],
                ['label' => $charge['label'], 'sort_order' => $charge['sort_order'], 'is_active' => true]
            );
        }
    }
}
