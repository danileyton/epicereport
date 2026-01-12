<?php
/**
 * Spanish language strings for local_epicereports
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
$string['active_users'] = 'Usuarios Activos';
$string['visible_courses'] = 'Cursos Visibles';
$string['hidden_courses'] = 'Cursos Ocultos';

// Capabilities.
$string['epicereports:view'] = 'Ver EpicE Reports';
$string['epicereports:manageschedules'] = 'Gestionar programaciones de reportes';
$string['epicereports:viewlogs'] = 'Ver historial de envíos de reportes';
$string['epicereports:resend'] = 'Reenviar reportes manualmente';

// Schedule management.
$string['scheduledreports'] = 'Reportes Programados';
$string['newschedule'] = 'Nueva Programación';
$string['editschedule'] = 'Editar Programación';
$string['deleteschedule'] = 'Eliminar Programación';
$string['deletescheduleconfirm'] = '¿Está seguro de que desea eliminar esta programación? Esta acción no se puede deshacer.';
$string['schedulename'] = 'Nombre de la Programación';
$string['schedulename_help'] = 'Un nombre descriptivo para esta programación (ej: "Reporte semanal para Gerencia")';
$string['scheduleenabled'] = 'Habilitada';
$string['scheduledisabled'] = 'Deshabilitada';
$string['schedulestatus'] = 'Estado';

// Date and time settings.
$string['daterange'] = 'Rango de Fechas';
$string['startdate'] = 'Fecha de Inicio';
$string['startdate_help'] = 'La programación comenzará a enviar reportes a partir de esta fecha';
$string['enddate'] = 'Fecha de Término';
$string['enddate_help'] = 'La programación dejará de enviar reportes después de esta fecha. Dejar vacío para sin fecha de término.';
$string['sendtime'] = 'Hora de Envío';
$string['sendtime_help'] = 'Hora del día en que se enviarán los reportes (zona horaria del servidor)';
$string['senddays'] = 'Días de Envío';
$string['senddays_help'] = 'Seleccione los días de la semana en que se deben enviar los reportes';
$string['monday'] = 'Lunes';
$string['tuesday'] = 'Martes';
$string['wednesday'] = 'Miércoles';
$string['thursday'] = 'Jueves';
$string['friday'] = 'Viernes';
$string['saturday'] = 'Sábado';
$string['sunday'] = 'Domingo';

// Report content.
$string['reportcontent'] = 'Contenido del Reporte';
$string['includecourseexcel'] = 'Incluir Reporte del Curso (Excel)';
$string['includecourseexcel_help'] = 'Adjuntar el reporte Excel de progreso del curso al correo';
$string['includefeedbackexcel'] = 'Incluir Reporte de Encuestas (Excel)';
$string['includefeedbackexcel_help'] = 'Adjuntar el reporte Excel de respuestas de encuestas/feedback al correo';

// Email settings.
$string['emailsettings'] = 'Configuración del Correo';
$string['emailsubject'] = 'Asunto del Correo';
$string['emailsubject_help'] = 'Asunto personalizado para el correo. Puede usar marcadores: {coursename}, {date}';
$string['emailsubjectdefault'] = 'Reporte del Curso: {coursename} - {date}';
$string['emailbody'] = 'Cuerpo del Correo';
$string['emailbody_help'] = 'Mensaje personalizado del correo. Puede usar marcadores: {coursename}, {date}, {recipientname}';
$string['emailbodydefault'] = 'Estimado/a {recipientname},

Adjunto encontrará el/los reporte(s) del curso "{coursename}" generado(s) el {date}.

Este es un mensaje automático del sistema EpicE Reports.';

// Recipients.
$string['recipients'] = 'Destinatarios';
$string['addrecipient'] = 'Agregar Destinatario';
$string['removerecipient'] = 'Eliminar Destinatario';
$string['recipientemail'] = 'Correo Electrónico';
$string['recipientname'] = 'Nombre';
$string['recipienttype'] = 'Tipo';
$string['recipienttype_to'] = 'Para';
$string['recipienttype_cc'] = 'CC';
$string['recipienttype_bcc'] = 'CCO';
$string['selectuser'] = 'Seleccionar Usuario Moodle';
$string['externalrecipient'] = 'Correo Externo';
$string['norecipients'] = 'No hay destinatarios configurados';
$string['atleastonerecipient'] = 'Se requiere al menos un destinatario';
$string['duplicateemail'] = 'Este correo electrónico ya está agregado';
$string['invalidemail'] = 'Dirección de correo electrónico inválida';

// Execution.
$string['lastrun'] = 'Última Ejecución';
$string['nextrun'] = 'Próxima Ejecución';
$string['neverrun'] = 'Nunca';
$string['runningnow'] = 'Ejecutando ahora...';
$string['runnow'] = 'Ejecutar Ahora';
$string['runnowconfirm'] = '¿Está seguro de que desea ejecutar esta programación ahora? Los reportes se enviarán inmediatamente.';

// Logs.
$string['reportlogs'] = 'Historial de Envíos';
$string['viewlogs'] = 'Ver Historial';
$string['logstatus'] = 'Estado';
$string['logstatus_pending'] = 'Pendiente';
$string['logstatus_sent'] = 'Enviado';
$string['logstatus_failed'] = 'Fallido';
$string['logstatus_retry'] = 'Reintento Pendiente';
$string['logtimescheduled'] = 'Hora Programada';
$string['logtimesent'] = 'Hora de Envío';
$string['logrecipient'] = 'Destinatario';
$string['logerror'] = 'Error';
$string['logattachments'] = 'Adjuntos';
$string['logretrycount'] = 'Reintentos';
$string['resend'] = 'Reenviar';
$string['resendconfirm'] = '¿Está seguro de que desea reenviar este reporte?';
$string['resendsuccessful'] = 'El reporte ha sido encolado para reenvío';
$string['nologs'] = 'No se encontraron registros';

// Task.
$string['taskname'] = 'Enviar reportes programados';
$string['taskdescription'] = 'Procesa y envía correos de reportes programados con archivos adjuntos';

// Messages and notifications.
$string['schedulecreated'] = 'Programación creada exitosamente';
$string['scheduleupdated'] = 'Programación actualizada exitosamente';
$string['scheduledeleted'] = 'Programación eliminada exitosamente';
$string['schedulenotsaved'] = 'Error al guardar la programación';
$string['emailsent'] = 'Correo enviado exitosamente';
$string['emailfailed'] = 'Error al enviar correo';
$string['reportgenerated'] = 'Reporte generado exitosamente';
$string['reportgenerationfailed'] = 'Error al generar reporte';
$string['noactivitieswithcompletion'] = 'No hay actividades con seguimiento de finalización en este curso';

// Errors.
$string['error:invalidcourseid'] = 'ID de curso inválido';
$string['error:invalidscheduleid'] = 'ID de programación inválido';
$string['error:nopermission'] = 'No tiene permiso para realizar esta acción';
$string['error:nodaysselected'] = 'Debe seleccionar al menos un día';
$string['error:invalidtimeformat'] = 'Formato de hora inválido. Use HH:MM';
$string['error:enddatebeforestart'] = 'La fecha de término no puede ser anterior a la fecha de inicio';
$string['error:startdateinpast'] = 'La fecha de inicio no puede ser en el pasado';
$string['error:noreportselected'] = 'Debe seleccionar al menos un tipo de reporte';

// Sidebar menu.
$string['menu_schedules'] = 'Reportes Programados';
$string['menu_logs'] = 'Historial de Envíos';
