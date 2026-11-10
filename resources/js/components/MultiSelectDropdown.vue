<script setup lang="ts">
import { Check, ChevronDown } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

type Item = { id: number; nombre: string; codigo_hex?: string };

const props = withDefaults(
    defineProps<{
        modelValue: number[];
        items: Item[];
        label?: string;
        placeholder?: string;
        showColorDot?: boolean;
    }>(),
    { placeholder: 'Seleccionar opciones', showColorDot: false },
);

const emit = defineEmits<{
    'update:modelValue': [value: number[]];
}>();

const open = ref(false);
const triggerRef = ref<HTMLElement | null>(null);
const panelRef = ref<HTMLElement | null>(null);

const selectedNames = computed(() => {
    return props.items
        .filter((item) => props.modelValue.includes(item.id))
        .map((item) => item.nombre);
});

const displayText = computed(() => {
    if (selectedNames.value.length === 0) return '';
    return selectedNames.value.join(', ');
});

function isSelected(id: number): boolean {
    return props.modelValue.includes(id);
}

function toggle(id: number) {
    const current = [...props.modelValue];
    const idx = current.indexOf(id);
    if (idx === -1) {
        current.push(id);
    } else {
        current.splice(idx, 1);
    }
    emit('update:modelValue', current);
}

function handleClickOutside(e: MouseEvent) {
    if (
        triggerRef.value &&
        !triggerRef.value.contains(e.target as Node) &&
        panelRef.value &&
        !panelRef.value.contains(e.target as Node)
    ) {
        open.value = false;
    }
}

onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener('mousedown', handleClickOutside);
});
</script>

<template>
    <div class="relative w-full">
        <button
            ref="triggerRef"
            type="button"
            class="flex h-9 w-full items-center justify-between gap-2 rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50"
            :class="open ? 'border-ring ring-[3px] ring-ring/50' : ''"
            @click="open = !open"
        >
            <span
                :class="
                    displayText ? 'text-foreground' : 'text-muted-foreground'
                "
            >
                {{ displayText || placeholder }}
            </span>
            <ChevronDown
                class="size-4 shrink-0 opacity-50 transition-transform"
                :class="open ? 'rotate-180' : ''"
            />
        </button>

        <div
            v-if="open"
            ref="panelRef"
            class="absolute z-50 mt-1 max-h-60 w-full overflow-auto rounded-md border bg-popover p-1 text-popover-foreground shadow-md"
        >
            <div
                v-for="item in items"
                :key="item.id"
                class="flex cursor-pointer items-center gap-2 rounded-sm px-2 py-1.5 text-sm transition-colors outline-none"
                :class="
                    isSelected(item.id)
                        ? 'bg-slate-100'
                        : 'hover:bg-accent hover:text-accent-foreground'
                "
                @click="toggle(item.id)"
            >
                <span
                    class="flex size-4 shrink-0 items-center justify-center rounded border transition-colors"
                    :class="
                        isSelected(item.id)
                            ? 'border-fuchsia-500 bg-fuchsia-500 text-white'
                            : 'border-slate-300 bg-white'
                    "
                >
                    <Check v-if="isSelected(item.id)" class="size-3" />
                </span>
                <span
                    v-if="showColorDot && item.codigo_hex"
                    class="inline-block h-3 w-3 shrink-0 rounded-full border shadow-sm"
                    :style="{ backgroundColor: item.codigo_hex }"
                />
                <span class="flex-1">{{ item.nombre }}</span>
            </div>
        </div>
    </div>
</template>
