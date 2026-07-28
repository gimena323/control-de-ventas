<script setup lang="ts">
import { CalendarDays } from 'lucide-vue-next';
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    modelValue?: string | null;
    class?: HTMLAttributes['class'];
    placeholder?: string;
    id?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

function onInput(event: Event) {
    const target = event.target as HTMLInputElement;
    emit('update:modelValue', target.value);
}
</script>

<template>
    <div class="relative">
        <input
            :id="id"
            type="date"
            :value="modelValue ?? ''"
            :placeholder="placeholder ?? 'Seleccionar fecha'"
            :class="cn(
                'file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
                'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
                'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
                'pr-9',
                props.class,
            )"
            @input="onInput"
        />
        <CalendarDays
            class="pointer-events-none absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
        />
    </div>
</template>
