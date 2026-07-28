import type { Color, Marca, Modelo, Talla } from '@/types/productos';

export type ProductoInventario = {
    id: number;
    categoria_id: number;
    marca_id: number;
    modelo_id: number;
    talla_id: number;
    color_id: number;
    nombre: string;
    descripcion: string | null;
    precio_venta: string;
    imagen: string | null;
    created_at: string;
    updated_at: string;
    categoria?: { id: number; nombre: string };
    marca?: Marca;
    modelo?: Modelo;
    talla?: Talla;
    color?: Color;
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
    tipo: 'entrada' | 'salida';
    cantidad: number;
    motivo: string | null;
    user_id: number | null;
    created_at: string;
    updated_at: string;
    user?: { id: number; name: string };
};
