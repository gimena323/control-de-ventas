Actúa como desarrollador senior Laravel especializado en seguridad, autenticación y control de acceso basado en roles (RBAC).

Tu tarea es implementar **autenticación, roles y permisos** de modo que cada usuario solo acceda a las funciones que su rol permite.


==================================================
0) MODELO DE SEGURIDAD (CONCEPTO)
==================================================

El sistema NO usa solo un campo `roles` en texto libre. Combina:

1. **Autenticación** — Laravel Fortify (login, logout, 2FA opcional, sin registro público).
2. **Roles** — tabla `roles` (un usuario puede tener solo uno rol). 
3. **Permisos** — tabla `permissions` (cada rol tiene muchos permisos).
4. **Autorización en rutas** — middleware `role` y `permission` en el backend.
5. **Autorización en UI** — composable Vue `usePermissions()` que oculta menús y botones.
6. **Columna legacy `users.role`** — se mantiene por compatibilidad y se sincroniza al login.

Regla de oro: **nunca confiar solo en el frontend**. Toda ruta y acción sensible debe validarse en el servidor con middleware o `$user->hasPermission()`.

==================================================
1) STACK REQUERIDO
==================================================

- PHP 8.3+
- Laravel 12 o 13
- Laravel Fortify (autenticación)
- Inertia.js v3 + Vue 3
- Pest (tests de autorización)
- MySQL o SQLite

==================================================
2) ESQUEMA DE BASE DE DATOS
==================================================

Crear estas tablas (nombres exactos recomendados):

**roles**
- id
- nombre (string 50, unique) — ej: `admin`, `cajera`, `operador`
- descripcion (nullable)
- timestamps
- user_id (FK users, cascade)

**permissions**
- id
- nombre (string 80, unique) — ej: `pagos.gestionar`
- descripcion (nullable)
- grupo (nullable) — ej: `pagos`, `afiliados`
- timestamps

**permission_role** (pivote rol ↔ permiso)
- permission_id (FK)
- role_id (FK)
- unique(permission_id, role_id)

**users** (columna adicional legacy)
- role (string nullable) — ej: `admin`, `cajera` (se sincroniza a `role_user` al iniciar sesión)

Comandos sugeridos:
```bash
php artisan make:migration create_roles_table
php artisan make:migration create_permissions_table
php artisan migrate
```

==================================================
3) ENUM DE PERMISOS (PHP)
==================================================

Crear `app/Enums/Permission.php` como enum backed string.

Ejemplo de permisos (como en ISINUTA):

Ver panel principal 
Listar afiliados 
Crear/editar/eliminar afiliados
Ver pagos 
Registrar y cobrar pagos
Ver multas
Registrar y cobrar multas
Ver trámites
Crear trámites 
Aprobar/rechazar trámites
Reporte de recaudación
Reporte de deudas 
Gestionar usuarios del sistema

Métodos estáticos obligatorios en el enum:
- `forAdministrador(): array` — devuelve TODOS los permisos.
- `forCajera(): array` — devuelve solo permisos de operación diaria (sin gestionar afiliados, sin aprobar trámites, sin reportes.deudas, sin usuarios.gestionar).
- `valuesForAdministrador(): array` — strings.
- `valuesForCajera(): array` — strings.
- `label(): string` y `group(): string` por cada case.

==================================================
4) MODELOS ELOQUENT
==================================================

**app/Models/Role.php**
- Constantes: `ADMIN = 'admin'`, `CAJERA = 'cajera'`, `OPERADOR = 'operador'`.
- Relaciones: `users()`, `permissions()`.
- Método `syncPermissionsByName(array $names)`.
- Método `ensureDefaultPermissions()` — si el rol no tiene permisos en BD, asigna los del enum según el nombre del rol.

**app/Models/Permission.php**
- Fillable: nombre, descripcion, grupo.
- Relación `roles()`.

**app/Models/User.php** — métodos obligatorios:

```php
public function roles(): BelongsToMany;
public function assignRole(string $roleName): void;
public function hasRole(string $role): bool;
public function hasAnyRole(array|string $roles): bool;
public function isAdmin(): bool;
public function rolesNombres(): array;
public function permissionsNombres(): array;
public function hasPermission(string $permission): bool;
public function canAccessPanel(): bool;
public function syncRolesFromLegacyColumn(): void;
public function ensureRolesHavePermissions(): void;
```

