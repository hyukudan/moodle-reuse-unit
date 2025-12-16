<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Language strings for local_reuseunit (Spanish).
 *
 * @package    local_reuseunit
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// General.
$string['pluginname'] = 'Reutilizar Unidad';
$string['plugindescription'] = 'Importa secciones/unidades de otros cursos con todas sus actividades y recursos.';

// Capabilities.
$string['reuseunit:import'] = 'Importar unidades de otros cursos';
$string['reuseunit:export'] = 'Permitir exportar/compartir unidades';
$string['reuseunit:managetemplates'] = 'Gestionar plantillas globales de unidades';

// Navigation and actions.
$string['importunit'] = 'Importar unidad';
$string['importunithere'] = 'Importar unidad aquí';
$string['importfromcourse'] = 'Importar de otro curso';

// Wizard steps.
$string['step1_title'] = 'Seleccionar origen';
$string['step1_description'] = 'Elige el curso y la sección que quieres importar.';
$string['step2_title'] = 'Vista previa y opciones';
$string['step2_description'] = 'Revisa el contenido y configura las opciones de importación.';
$string['step3_title'] = 'Seleccionar destino';
$string['step3_description'] = 'Elige dónde colocar la sección importada.';

// Source selection.
$string['sourcecourse'] = 'Curso origen';
$string['selectsourcecourse'] = 'Seleccionar curso origen';
$string['searchcourse'] = 'Buscar curso...';
$string['searchcourseplaceholder'] = 'Escribe para buscar cursos...';
$string['nocoursesfound'] = 'No se encontraron cursos';
$string['selectsection'] = 'Seleccionar sección';
$string['availablesections'] = 'Secciones disponibles';
$string['nosections'] = 'Este curso no tiene secciones';
$string['sectioncontent'] = '{$a->activities} actividades, {$a->resources} recursos';

// Preview.
$string['preview'] = 'Vista previa';
$string['contenttobeimported'] = 'Contenido a importar';
$string['activities'] = 'actividades';
$string['resources'] = 'recursos';
$string['activity'] = 'actividad';
$string['resource'] = 'recurso';
$string['emptysection'] = 'Esta sección está vacía';

// Options.
$string['importoptions'] = 'Opciones de importación';
$string['options'] = 'Opciones';
$string['resetdates'] = 'Resetear fechas';
$string['resetdates_desc'] = 'Resetear todas las fechas de actividades relativas a la fecha de inicio del nuevo curso.';
$string['includerestrictions'] = 'Incluir restricciones de acceso';
$string['includerestrictions_desc'] = 'Importar las restricciones de acceso configuradas en las actividades.';
$string['includegradebook'] = 'Incluir estructura del libro de calificaciones';
$string['includegradebook_desc'] = 'Importar categorías y configuración del libro de calificaciones (no las notas de estudiantes).';
$string['includegroups'] = 'Incluir restricciones de grupo';
$string['includegroups_desc'] = 'Importar restricciones basadas en grupos (los grupos deben existir en el destino).';

// Destination selection.
$string['destinationcourse'] = 'Curso destino';
$string['selectdestination'] = 'Seleccionar destino';
$string['currentcourse'] = 'Curso actual';
$string['insertposition'] = 'Posición de inserción';
$string['position_start'] = 'Al principio del curso';
$string['position_end'] = 'Al final del curso';
$string['position_after'] = 'Después de la sección:';
$string['newsectionname'] = 'Nuevo nombre de sección (opcional)';
$string['keepsectionname'] = 'Mantener nombre original';

// Import process.
$string['import'] = 'Importar';
$string['importing'] = 'Importando...';
$string['importprogress'] = 'Progreso de importación';
$string['preparingimport'] = 'Preparando importación...';
$string['creatingbackup'] = 'Creando copia de seguridad de la sección origen...';
$string['restoringcontent'] = 'Restaurando contenido en el destino...';
$string['finalizingimport'] = 'Finalizando importación...';

