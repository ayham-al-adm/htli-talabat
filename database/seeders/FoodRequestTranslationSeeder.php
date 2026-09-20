<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FoodRequestTranslationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $translations = [
            'en' => [
                'food-requests' => 'Food Requests',
                'food_requests' => 'Food Requests',
                'food_request_details' => 'Food Request Details',
                'food' => 'Food',
                'manage-food-request' => 'Manage Food Request',
            ],
            'ar' => [
                'food-requests' => 'طلبات الطعام',
                'food_requests' => 'طلبات الطعام',
                'food_request_details' => 'تفاصيل طلب الطعام',
                'food' => 'طعام',
                'manage-food-request' => 'إدارة طلبات الطعام',
            ],
            'es' => [
                'food-requests' => 'Solicitudes de Comida',
                'food_requests' => 'Solicitudes de Comida',
                'food_request_details' => 'Detalles de Solicitud de Comida',
                'food' => 'Comida',
                'manage-food-request' => 'Gestionar Solicitudes de Comida',
            ],
            'fr' => [
                'food-requests' => 'Demandes de Nourriture',
                'food_requests' => 'Demandes de Nourriture',
                'food_request_details' => 'Détails de la Demande de Nourriture',
                'food' => 'Nourriture',
                'manage-food-request' => 'Gérer les Demandes de Nourriture',
            ],
        ];

        foreach ($translations as $locale => $keys) {
            foreach ($keys as $key => $value) {
                DB::table('ltm_translations')->updateOrInsert(
                    [
                        'locale' => $locale,
                        'group' => 'messages',
                        'key' => $key,
                    ],
                    [
                        'value' => $value,
                        'status' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}
