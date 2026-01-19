<?php

namespace App\Http\Controllers;

use App\Services\TranscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Native\Desktop\Facades\MenuBar;
use Native\Desktop\Facades\Shell;

class TranscriptionController extends Controller
{
    public function __construct(
        private TranscriptionService $transcriptionService
    ) {}

    /**
     * Handle audio file upload and transcription.
     */
    public function transcribe(Request $request): JsonResponse
    {
        $request->validate([
            'audio' => 'required|file|max:25600', // 25MB max
        ]);

        Log::info('Received audio upload', [
            'original_name' => $request->file('audio')->getClientOriginalName(),
            'mime_type' => $request->file('audio')->getMimeType(),
            'size' => $request->file('audio')->getSize(),
        ]);

        // Get the recordings directory and ensure it exists
        $recordingsDir = storage_path('app/recordings');
        if (! is_dir($recordingsDir)) {
            mkdir($recordingsDir, 0755, true);
        }

        $filename = 'recording_'.time().'.'.$request->file('audio')->getClientOriginalExtension();
        $fullPath = $recordingsDir.'/'.$filename;

        // Move the uploaded file directly instead of using storeAs
        $request->file('audio')->move($recordingsDir, $filename);

        Log::info("File saved to: {$fullPath}");
        Log::info('File exists after save: '.(file_exists($fullPath) ? 'yes' : 'no'));

        if (! file_exists($fullPath)) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to save uploaded file',
            ], 500);
        }

        try {
            Log::info("Starting transcription for: {$fullPath}");

            $text = $this->transcriptionService->transcribe($fullPath);

            Log::info("Transcription completed: {$text}");

            $this->transcriptionService->setClipboardAndPaste($text);

            return response()->json([
                'success' => true,
                'text' => $text,
            ]);
        } catch (\Exception $e) {
            Log::error("Transcription failed: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        } finally {
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
    }

    /**
     * Check if the Whisper model is ready.
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'model' => $this->transcriptionService->getModelName(),
            'ready' => $this->transcriptionService->isModelDownloaded(),
            'shortcut' => config('wisper.shortcut'),
        ]);
    }

    /**
     * Update the recording state (updates menu bar label).
     */
    public function setRecordingState(Request $request): JsonResponse
    {
        $request->validate([
            'recording' => 'required|boolean',
        ]);

        $isRecording = $request->boolean('recording');

        if ($isRecording) {
            MenuBar::label('🔴');
        } else {
            MenuBar::label('');
        }

        return response()->json(['success' => true]);
    }

    /**
     * Open macOS Accessibility settings.
     */
    public function openAccessibilitySettings(): JsonResponse
    {
        if (PHP_OS_FAMILY === 'Darwin') {
            // Open Accessibility settings on macOS
            Shell::openExternal('x-apple.systempreferences:com.apple.preference.security?Privacy_Accessibility');
        }

        return response()->json(['success' => true]);
    }
}
