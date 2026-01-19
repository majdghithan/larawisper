<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Whisper Model
    |--------------------------------------------------------------------------
    |
    | The Whisper model to use for transcription. Available models:
    | - tiny.en, tiny (fastest, ~75MB)
    | - base.en, base (recommended, ~150MB)
    | - small.en, small (~500MB)
    | - medium.en, medium (~1.5GB)
    | - large (~3GB, most accurate)
    |
    */
    'model' => env('WISPER_MODEL', 'base.en'),

    /*
    |--------------------------------------------------------------------------
    | Models Storage Path
    |--------------------------------------------------------------------------
    |
    | The directory where Whisper models will be downloaded and cached.
    |
    */
    'models_path' => storage_path('app/models'),

    /*
    |--------------------------------------------------------------------------
    | Recordings Storage Path
    |--------------------------------------------------------------------------
    |
    | The directory where temporary audio recordings will be stored.
    |
    */
    'recordings_path' => storage_path('app/recordings'),

    /*
    |--------------------------------------------------------------------------
    | Global Shortcut
    |--------------------------------------------------------------------------
    |
    | The keyboard shortcut to toggle recording. Uses Electron accelerator format.
    | Available modifiers: Cmd, Ctrl, CmdOrCtrl, Alt, Option, Shift, Super, Meta
    | Available keys: A-Z, 0-9, F1-F24, Space, Up, Down, Left, Right, etc.
    |
    | Examples: 'CmdOrCtrl+Shift+Space', 'Alt+R', 'F5'
    |
    */
    'shortcut' => env('WISPER_SHORTCUT', 'Alt+W'),

    /*
    |--------------------------------------------------------------------------
    | Maximum Recording Duration
    |--------------------------------------------------------------------------
    |
    | The maximum duration in seconds for a single recording.
    |
    */
    'max_recording_seconds' => env('WISPER_MAX_RECORDING', 60),

    /*
    |--------------------------------------------------------------------------
    | Language
    |--------------------------------------------------------------------------
    |
    | The language code for transcription. Use 'en' for English.
    | Set to null for auto-detection (requires non-english model).
    |
    */
    'language' => env('WISPER_LANGUAGE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Auto Paste
    |--------------------------------------------------------------------------
    |
    | Whether to automatically paste the transcribed text after transcription.
    |
    */
    'auto_paste' => env('WISPER_AUTO_PASTE', true),

    /*
    |--------------------------------------------------------------------------
    | Binary Paths
    |--------------------------------------------------------------------------
    |
    | Paths to external binaries. Set these if the binaries are not in your
    | system PATH or if you want to use specific versions.
    |
    | On macOS (Homebrew): /opt/homebrew/bin/ or /usr/local/bin/
    | On Linux: /usr/bin/ or /usr/local/bin/
    | On Windows: C:\path\to\bin\ (use forward slashes)
    |
    */
    'ffmpeg_path' => env('WISPER_FFMPEG_PATH', 'ffmpeg'),
    'whisper_path' => env('WISPER_WHISPER_PATH', 'whisper-cli'),
];
