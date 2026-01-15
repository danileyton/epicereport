<?php
defined('MOODLE_INTERNAL') || die();

// General.
$string['pluginname'] = 'EpicE Reports';
$string['pluginname_help'] = 'Sistema avanzado de reportes para Moodle';

// Dashboard.
$string['dashboard'] = 'Dashboard';
$string['dashboardwelcome'] = 'Bienvenido al panel de reportes';
$string['active_users'] = 'Usuarios activos';
$string['visible_courses'] = 'Cursos visibles';
$string['hidden_courses'] = 'Cursos ocultos';
$string['total_enrolments'] = 'Matrículas totales';
$string['satisfaction_percent'] = 'Porcentaje de satisfacción';
$string['quickaccess'] = 'Acceso rápido';

// Courses list.
$string['courses'] = 'Cursos';
$string['courselist'] = 'Listado de cursos';
$string['course'] = 'Curso';
$string['coursedetail'] = 'Detalle del curso';
$string['courseid'] = 'ID del curso';
$string['students'] = 'Estudiantes';
$string['currentcourse'] = 'Curso actual';
$string['nocourses'] = 'No hay cursos disponibles.';
$string['viewcourses'] = 'Ver cursos';
$string['viewdetail'] = 'Ver detalle';

// Course filters.
$string['category'] = 'Categoría';
$string['visibility'] = 'Visibilidad';
$string['visible'] = 'Visible';
$string['hidden'] = 'Oculto';
$string['visiblecoursesonly'] = 'Solo cursos visibles';
$string['hiddencoursesonly'] = 'Solo cursos ocultos';

// Course info.
$string['fullname'] = 'Nombre completo';
$string['shortname'] = 'Nombre corto';
$string['generalinfo'] = 'Información general';
$string['enrolled'] = 'Matriculados';
$string['enrolledusers'] = 'Usuarios matriculados';

// User data.
$string['userdata'] = 'Datos del usuario';
$string['userid'] = 'ID usuario';
$string['username'] = 'Usuario';
$string['idnumber'] = 'ID interno / RUT';
$string['firstaccess'] = 'Primer acceso';
$string['lastaccess'] = 'Último acceso';
$string['groups'] = 'Grupos';

// Progress.
$string['progress'] = 'Progreso';
$string['percentage'] = 'Porcentaje';
$string['completionstatus'] = 'Estado de finalización';
$string['completiondate'] = 'Fecha de finalización';
$string['notstarted'] = 'Sin iniciar';
$string['started'] = 'Iniciado';
$string['inprogress'] = 'En progreso';
$string['completed'] = 'Completado';

// Grades.
$string['grade'] = 'Nota';
$string['finalgrade'] = 'Nota final';
$string['highestgrade'] = 'Nota más alta';
$string['score'] = 'Puntuación';
$string['attempts'] = 'Intentos';

// Certificate.
$string['certificate'] = 'Certificado';
$string['certificated'] = 'Certificados';
$string['nocertificates'] = 'No hay certificados disponibles.';
$string['noissuedcertificates'] = 'No hay certificados emitidos.';
$string['downloadcertificates'] = 'Descargar certificados';

// Feedback / Satisfaction.
$string['satisfaction'] = 'Satisfacción';
$string['feedbackreport'] = 'Reporte de encuestas';
$string['nofeedback'] = 'No hay encuestas de retroalimentación.';
$string['nofeedbackresponses'] = 'No hay respuestas de encuestas.';
$string['responsesnumber'] = 'Número de respuestas';

// Submission (assignments).
$string['submission'] = 'Entrega';
$string['submissiondate'] = 'Fecha de entrega';

// Navigation.
$string['back'] = 'Volver';
$string['backtomoodle'] = 'Volver a Moodle';

