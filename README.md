# Reporteador CCTV - ECC (Enterprise Edition)

> **Plataforma de Gestión de Evidencia Digital y Reportes Automatizados con IA.**
> *Sistema privado desarrollado para la optimización operativa de Centros de Monitoreo.*

---

## 📋 Descripción del Proyecto

El **Reporteador ECC** es una solución Full-Stack diseñada para centralizar, estandarizar y automatizar el flujo de trabajo de los monitoristas de seguridad. El sistema reemplaza el envío manual de correos y archivos adjuntos dispersos por una interfaz web unificada que gestiona la carga de evidencias, redacción de bitácoras y generación de documentos legales (PDF).

### 🚀 Características Clave

* **🤖 Redacción Asistida por IA:** Integración con **Google Gemini 1.5 Flash** para procesar las observaciones de los operadores, corrigiendo ortografía, gramática y tono, transformando notas breves en reportes ejecutivos en tiempo real.
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

## 🔒 Privacidad y Seguridad

Este repositorio sirve como **Vitrina Tecnológica**. El código fuente completo (Backend PHP, Credenciales API y Lógica de Base de Datos) se encuentra alojado en servidores privados de producción bajo estrictos protocolos de seguridad.

**Desarrollado por:**
**William Velázquez Valenzuela**
*Director de Tecnologías | Pixmedia Agency*
