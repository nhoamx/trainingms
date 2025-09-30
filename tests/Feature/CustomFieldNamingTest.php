<?php

namespace Tests\Feature;

use App\Http\Controllers\QuizController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

class CustomFieldNamingTest extends TestCase
{
    use RefreshDatabase;

    public function test_sanitize_field_name()
    {
        $controller = new QuizController;

        // Use reflection to access private method
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('sanitizeFieldName');
        $method->setAccessible(true);

        // Test cases for field name sanitization
        $testCases = [
            'Sucursal' => 'sucursal',
            'Ciudad de Origen' => 'ciudad_de_origen',
            'Número de Empleado' => 'numero_de_empleado',
            'Estado Civil' => 'estado_civil',
            'Fecha de Nacimiento' => 'fecha_de_nacimiento',
            'Área de Trabajo' => 'area_de_trabajo',
            'Código Postal' => 'codigo_postal',
        ];

        foreach ($testCases as $input => $expected) {
            $result = $method->invoke($controller, $input);
            $this->assertEquals($expected, $result, "Failed to sanitize '{$input}' to '{$expected}', got '{$result}'");
        }
    }
}
