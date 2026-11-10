<script setup lang="ts">
import { Check, Eye, Lightbulb, Palette } from 'lucide-vue-next';
import { computed, watch } from 'vue';

type PaletteColor = { name: string; hex: string };

const props = defineProps<{ name: string; hex: string }>();
const emit = defineEmits<{
    'update:name': [value: string];
    'update:hex': [value: string];
}>();

const colors: PaletteColor[] = [
    { name: 'Blanco', hex: '#FFFFFF' },
    { name: 'Marfil', hex: '#FFFFF0' },
    { name: 'Crema', hex: '#FFFDD0' },
    { name: 'Beige', hex: '#D6B89C' },
    { name: 'Arena', hex: '#C2B280' },
    { name: 'Amarillo Pastel', hex: '#FFF59D' },
    { name: 'Amarillo', hex: '#FACC15' },
    { name: 'Mostaza', hex: '#D4A017' },
    { name: 'Dorado', hex: '#D4AF37' },
    { name: 'Naranja', hex: '#F97316' },
    { name: 'Naranja Quemado', hex: '#CC5500' },
    { name: 'Durazno', hex: '#FFCBA4' },
    { name: 'Coral', hex: '#FB7185' },
    { name: 'Rojo', hex: '#EF4444' },
    { name: 'Rojo Carmesí', hex: '#DC143C' },
    { name: 'Rojo Ladrillo', hex: '#B22222' },
    { name: 'Bordó', hex: '#800020' },
    { name: 'Vinotinto', hex: '#722F37' },
    { name: 'Rosa Bebé', hex: '#F4C2C2' },
    { name: 'Rosa Pastel', hex: '#F9A8D4' },
    { name: 'Rosa', hex: '#EC4899' },
    { name: 'Fucsia', hex: '#D946EF' },
    { name: 'Magenta', hex: '#FF00FF' },
    { name: 'Palo Rosa', hex: '#D8A0A6' },
    { name: 'Salmón', hex: '#FA8072' },
    { name: 'Lila', hex: '#C8A2C8' },
    { name: 'Lavanda', hex: '#E6E6FA' },
    { name: 'Malva', hex: '#E0B0FF' },
    { name: 'Violeta', hex: '#8F00FF' },
    { name: 'Morado', hex: '#9333EA' },
    { name: 'Púrpura', hex: '#800080' },
    { name: 'Uva', hex: '#6F2DA8' },
    { name: 'Índigo', hex: '#4F46E5' },
    { name: 'Azul Marino', hex: '#172554' },
    { name: 'Azul Rey', hex: '#1D4ED8' },
    { name: 'Azul Eléctrico', hex: '#0066FF' },
    { name: 'Azul', hex: '#3B82F6' },
    { name: 'Azul Acero', hex: '#4682B4' },
    { name: 'Azul Cielo', hex: '#0EA5E9' },
    { name: 'Azul Claro', hex: '#87CEEB' },
    { name: 'Celeste', hex: '#38BDF8' },
    { name: 'Turquesa', hex: '#14B8A6' },
    { name: 'Verde Agua', hex: '#7FFFD4' },
    { name: 'Verde Menta', hex: '#98FF98' },
    { name: 'Verde Lima', hex: '#84CC16' },
    { name: 'Verde', hex: '#22C55E' },
    { name: 'Verde Esmeralda', hex: '#10B981' },
    { name: 'Verde Olivo', hex: '#808000' },
    { name: 'Verde Militar', hex: '#4B5320' },
    { name: 'Verde Botella', hex: '#006A4E' },
    { name: 'Verde Bosque', hex: '#228B22' },
    { name: 'Café Claro', hex: '#B5651D' },
    { name: 'Café', hex: '#6F4E37' },
    { name: 'Café Oscuro', hex: '#3B2414' },
    { name: 'Marrón Claro', hex: '#A0522D' },
    { name: 'Marrón', hex: '#92400E' },
    { name: 'Chocolate', hex: '#7B3F00' },
    { name: 'Camello', hex: '#C19A6B' },
    { name: 'Caqui', hex: '#BDB76B' },
    { name: 'Gris Claro', hex: '#D4D4D4' },
    { name: 'Gris', hex: '#737373' },
    { name: 'Plomo', hex: '#6B7280' },
    { name: 'Gris Oscuro', hex: '#404040' },
    { name: 'Plateado', hex: '#C0C0C0' },
    { name: 'Carbón', hex: '#36454F' },
    { name: 'Negro', hex: '#171717' },
];

