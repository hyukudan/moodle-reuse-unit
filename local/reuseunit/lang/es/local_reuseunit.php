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
 * @copyright  2025 Your Name
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

// Notifications.
$string['messageprovider:importcompleted'] = 'Notificación de importación completada';
$string['messageprovider:templateshared'] = 'Notificación de plantilla compartida';
$string['messageprovider:templateapprovalneeded'] = 'Notificación de aprobación de plantilla requerida';
$string['messageprovider:templateapproved'] = 'Notificación de plantilla aprobada';
$string['messageprovider:templaterejected'] = 'Notificación de plantilla rechazada';
$string['messageprovider:templateupdated'] = 'Notificación de plantilla actualizada';
$string['messageprovider:scheduledimportcompleted'] = 'Notificación de importación programada completada';

$string['notification_importcompleted_subject'] = 'Importación de sección completada';
$string['notification_importcompleted_message'] = 'La sección "{$a->sectionname}" ha sido importada correctamente al curso "{$a->coursename}". Se importaron {$a->activities} actividades y {$a->resources} recursos.';

$string['notification_templateshared_subject'] = 'Una plantilla ha sido compartida contigo';
$string['notification_templateshared_message'] = 'La plantilla "{$a->templatename}" ha sido compartida contigo por {$a->sharername}.';

$string['notification_approvalneeded_subject'] = 'Aprobación de plantilla requerida';
$string['notification_approvalneeded_message'] = 'La plantilla "{$a->templatename}" enviada por {$a->submittername} requiere tu aprobación para ser publicada globalmente.';

$string['notification_templateapproved_subject'] = 'Tu plantilla ha sido aprobada';
$string['notification_templateapproved_message'] = 'Tu plantilla "{$a->templatename}" ha sido aprobada y ahora está disponible globalmente.';

$string['notification_templaterejected_subject'] = 'Tu plantilla ha sido rechazada';
$string['notification_templaterejected_message'] = 'Tu plantilla "{$a->templatename}" ha sido rechazada. Motivo: {$a->reason}';

$string['notification_templateupdated_subject'] = 'Plantilla actualizada';
$string['notification_templateupdated_message'] = 'La plantilla "{$a->templatename}" ha sido actualizada a la versión {$a->version}.';

$string['notification_scheduledimport_subject'] = 'Importación programada completada';
$string['notification_scheduledimport_message'] = 'La importación programada al curso "{$a->coursename}" ha finalizado. Se importaron {$a->sectionsimported} secciones. Estado: {$a->status}';

$string['viewcourse'] = 'Ver curso';
$string['viewtemplates'] = 'Ver plantillas';
$string['reviewtemplate'] = 'Revisar plantilla';
$string['noreasonprovided'] = 'No se proporcionó motivo';

// Dashboard.
$string['dashboard'] = 'Panel de control';
$string['usagestatistics'] = 'Estadísticas de uso';
$string['periodfilter'] = 'Filtro de período';
$string['alltime'] = 'Todo el tiempo';
$string['lastmonth'] = 'Último mes';
$string['lastweek'] = 'Última semana';
$string['totalimports'] = 'Total de importaciones';
$string['successfulimports'] = 'Importaciones exitosas';
$string['activitiesimported_stat'] = 'Actividades importadas';
$string['resourcesimported_stat'] = 'Recursos importados';
$string['totaltemplates'] = 'Total de plantillas';
$string['templateusagestat'] = 'Usos de plantillas';
$string['toptemplates'] = 'Plantillas más usadas';
$string['importsbycourse'] = 'Importaciones por curso';
$string['importsbyactivity'] = 'Importaciones por tipo de actividad';
$string['viewallusers'] = 'Ver todos los usuarios';
$string['nodata'] = 'No hay datos disponibles';
$string['content'] = 'Contenido';
$string['when'] = 'Cuándo';
$string['justnow'] = 'Ahora mismo';
$string['minutesago'] = 'Hace {$a} minutos';
$string['hoursago'] = 'Hace {$a} horas';
$string['daysago'] = 'Hace {$a} días';

