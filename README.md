# Aura Luxury 💎

Breve descripción del proyecto (Tienda Ropa Streetwear + ERP Dolibarr).

## 🏛️ Arquitectura del Repositorio
- / (Raíz) -> Landing y catálogo web público.
- /custom -> Módulos y personalizaciones de Dolibarr.
- /docker -> Dockerfile y dump inicial de base de datos.
- /deploy_site -> [Propósito de esta carpeta y sincronización].

## 🚀 Requisitos Previos
- Docker & Docker Compose
- Node.js (v18+)
- Python 3.10+ (para scripts de catálogo)

Viewed conf.php.example:1-100
Ran command: `ls -la documents/ 2>&1`
Searched for "^(import |from )"

## 🛠️ Instalación y Puesta en Marcha

### 1. Requisitos Previos

Antes de comenzar, asegúrate de tener instalado:

| Herramienta | Versión mínima | Comprobación |
| :--- | :--- | :--- |
| **Git** | 2.x+ | `git --version` |
| **Docker Engine / Desktop** | 20.x+ | `docker --version` |
| **Docker Compose** | v2.x+ | `docker compose version` |
| **Node.js & npm** *(opcional, desarrollo web)* | v18+ | `node -v` y `npm -v` |
| **Python** *(opcional, scripts de catálogo)* | 3.10+ | `python3 --version` o `python --version` |

> 💡 **Nota para usuarios de Windows:** Asegúrate de tener instalado **Docker Desktop** con la integración de **WSL 2** activada y Docker corriendo antes de ejecutar los comandos.

---

### 2. Clonar el Repositorio

Abre tu terminal y clona el proyecto:

#### 🐧 Linux (Bash) / macOS
```bash
git clone <URL_DEL_REPOSITORIO> aura-luxury
cd aura-luxury
```

#### 🪟 Windows (PowerShell)
```powershell
git clone <URL_DEL_REPOSITORIO> aura-luxury
cd aura-luxury
```

---

### 3. Preparación de Archivos y Carpetas

> `docker-compose.override.yml` mapea directamente los archivos `./conf/conf.php` y la carpeta `./documents`.  
> **Si ejecutas Docker sin haber creado antes el archivo `conf/conf.php`, Docker creará una carpeta llamada `conf.php` en su lugar**, lo que provocará un error fatal en PHP (`Is a directory`).

Ejecuta los siguientes comandos según tu sistema operativo antes de levantar los contenedores:

#### 🐧 En Linux (Bash)
```bash
# 1. Crear el archivo de configuración a partir de la plantilla
cp conf/conf.php.example conf/conf.php

# 2. Crear la carpeta para los documentos generados por Dolibarr
mkdir -p documents

# 3. Asignar permisos de escritura para que el servidor web de Docker pueda guardar archivos
chmod -R 775 documents/ custom/
```
*(Si experimentas problemas de permisos de escritura en Linux, puedes ejecutar temporalmente: `sudo chmod -R 777 documents/`)*.

#### 🪟 En Windows (PowerShell)
```powershell
# 1. Crear el archivo de configuración a partir de la plantilla
Copy-Item conf\conf.php.example conf\conf.php

# 2. Crear la carpeta de documentos
New-Item -ItemType Directory -Force -Path documents
```

#### 🪟 En Windows (CMD / Símbolo del Sistema)
```cmd
REM 1. Crear el archivo de configuración
copy conf\conf.php.example conf\conf.php

REM 2. Crear la carpeta de documentos
if not exist documents mkdir documents
```

---

### 4. Instalar Dependencias de Node (Frontend / Testing)

El proyecto cuenta con librerías auxiliares como `jsdom` para pruebas y manipulación del catálogo.

```bash
npm install
```

---

### 5. Levantar el Entorno con Docker

Una vez preparados los archivos, levantamos la base de datos MariaDB y el servidor Apache/PHP de Dolibarr. En la primera ejecución se construirá la imagen y se importará automáticamente la base de datos inicial desde `docker/db/aura-luxury.sql`.

```bash
docker compose up -d --build
```

#### Verificar que los contenedores estén activos:
```bash
docker compose ps
```

Deberás ver dos servicios en estado `Up` / `running`:
* `aura-luxury-dolibarr-1` (Puerto `8080:80`)
* `aura-luxury-db-1` (MariaDB 10.6)

#### Ver logs en tiempo real (si necesitas diagnosticar algo):
```bash
docker compose logs -f dolibarr
```

---

### 6. Accesos Locales y URLs

Una vez levantados los contenedores, abre tu navegador:

| Servicio | URL Local | Descripción |
| :--- | :--- | :--- |
| **Dolibarr ERP / TakePOS** | [http://localhost:8080](http://localhost:8080) | Panel de administración y punto de venta. |
| **Catálogo Web / Landing** | [http://localhost:8080/index.html](http://localhost:8080/index.html) | Vista de la tienda servida por Apache. |

* **Root Password:** `root`

> 💡 **Para desarrollo exclusivo del Catálogo Web (Frontend):**  
> Si no necesitas el ERP Dolibarr y solo vas a maquetar el catálogo, puedes abrir `index.html` con la extensión **Live Server** de VS Code o ejecutando `npx serve .` sin necesidad de encender Docker.

---

### 7. Comandos de Operación Habitual

| Acción | Comando (Linux & Windows) |
| :--- | :--- |
| **Detener contenedores temporalmente** | `docker compose stop` |
| **Reanudar contenedores** | `docker compose start` |
| **Bajar contenedores (conservando la BD)** | `docker compose down` |
| **Reiniciar la base de datos desde cero (recarga el SQL inicial)** | `docker compose down -v` seguido de `docker compose up -d` |
| **Acceder a la terminal dentro del contenedor PHP** | `docker compose exec dolibarr bash` |

---

## 🐍 Scripts de Catálogo y Automatización
- Cómo actualizar precios o agregar productos con los scripts de Python.

## 🌿 Flujo de Trabajo con Git
- Estrategia de ramas (`develop` -> `main`).
- Formato de commits (Conventional Commits).
- Reglas de Pull Requests.

## 🚫 Archivos que NO deben comitearse
- Credenciales y configs (`conf/conf.php`, `.env`).
- Documentos generados por Dolibarr (`documents/`, `*.pdf`, `*.docx`).
- Dumps temporales o diffs de tareas (`task-*.diff`).

## 🚢 Despliegue
- Despliegue de la web en Vercel.
- Actualización de Dolibarr en servidor de producción.
