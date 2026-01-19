# Larawisper

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

Download a Whisper model file to `storage/app/models/`:

```bash
# Create models directory
mkdir -p storage/app/models

# Download base.en model (recommended, ~150MB)
curl -L -o storage/app/models/ggml-base.en.bin \
  "https://huggingface.co/ggerganov/whisper.cpp/resolve/main/ggml-base.en.bin"
```

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

## License

MIT License - see [LICENSE](LICENSE) for details.

## Credits

- [OpenAI Whisper](https://github.com/openai/whisper) - Speech recognition model
- [whisper.cpp](https://github.com/ggerganov/whisper.cpp) - C++ port of Whisper
- [NativePHP](https://nativephp.com) - Desktop app framework for Laravel
- [FFmpeg](https://ffmpeg.org) - Audio processing