// Approval workflow.
$string['templateapproval'] = 'Aprobación de plantillas';
$string['pendingtemplates'] = 'Plantillas pendientes';
$string['refresh'] = 'Actualizar';
$string['submittedby'] = 'Enviado por';
$string['source'] = 'Origen';
$string['submittedon'] = 'Fecha de envío';
$string['actions'] = 'Acciones';
$string['approve'] = 'Aprobar';
$string['reject'] = 'Rechazar';
$string['nopendingapprovals'] = 'No hay plantillas pendientes de aprobación';
$string['rejecttemplate'] = 'Rechazar plantilla';
$string['rejectionreason'] = 'Motivo del rechazo';
$string['rejectionreason_placeholder'] = 'Explica por qué se rechaza esta plantilla...';
$string['rejectionreason_help'] = 'Este motivo se enviará al propietario de la plantilla.';
$string['confirmapproval'] = 'Confirmar aprobación';
$string['confirmapprovalmsg'] = '¿Estás seguro de que quieres aprobar esta plantilla? Estará disponible globalmente.';
$string['submittedforapproval'] = 'Plantilla enviada para aprobación';
$string['templateapprovedmsg'] = 'La plantilla ha sido aprobada correctamente';
$string['templaterejectedmsg'] = 'La plantilla ha sido rechazada';
$string['submitforglobal'] = 'Enviar para aprobación global';
$string['error_alreadyapproved'] = 'Esta plantilla ya está aprobada';
$string['error_notpending'] = 'Esta plantilla no está pendiente de aprobación';
$string['pendingapproval'] = 'Pendiente de aprobación';
$string['approved'] = 'Aprobada';
$string['rejected'] = 'Rechazada';

// Section synchronization.
$string['syncsection'] = 'Sincronizar sección';
$string['linksection'] = 'Vincular a plantilla';
$string['unlinksection'] = 'Desvincular de plantilla';
$string['linkedsections'] = 'Secciones vinculadas';
$string['sectionlinked'] = 'Sección vinculada a la plantilla correctamente';
$string['sectionunlinked'] = 'Sección desvinculada de la plantilla';
$string['synccompleted'] = 'Sincronización completada correctamente';
$string['syncfailed'] = 'Error en la sincronización';
$string['autosync'] = 'Auto-sincronización';
$string['autosync_desc'] = 'Sincronizar automáticamente cuando se actualiza la plantilla';
$string['manualonly'] = 'Solo manual';
$string['syncnow'] = 'Sincronizar ahora';
$string['updateavailable'] = 'Actualización disponible';
$string['noupdateavailable'] = 'Actualizado';
$string['lastsynced'] = 'Última sincronización';
$string['syncmode'] = 'Modo de sincronización';
$string['syncmode_replace'] = 'Reemplazar (eliminar existente e importar)';
$string['syncmode_merge'] = 'Fusionar (mantener existente y añadir nuevo)';
$string['confirmsync'] = 'Confirmar sincronización';
$string['confirmsyncmsg'] = 'Esto actualizará el contenido de la sección desde la plantilla. ¿Continuar?';
$string['keeplinked'] = 'Mantener vinculado para futuras actualizaciones';
$string['keeplinked_desc'] = 'Mantener un vínculo para recibir notificaciones cuando se actualice la plantilla';
$string['nolinkedsections'] = 'No hay secciones vinculadas en este curso';
$string['linkedto'] = 'Vinculado a: {$a}';
$string['error_sourcenotfound'] = 'Contenido de plantilla origen no encontrado';

// Scheduled imports.
$string['scheduleimport'] = 'Programar importación';
$string['scheduledimports'] = 'Importaciones programadas';
$string['importscheduled'] = 'Importación programada correctamente';
$string['schedulecancelled'] = 'Importación programada cancelada';
$string['schedulefor'] = 'Programar para';
$string['importnow'] = 'Importar ahora';
$string['importlater'] = 'Importar después';
$string['scheduledtime'] = 'Hora programada';
$string['noscheduledimports'] = 'No hay importaciones programadas';
$string['status_pending'] = 'Pendiente';
$string['status_running'] = 'En ejecución';
$string['status_completed'] = 'Completado';
$string['status_completedwitherrors'] = 'Completado con errores';
$string['status_cancelled'] = 'Cancelado';
$string['completed'] = 'Completado';
$string['completedwitherrors'] = 'Completado con errores';
$string['cancelschedule'] = 'Cancelar';
$string['viewdetails'] = 'Ver detalles';
$string['error_pasttime'] = 'La hora programada no puede ser en el pasado';
$string['error_cannotcancel'] = 'Esta importación programada no se puede cancelar';
$string['task_scheduledimport'] = 'Procesar importación de sección programada';
$string['task_cleanup'] = 'Limpiar datos antiguos de Reuse Unit';

