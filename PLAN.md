# Moodle Reuse Unit - Plan de Extensión

## 🎯 Objetivo
Crear una extensión para Moodle 4.x+ que permita reutilizar secciones/unidades completas de un curso en otro, incluyendo todas las actividades, recursos y configuraciones.

---

## 📋 Funcionalidades Principales

### 1. **Importación de Unidades** (Core)
- [ ] Seleccionar curso origen mediante autocompletado
- [ ] Visualizar y seleccionar sección(es) del curso origen
- [ ] Seleccionar curso destino mediante autocompletado
- [ ] Elegir posición de inserción en el curso destino
- [ ] Importar contenido completo de la sección:
  - Actividades (quizzes, tareas, foros, etc.)
  - Recursos (archivos, URLs, páginas, etc.)
  - Etiquetas y descripciones
  - Restricciones de acceso (opcionales)
  - Calificaciones y configuración de notas (opcional)

### 2. **Vista Previa**
- [ ] Mostrar árbol de contenido de la sección antes de importar
- [ ] Indicar tipos de actividades con iconos
- [ ] Mostrar tamaño estimado de la importación
- [ ] Advertencias sobre contenido no compatible

### 3. **Opciones de Importación**
> ⚠️ **IMPORTANTE**: NUNCA se importan datos de usuarios (entregas, intentos, calificaciones obtenidas).
> Solo se importa la estructura y configuración del contenido.

- [ ] Mantener/resetear fechas de actividades (resetear por defecto)
- [ ] Incluir/excluir restricciones de acceso (excluir por defecto)
- [ ] Incluir/excluir estructura del libro de calificaciones (excluir por defecto)
- [ ] Renombrar sección al importar
- [ ] Importar múltiples secciones a la vez

### 4. **Gestión de Conflictos**
- [ ] Detectar actividades duplicadas por nombre
- [ ] Opciones: Renombrar automático / Sobrescribir / Omitir / Preguntar
- [ ] Log de acciones realizadas

### 5. **Historial y Favoritos**
- [ ] Historial de importaciones recientes
- [ ] Marcar secciones como "plantillas favoritas"
- [ ] Acceso rápido a plantillas frecuentes

### 6. **Permisos y Seguridad**
- [ ] Capability `local/reuseunit:import` - Puede importar unidades
- [ ] Capability `local/reuseunit:export` - Puede exportar/compartir unidades
- [ ] Capability `local/reuseunit:managetemplates` - Gestionar plantillas globales
- [ ] Respetar permisos de acceso a cursos origen

### 7. **Acceso Rápido desde Modo Edición** ⭐ NEW
- [ ] Botón/icono "Importar unidad" en cada sección del curso (modo edición)
- [ ] Abre modal con wizard simplificado (curso destino ya seleccionado)
- [ ] Integración con el menú de acciones de sección
- [ ] Acceso desde el menú "Añadir actividad o recurso"

---

## 🎨 UX/UI Design

### Flujo Principal (3 pasos)

```
┌─────────────────────────────────────────────────────────────────┐
│  PASO 1: Seleccionar Origen                                     │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Curso origen:  [🔍 Buscar curso...                        ▼]  │
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ 📚 Matemáticas 101 - 2024                               │   │
│  │ 📚 Física Básica - Grupo A                              │   │
│  │ 📚 Química Orgánica                                     │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  Secciones disponibles:                                         │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ ☐ Tema 1: Introducción (3 actividades, 2 recursos)      │   │
│  │ ☑ Tema 2: Ecuaciones (5 actividades, 4 recursos)        │   │
│  │ ☐ Tema 3: Funciones (4 actividades, 3 recursos)         │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│                                          [Siguiente →]          │
└─────────────────────────────────────────────────────────────────┘
```

```
┌─────────────────────────────────────────────────────────────────┐
│  PASO 2: Vista Previa y Opciones                                │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  📁 Tema 2: Ecuaciones                                          │
│  ├── 📝 Cuestionario: Evaluación inicial                       │
│  ├── 📄 Página: Teoría de ecuaciones                           │
│  ├── 📎 Archivo: ejercicios.pdf                                │
│  ├── ✏️ Tarea: Ejercicios prácticos                            │
│  ├── 💬 Foro: Dudas del tema                                   │
│  └── 📝 Cuestionario: Examen final                             │
│                                                                 │
│  Opciones:                                                      │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ ☑ Resetear fechas de entrega                            │   │
│  │ ☐ Incluir restricciones de acceso                       │   │
│  │ ☑ Incluir configuración de calificaciones               │   │
│  │ ☐ Incluir banco de preguntas asociado                   │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│                              [← Anterior]  [Siguiente →]        │
└─────────────────────────────────────────────────────────────────┘
```

