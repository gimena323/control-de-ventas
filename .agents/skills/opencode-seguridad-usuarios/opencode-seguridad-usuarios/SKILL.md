---
name: opencode-seguridad-usuarios
description: >-
  Guía pedagógica para desarrollar seguridad, autenticación, roles/permisos y
  gestión de usuarios en apps web Laravel + Inertia + Vue con estudiantes usando
  OpenCode. Activar cuando el usuario mencione OpenCode, clase, estudiantes,
  seguridad web, autenticación, login, roles, permisos, RBAC, tipos de usuario,
  administrador, gestión de usuarios, Fortify, middleware de permisos, o
  ejercicios de laboratorio sobre acceso y autorización.
---

# OpenCode — Seguridad, autenticación y gestión de usuarios (clase)

Skill para guiar el desarrollo **en aula** de la capa de seguridad. Stack de referencia: **Laravel 13 + Fortify + Inertia v3 + Vue 3 + Pest**.

## Skills complementarios (activar siempre)

| Dominio | Skill |
|---------|-------|
| Login, Fortify, 2FA | `fortify-development` |
| Controladores, middleware, modelos | `laravel-best-practices` |
| Tests | `pest-testing` |
| Formularios y vistas Inertia | `inertia-vue-development` |

## Objetivos de aprendizaje (sesión)

Al terminar, el estudiante debe poder explicar e implementar:

1. **Autenticación** — quién es el usuario (login, sesión, cuentas inactivas).
2. **Autorización** — qué puede hacer (roles + permisos + middleware).
3. **Gestión de usuarios** — CRUD restringido al administrador.
4. **Reglas de negocio de seguridad** — no auto-bloquearse, no quedar sin admin.
5. **Tests** — demostrar que cajera no accede y admin sí.

## Mapa mental (3 capas)

```
[1] Autenticación     → Fortify, sesión, password, estado activo/inactivo
[2] Rol               → admin | cajera | operador (grupo de permisos)
[3] Permiso           → acción concreta (ej. usuarios.gestionar, pagos.ver)
```

**Regla de clase:** autenticación ≠ autorización. Login correcto no implica acceso al módulo.

## Flujo de trabajo con OpenCode en clase

### Antes de pedir código al agente

El estudiante debe escribir en el prompt:

1. Qué módulo toca (ej. gestión de usuarios).
2. Qué rol(es) pueden acceder.
3. Qué validaciones de seguridad aplican.
4. Qué test debe pasar al final.

Plantilla de prompt para estudiantes:

```text
Contexto: app Laravel ISINUTA, módulo [X].
Objetivo: [crear/editar/proteger] [funcionalidad].
Roles: admin [sí/no], cajera [sí/no], operador [sí/no].
Permiso requerido: [nombre.permiso].
Restricciones: Form Request, middleware, tests Pest.
No cambiar archivos fuera de [lista].
```

### Orden de implementación (obligatorio)

Seguir este orden en clase; no saltar pasos:

```
- [ ] 1. Modelo de datos (roles, permisos, pivots, estado usuario)
- [ ] 2. Enum Permission + seeder de permisos por rol
- [ ] 3. Métodos en User: hasRole(), hasPermission(), isActivo()
- [ ] 4. Middleware EnsureUserHasRole / EnsureUserHasPermission
- [ ] 5. Rutas protegidas (auth → verified → role → permission)
- [ ] 6. Form Requests con authorize()
- [ ] 7. Controlador (sin lógica de auth inline)
- [ ] 8. Vista Inertia (ocultar UI ≠ seguridad; la ruta debe bloquear)
- [ ] 9. Tests Feature (403 cajera, 200 admin, reglas de negocio)
- [ ] 10. php artisan test --compact --filter=[TestName]
```

## Módulo de referencia en este proyecto

Usar **ISINUTA** como ejemplo canónico. Archivos clave:

