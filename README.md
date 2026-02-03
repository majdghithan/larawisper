# Larawisper
<img width="333" height="538" alt="larawisper3" src="https://github.com/user-attachments/assets/67f1755f-d9a2-48ae-94fd-e78a3fdc1a7c" />
<img width="333" height="540" alt="larawisper2" src="https://github.com/user-attachments/assets/09337f79-a08e-4334-9a56-2ead885feed6" />
<img width="327" height="534" alt="larawisper1" src="https://github.com/user-attachments/assets/e3c664fd-e2c3-48fe-957d-eea7c52df001" />
<img width="274" height="63" alt="larawisper5" src="https://github.com/user-attachments/assets/d2011f33-05b8-43a5-9c32-8cc7119a3453" />
<img width="267" height="66" alt="larawisper4" src="https://github.com/user-attachments/assets/c165fbb8-3fbb-481b-89dd-d334cfccab01" />

A cross-platform voice-to-text desktop application built with [Laravel](https://laravel.com), [NativePHP](https://nativephp.com), and [whisper.cpp](https://github.com/ggerganov/whisper.cpp). Record your voice, transcribe it locally using OpenAI's Whisper model, and automatically type the text wherever your cursor is.

## Features

- **Menu bar application** - Runs quietly in your system tray/menu bar
- **Global keyboard shortcut** - Toggle recording from anywhere (configurable, default: `Alt+W`)
- **Floating recorder window** - Shows audio visualization while recording
- **Local transcription** - Uses whisper.cpp for 100% offline, private transcription
- **Auto-type** - Transcribed text is automatically typed at your cursor position
- **Settings panel** - Toggle notifications, auto-paste, and floating window
- **GPU acceleration** - Utilizes Metal on macOS for fast transcription
- **Cross-platform** - Works on macOS, Linux, and Windows

## Requirements

- PHP 8.2+
- Node.js 18+
- Composer
- FFmpeg (for audio conversion)
- whisper.cpp (for transcription)

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/yourusername/larawisper.git
cd larawisper
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Install Node Dependencies

```bash
npm install
```

### 4. Install System Dependencies

Choose your operating system:

#### macOS (Homebrew)

```bash
# Install FFmpeg and whisper.cpp
brew install ffmpeg whisper-cpp

# Verify installation
ffmpeg -version
whisper-cli --help
```

#### Linux (Ubuntu/Debian)

```bash
# Install FFmpeg
sudo apt update
sudo apt install ffmpeg

# Install whisper.cpp from source
git clone https://github.com/ggerganov/whisper.cpp.git
cd whisper.cpp
make
sudo cp main /usr/local/bin/whisper-cli
cd ..

# For auto-paste functionality, install xdotool (X11) or wtype (Wayland)
sudo apt install xdotool  # For X11
# OR
sudo apt install wtype    # For Wayland
```

#### Windows

1. **Install FFmpeg:**
   - Download from [ffmpeg.org](https://ffmpeg.org/download.html)
   - Extract to `C:\ffmpeg`
   - Add `C:\ffmpeg\bin` to your PATH environment variable

2. **Install whisper.cpp:**
   - Download pre-built binaries from [whisper.cpp releases](https://github.com/ggerganov/whisper.cpp/releases)
   - Extract to `C:\whisper-cpp`
   - Add to PATH or set `WISPER_WHISPER_PATH` in `.env`

3. **Verify installation:**
   ```powershell
   ffmpeg -version
   whisper-cli --help
   ```

### 5. Download Whisper Model

Download a Whisper model file to `storage/app/models/` (for development) or `extras/models/` (for production builds):

```bash
# Create models directory
mkdir -p storage/app/models
mkdir -p extras/models

# Download base.en model (recommended, ~150MB)
curl -L -o storage/app/models/ggml-base.en.bin \
  "https://huggingface.co/ggerganov/whisper.cpp/resolve/main/ggml-base.en.bin"

# For production builds, also copy to extras (bundled with app)
cp storage/app/models/ggml-base.en.bin extras/models/
```

> **Note:** Model files are not included in git due to size (~150MB). You must download them after cloning.

**Available models:**

| Model | Size | Description |
|-------|------|-------------|
| `tiny.en` | ~75MB | Fastest, lower accuracy |
| `base.en` | ~150MB | Recommended balance |
| `small.en` | ~500MB | Better accuracy |
| `medium.en` | ~1.5GB | High accuracy |
| `large` | ~3GB | Best accuracy, slowest |

### 6. Configure Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` if needed:

```env
# Optional: Custom binary paths (if not in PATH)
WISPER_FFMPEG_PATH=/path/to/ffmpeg
WISPER_WHISPER_PATH=/path/to/whisper-cli

# Optional: Change the model
WISPER_MODEL=base.en

# Optional: Change the keyboard shortcut
WISPER_SHORTCUT=Ctrl+Shift+Space

# Optional: Disable auto-paste
WISPER_AUTO_PASTE=true
```

### 7. Build Frontend Assets

```bash
npm run build
```

### 8. Run the Application

```bash
php artisan native:serve
```

## Usage

1. **Start the app** - Look for the Wisper icon in your menu bar/system tray
2. **Click the icon** or press `Ctrl+Shift+Space` to start recording
3. **Speak** your message
4. **Click again** or press the shortcut to stop recording
5. **Wait** for transcription (usually 2-5 seconds)
6. **Text is automatically pasted** at your cursor position

## Configuration

All configuration options are in `config/wisper.php`:

| Option | Default | Description |
|--------|---------|-------------|
| `model` | `base.en` | Whisper model to use |
| `shortcut` | `Ctrl+Shift+Space` | Global keyboard shortcut |
| `language` | `en` | Transcription language |
| `auto_paste` | `true` | Auto-paste after transcription |
| `max_recording_seconds` | `60` | Maximum recording duration |
| `ffmpeg_path` | `ffmpeg` | Path to FFmpeg binary |
| `whisper_path` | `whisper-cli` | Path to whisper-cli binary |

## System Permissions

### macOS

- **Microphone**: Automatically prompted on first use
- **Accessibility**: Required for auto-paste. Go to:
  `System Settings > Privacy & Security > Accessibility` and enable the app

### Linux

- Ensure your user has access to the microphone
- For auto-paste: `xdotool` (X11) or `wtype` (Wayland) must be installed

### Windows

- Microphone permission will be requested on first use
- Run as administrator if auto-paste doesn't work

## Troubleshooting

### "Audio conversion failed"

- Ensure FFmpeg is installed and accessible
- Check the path: `which ffmpeg` (macOS/Linux) or `where ffmpeg` (Windows)
- Set explicit path in `.env`: `WISPER_FFMPEG_PATH=/full/path/to/ffmpeg`

### "Transcription failed"

- Ensure whisper-cli is installed and accessible
- Verify the model file exists in `storage/app/models/`
- Check logs: `cat storage/logs/laravel.log | tail -50`

### Shortcut not working

- Try a different shortcut in `.env`: `WISPER_SHORTCUT=Alt+Space`
- On macOS, some shortcuts may conflict with system functions

### Auto-paste not working

- **macOS**: Grant Accessibility permission in System Settings
- **Linux**: Install `xdotool` or `wtype`
- **Windows**: Try running as administrator

## Development

```bash
# Run in development mode with hot reload
npm run dev

# In another terminal
php artisan native:serve

# Run tests
php artisan test

# Format code
vendor/bin/pint
```

## Building for Distribution

```bash
# Build for current platform
php artisan native:build

# Build for specific platform
php artisan native:build --os=mac
php artisan native:build --os=linux
php artisan native:build --os=win
```

## Tech Stack

- **Backend**: Laravel 12, PHP 8.3
- **Desktop**: NativePHP Desktop v2 (Electron)
- **Transcription**: whisper.cpp (local, offline)
- **Audio**: Web MediaRecorder API
- **Styling**: Tailwind CSS v4

---

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                        Electron Shell                            │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────────┐  │
│  │  Menu Bar   │  │  Floating   │  │    Global Shortcut      │  │
│  │   Window    │  │   Window    │  │      Listener           │  │
│  └──────┬──────┘  └──────┬──────┘  └───────────┬─────────────┘  │
└─────────┼────────────────┼──────────────────────┼────────────────┘
          │                │                      │
          ▼                ▼                      ▼
┌─────────────────────────────────────────────────────────────────┐
│                     Laravel Backend (PHP)                        │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐  │
│  │  Recording      │  │  Transcription  │  │  Event          │  │
│  │  Controller     │  │  Service        │  │  Broadcasting   │  │
│  └────────┬────────┘  └────────┬────────┘  └────────┬────────┘  │
└───────────┼────────────────────┼─────────────────────┼───────────┘
            │                    │                     │
            ▼                    ▼                     ▼
┌─────────────────┐  ┌─────────────────┐  ┌─────────────────────┐
│   Web Audio     │  │   whisper-cpp   │  │   ChildProcess      │
│   Recording     │  │   (Local AI)    │  │   (AppleScript/     │
│   (Browser)     │  │                 │  │    xdotool/PS)      │
└─────────────────┘  └─────────────────┘  └─────────────────────┘
```

---

## How It Works (Step-by-Step)

This section explains the complete flow from application boot to text being typed.

### 1. Application Boot

When you run `php artisan native:serve`, NativePHP starts an Electron shell that runs a Laravel server. The entry point is `NativeAppServiceProvider::boot()`:

**File**: `app/Providers/NativeAppServiceProvider.php`

```php
public function boot(): void
{
    // 1. Create menu bar (system tray) with context menu
    MenuBar::create()
        ->route('recording')              // Loads recording.blade.php
        ->withContextMenu(Menu::make(...)); // Right-click shows Quit option

    // 2. Create floating window (transparent, always-on-top)
    Window::open('floating-recorder')
        ->route('floating-recorder')      // Loads floating-recorder.blade.php
        ->alwaysOnTop()
        ->frameless()
        ->transparent();

    // 3. Register global keyboard shortcut
    GlobalShortcut::key('Alt+W')          // Works even when app is not focused
        ->event(RecordingToggled::class)  // Dispatches this event
        ->register();
}
```

### 2. User Presses Global Shortcut (Alt+W)

When the shortcut is pressed anywhere on the system:

1. Electron detects the key combination
2. NativePHP dispatches the `RecordingToggled` event
3. The event broadcasts on the `nativephp` channel
4. Frontend JavaScript receives it via `Native.on()`

**File**: `app/Events/RecordingToggled.php`

```php
class RecordingToggled implements ShouldBroadcastNow
{
    public function broadcastOn(): array
    {
        return [new Channel('nativephp')];
    }
}
```

**File**: `resources/views/recording.blade.php` (JavaScript)

```javascript
// Wait for NativePHP to be ready
window.addEventListener('native:init', function() {
    // Listen for the shortcut event
    Native.on('App\\Events\\RecordingToggled', function() {
        toggleRecording();  // Start or stop recording
    });
});
```

### 3. Recording Starts

The `toggleRecording()` function uses the Web MediaRecorder API:

```javascript
async function toggleRecording() {
    if (!isRecording) {
        // Request microphone access
        stream = await navigator.mediaDevices.getUserMedia({ audio: true });

        // Create MediaRecorder with WebM format
        mediaRecorder = new MediaRecorder(stream, { mimeType: 'audio/webm' });
        mediaRecorder.start();
        isRecording = true;

        // Notify backend to update UI state
        fetch('/api/recording-state', {
            method: 'POST',
            body: JSON.stringify({ recording: true, state: 'recording' })
        });
    }
}
```

### 4. Recording State Updates

The backend receives the state update and broadcasts it:

**File**: `app/Http/Controllers/TranscriptionController.php`

```php
public function setRecordingState(Request $request): JsonResponse
{
    $state = $request->input('state');  // 'recording', 'processing', or 'idle'

    // Store in cache for polling fallback
    cache()->put('wisper_recording_state', $state, 300);

    // Update menu bar icon
    MenuBar::label($isRecording ? '🔴' : '');

    // Broadcast state to floating window
    FloatingWindowState::dispatch($state);

    return response()->json(['success' => true]);
}
```

**File**: `app/Events/FloatingWindowState.php`

```php
class FloatingWindowState implements ShouldBroadcastNow
{
    public function __construct(public string $state) {}

    public function broadcastOn(): array
    {
        return [new Channel('nativephp')];
    }
}
```

The floating window receives the state two ways:

1. **Event Broadcasting** (primary):
   ```javascript
   Native.on('App\\Events\\FloatingWindowState', function(payload) {
       setState(payload.state);  // Update UI
   });
   ```

2. **HTTP Polling** (fallback, every 200ms):
   ```javascript
   setInterval(async function() {
       const response = await fetch('/api/recording-state');
       const data = await response.json();
       if (data.state !== currentState) {
           setState(data.state);
       }
   }, 200);
   ```

### 5. User Stops Recording (Presses Shortcut Again)

```javascript
function toggleRecording() {
    if (isRecording) {
        mediaRecorder.stop();  // Triggers ondataavailable event

        mediaRecorder.ondataavailable = async (event) => {
            // Create blob from recorded audio
            const audioBlob = new Blob([event.data], { type: 'audio/webm' });

            // Update state to "processing"
            await fetch('/api/recording-state', {
                body: JSON.stringify({ state: 'processing' })
            });

            // Send audio to backend for transcription
            const formData = new FormData();
            formData.append('audio', audioBlob, 'recording.webm');

            const response = await fetch('/api/transcribe', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            // Text is now typed at cursor (handled by backend)
        };
    }
}
```

### 6. Transcription Process

**File**: `app/Http/Controllers/TranscriptionController.php`

```php
public function transcribe(Request $request): JsonResponse
{
    // Save uploaded audio file
    $audioPath = $request->file('audio')->store('recordings');

    // Transcribe using Whisper
    $text = $this->transcriptionService->transcribe($audioPath);

    // Copy to clipboard and auto-type
    $this->transcriptionService->setClipboardAndPaste($text);

    return response()->json(['success' => true, 'text' => $text]);
}
```

**File**: `app/Services/TranscriptionService.php`

```php
public function transcribe(string $audioPath): string
{
    // Step 1: Convert WebM to 16kHz WAV (required by Whisper)
    $wavPath = $this->convertToWav($audioPath);
    // Command: ffmpeg -i input.webm -ar 16000 -ac 1 output.wav

    // Step 2: Run whisper-cpp CLI
    $command = sprintf(
        '%s --model "%s" --language %s "%s"',
        $this->getWhisperPath(),      // /opt/homebrew/bin/whisper-cli
        $this->getModelPath(),         // storage/app/models/ggml-base.en.bin
        config('wisper.language'),     // 'en'
        $wavPath
    );

    Process::timeout(120)->run($command);

    // Step 3: Read transcription from output file
    $text = file_get_contents($wavPath . '.txt');

    return trim($text);
}
```

### 7. Auto-Type the Result

After transcription, the text is typed at the cursor position:

```php
public function setClipboardAndPaste(string $text): void
{
    // Copy to system clipboard
    Clipboard::text($text);

    // Show notification (if enabled)
    if (config('wisper.notifications_enabled')) {
        Notification::title('Text Copied!')->message($text)->show();
    }

    // Auto-type at cursor (if enabled)
    if (config('wisper.auto_paste')) {
        $this->typeText($text);
    }
}
```

The `typeText()` method uses OS-specific approaches because `ChildProcess` doesn't support command chaining (`&&` or `;`):

**macOS (AppleScript)**:
```php
// Write script to temp file
$script = "delay 0.5\ntell application \"System Events\" to keystroke \"$text\"";
file_put_contents('/tmp/larawisper_type.scpt', $script);

// Execute with osascript
ChildProcess::start(cmd: '/usr/bin/osascript /tmp/larawisper_type.scpt');
```

**Linux (xdotool)**:
```php
// Write bash script
$script = "#!/bin/bash\nsleep 0.5\nxdotool type --clearmodifiers '$text'";
file_put_contents('/tmp/larawisper_type.sh', $script);
chmod('/tmp/larawisper_type.sh', 0755);

ChildProcess::start(cmd: '/bin/bash /tmp/larawisper_type.sh');
```

**Windows (PowerShell)**:
```php
// Write PowerShell script
$script = "Start-Sleep -Milliseconds 500\n";
$script .= "Add-Type -AssemblyName System.Windows.Forms\n";
$script .= "[System.Windows.Forms.SendKeys]::SendWait(\"$text\")";
file_put_contents('C:\temp\larawisper_type.ps1', $script);

ChildProcess::start(cmd: 'powershell -ExecutionPolicy Bypass -File C:\temp\larawisper_type.ps1');
```

### 8. State Returns to Idle

After typing completes, the state is set back to idle:

```javascript
// After transcription completes
await fetch('/api/recording-state', {
    body: JSON.stringify({ state: 'idle' })
});
```

The floating window fades out and the menu bar icon clears.

---

## Project Structure

```
larawisper/
├── app/
│   ├── Events/
│   │   ├── RecordingToggled.php       # Dispatched on global shortcut
│   │   └── FloatingWindowState.php    # Updates floating window UI
│   ├── Http/Controllers/
│   │   └── TranscriptionController.php # API endpoints
│   ├── Providers/
│   │   └── NativeAppServiceProvider.php # NativePHP boot configuration
│   └── Services/
│       └── TranscriptionService.php    # Whisper integration & auto-type
├── config/
│   └── wisper.php                      # App configuration
├── resources/
│   ├── js/
│   │   └── audio-recorder.js           # Shared recording utilities
│   └── views/
│       ├── recording.blade.php         # Menu bar window UI
│       └── floating-recorder.blade.php # Floating indicator UI
├── routes/
│   └── web.php                         # API routes
├── storage/app/
│   ├── models/                         # Whisper model files (.bin)
│   └── recordings/                     # Temporary audio files
└── docs/
    └── nativephp-reference.md          # NativePHP API reference
```

---

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/transcribe` | Upload audio file, returns transcription |
| `GET` | `/api/status` | Check if Whisper model is ready |
| `POST` | `/api/recording-state` | Update recording state (recording/processing/idle) |
| `GET` | `/api/recording-state` | Get current state (for polling) |
| `POST` | `/api/settings` | Update user preferences |
| `POST` | `/api/open-accessibility-settings` | Open macOS Accessibility settings |

---

## Key Files Reference

| File | Purpose |
|------|---------|
| `NativeAppServiceProvider.php` | Boot configuration: menu bar, windows, shortcuts |
| `TranscriptionController.php` | API endpoints for recording and transcription |
| `TranscriptionService.php` | Audio conversion, Whisper CLI, auto-typing |
| `RecordingToggled.php` | Event dispatched when shortcut is pressed |
| `FloatingWindowState.php` | Event to update floating window state |
| `recording.blade.php` | Main menu bar window with settings |
| `floating-recorder.blade.php` | Floating indicator with waveform visualization |
| `config/wisper.php` | All configurable options |

---

## License

MIT License - see [LICENSE](LICENSE) for details.

## Credits

- [OpenAI Whisper](https://github.com/openai/whisper) - Speech recognition model
- [whisper.cpp](https://github.com/ggerganov/whisper.cpp) - C++ port of Whisper
- [NativePHP](https://nativephp.com) - Desktop app framework for Laravel
- [FFmpeg](https://ffmpeg.org) - Audio processing
