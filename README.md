# 💍 Momentia

> **CMS personal para crear experiencias digitales, historias y momentos especiales.**

Momentia es un **CMS desarrollado en PHP** para crear y gestionar experiencias digitales personalizadas, originalmente pensado para propuestas románticas, historias de pareja y recuerdos especiales.

El proyecto combina una **experiencia pública interactiva** con un sistema de administración inspirado en la filosofía del **MyBB AdminCP**, incorporando instalación, configuración dinámica, gestión de contenido, idiomas, multimedia y herramientas administrativas.

---

## ✨ Características

<details>
<summary>💖 Experiencia y contenido</summary>

- 💌 Experiencias románticas interactivas
- 📝 Editor de contenidos dinámicos
- 💖 Carta y mensajes personalizados
- 📅 Línea temporal de momentos
- ✨ Razones y deseos personalizados
- 📸 Fotografías y contenido visual
- 💍 Propuesta final

</details>

<details>
<summary>🛠️ Administración y CMS</summary>

- 🛠️ Panel de administración estilo MyBB AdminCP
- ⚙️ Configuración dinámica
- 📝 Gestión de contenidos
- 🔄 Herramientas de mantenimiento
- 🔐 Sistema de administración y autenticación

</details>

<details>
<summary>🌐 Idiomas</summary>

- 🇪🇸 Español
- 🇬🇧 English
- ➕ Sistema extensible para nuevos idiomas
- 🧩 Archivos de idioma independientes
- 🔄 Cambio de idioma

</details>

<details>
<summary>🖼️ Multimedia</summary>

- 🖼️ Gestión de imágenes
- 🎵 Gestión y reproducción de música
- 📤 Subida de archivos
- 🗂️ Gestión de recursos multimedia

</details>

<details>
<summary>🗄️ Base de datos y sistema</summary>

- 🗄️ MySQL / MariaDB
- 🔌 PDO
- 📦 Instalador propio
- 💾 Gestión de datos y configuración
- 🔗 API y operaciones del sistema

</details>

<details>
<summary>🔐 Seguridad y compatibilidad</summary>

- 🔑 Autenticación administrativa
- 🔒 Contraseñas mediante hash
- 🛡️ Acceso controlado al panel
- 📦 Protección del instalador
- 📤 Control de archivos subidos
- 📱 Diseño responsive
- 🔗 Open Graph y Twitter Cards

</details>

---

## 🎛️ Administración

Momentia está diseñado para que la experiencia pueda administrarse sin tener que modificar directamente el código de la página pública.

| Sección | Funcionalidad |
|---|---|
| 🏠 **Inicio** | Resumen y acceso a las diferentes áreas del sistema |
| ⚙️ **Configuración** | Configuración general de la experiencia |
| 🌐 **Idiomas** | Gestión de los textos e idiomas disponibles |
| 🖼️ **Multimedia** | Gestión de imágenes y recursos visuales |
| 🎵 **Música** | Configuración y reproducción de música |
| ✉️ **Mensajes** | Gestión del contenido de la carta |
| 💖 **Razones** | Gestión de motivos y mensajes personalizados |
| 📅 **Timeline** | Gestión de capítulos y momentos |
| ✨ **Deseos** | Gestión de deseos y mensajes especiales |
| 🛠️ **Herramientas** | Mantenimiento y herramientas administrativas |

---

## 🌐 Sistema de idiomas

Momentia incorpora un sistema de idiomas basado en **archivos PHP independientes**, inspirado en el sistema utilizado por MyBB.

Actualmente incluye:

- 🇪🇸 `languages/es.php` — Español
- 🇬🇧 `languages/en.php` — English

La estructura permite ampliar el sistema con nuevos archivos de idioma sin modificar la lógica principal de la aplicación.

---

## 🗄️ Base de datos

Momentia utiliza **MySQL / MariaDB** para almacenar la información dinámica de la experiencia y acceder a ella mediante **PDO**.

La carpeta `data/` contiene los archivos relacionados con la configuración y estructura de la base de datos:

- `db_config.php` — Configuración de conexión generada para la instalación.
- `db_schema.php` — Definición del esquema de base de datos utilizado por la aplicación.
- `default_data.php` — Datos iniciales utilizados durante la instalación.
- `schema.sql` — Esquema SQL de referencia.
- `index.html` — Archivo de protección/directorio.

---

## 📸 Experiencia pública

La página pública es la parte visible para la persona que recibe la experiencia.

Puede incluir diferentes elementos como:

- 💕 Presentación personalizada
- 💌 Carta romántica
- 💖 Razones
- 📅 Historia / línea temporal
- ✨ Deseos de futuro
- 🎵 Música de fondo
- 🖼️ Fotografías
- 💍 Propuesta final
- 📱 Adaptación para móviles

La información se carga dinámicamente desde la configuración y los contenidos administrados por Momentia.

---

## 🖼️ Multimedia

Momentia incorpora gestión de recursos multimedia mediante `upload.php`, permitiendo trabajar con archivos utilizados por la experiencia sin modificar directamente `index.php`.

El sistema contempla recursos como imágenes y música, además de la gestión de las subidas desde el sistema.

---

## 📦 Instalación

Momentia dispone de un instalador propio para preparar el sistema.

### Requisitos

