// local/epicereports/amd/src/dashboard.js
define(['jquery', 'core/chartjs'], function($, Chart) {
    'use strict';

    return {
        init: function(config) {
            console.log('Módulo dashboard cargado. Config:', config);

            var coursesCtx = document.getElementById('coursesChart');
            if (coursesCtx) {
                new Chart(coursesCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Visibles', 'Ocultos'],
                        datasets: [{
                            data: [config.visibleCourses, config.hiddenCourses],
                            backgroundColor: ['#4E73DF', '#1CC88A'],
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            }
        }
    };
});