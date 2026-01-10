# Reporteador CCTV - ECC (Enterprise Edition)

> **Plataforma de Gestión de Evidencia Digital y Reportes Automatizados con IA.**
> *Sistema privado desarrollado para la optimización operativa de Centros de Monitoreo.*

---

## 📋 Descripción del Proyecto

El **Reporteador ECC** es una solución Full-Stack diseñada para centralizar, estandarizar y automatizar el flujo de trabajo de los monitoristas de seguridad. El sistema reemplaza el envío manual de correos y archivos adjuntos dispersos por una interfaz web unificada que gestiona la carga de evidencias, redacción de bitácoras y generación de documentos legales (PDF).

### 🚀 Características Clave

* **🤖 Redacción Asistida por IA:** Integración con **Google Gemini Pro** para procesar las observaciones de los operadores, corrigiendo ortografía, gramática y tono, transformando notas breves en reportes ejecutivos en tiempo real.
* **📸 Motor de Procesamiento de Imágenes:** Compresión y redimensionado automático de evidencias (Server-side) para optimizar el ancho de banda y almacenamiento.
* **💾 Auto-Guardado Resiliente:** Sistema de "Drafts" (Borradores) que guarda el progreso cada cambio, previniendo pérdida de datos por fallos eléctricos o de red.
* **📄 Generación PDF Dinámica:** Motor basado en `FPDF` que compila textos e imágenes en bitácoras oficiales listas para auditoría.
* **📧 Distribución Automatizada:** Integración con `PHPMailer` para el envío masivo a listas de distribución corporativas.

---

## 🛠️ Stack Tecnológico

La arquitectura está diseñada para ser ligera, rápida y desplegable en servidores LAMP estándar.

| Capa | Tecnología | Función |
| :--- | :--- | :--- |
| **Frontend** | HTML5 / Bootstrap 5 | Interfaz Responsiva y UX (Drag & Drop). |
| **Backend** | PHP 8.1 | Lógica de negocio y procesamiento de archivos. |
| **Database** | MySQL / MariaDB | Almacenamiento de historiales y borradores. |
| **AI Core** | Google Gemini API | Procesamiento de Lenguaje Natural (NLP). |
| **Libs** | FPDF / PHPMailer | Generación de documentos y transporte SMTP. |

---

## 📸 Galería del Sistema

### 1. Interfaz Principal (Dashboard)
Panel de control con selectores de turno, estado de sincronización y zonas de carga.
![Dashboard UI](http://imgfz.com/i/4Ttnfde.png)

### 2. Historial de Reportes
Módulo administrativo para consulta y descarga de folios anteriores.
![Historial](http://imgfz.com/i/JcROyd1.png)

### 3. Resultado Final (PDF)
Ejemplo del documento generado automáticamente y enviado al cliente.
![Reporte PDF](http://imgfz.com/i/9y3PIML.png)

---

## 👨‍💻 Guía de Despliegue (Para Desarrolladores)

Este proyecto ha sido liberado con fines educativos y de colaboración. Si deseas implementar este sistema en tu propio entorno local o servidor, sigue estos pasos:

### ⚠️ 1. Estructura de Carpetas (Archivos Excluidos)
Por razones de seguridad y peso, el repositorio **NO incluye** las siguientes carpetas. Debes crearlas manualmente en la raíz del proyecto:

```bash
/proyecto-root
├── libs/           <-- AQUÍ van las librerías externas
│   ├── fpdf/       <-- Descomprime FPDF aquí
│   └── phpmailer/  <-- Descomprime PHPMailer aquí
├── uploads/        <-- AQUÍ se guardarán las fotos y PDFs (Permisos 755 o 777)

### 📥 2. Instalación de Dependencias
Descarga las librerías necesarias y colócalas en la carpeta libs que acabas de crear:

FPDF (Generación de PDF): Descargar FPDF

PHPMailer (Envío de Correo): Descargar PHPMailer

### ⚙️ 3. Configuración de Credenciales
El código fuente ha sido "sanitizado" para proteger la infraestructura de producción. Debes abrir los siguientes archivos y colocar tus propios datos:

config.php:

Configura tu conexión a MySQL (DB_HOST, DB_USER, DB_PASS).

Configura tu servidor SMTP para el envío de correos (SMTP_HOST, SMTP_USER, SMTP_PASS).

ia_proxy.php:

Reemplaza TU_API_KEY_DE_GEMINI por tu propia llave. Puedes obtener una gratis en Google AI Studio.

###🗄️ 4. Base de Datos
Necesitarás una base de datos MySQL con al menos dos tablas principales:

reportes: Para almacenar el historial de folios generados, rutas de PDF y datos del operador.

borradores: Para el sistema de auto-guardado (debe contener campos para fecha, turno y datos_json).

🔒 Nota de Seguridad

Las credenciales críticas (API Keys, Contraseñas SMTP, Accesos DB) han sido eliminadas de este repositorio público.

Se recomienda encarecidamente no subir tus propios archivos de configuración a repositorios públicos sin usar un .gitignore adecuado.

Desarrollado por: William Velázquez Valenzuela Director de Tecnologías | Pixmedia Agency
