<?php
// /app/api/reports.php - Endpoints de la API

require_once __DIR__ . '/../reports/ReportController.php';

header('Content-Type: application/json');

$reportController = new ReportController();
$action = $_GET['action'] ?? '';
$format = $_GET['format'] ?? 'pdf';

try {
    switch ($action) {
        // ==================== OFERTA ACADÉMICA ====================
        case 'course-offer':
            $careerId = intval($_GET['career'] ?? 0);
            $semester = intval($_GET['semester'] ?? 0);
            
            if ($format === 'csv') {
                $reportController->exportCourseOfferCsv($careerId, $semester);
            } else {
                $reportController->generateCourseOfferPdf($careerId, $semester);
            }
            break;
            
        // ==================== LISTADO DE ESTUDIANTES ====================
        case 'student-list':
            $sectionId = intval($_GET['section'] ?? 0);
            $careerId = intval($_GET['career'] ?? 0);
            $status = isset($_GET['status']) ? intval($_GET['status']) : null;
            
            if ($format === 'csv') {
                $reportController->exportStudentsCsv($sectionId, $careerId, $status);
            } else {
                $reportController->generateStudentListPdf($sectionId, $careerId);
            }
            break;
            
        // ==================== CALIFICACIONES ====================
        case 'grades':
            $studentId = intval($_GET['student'] ?? 0);
            $subjectId = intval($_GET['subject'] ?? 0);
            $sectionId = intval($_GET['section'] ?? 0);
            
            if ($format === 'csv') {
                $reportController->exportGradesCsv($sectionId, $subjectId);
            } else {
                $reportController->generateGradesReportPdf($studentId, $subjectId, $sectionId);
            }
            break;
            
        // ==================== EXPEDIENTE COMPLETO ====================
        case 'student-record':
            $studentId = intval($_GET['id'] ?? 0);
            if ($studentId === 0) {
                throw new Exception("ID de estudiante requerido");
            }
            $reportController->exportStudentFullDataCsv($studentId);
            break;
            
        default:
            throw new Exception("Acción no válida");
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>