Lógica clave de `hasPermission`:
- Si `isAdmin()` → siempre `true`.
- Si no, revisar permisos cargados desde `roles.permissions`.
- Si la BD está vacía, usar fallback del enum (`defaultPermissionsForAssignedRoles`).

Lógica de `permissionsNombres`:
- Admin → todos los valores del enum.
- Otros → unión de permisos de sus roles en BD.

==================================================
5) SEEDERS
==================================================

**PermissionsSeeder**
- Recorrer `Permission::cases()` y hacer `updateOrCreate` por `nombre`.
- Obtener roles admin y cajera.
- `admin->syncPermissionsByName(PermissionEnum::valuesForAdministrador())`.
- `cajera->syncPermissionsByName(PermissionEnum::valuesForCajera())`.
- Si existe rol operador, mismos permisos que cajera.

**UsuariosSeeder** (usuarios de prueba)
| email | password | role |
|-------|----------|------|
| admin@gmail.com | admin1234 | admin |
| cajera@gmail.com | cajera1234 | cajera |
| operador@gmail.com | operador1234 | operador |

Cada usuario: `User::updateOrCreate` + `$user->assignRole($role)`.

**RolesSeeder** (opcional) — crear filas en `roles` con descripciones.

Comando de mantenimiento (crear en el proyecto):
```bash
php artisan isinuta:sync-roles
```
Debe ejecutar PermissionsSeeder y sincronizar `users.role` legacy → `role_user`.

==================================================
6) MIDDLEWARE DE AUTORIZACIÓN
==================================================

**app/Http/Middleware/EnsureUserHasRole.php**
- Parámetros: uno o más nombres de rol.
- Si no hay usuario → 401.
- Si `!$user->hasAnyRole($roles)` → 403.
- Uso: `middleware('role:admin,cajera,operador')`.

**app/Http/Middleware/EnsureUserHasPermission.php**
- Parámetros: uno o más permisos (OR lógico: basta con tener uno).
- Si no hay usuario → 401.
- Si ningún permiso coincide → 403.
- Uso: `middleware('permission:pagos.gestionar')`.

Registrar alias en `bootstrap/app.php`:
```php
$middleware->alias([
    'role' => EnsureUserHasRole::class,
    'permission' => \App\Http\Middleware\EnsureUserHasPermission::class,
]);
```

==================================================
7) RUTAS PROTEGIDAS (EJEMPLO)
==================================================

Estructura recomendada en `routes/web.php`:

```php
Route::middleware(['auth', 'verified', 'role:admin,cajera,operador'])->group(function () {

    Route::get('panel', ...)
        ->middleware('permission:panel.ver')
        ->name('panel');

    Route::middleware('permission:afiliados.ver')->group(function () {
        Route::get('afiliados', ...)->name('afiliados.index');

        Route::middleware('permission:afiliados.gestionar')->group(function () {
            Route::get('afiliados/create', ...);
            Route::post('afiliados', ...);
            // edit, update, destroy...
        });
    });

    Route::middleware('permission:usuarios.gestionar')->group(function () {
        Route::get('usuarios', ...);
        // edit, update...
    });
});
```

Patrón:
- Capa 1: `auth` + `verified` + rol general del panel.
- Capa 2: permiso de **ver** el módulo.
- Capa 3: permiso de **gestionar** acciones CRUD.

==================================================
8) COMPARTIR DATOS CON INERTIA (FRONTEND)
==================================================

En `app/Http/Middleware/HandleInertiaRequests.php`, método `share()`:

```php
if ($user) {
    $user->loadMissing('roles.permissions');
}

'auth' => [
    'user' => $user,
    'roles' => fn () => $user ? $user->rolesNombres() : [],
    'permissions' => fn () => $user ? $user->permissionsNombres() : [],
    'isAdmin' => fn () => $user?->isAdmin() ?? false,
],
```

==================================================
9) COMPOSABLE VUE — usePermissions
==================================================

