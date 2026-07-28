export type Categoria = {
    id: number;
    nombre: string;
    created_at: string;
    updated_at: string;
};

export type Marca = {
    id: number;
    nombre: string;
    created_at: string;
    updated_at: string;
};

export type Modelo = {
    id: number;
    marca_id: number;
    nombre: string;
    created_at: string;
    updated_at: string;
    marca?: Marca;
};

export type Talla = {
    id: number;
    nombre: string;
    created_at: string;
    updated_at: string;
};

export type Color = {
    id: number;
    nombre: string;
    codigo_hex: string;
    created_at: string;
    updated_at: string;
};

export type ProductoVariante = {
    id: number;
    producto_id: number;
    color_id: number;
    talla_id: number;
    stock: number;
    disponible: boolean;
    color?: Color;
    talla?: Talla;
    created_at: string;
    updated_at: string;
};

export type Producto = {
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
    estado?: string;
    created_at: string;
    updated_at: string;
    categoria?: Categoria;
    marca?: Marca;
    modelo?: Modelo;
    talla?: Talla;
    color?: Color;
    variantes?: ProductoVariante[];
};
