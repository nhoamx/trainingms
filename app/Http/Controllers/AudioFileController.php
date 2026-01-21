<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadAudioFileRequest;
use App\Models\AudioFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AudioFileController extends Controller
{
    /**
     * Display audio files library
     */
    public function index()
    {
        $audioFiles = AudioFile::with('uploader:id,name')
            ->orderBy('question_type')
            ->orderBy('question_index')
            ->get()
            ->groupBy('question_type')
            ->map(function ($files) {
                return $files->map(function ($file) {
                    return [
                        'id' => $file->id,
                        'question_type' => $file->question_type,
                        'question_index' => $file->question_index,
                        'original_filename' => $file->original_filename,
                        'file_extension' => $file->file_extension,
                        'file_size' => $file->file_size,
                        'file_size_human' => $file->file_size_human,
                        'url' => $file->url,
                        'uploader_name' => $file->uploader?->name,
                        'created_at' => $file->created_at->format('Y-m-d H:i'),
                    ];
                });
            });

        return Inertia::render('Audio/Index', [
            'title' => 'Biblioteca de Audio',
            'audioFiles' => $audioFiles,
            'questionTypes' => config('audio.question_types'),
        ]);
    }

    /**
     * Show upload form
     */
    public function create()
    {
        return Inertia::render('Audio/Upload', [
            'title' => 'Subir Archivo de Audio',
            'questionTypes' => config('audio.question_types'),
            'supportedFormats' => config('audio.supported_formats'),
            'maxFileSize' => config('audio.max_file_size'),
        ]);
    }

    /**
     * Store uploaded audio file
     */
    public function store(UploadAudioFileRequest $request)
    {
        $validated = $request->validated();

        $audioFile = $request->file('audio_file');
        $questionType = $validated['question_type'];
        $questionIndex = $validated['question_index'];

        // Check if file already exists for this question
        $existing = AudioFile::where([
            'question_type' => $questionType,
            'question_index' => $questionIndex,
        ])->first();

        // Delete old file if exists
        if ($existing) {
            Storage::disk('public')->delete($existing->storage_path);
            $existing->delete();
        }

        // Get file extension
        $extension = $audioFile->getClientOriginalExtension();

        // Store file with numeric name: {question_index}.{extension}
        $filename = "{$questionIndex}.{$extension}";
        $directory = "audio/{$questionType}";
        $path = "{$directory}/{$filename}";

        // Store the file
        Storage::disk('public')->putFileAs($directory, $audioFile, $filename);

        // Save to database
        AudioFile::create([
            'question_type' => $questionType,
            'question_index' => $questionIndex,
            'original_filename' => $audioFile->getClientOriginalName(),
            'storage_path' => $path,
            'file_extension' => $extension,
            'mime_type' => $audioFile->getMimeType(),
            'file_size' => $audioFile->getSize(),
            'uploaded_by' => auth()->id(),
        ]);

        return back()->with('flash', [
            'type' => 'success',
            'title' => '¡Éxito!',
            'message' => "Audio subido correctamente para {$questionType} #{$questionIndex}",
        ]);
    }

    /**
     * Delete audio file
     */
    public function destroy(AudioFile $audioFile)
    {
        // Additional authorization check
        if (! auth()->user()->hasRole(['admin', 'super-admin'])) {
            abort(403, 'No tienes permisos para eliminar archivos de audio.');
        }

        // Delete file from storage
        Storage::disk('public')->delete($audioFile->storage_path);

        // Delete database record
        $audioFile->delete();

        return back()->with('flash', [
            'type' => 'success',
            'title' => '¡Éxito!',
            'message' => 'Audio eliminado correctamente',
        ]);
    }
}