Crear `resources/js/composables/usePermissions.ts`:

```typescript
export function usePermissions() {
    const page = usePage();
    const auth = computed(() => page.props.auth ?? {});

    const can = (permission: string): boolean => {
        if (auth.value.isAdmin || auth.value.roles?.includes('admin')) {
            return true;
        }
        return (auth.value.permissions ?? []).includes(permission);
    };

    const hasRole = (...roles: string[]) =>
        roles.some((r) => (auth.value.roles ?? []).includes(r));

    return { can, hasRole, roles, permissions, isAdmin };
}
```

Uso en sidebar (`AppSidebar.vue`):

```typescript
const allNavItems = [
    { title: 'Panel', href: '/panel', permission: 'panel.ver' },
    { title: 'Afiliados', href: '/afiliados', permission: 'afiliados.ver' },
    { title: 'Gestión de usuarios', href: '/usuarios', permission: 'usuarios.gestionar' },
];

const mainNavItems = computed(() =>
    allNavItems.filter((item) => !item.permission || can(item.permission))
);
```

Uso en botones de acción:

```vue
<Link v-if="can('afiliados.gestionar')" :href="createUrl">
    <Button>Nuevo afiliado</Button>
</Link>
```

==================================================
10) LOGIN Y REDIRECCIÓN POST-AUTH
==================================================

**Registro público deshabilitado** en `config/fortify.php`:
- Quitar `Features::registration()` del array `features`.
- No mostrar "Registrarse" en Welcome ni Login.

**LoginResponse personalizado** (`app/Http/Responses/LoginResponse.php`):
Al autenticar:
1. `$user->syncRolesFromLegacyColumn()`
2. `$user->ensureRolesHavePermissions()`
3. Redirigir según permisos (trait `RedirectsUsersByRole`):
   - Sin acceso al panel → `/` (home).
   - Con `panel.ver` → `/panel`.
   - Si no, primera ruta permitida (pagos, afiliados, etc.).

Registrar en `FortifyServiceProvider`:
```php
$this->app->singleton(LoginResponseContract::class, LoginResponse::class);
```

==================================================
11) MATRIZ DE PERMISOS POR ROL (REFERENCIA)
==================================================

| Permiso | Admin | Cajera / Operador |
|---------|:-----:|:-----------------:|
| panel.ver | ✓ | ✓ |
| afiliados.ver | ✓ | ✓ |
| afiliados.gestionar | ✓ | ✗ |
| pagos.ver | ✓ | ✓ |
| pagos.gestionar | ✓ | ✓ |
| multas.ver | ✓ | ✓ |
| multas.gestionar | ✓ | ✓ |
| tramites.ver | ✓ | ✓ |
| tramites.gestionar | ✓ | ✓ |
| tramites.aprobar | ✓ | ✗ |
| reportes.ver | ✓ | ✓ |
| reportes.deudas | ✓ | ✗ |
| usuarios.gestionar | ✓ | ✗ |

Admin: `hasPermission()` siempre true (atajo en código).

==================================================
12) TESTS OBLIGATORIOS (PEST)
==================================================

Crear `tests/Feature/RolePermissionsTest.php`:

```php
beforeEach(fn () => $this->seed(PermissionsSeeder::class));

function userWithRole(string $roleName): User {
    $user = User::factory()->create(['role' => $roleName]);
    $role = Role::where('nombre', $roleName)->firstOrFail();
    $user->roles()->sync([$role->id]);
    return $user->fresh();
}
```

Casos mínimos:
1. Admin accede a `afiliados.create` → assertOk.
2. Admin accede a `reportes.deudas` → assertOk.
3. Cajera accede a `afiliados.index` → assertOk.
4. Cajera en `afiliados.create` → assertForbidden (403).
5. Cajera en `pagos.create` → assertOk.
6. Cajera en `tramites.aprobar` → assertForbidden.
7. Cajera en `reportes.deudas` → assertForbidden.
8. `hasPermission('tramites.aprobar')` true solo para admin.

Crear también `tests/Feature/UsuarioGestionTest.php`:
- Admin → `usuarios.index` assertOk.
- Cajera → assertForbidden.

