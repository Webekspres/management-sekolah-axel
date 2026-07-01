<div
    x-data="notificationPoller()"
    x-init="init()"
    x-cloak
>
    {{-- Sound toggle button — visible in the bottom-right corner --}}
    <button
        x-show="initialized"
        x-transition
        type="button"
        class="fixed bottom-4 right-4 z-[9999] flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-medium shadow-lg transition-all duration-200"
        :class="soundEnabled
            ? 'bg-success-500/90 text-white hover:bg-success-600'
            : 'bg-gray-500/80 text-white hover:bg-gray-600'"
        @click="toggleSound()"
        x-cloak
        aria-label="Toggle notification sound"
    >
        <span x-show="soundEnabled">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217zM14.657 2.929a1 1 0 011.414 0A9.972 9.972 0 0119 10a9.972 9.972 0 01-2.929 7.071 1 1 0 01-1.414-1.414A7.971 7.971 0 0017 10c0-2.21-.894-4.208-2.343-5.657a1 1 0 010-1.414zm-2.829 2.828a1 1 0 011.415 0A5.983 5.983 0 0115 10a5.984 5.984 0 01-1.757 4.243 1 1 0 01-1.415-1.415A3.984 3.984 0 0013 10a3.983 3.983 0 00-1.172-2.828 1 1 0 010-1.415z" clip-rule="evenodd"/>
            </svg>
        </span>
        <span x-show="!soundEnabled">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217zM12.293 7.293a1 1 0 011.414 0L15 8.586l1.293-1.293a1 1 0 111.414 1.414L16.414 10l1.293 1.293a1 1 0 01-1.414 1.414L15 11.414l-1.293 1.293a1 1 0 01-1.414-1.414L13.586 10l-1.293-1.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
            </svg>
        </span>
        <span x-text="soundEnabled ? 'Suara ON' : 'Suara OFF'"></span>
    </button>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('notificationPoller', () => ({
            previousCount: null, // null = first load, don't play sound
            lastCheckedAt: null,
            soundEnabled: true,
            initialized: false,
            init() {
                // Load sound preference from localStorage
                try {
                    this.soundEnabled = localStorage.getItem('notification_sound') !== 'off';
                } catch (e) {
                    this.soundEnabled = true;
                }

                this.initialized = true;

                // Initial poll
                this.poll();

                // Poll every 3 seconds
                setInterval(() => this.poll(), 3000);
            },
            toggleSound() {
                this.soundEnabled = !this.soundEnabled;
                try {
                    localStorage.setItem('notification_sound', this.soundEnabled ? 'on' : 'off');
                } catch (e) {
                    // Silent fail
                }
            },
            async poll() {
                try {
                    const response = await fetch('/notifications/poll', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) return;

                    const data = await response.json();

                    // Only play sound if we have a previous count (not first load)
                    if (this.previousCount !== null && data.unread_count > this.previousCount) {
                        this.playSound(data);
                    }

                    // On first load, just record the count without playing sound
                    this.previousCount = data.unread_count ?? 0;
                    this.lastCheckedAt = data.server_time;

                    // Update document title with unread count
                    this.updateTitle(data.unread_count);
                } catch (e) {
                    // Silent fail — polling will retry
                }
            },
            playSound(data) {
                if (!this.soundEnabled) return;
                if (document.visibilityState !== 'visible') return;

                try {
                    const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                    const oscillator = audioCtx.createOscillator();
                    const gainNode = audioCtx.createGain();

                    oscillator.connect(gainNode);
                    gainNode.connect(audioCtx.destination);

                    oscillator.type = 'sine';
                    oscillator.frequency.setValueAtTime(880, audioCtx.currentTime);
                    oscillator.frequency.setValueAtTime(1100, audioCtx.currentTime + 0.1);

                    gainNode.gain.setValueAtTime(0.3, audioCtx.currentTime);
                    gainNode.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.3);

                    oscillator.start(audioCtx.currentTime);
                    oscillator.stop(audioCtx.currentTime + 0.3);

                    setTimeout(() => audioCtx.close(), 1000);
                } catch (e) {
                    // Audio not supported — silent fail
                }
            },
            updateTitle(unreadCount) {
                const originalTitle = document.title.replace(/^\(\d+\)\s*/, '');
                document.title = unreadCount > 0
                    ? `(${unreadCount}) ${originalTitle}`
                    : originalTitle;
            },
        }));
    });
</script>
