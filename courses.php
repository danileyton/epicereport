<?php
require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/locallib.php'); // <--- importante

require_login();
$context = context_system::instance();
$PAGE->set_context($context);

$PAGE->set_url(new moodle_url('/local/epicereports/courses.php'));
$PAGE->set_title('Listado de cursos');
$PAGE->set_heading('Listado de cursos');
$PAGE->set_pagelayout('popup');

use local_epicereports\helper;

// ---------------------------------------------------------------------
// Filtros: solo categoría y visibilidad (estado del curso)
// ---------------------------------------------------------------------
$category_filter = optional_param('category', 0, PARAM_INT);
$visible_filter  = optional_param('visible', -1, PARAM_INT); // -1 = todos, 1 = visibles, 0 = ocultos

// Obtenemos la lista de categorías para el menú desplegable.
$categories = helper::get_course_categories();

// Obtenemos los cursos usando SOLO categoría y visibilidad.
$courses_list = helper::get_courses_list('', $category_filter, $visible_filter);

// ---------------------------------------------------------------------
// DataTables: versión local + hack para RequireJS
// ---------------------------------------------------------------------
$PAGE->requires->jquery();

// CSS local de DataTables.
$PAGE->requires->css('/local/epicereports/css/jquery.dataTables.min.css');

// URL JS local de DataTables.
$dtjspath = (new moodle_url('/local/epicereports/js/jquery.dataTables.min.js'))->out(false);

// Inicialización: cargamos el JS manualmente y luego activamos DataTables.
$PAGE->requires->js_init_code("
    require(['jquery'], function($) {
        var oldDefine    = window.define;
        var oldDefineAmd = oldDefine && oldDefine.amd;

        // Desactivamos AMD temporalmente.
        if (oldDefine && oldDefine.amd) {
            oldDefine.amd = undefined;
        }

        var script = document.createElement('script');
        script.src = '{$dtjspath}';
        script.onload = function() {

            // Restauramos define/AMD.
            if (oldDefine) {
                window.define = oldDefine;
                if (oldDefineAmd) {
                    oldDefine.amd = oldDefineAmd;
                }
            }

            $('#courses-table').DataTable({
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1],[10, 25, 50, 100, 'Todos']],
                order: [[1, 'asc']],
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json'
                }
            });
        };

        document.head.appendChild(script);
    });
");

// ---------------------------------------------------------------------
// Salida HTML
// ---------------------------------------------------------------------
echo $OUTPUT->header();
echo $OUTPUT->heading('Listado de cursos');

// Layout con menú lateral + contenido
echo html_writer::start_div('row');

// Columna izquierda: menú lateral
echo html_writer::start_div('col-md-3 col-lg-2');
local_epicereports_render_sidebar('courses');
echo html_writer::end_div();

// Columna derecha: filtros + tabla
echo html_writer::start_div('col-md-9 col-lg-10');

// ----- Formulario de filtros (1 sola línea) -----
echo html_writer::start_tag('form', [
    'method' => 'get',
    'class'  => 'form-inline mb-3'
]);

// Filtro por categoría.
echo html_writer::start_div('form-group mr-2 mb-2');
echo html_writer::label('Categoría', 'category_filter', ['class' => 'mr-2 mb-0']);
echo html_writer::select(
    $categories,
    'category',
    $category_filter,
    'Todas',
    [
        'id'    => 'category_filter',
        'class' => 'form-control'
    ]
);
echo html_writer::end_div();

// Filtro por visibilidad (estado del curso).
echo html_writer::start_div('form-group mr-2 mb-2');
echo html_writer::label('Estado', 'visible_filter', ['class' => 'mr-2 mb-0']);
$options = [
    -1 => 'Todos',
    1  => 'Visibles',
    0  => 'Ocultos'
];
echo html_writer::select(
    $options,
    'visible',
    $visible_filter,
    '',
    [
        'id'    => 'visible_filter',
        'class' => 'form-control'
    ]
);
echo html_writer::end_div();

// Botón de filtrar.
echo html_writer::start_div('form-group mb-2');
echo html_writer::empty_tag('input', [
    'type'  => 'submit',
    'class' => 'btn btn-primary',
    'value' => 'Filtrar'
]);
echo html_writer::end_div();

echo html_writer::end_tag('form');

// ----- Tabla de resultados -----
$table = new html_table();
$table->attributes = [
    'id'    => 'courses-table',
    'class' => 'table table-striped table-bordered table-sm'
];

$table->head = [
    'ID',
    'Nombre completo',
    'Nombre corto',
    'Visible',
    'Acciones'
];

$table->data = [];

foreach ($courses_list as $course) {
    $visible_text  = $course->visible ? 'Sí' : 'No';
    $visible_class = $course->visible ? 'text-success' : 'text-danger';

    // URL para la página de detalle del curso.
    $detail_url = new moodle_url('/local/epicereports/course_detail.php', ['id' => $course->id]);

    $table->data[] = [
        $course->id,
        format_string($course->fullname),
        format_string($course->shortname),
        html_writer::tag('span', $visible_text, ['class' => $visible_class]),
        html_writer::link($detail_url, 'Ver detalle', ['class' => 'btn btn-sm btn-outline-primary'])
    ];
}

echo html_writer::table($table);

echo html_writer::end_div(); // col-md-9 col-lg-10
echo html_writer::end_div(); // row

echo $OUTPUT->footer();
