<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Native\Desktop\Facades\ChildProcess;
use Native\Desktop\Facades\Clipboard;
use Native\Desktop\Facades\Notification;

class TranscriptionService
{
    public function __construct()
    {
        $this->ensureDirectoriesExist();
    }

    /**
     * Transcribe an audio file to text using whisper-cpp CLI.
     */
    public function transcribe(string $audioPath): string
    {
        $model = config('wisper.model', 'base.en');
        $modelsPath = config('wisper.models_path');
        $modelFile = "{$modelsPath}/ggml-{$model}.bin";
        $language = config('wisper.language', 'en');

        Log::info("Transcribing with whisper-cpp: {$audioPath}");

        // Convert audio to 16kHz WAV format required by whisper-cpp
        $wavPath = $this->convertToWav($audioPath);

        // Build whisper-cli command with environment variables for GPU acceleration
        $whisperPath = $this->getWhisperPath();
        $envPrefix = $this->getWhisperEnvPrefix();

        $command = sprintf(
            '%s"%s" --model "%s" --language %s --output-txt "%s" 2>&1',
            $envPrefix,
            $whisperPath,
            $modelFile,
            $language,
            $wavPath
        );

        Log::info("Running command: {$command}");

        $result = Process::timeout(120)->run($command);

        // Clean up temp wav file
        if (file_exists($wavPath)) {
            unlink($wavPath);
        }

        if (! $result->successful()) {
            Log::error("Whisper transcription failed: {$result->errorOutput()}");
            throw new \RuntimeException('Transcription failed: '.$result->errorOutput());
        }

        // Read the output txt file
        $txtPath = $wavPath.'.txt';
        if (file_exists($txtPath)) {
            $text = trim(file_get_contents($txtPath));
            unlink($txtPath);

            Log::info("Transcription result: {$text}");

            return $text;
        }

        // Fallback: parse stdout for transcription
        $output = $result->output();
        Log::info("Whisper stdout: {$output}");

        // Extract text from whisper output (lines starting with timestamps)
        $lines = explode("\n", $output);
        $text = '';
        foreach ($lines as $line) {
            // Match lines like: [00:00:00.000 --> 00:00:02.000]   Hello world
            if (preg_match('/\[\d{2}:\d{2}:\d{2}\.\d{3}\s*-->\s*\d{2}:\d{2}:\d{2}\.\d{3}\]\s*(.+)/', $line, $matches)) {
                $text .= ' '.$matches[1];
            }
        }

        return trim($text);
    }

    /**
     * Convert audio file to 16kHz WAV format required by whisper-cpp.
     */
    private function convertToWav(string $audioPath): string
    {
        Log::info("Converting audio file: {$audioPath}");
        Log::info('Source file exists: '.(file_exists($audioPath) ? 'yes' : 'no'));

        if (! file_exists($audioPath)) {
            throw new \RuntimeException("Source audio file not found: {$audioPath}");
        }

        $fileSize = filesize($audioPath);
        Log::info("Source file size: {$fileSize} bytes");

        if ($fileSize < 1000) {
            throw new \RuntimeException("Audio file too small ({$fileSize} bytes). Recording may have failed.");
        }

        $wavPath = storage_path('app/recordings/temp_'.time().'.wav');
        $ffmpegPath = $this->getFfmpegPath();

        // Use -err_detect ignore_err to handle potentially incomplete webm files
        // -acodec copy first attempt might fail, so we use a more robust approach
        $command = sprintf(
            '"%s" -y -err_detect ignore_err -i "%s" -ar 16000 -ac 1 -c:a pcm_s16le "%s" 2>&1',
            $ffmpegPath,
            $audioPath,
            $wavPath
        );

        Log::info("FFmpeg command: {$command}");

        $result = Process::timeout(60)->run($command);

        Log::info("FFmpeg exit code: {$result->exitCode()}");
        Log::info("FFmpeg stdout: {$result->output()}");
        Log::info("FFmpeg stderr: {$result->errorOutput()}");

        if (! $result->successful()) {
            $error = $result->errorOutput() ?: $result->output();
            Log::error("FFmpeg conversion failed: {$error}");
            throw new \RuntimeException('Audio conversion failed: '.$error);
        }

        if (! file_exists($wavPath)) {
            throw new \RuntimeException("WAV file was not created: {$wavPath}");
        }

        Log::info("WAV file created: {$wavPath}");

        return $wavPath;
    }

