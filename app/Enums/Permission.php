<?php

namespace App\Enums;

enum Permission: string
{
    case PanelVer = 'panel.ver';
    case ProductosVer = 'productos.ver';
    case ProductosCrear = 'productos.crear';
    case ProductosEditar = 'productos.editar';
    case ProductosEliminar = 'productos.eliminar';
    case VentasVer = 'ventas.ver';
    case VentasCrear = 'ventas.crear';
    case ComprasVer = 'compras.ver';
    case ComprasCrear = 'compras.crear';
    case ComprasEditar = 'compras.editar';
    case ComprasEliminar = 'compras.eliminar';
    case InventarioVer = 'inventario.ver';
    case InventarioGestion = 'inventario.gestion';
    case ClientesVer = 'clientes.ver';
    case ClientesGestion = 'clientes.gestion';
    case ProveedoresVer = 'proveedores.ver';
    case ProveedoresGestion = 'proveedores.gestion';
    case FacturasVer = 'facturas.ver';
    case UsuariosGestion = 'usuarios.gestion';
    case ConfiguracionesGestion = 'configuraciones.gestion';

    public function label(): string
    {
        return match ($this) {
            self::PanelVer => 'Ver panel principal',
            self::ProductosVer => 'Listar productos',
            self::ProductosCrear => 'Crear productos',
            self::ProductosEditar => 'Editar productos',
            self::ProductosEliminar => 'Eliminar productos',
            self::VentasVer => 'Ver ventas',
            self::VentasCrear => 'Registrar ventas',
            self::ComprasVer => 'Ver compras',
            self::ComprasCrear => 'Registrar compras',
            self::ComprasEditar => 'Editar compras',
            self::ComprasEliminar => 'Eliminar compras',
            self::InventarioVer => 'Ver inventario',
            self::InventarioGestion => 'Gestionar inventario',
            self::ClientesVer => 'Listar clientes',
            self::ClientesGestion => 'Crear/editar/eliminar clientes',
            self::ProveedoresVer => 'Listar proveedores',
            self::ProveedoresGestion => 'Crear/editar/eliminar proveedores',
            self::FacturasVer => 'Ver facturas',
            self::UsuariosGestion => 'Gestionar usuarios del sistema',
            self::ConfiguracionesGestion => 'Gestionar configuraciones',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::PanelVer => 'panel',
            self::ProductosVer, self::ProductosCrear, self::ProductosEditar, self::ProductosEliminar => 'productos',
            self::VentasVer, self::VentasCrear => 'ventas',
            self::ComprasVer, self::ComprasCrear, self::ComprasEditar, self::ComprasEliminar => 'compras',
            self::InventarioVer, self::InventarioGestion => 'inventario',
            self::ClientesVer, self::ClientesGestion => 'clientes',
            self::ProveedoresVer, self::ProveedoresGestion => 'proveedores',
            self::FacturasVer => 'facturas',
            self::UsuariosGestion => 'usuarios',
            self::ConfiguracionesGestion => 'configuraciones',
        };
    }

    public static function forAdministrador(): array
    {
        return self::cases();
    }

    public static function forVendedor(): array
    {
        return [
            self::PanelVer,
            self::ProductosVer,
            self::VentasVer,
            self::VentasCrear,
            self::ClientesVer,
            self::FacturasVer,
        ];
    }

    public static function forEncargadoInventario(): array
    {
        return [
            self::PanelVer,
            self::ProductosVer,
            self::InventarioVer,
            self::InventarioGestion,
            self::ComprasVer,
            self::ProveedoresVer,
        ];
    }

    public static function valuesForAdministrador(): array
    {
        return array_map(fn (self $p) => $p->value, self::forAdministrador());
    }

    public static function valuesForVendedor(): array
    {
        return array_map(fn (self $p) => $p->value, self::forVendedor());
    }

    public static function valuesForEncargadoInventario(): array
    {
        return array_map(fn (self $p) => $p->value, self::forEncargadoInventario());
    }
}
