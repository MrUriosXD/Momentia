# 💍 Momentia

> **CMS personal para crear experiencias digitales, historias y momentos especiales.**

Momentia es un **CMS desarrollado en PHP** que permite crear y gestionar experiencias digitales personalizadas, originalmente pensado para propuestas románticas, historias de pareja y recuerdos especiales.

El proyecto combina una **experiencia pública interactiva** con un sistema de administración inspirado en la filosofía del **MyBB AdminCP**, incorporando instalación, configuración dinámica, gestión de contenido, idiomas, multimedia y herramientas administrativas.

---

## ✨ Características

* 💌 **Experiencias románticas interactivas**
* 🛠️ **Panel de administración estilo MyBB AdminCP**
* 🗄️ **MySQL mediante PDO**
* 📦 **Instalador propio**
* 🔐 **Sistema de administración y autenticación**
* 🌐 **Gestor de idiomas modular**
* 📝 **Editor de contenidos dinámicos**
* 🖼️ **Gestión de imágenes**
* 🎵 **Gestión y reproducción de música**
* 📤 **Subida de archivos**
* 💖 **Carta y mensajes personalizados**
* 📅 **Línea temporal de momentos**
* ✨ **Razones y deseos personalizados**
* 🔗 **Open Graph y Twitter Cards**
* 📱 **Diseño responsive**
* 💾 **Gestión de datos y configuración**
* 🔄 **Herramientas de mantenimiento del sistema**

---

## 🎛️ Administración

Momentia está diseñado para que la experiencia pueda administrarse sin tener que modificar directamente el código de la página pública.

El sistema puede centralizar diferentes áreas del proyecto desde el panel de administración:

| Sección              | Funcionalidad                                       |
| -------------------- | --------------------------------------------------- |
| 🏠 **Inicio**        | Resumen y acceso a las diferentes áreas del sistema |
| ⚙️ **Configuración** | Configuración general de la experiencia             |
| 🌐 **Idiomas**       | Gestión de los textos e idiomas disponibles         |
| 🖼️ **Multimedia**   | Gestión de imágenes y recursos visuales             |
| 🎵 **Música**        | Configuración y reproducción de música              |
| ✉️ **Mensajes**      | Gestión del contenido de la carta                   |
| 💖 **Razones**       | Gestión de motivos y mensajes personalizados        |
| 📅 **Timeline**      | Gestión de capítulos y momentos                     |
| ✨ **Deseos**         | Gestión de deseos y mensajes especiales             |
| 🛠️ **Herramientas** | Mantenimiento y herramientas administrativas        |

> La arquitectura está pensada para poder ampliar progresivamente el panel con nuevos módulos y tipos de contenido.

---

## 🌐 Sistema de idiomas

Momentia incorpora un sistema de idiomas basado en **archivos independientes**, inspirado en el sistema utilizado por MyBB.

Los textos de la interfaz pueden separarse del código de la aplicación, permitiendo:

* 🇪🇸 Español
* 🇬🇧 English
* ➕ Creación de nuevos idiomas
* ✏️ Edición de frases
* 🔄 Cambio del idioma activo
* 🧩 Organización modular de las traducciones

El objetivo es que añadir un nuevo idioma no requiera modificar directamente la lógica principal de Momentia.

---

## 🗄️ Base de datos

Momentia utiliza **MySQL / MariaDB** para almacenar la información dinámica de la experiencia.

Entre los contenidos gestionados por el sistema se encuentran:

* 👩‍❤️‍👨 Información de la pareja
* 📅 Fechas importantes
* ⚙️ Configuración general
* 🖼️ Recursos multimedia
* ✉️ Párrafos de la carta
* 💖 Razones
* 📅 Capítulos de la línea temporal
* ✨ Deseos y mensajes
* 👤 Información administrativa

El acceso a la base de datos se realiza mediante **PDO**.

---

## 📸 Experiencia pública

La página pública es la parte visible para la persona que recibe la experiencia.

Puede incluir diferentes elementos como:

* 💕 Presentación personalizada
* 💌 Carta romántica
* 💖 Razones
* 📅 Historia / línea temporal
* ✨ Deseos de futuro
* 🎵 Música de fondo
* 🖼️ Fotografías
* 💍 Propuesta final
* 📱 Adaptación para móviles

La información se carga dinámicamente desde la configuración y los contenidos administrados por Momentia.

---

## 🖼️ Multimedia

Momentia incorpora gestión de recursos multimedia para permitir utilizar contenido personalizado sin modificar directamente `index.php`.

El sistema contempla recursos como:

* 📷 Imágenes de portada
* 🖼️ Segunda imagen
* 🎵 Música de fondo
* 📤 Subida de archivos
* 🗂️ Selección de recursos existentes

También se contemplan **fallbacks visuales** cuando una imagen no está disponible, evitando que la experiencia quede rota por la ausencia de un recurso.

---

## 📦 Instalación

Momentia dispone de un instalador propio para preparar el sistema.

### Requisitos

* **PHP**
* **MySQL / MariaDB**
* Extensión **PDO**
* Extensión **PDO MySQL**
* Servidor web compatible con PHP
* Permisos de escritura donde sean necesarios

### Instalación

1. Descarga o clona el repositorio.
2. Sube Momentia a tu servidor.
3. Accede a `install.php`.
4. Configura la conexión con MySQL.
5. Completa la configuración inicial.
6. Finaliza la instalación.
7. Accede al panel de administración.
8. Personaliza la experiencia.

Una vez instalado, el sistema puede bloquear el acceso al instalador para evitar una reinstalación accidental.

---

## 📁 Estructura del proyecto

```text
Momentia/
├── admin/              # Panel de administración
├── data/               # Configuración y datos del sistema
├── languages/          # Archivos de idiomas
├── api.php             # API y operaciones del sistema
├── config.php          # Configuración y funciones principales
├── index.php           # Experiencia pública
├── install.php         # Instalador
├── upload.php          # Gestión de archivos
└── README.md           # Documentación
```

> La estructura puede evolucionar a medida que Momentia incorpore nuevos módulos y funcionalidades.

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

El proyecto incorpora diferentes mecanismos destinados a proteger la instalación y administración, entre ellos:

* 🔑 Autenticación administrativa
* 🔒 Contraseñas almacenadas mediante hash
* 🛡️ Acceso controlado al panel
* 🗄️ Consultas mediante PDO
* 📦 Bloqueo del instalador después de la instalación
* 📤 Control de archivos subidos
* 🔐 Protección de información de configuración

La seguridad seguirá evolucionando junto con el proyecto.
---

## 🚧 Estado del proyecto

**En desarrollo activo.**

Momentia continúa evolucionando desde su concepto inicial de propuesta romántica hacia un **CMS personal más flexible y reutilizable**.

La arquitectura, el panel de administración y los módulos pueden seguir ampliándose en futuras versiones.

---

## 🛠️ Tecnologías

* 🐘 **PHP**
* 🗄️ **MySQL / MariaDB**
* 🌐 **HTML5**
* 🎨 **CSS3**
* ⚡ **JavaScript**
* 🔌 **PDO**
* 🌐 **Google Fonts**

---

## 📌 Nombre del proyecto

**Momentia** representa la evolución del proyecto original de propuesta romántica hacia una plataforma capaz de crear diferentes experiencias digitales personalizadas.

> **Create memorable digital moments.** 💫

---

<p align="center">
  <strong>Momentia</strong><br>
  <sub>Personalized digital experiences.</sub>
</p>
