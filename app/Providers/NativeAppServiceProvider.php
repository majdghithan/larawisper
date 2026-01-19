<?php

namespace App\Providers;

use App\Events\RecordingToggled;
use Native\Desktop\Contracts\ProvidesPhpIni;
use Native\Desktop\Facades\GlobalShortcut;
use Native\Desktop\Facades\Menu;
use Native\Desktop\Facades\MenuBar;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Executed once the native application has been booted.
     * Use this method to open windows, register global shortcuts, etc.
     */
    public function boot(): void
    {
        // Window::open();
        
        MenuBar::create()
            ->tooltip('Wisper - Voice to Text')
            ->width(320)
            ->height(280)
            ->route('recording')
            ->withContextMenu(
                Menu::make(
                    Menu::label('Wisper - Voice to Text'),
                    Menu::separator(),
                    Menu::link('https://github.com', 'About'),
                    Menu::separator(),
                    Menu::quit()
                )
            );

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
