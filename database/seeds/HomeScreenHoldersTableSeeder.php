<?php

use Illuminate\Database\Seeder;

class HomeScreenHoldersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $arr_home_screen_holders = [
            ['name' => 'Banner', 'slug' => 'BANNER', 'status' => 1],
            ['name' => 'Recent', 'slug' => 'RECENTS', 'status' => 1],
            ['name' => 'All Services', 'slug' => 'ALL_SERVICES', 'status' => 1],
            ['name' => 'All Services Horizontal', 'slug' => 'HORIZONTAL_ALL_SERVICES', 'status' => 1],
            ['name' => 'Recommended Services', 'slug' => 'RECOMMENDED_SERVICE', 'status' => 1],
            ['name' => 'Popular Restaurants', 'slug' => 'POPULAR_RESTAURANT', 'status' => 1],
            ['name' => 'Popular Stores', 'slug' => 'POPULAR_STORE', 'status' => 1],
            ['name' => 'Bottom Banner', 'slug' => 'BOTTOM_BANNERS', 'status' => 1],
            ['name' => 'Add Money', 'slug' => 'ADDMONEY', 'status' => 1],
            ['name' => 'ALL Services V2', 'slug' => 'ALL_SERVICES_V2', 'status' => 1],
        ];
        foreach ($arr_home_screen_holders as $key => $value)
        {
            DB::table('home_screen_holders')->insert([
                'name' => $value['name'],
                'slug' => $value['slug'],
                'status' => $value['status'],
            ]);
        }
    }
}
