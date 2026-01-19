/**
 * AudioRecorder class for handling microphone recording in the browser.
 * Uses MediaRecorder API to capture audio and upload to backend for transcription.
 *
 * Microphone is only accessed during recording to avoid constant "mic in use" indicator.
 */
export class AudioRecorder {
    constructor() {
        this.mediaRecorder = null;
        this.audioChunks = [];
        this.stream = null;
        this.isRecording = false;
        this.startTime = null;
        this.onStateChange = null;
        this.onDurationUpdate = null;
        this.durationInterval = null;
    }

    /**
     * Start recording audio. Requests microphone access only when needed.
     * @returns {Promise<boolean>} Whether recording started successfully
     */
    async startRecording() {
        if (this.isRecording) {
            console.warn('Already recording.');
            return false;
        }

        // Ensure clean state before starting
        this.cleanupPreviousRecording();

        try {
            // Request microphone access only when starting to record
            this.stream = await navigator.mediaDevices.getUserMedia({
                audio: {
                    channelCount: 1,
                    sampleRate: 16000,
                    echoCancellation: true,
                    noiseSuppression: true,
                    autoGainControl: true,
                },
            });
        } catch (error) {
            console.error('Failed to access microphone:', error);
            if (this.onStateChange) {
                this.onStateChange('permission-denied');
            }
            throw error;
        }

        // Reset audio chunks for fresh recording
        this.audioChunks = [];

        // Determine best supported MIME type
        const mimeType = this.getSupportedMimeType();

        this.mediaRecorder = new MediaRecorder(this.stream, {
            mimeType: mimeType,
        });

        this.mediaRecorder.ondataavailable = (event) => {
            if (event.data.size > 0) {
                this.audioChunks.push(event.data);
            }
        };

        this.mediaRecorder.start(100); // Collect data every 100ms
        this.isRecording = true;
        this.startTime = Date.now();

        // Update menu bar to show recording indicator
        this.updateMenuBarState(true);

        // Start duration tracking
        this.durationInterval = setInterval(() => {
            if (this.onDurationUpdate) {
                const duration = Math.floor((Date.now() - this.startTime) / 1000);
                this.onDurationUpdate(duration);
            }
        }, 100);

        if (this.onStateChange) {
            this.onStateChange('recording');
        }

        return true;
    }

    /**
     * Stop recording and return the audio blob. Releases microphone.
     * @returns {Promise<Blob>} The recorded audio as a Blob
     */
    async stopRecording() {
        return new Promise((resolve, reject) => {
            if (!this.isRecording || !this.mediaRecorder) {
                reject(new Error('Not currently recording.'));
                return;
            }

            // Clear duration interval
            if (this.durationInterval) {
                clearInterval(this.durationInterval);
                this.durationInterval = null;
            }

            // Request any pending data before stopping
            if (this.mediaRecorder.state === 'recording') {
                this.mediaRecorder.requestData();
            }

            const mimeType = this.mediaRecorder.mimeType;

            this.mediaRecorder.onstop = () => {
                // Small delay to ensure all data is collected
                setTimeout(() => {
                    const blob = new Blob(this.audioChunks, { type: mimeType });
                    this.isRecording = false;
                    this.startTime = null;

                    // Release microphone immediately after recording stops
                    this.releaseMicrophone();

                    if (this.onStateChange) {
                        this.onStateChange('stopped');
                    }

                    // Check if we have actual audio data
                    if (blob.size < 1000) {
                        reject(new Error('Recording too short. Please record for at least 1 second.'));
                        return;
                    }

                    console.log('Recording blob size:', blob.size, 'type:', mimeType);
                    resolve(blob);
                }, 100);
            };

            this.mediaRecorder.onerror = (event) => {
                this.isRecording = false;
                this.releaseMicrophone();
                reject(new Error('MediaRecorder error: ' + event.error));
            };

            this.mediaRecorder.stop();
        });
    }

    /**
     * Release the microphone stream to stop the "recording" indicator.
     */
    releaseMicrophone() {
        // Stop all tracks and ensure they're fully released
        if (this.stream) {
            const tracks = this.stream.getTracks();
            tracks.forEach((track) => {
                track.stop();
                track.enabled = false;
            });
            this.stream = null;
        }

        // Clear media recorder
        if (this.mediaRecorder) {
            this.mediaRecorder.ondataavailable = null;
            this.mediaRecorder.onstop = null;
            this.mediaRecorder.onerror = null;
            this.mediaRecorder = null;
        }

        this.audioChunks = [];

        // Update menu bar to clear recording indicator
        this.updateMenuBarState(false);
    }

