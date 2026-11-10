<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { TrendingUp, Users } from 'lucide-vue-next';
import { index as clientesIndex } from '@/routes/clientes';
import { index as ventasIndex } from '@/routes/ventas';

defineProps<{
    active: 'clientes' | 'ventas';
}>();

const tabs = [
    {
        id: 'clientes' as const,
        label: 'Clientes',
        icon: Users,
        href: clientesIndex(),
    },
    {
        id: 'ventas' as const,
        label: 'Ventas',
        icon: TrendingUp,
        href: ventasIndex(),
    },
];
</script>

<template>
    <div
        class="bg-glass flex flex-wrap items-center gap-1 rounded-xl border p-1"
    >
        <Link
            v-for="tab in tabs"
            :key="tab.id"
            :href="tab.href"
            :class="[
                'flex items-center gap-2 rounded-lg px-5 py-2.5 text-sm font-medium transition-all',
                active === tab.id
                    ? 'bg-primary text-primary-foreground shadow-md'
                    : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground',
            ]"
        >
            <component :is="tab.icon" class="h-4 w-4" />
            {{ tab.label }}
        </Link>
    </div>
</template>