```
┌─────────────────────────────────────────────────────────────────┐
│  PASO 3: Seleccionar Destino                                    │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Curso destino: [🔍 Buscar curso...                        ▼]  │
│                                                                 │
│  Insertar en posición:                                          │
│  ○ Al principio del curso                                       │
│  ○ Después de: [Seleccionar sección...                     ▼]  │
│  ● Al final del curso                                           │
│                                                                 │
│  Nuevo nombre (opcional):                                       │
│  [Tema 2: Ecuaciones                                        ]   │
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ ⚠️ Se importarán:                                        │   │
│  │    • 5 actividades                                       │   │
│  │    • 4 recursos                                          │   │
│  │    • 1 sección                                           │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│                              [← Anterior]  [🚀 Importar]        │
└─────────────────────────────────────────────────────────────────┘
```

### Componentes UI

1. **Autocompletado de cursos**
   - Búsqueda por nombre, shortname o ID
   - Mostrar categoría del curso
   - Filtrar por cursos donde el usuario tiene acceso
   - Mostrar cursos recientes primero

2. **Selector de secciones**
   - Checkbox múltiple para selección
   - Preview al hacer hover/click
   - Contador de contenido (X actividades, Y recursos)
   - Indicador visual del tipo de contenido

3. **Barra de progreso**
   - Progreso por etapas durante importación
   - Mensajes descriptivos del paso actual
   - Opción de cancelar (si es posible)

4. **Notificaciones**
   - Toast de éxito/error
   - Modal de confirmación antes de importar
   - Resumen post-importación con enlace al curso

### Flujo Rápido desde Modo Edición (2 pasos)

```
┌─────────────────────────────────────────────────────────────────┐
│  Modo Edición del Curso                                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  📚 Tema 1: Introducción                                        │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │ [+ Añadir actividad] [+ Añadir recurso] [📥 Importar]   │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                 │
│  📚 Tema 2: Contenido principal                    [⚙️ ▼]      │
│  ├── 📝 Cuestionario 1                             ┌─────────┐ │
│  └── 📄 Material de lectura                        │ Editar  │ │
│                                                    │ Ocultar │ │
│                                                    │ Eliminar│ │
│                                                    │─────────│ │
│                                                    │📥Importar│ │
│                                                    │  unidad │ │
│                                                    └─────────┘ │
└─────────────────────────────────────────────────────────────────┘
```

Al hacer clic en "Importar unidad":

```
┌─────────────────────────────────────────────────────────────────┐
│  📥 Importar Unidad en: Tema 2                           [✕]   │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Curso origen:  [🔍 Buscar curso...                        ▼]  │
│                                                                 │
│  Sección a importar:                                            │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ ○ Tema 1: Intro (2 act.)  ○ Tema 2: Core (5 act.)      │   │
│  │ ○ Tema 3: Avanzado (3 act.)                             │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  ☑ Resetear fechas   ☐ Incluir restricciones                   │
│                                                                 │
│                              [Cancelar]  [📥 Importar aquí]     │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🌐 Internacionalización (i18n)

### Idiomas iniciales
- Español (es)
- English (en)

### Cadenas principales

| Clave | Español | English |
|-------|---------|---------|
| pluginname | Reutilizar Unidad | Reuse Unit |
| selectsourcecourse | Seleccionar curso origen | Select source course |
| selectsection | Seleccionar sección | Select section |
| selectdestination | Seleccionar destino | Select destination |
| import | Importar | Import |
| preview | Vista previa | Preview |
| options | Opciones | Options |
| resetdates | Resetear fechas | Reset dates |
| includerestrictions | Incluir restricciones | Include restrictions |
| includegrades | Incluir calificaciones | Include grades |
| importsuccessful | Importación exitosa | Import successful |
| importfailed | Error en la importación | Import failed |
| nocourseaccess | No tienes acceso a este curso | You don't have access to this course |
| nosections | Este curso no tiene secciones | This course has no sections |
| searchcourse | Buscar curso... | Search course... |
| activities | actividades | activities |
| resources | recursos | resources |

---

## 🏗️ Arquitectura Técnica

### Tipo de Plugin
**`local/reuseunit`** - Plugin local que añade funcionalidad sin modificar el core.

### Estructura de Archivos
```
local/reuseunit/
├── classes/
│   ├── external/
│   │   ├── get_courses.php          # API para buscar cursos
│   │   ├── get_sections.php         # API para obtener secciones
│   │   ├── get_section_content.php  # API para contenido de sección
│   │   └── import_section.php       # API para importar
│   ├── form/
│   │   └── import_form.php          # Formulario de importación
│   ├── output/
│   │   └── renderer.php             # Renderizado de vistas
│   └── task/
│       └── import_task.php          # Tarea asíncrona de importación
├── db/
│   ├── access.php                   # Capabilities
│   ├── services.php                 # Servicios web
│   ├── install.xml                  # Tablas de BD
│   └── upgrade.php                  # Migraciones
├── lang/
│   ├── en/
│   │   └── local_reuseunit.php
│   └── es/
│       └── local_reuseunit.php
├── templates/
│   ├── import_wizard.mustache       # Wizard principal
│   ├── section_preview.mustache     # Vista previa
│   └── course_selector.mustache     # Selector de curso
├── amd/
│   └── src/
│       ├── import_wizard.js         # Lógica del wizard
│       ├── course_search.js         # Autocompletado
│       └── section_selector.js      # Selector de secciones
├── styles.css                       # Estilos
├── index.php                        # Página principal
├── import.php                       # Proceso de importación
├── lib.php                          # Funciones de librería
├── settings.php                     # Configuración admin
└── version.php                      # Versión del plugin
```

### Tablas de Base de Datos

```sql
-- Historial de importaciones
CREATE TABLE {local_reuseunit_history} (
    id BIGINT PRIMARY KEY,
    userid BIGINT NOT NULL,
    source_courseid BIGINT NOT NULL,
    source_sectionid BIGINT NOT NULL,
    dest_courseid BIGINT NOT NULL,
    dest_sectionid BIGINT NOT NULL,
    options TEXT,
    status VARCHAR(20),
    timecreated BIGINT,
    timecompleted BIGINT
);

