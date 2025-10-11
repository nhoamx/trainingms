<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('razon_social')->nullable()->after('name');
            $table->string('rfc')->nullable()->after('razon_social');
            $table->string('registro_patronal')->nullable()->after('rfc');
            $table->string('calle_numero')->nullable()->after('registro_patronal');
            $table->string('colonia')->nullable()->after('calle_numero');
            $table->string('codigo_postal')->nullable()->after('colonia');
            $table->string('municipio')->nullable()->after('codigo_postal');
            $table->string('estado')->nullable()->after('municipio');
            $table->string('contacto_nombre')->nullable()->after('estado');
            $table->string('contacto_puesto')->nullable()->after('contacto_nombre');
            $table->string('contacto_email')->nullable()->after('contacto_puesto');
            $table->string('contacto_movil')->nullable()->after('contacto_email');
            $table->string('responsable_nombre')->nullable()->after('contacto_movil');
            $table->string('responsable_puesto')->nullable()->after('responsable_nombre');
            $table->string('responsable_email')->nullable()->after('responsable_puesto');
            $table->string('responsable_movil')->nullable()->after('responsable_email');
            $table->string('actividad_principal')->nullable()->after('responsable_movil');
            $table->integer('total_trabajadores')->nullable()->after('actividad_principal');
            $table->integer('total_hombres')->nullable()->after('total_trabajadores');
            $table->integer('total_mujeres')->nullable()->after('total_hombres');
            $table->integer('muestra_aplicada')->nullable()->after('total_mujeres');
            $table->integer('muestra_hombres')->nullable()->after('muestra_aplicada');
            $table->integer('muestra_mujeres')->nullable()->after('muestra_hombres');
            $table->integer('comite_integrantes')->nullable()->after('muestra_mujeres');
            $table->integer('comite_hombres')->nullable()->after('comite_integrantes');
            $table->integer('comite_mujeres')->nullable()->after('comite_hombres');
            $table->date('fecha_aplicacion')->nullable()->after('comite_mujeres');
            $table->text('justificacion_muestra')->nullable()->after('fecha_aplicacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'razon_social',
                'rfc',
                'registro_patronal',
                'calle_numero',
                'colonia',
                'codigo_postal',
                'municipio',
                'estado',
                'contacto_nombre',
                'contacto_puesto',
                'contacto_email',
                'contacto_movil',
                'responsable_nombre',
                'responsable_puesto',
                'responsable_email',
                'responsable_movil',
                'actividad_principal',
                'total_trabajadores',
                'total_hombres',
                'total_mujeres',
                'muestra_aplicada',
                'muestra_hombres',
                'muestra_mujeres',
                'comite_integrantes',
                'comite_hombres',
                'comite_mujeres',
                'fecha_aplicacion',
                'justificacion_muestra',
            ]);
        });
    }
};
