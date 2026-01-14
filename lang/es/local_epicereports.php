<?php
/**
 * Strings en español para local_epicereports
 *
 * @package    local_epicereports
 * @copyright  2024 EpicE
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// General.
$string['pluginname'] = 'EpicE Reports';
$string['pluginname_help'] = 'Sistema avanzado de reportes para Moodle';

// Dashboard.
$string['dashboard'] = 'Dashboard';
$string['dashboardwelcome'] = 'Bienvenido al centro de reportes. Aquí encontrarás estadísticas generales de la plataforma.';
$string['quickaccess'] = 'Acceso rápido';

// Estadísticas.
$string['active_users'] = 'Usuarios activos';
$string['visible_courses'] = 'Cursos visibles';
$string['hidden_courses'] = 'Cursos ocultos';
$string['satisfaction'] = 'Satisfacción';
$string['students'] = 'Estudiantes';

// Navegación.
$string['courses'] = 'Cursos';
$string['courselist'] = 'Listado de cursos';
$string['coursedetail'] = 'Detalle del curso';
$string['backtomoodle'] = 'Volver a Moodle';
$string['viewcourses'] = 'Ver todos los cursos';
$string['visiblecoursesonly'] = 'Solo cursos visibles';
$string['hiddencoursesonly'] = 'Solo cursos ocultos';
$string['viewdetail'] = 'Ver detalle';

// Filtros y formularios.
$string['filter'] = 'Filtrar';
$string['category'] = 'Categoría';
$string['visibility'] = 'Visibilidad';
$string['all'] = 'Todos';
$string['visible'] = 'Visible';
$string['hidden'] = 'Oculto';
$string['yes'] = 'Sí';
$string['no'] = 'No';
$string['actions'] = 'Acciones';
$string['status'] = 'Estado';
$string['results'] = 'Resultados';

// Cursos.
$string['course'] = 'Curso';
$string['shortname'] = 'Nombre corto';
$string['nocourses'] = 'No se encontraron cursos.';
$string['enrolledusers'] = 'Usuarios matriculados';
$string['nousers'] = 'No hay usuarios matriculados en este curso.';
$string['generalinfo'] = 'Información general';
$string['enrolled'] = 'Matriculados';
$string['currentcourse'] = 'Curso actual';
$string['scheduleforcourse'] = 'Programación de reportes para';

// Campos de usuario.
$string['fullname'] = 'Nombre completo';
$string['email'] = 'Correo electrónico';
$string['firstaccess'] = 'Primer acceso';
$string['lastaccess'] = 'Último acceso';
$string['groups'] = 'Grupos';
$string['progress'] = 'Progreso';
$string['completionstatus'] = 'Estado';
$string['finalgrade'] = 'Nota final';
$string['name'] = 'Nombre';

// Exportación.
$string['exporttoexcel'] = 'Exportar Excel';
$string['preview'] = 'Vista previa';
$string['downloadcertificates'] = 'Descargar diplomas';
$string['exportfeedback'] = 'Exportar encuestas';

// Reportes programados.
$string['scheduledreports'] = 'Reportes programados';
$string['schedules'] = 'Programaciones';
$string['newschedule'] = 'Nueva programación';
$string['editschedule'] = 'Editar programación';
$string['schedulename'] = 'Nombre';
$string['scheduleenabled'] = 'Habilitado';
$string['schedulestatus'] = 'Estado de programación';
$string['enabled'] = 'Habilitado';
$string['disabled'] = 'Deshabilitado';
$string['active'] = 'Activo';
$string['inactive'] = 'Inactivo';
$string['noschedules'] = 'No hay programaciones configuradas.';

// Días.
$string['senddays'] = 'Días de envío';
$string['monday'] = 'Lunes';
$string['tuesday'] = 'Martes';
$string['wednesday'] = 'Miércoles';
$string['thursday'] = 'Jueves';
$string['friday'] = 'Viernes';
$string['saturday'] = 'Sábado';
$string['sunday'] = 'Domingo';

// Horarios.
$string['sendtime'] = 'Hora de envío';
$string['startdate'] = 'Fecha inicio';
$string['enddate'] = 'Fecha fin';
$string['lastrun'] = 'Última ejecución';
$string['nextrun'] = 'Próxima ejecución';
$string['daterange'] = 'Rango de fechas';
$string['noenddate'] = 'Sin fecha de fin';

// Contenido de reporte.
$string['reportcontent'] = 'Contenido del reporte';
$string['includecourseexcel'] = 'Incluir reporte Excel del curso';
$string['includefeedbackexcel'] = 'Incluir reporte de encuestas';

// Email.
$string['emailsettings'] = 'Configuración de correo';
$string['emailsubject'] = 'Asunto del correo';
$string['emailbody'] = 'Cuerpo del correo';
$string['emailsubjectdefault'] = 'Reporte del curso: {coursename}';
$string['emailbodydefault'] = 'Estimado/a {recipientname},

Adjunto encontrará el reporte del curso {coursename}.

Fecha de generación: {date}

Saludos cordiales.';

// Destinatarios.
$string['recipients'] = 'Destinatarios';
$string['recipient'] = 'Destinatario';
$string['addrecipient'] = 'Agregar destinatario';
$string['recipienttype'] = 'Tipo';
$string['norecipients'] = 'No hay destinatarios configurados.';
$string['recipientadded'] = 'Destinatario agregado correctamente.';
$string['recipientdeleted'] = 'Destinatario eliminado correctamente.';
$string['type'] = 'Tipo';
$string['optional'] = 'Opcional';
$string['add'] = 'Agregar';
$string['back'] = 'Volver';

// Logs.
$string['reportlogs'] = 'Historial de envíos';
$string['nologs'] = 'No hay registros de envío.';
$string['timescheduled'] = 'Programado';
$string['timesent'] = 'Enviado';
$string['retries'] = 'Reintentos';
$string['error'] = 'Error';

// Estados de envío.
$string['sent'] = 'Enviado';
$string['failed'] = 'Fallido';
$string['pending'] = 'Pendiente';
$string['retry'] = 'Reintentando';

// Herramientas.
$string['tools'] = 'Herramientas';
$string['testemail'] = 'Probar correo';
$string['testreports'] = 'Probar reportes';
$string['testemailinfo'] = 'Utilice esta herramienta para probar el envío de correos desde la plataforma.';
$string['testreportsinfo'] = 'Utilice esta herramienta para probar la generación de reportes del curso.';
$string['emailconfig'] = 'Configuración de correo';
$string['sendtestemail'] = 'Enviar correo de prueba';
$string['options'] = 'Opciones';
$string['includeattachment'] = 'Incluir archivo adjunto de prueba';
$string['testemailsent'] = 'Correo de prueba enviado correctamente a';
$string['testemailfailed'] = 'Error al enviar el correo de prueba.';

// Acciones.
$string['togglestatus'] = 'Cambiar estado';
$string['confirmdelete'] = '¿Está seguro de que desea eliminar este elemento?';
$string['scheduledeleted'] = 'Programación eliminada correctamente.';
$string['schedulecreated'] = 'Programación creada correctamente.';
$string['scheduleupdated'] = 'Programación actualizada correctamente.';
$string['edit'] = 'Editar';
$string['delete'] = 'Eliminar';
$string['save'] = 'Guardar';
$string['cancel'] = 'Cancelar';

// Placeholders.
$string['availableplaceholders'] = 'Placeholders disponibles';
$string['placeholder_coursename'] = 'Nombre completo del curso';
$string['placeholder_courseshortname'] = 'Nombre corto del curso';
$string['placeholder_date'] = 'Fecha actual (formato corto)';
$string['placeholder_datetime'] = 'Fecha y hora actual';
$string['placeholder_recipientname'] = 'Nombre del destinatario';
$string['placeholder_recipientemail'] = 'Email del destinatario';

// Errores.
$string['error:invalidcourseid'] = 'ID de curso inválido.';
$string['error:invalidscheduleid'] = 'ID de programación inválido.';
$string['error:nopermission'] = 'No tiene permisos para realizar esta acción.';

// Capacidades.
$string['epicereports:view'] = 'Ver reportes EpicE';
$string['epicereports:manage'] = 'Gestionar reportes EpicE';

// Errores de validación del formulario.
$string['error:nodaysselected'] = 'Debe seleccionar al menos un día de la semana.';
$string['error:enddatebeforestart'] = 'La fecha de fin debe ser posterior a la fecha de inicio.';
$string['error:noreportselected'] = 'Debe seleccionar al menos un tipo de reporte.';

// Help strings.
$string['schedulename_help'] = 'Nombre descriptivo para identificar esta programación.';
$string['startdate_help'] = 'Fecha desde la cual comenzará a enviarse el reporte.';
$string['enddate_help'] = 'Fecha hasta la cual se enviará el reporte. Dejar vacío para envío indefinido.';
$string['sendtime_help'] = 'Hora del día en que se enviará el reporte.';
$string['senddays_help'] = 'Días de la semana en los que se enviará el reporte.';
$string['includecourseexcel_help'] = 'Incluir el reporte Excel con el progreso de los estudiantes.';
$string['includefeedbackexcel_help'] = 'Incluir el reporte de encuestas de satisfacción.';
$string['emailsubject_help'] = 'Asunto del correo electrónico. Puede usar marcadores como {coursename}.';
$string['emailbody_help'] = 'Cuerpo del correo electrónico. Puede usar marcadores como {coursename}, {date}, {recipientname}.';

// Envío manual.
$string['sendnow'] = 'Enviar ahora';
$string['confirmsendnow'] = '¿Está seguro que desea enviar este reporte ahora a todos los destinatarios configurados?';
$string['manualsend:success'] = 'Reporte enviado exitosamente a {$a} destinatario(s).';
$string['manualsend:partial'] = 'Reporte enviado con algunos errores: {$a->success} exitosos, {$a->errors} fallidos.';
$string['error:norecipients'] = 'No hay destinatarios configurados para esta programación.';
$string['error:noreportsgenerated'] = 'No se pudieron generar los reportes.';
$string['error:sendfailed'] = 'Error al enviar el reporte';
$string['error:invalidscheduleid'] = 'ID de programación inválido.';

// Envío manual.
$string['sendnow'] = 'Enviar ahora';
$string['confirmsendnow'] = '¿Está seguro que desea enviar el reporte ahora a todos los destinatarios habilitados?';
$string['manualsend:success'] = 'Reporte enviado exitosamente a {$a} destinatario(s).';
$string['manualsend:partial'] = 'Envío parcial: {$a->success} exitoso(s), {$a->errors} error(es).';
$string['error:norecipients'] = 'No hay destinatarios habilitados para esta programación.';
$string['error:noreportsgenerated'] = 'No se pudo generar ningún reporte.';
$string['error:sendfailed'] = 'Error al enviar el reporte';
$string['error:invalidscheduleid'] = 'ID de programación inválido.';

// Placeholders.
$string['availableplaceholders'] = 'Marcadores disponibles';
$string['placeholder:coursename'] = 'Nombre del curso';
$string['placeholder:date'] = 'Fecha del reporte';
$string['placeholder:recipientname'] = 'Nombre del destinatario';
$string['recipientsinstructions'] = 'Los destinatarios se configuran después de guardar la programación.';
$string['recipientsconfigured'] = 'destinatario(s) configurado(s)';
