<?php
// /app/reports/PdfGenerator.php

require_once __DIR__ . '/../config/database.php';

class PdfGenerator {
    
    /**
     * Genera PDF de oferta académica por carrera
     * 
     * @param array $data Datos agrupados por carrera
     * @param array $filters Filtros aplicados
     */
    public function generateCourseOffer($data, $filters) {
        // Configuración de headers para PDF
        $title = "Reporte de Oferta Académica";
        $date = date('d/m/Y H:i:s');
        
        // Preparar contenido del PDF
        $content = $this->buildCourseOfferContent($data, $filters, $title, $date);
        
        // Generar y descargar PDF
        $this->outputPdf($content, "oferta_academica_" . date('Y-m-d') . ".pdf");
    }
    
    /**
     * Genera PDF de listado de estudiantes
     * 
     * @param array $data Datos agrupados por sección
     * @param array $filters Filtros aplicados
     */
    public function generateStudentList($data, $filters) {
        $title = "Listado de Estudiantes";
        $date = date('d/m/Y H:i:s');
        
        $content = $this->buildStudentListContent($data, $filters, $title, $date);
        
        $this->outputPdf($content, "listado_estudiantes_" . date('Y-m-d') . ".pdf");
    }
    
    /**
     * Genera PDF de reporte de calificaciones
     * 
     * @param array $grades Datos de calificaciones
     * @param array $filters Filtros aplicados
     * @param array $stats Estadísticas calculadas
     */
    public function generateGradesReport($grades, $filters, $stats) {
        $title = "Reporte de Calificaciones";
        $date = date('d/m/Y H:i:s');
        
        $content = $this->buildGradesReportContent($grades, $filters, $stats, $title, $date);
        
        $this->outputPdf($content, "reporte_calificaciones_" . date('Y-m-d') . ".pdf");
    }
    
