<?php

namespace Database\Seeders;

use App\Models\Assessee;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AssesseeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $questions = [
            ['pregunta' => 1, 'respuesta' => 'A'],
            ['pregunta' => 2, 'respuesta' => 'C'],
            ['pregunta' => 3, 'respuesta' => 'A'],
            ['pregunta' => 4, 'respuesta' => 'A'],
            ['pregunta' => 5, 'respuesta' => 'B'],
            ['pregunta' => 6, 'respuesta' => 'E'],
            ['pregunta' => 7, 'respuesta' => 'C'],
            ['pregunta' => 8, 'respuesta' => 'A'],
            ['pregunta' => 9, 'respuesta' => 'B'],
            ['pregunta' => 10, 'respuesta' => 'D'],
            ['pregunta' => 11, 'respuesta' => 'D'],
            ['pregunta' => 12, 'respuesta' => 'C'],
            ['pregunta' => 13, 'respuesta' => 'E'],
            ['pregunta' => 14, 'respuesta' => 'E'],
            ['pregunta' => 15, 'respuesta' => 'C'],
            ['pregunta' => 16, 'respuesta' => 'C'],
            ['pregunta' => 17, 'respuesta' => 'E'],
            ['pregunta' => 18, 'respuesta' => 'C'],
            ['pregunta' => 19, 'respuesta' => 'E'],
            ['pregunta' => 20, 'respuesta' => 'E'],
            ['pregunta' => 21, 'respuesta' => 'D'],
            ['pregunta' => 22, 'respuesta' => 'E'],
            ['pregunta' => 23, 'respuesta' => 'A'],
            ['pregunta' => 24, 'respuesta' => 'A'],
            ['pregunta' => 25, 'respuesta' => 'D'],
            ['pregunta' => 26, 'respuesta' => 'E'],
            ['pregunta' => 27, 'respuesta' => 'E'],
            ['pregunta' => 28, 'respuesta' => 'C'],
            ['pregunta' => 29, 'respuesta' => 'C'],
            ['pregunta' => 30, 'respuesta' => 'E'],
            ['pregunta' => 31, 'respuesta' => 'D'],
            ['pregunta' => 32, 'respuesta' => 'A'],
            ['pregunta' => 33, 'respuesta' => 'A'],
            ['pregunta' => 34, 'respuesta' => 'B'],
            ['pregunta' => 35, 'respuesta' => 'A'],
            ['pregunta' => 36, 'respuesta' => 'A'],
            ['pregunta' => 37, 'respuesta' => 'C'],
            ['pregunta' => 38, 'respuesta' => 'D'],
            ['pregunta' => 39, 'respuesta' => 'A'],
            ['pregunta' => 40, 'respuesta' => 'B'],
            ['pregunta' => 41, 'respuesta' => 'A'],
            ['pregunta' => 42, 'respuesta' => 'C'],
            ['pregunta' => 43, 'respuesta' => 'B'],
            ['pregunta' => 44, 'respuesta' => 'A'],
            ['pregunta' => 45, 'respuesta' => 'B'],
            ['pregunta' => 46, 'respuesta' => 'A'],
            ['pregunta' => 47, 'respuesta' => 'A'],
            ['pregunta' => 48, 'respuesta' => 'A'],
            ['pregunta' => 49, 'respuesta' => 'A'],
            ['pregunta' => 50, 'respuesta' => 'C'],
            ['pregunta' => 51, 'respuesta' => 'D'],
            ['pregunta' => 52, 'respuesta' => 'D'],
            ['pregunta' => 53, 'respuesta' => 'B'],
            ['pregunta' => 54, 'respuesta' => 'B'],
            ['pregunta' => 55, 'respuesta' => 'A'],
            ['pregunta' => 56, 'respuesta' => 'A'],
            ['pregunta' => 57, 'respuesta' => 'C'],
            ['pregunta' => 58, 'respuesta' => 'D'],
            ['pregunta' => 59, 'respuesta' => 'E'],
            ['pregunta' => 60, 'respuesta' => 'E'],
            ['pregunta' => 61, 'respuesta' => 'E'],
            ['pregunta' => 62, 'respuesta' => 'C'],
            ['pregunta' => 63, 'respuesta' => 'E'],
            ['pregunta' => 64, 'respuesta' => 'E'],
            ['pregunta' => 65, 'respuesta' => ''],
            ['pregunta' => 66, 'respuesta' => ''],
            ['pregunta' => 67, 'respuesta' => ''],
            ['pregunta' => 68, 'respuesta' => ''],
            ['pregunta' => 69, 'respuesta' => ''],
            ['pregunta' => 70, 'respuesta' => ''],
            ['pregunta' => 71, 'respuesta' => ''],
        ];

        Assessee::create([
            'folio' => '121470092',
            'empresa' => 'empresa 1',
            'datos' => json_encode($questions), // Guardar preguntas como array
        ]);
    }
}