- **PHP**
- **MySQL / MariaDB**
- Extensión **PDO**
- Extensión **PDO MySQL**
- Servidor web compatible con PHP
- Permisos de escritura donde sean necesarios

### Instalación

1. Descarga o clona el repositorio.
2. Sube Momentia a tu servidor.
3. Accede a `install.php`.
4. Configura la conexión con MySQL.
5. Completa la configuración inicial.
6. Finaliza la instalación.
7. Accede al panel de administración.
8. Personaliza la experiencia.

---

## 📁 Estructura del proyecto

```text
Momentia/
├── admin/
│   ├── index.php          # Panel principal de administración
│   └── login.php          # Acceso al panel de administración
│
├── data/
│   ├── db_config.php      # Configuración de conexión a la base de datos
│   ├── db_schema.php      # Definición del esquema de la base de datos
│   ├── default_data.php   # Datos iniciales del sistema
│   ├── index.html         # Protección del directorio data
│   └── schema.sql         # Esquema SQL de referencia
│
├── languages/
│   ├── en.php             # Idioma inglés
│   └── es.php             # Idioma español
│
├── api.php                # API y operaciones CRUD del sistema
├── config.php             # Configuración, conexión y funciones principales
├── index.php              # Experiencia pública de Momentia
├── install.php            # Asistente de instalación
├── README.md              # Documentación del proyecto
└── upload.php             # Gestión y subida de archivos multimedia
```

### 📂 Descripción rápida

| Archivo / carpeta | Función |
|---|---|
| `admin/` | Contiene el panel de administración de Momentia. |
| `admin/index.php` | Punto principal del AdminCP. |
| `admin/login.php` | Inicio de sesión del administrador. |
| `data/` | Archivos relacionados con datos y base de datos. |
| `data/db_config.php` | Configuración de conexión a MySQL/MariaDB. |
| `data/db_schema.php` | Estructura de las tablas utilizadas por el sistema. |
| `data/default_data.php` | Datos iniciales utilizados para preparar la instalación. |
| `data/index.html` | Evita la navegación directa del directorio. |
| `data/schema.sql` | Definición SQL de referencia. |
| `languages/` | Paquetes de idioma de Momentia. |
| `languages/es.php` | Traducciones en español. |
| `languages/en.php` | Traducciones en inglés. |
| `api.php` | Backend y operaciones de la aplicación. |
| `config.php` | Configuración y funciones comunes. |
| `index.php` | Página pública y experiencia interactiva. |
| `install.php` | Instalador y configuración inicial. |
| `upload.php` | Gestión de archivos multimedia. |
| `README.md` | Documentación del proyecto. |

---

## 🔌 Arquitectura

Momentia está planteado como una aplicación PHP modular en la que diferentes partes del sistema cumplen funciones específicas:

```text
                    ┌─────────────────────┐
                    │      Momentia       │
                    └──────────┬──────────┘
                               │
              ┌────────────────┼────────────────┐
              │                │                │
              ▼                ▼                ▼
        ┌───────────┐    ┌───────────┐    ┌───────────┐
        │  Público  │    │  AdminCP  │    │ Instalador│
        │ index.php │    │   admin/  │    │ install   │
        └─────┬─────┘    └─────┬─────┘    └─────┬─────┘
              │                │                │
              └────────────────┼────────────────┘
                               ▼
                      ┌─────────────────┐
                      │   API / Core    │
                      │    api.php      │
                      └────────┬────────┘
                               │
                    ┌──────────▼──────────┐
                    │   MySQL / MariaDB   │
                    └─────────────────────┘
```

---

## 🔐 Seguridad

El proyecto incorpora mecanismos destinados a proteger la instalación y administración, incluyendo autenticación administrativa, contraseñas almacenadas mediante hash, acceso controlado al panel, consultas mediante PDO y protección del instalador.

---

## 🎯 Objetivo del proyecto

Momentia nació como una **propuesta romántica personalizada**, pero su objetivo actual es convertirse en una base reutilizable para crear diferentes tipos de experiencias digitales.

Algunos ejemplos:

- 💍 Propuestas románticas
- ❤️ Aniversarios
- 🎂 Cumpleaños
- 💌 Cartas digitales
- 📖 Historias personales
- 🎁 Regalos digitales
- 👫 Historias de pareja
- 🌟 Recuerdos especiales
- 🎉 Celebraciones

---

## 🚧 Estado del proyecto

**En desarrollo activo.**

Momentia continúa evolucionando desde su concepto inicial de propuesta romántica hacia un **CMS personal más flexible y reutilizable**.

La arquitectura, el panel de administración y los módulos pueden seguir ampliándose en futuras versiones.

---

## 🛠️ Tecnologías

- 🐘 **PHP**
- 🗄️ **MySQL / MariaDB**
- 🌐 **HTML5**
- 🎨 **CSS3**
- ⚡ **JavaScript**
- 🔌 **PDO**
- 🌐 **Google Fonts**

---

## 📌 Nombre del proyecto

**Momentia** representa la evolución del proyecto original de propuesta romántica hacia una plataforma capaz de crear diferentes experiencias digitales personalizadas.

> **Create memorable digital moments.** 💫

---

<p align="center">
  <strong>Momentia</strong><br>
  <sub>Personalized digital experiences.</sub>
</p>
