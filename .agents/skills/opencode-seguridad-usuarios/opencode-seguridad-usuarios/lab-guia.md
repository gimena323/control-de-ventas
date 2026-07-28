# Guía de laboratorio — Seguridad y usuarios (OpenCode)

Sesiones sugeridas para trabajo en aula. Cada lab incluye objetivo, tareas, criterio de aceptación y prompt base para OpenCode.

---

## Lab 1 — Explorar autenticación (45 min)

### Objetivo
Entender login, sesión y diferencia con autorización.

### Tareas

1. Iniciar sesión como `admin@isinuta.test` / `admin1234`.
2. Iniciar sesión como `cajera@isinuta.test` / `cajera1234`.
3. Ejecutar `php artisan route:list --path=login`.
4. Leer `config/fortify.php` y listar features habilitados.
5. Responder por escrito: ¿está habilitado el registro público?

### Criterio de aceptación

- Documento con captura de login exitoso de ambos usuarios.
- Respuesta: registro público deshabilitado y por qué.

### Prompt OpenCode

```text
Explícame el flujo de login de esta app Laravel Fortify.
¿Qué pasa si el usuario está inactivo?
Muéstrame los archivos involucrados sin modificar código.
```

---

## Lab 2 — Roles y permisos (60 min)

### Objetivo
Mapear RBAC del proyecto.

### Tareas

1. Dibujar tabla rol → permisos leyendo `Permission.php` y `PermissionsSeeder.php`.
2. Probar en navegador: cajera intenta abrir `/usuarios` → debe ver 403.
3. Ejecutar `php artisan test --compact --filter=UsuarioGestionTest`.
4. Explicar qué hace `EnsureUserHasPermission`.

### Criterio de aceptación

- Tabla rol/permisos entregada.
- Test `cajera cannot access user management` pasa.

### Prompt OpenCode

```text
Lista todos los permisos del enum Permission y qué roles los tienen según el seeder.
¿Por qué cajera recibe 403 en /usuarios?
```

---

## Lab 3 — Crear usuario como admin (60 min)

### Objetivo
Implementar o verificar flujo de alta de usuarios.

### Tareas

1. Como admin, crear usuario de prueba en `/usuarios/crear`.
2. Verificar en BD: `users`, `rol_usuario`, `team_members`.
3. Login con el nuevo usuario.
4. Revisar test `admin can create a new user`.

### Criterio de aceptación

- Usuario creado con rol cajera y estado activo.
- Login exitoso del nuevo usuario.
- Test pasa.

### Prompt OpenCode

```text
Revisa UsuarioController::store y dime qué validaciones de seguridad aplica.
¿Qué campos valida StoreUsuarioRequest?
Propón un test si falta alguno.
```

---

## Lab 4 — Estado activo/inactivo (60 min)

### Objetivo
Gestionar ciclo de vida de cuentas.

### Tareas

1. Admin desactiva usuario de prueba desde listado.
2. Intentar login con cuenta desactivada → debe fallar.
3. Admin reactiva la cuenta → login debe funcionar.
4. Intentar desactivarse a sí mismo → debe mostrar error.

### Criterio de aceptación

- Mensaje claro al login de inactivo.
- Auto-desactivación bloqueada.
- Tests de estado pasan.

### Prompt OpenCode

```text
Implementa o verifica updateEstado en UsuarioController.
Asegura tests para: desactivar otro usuario, no auto-desactivarse, inactivo no login.
Ejecuta php artisan test --compact --filter=UsuarioGestionTest
```

---

## Lab 5 — Auditoría de seguridad (90 min)

### Objetivo
Evaluar la app como auditor.

### Tareas

1. Revisar checklist en `SKILL.md` (sección docente).
2. Identificar 3 riesgos reales + 3 controles bien implementados.
3. Proponer **una** mejora concreta (ej. middleware usuario activo).
4. Opcional: implementar la mejora + test.

### Criterio de aceptación

- Informe markdown con:
  - Resumen ejecutivo (5 líneas)
  - Tabla riesgo / severidad / recomendación
  - PR o diff de la mejora opcional

### Prompt OpenCode

```text
Audita la seguridad del módulo de usuarios y autenticación.
Clasifica hallazgos en alta/media/baja.
Propón la mejora de mayor impacto con el diff mínimo y un test Pest.
Responde en español.
```

---

## Lab 6 — UI: rol en sidebar (30 min)

### Objetivo
Mostrar información de rol sin confundir con seguridad.

### Tareas

1. Verificar que el sidebar muestra `etiqueta_rol` del usuario logueado.
2. Explicar por qué mostrar el rol en UI **no reemplaza** middleware.

### Criterio de aceptación

- Captura con nombre + rol visible.
- Párrafo explicando autenticación vs autorización vs UI.

---

## Rúbrica simplificada (docente)

| Criterio | 0 | 1 | 2 |
|----------|---|---|---|
| Middleware/permisos | No usa o incorrecto | Parcial | Completo + tests |
| Form Request authorize | Ausente | Solo rules | rules + authorize |
| Reglas de negocio admin | Ignoradas | Parcial | Completas |
| Tests Pest | No hay | Algunos | Cubren 403/200 y edge cases |
| Explicación conceptual | Incorrecta | Básica | Clara (auth vs authz) |

**Nota:** 8–10 puntos = apto. Priorizar tests de autorización sobre estética UI.

---

## Credenciales demo (desarrollo)

| Usuario | Email | Contraseña | Rol |
|---------|-------|------------|-----|
| Admin | admin@isinuta.test | admin1234 | Administrador |
| Cajera | cajera@isinuta.test | cajera1234 | Cajera |
| Operador | operador@isinuta.test | operador1234 | Operador |

Tras `migrate:fresh --seed` o `db:seed --class=UsuariosSeeder`.
