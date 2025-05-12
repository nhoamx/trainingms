<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FolioBatchController extends Controller
{
    /**
     * Almacena un nuevo lote de folios.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'quantity' => 'required|integer|min:1',
            'type' => 'required|in:presencial,en_linea',
        ]);

        // Calcular el siguiente número disponible para la organización
        $lastBatch = \App\Models\FolioBatch::where('organization_id', $request->organization_id)
                              ->orderBy('end_number', 'desc')
                              ->first();
                              
        $startNumber = 1; // Valor predeterminado para iniciar
        if ($lastBatch) {
            $startNumber = $lastBatch->end_number + 1;
        }
        
        $endNumber = $startNumber + $validated['quantity'] - 1;
        
        try {
            \Illuminate\Support\Facades\DB::beginTransaction();
            
            // Crear el lote
            $batch = \App\Models\FolioBatch::create([
                'organization_id' => $validated['organization_id'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'start_number' => $startNumber,
                'end_number' => $endNumber,
                'quantity' => $validated['quantity'],
                'type' => $validated['type'],
            ]);
            
            // Crear los folios individuales
            $folios = [];
            for ($i = $startNumber; $i <= $endNumber; $i++) {
                $folioNumber = str_pad($i, 4, '0', STR_PAD_LEFT); // Formato: 0001, 0002, etc.
                $folios[] = [
                    'folio_batch_id' => $batch->id,
                    'folio_number' => $folioNumber,
                    'numeric_value' => $i,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            
            // Inserción masiva para mejor rendimiento
            \App\Models\Folio::insert($folios);
            
            \Illuminate\Support\Facades\DB::commit();
            
            return redirect()->back()->with('success', 'Lote de folios creado exitosamente');
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return redirect()->back()->with('error', 'Error al crear el lote de folios: ' . $e->getMessage());
        }
    }

    /**
     * Obtiene los folios de un lote específico.
     *
     * @param  int  $batchId
     * @return \Illuminate\Http\Response
     */
    public function getFolios($batchId)
    {
        $batch = \App\Models\FolioBatch::findOrFail($batchId);
        $folios = $batch->folios()->orderBy('numeric_value')->get();
        
        return response()->json($folios);
    }

    /**
     * Elimina un lote de folios y todos sus folios asociados.
     *
     * @param  int  $batchId
     * @return \Illuminate\Http\Response
     */
    public function destroy($batchId)
    {
        $batch = \App\Models\FolioBatch::findOrFail($batchId);
        
        // Verificar si hay folios utilizados
        $usedCount = $batch->folios()->where('used', true)->count();
        if ($usedCount > 0) {
            return response()->json([
                'message' => 'No se puede eliminar el lote porque tiene folios que ya han sido utilizados.'
            ], 422);
        }
        
        try {
            \Illuminate\Support\Facades\DB::beginTransaction();
            
            // Eliminar primero los folios
            $batch->folios()->delete();
            
            // Eliminar el lote
            $batch->delete();
            
            \Illuminate\Support\Facades\DB::commit();
            
            return response()->json([
                'message' => 'Lote de folios eliminado correctamente'
            ]);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar el lote de folios',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