| Pieza | Ruta |
|-------|------|
| Permisos | `app/Enums/Permission.php` |
| Roles | `app/Models/Role.php` |
| Usuario | `app/Models/User.php` |
| Middleware permiso | `app/Http/Middleware/EnsureUserHasPermission.php` |
| Middleware rol | `app/Http/Middleware/EnsureUserHasRole.php` |
| Rutas protegidas | `routes/web.php` |
| CRUD usuarios | `app/Http/Controllers/UsuarioController.php` |
| Validación | `app/Http/Requests/StoreUsuarioRequest.php`, `UpdateUsuarioRequest.php` |
| Login + inactivos | `app/Providers/FortifyServiceProvider.php` |
| Props auth | `app/Http/Middleware/HandleInertiaRequests.php` |
| Tests | `tests/Feature/UsuarioGestionTest.php` |
| Vista listado | `resources/js/pages/Usuarios/Index.vue` |

Detalle de arquitectura: [reference.md](reference.md)  
Laboratorios sugeridos: [lab-guia.md](lab-guia.md)

## Reglas de seguridad (no negociables)

### Autenticación

- Registro público **desactivado** en apps administrativas (`config/fortify.php` sin `Features::registration()`).
- Usuarios **inactivos** no pueden iniciar sesión (`Fortify::authenticateUsing` + `User::isActivo()`).
- Rate limiting en login (Fortify + `RateLimiter`).
- Contraseñas fuertes en producción (`Password::defaults()` en `AppServiceProvider`).

### Autorización

- Toda ruta sensible lleva middleware `permission:` o `role:`.
- Todo Form Request implementa `authorize()` — no confiar solo en ocultar botones en Vue.
- Admin tiene todos los permisos vía código (`User::hasPermission()`), no solo por BD.

### Gestión de usuarios (admin)

- Solo permiso `usuarios.gestionar`.
- Validar rol con `Rule::in([Role::ADMIN, Role::CAJERA, Role::OPERADOR])`.
- **No** auto-desactivarse ni quitarse rol admin a sí mismo.
- **No** dejar al sistema sin al menos un **administrador activo**.
- Crear usuario con `estado = activo`, email verificado por admin, y equipo personal (`CreateTeam`).

### Mass assignment

- `$fillable` acotado en `User`.
- Usar `$request->validated()` o arrays explícitos — nunca `$request->all()` para campos sensibles (`role`, `estado`).

## Checklist de revisión (docente)

Usar al evaluar entregas de estudiantes:

```markdown
## Seguridad — revisión rápida

- [ ] ¿Ruta sin middleware de permiso/rol en módulo restringido?
- [ ] ¿Form Request sin authorize()?
- [ ] ¿Cajera obtiene 403 en usuarios/gestión?
- [ ] ¿Tests demuestran lo anterior?
- [ ] ¿Usuario inactivo no puede login?
- [ ] ¿Último admin protegido al cambiar rol/estado?
- [ ] ¿Se ejecutó pest en el test del módulo?
```

## Comandos útiles en laboratorio

```bash
# Ver rutas del módulo usuarios
php artisan route:list --path=usuarios

# Ejecutar solo tests de usuarios
php artisan test --compact --filter=UsuarioGestionTest

# Usuarios demo (tras seed)
# admin@isinuta.test / admin1234
# cajera@isinuta.test / cajera1234
php artisan db:seed --class=UsuariosSeeder
```

## Cómo responder en clase (tono del agente)

1. Explicar **por qué** antes del código (1–2 frases).
2. Mostrar el **archivo mínimo** a tocar.
3. Proponer **un test** que pruebe la regla de seguridad.
4. Ejecutar el test antes de dar por terminado.
5. Si el estudiante pide “solo el frontend”, recordar: **la seguridad real está en el backend**.

## Errores frecuentes de estudiantes

| Error | Corrección |
|-------|------------|
| Ocultar botón en Vue y creer que está protegido | Agregar middleware + authorize() |
| Poner `role` en `$request->all()` al actualizar perfil | Separar flujos: perfil vs gestión admin |
| Olvidar `verified` middleware | Email no verificado accede al panel |
| No probar con usuario cajera | Siempre test `actingAs($cajera)->assertForbidden()` |
| Desactivar usuario pero sesión sigue activa | Middleware `EnsureUserIsActive` (mejora avanzada) |

## Mejoras avanzadas (opcional, sesión 2)

- Middleware que cierre sesión si `estado = inactivo`.
- `UserPolicy` para defensa en profundidad.
- Auditoría (log) de cambios de rol/estado.
- 2FA obligatorio para administradores.
