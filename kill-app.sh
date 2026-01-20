#!/bin/bash
# Kill all Larawisper/wisper-clone related processes

echo "Stopping NativePHP processes..."

# Get PIDs for all related processes
PIDS=$(ps aux | grep -E "wisper-clone.*(Electron|php|node|esbuild)" | grep -v grep | awk '{print $2}')

if [ -z "$PIDS" ]; then
    echo "No processes found."
else
    echo "Found processes: $PIDS"

    # First try graceful kill
    echo "$PIDS" | xargs kill 2>/dev/null
    sleep 1

    # Force kill any remaining
    REMAINING=$(ps aux | grep -E "wisper-clone.*(Electron|php|node|esbuild)" | grep -v grep | awk '{print $2}')
    if [ -n "$REMAINING" ]; then
        echo "Force killing remaining: $REMAINING"
        echo "$REMAINING" | xargs kill -9 2>/dev/null
        sleep 1
    fi
fi

# Check for any remaining (excluding zombie processes marked with Z or UE state)
ACTIVE=$(ps aux | grep -E "wisper-clone.*(Electron|php|node|esbuild)" | grep -v grep | grep -v " Z " | grep -v " UE " | wc -l)
if [ "$ACTIVE" -gt 0 ]; then
    echo "Warning: $ACTIVE active processes still running"
    ps aux | grep -E "wisper-clone.*(Electron|php|node|esbuild)" | grep -v grep | grep -v " Z " | grep -v " UE "
else
    echo "All processes stopped successfully!"
fi

# Note about zombie processes
ZOMBIES=$(ps aux | grep -E "wisper-clone.*Electron" | grep -E " (Z|UE) " | wc -l)
if [ "$ZOMBIES" -gt 0 ]; then
    echo ""
    echo "Note: $ZOMBIES zombie process(es) exist but won't interfere. They'll clear on reboot."
fi

echo ""
echo "You can now run: php artisan native:serve"