-- Plantillas favoritas
CREATE TABLE {local_reuseunit_favorites} (
    id BIGINT PRIMARY KEY,
    userid BIGINT NOT NULL,
    courseid BIGINT NOT NULL,
    sectionid BIGINT NOT NULL,
    name VARCHAR(255),
    timecreated BIGINT
);
```

### APIs Web Services

| Servicio | Descripción |
|----------|-------------|
| `local_reuseunit_get_courses` | Buscar cursos con acceso |
| `local_reuseunit_get_sections` | Obtener secciones de un curso |
| `local_reuseunit_get_section_content` | Obtener contenido detallado |
| `local_reuseunit_import_section` | Ejecutar importación |
| `local_reuseunit_get_history` | Obtener historial |
| `local_reuseunit_add_favorite` | Añadir a favoritos |

---

## 🔧 Implementación Técnica

### Proceso de Importación

1. **Validación**
   - Verificar permisos en curso origen y destino
   - Verificar que la sección existe
   - Verificar espacio y límites

2. **Backup Parcial**
   - Usar `backup_controller` de Moodle
   - Configurar para solo la sección seleccionada
   - Generar archivo MBZ temporal

3. **Restore Parcial**
   - Usar `restore_controller` de Moodle
   - Restaurar en la posición indicada
   - Aplicar opciones (fechas, restricciones, etc.)

4. **Post-procesamiento**
   - Actualizar referencias internas
   - Registrar en historial
   - Limpiar archivos temporales
   - Notificar al usuario

### Integración con Moodle

```php
// Ejemplo: Obtener secciones con contenido
function get_sections_with_content($courseid) {
    global $DB;

    $modinfo = get_fast_modinfo($courseid);
    $sections = [];

    foreach ($modinfo->get_section_info_all() as $section) {
        $sections[] = [
            'id' => $section->id,
            'section' => $section->section,
            'name' => get_section_name($courseid, $section),
            'summary' => $section->summary,
            'activities' => count($modinfo->sections[$section->section] ?? []),
            'visible' => $section->visible
        ];
    }

    return $sections;
}
```

---

## 📱 Consideraciones de Accesibilidad

- Navegación completa por teclado
- Labels ARIA en todos los controles
- Contraste adecuado (WCAG AA)
- Mensajes de estado para lectores de pantalla
- Focus visible en todos los elementos interactivos

---

## 🚀 Fases de Desarrollo

### Fase 1: MVP (Mínimo Viable)
- [x] Estructura base del plugin
- [ ] Selector de curso origen con autocompletado
- [ ] Listado de secciones
- [ ] Selector de curso destino
- [ ] Importación básica de sección
- [ ] Soporte ES/EN

### Fase 2: Mejoras UX
- [ ] Vista previa de contenido
- [ ] Opciones de importación
- [ ] Barra de progreso
- [ ] Historial de importaciones

### Fase 3: Funcionalidades Avanzadas
- [ ] Importación múltiple de secciones
- [ ] Plantillas favoritas
- [ ] Gestión de conflictos avanzada
- [ ] Banco de preguntas asociado

### Fase 4: Optimización
- [ ] Importación asíncrona para secciones grandes
- [ ] Caché de búsquedas
- [ ] Tests unitarios y de integración

---

## 📝 Notas Adicionales

### Limitaciones conocidas de Moodle
- El backup/restore a nivel de sección requiere workarounds
- Algunos módulos tienen dependencias externas (H5P, etc.)
- Las referencias entre actividades pueden romperse

### Compatibilidad
- Moodle 4.0+ (LTS)
- Moodle 4.1+
- Moodle 4.2+
- Moodle 4.3+
- Moodle 4.4+

### Testing
- Probar con diferentes tipos de actividades
- Probar con cursos grandes (>50 secciones)
- Probar importación cruzada entre categorías
- Probar con diferentes roles de usuario
