# MONCAO SECURE — Documentación de Desarrollo
### 0376-RA6PR1-RuizTafurCarlos

> Registro del proceso de desarrollo: prompts utilizados con Cline y errores encontrados durante el desarrollo.

---

## 📋 ÍNDICE

1. [Prompt Maestro para Cline](#prompt-maestro)
2. [Prompts de Corrección](#prompts-de-corrección)
3. [Log de Errores y Soluciones](#log-de-errores)
4. [Comandos para Arrancar la App](#comandos-para-arrancar-la-app)
5. [Credenciales de Prueba](#credenciales-de-prueba)

---

## PROMPT MAESTRO

> Prompt inicial pasado a Cline para generar el proyecto completo desde cero.

```
Eres un desarrollador web senior experto en PHP, MySQL y Bootstrap 5.
Debes construir desde cero una aplicación web completa llamada MONCAO SECURE
— Control de Acceso y Fichaje. Es un sistema de gestión de fichaje y horarios
para una empresa de 400 empleados con sede en Roses, España.

IDENTIDAD VISUAL
════════════════
Nombre: MONCAO SECURE
Subtítulo: Control de Acceso y Fichaje
Logo: texto bold "MONCAO SECURE" + icono de candado (usa emoji 🔒 o FontAwesome fa-lock)
Idioma: Español (toda la interfaz en español)
Estilo UI: inspirado en Google Classroom — limpio, card-based, moderno

Colores CSS (declara estas variables en :root):
  --color-primary:   #1A73E8
  --color-secondary: #F1F3F4
  --color-accent:    #34A853
  --color-danger:    #EA4335
  --color-dark:      #202124
  --color-white:     #FFFFFF

Fuente: Google Fonts — "Inter" para todo el proyecto.

STACK TECNOLÓGICO
═════════════════
Frontend:  HTML5 + CSS3 + JavaScript (Vanilla)
Backend:   PHP 8+
BD:        MySQL 8
Estilos:   Bootstrap 5.3 (CDN) + archivo assets/css/style.css custom
Gráficos:  Chart.js (CDN)
Emails:    PHPMailer (composer require phpmailer/phpmailer)
PDF:       TCPDF (composer require tecnickcom/tcpdf)
Iconos:    FontAwesome 6 (CDN)

ESTRUCTURA DE ARCHIVOS
══════════════════════
moncao-secure/
├── index.php
├── dashboard.php
├── logout.php
├── composer.json
├── config/
│   └── db.php
├── modules/
│   ├── fichaje.php
│   ├── horario.php
│   ├── solicitudes.php
│   ├── informes.php
│   ├── proyectos.php
│   └── perfil.php
├── admin/
│   ├── empleados.php
│   ├── proyectos.php
│   ├── solicitudes.php
│   └── fichajes.php
├── includes/
│   ├── header.php
│   ├── navbar.php
│   ├── footer.php
│   └── auth_check.php
├── mail/
│   └── notificaciones.php
├── pdf/
│   └── generar_informe.php
├── uploads/
├── assets/
│   ├── css/style.css
│   ├── js/main.js
│   └── img/
└── sql/
    └── moncao_secure.sql

BASE DE DATOS
═════════════
[Schema completo con tablas: users, departamentos, fichajes, proyectos,
proyecto_usuario, horarios, solicitudes, vacaciones, eventos]

Contraseña de prueba para todos los usuarios: moncao2024

SEGURIDAD OBLIGATORIA
═════════════════════
1. session_start() al inicio de CADA archivo PHP
2. Incluir auth_check.php en TODAS las páginas protegidas
3. PDO + prepared statements en TODAS las consultas SQL
4. htmlspecialchars() al mostrar datos del usuario en HTML
5. Validar uploads: extensión y tamaño antes de guardar
6. Nunca mostrar errores de BD — usar try/catch
7. password_hash() al guardar, password_verify() al comprobar
8. Verificar rol en CADA página del panel admin
```

---

## PROMPTS DE CORRECCIÓN

> Prompts enviados a Cline durante el proceso de corrección de errores.

---

### Corrección 1 — PHPMailer no reconocido

```
En el archivo mail/notificaciones.php añade al principio del archivo,
después de <?php, estas líneas:

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

Asegúrate de que no estén duplicadas si ya existen.
```

---

### Corrección 2 — Instalar dependencias Composer

```
Ejecuta composer install en la carpeta raíz del proyecto moncao-secure
para instalar PHPMailer y TCPDF.
```

---

### Corrección 3 — Módulos en página blanco (primera vez)

```
Los módulos modules/fichaje.php, modules/horario.php, modules/solicitudes.php,
modules/informes.php, modules/proyectos.php, modules/perfil.php y los de
admin/empleados.php, admin/proyectos.php, admin/solicitudes.php, admin/fichajes.php
muestran página en blanco. Revisa todos estos archivos, activa la visualización
de errores PHP añadiendo al inicio de cada uno:
error_reporting(E_ALL); ini_set('display_errors', 1);
y arregla los errores que encuentres.
```

---

### Corrección 4 — Variables indefinidas

```
Arregla las variables posiblemente indefinidas en estos archivos:
- admin/empleados.php línea 156 ($departamentos) y línea 194 ($empleados)
- admin/fichajes.php línea 157 ($empleados) y línea 181 ($fichajes)
- admin/proyectos.php línea 188 ($departamentos)
- modules/fichaje.php línea 198 ($proyectos)
- modules/horario.php línea 138 ($horarios)
- modules/informes.php líneas 213, 216, 233, 235 ($horasSemana y $horasProyecto)

Inicializa todas estas variables con un array vacío o valor por defecto
antes de usarlas, y asegúrate de que los módulos muestran contenido en pantalla.
```

---

### Corrección 5 — Rutas de navbar y módulos en blanco

```
Al hacer clic en cualquier módulo desde el dashboard sale 'Not Found' en los
enlaces de la navbar y página en blanco en todos los módulos.
Revisa y arregla:
1) includes/navbar.php — corrige todas las rutas de los enlaces
2) todos los archivos en modules/ y admin/ que muestran página en blanco.
El servidor corre con php -S localhost:8000 desde la carpeta raíz del proyecto.
Asegúrate de que cada módulo funciona correctamente para los 3 roles:
superadmin, admin y empleado. Cada rol debe ver solo lo que le corresponde.
```

---

### Corrección 6 — Panel de Gestión en blanco

```
La parte de acceso rápido ya funciona, pero en la parte de panel de gestión
siguen sin aparecer los módulos. Solucionalo sin perjudicar a la parte de
acceso rápido. Revisa admin/empleados.php, admin/proyectos.php,
admin/solicitudes.php y admin/fichajes.php para que incluyan auth_check.php
correctamente.
```

---

### Corrección 7 — Push a GitHub rechazado

```
No me deja hacer push al git, solucionamelo.
```

**Solución aplicada por Cline:**
```bash
git pull --rebase origin main
git push
```

---

## LOG DE ERRORES

> Registro cronológico de todos los errores encontrados y cómo se resolvieron.

---

### ❌ ERROR 1 — PHPMailer no reconocido en notificaciones.php

**Descripción:** VS Code mostraba 23 errores en `mail/notificaciones.php`:
- `Undefined function 'getDB'`
- `Undefined type 'PHPMailer\PHPMailer\PHPMailer'`
- `Undefined type 'PHPMailer\PHPMailer\Exception'`

**Causa:** Faltaban los `require_once` de `config/db.php` y `vendor/autoload.php` al inicio del archivo, y los `use` de las clases de PHPMailer.

**Solución:** Añadir al inicio de `mail/notificaciones.php`:
```php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
```

---

### ❌ ERROR 2 — Composer no instalado / vendor/ no existe

**Descripción:** Tras arreglar notificaciones.php, seguían errores de PHPMailer porque no existía la carpeta `vendor/`.

**Causa:** No se había ejecutado `composer install`.

**Solución:**
```bash
cd ~/Documents/Projects/moncao-secure
composer install
```

---

### ❌ ERROR 3 — Error de conexión a la BD al hacer login

**Descripción:** Al intentar hacer login salía: *"Error de conexión. Contacta con el administrador."*

**Causa:** MySQL no estaba arrancado y el usuario root tenía autenticación por `auth_socket` bloqueando el acceso sin contraseña.

**Solución:**
```bash
sudo service mysql start
sudo mysql
```
```sql
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '';
FLUSH PRIVILEGES;
CREATE DATABASE IF NOT EXISTS moncao_secure;
EXIT;
```

---

### ❌ ERROR 4 — Email o contraseña incorrecta al login

**Descripción:** El login fallaba con credenciales correctas.

**Causa:** El hash bcrypt insertado en los datos de prueba no correspondía a `moncao2024`.

**Solución:** Actualizar el hash directamente en la BD:
```bash
mysql -u root moncao_secure -e "UPDATE users SET password = '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE 1;"
```

---

### ❌ ERROR 5 — Tabla 'departamentos' ya existe al importar SQL

**Descripción:** Al ejecutar el SQL de importación salía:
`ERROR 1050 (42S01): Table 'departamentos' already exists`

**Causa:** La base de datos ya estaba importada de una sesión anterior.

**Solución:** No era un error real — la BD ya existía y estaba correcta. Se continuó sin reimportar.

---

### ❌ ERROR 6 — Módulos en página en blanco

**Descripción:** Al entrar a cualquier módulo (fichaje, horario, solicitudes, etc.) la página aparecía completamente en blanco.

**Causa raíz detectada por Cline:** Las rutas `require_once` en los módulos eran incorrectas. Usaban `../config/db.php` pero el servidor PHP no resolvía correctamente las rutas relativas desde subdirectorios.

**Archivos afectados:** Todos los archivos en `modules/` y `admin/`.

**Solución:** Cline corrigió las rutas en los 10 archivos para usar `__DIR__` o rutas absolutas correctas.

---

### ❌ ERROR 7 — Not Found al hacer clic en la navbar

**Descripción:** Los enlaces de la navbar devolvían 404 Not Found.

**Causa:** Los enlaces en `includes/navbar.php` usaban rutas que no coincidían con la estructura real del proyecto al usar el servidor PHP built-in.

**Solución:** Cline corrigió `includes/navbar.php` para usar rutas absolutas desde la raíz.

**Nota:** Este error se repitió varias veces durante el desarrollo porque cada vez que se reiniciaba el servidor desde una carpeta diferente las rutas volvían a fallar.

---

### ❌ ERROR 8 — Panel de Gestión en blanco (admin/)

**Descripción:** Los módulos de acceso rápido funcionaban pero los del Panel de Gestión (admin/) seguían en blanco.

**Causa:** Los archivos `admin/*.php` no incluían `auth_check.php` correctamente.

**Solución:** Cline añadió el `require_once` de `auth_check.php` en todos los archivos de `admin/`.

---

### ❌ ERROR 9 — Git: repositorio no inicializado

**Descripción:**
```
fatal: no es un repositorio git (ni ninguno de los directorios superiores): .git
```

**Causa:** Cline no había inicializado el repositorio git en la carpeta del proyecto.

**Solución:**
```bash
git init
git add .
git commit -m "trabajo en progreso"
git branch -M main
git remote add origin https://github.com/RuizTafurCarlos/0376-RA6PR1-RuizTafurCarlos.git
git push -u origin main
```

---

### ❌ ERROR 10 — Git: push rechazado (ramas divergentes)

**Descripción:**
```
error: falló el empuje de algunas referencias
Updates were rejected because the remote contains work that you do not have locally
```

**Causa:** El repositorio remoto tenía commits que no existían en local (commits previos de Cline antes del crash de la VM).

**Solución:**
```bash
git config pull.rebase false
git pull origin main --allow-unrelated-histories
git push -u origin main --force
```

**Nota:** Se usó `--force` porque había conflicto en `sql/moncao_secure.sql`. Esto hizo que se perdieran los commits anteriores del repositorio remoto.

---

### ❌ ERROR 11 — VM de Isard crasheando repetidamente

**Descripción:** La máquina virtual de Isard se apagó/crasheó 3 veces durante el desarrollo, interrumpiendo el trabajo de Cline.

**Causa:** Problema con la plataforma Isard (VM del instituto).

**Consecuencia:** Se perdieron commits y progreso de Cline en dos ocasiones.

**Solución:** Hacer commit y push a GitHub antes de cada sesión de trabajo.

---

### ❌ ERROR 12 — Puerto ocupado al arrancar servidor PHP

**Descripción:**
```
Failed to listen on localhost:8000 (reason: Address already in use)
```

**Causa:** El servidor PHP seguía corriendo en segundo plano tras un crash o Ctrl+Z.

**Solución:**
```bash
sudo fuser -k 8000/tcp
php -S localhost:8000
```
O usar un puerto diferente: `php -S localhost:9000`

---

### ❌ ERROR 13 — Gemini API quota agotada

**Descripción:**
```
429 Too Many Requests — You exceeded your current quota
Quota exceeded for: generativelanguage.googleapis.com/generate_content_free_tier_requests
limit: 20, model: gemini-2.5-flash
```

**Causa:** El tier gratuito de Gemini API tiene un límite de 20 requests diarios.

**Solución:** Esperar 52 segundos (rate limit temporal) o esperar al día siguiente para el reset diario. También se probó cambiar al modelo `gemini-2.0-flash`.

---

### ❌ ERROR 14 — Variables PHP indefinidas (warnings)

**Descripción:** VS Code mostraba 11 warnings de tipo `Possible undefined variable` en varios archivos.

**Archivos afectados y variables:**
- `admin/empleados.php`: `$departamentos`, `$empleados`
- `admin/fichajes.php`: `$empleados`, `$fichajes`
- `admin/proyectos.php`: `$departamentos`
- `modules/fichaje.php`: `$proyectos`
- `modules/horario.php`: `$horarios`
- `modules/informes.php`: `$horasSemana`, `$horasProyecto`

**Solución:** Inicializar todas las variables con array vacío antes del bloque `try`:
```php
$departamentos = [];
$empleados = [];
$fichajes = [];
// etc.
```

---

## COMANDOS PARA ARRANCAR LA APP

```bash
# 1. Ir a la carpeta del proyecto
cd ~/Documents/Projects/moncao-secure

# 2. Arrancar el servidor PHP
php -S localhost:9000

# 3. Abrir en el navegador
# http://localhost:9000
```

> ⚠️ Mantener la terminal abierta mientras se usa la app. Si se cierra la terminal, el servidor se para.

---

## CREDENCIALES DE PRUEBA

| Nombre | Email | Contraseña | Rol |
|---|---|---|---|
| Admin Principal | superadmin@moncao.com | moncao2024 | superadmin |
| Jefe RRHH | rrhh@moncao.com | moncao2024 | admin |
| Juan García | juan@moncao.com | moncao2024 | empleado |
| María López | maria@moncao.com | moncao2024 | empleado |

---

*MONCAO SECURE — 0376-RA6PR1-RuizTafurCarlos | Proyecto académico PHP + MySQL + Cline*
