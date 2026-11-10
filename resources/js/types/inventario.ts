import type { Color, Marca, Talla } from '@/types/productos';

export type ProductoInventario = {
    id: number;
    categoria_id: number;
    marca_id: number;
    talla_id: number;
    color_id: number;
    nombre: string;
    descripcion: string | null;
    precio_venta: string;
    imagen: string | null;
    genero?: string;
    created_at: string;
    updated_at: string;
    categoria?: { id: number; nombre: string };
    marca?: Marca;
    talla?: Talla;
    color?: Color;
    variantes?: Array<{
        id: number;
        talla_id: number;
        stock: number;
        talla?: Talla;
    }>;
};

export type Inventario = {
    id: number;
    producto_id: number;
    stock_actual: number;
    disponible: boolean;
    total_entradas: number;
    total_salidas: number;
    created_at: string;
    updated_at: string;
    producto?: ProductoInventario;
};

export type InventarioMovimiento = {
    id: number;
    inventario_id: number;
    producto: string;
    tipo: 'entrada' | 'salida';
    cantidad: number;
    motivo: string | null;
    origen_tipo: 'compra' | 'venta' | 'anulacion_venta' | null;
    origen_id: number | null;
    origen: string;
    user_id: number | null;
    created_at: string;
    updated_at: string;
    user?: { id: number; name: string };
};
