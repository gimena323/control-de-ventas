import { ref, computed, watch } from 'vue';

export interface CartItem {
    id: number;
    producto_id: number;
    producto_variante_id: number | null;
    nombre: string;
    precio: number;
    imagen: string | null;
    cantidad: number;
    color: string | null;
    talla: string | null;
    stock: number;
}

const STORAGE_KEY = 'emma_fashion_cart_v2';
const LEGACY_STORAGE_KEY = 'emma_fashion_cart';

const items = ref<CartItem[]>([]);

function loadFromStorage() {
    try {
        localStorage.removeItem(LEGACY_STORAGE_KEY);

        const stored = localStorage.getItem(STORAGE_KEY);

        if (stored) {
            items.value = (JSON.parse(stored) as CartItem[]).map((item) => ({
                ...item,
                producto_variante_id: item.producto_variante_id ?? null,
                stock: Math.max(0, Number(item.stock) || 0),
            }));
        }
    } catch {
        items.value = [];
    }
}

function saveToStorage() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(items.value));
}

loadFromStorage();

watch(items, saveToStorage, { deep: true });

export function useCart() {
    const totalItems = computed(() =>
        items.value.reduce((sum, item) => sum + item.cantidad, 0),
    );

    const subtotal = computed(() =>
        items.value.reduce((sum, item) => sum + item.precio * item.cantidad, 0),
    );

    function addItem(
        producto: {
            id: number;
            nombre: string;
            precio_venta: number;
            imagen: string | null;
            stock: number;
        },
        cantidad: number = 1,
        color: string | null = null,
        talla: string | null = null,
        productoVarianteId: number | null = null,
    ) {
        const existing = items.value.find(
            (i) =>
                i.producto_id === producto.id &&
                i.color === color &&
                i.talla === talla &&
                i.producto_variante_id === productoVarianteId,
        );

        if (existing) {
            existing.stock = producto.stock;
            existing.cantidad = Math.max(
                1,
                Math.min(existing.cantidad + cantidad, producto.stock),
            );
        } else {
            items.value.push({
                id: Date.now(),
                producto_id: producto.id,
                producto_variante_id: productoVarianteId,
                nombre: producto.nombre,
                precio: producto.precio_venta,
                imagen: producto.imagen,
                cantidad: Math.max(1, Math.min(cantidad, producto.stock)),
                color,
                talla,
                stock: producto.stock,
            });
        }
    }

    function removeItem(id: number) {
        items.value = items.value.filter((i) => i.id !== id);
    }

    function updateCantidad(id: number, cantidad: number) {
        const item = items.value.find((i) => i.id === id);

        if (item) {
            item.cantidad = Math.max(1, Math.min(cantidad, item.stock));
        }
    }

    function clearCart() {
        items.value = [];
    }

    return {
        items,
        totalItems,
        subtotal,
        addItem,
        removeItem,
        updateCantidad,
        clearCart,
    };
}
