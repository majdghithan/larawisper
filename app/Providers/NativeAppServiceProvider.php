<?php

namespace App\Providers;

use App\Events\RecordingToggled;
use Native\Desktop\Contracts\ProvidesPhpIni;
use Native\Desktop\Facades\GlobalShortcut;
use Native\Desktop\Facades\Menu;
use Native\Desktop\Facades\MenuBar;
use Native\Desktop\Facades\Window;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Executed once the native application has been booted.
     * Use this method to open windows, register global shortcuts, etc.
     */
    public function boot(): void
    {
        MenuBar::create()
            ->tooltip('Larawisper - Voice to Text')
            ->width(320)
            ->height(500)
            ->route('recording')
            ->withContextMenu(
                Menu::make(
                    Menu::label('Larawisper - Voice to Text'),
                    Menu::separator(),
                    Menu::link('https://github.com', 'About'),
                    Menu::separator(),
                    Menu::quit()
                )
            );

        // Create floating recorder window (center bottom of screen)
        // Window ID 'floating-recorder' ensures only one instance exists
        if (config('wisper.floating_window', true)) {
            Window::open('floating-recorder')
                ->route('floating-recorder')
                ->height(60)
                ->position(
                    x: (int) ((1920 + 330) / 2),
                    y: (int) (1080) 
                )
                ->alwaysOnTop()
                ->frameless()
                ->transparent()
                ->resizable(false)
                ->showDevTools(false)
                ->focusable(false)
                ->titleBarHidden()
                ->closable(false)
                ->minimizable(false)
                ->maximizable(false);
        }

        GlobalShortcut::key(config('wisper.shortcut', 'Ctrl+Shift+Space'))
            ->event(RecordingToggled::class)
            ->register();
    }

    /**
     * Return an array of php.ini directives to be set.
     */
    public function phpIni(): array
    {
        return [
            'memory_limit' => '1024M',
            'display_errors' => '1',
            'error_reporting' => 'E_ALL',
            'max_execution_time' => '0',
            'max_input_time' => '0',
            'ffi.enable' => 'true',
        ];
    }
}
