export type VentaDetalle = {
    id: number;
    venta_id: number;
    producto_id: number | null;
    producto_variante_id: number | null;
    producto: string;
    talla: string | null;
    color: string | null;
    cantidad: number;
    precio_unitario: string | number;
    descuento: string | number;
    subtotal: string | number;
    producto_relacion?: {
        id: number;
        nombre: string;
    } | null;
    variante?: {
        id: number;
        talla?: { id: number; nombre: string } | null;
        color?: { id: number; nombre: string; codigo_hex?: string } | null;
    } | null;
};

export type Venta = {
    id: number;
    cliente_id: number;
    user_id: number;
    numero_venta: string;
    total: string | number;
    ganancia: string | number;
    fecha: string;
    observaciones: string | null;
    estado: string;
    anulada_en: string | null;
    anulada_por_user_id: number | null;
    cliente: {
        id: number;
        nombre: string;
        apellido: string | null;
        ci_nit?: string;
        telefono?: string;
        email?: string;
    } | null;
    user: {
        id: number;
        name?: string;
        nombres?: string;
        apellido_paterno?: string;
        apellido_materno?: string;
    } | null;
    anulada_por?: {
        id: number;
        name?: string;
        nombres?: string;
        apellido_paterno?: string;
        apellido_materno?: string;
    } | null;
    detalles: VentaDetalle[];
    created_at: string;
    updated_at: string;
};
