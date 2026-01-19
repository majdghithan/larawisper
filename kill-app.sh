#!/bin/bash
# Kill all Larawisper/wisper-clone related processes

echo "Killing Electron processes..."
pkill -9 -f "wisper-clone.*Electron" 2>/dev/null

echo "Killing esbuild processes..."
pkill -9 -f "wisper-clone.*esbuild" 2>/dev/null

echo "Killing electron-vite processes..."
pkill -9 -f "wisper-clone.*electron-vite" 2>/dev/null

echo "Killing php artisan native:serve..."
pkill -9 -f "php artisan native:serve" 2>/dev/null

# Wait a moment for processes to terminate
sleep 1

# Check if any are still running
remaining=$(ps aux | grep -E "wisper-clone.*(Electron|esbuild|electron-vite)" | grep -v grep | wc -l)
if [ "$remaining" -gt 0 ]; then
    echo "Warning: $remaining processes still running. Trying harder..."
    pkill -9 -f "wisper-clone" 2>/dev/null
    sleep 1
fi

echo "Done! You can now run: php artisan native:serve"
