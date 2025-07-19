<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // DB::table('samples')->truncate();
        // DB::table('water_contents')->truncate();
        // DB::table('densities')->truncate();
        // DB::table('clients')->delete();
        // DB::table('users')->delete();



        // DB::table('boreholes')->truncate();
        // DB::table('projects')->delete();
        // DB::table('granulometries')->truncate();
        // DB::table('atterberglimits')->truncate();


        // Generăm 10 utilizatori
        \App\Models\User::factory(10)->create()->each(function ($user) {

            // Fiecare utilizator va avea între 1 și 5 clienți
            \App\Models\Client::factory(rand(1, 5))->create(['user_id' => $user->id])->each(function ($client) use ($user) {

                // Fiecare client va avea între 1 și 5 proiecte
                \App\Models\Project::factory(rand(1, 5))->create(['client_id' => $client->id, 'user_id' => $user->id])->each(function ($project) {

                    // Pentru fiecare proiect, generăm între 1 și 3 boreholes
                    \App\Models\Borehole::factory(rand(1, 3))->create(['project_id' => $project->id])->each(function ($borehole) {

                        // Pentru fiecare borehole, generăm între 5 și 10 samples
                        \App\Models\Sample::factory(rand(5, 10))->create(['borehole_id' => $borehole->id])->each(function ($sample) {

                            // Pentru fiecare sample, generăm 1 WaterContent, Density, AtterbergLimit, Granulometry
                            \App\Models\Granulometry::factory(1)->create(['sample_id' => $sample->id]);
                            \App\Models\WaterContent::factory(1)->create(['sample_id' => $sample->id]);
                            \App\Models\BulkDensity::factory(1)->create(['sample_id' => $sample->id]);
                            \App\Models\ParticleDensity::factory(1)->create(['sample_id' => $sample->id]);
                            \App\Models\AtterbergLimit::factory(1)->create(['sample_id' => $sample->id]);
                        });
                    });
                });
            });
        });

        // \App\Models\WaterContent::factory(10)->create();
        // \App\Models\Density::factory(10)->create();
        // \App\Models\AtterbergLimit::factory(10)->create();
        // \App\Models\Granulometry::factory(10)->create();



        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