// Results.
$string['importsuccessful'] = 'Importación exitosa';
$string['importcompleted'] = 'La sección se ha importado correctamente.';
$string['importedactivities'] = '{$a} actividades importadas';
$string['importedresources'] = '{$a} recursos importados';
$string['viewimportedsection'] = 'Ver sección importada';
$string['importanother'] = 'Importar otra unidad';

// Errors.
$string['importfailed'] = 'Error en la importación';
$string['error_nocourseaccess'] = 'No tienes acceso a este curso.';
$string['error_nosectionaccess'] = 'No tienes acceso a esta sección.';
$string['error_sectionnotfound'] = 'Sección no encontrada.';
$string['error_coursenotfound'] = 'Curso no encontrado.';
$string['error_backupfailed'] = 'Error al crear la copia de seguridad de la sección origen.';
$string['error_restorefailed'] = 'Error al restaurar el contenido en el destino.';
$string['error_nopermission'] = 'No tienes permiso para realizar esta acción.';
$string['error_invalidsection'] = 'Sección seleccionada no válida.';
$string['error_invalidcourse'] = 'Curso seleccionado no válido.';

// Confirmation.
$string['confirmmimport'] = 'Confirmar importación';
$string['confirmimportmessage'] = 'Estás a punto de importar el siguiente contenido:';
$string['importwarning'] = 'Esta acción no se puede deshacer. Asegúrate de haber seleccionado la sección correcta.';

// Settings.
$string['defaultoptions'] = 'Opciones de importación por defecto';
$string['defaultoptions_desc'] = 'Configura las opciones por defecto para importar unidades.';
$string['maxsections'] = 'Máximo de secciones';
$string['maxsections_desc'] = 'Número máximo de secciones que se pueden importar a la vez.';

// History.
$string['importhistory'] = 'Historial de importaciones';
$string['recentimports'] = 'Importaciones recientes';
$string['nohistory'] = 'Sin historial de importaciones';
$string['importedon'] = 'Importado el {$a}';
$string['importedby'] = 'Importado por {$a}';

// Favorites.
$string['favorites'] = 'Favoritos';
$string['addtofavorites'] = 'Añadir a favoritos';
$string['removefromfavorites'] = 'Quitar de favoritos';
$string['nofavorites'] = 'Sin plantillas favoritas';
$string['favoriteadded'] = 'Añadido a favoritos';
$string['favoriteremoved'] = 'Eliminado de favoritos';

// Navigation.
$string['next'] = 'Siguiente';
$string['previous'] = 'Anterior';
$string['cancel'] = 'Cancelar';
$string['close'] = 'Cerrar';
$string['back'] = 'Volver';

// Misc.
$string['loading'] = 'Cargando...';
$string['section'] = 'Sección';
$string['course'] = 'Curso';
$string['category'] = 'Categoría';

// Templates.
$string['templates'] = 'Plantillas';
$string['searchtemplates'] = 'Buscar plantillas...';
$string['notemplates'] = 'No hay plantillas disponibles';
$string['saveastemplate'] = 'Guardar como plantilla';
$string['savetemplate'] = 'Guardar plantilla';
$string['templatesaved'] = 'Plantilla guardada correctamente';
$string['templatedeleted'] = 'Plantilla eliminada correctamente';
$string['templatename'] = 'Nombre de la plantilla';
$string['templatedescription'] = 'Descripción';
$string['templatedescription_placeholder'] = 'Describe qué contiene esta plantilla...';
$string['templatetags'] = 'Etiquetas';
$string['templatetags_placeholder'] = 'matemáticas, álgebra, principiante';
$string['templatetags_help'] = 'Separa las etiquetas con comas';

// Share levels.
$string['sharelevel'] = 'Compartir con';
$string['sharelevel_personal'] = 'Solo yo';
$string['sharelevel_personal_desc'] = 'Solo tú puedes ver y usar esta plantilla';
$string['sharelevel_category'] = 'Mi departamento';
$string['sharelevel_category_desc'] = 'Los profesores de la misma categoría pueden usar esta plantilla';
$string['sharelevel_global'] = 'Toda la institución';
$string['sharelevel_global_desc'] = 'Todos los profesores del sitio pueden usar esta plantilla';