// Scheduled Reports.
$string['scheduledreports'] = 'Reportes programados';
$string['schedules'] = 'Programaciones';
$string['newschedule'] = 'Nueva programación';
$string['editschedule'] = 'Editar programación';
$string['schedulename'] = 'Nombre';
$string['schedulename_help'] = 'Nombre descriptivo para identificar esta programación.';
$string['scheduleenabled'] = 'Habilitado';
$string['schedulestatus'] = 'Estado de programación';
$string['noschedules'] = 'No hay programaciones configuradas.';
$string['schedulecreated'] = 'Programación creada exitosamente.';
$string['scheduleupdated'] = 'Programación actualizada exitosamente.';
$string['scheduledeleted'] = 'Programación eliminada exitosamente.';

// Dates.
$string['daterange'] = 'Rango de fechas';
$string['startdate'] = 'Fecha de inicio';
$string['startdate_help'] = 'Fecha desde la cual comenzará a enviarse el reporte.';
$string['enddate'] = 'Fecha de fin';
$string['enddate_help'] = 'Fecha hasta la cual se enviará el reporte. Dejar vacío para envío indefinido.';
$string['noenddate'] = 'Sin fecha de fin';
$string['exportdate'] = 'Fecha de exportación';

// Schedule Time.
$string['sendtime'] = 'Hora de envío';
$string['sendtime_help'] = 'Hora del día en que se enviará el reporte.';
$string['senddays'] = 'Días de envío';
$string['senddays_help'] = 'Días de la semana en los que se enviará el reporte.';
$string['lastrun'] = 'Última ejecución';

// Days of the week.
$string['monday'] = 'Lunes';
$string['tuesday'] = 'Martes';
$string['wednesday'] = 'Miércoles';
$string['thursday'] = 'Jueves';
$string['friday'] = 'Viernes';
$string['saturday'] = 'Sábado';
$string['sunday'] = 'Domingo';

// Report Content.
$string['reportcontent'] = 'Contenido del reporte';
$string['includecourseexcel'] = 'Incluir reporte de progreso del curso (Excel)';
$string['includecourseexcel_help'] = 'Incluir el reporte Excel con el progreso de los estudiantes.';
$string['includefeedbackexcel'] = 'Incluir reporte de encuestas (Excel)';
$string['includefeedbackexcel_help'] = 'Incluir el reporte de encuestas de satisfacción.';
$string['coursereport'] = 'Reporte del curso';

// Email Settings.
$string['emailsettings'] = 'Configuración de correo';
$string['emailsubject'] = 'Asunto del correo';
$string['emailsubject_help'] = 'Asunto del correo electrónico. Puede usar marcadores como {coursename}.';
$string['emailbody'] = 'Cuerpo del correo';
$string['emailbody_help'] = 'Cuerpo del correo electrónico. Puede usar marcadores como {coursename}, {date}, {recipientname}.';
$string['emailsubjectdefault'] = 'Reporte del curso {coursename} - {date}';
$string['emailbodydefault'] = 'Estimado/a {recipientname},

Adjunto encontrará el reporte del curso {coursename} correspondiente al {date}.

Saludos cordiales.';

// Placeholders.
$string['availableplaceholders'] = 'Marcadores disponibles';
$string['placeholder:coursename'] = 'Nombre del curso';
$string['placeholder:date'] = 'Fecha del reporte';
$string['placeholder:recipientname'] = 'Nombre del destinatario';

// Recipients.
$string['recipients'] = 'Destinatarios';
$string['recipient'] = 'Destinatario';
$string['recipientsinstructions'] = 'Los destinatarios se configuran después de guardar la programación.';
$string['recipientsconfigured'] = 'destinatario(s) configurado(s)';
$string['addrecipient'] = 'Agregar destinatario';
$string['norecipients'] = 'No hay destinatarios configurados.';
$string['recipientadded'] = 'Destinatario agregado exitosamente.';
$string['recipientdeleted'] = 'Destinatario eliminado exitosamente.';

// Status.
$string['status'] = 'Estado';
$string['enabled'] = 'Habilitado';
$string['disabled'] = 'Deshabilitado';
$string['active'] = 'Activo';
$string['inactive'] = 'Inactivo';
$string['togglestatus'] = 'Cambiar estado';