    /**
     * Get the path to the ffmpeg binary.
     */
    private function getFfmpegPath(): string
    {
        $configPath = config('wisper.ffmpeg_path', 'ffmpeg');

        // If it's already an absolute path, use it
        if ($this->isAbsolutePath($configPath)) {
            return $configPath;
        }

        // Try to find ffmpeg in common locations
        $possiblePaths = $this->getOsPaths('ffmpeg');
        foreach ($possiblePaths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }

        // Fall back to PATH lookup
        return $configPath;
    }

    /**
     * Get the path to the whisper-cli binary.
     */
    private function getWhisperPath(): string
    {
        $configPath = config('wisper.whisper_path', 'whisper-cli');

        // If it's already an absolute path, use it
        if ($this->isAbsolutePath($configPath)) {
            return $configPath;
        }

        // Try to find whisper-cli in common locations
        $possiblePaths = $this->getOsPaths('whisper-cli');
        foreach ($possiblePaths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }

        // Fall back to PATH lookup
        return $configPath;
    }

    /**
     * Get environment variable prefix for whisper command (Metal acceleration on macOS).
     */
    private function getWhisperEnvPrefix(): string
    {
        if (PHP_OS_FAMILY !== 'Darwin') {
            return '';
        }

        // Try to find Metal resources path for GPU acceleration on macOS
        $metalPath = trim(shell_exec('brew --prefix whisper-cpp 2>/dev/null') ?? '');
        if ($metalPath && is_dir("{$metalPath}/share/whisper-cpp")) {
            return "GGML_METAL_PATH_RESOURCES=\"{$metalPath}/share/whisper-cpp\" ";
        }

        return '';
    }

    /**
     * Get possible paths for a binary based on OS.
     *
     * @return array<string>
     */
    private function getOsPaths(string $binary): array
    {
        $paths = [];

        if (PHP_OS_FAMILY === 'Darwin') {
            // macOS paths (Apple Silicon and Intel)
            $paths = [
                "/opt/homebrew/bin/{$binary}",      // Apple Silicon Homebrew
                "/usr/local/bin/{$binary}",          // Intel Homebrew
                "/usr/bin/{$binary}",
            ];
        } elseif (PHP_OS_FAMILY === 'Linux') {
            // Linux paths
            $paths = [
                "/usr/bin/{$binary}",
                "/usr/local/bin/{$binary}",
                "/snap/bin/{$binary}",
            ];
        } elseif (PHP_OS_FAMILY === 'Windows') {
            // Windows paths
            $programFiles = getenv('ProgramFiles') ?: 'C:\\Program Files';
            $localAppData = getenv('LOCALAPPDATA') ?: 'C:\\Users\\'.getenv('USERNAME').'\\AppData\\Local';
            $paths = [
                "{$programFiles}\\{$binary}\\{$binary}.exe",
                "{$localAppData}\\{$binary}\\{$binary}.exe",
                "C:\\{$binary}\\{$binary}.exe",
            ];
        }

        return $paths;
    }

