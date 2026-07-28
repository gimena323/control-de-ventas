<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Check, Package, Search, ShoppingBag, X } from 'lucide-vue-next';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';

type VarianteDetalle = {
    id: number;
    color_nombre: string;
    color_hex: string;
    talla: string;
    stock: number;
    disponible: boolean;
};

type ColorResumen = {
    color_nombre: string;
    color_hex: string;
    stock_total: number;
    disponible: boolean;
};

type TallaResumen = {
    talla: string;
    stock_total: number;
    disponible: boolean;
};

type Producto = {
    id: number;
    nombre: string;
    descripcion: string;
    precio_venta: number;
    imagen: string | null;
    categoria: string;
    marca: string;
    modelo: string;
    talla: string;
    color: string;
    color_hex: string;
    stock: number;
    disponible: boolean;
    variantes: VarianteDetalle[];
    colores: ColorResumen[];
    tallas: TallaResumen[];
};

type Categoria = {
    id: number;
    nombre: string;
};

type Props = {
    productos: Producto[];
    categorias: Categoria[];
    filtros: {
        search?: string;
        categoria_id?: string;
        marca_id?: string;
    };
};

const props = defineProps<Props>();

const search = ref(props.filtros.search ?? '');
const categoriaId = ref(props.filtros.categoria_id ?? '');

function applyFilters() {
    const params: Record<string, string> = {};
    if (search.value) params.search = search.value;
    if (categoriaId.value && categoriaId.value !== 'all') params.categoria_id = categoriaId.value;

    router.get('/catalogo', params, { preserveState: true, replace: true });
}

function clearFilters() {
    search.value = '';
    categoriaId.value = '';
    applyFilters();
}

function formatCurrency(value: string | number): string {
    const num = typeof value === 'string' ? parseFloat(value) : value;
    return new Intl.NumberFormat('es-BO', { style: 'currency', currency: 'BOB', minimumFractionDigits: 2 }).format(num);
}

function isLightColor(hex: string): boolean {
    if (!hex || !hex.startsWith('#')) return false;
    const r = parseInt(hex.slice(1, 3), 16);
    const g = parseInt(hex.slice(3, 5), 16);
    const b = parseInt(hex.slice(5, 7), 16);
    const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
    return luminance > 0.6;
}
</script>