// Diff comparison.
$string['compare'] = 'Comparar';
$string['comparesections'] = 'Comparar secciones';
$string['compareversions'] = 'Comparar versiones';
$string['comparison'] = 'Comparación';
$string['differences'] = 'Diferencias';
$string['nodifferences'] = 'No se encontraron diferencias';
$string['added'] = 'Añadido';
$string['removed'] = 'Eliminado';
$string['modified'] = 'Modificado';
$string['unchanged'] = 'Sin cambios';
$string['itemsadded'] = '{$a} elementos serán añadidos';
$string['itemsremoved'] = '{$a} elementos serán eliminados';
$string['itemsmodified'] = '{$a} elementos modificados';
$string['itemsunchanged'] = '{$a} elementos sin cambios';
$string['previewchanges'] = 'Vista previa de cambios';
$string['selectversions'] = 'Seleccionar versiones a comparar';
$string['olderversion'] = 'Versión anterior';
$string['newerversion'] = 'Versión más reciente';
$string['changesummary'] = 'Resumen de cambios';
$string['increased'] = 'Aumentado';
$string['decreased'] = 'Disminuido';
$string['changed'] = 'Cambiado';

// Privacy API.
$string['privacy:metadata:templates'] = 'Información sobre plantillas creadas por usuarios.';
$string['privacy:metadata:templates:userid'] = 'El ID del usuario que creó la plantilla.';
$string['privacy:metadata:templates:name'] = 'El nombre de la plantilla.';
$string['privacy:metadata:templates:description'] = 'La descripción de la plantilla.';
$string['privacy:metadata:templates:tags'] = 'Etiquetas asociadas a la plantilla.';
$string['privacy:metadata:templates:timecreated'] = 'Cuándo se creó la plantilla.';
$string['privacy:metadata:templates:timemodified'] = 'Cuándo se modificó la plantilla por última vez.';

$string['privacy:metadata:history'] = 'Registro de importaciones de secciones realizadas por usuarios.';
$string['privacy:metadata:history:userid'] = 'El ID del usuario que realizó la importación.';
$string['privacy:metadata:history:source_courseid'] = 'El ID del curso origen.';
$string['privacy:metadata:history:source_sectionid'] = 'El ID de la sección origen.';
$string['privacy:metadata:history:target_courseid'] = 'El ID del curso destino.';
$string['privacy:metadata:history:target_sectionid'] = 'El ID de la sección destino.';
$string['privacy:metadata:history:timecreated'] = 'Cuándo se realizó la importación.';

$string['privacy:metadata:favorites'] = 'Información sobre plantillas favoritas del usuario.';
$string['privacy:metadata:favorites:userid'] = 'El ID del usuario que añadió el favorito.';
$string['privacy:metadata:favorites:templateid'] = 'El ID de la plantilla favorita.';
$string['privacy:metadata:favorites:timecreated'] = 'Cuándo se añadió la plantilla a favoritos.';

$string['privacy:metadata:scheduled'] = 'Información sobre importaciones programadas.';
$string['privacy:metadata:scheduled:userid'] = 'El ID del usuario que programó la importación.';
$string['privacy:metadata:scheduled:scheduled_time'] = 'Cuándo está programada la importación.';
$string['privacy:metadata:scheduled:status'] = 'El estado de la importación programada.';
$string['privacy:metadata:scheduled:timecreated'] = 'Cuándo se creó la importación programada.';

$string['privacy:metadata:links'] = 'Información sobre secciones vinculadas para sincronización.';
$string['privacy:metadata:links:userid'] = 'El ID del usuario que creó el vínculo.';
$string['privacy:metadata:links:templateid'] = 'El ID de la plantilla vinculada.';
$string['privacy:metadata:links:timecreated'] = 'Cuándo se creó el vínculo.';