    /**
     * Update the menu bar recording state indicator.
     * @param {boolean} isRecording Whether currently recording
     */
    updateMenuBarState(isRecording) {
        fetch('/api/recording-state', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({ recording: isRecording }),
        }).catch((err) => console.warn('Failed to update menu bar state:', err));
    }

    /**
     * Cleanup any leftover state from previous recording.
     */
    cleanupPreviousRecording() {
        // Stop any existing stream tracks
        if (this.stream) {
            this.stream.getTracks().forEach((track) => {
                track.stop();
                track.enabled = false;
            });
            this.stream = null;
        }

        // Clear media recorder handlers and reference
        if (this.mediaRecorder) {
            this.mediaRecorder.ondataavailable = null;
            this.mediaRecorder.onstop = null;
            this.mediaRecorder.onerror = null;
            this.mediaRecorder = null;
        }

        // Clear any pending interval
        if (this.durationInterval) {
            clearInterval(this.durationInterval);
            this.durationInterval = null;
        }

        // Reset state
        this.audioChunks = [];
        this.isRecording = false;
        this.startTime = null;
    }

    /**
     * Cancel the current recording without saving.
     */
    cancelRecording() {
        if (this.durationInterval) {
            clearInterval(this.durationInterval);
            this.durationInterval = null;
        }

        if (this.mediaRecorder && this.isRecording) {
            this.mediaRecorder.stop();
        }

        this.audioChunks = [];
        this.isRecording = false;
        this.startTime = null;

        // Release microphone
        this.releaseMicrophone();

        if (this.onStateChange) {
            this.onStateChange('cancelled');
        }
    }

    /**
     * Upload the recorded audio to the backend for transcription.
     * @param {Blob} blob The audio blob to upload
     * @returns {Promise<Object>} The transcription response
     */
    async uploadAndTranscribe(blob) {
        if (this.onStateChange) {
            this.onStateChange('transcribing');
        }

        const formData = new FormData();

        // Determine file extension from MIME type
        const extension = this.getExtensionFromMimeType(blob.type);
        formData.append('audio', blob, `recording.${extension}`);

        try {
            const response = await fetch('/api/transcribe', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
            });

            const data = await response.json();

            if (this.onStateChange) {
                this.onStateChange(data.success ? 'completed' : 'error');
            }

            return data;
        } catch (error) {
            console.error('Transcription upload failed:', error);

            if (this.onStateChange) {
                this.onStateChange('error');
            }

            throw error;
        }
    }

    /**
     * Get the best supported MIME type for recording.
     * @returns {string} The supported MIME type
     */
    getSupportedMimeType() {
        const types = [
            'audio/webm;codecs=opus',
            'audio/webm',
            'audio/ogg;codecs=opus',
            'audio/mp4',
            'audio/wav',
        ];

        for (const type of types) {
            if (MediaRecorder.isTypeSupported(type)) {
                return type;
            }
        }

        return 'audio/webm'; // Fallback
    }

    /**
     * Get file extension from MIME type.
     * @param {string} mimeType The MIME type
     * @returns {string} The file extension
     */
    getExtensionFromMimeType(mimeType) {
        const mapping = {
            'audio/webm': 'webm',
            'audio/webm;codecs=opus': 'webm',
            'audio/ogg': 'ogg',
            'audio/ogg;codecs=opus': 'ogg',
            'audio/mp4': 'm4a',
            'audio/wav': 'wav',
            'audio/mpeg': 'mp3',
        };

        return mapping[mimeType] || 'webm';
    }

    /**
     * Get the current recording duration in seconds.
     * @returns {number} Duration in seconds
     */
    getDuration() {
        if (!this.startTime) return 0;
        return Math.floor((Date.now() - this.startTime) / 1000);
    }

    /**
     * Format seconds as MM:SS.
     * @param {number} seconds Total seconds
     * @returns {string} Formatted time string
     */
    static formatDuration(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }

    /**
     * Clean up resources.
     */
    destroy() {
        this.cancelRecording();
    }
}

export default AudioRecorder;
