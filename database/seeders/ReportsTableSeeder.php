<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ReportsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('reports')->delete();
        
        \DB::table('reports')->insert(array (
            0 => 
            array (
                'id' => 5,
                'created_at' => '2024-02-13 08:07:30',
                'updated_at' => '2024-02-13 08:07:30',
                'user_id' => 1,
                'date' => '2024-02-01',
                'year' => 2024,
                'month' => 2,
                'day' => 1,
            ),
            1 => 
            array (
                'id' => 6,
                'created_at' => '2024-02-13 08:09:15',
                'updated_at' => '2024-02-13 08:09:15',
                'user_id' => 1,
                'date' => '2024-02-02',
                'year' => 2024,
                'month' => 2,
                'day' => 2,
            ),
            2 => 
            array (
                'id' => 7,
                'created_at' => '2024-02-13 08:10:58',
                'updated_at' => '2024-02-13 08:10:58',
                'user_id' => 1,
                'date' => '2024-02-05',
                'year' => 2024,
                'month' => 2,
                'day' => 5,
            ),
            3 => 
            array (
                'id' => 8,
                'created_at' => '2024-02-13 08:11:41',
                'updated_at' => '2024-02-13 08:11:41',
                'user_id' => 1,
                'date' => '2024-02-06',
                'year' => 2024,
                'month' => 2,
                'day' => 6,
            ),
            4 => 
            array (
                'id' => 9,
                'created_at' => '2024-02-13 08:14:56',
                'updated_at' => '2024-02-13 08:14:56',
                'user_id' => 1,
                'date' => '2024-02-07',
                'year' => 2024,
                'month' => 2,
                'day' => 7,
            ),
            5 => 
            array (
                'id' => 10,
                'created_at' => '2024-02-15 07:17:48',
                'updated_at' => '2024-02-15 07:17:48',
                'user_id' => 1,
                'date' => '2024-02-12',
                'year' => 2024,
                'month' => 2,
                'day' => 12,
            ),
            6 => 
            array (
                'id' => 11,
                'created_at' => '2024-02-15 07:18:42',
                'updated_at' => '2024-02-15 07:18:42',
                'user_id' => 1,
                'date' => '2024-02-13',
                'year' => 2024,
                'month' => 2,
                'day' => 13,
            ),
            7 => 
            array (
                'id' => 12,
                'created_at' => '2024-02-15 08:53:12',
                'updated_at' => '2024-02-15 08:53:12',
                'user_id' => 5,
                'date' => '2024-02-08',
                'year' => 2024,
                'month' => 2,
                'day' => 8,
            ),
            8 => 
            array (
                'id' => 13,
                'created_at' => '2024-02-19 02:10:59',
                'updated_at' => '2024-02-19 02:10:59',
                'user_id' => 3,
                'date' => '2024-02-12',
                'year' => 2024,
                'month' => 2,
                'day' => 12,
            ),
            9 => 
            array (
                'id' => 14,
                'created_at' => '2024-02-19 02:11:11',
                'updated_at' => '2024-02-19 02:11:11',
                'user_id' => 3,
                'date' => '2024-02-13',
                'year' => 2024,
                'month' => 2,
                'day' => 13,
            ),
        ));
        
        
    }
}