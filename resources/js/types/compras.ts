export type CompraDetalle = {
    id: number;
    compra_id: number;
    producto_id: number;
    producto_variante_id: number | null;
    producto: string;
    talla: string | null;
    color: string | null;
    cantidad: number;
    precio_unitario: string | number;
    subtotal: string | number;
    producto_relacion?: {
        id: number;
        talla?: { id: number; nombre: string } | null;
        color?: { id: number; nombre: string; codigo_hex?: string } | null;
    } | null;
    variante?: {
        id: number;
        talla?: { id: number; nombre: string } | null;
        color?: { id: number; nombre: string; codigo_hex?: string } | null;
    } | null;
};

export type Compra = {
    id: number;
    proveedor_id: number;
    user_id: number;
    numero_compra: string;
    total: string | number;
    fecha: string;
    observaciones: string | null;
    estado: 'completada' | 'anulada';
    anulada_en: string | null;
    anulada_por_user_id: number | null;
    proveedor: {
        id: number;
        nombre: string;
    } | null;
    user: {
        id: number;
        name?: string;
        nombres?: string;
        apellido_paterno?: string;
        apellido_materno?: string;
    } | null;
    detalles: CompraDetalle[];
    created_at: string;
    updated_at: string;
};