<template>
    <Head title="Catálogo de Productos" />

    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <div class="flex items-center justify-between">
            <Heading title="Catálogo de Productos" description="Explora nuestra colección de ropa y accesorios." />
            <Badge variant="secondary" class="text-sm">
                <ShoppingBag class="mr-1 h-4 w-4" />
                {{ productos.length }} productos
            </Badge>
        </div>

        <!-- Filters -->
        <div class="overflow-hidden rounded-xl border bg-glass">
            <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-3">
                <div class="grid gap-2">
                    <Label>Buscar</Label>
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            v-model="search"
                            placeholder="Nombre o descripción..."
                            class="pl-9"
                            @keyup.enter="applyFilters"
                        />
                    </div>
                </div>
                <div class="grid gap-2">
                    <Label>Categoría</Label>
                    <Select v-model="categoriaId">
                        <SelectTrigger>
                            <SelectValue placeholder="Todas las categorías" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todas las categorías</SelectItem>
                            <SelectItem v-for="c in categorias" :key="c.id" :value="String(c.id)">
                                {{ c.nombre }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="flex items-end gap-2">
                    <Button @click="applyFilters" class="flex-1">
                        <Search class="mr-2 h-4 w-4" />
                        Buscar
                    </Button>
                    <Button variant="outline" @click="clearFilters">
                        Limpiar
                    </Button>
                </div>
            </div>
        </div>

        <!-- Products Grid -->
        <div v-if="productos.length > 0" class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <div
                v-for="producto in productos"
                :key="producto.id"
                class="group overflow-hidden rounded-xl border bg-glass transition-all duration-300 hover:shadow-lg hover:-translate-y-1"
            >
                <!-- Image -->
                <div class="relative aspect-square overflow-hidden bg-muted/30">
                    <img
                        v-if="producto.imagen"
                        :src="producto.imagen"
                        :alt="producto.nombre"
                        class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
                    />
                    <div v-else class="flex h-full items-center justify-center">
                        <Package class="h-16 w-16 text-muted-foreground/30" />
                    </div>

                    <!-- Disponibilidad badge -->
                    <div class="absolute right-2 top-2">
                        <Badge v-if="producto.disponible" variant="default" class="bg-green-500 text-white text-xs">
                            Disponible
                        </Badge>
                        <Badge v-else variant="destructive" class="text-xs">
                            Agotado
                        </Badge>
                    </div>
                </div>

                <!-- Info -->
                <div class="p-4">
                    <div class="mb-1 flex items-start justify-between gap-2">
                        <h3 class="font-semibold leading-tight line-clamp-1">{{ producto.nombre }}</h3>
                    </div>
                    <p class="text-muted-foreground mb-3 text-xs line-clamp-2">
                        {{ producto.descripcion || 'Sin descripción' }}
                    </p>
                    <div class="mb-3 flex flex-wrap gap-1.5">
                        <Badge variant="outline" class="text-[10px]">{{ producto.categoria }}</Badge>
                        <Badge variant="outline" class="text-[10px]">{{ producto.marca }}</Badge>
                        <Badge variant="secondary" class="text-[10px]">{{ producto.color }}</Badge>
                    </div>

                    <!-- Colores disponibles (variantes) -->
                    <div v-if="producto.colores && producto.colores.length > 0" class="mb-2">
                        <p class="text-muted-foreground mb-1.5 text-[10px] font-medium uppercase tracking-wide">Colores</p>
                        <div class="flex flex-wrap gap-1.5">
                            <TooltipProvider v-for="(color, ci) in producto.colores" :key="ci" :delay-duration="200">
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <span
                                            class="inline-block h-5 w-5 rounded-full border-2 transition-transform hover:scale-110"
                                            :class="color.disponible
                                                ? 'border-gray-300 shadow-sm'
                                                : 'border-gray-200 opacity-50'"
                                            :style="{ backgroundColor: color.color_hex }"
                                        />
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <p class="text-xs font-medium">{{ color.color_nombre }}</p>
                                        <p class="text-xs" :class="color.disponible ? 'text-green-400' : 'text-red-400'">
                                            {{ color.disponible ? `✅ En stock (${color.stock_total})` : '❌ Sin stock' }}
                                        </p>
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        </div>
                    </div>

                    <!-- Tallas disponibles (variantes) -->
                    <div v-if="producto.tallas && producto.tallas.length > 0" class="mb-3">
                        <p class="text-muted-foreground mb-1.5 text-[10px] font-medium uppercase tracking-wide">Tallas</p>
                        <div class="flex flex-wrap gap-1">
                            <Badge
                                v-for="(talla, ti) in producto.tallas"
                                :key="ti"
                                :variant="talla.disponible ? 'secondary' : 'outline'"
                                class="text-[10px]"
                                :class="!talla.disponible && 'opacity-50 line-through'"
                            >
                                {{ talla.talla }}
                            </Badge>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <p class="text-lg font-bold text-rose-600">{{ formatCurrency(producto.precio_venta) }}</p>
                        <span class="text-muted-foreground text-xs">Stock: {{ producto.stock }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="overflow-hidden rounded-xl border bg-glass">
            <div class="px-6 py-16 text-center">
                <Package class="mx-auto mb-3 h-12 w-12 text-muted-foreground opacity-40" />
                <p class="font-medium text-muted-foreground">No se encontraron productos.</p>
                <p class="text-muted-foreground text-xs">Intenta ajustar los filtros de búsqueda.</p>
            </div>
        </div>
    </div>
</template>
