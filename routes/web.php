<?php

use App\Http\Controllers\TranscriptionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/recording', function () {
    return view('recording');
})->name('recording');

Route::get('/recording-indicator', function () {
    return view('recording-indicator');
})->name('recording-indicator');

Route::post('/api/transcribe', [TranscriptionController::class, 'transcribe'])->name('api.transcribe');
Route::get('/api/status', [TranscriptionController::class, 'status'])->name('api.status');
Route::post('/api/recording-state', [TranscriptionController::class, 'setRecordingState'])->name('api.recording-state');
Route::post('/api/open-accessibility-settings', [TranscriptionController::class, 'openAccessibilitySettings'])->name('api.open-accessibility-settings');
