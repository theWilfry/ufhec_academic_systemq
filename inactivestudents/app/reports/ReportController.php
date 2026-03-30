<?php
// /app/reports/ReportController.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/PdfGenerator.php';
require_once __DIR__ . '/CsvGenerator.php';

class ReportController {
    private $db;
    private $pdfGenerator;
    private $csvGenerator;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->pdfGenerator = new PdfGenerator();
        $this->csvGenerator = new CsvGenerator();
    }

    /**
     * ============================================
     * REPORTES PDF - OFERTA ACADÉMICA
     * ============================================
     */
    
    /**
     * Genera PDF de oferta académica por carrera
     * 
     * @param int $careerId ID de la carrera (0 = todas)
     * @param int $semester Semestre (0 = todos)
     */
    public function generateCourseOfferPdf($careerId = 0, $semester = 0) {
        try {
            // Construir query dinámica
            $whereConditions = [];
            $params = [];
            
            if ($careerId > 0) {
                $whereConditions[] = "c.career_id = :career_id";
                $params[':career_id'] = $careerId;
            }
            
            if ($semester > 0) {
                $whereConditions[] = "s.semester = :semester";
                $params[':semester'] = $semester;
            }
            
            $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
            
            // Obtener datos de la oferta académica
            $query = "
                SELECT 
                    c.career_name,
                    c.career_level,
                    s.subject_id,
                    s.subject_name,
                    s.semester,
                    s.subject_type,
                    CASE 
                        WHEN s.subject_type = 0 THEN 'Obligatoria'
                        WHEN s.subject_type = 1 THEN 'Electiva'
                        ELSE 'Optativa'
                    END as subject_type_name
                FROM career_subjects cs
                JOIN careers c ON cs.career_id = c.career_id
                LEFT JOIN subjects s ON (
                    s.subject_id = cs.subject_1 OR
                    s.subject_id = cs.subject_2 OR
                    s.subject_id = cs.subject_3 OR
                    s.subject_id = cs.subject_4 OR
                    s.subject_id = cs.subject_5 OR
                    s.subject_id = cs.subject_6 OR
                    s.subject_id = cs.subject_7 OR
                    s.subject_id = cs.subject_8 OR
                    s.subject_id = cs.subject_9
                )
                $whereClause
                ORDER BY c.career_name, s.semester, s.subject_name
            ";
            
            $stmt = $this->db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Agrupar por carrera
            $groupedData = [];
            foreach ($courses as $course) {
                $careerName = $course['career_name'];
                if (!isset($groupedData[$careerName])) {
                    $groupedData[$careerName] = [
                        'info' => [
                            'name' => $course['career_name'],
                            'level' => $course['career_level']
                        ],
                        'subjects' => []
                    ];
                }
                $groupedData[$careerName]['subjects'][] = $course;
            }
            
            // Generar PDF
            $filters = [
                'career' => $careerId > 0 ? $this->getCareerName($careerId) : 'Todas las carreras',
                'semester' => $semester > 0 ? "Semestre $semester" : 'Todos los semestres'
            ];
            
            $this->pdfGenerator->generateCourseOffer($groupedData, $filters);
            
        } catch (Exception $e) {
            throw new Exception("Error generando PDF de oferta: " . $e->getMessage());
        }
    }

    /**
     * Genera PDF de listado de estudiantes
     * 
     * @param int $sectionId ID de la sección (0 = todas)
     * @param int $careerId ID de la carrera (0 = todas)
     */
    public function generateStudentListPdf($sectionId = 0, $careerId = 0) {
        try {
            $whereConditions = ["uc.usertype = 3"]; // Solo estudiantes
            $params = [];
            
            if ($sectionId > 0) {
                $whereConditions[] = "usi.section = :section_id";
                $params[':section_id'] = $sectionId;
            }
            
            if ($careerId > 0) {
                $whereConditions[] = "s.career_id = :career_id";
                $params[':career_id'] = $careerId;
            }
            
            $whereClause = "WHERE " . implode(" AND ", $whereConditions);
            
            $query = "
                SELECT 
                    uc.id as student_id,
                    ui.firstname,
                    ui.middlename,
                    ui.lastname,
                    ui.gender,
                    ui.contact,
                    usi.lrn,
                    s.section_name,
                    c.career_name,
                    uc.account_status
                FROM user_credentials uc
                JOIN user_information ui ON uc.id = ui.id
                LEFT JOIN user_school_info usi ON uc.id = usi.id
                LEFT JOIN sections s ON usi.section = s.id
                LEFT JOIN careers c ON s.career_id = c.career_id
                $whereClause
                ORDER BY c.career_name, s.section_name, ui.lastname, ui.firstname
            ";
            
            $stmt = $this->db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Agrupar por sección/carrera
            $groupedData = [];
            foreach ($students as $student) {
                $key = $student['section_name'] ?? 'Sin Sección';
                if (!isset($groupedData[$key])) {
                    $groupedData[$key] = [
                        'section_name' => $key,
                        'career_name' => $student['career_name'] ?? 'N/A',
                        'students' => []
                    ];
                }
                $groupedData[$key]['students'][] = $student;
            }
            
            $filters = [
                'section' => $sectionId > 0 ? $this->getSectionName($sectionId) : 'Todas las secciones',
                'career' => $careerId > 0 ? $this->getCareerName($careerId) : 'Todas las carreras',
                'total' => count($students)
            ];
            
            $this->pdfGenerator->generateStudentList($groupedData, $filters);
            
        } catch (Exception $e) {
            throw new Exception("Error generando PDF de estudiantes: " . $e->getMessage());
        }
    }

    /**
     * Genera PDF de reporte de calificaciones
     * 
     * @param int $studentId ID del estudiante (0 = todos)
     * @param int $subjectId ID de la materia (0 = todas)
     * @param int $sectionId ID de la sección (0 = todas)
     */
    public function generateGradesReportPdf($studentId = 0, $subjectId = 0, $sectionId = 0) {
        try {
            $whereConditions = [];
            $params = [];
            
            if ($studentId > 0) {
                $whereConditions[] = "sg.student_id = :student_id";
                $params[':student_id'] = $studentId;
            }
            
            if ($subjectId > 0) {
                $whereConditions[] = "sg.subject_id = :subject_id";
                $params[':subject_id'] = $subjectId;
            }
            
            if ($sectionId > 0) {
                $whereConditions[] = "usi.section = :section_id";
                $params[':section_id'] = $sectionId;
            }
            
            $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
            
            $query = "
                SELECT 
                    sg.*,
                    ui.firstname,
                    ui.middlename,
                    ui.lastname,
                    ui.gender,
                    s.subject_name,
                    s.semester,
                    sec.section_name,
                    c.career_name,
                    ((sg.grading_period_1 + sg.grading_period_2) / 2) as final_average,
                    CASE 
                        WHEN ((sg.grading_period_1 + sg.grading_period_2) / 2) >= 70 THEN 'Aprobado'
                        ELSE 'Reprobado'
                    END as status
                FROM student_grades sg
                JOIN user_information ui ON sg.student_id = ui.id
                JOIN subjects s ON sg.subject_id = s.subject_id
                LEFT JOIN user_school_info usi ON sg.student_id = usi.id
                LEFT JOIN sections sec ON usi.section = sec.id
                LEFT JOIN careers c ON sec.career_id = c.career_id
                $whereClause
                ORDER BY c.career_name, sec.section_name, ui.lastname, s.semester
            ";
            
            $stmt = $this->db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            
            $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calcular estadísticas
            $stats = $this->calculateGradeStats($grades);
            
            $filters = [
                'student' => $studentId > 0 ? $this->getStudentName($studentId) : 'Todos los estudiantes',
                'subject' => $subjectId > 0 ? $this->getSubjectName($subjectId) : 'Todas las materias',
                'section' => $sectionId > 0 ? $this->getSectionName($sectionId) : 'Todas las secciones'
            ];
            
            $this->pdfGenerator->generateGradesReport($grades, $filters, $stats);
            
        } catch (Exception $e) {
            throw new Exception("Error generando PDF de calificaciones: " . $e->getMessage());
        }
    }

    /**
     * ============================================
     * EXPORTACIÓN CSV
     * ============================================
     */
    
    /**
     * Exporta oferta académica a CSV
     */
    public function exportCourseOfferCsv($careerId = 0, $semester = 0) {
        try {
            $whereConditions = [];
            $params = [];
            
            if ($careerId > 0) {
                $whereConditions[] = "c.career_id = :career_id";
                $params[':career_id'] = $careerId;
            }
            
            if ($semester > 0) {
                $whereConditions[] = "s.semester = :semester";
                $params[':semester'] = $semester;
            }
            
            $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
            
            $query = "
                SELECT 
                    c.career_name as 'Carrera',
                    c.career_level as 'Nivel',
                    s.subject_name as 'Materia',
                    s.semester as 'Semestre',
                    CASE 
                        WHEN s.subject_type = 0 THEN 'Obligatoria'
                        WHEN s.subject_type = 1 THEN 'Electiva'
                        ELSE 'Optativa'
                    END as 'Tipo'
                FROM career_subjects cs
                JOIN careers c ON cs.career_id = c.career_id
                LEFT JOIN subjects s ON (
                    s.subject_id = cs.subject_1 OR
                    s.subject_id = cs.subject_2 OR
                    s.subject_id = cs.subject_3 OR
                    s.subject_id = cs.subject_4 OR
                    s.subject_id = cs.subject_5 OR
                    s.subject_id = cs.subject_6 OR
                    s.subject_id = cs.subject_7 OR
                    s.subject_id = cs.subject_8 OR
                    s.subject_id = cs.subject_9
                )
                $whereClause
                ORDER BY c.career_name, s.semester, s.subject_name
            ";
            
            $stmt = $this->db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $filename = "oferta_academica_" . date('Y-m-d_H-i-s') . ".csv";
            
            $this->csvGenerator->download($data, $filename);
            
        } catch (Exception $e) {
            throw new Exception("Error exportando CSV de oferta: " . $e->getMessage());
        }
    }

    /**
     * Exporta listado de estudiantes a CSV
     */
    public function exportStudentsCsv($sectionId = 0, $careerId = 0, $status = null) {
        try {
            $whereConditions = ["uc.usertype = 3"];
            $params = [];
            
            if ($sectionId > 0) {
                $whereConditions[] = "usi.section = :section_id";
                $params[':section_id'] = $sectionId;
            }
            
            if ($careerId > 0) {
                $whereConditions[] = "s.career_id = :career_id";
                $params[':career_id'] = $careerId;
            }
            
            if ($status !== null) {
                $whereConditions[] = "uc.account_status = :status";
                $params[':status'] = $status;
            }
            
            $whereClause = "WHERE " . implode(" AND ", $whereConditions);
            
            $query = "
                SELECT 
                    uc.id as 'ID Estudiante',
                    usi.lrn as 'LRN',
                    CONCAT(ui.lastname, ', ', ui.firstname, ' ', COALESCE(ui.middlename, '')) as 'Nombre Completo',
                    ui.gender as 'Género',
                    ui.contact as 'Teléfono',
                    ui.email as 'Correo',
                    c.career_name as 'Carrera',
                    s.section_name as 'Sección',
                    CASE 
                        WHEN uc.account_status = 1 THEN 'Activo'
                        ELSE 'Inactivo'
                    END as 'Estado',
                    ui.birthday as 'Fecha Nacimiento'
                FROM user_credentials uc
                JOIN user_information ui ON uc.id = ui.id
                LEFT JOIN user_school_info usi ON uc.id = usi.id
                LEFT JOIN sections s ON usi.section = s.id
                LEFT JOIN careers c ON s.career_id = c.career_id
                $whereClause
                ORDER BY c.career_name, s.section_name, ui.lastname
            ";
            
            $stmt = $this->db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $filename = "estudiantes_" . date('Y-m-d_H-i-s') . ".csv";
            
            $this->csvGenerator->download($data, $filename);
            
        } catch (Exception $e) {
            throw new Exception("Error exportando CSV de estudiantes: " . $e->getMessage());
        }
    }

    /**
     * Exporta calificaciones a CSV
     */
    public function exportGradesCsv($sectionId = 0, $subjectId = 0, $period = 0) {
        try {
            $whereConditions = [];
            $params = [];
            
            if ($sectionId > 0) {
                $whereConditions[] = "usi.section = :section_id";
                $params[':section_id'] = $sectionId;
            }
            
            if ($subjectId > 0) {
                $whereConditions[] = "sg.subject_id = :subject_id";
                $params[':subject_id'] = $subjectId;
            }
            
            $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
            
            $query = "
                SELECT 
                    uc.id as 'ID Estudiante',
                    CONCAT(ui.lastname, ', ', ui.firstname) as 'Estudiante',
                    usi.lrn as 'LRN',
                    c.career_name as 'Carrera',
                    sec.section_name as 'Sección',
                    s.subject_name as 'Materia',
                    s.semester as 'Semestre',
                    sg.grading_period_1 as 'Periodo 1',
                    sg.grading_period_2 as 'Periodo 2',
                    ROUND((sg.grading_period_1 + sg.grading_period_2) / 2, 2) as 'Promedio Final',
                    CASE 
                        WHEN (sg.grading_period_1 + sg.grading_period_2) / 2 >= 70 THEN 'Aprobado'
                        ELSE 'Reprobado'
                    END as 'Estado'
                FROM student_grades sg
                JOIN user_credentials uc ON sg.student_id = uc.id
                JOIN user_information ui ON sg.student_id = ui.id
                JOIN subjects s ON sg.subject_id = s.subject_id
                LEFT JOIN user_school_info usi ON sg.student_id = usi.id
                LEFT JOIN sections sec ON usi.section = sec.id
                LEFT JOIN careers c ON sec.career_id = c.career_id
                $whereClause
                ORDER BY c.career_name, sec.section_name, s.subject_name, ui.lastname
            ";
            
            $stmt = $this->db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $filename = "calificaciones_" . date('Y-m-d_H-i-s') . ".csv";
            
            $this->csvGenerator->download($data, $filename);
            
        } catch (Exception $e) {
            throw new Exception("Error exportando CSV de calificaciones: " . $e->getMessage());
        }
    }

    /**
     * Exporta datos completos de un estudiante
     */
    public function exportStudentFullDataCsv($studentId) {
        try {
            // Información personal
            $query = "
                SELECT 
                    'Información Personal' as 'Sección',
                    ui.firstname as 'Nombre',
                    ui.middlename as 'Segundo Nombre',
                    ui.lastname as 'Apellido',
                    ui.gender as 'Género',
                    ui.birthday as 'Fecha Nacimiento',
                    ui.religion as 'Religión',
                    ui.country as 'País',
                    ui.region as 'Región',
                    ui.address as 'Dirección',
                    ui.contact as 'Teléfono',
                    uc.email as 'Correo Electrónico'
                FROM user_information ui
                JOIN user_credentials uc ON ui.id = uc.id
                WHERE ui.id = :student_id
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':student_id', $studentId);
            $stmt->execute();
            $personalInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Información familiar
            $query = "
                SELECT 
                    'Información Familiar' as 'Sección',
                    mother_fullname as 'Nombre Madre',
                    mother_work as 'Trabajo Madre',
                    mother_contact as 'Contacto Madre',
                    father_fullname as 'Nombre Padre',
                    father_work as 'Trabajo Padre',
                    father_contact as 'Contacto Padre',
                    guardian_fullname as 'Nombre Tutor',
                    guardian_contact as 'Contacto Tutor',
                    relationship as 'Relación'
                FROM user_family_background
                WHERE id = :student_id
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':student_id', $studentId);
            $stmt->execute();
            $familyInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Información académica
            $query = "
                SELECT 
                    'Información Académica' as 'Sección',
                    usi.lrn as 'LRN',
                    c.career_name as 'Carrera',
                    s.section_name as 'Sección'
                FROM user_school_info usi
                LEFT JOIN sections s ON usi.section = s.id
                LEFT JOIN careers c ON s.career_id = c.career_id
                WHERE usi.id = :student_id
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':student_id', $studentId);
            $stmt->execute();
            $academicInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Calificaciones
            $query = "
                SELECT 
                    'Calificación' as 'Tipo',
                    s.subject_name as 'Materia',
                    s.semester as 'Semestre',
                    sg.grading_period_1 as 'Periodo 1',
                    sg.grading_period_2 as 'Periodo 2',
                    ROUND((sg.grading_period_1 + sg.grading_period_2) / 2, 2) as 'Promedio'
                FROM student_grades sg
                JOIN subjects s ON sg.subject_id = s.subject_id
                WHERE sg.student_id = :student_id
                ORDER BY s.semester, s.subject_name
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':student_id', $studentId);
            $stmt->execute();
            $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Combinar datos
            $data = array_filter([$personalInfo, $familyInfo, $academicInfo]);
            $data = array_merge($data, $grades);
            
            $studentName = $this->getStudentName($studentId);
            $filename = "expediente_" . preg_replace('/[^a-zA-Z0-9]/', '_', $studentName) . "_" . date('Y-m-d') . ".csv";
            
            $this->csvGenerator->download($data, $filename);
            
        } catch (Exception $e) {
            throw new Exception("Error exportando expediente: " . $e->getMessage());
        }
    }

    /**
     * ============================================
     * MÉTODOS AUXILIARES
     * ============================================
     */
    
    private function getCareerName($careerId) {
        $stmt = $this->db->prepare("SELECT career_name FROM careers WHERE career_id = ?");
        $stmt->execute([$careerId]);
        return $stmt->fetchColumn() ?? 'Desconocida';
    }
    
    private function getSectionName($sectionId) {
        $stmt = $this->db->prepare("SELECT section_name FROM sections WHERE id = ?");
        $stmt->execute([$sectionId]);
        return $stmt->fetchColumn() ?? 'Desconocida';
    }
    
    private function getSubjectName($subjectId) {
        $stmt = $this->db->prepare("SELECT subject_name FROM subjects WHERE subject_id = ?");
        $stmt->execute([$subjectId]);
        return $stmt->fetchColumn() ?? 'Desconocida';
    }
    
    private function getStudentName($studentId) {
        $stmt = $this->db->prepare("
            SELECT CONCAT(firstname, ' ', lastname) 
            FROM user_information 
            WHERE id = ?
        ");
        $stmt->execute([$studentId]);
        return $stmt->fetchColumn() ?? 'Desconocido';
    }
    
    private function calculateGradeStats($grades) {
        if (empty($grades)) return [];
        
        $total = count($grades);
        $sum = 0;
        $approved = 0;
        $failed = 0;
        
        foreach ($grades as $grade) {
            $avg = ($grade['grading_period_1'] + $grade['grading_period_2']) / 2;
            $sum += $avg;
            if ($avg >= 70) $approved++;
            else $failed++;
        }
        
        return [
            'total' => $total,
            'average' => round($sum / $total, 2),
            'approved' => $approved,
            'failed' => $failed,
            'approval_rate' => round(($approved / $total) * 100, 2)
        ];
    }
}
?>