Ejecutar:
```bash
php artisan test --compact tests/Feature/RolePermissionsTest.php
```

==================================================
13) CHECKLIST DE IMPLEMENTACIÓN (PARA ESTUDIANTES)
==================================================

Base de datos:
- [ ] Migraciones roles, role_user, permissions, permission_role
- [ ] Columna users.role (legacy) si aplica

Backend:
- [ ] Enum Permission con forAdministrador / forCajera
- [ ] Modelos Role, Permission con relaciones
- [ ] User: assignRole, hasRole, hasPermission, permissionsNombres
- [ ] PermissionsSeeder + UsuariosSeeder
- [ ] Comando isinuta:sync-roles
- [ ] Middleware role y permission registrados
- [ ] Rutas agrupadas por permiso
- [ ] HandleInertiaRequests comparte roles y permissions
- [ ] LoginResponse sincroniza roles al entrar
- [ ] Registro público deshabilitado

Frontend:
- [ ] Composable usePermissions
- [ ] Sidebar filtra por can(permission)
- [ ] Botones CRUD ocultos sin permiso
- [ ] Sin enlaces a /register

Calidad:
- [ ] Tests Pest admin vs cajera (mínimo 8 casos)
- [ ] vendor/bin/pint en archivos PHP tocados
- [ ] php artisan isinuta:sync-roles después de cambiar permisos

==================================================
14) ERRORES COMUNES A EVITAR
==================================================

1. **403 al iniciar sesión** — permisos no sembrados en BD. Solución: `php artisan db:seed --class=PermissionsSeeder` o `isinuta:sync-roles`.
2. **Menú visible pero ruta 403** — falta middleware en la ruta; corregir backend, no solo ocultar UI.
3. **UI oculta pero API expuesta** — siempre middleware `permission:` en rutas.
4. **Confiar en `users.role` sin pivote** — llamar `syncRolesFromLegacyColumn()` al login.
5. **Admin sin permisos en BD** — `isAdmin()` debe retornar true en `hasPermission()` aunque la tabla esté vacía.
6. **Registro público abierto** — deshabilitar en Fortify; usuarios nuevos solo por admin en Gestión de usuarios.

==================================================
15) ENTREGABLES QUE DEBES ENTREGAR
==================================================

Al finalizar la implementación, documenta brevemente:

1. Diagrama textual: Usuario → Roles → Permisos → Rutas.
2. Lista de archivos creados/modificados.
3. Matriz rol-permiso actualizada.
4. Captura o descripción de prueba manual:
   - Login admin → ve todos los menús.
   - Login cajera → no ve Gestión de usuarios ni Crear afiliado.
5. Salida de tests Pest (todos en verde).

==================================================
16) ORDEN SUGERIDO DE DESARROLLO (PASO A PASO)
==================================================

Paso 1 — Migraciones y modelos Role, Permission, relaciones en User.
Paso 2 — Enum Permission + PermissionsSeeder.
Paso 3 — Middleware role y permission + registro en bootstrap/app.php.
Paso 4 — Proteger rutas en web.php (empezar por un módulo: pagos).
Paso 5 — HandleInertiaRequests + usePermissions + sidebar.
Paso 6 — LoginResponse + sync al login + deshabilitar registro.
Paso 7 — Tests Pest admin vs cajera.
Paso 8 — Módulo usuarios.gestionar solo para admin.
Paso 9 — Comando sync-roles y documentación para el equipo.

==================================================
17) PROMPT CORTO PARA CODEX (COPIAR EN CADA SESIÓN)
==================================================

"Implementa el sistema RBAC del proyecto ISINUTA: tablas roles/permissions y pivotes, enum Permission, métodos hasRole/hasPermission en User, middleware role y permission, rutas protegidas por permiso, share de roles/permissions en Inertia, composable usePermissions en Vue, sidebar filtrado, LoginResponse con sync de roles, registro público deshabilitado, seeders y tests Pest admin vs cajera. Sigue el archivo PROMPT_CODEX_ROLES_PERMISOS_ISINUTA.txt y el código de referencia en appweb_gestionaguaisinuta."

==================================================
FIN — Seguridad, roles y permisos
==================================================