    /**
     * Check if a path is absolute.
     */
    private function isAbsolutePath(string $path): bool
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1;
        }

        return str_starts_with($path, '/');
    }

    /**
     * Set the transcribed text to clipboard and optionally paste it.
     */
    public function setClipboardAndPaste(string $text): void
    {
        Clipboard::text($text);

        // Check cached user settings (fallback to config)
        $notificationsEnabled = cache()->get('wisper_notifications', config('wisper.notifications_enabled', true));
        $autoPasteEnabled = cache()->get('wisper_auto_paste', config('wisper.auto_paste', true));

        // Show notification that text is ready (if enabled)
        if ($notificationsEnabled) {
            $preview = strlen($text) > 50 ? substr($text, 0, 50).'...' : $text;
            Notification::title('Text Copied!')
                ->message($preview)
                ->show();
        }

        if ($autoPasteEnabled) {
            $this->typeText($text);
        }
    }

    /**
     * Type text directly using OS-specific methods.
     * This is more reliable than simulating Cmd+V paste.
     */
    private function typeText(string $text): void
    {
        if (PHP_OS_FAMILY === 'Darwin') {
            // macOS: Use AppleScript to type text directly
            // Escape only double quotes for AppleScript string (backslash-quote)
            $escapedText = str_replace('\\', '\\\\', $text);
            $escapedText = str_replace('"', '\\"', $escapedText);
            $escapedText = str_replace(["\n", "\r"], ' ', $escapedText);

            // Write AppleScript to temp file to avoid all shell escaping issues
            $scriptContent = "delay 0.5\ntell application \"System Events\" to keystroke \"{$escapedText}\"";
            $tempFile = '/tmp/larawisper_type_'.time().'.scpt';
            file_put_contents($tempFile, $scriptContent);

            Log::info("Typing text via AppleScript file: {$tempFile}");

            // Run osascript with the file (temp files in /tmp are auto-cleaned)
            ChildProcess::start(
                cmd: "/usr/bin/osascript {$tempFile}",
                alias: 'type-'.time()
            );
        } elseif (PHP_OS_FAMILY === 'Linux') {
            // Linux: Use xdotool to type text via bash script
            // ChildProcess doesn't support && chaining, so we write a temp script
            $escapedText = str_replace("'", "'\\''", $text); // Escape single quotes for bash
            $escapedText = str_replace(["\n", "\r"], ' ', $escapedText);

            $scriptContent = "#!/bin/bash\nsleep 0.5\nxdotool type --clearmodifiers '{$escapedText}'";
            $tempFile = '/tmp/larawisper_type_'.time().'.sh';
            file_put_contents($tempFile, $scriptContent);
            chmod($tempFile, 0755);

            Log::info("Typing text via xdotool script: {$tempFile}");

            ChildProcess::start(
                cmd: "/bin/bash {$tempFile}",
                alias: 'type-'.time()
            );
        } elseif (PHP_OS_FAMILY === 'Windows') {
            // Windows: Use PowerShell script to send keys
            // ChildProcess doesn't support complex commands, so we write a temp script
            $escapedText = str_replace(['{', '}', '(', ')', '+', '^', '%', '~', '[', ']'],
                ['{{}', '{}}', '{(}', '{)}', '{+}', '{^}', '{%}', '{~}', '{[}', '{]}'], $text);
            $escapedText = str_replace(["\n", "\r"], ' ', $escapedText);

            $scriptContent = "Start-Sleep -Milliseconds 500\nAdd-Type -AssemblyName System.Windows.Forms\n[System.Windows.Forms.SendKeys]::SendWait(\"{$escapedText}\")";
            $tempFile = sys_get_temp_dir().'\\larawisper_type_'.time().'.ps1';
            file_put_contents($tempFile, $scriptContent);

            Log::info("Typing text via PowerShell script: {$tempFile}");

            ChildProcess::start(
                cmd: "powershell -ExecutionPolicy Bypass -File \"{$tempFile}\"",
                alias: 'type-'.time()
            );
        }
    }

    /**
     * Ensure required directories exist.
     */
    private function ensureDirectoriesExist(): void
    {
        $directories = [
            config('wisper.models_path'),
            config('wisper.recordings_path'),
        ];

        foreach ($directories as $directory) {
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
        }
    }

    /**
     * Check if the Whisper model is downloaded.
     */
    public function isModelDownloaded(): bool
    {
        $model = config('wisper.model', 'base.en');
        $modelsPath = config('wisper.models_path');

        $modelFile = "{$modelsPath}/ggml-{$model}.bin";

        return file_exists($modelFile);
    }

    /**
     * Get the configured model name.
     */
    public function getModelName(): string
    {
        return config('wisper.model', 'base.en');
    }
}