function normalizeColorName(value: string): string {
    return value
        .trim()
        .toLocaleLowerCase('es')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');
}

const normalizedName = computed(() => normalizeColorName(props.name));
const relatedColors = computed(() => {
    if (!normalizedName.value) {
        return colors;
    }

    const family = normalizedName.value.split(/\s+/)[0];
    const matches = colors.filter((color) => {
        const name = normalizeColorName(color.name);

        return name.includes(normalizedName.value) || name.includes(family);
    });

    return matches.length ? matches : colors;
});
const selected = computed(
    () => colors.find((color) => color.hex === props.hex) ?? colors[0],
);

watch(
    () => props.name,
    (value) => {
        const normalized = normalizeColorName(value);

        if (!normalized) {
            return;
        }

        const exact = colors.find(
            (color) => normalizeColorName(color.name) === normalized,
        );
        const family = colors.find((color) =>
            normalizeColorName(color.name).includes(normalized.split(/\s+/)[0]),
        );
        const match = exact ?? family;

        if (match && match.hex !== props.hex) {
            emit('update:hex', match.hex);
        }
    },
);

function selectColor(color: PaletteColor): void {
    emit('update:name', color.name);
    emit('update:hex', color.hex);
}
</script>

<template>
    <div
        class="grid gap-4 border-t border-dashed pt-4 lg:grid-cols-[1fr_190px]"
    >
        <section>
            <div class="mb-3 flex items-center gap-2">
                <Palette class="size-5 text-pink-600" />
                <div>
                    <p class="font-semibold">Paleta de colores</p>
                    <p class="text-xs text-muted-foreground">
                        Haz clic sobre el color deseado
                    </p>
                </div>
            </div>
            <div
                class="mx-auto grid max-w-md grid-cols-9 justify-items-center gap-1 py-1"
            >
                <button
                    v-for="color in relatedColors"
                    :key="color.hex"
                    type="button"
                    :title="color.name"
                    :aria-label="`Seleccionar ${color.name}`"
                    class="relative size-8 transition hover:z-10 hover:scale-110 focus-visible:outline-none"
                    :class="
                        color.hex === selected.hex
                            ? 'z-10 scale-110 ring-2 ring-pink-500 ring-offset-2'
                            : ''
                    "
                    :style="{
                        backgroundColor: color.hex,
                        clipPath:
                            'polygon(25% 6%, 75% 6%, 100% 50%, 75% 94%, 25% 94%, 0 50%)',
                    }"
                    @click="selectColor(color)"
                >
                    <span
                        v-if="color.hex === selected.hex"
                        class="absolute inset-1 flex items-center justify-center text-white drop-shadow"
                        ><Check class="size-4 stroke-[3]"
                    /></span>
                </button>
            </div>
        </section>
        <aside class="border-t pt-5 lg:border-t-0 lg:border-l lg:pt-0 lg:pl-5">
            <div class="mb-3 flex items-center gap-2">
                <Eye class="size-5 text-pink-600" />
                <p class="font-semibold">Vista previa</p>
            </div>
            <div
                class="h-20 rounded-xl border shadow-inner"
                :style="{ backgroundColor: selected.hex }"
            />
            <p class="mt-2 font-bold">{{ selected.name }}</p>
            <div
                class="mt-3 flex gap-2 rounded-lg border border-pink-100 bg-pink-50 p-2.5 text-xs text-pink-900"
            >
                <Lightbulb class="mt-0.5 size-4 shrink-0 text-pink-600" />
                <p>
                    <strong>Consejo:</strong> Elige un nombre claro para
                    identificar fácilmente el color.
                </p>
            </div>
        </aside>
    </div>
</template>
