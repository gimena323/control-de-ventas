<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BellRing, Volume2, VolumeX, X } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { usePermissions } from '@/composables/usePermissions';
import { index as ventasIndex } from '@/routes/ventas';
import { pendientes } from '@/routes/ventas/solicitudes';

const { can } = usePermissions();
const enabled = computed(() => can('pedidos.gestion') && can('ventas.ver'));

const visible = ref(false);
const count = ref(0);

const SOUND_STORAGE_KEY = 'emma:purchase-notifications-enabled';

function readSoundPreference(): boolean {
    if (typeof window === 'undefined') {
        return true;
    }

    try {
        return window.localStorage.getItem(SOUND_STORAGE_KEY) !== 'false';
    } catch {
        return true;
    }
}

const soundEnabled = ref(true);

function persistSoundPreference(value: boolean): void {
    if (typeof window === 'undefined') {
        return;
    }

    try {
        window.localStorage.setItem(SOUND_STORAGE_KEY, String(value));
    } catch {
        // Ignore storage errors (private mode, quota, etc.).
    }
}

const POLL_INTERVAL_MS = 2000;
let pollTimer: number | null = null;
let hideTimer: number | null = null;
let lastSeenId: number | null = null;
let audioContext: AudioContext | null = null;
let isPolling = false;

function unlockAudio(): void {
    if (typeof window === 'undefined') {
        return;
    }

    if (!audioContext) {
        const AudioContextClass =
            window.AudioContext ??
            (
                window as Window & {
                    webkitAudioContext?: typeof AudioContext;
                }
            ).webkitAudioContext;

        if (!AudioContextClass) {
            return;
        }

        audioContext = new AudioContextClass();
    }

    void audioContext.resume();
}

function playNotificationSound(): void {
    if (!soundEnabled.value) {
        return;
    }

    unlockAudio();

    if (!audioContext) {
        return;
    }

    const start = audioContext.currentTime;

    [880, 1174.66].forEach((frequency, index) => {
        const oscillator = audioContext!.createOscillator();
        const gain = audioContext!.createGain();
        const offset = index * 0.18;

        oscillator.type = 'sine';
        oscillator.frequency.value = frequency;
        gain.gain.setValueAtTime(0.0001, start + offset);
        gain.gain.exponentialRampToValueAtTime(0.25, start + offset + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, start + offset + 0.16);

        oscillator.connect(gain);
        gain.connect(audioContext!.destination);
        oscillator.start(start + offset);
        oscillator.stop(start + offset + 0.18);
    });
}

function toggleSound(): void {
    soundEnabled.value = !soundEnabled.value;
    persistSoundPreference(soundEnabled.value);

    if (soundEnabled.value) {
        playNotificationSound();
    }
}

function stopPolling(): void {
    if (pollTimer !== null) {
        window.clearInterval(pollTimer);
        pollTimer = null;
    }
}

async function pollPendingRequests(): Promise<void> {
    if (isPolling) {
        return;
    }

    isPolling = true;

    try {
        const response = await fetch(pendientes.url(), {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });

        if (response.status === 401 || response.status === 403) {
            stopPolling();

            return;
        }

        if (!response.ok) {
            return;
        }

        const data = (await response.json()) as {
            count: number;
            latest_id: number;
        };

        count.value = data.count;

        if (lastSeenId === null) {
            lastSeenId = data.latest_id;

            return;
        }

        if (data.latest_id > lastSeenId) {
            lastSeenId = data.latest_id;
            visible.value = true;
            playNotificationSound();

            if (hideTimer !== null) {
                window.clearTimeout(hideTimer);
            }

            hideTimer = window.setTimeout(() => {
                visible.value = false;
            }, 20000);

            return;
        }

        lastSeenId = Math.max(lastSeenId, data.latest_id);
    } catch {
        // Silently ignore polling failures; the next poll will retry.
    } finally {
        isPolling = false;
    }
}

function pollWhenVisible(): void {
    if (document.visibilityState === 'visible') {
        void pollPendingRequests();
    }
}

onMounted(() => {
    if (!enabled.value) {
        return;
    }

    soundEnabled.value = readSoundPreference();

    document.addEventListener('pointerdown', unlockAudio);
    document.addEventListener('keydown', unlockAudio);
    document.addEventListener('visibilitychange', pollWhenVisible);

    void pollPendingRequests();

    pollTimer = window.setInterval(() => {
        void pollPendingRequests();
    }, POLL_INTERVAL_MS);
});

onBeforeUnmount(() => {
    if (pollTimer !== null) {
        window.clearInterval(pollTimer);
    }

    if (hideTimer !== null) {
        window.clearTimeout(hideTimer);
    }

    document.removeEventListener('pointerdown', unlockAudio);
    document.removeEventListener('keydown', unlockAudio);
    document.removeEventListener('visibilitychange', pollWhenVisible);

    if (audioContext) {
        void audioContext.close();
        audioContext = null;
    }
});
</script>

<template>
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="translate-y-4 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="visible"
            class="fixed right-4 bottom-4 z-[100] w-[min(92vw,22rem)] overflow-hidden rounded-2xl border border-amber-200 bg-white shadow-2xl shadow-amber-200/60"
        >
            <div
                class="flex items-start gap-3 bg-gradient-to-r from-amber-500 to-orange-500 px-4 py-3 text-white"
            >
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/20"
                >
                    <BellRing class="h-5 w-5" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-bold">Nueva solicitud de compra</p>
                    <p class="text-xs text-white/90">
                        {{
                            count === 1
                                ? 'Hay 1 solicitud pendiente de confirmar.'
                                : `Hay ${count} solicitudes pendientes de confirmar.`
                        }}
                    </p>
                </div>
                <button
                    type="button"
                    class="rounded-full p-1 transition-colors hover:bg-white/20"
                    :aria-label="
                        soundEnabled ? 'Desactivar sonido' : 'Activar sonido'
                    "
                    :title="
                        soundEnabled ? 'Desactivar sonido' : 'Activar sonido'
                    "
                    @click="toggleSound"
                >
                    <Volume2 v-if="soundEnabled" class="h-4 w-4" />
                    <VolumeX v-else class="h-4 w-4" />
                </button>
                <button
                    type="button"
                    class="rounded-full p-1 transition-colors hover:bg-white/20"
                    aria-label="Cerrar notificación"
                    @click="visible = false"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>
            <div class="flex items-center justify-between gap-3 px-4 py-3">
                <p class="text-xs text-slate-500">
                    El stock se descontará cuando confirmes.
                </p>
                <Link
                    :href="ventasIndex().url"
                    class="inline-flex shrink-0 items-center rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-slate-700"
                >
                    Ver en Ventas
                </Link>
            </div>
        </div>
    </Transition>
</template>