// Settings.
$string['generalsettings'] = 'Configuración general';
$string['enabled'] = 'Habilitar plugin';
$string['enabled_desc'] = 'Habilitar o deshabilitar la funcionalidad del plugin Reutilizar Unidad.';
$string['showincourse'] = 'Mostrar en menú del curso';
$string['showincourse_desc'] = 'Mostrar la opción de importar unidad en el menú del curso.';
$string['limits'] = 'Límites';
$string['maxtemplates'] = 'Máximo de plantillas por usuario';
$string['maxtemplates_desc'] = 'Número máximo de plantillas que un usuario puede crear.';
$string['historyretention'] = 'Retención del historial (días)';
$string['historyretention_desc'] = 'Número de días para mantener registros del historial de importaciones.';
$string['templatesettings'] = 'Configuración de plantillas';
$string['allowglobal'] = 'Permitir plantillas globales';
$string['allowglobal_desc'] = 'Permitir a los usuarios compartir plantillas globalmente en todo el sitio.';
$string['requireapproval'] = 'Requerir aprobación para plantillas globales';
$string['requireapproval_desc'] = 'Requerir aprobación del administrador antes de que las plantillas estén disponibles globalmente.';
$string['allowcategoryshare'] = 'Permitir compartir por categoría';
$string['allowcategoryshare_desc'] = 'Permitir a los usuarios compartir plantillas dentro de su categoría de curso.';
$string['syncsettings'] = 'Configuración de sincronización';
$string['allowautosync'] = 'Permitir auto-sincronización';
$string['allowautosync_desc'] = 'Permitir que las secciones se sincronicen automáticamente cuando se actualizan las plantillas.';
$string['defaultsyncmode'] = 'Modo de sincronización por defecto';
$string['defaultsyncmode_desc'] = 'Modo predeterminado para sincronizar secciones con plantillas.';
$string['notificationsettings'] = 'Configuración de notificaciones';
$string['notifyimport'] = 'Notificar al completar importación';
$string['notifyimport_desc'] = 'Enviar una notificación cuando se complete una importación.';
$string['notifytemplate'] = 'Notificar actualizaciones de plantillas';
$string['notifytemplate_desc'] = 'Enviar notificaciones cuando se comparten o actualizan plantillas.';
$string['cleanupsettings'] = 'Configuración de limpieza';
$string['autocleanup'] = 'Limpieza automática de datos antiguos';
$string['autocleanup_desc'] = 'Limpiar automáticamente importaciones programadas antiguas y registros del historial.';
$string['cleanupage'] = 'Antigüedad de limpieza (días)';
$string['cleanupage_desc'] = 'Antigüedad en días después de la cual se limpian las importaciones programadas completadas.';

// Importación granular.
$string['selectall'] = 'Seleccionar todo';
$string['selected'] = 'seleccionados';
$string['partialimport'] = 'Importación parcial';
$string['partialimport_desc'] = 'Solo se importarán y sincronizarán los elementos seleccionados.';
$string['granularimport'] = 'Importación granular';
$string['granularimport_desc'] = 'Selecciona actividades y recursos específicos para importar.';
$string['selectactivities'] = 'Seleccionar actividades';
$string['deselectall'] = 'Deseleccionar todo';
$string['selecteditems'] = '{$a} elementos seleccionados';
$string['allitemsselected'] = 'Todos los elementos seleccionados';
$string['noitemsselected'] = 'Ningún elemento seleccionado';
$string['includenew'] = 'Incluir nuevas actividades';
$string['includenew_desc'] = 'También importar nuevas actividades que se agregaron a la plantilla desde tu última importación.';
$string['synconlyimported'] = 'Solo sincronizar elementos originalmente importados';
$string['newitemsavailable'] = '{$a} nuevos elementos disponibles en la plantilla';
$string['partialsynccompleted'] = 'Sincronización parcial completada ({$a} elementos)';
$string['importselection'] = 'Importar selección';

// Detección de actualizaciones.
$string['task_checkupdates'] = 'Verificar actualizaciones de secciones vinculadas';
$string['updateavailable'] = 'Actualización disponible';
$string['noupdates'] = 'Sin actualizaciones disponibles';
$string['lastchecked'] = 'Última verificación: {$a}';
$string['checkforupdates'] = 'Buscar actualizaciones';
$string['notification_updateavailable_subject'] = 'Actualización de plantilla disponible';
$string['notification_updateavailable_message'] = 'La plantilla "{$a->templatename}" vinculada a tu curso "{$a->coursename}" ha sido actualizada. Cambios: {$a->changes}. Por favor sincroniza para obtener el contenido más reciente.';
$string['notification_autosynccompleted_subject'] = 'Sección sincronizada automáticamente';
$string['notification_autosynccompleted_message'] = 'La sección vinculada a la plantilla "{$a->templatename}" en tu curso "{$a->coursename}" ha sido sincronizada automáticamente con los últimos cambios de la plantilla.';
