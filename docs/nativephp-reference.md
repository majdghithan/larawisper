# NativePHP Desktop v2 Reference

This document provides a comprehensive reference for NativePHP Desktop v2 APIs used in this application. It is intended to help future developers (and LLMs) understand the available functionality without needing to re-research the documentation.

> **Documentation**: https://nativephp.com/docs/desktop/2

---

## Table of Contents

- [Application Lifecycle](#application-lifecycle)
- [Global Shortcuts](#global-shortcuts)
- [Menu Bar](#menu-bar)
- [Clipboard](#clipboard)
- [Windows](#windows)
- [Child Processes](#child-processes)
- [Notifications](#notifications)
- [Broadcasting (PHP to Frontend)](#broadcasting-php-to-frontend)
- [Shell Operations](#shell-operations)

---

## Application Lifecycle

NativePHP follows a five-step startup sequence:

1. The native shell (Electron) initializes
2. Database migrations run via `php artisan migrate`
3. PHP development server starts with `php artisan serve`
4. The `boot()` method executes on `NativeAppServiceProvider`
5. An `ApplicationBooted` event is dispatched

### NativeAppServiceProvider

The main configuration point for your NativePHP application:

```php
namespace App\Providers;

use Native\Desktop\Contracts\ProvidesPhpIni;
use Native\Desktop\Facades\GlobalShortcut;
use Native\Desktop\Facades\MenuBar;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    public function boot(): void
    {
        // Initialize windows, register shortcuts, configure menus
        MenuBar::create()->route('home');

        GlobalShortcut::key('Alt+Space')
            ->event(MyEvent::class)
            ->register();
    }

    public function phpIni(): array
    {
        return [
            'memory_limit' => '512M',
            'max_execution_time' => '0',
        ];
    }
}
```

---

## Global Shortcuts

Register keyboard shortcuts that work even when the app is in the background.

```php
use Native\Desktop\Facades\GlobalShortcut;

// Register a shortcut
GlobalShortcut::key('Alt+Space')
    ->event(\App\Events\MyEvent::class)
    ->register();

// Unregister
GlobalShortcut::key('Alt+Space')->unregister();
```

### Supported Modifiers

| Modifier | Aliases |
|----------|---------|
| Command | Cmd |
| Control | Ctrl |
| CommandOrControl | CmdOrCtrl |
| Alt | Option |
| Shift | - |
| Super | - |
| Meta | - |

### Supported Keys

- **Alphanumeric**: A-Z, 0-9
- **Function Keys**: F1-F24
- **Navigation**: Up, Down, Left, Right, Home, End, PageUp, PageDown
- **Special**: Space, Enter/Return, Escape/Esc, Backspace, Delete, Insert, Tab
- **Media**: VolumeUp, VolumeDown, VolumeMute, MediaNextTrack, MediaPreviousTrack, MediaStop, MediaPlayPause
- **Other**: PrintScreen, Numlock, Scrolllock, Plus

### Key Format

Keys are combined with `+`:
```
CmdOrCtrl+Shift+A
Alt+Space
Ctrl+F1
```

---

## Menu Bar

Create menu bar (tray) applications that run in the background.

```php
use Native\Desktop\Facades\MenuBar;
use Native\Desktop\Facades\Menu;

MenuBar::create()
    ->icon(public_path('icons/menuBarIconTemplate.png'))
    ->tooltip('My App')
    ->width(300)
    ->height(400)
    ->route('home')
    ->alwaysOnTop()
    ->withContextMenu(
        Menu::make(
            Menu::label('My App'),
            Menu::separator(),
            Menu::link('https://example.com', 'Website')->openInBrowser(),
            Menu::separator(),
            Menu::quit()
        )
    );
```

### Configuration Methods

| Method | Description |
|--------|-------------|
| `icon($path)` | Set menu bar icon (22x22px PNG recommended, use "Template" in filename for auto-coloring) |
| `tooltip($text)` | Hover tooltip text |
| `width($px)` / `height($px)` | Popup window dimensions (default: 400x400) |
| `route($name)` | Laravel route to display |
| `url($url)` | Absolute URL to display |
| `alwaysOnTop()` | Keep window above others |
| `vibrancy($style)` | macOS vibrancy effect ('light', 'dark', etc.) |
| `backgroundColor($color)` | Solid background color |
| `resizable($bool)` | Allow window resizing |
| `withContextMenu($menu)` | Right-click context menu |
| `showDockIcon()` | Show in dock (hidden by default for menu bar apps) |

### Dynamic Control

```php
MenuBar::show();
MenuBar::hide();
MenuBar::label('Status: Active');
```

### Events

- `MenuBarShown` - Window opened
- `MenuBarHidden` - Window closed
- `MenuBarContextMenuOpened` - Right-click on icon

### Icon Specifications

- Standard: 22x22px PNG with transparent background
- Retina: 44x44px with `@2x` suffix (e.g., `icon@2x.png`)
- Template: Add "Template" to filename for auto white/black rendering on macOS

---

## Clipboard

Read and write system clipboard content.

```php
use Native\Desktop\Facades\Clipboard;

// Read
$text = Clipboard::text();
$html = Clipboard::html();
$image = Clipboard::image();

// Write
Clipboard::text('Hello world');
Clipboard::html('<b>Hello</b>');
Clipboard::image('path/to/image.png');

// Clear
Clipboard::clear();
```

---

## Windows

Create and manage application windows.

```php
use Native\Desktop\Facades\Window;

// Open a window
Window::open()
    ->route('home')
    ->title('My Window')
    ->width(800)
    ->height(600)
    ->alwaysOnTop()
    ->titleBarHidden()
    ->rememberState();

// Control windows
Window::close();
Window::close('window-id');
Window::minimize();
Window::maximize();
Window::resize(400, 300);

// Get windows
$current = Window::current();
$all = Window::all();
Window::get('window-id')->title('New Title');
```

### Configuration Options

| Method | Description |
|--------|-------------|
| `route($name)` / `url($url)` | Content to display |
| `title($text)` | Window title |
| `width($px)` / `height($px)` | Dimensions |
| `minWidth($px)` / `maxWidth($px)` | Size constraints |
| `position($x, $y)` | Window position |
| `rememberState()` | Remember size/position |
| `resizable($bool)` | Allow resizing |
| `movable($bool)` | Allow moving |
| `minimizable($bool)` / `maximizable($bool)` / `closable($bool)` | Button availability |
| `fullscreen()` / `fullscreenable($bool)` | Fullscreen mode |
| `alwaysOnTop()` | Stay above other windows |
| `titleBarHidden()` | Hide title bar |
| `skipTaskbar()` | Hide from taskbar |
| `hiddenInMissionControl()` | Hide on macOS Mission Control |
| `backgroundColor($color)` | Background color (supports transparency) |

### Events

- `WindowShown`, `WindowClosed`
- `WindowFocused`, `WindowBlurred`
- `WindowMinimized`, `WindowMaximized`
- `WindowResized`

---

## Child Processes

Run background processes and shell commands.

```php
use Native\Desktop\Facades\ChildProcess;

// Start a process
ChildProcess::start(
    cmd: 'osascript -e "tell application \"System Events\" to keystroke \"v\" using command down"',
    alias: 'paste-command'
);

// Convenience methods
ChildProcess::php('path/to/script.php', alias: 'php-worker');
ChildProcess::artisan('queue:work', alias: 'queue');
ChildProcess::node('resources/js/script.js', alias: 'node-worker');

// Control
ChildProcess::stop('alias');
ChildProcess::restart('alias');
$process = ChildProcess::get('alias');
$all = ChildProcess::all();
```

### Events

- `MessageReceived` - STDOUT output
- `ErrorReceived` - STDERR output

Events broadcast to frontend via `nativephp` channel.

### Important Notes

- Commands are non-blocking
- Use full binary paths when possible (don't rely on PATH)
- Arguments may differ between macOS/Linux and Windows

---

## Notifications

Display system notifications.

```php
use Native\Desktop\Facades\Notification;

Notification::title('Hello')
    ->message('This is a notification')
    ->event(\App\Events\NotificationClicked::class)
    ->show();

// macOS-specific features
Notification::title('Reply Example')
    ->message('Send a reply')
    ->hasReply('Type your reply...')
    ->addAction('Accept')
    ->addAction('Decline')
    ->reference('notification-123')
    ->show();
```

### Events

- `NotificationClicked` - User clicked notification
- `NotificationClosed` - User dismissed notification
- `NotificationReply` - User replied (macOS)
- `NotificationActionClicked` - User clicked action button (includes `$index`)

---

## Broadcasting (PHP to Frontend)

Send events from Laravel to the Electron frontend.

### PHP Event Class

```php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class RecordingToggled implements ShouldBroadcastNow
{
    public function __construct(
        public string $combo = '',
        public array $bounds = [],
        public array $position = []
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('nativephp')];
    }
}
```

### Frontend JavaScript

```javascript
document.addEventListener('native:init', () => {
    Native.on('App\\Events\\RecordingToggled', (event) => {
        console.log('Recording toggled!', event);
    });
});
```

### Livewire Integration

```php
// In Livewire component
protected $listeners = [
    'native:App\Events\RecordingToggled' => 'handleRecording'
];
```

---

## Shell Operations

Basic file and URL operations.

```php
use Native\Desktop\Facades\Shell;

// Open in file manager (Finder, File Explorer)
Shell::showInFolder('/path/to/file');

// Open with default application
Shell::openFile('/path/to/document.pdf');

// Move to trash
Shell::trashFile('/path/to/file');

// Open URL in default browser
Shell::openExternal('https://example.com');
```

---

## Running the Application

```bash
# Development
php artisan native:serve

# Build for production
php artisan native:build
```

---

## Common Patterns

### Push-to-Talk Recording (Toggle Mode)

Since NativePHP doesn't detect key-up events, use toggle mode:

1. First shortcut press → Start recording
2. Second shortcut press → Stop recording, process, paste

### Simulating Paste (macOS)

Use AppleScript via ChildProcess:

```php
$script = 'tell application "System Events" to keystroke "v" using command down';
ChildProcess::start(
    cmd: "osascript -e '{$script}'",
    alias: 'paste-' . time()
);
```

**Note**: Requires Accessibility permission in System Settings.

### Menu Bar App with Background Recording

```php
// NativeAppServiceProvider
public function boot(): void
{
    MenuBar::create()
        ->icon(public_path('icons/mic.png'))
        ->route('recording')
        ->width(320)
        ->height(280);

    GlobalShortcut::key('Alt+Space')
        ->event(RecordingToggled::class)
        ->register();
}
```

---

## Permissions Required (macOS)

| Permission | Purpose | How to Grant |
|------------|---------|--------------|
| Microphone | Audio recording | Auto-prompted on first use |
| Accessibility | Simulate keyboard (paste) | Manual: System Settings > Privacy & Security > Accessibility |

---

## Resources

- [NativePHP Documentation](https://nativephp.com/docs/desktop/2)
- [NativePHP GitHub](https://github.com/NativePHP)
- [Electron Documentation](https://www.electronjs.org/docs)
