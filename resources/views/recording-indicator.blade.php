<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recording</title>
    @vite(['resources/css/app.css'])
    <style>
        body {
            -webkit-app-region: drag;
            user-select: none;
            background: transparent;
        }
        button {
            -webkit-app-region: no-drag;
        }
        @keyframes pulse-recording {
            0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            50% { opacity: 0.8; box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
        }
        .recording-pulse {
            animation: pulse-recording 1.5s ease-in-out infinite;
        }
    </style>
</head>
<body class="h-full">
    <div id="indicator" class="h-full flex items-center justify-center p-2">
        <div class="bg-gray-900/95 backdrop-blur-sm rounded-full px-4 py-2 flex items-center gap-3 shadow-lg border border-gray-700">
            <!-- Recording dot -->
            <div class="w-3 h-3 bg-red-500 rounded-full recording-pulse"></div>

            <!-- Duration -->
            <span id="duration" class="text-white font-mono text-sm">00:00</span>

            <!-- Cancel button -->
            <button id="cancel-btn" class="w-6 h-6 rounded-full bg-gray-700 hover:bg-gray-600 flex items-center justify-center transition-colors">
                <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </div>

    <script>
        let startTime = Date.now();
        let intervalId = null;

        function formatDuration(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }

        function updateDuration() {
            const elapsed = Math.floor((Date.now() - startTime) / 1000);
            document.getElementById('duration').textContent = formatDuration(elapsed);
        }

        // Start updating duration
        intervalId = setInterval(updateDuration, 100);

        // Cancel button
        document.getElementById('cancel-btn').addEventListener('click', () => {
            if (window.Native) {
                // Signal cancellation to main window
                window.Native.notify('recording-cancelled');
            }
            window.close();
        });

        // Listen for stop signal
        document.addEventListener('native:init', () => {
            if (window.Native) {
                window.Native.on('stop-indicator', () => {
                    clearInterval(intervalId);
                    window.close();
                });
            }
        });
    </script>
</body>
</html>