// Actions.
$string['actions'] = 'Acciones';
$string['edit'] = 'Editar';
$string['delete'] = 'Eliminar';
$string['save'] = 'Guardar';
$string['cancel'] = 'Cancelar';
$string['add'] = 'Agregar';
$string['clear'] = 'Limpiar';
$string['confirmdelete'] = '¿Está seguro que desea eliminar este elemento?';

// Manual Send.
$string['sendnow'] = 'Enviar ahora';
$string['confirmsendnow'] = '¿Está seguro que desea enviar el reporte ahora a todos los destinatarios habilitados?';
$string['manualsend:success'] = 'Reporte enviado exitosamente a {$a} destinatario(s).';
$string['manualsend:partial'] = 'Envío parcial: {$a->success} exitoso(s), {$a->errors} error(es).';

// Logs / History.
$string['logs'] = 'Historial de envíos';
$string['reportlogs'] = 'Historial de envíos';
$string['nologs'] = 'No hay registros de envío.';
$string['logdate'] = 'Fecha';
$string['logemail'] = 'Correo';
$string['logstatus'] = 'Estado';
$string['logattachments'] = 'Adjuntos';
$string['logerror'] = 'Error';

// Log table columns.
$string['timescheduled'] = 'Fecha programada';
$string['timesent'] = 'Fecha de envío';
$string['retries'] = 'Reintentos';

// Filter.
$string['filter'] = 'Filtrar';
$string['all'] = 'Todos';
$string['results'] = 'Resultados';
$string['count'] = 'Total';

// Status values.
$string['success'] = 'Exitoso';
$string['error'] = 'Error';
$string['pending'] = 'Pendiente';
$string['sent'] = 'Enviado';
$string['failed'] = 'Fallido';

// Tools.
$string['tools'] = 'Herramientas';
$string['testreports'] = 'Probar reportes';
$string['testreportsinfo'] = 'Genera y descarga reportes de prueba para verificar la configuración.';
$string['testemail'] = 'Probar correo';
$string['generatereport'] = 'Generar reporte';
$string['sendemail'] = 'Enviar correo';
$string['downloadreport'] = 'Descargar reporte';

// Export.
$string['exporttoexcel'] = 'Exportar a Excel';
$string['exportfeedback'] = 'Exportar encuestas';
$string['downloadexcel'] = 'Descargar Excel';
$string['preview'] = 'Vista previa';
$string['previewdescription'] = 'Vista previa de los datos a exportar.';
$string['generatedby'] = 'Generado por';
$string['nofilesfound'] = 'No se encontraron archivos.';
$string['nofilesfound_help'] = 'Verifique que el curso tenga datos para exportar.';

// Form fields.
$string['email'] = 'Correo electrónico';
$string['name'] = 'Nombre';
$string['type'] = 'Tipo';
$string['optional'] = 'opcional';
$string['criteria'] = 'Criterio';
$string['summary'] = 'Resumen';

// Yes/No.
$string['yes'] = 'Sí';
$string['no'] = 'No';
$string['nousers'] = 'No hay usuarios.';

// Error messages.
$string['error:invalidscheduleid'] = 'ID de programación inválido.';
$string['error:norecipients'] = 'No hay destinatarios habilitados para esta programación.';
$string['error:noreportsgenerated'] = 'No se pudo generar ningún reporte.';
$string['error:sendfailed'] = 'Error al enviar el reporte';
$string['error:nodaysselected'] = 'Debe seleccionar al menos un día de la semana.';
$string['error:enddatebeforestart'] = 'La fecha de fin debe ser posterior a la fecha de inicio.';
$string['error:noreportselected'] = 'Debe seleccionar al menos un tipo de reporte.';

// Capabilities.
$string['epicereports:view'] = 'Ver reportes EpicE';
$string['epicereports:manage'] = 'Gestionar reportes EpicE';

// Task.
$string['task:sendscheduledreports'] = 'Enviar reportes programados';
$string['task:cleanuptempfiles'] = 'Limpiar archivos temporales';