    /**
     * Construye el contenido HTML para oferta académica
     */
    private function buildCourseOfferContent($data, $filters, $title, $date) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?= htmlspecialchars($title) ?></title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
                h1 { font-size: 18px; text-align: center; color: #333; }
                h2 { font-size: 14px; color: #0066cc; margin-top: 20px; border-bottom: 1px solid #ddd; }
                table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
                th { background-color: #f0f0f0; font-weight: bold; }
                .header { margin-bottom: 20px; }
                .filters { background-color: #f9f9f9; padding: 10px; margin-bottom: 15px; font-size: 11px; }
                .footer { margin-top: 30px; font-size: 10px; color: #666; text-align: center; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1><?= htmlspecialchars($title) ?></h1>
                <p style="text-align: center; color: #666;">Generado: <?= htmlspecialchars($date) ?></p>
            </div>
            
            <div class="filters">
                <strong>Filtros aplicados:</strong> 
                <?= htmlspecialchars($filters['career'] ?? 'Todas') ?> | 
                <?= htmlspecialchars($filters['semester'] ?? 'Todos') ?>
            </div>
            
            <?php foreach ($data as $careerName => $careerData): ?>
                <h2><?= htmlspecialchars($careerName) ?> (<?= htmlspecialchars($careerData['info']['level'] ?? 'N/A') ?>)</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Semestre</th>
                            <th>Materia</th>
                            <th>Tipo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($careerData['subjects'] as $subject): ?>
                            <tr>
                                <td><?= htmlspecialchars($subject['semester'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($subject['subject_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($subject['subject_type_name'] ?? 'N/A') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
            
            <div class="footer">
                UFHEC - Sistema Académico | Página 1
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Construye el contenido HTML para listado de estudiantes
     */
    private function buildStudentListContent($data, $filters, $title, $date) {
        $totalStudents = $filters['total'] ?? 0;
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?= htmlspecialchars($title) ?></title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 11px; margin: 20px; }
                h1 { font-size: 18px; text-align: center; color: #333; }
                h2 { font-size: 13px; color: #0066cc; margin-top: 15px; }
                table { width: 100%; border-collapse: collapse; margin-top: 8px; }
                th, td { border: 1px solid #ddd; padding: 5px; text-align: left; }
                th { background-color: #f0f0f0; font-weight: bold; font-size: 10px; }
                td { font-size: 10px; }
                .header { margin-bottom: 20px; }
                .filters { background-color: #f9f9f9; padding: 10px; margin-bottom: 15px; font-size: 11px; }
                .summary { font-weight: bold; margin-bottom: 10px; }
                .footer { margin-top: 30px; font-size: 10px; color: #666; text-align: center; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1><?= htmlspecialchars($title) ?></h1>
                <p style="text-align: center; color: #666;">Generado: <?= htmlspecialchars($date) ?></p>
            </div>
            
            <div class="filters">
                <strong>Filtros:</strong> 
                <?= htmlspecialchars($filters['section'] ?? 'Todas las secciones') ?> | 
                <?= htmlspecialchars($filters['career'] ?? 'Todas las carreras') ?>
            </div>
            
            <p class="summary">Total de estudiantes: <?= intval($totalStudents) ?></p>
            
            <?php foreach ($data as $sectionName => $sectionData): ?>
                <h2><?= htmlspecialchars($sectionName) ?> - <?= htmlspecialchars($sectionData['career_name'] ?? 'N/A') ?></h2>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>ID</th>
                            <th>Nombre Completo</th>
                            <th>Género</th>
                            <th>Contacto</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $counter = 1; foreach ($sectionData['students'] as $student): ?>
                            <tr>
                                <td><?= $counter++ ?></td>
                                <td><?= htmlspecialchars($student['student_id'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars(($student['lastname'] ?? '') . ', ' . ($student['firstname'] ?? '') . ' ' . ($student['middlename'] ?? '')) ?></td>
                                <td><?= htmlspecialchars($student['gender'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($student['contact'] ?? 'N/A') ?></td>
                                <td><?= $student['account_status'] == 1 ? 'Activo' : 'Inactivo' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
            
            <div class="footer">
                UFHEC - Sistema Académico | Página 1
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Construye el contenido HTML para reporte de calificaciones
     */
    private function buildGradesReportContent($grades, $filters, $stats, $title, $date) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?= htmlspecialchars($title) ?></title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 11px; margin: 20px; }
                h1 { font-size: 18px; text-align: center; color: #333; }
                table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                th, td { border: 1px solid #ddd; padding: 5px; text-align: left; }
                th { background-color: #f0f0f0; font-weight: bold; font-size: 10px; }
                td { font-size: 10px; }
                .header { margin-bottom: 20px; }
                .filters { background-color: #f9f9f9; padding: 10px; margin-bottom: 15px; font-size: 11px; }
                .stats { background-color: #e8f4e8; padding: 10px; margin-bottom: 15px; font-size: 11px; }
                .approved { color: green; }
                .failed { color: red; }
                .footer { margin-top: 30px; font-size: 10px; color: #666; text-align: center; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1><?= htmlspecialchars($title) ?></h1>
                <p style="text-align: center; color: #666;">Generado: <?= htmlspecialchars($date) ?></p>
            </div>
            
            <div class="filters">
                <strong>Filtros:</strong> 
                <?= htmlspecialchars($filters['student'] ?? 'Todos') ?> | 
                <?= htmlspecialchars($filters['subject'] ?? 'Todas') ?> | 
                <?= htmlspecialchars($filters['section'] ?? 'Todas') ?>
            </div>
            
            <?php if (!empty($stats)): ?>
            <div class="stats">
                <strong>Estadísticas:</strong><br>
                Total registros: <?= intval($stats['total'] ?? 0) ?> | 
                Aprobados: <?= intval($stats['approved'] ?? 0) ?> | 
                Reprobados: <?= intval($stats['failed'] ?? 0) ?> | 
                Promedio general: <?= number_format($stats['average'] ?? 0, 2) ?>
            </div>
            <?php endif; ?>
            
            <table>
                <thead>
                    <tr>
                        <th>Estudiante</th>
                        <th>Materia</th>
                        <th>Semestre</th>
                        <th>P1</th>
                        <th>P2</th>
                        <th>Promedio</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($grades as $grade): 
                        $average = $grade['final_average'] ?? 0;
                        $statusClass = $average >= 70 ? 'approved' : 'failed';
                    ?>
                        <tr>
                            <td><?= htmlspecialchars(($grade['lastname'] ?? '') . ', ' . ($grade['firstname'] ?? '')) ?></td>
                            <td><?= htmlspecialchars($grade['subject_name'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($grade['semester'] ?? 'N/A') ?></td>
                            <td><?= number_format($grade['grading_period_1'] ?? 0, 1) ?></td>
                            <td><?= number_format($grade['grading_period_2'] ?? 0, 1) ?></td>
                            <td><?= number_format($average, 2) ?></td>
                            <td class="<?= $statusClass ?>"><?= htmlspecialchars($grade['status'] ?? 'N/A') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="footer">
                UFHEC - Sistema Académico | Página 1
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Genera y descarga el PDF usando TCPDF o alternativa HTML
     */
    private function outputPdf($htmlContent, $filename) {
        // Intentar usar TCPDF si está disponible
        if (class_exists('TCPDF')) {
            $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
            $pdf->SetCreator('UFHEC Academic System');
            $pdf->SetAuthor('UFHEC');
            $pdf->SetTitle($filename);
            $pdf->SetHeaderData('', 0, 'UFHEC - Sistema Académico', '');
            $pdf->setHeaderFont(Array('helvetica', '', 10));
            $pdf->setFooterFont(Array('helvetica', '', 8));
            $pdf->SetDefaultMonospacedFont('courier');
            $pdf->SetMargins(15, 27, 15);
            $pdf->SetHeaderMargin(5);
            $pdf->SetFooterMargin(10);
            $pdf->SetAutoPageBreak(true, 25);
            $pdf->AddPage();
            $pdf->writeHTML($htmlContent, true, false, true, false, '');
            $pdf->Output($filename, 'D');
            exit;
        }
        
        // Alternativa: generar HTML para imprimir (sin dependencia de TCPDF)
        header('Content-Type: text/html; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.html"');
        echo $htmlContent;
        exit;
    }
}
