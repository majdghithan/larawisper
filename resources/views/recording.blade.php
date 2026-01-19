<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Wisper</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            -webkit-app-region: drag;
            user-select: none;
        }
        button, input, a, .clickable {
            -webkit-app-region: no-drag;
        }
        @keyframes pulse-recording {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.1); }
        }
        .recording-pulse {
            animation: pulse-recording 1s ease-in-out infinite;
        }
        @keyframes wave {
            0%, 100% { height: 8px; }
            50% { height: 24px; }
        }
        .wave-bar {
            animation: wave 0.8s ease-in-out infinite;
        }
        .wave-bar:nth-child(2) { animation-delay: 0.1s; }
        .wave-bar:nth-child(3) { animation-delay: 0.2s; }
        .wave-bar:nth-child(4) { animation-delay: 0.3s; }
        .wave-bar:nth-child(5) { animation-delay: 0.4s; }
    </style>
</head>
<body class="h-full bg-gray-900 text-white">
    <div id="app" class="h-full flex flex-col p-4">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm-1-9c0-.55.45-1 1-1s1 .45 1 1v6c0 .55-.45 1-1 1s-1-.45-1-1V5z"/>
                        <path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/>
                    </svg>
                </div>
                <span class="font-semibold text-sm">Wisper</span>
            </div>
            <div class="text-xs text-gray-400 bg-gray-800 px-2 py-1 rounded">
                <kbd class="font-mono">{{ config('wisper.shortcut') }}</kbd>
            </div>
        </div>

        <!-- Status Area -->
        <div id="status-area" class="flex-1 flex flex-col items-center justify-center">
            <!-- Ready State (default) -->
            <div id="state-ready" class="text-center">
                <div id="start-btn" class="clickable w-20 h-20 mx-auto mb-4 bg-gray-800 rounded-full flex items-center justify-center cursor-pointer hover:bg-gray-700 transition-colors">
                    <svg class="w-10 h-10 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm-1-9c0-.55.45-1 1-1s1 .45 1 1v6c0 .55-.45 1-1 1s-1-.45-1-1V5z"/>
                        <path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/>
                    </svg>
                </div>
                <p class="text-gray-400 text-sm">Click or press shortcut to record</p>
            </div>

            <!-- Recording State -->
            <div id="state-recording" class="text-center hidden">
                <div id="stop-btn" class="clickable w-20 h-20 mx-auto mb-4 bg-red-500/20 rounded-full flex items-center justify-center recording-pulse cursor-pointer">
                    <div class="w-12 h-12 bg-red-500 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/>
                        </svg>
                    </div>
                </div>
                <div class="flex items-center justify-center gap-1 h-8 mb-3">
                    <div class="wave-bar w-1 bg-red-500 rounded-full"></div>
                    <div class="wave-bar w-1 bg-red-500 rounded-full"></div>
                    <div class="wave-bar w-1 bg-red-500 rounded-full"></div>
                    <div class="wave-bar w-1 bg-red-500 rounded-full"></div>
                    <div class="wave-bar w-1 bg-red-500 rounded-full"></div>
                </div>
                <p id="recording-duration" class="text-2xl font-mono text-white mb-2">00:00</p>
                <p class="text-gray-400 text-sm">Click to stop</p>
            </div>

            <!-- Transcribing State -->
            <div id="state-transcribing" class="text-center hidden">
                <div class="w-20 h-20 mx-auto mb-4 bg-purple-500/20 rounded-full flex items-center justify-center">
                    <svg class="w-10 h-10 text-purple-400 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <p class="text-gray-400 text-sm">Transcribing...</p>
            </div>

            <!-- Completed State -->
            <div id="state-completed" class="text-center hidden">
                <div class="w-20 h-20 mx-auto mb-4 bg-green-500/20 rounded-full flex items-center justify-center">
                    <svg class="w-10 h-10 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <p id="transcribed-text" class="text-white text-sm mb-2 max-w-full overflow-hidden text-ellipsis px-2"></p>
                <p class="text-green-400 text-sm">Text copied!</p>
            </div>

            <!-- Error State -->
            <div id="state-error" class="text-center hidden">
                <div class="w-20 h-20 mx-auto mb-4 bg-red-500/20 rounded-full flex items-center justify-center">
                    <svg class="w-10 h-10 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                <p id="error-message" class="text-red-400 text-sm mb-3 px-2 max-h-16 overflow-y-auto">An error occurred</p>
                <div class="flex gap-2 justify-center">
                    <button id="copy-error-btn" class="clickable px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded text-sm text-white transition-colors">
                        Copy Error
                    </button>
                    <button id="retry-btn" class="clickable px-4 py-2 bg-purple-600 hover:bg-purple-500 rounded text-sm text-white transition-colors">
                        Try Again
                    </button>
                </div>
            </div>

            <!-- Permission Denied State -->
            <div id="state-permission-denied" class="text-center hidden">
                <div class="w-20 h-20 mx-auto mb-4 bg-yellow-500/20 rounded-full flex items-center justify-center">
                    <svg class="w-10 h-10 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <p class="text-yellow-400 text-sm mb-2">Microphone access denied</p>
                <p class="text-gray-400 text-xs mb-3">Enable in System Settings</p>
                <button id="request-permission-btn" class="clickable px-4 py-2 bg-purple-600 hover:bg-purple-500 rounded text-sm text-white transition-colors">
                    Try Again
                </button>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center text-xs text-gray-500 mt-4">
            <p>Model: {{ config('wisper.model') }}</p>
            <button id="open-accessibility-btn" class="clickable mt-2 text-purple-400 hover:text-purple-300 underline">
                Setup Auto-Paste
            </button>
        </div>
    </div>

    <script>
        const CONFIG = {
            shortcut: '{{ config('wisper.shortcut') }}'
        };

        let recorder = null;
        let currentState = 'ready';

        window.addEventListener('load', function() {
            const checkInterval = setInterval(function() {
                if (window.AudioRecorder) {
                    clearInterval(checkInterval);
                    initApp();
                }
            }, 100);

            setTimeout(function() {
                clearInterval(checkInterval);
                if (!window.AudioRecorder) {
                    setState('error', { message: 'Failed to load app' });
                }
            }, 5000);
        });

        function initApp() {
            console.log('Wisper ready');
            recorder = new window.AudioRecorder();

            recorder.onDurationUpdate = function(seconds) {
                const el = document.getElementById('recording-duration');
                if (el) el.textContent = window.AudioRecorder.formatDuration(seconds);
            };

            recorder.onStateChange = function(state) {
                if (state === 'recording') setState('recording');
                else if (state === 'transcribing') setState('transcribing');
                else if (state === 'permission-denied') setState('permission-denied');
            };

            // Click handlers
            document.getElementById('start-btn').onclick = startRecording;
            document.getElementById('stop-btn').onclick = stopRecording;
            document.getElementById('retry-btn').onclick = function() { setState('ready'); };
            document.getElementById('request-permission-btn').onclick = startRecording;
            document.getElementById('open-accessibility-btn').onclick = openAccessibilitySettings;
            document.getElementById('copy-error-btn').onclick = copyError;

            setState('ready');
        }

        let lastErrorMessage = '';

        function copyError() {
            if (lastErrorMessage) {
                navigator.clipboard.writeText(lastErrorMessage).then(function() {
                    const btn = document.getElementById('copy-error-btn');
                    btn.textContent = 'Copied!';
                    setTimeout(function() { btn.textContent = 'Copy Error'; }, 1500);
                });
            }
        }

        function openAccessibilitySettings() {
            fetch('/api/open-accessibility-settings', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
            });
        }

        function setState(state, data) {
            data = data || {};
            currentState = state;
            const states = ['ready', 'recording', 'transcribing', 'completed', 'error', 'permission-denied'];

            states.forEach(function(s) {
                const el = document.getElementById('state-' + s);
                if (el) el.classList.toggle('hidden', s !== state);
            });

            if (state === 'completed' && data.text) {
                const textEl = document.getElementById('transcribed-text');
                if (textEl) textEl.textContent = data.text.length > 80 ? data.text.substring(0, 80) + '...' : data.text;
                setTimeout(function() { setState('ready'); }, 2500);
            }

            if (state === 'error' && data.message) {
                lastErrorMessage = data.message;
                const msgEl = document.getElementById('error-message');
                if (msgEl) msgEl.textContent = data.message;
            }
        }

        async function startRecording() {
            if (!recorder || recorder.isRecording) return;
            try {
                await recorder.startRecording();
            } catch (error) {
                console.error('Failed to start recording:', error);
                if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
                    setState('permission-denied');
                } else {
                    setState('error', { message: error.message });
                }
            }
        }

        async function stopRecording() {
            if (!recorder || !recorder.isRecording) return;
            try {
                const blob = await recorder.stopRecording();
                setState('transcribing');
                const result = await recorder.uploadAndTranscribe(blob);
                if (result.success) {
                    setState('completed', { text: result.text });
                } else {
                    setState('error', { message: result.error || 'Transcription failed' });
                }
            } catch (error) {
                setState('error', { message: error.message });
            }
        }

        let isProcessing = false;

        async function toggleRecording() {
            if (!recorder) return;

            // Prevent multiple simultaneous toggles
            if (isProcessing) {
                console.log('Already processing, ignoring toggle');
                return;
            }

            // If currently recording, stop it
            if (recorder.isRecording) {
                isProcessing = true;
                try {
                    await stopRecording();
                } finally {
                    // Allow new recording after completion
                    setTimeout(() => { isProcessing = false; }, 500);
                }
                return;
            }

            // Only start new recording if in ready, completed, or error state
            if (currentState === 'ready' || currentState === 'completed' || currentState === 'error') {
                isProcessing = true;
                try {
                    // Ensure we're in ready state
                    if (currentState !== 'ready') {
                        setState('ready');
                    }
                    // Small delay to ensure clean state
                    await new Promise(resolve => setTimeout(resolve, 150));
                    await startRecording();
                } finally {
                    isProcessing = false;
                }
            }
        }

        window.toggleRecording = toggleRecording;

        // NativePHP event listener
        if (typeof window.Native !== 'undefined') {
            window.Native.on('App\\Events\\RecordingToggled', toggleRecording);
        }
        document.addEventListener('native:init', function() {
            if (window.Native) {
                window.Native.on('App\\Events\\RecordingToggled', toggleRecording);
            }
        });

        // Keyboard shortcut handler
        function parseShortcut(shortcut) {
            const parts = shortcut.toLowerCase().split('+');
            const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
            const cmdOrCtrl = parts.includes('cmdorctrl');
            return {
                ctrl: parts.includes('ctrl') || (cmdOrCtrl && !isMac),
                shift: parts.includes('shift'),
                alt: parts.includes('alt') || parts.includes('option'),
                meta: parts.includes('cmd') || parts.includes('meta') || (cmdOrCtrl && isMac),
                key: parts[parts.length - 1]
            };
        }

        const shortcutConfig = parseShortcut(CONFIG.shortcut);

        document.addEventListener('keydown', function(e) {
            const match =
                (shortcutConfig.ctrl === e.ctrlKey) &&
                (shortcutConfig.shift === e.shiftKey) &&
                (shortcutConfig.alt === e.altKey) &&
                (shortcutConfig.meta === e.metaKey) &&
                ((shortcutConfig.key === 'space' && e.code === 'Space') ||
                 (shortcutConfig.key !== 'space' && e.key.toLowerCase() === shortcutConfig.key));

            if (match) {
                e.preventDefault();
                toggleRecording();
            }
        });
    </script>
</body>
</html>
