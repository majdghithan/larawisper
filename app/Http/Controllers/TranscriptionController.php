<?php

namespace App\Http\Controllers;

use App\Events\FloatingWindowState;
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
     * Update the recording state (updates menu bar label and floating window).
     */
    public function setRecordingState(Request $request): JsonResponse
    {
        $request->validate([
            'recording' => 'required|boolean',
            'state' => 'sometimes|string', // idle, recording, processing
        ]);

        $isRecording = $request->boolean('recording');
        $state = $request->input('state', $isRecording ? 'recording' : 'idle');

        // Store current state in cache for polling fallback
        cache()->put('wisper_recording_state', $state, 300); // 5 minutes TTL

        // Update menu bar label
        if ($isRecording) {
            MenuBar::label('🔴');
        } else {
            MenuBar::label('');
        }

        // Update floating window state via event (window handles its own visibility)
        // Check cached setting first, fall back to config
        $floatingWindowEnabled = cache()->get('wisper_floating_window', config('wisper.floating_window', true));
        if ($floatingWindowEnabled) {
            FloatingWindowState::dispatch($state);
        }

        Log::info("Recording state updated: {$state}");

        return response()->json(['success' => true, 'state' => $state]);
    }

    /**
     * Get the current recording state (for polling fallback).
     */
    public function getRecordingState(): JsonResponse
    {
        $state = cache()->get('wisper_recording_state', 'idle');

        return response()->json(['state' => $state]);
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

    /**
     * Update user settings (stored in session/cache for this session).
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $request->validate([
            'notifications' => 'sometimes|boolean',
            'auto_paste' => 'sometimes|boolean',
            'floating_window' => 'sometimes|boolean',
        ]);

        // Store settings in cache (persists for this session)
        if ($request->has('notifications')) {
            cache()->put('wisper_notifications', $request->boolean('notifications'));
        }
        if ($request->has('auto_paste')) {
            cache()->put('wisper_auto_paste', $request->boolean('auto_paste'));
        }
        if ($request->has('floating_window')) {
            cache()->put('wisper_floating_window', $request->boolean('floating_window'));
        }

        return response()->json(['success' => true]);
    }
}