// Template management.
$string['managetemplates'] = 'Gestionar plantillas';
$string['mytemplates'] = 'Mis plantillas';
$string['sharedtemplates'] = 'Plantillas compartidas';
$string['globaltemplates'] = 'Plantillas globales';
$string['templateusage'] = 'Usado {$a} veces';
$string['updatetemplate'] = 'Actualizar plantilla';
$string['deletetemplate'] = 'Eliminar plantilla';
$string['confirmdeletetemplate'] = '¿Estás seguro de que quieres eliminar esta plantilla?';

// Duplicate section.
$string['duplicatesection'] = 'Duplicar sección';
$string['duplicating'] = 'Duplicando...';
$string['duplicatesuccessful'] = 'Sección duplicada correctamente';
$string['duplicateposition'] = 'Ubicación';
$string['position_after_source'] = 'Después de la sección original';
$string['copy'] = 'copia';

// Export section.
$string['exportsection'] = 'Exportar sección';
$string['exportasmbz'] = 'Exportar como .mbz';
$string['exporting'] = 'Exportando...';
$string['exportsuccessful'] = 'Exportación exitosa';
$string['downloadbackup'] = 'Descargar copia de seguridad';
$string['exportfilename'] = 'Nombre del archivo de exportación';
$string['exportfilename_help'] = 'Deja vacío para generar automáticamente';

// Search sections.
$string['searchsections'] = 'Buscar secciones';
$string['searchbyactivity'] = 'Buscar por tipo de actividad';
$string['activitytypes'] = 'Tipos de actividad';
$string['filterbyactivity'] = 'Filtrar por tipo de actividad';
$string['selectactivitytypes'] = 'Selecciona tipos de actividad...';
$string['allactivities'] = 'Todas las actividades';
$string['sectionsmatching'] = '{$a} secciones encontradas';
$string['nosectionsfound'] = 'No se encontraron secciones que coincidan';
$string['searchplaceholder'] = 'Buscar por nombre de sección...';
$string['advancedsearch'] = 'Búsqueda avanzada';
$string['clearfilters'] = 'Limpiar filtros';

// Batch import.
$string['batchimport'] = 'Importación masiva';
$string['batchimportdesc'] = 'Importar múltiples secciones a la vez';
$string['selectmultiple'] = 'Seleccionar múltiples secciones';
$string['selectedsections'] = 'Secciones seleccionadas';
$string['nosectionsselected'] = 'Sin secciones seleccionadas';
$string['batchimporting'] = 'Importando secciones...';
$string['batchimportcomplete'] = '{$a->success} de {$a->total} secciones importadas correctamente';
$string['batchimportfailed'] = 'Algunas importaciones fallaron. Revisa los resultados individuales.';
$string['addtoqueue'] = 'Añadir a la cola';
$string['removefromqueue'] = 'Quitar de la cola';
$string['clearqueue'] = 'Limpiar todo';
$string['importqueue'] = 'Cola de importación';
$string['queueitems'] = '{$a} elementos en cola';
$string['error_toomanyimports'] = 'Máximo {$a} secciones pueden importarse a la vez';
$string['processingitem'] = 'Procesando {$a->current} de {$a->total}...';

// Template versioning.
$string['versions'] = 'Versiones';
$string['versionhistory'] = 'Historial de versiones';
$string['currentversion'] = 'Versión actual';
$string['version'] = 'Versión {$a}';
$string['versioncreated'] = 'Versión {$a} creada correctamente';
$string['createversion'] = 'Crear nueva versión';
$string['updatetemplate'] = 'Actualizar plantilla';
$string['changelog'] = 'Descripción del cambio';
$string['changelog_help'] = 'Describe qué cambió en esta versión';
$string['noversions'] = 'No hay historial de versiones disponible';
$string['viewversions'] = 'Ver versiones';
$string['restoreversion'] = 'Restaurar esta versión';
$string['confirmrestore'] = '¿Estás seguro de que quieres restaurar la versión {$a}?';
$string['versionrestored'] = 'Versión restaurada correctamente';
$string['compareversions'] = 'Comparar versiones';
