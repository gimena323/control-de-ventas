<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <img
        src="/images/icono-emma-fashion-ef.png"
        alt="Ícono de Emma Fashion"
        :class="className"
        v-bind="$attrs"
    />
</template>
