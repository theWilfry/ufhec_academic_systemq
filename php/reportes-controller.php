<?php

require_once 'connection.php';
require_once 'data-encryption.inc.php';
require_once 'input_validation.inc.php';

/**
 * Clase para generar reportes profesionales en PDF y CSV
 */
class ReporteController {
    
    private $con;
    
    public function __construct() {
        $this->con = connect();
    }
    
    /**
     * Genera reporte PDF de oferta académica
     */
    public function generarPDFOferta($filtros = []) {
        // Obtener datos de la oferta académica
        $ofertas = $this->obtenerDatosOferta($filtros);
        
        // Generar HTML para el PDF
        $html = $this->generarHTMLPDF($ofertas, $filtros);
        
        // Usar TCPDF (librería gratuita) o generar HTML con headers para imprimir a PDF
        $this->generarPDFConBrowser($html, 'oferta-academica-' . date('Y-m-d'));
    }
    
    /**
     * Genera archivo CSV de oferta académica
     */
    public function generarCSVOferta($filtros = []) {
        $ofertas = $this->obtenerDatosOferta($filtros);
        
        // Headers para forzar descarga CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="oferta-academica-' . date('Y-m-d-His') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // BOM para Excel (UTF-8)
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Encabezados
        fputcsv($output, [
            'ID', 'Código', 'Carrera/Strand', 'Periodo', 'Asignatura', 
            'Docente', 'Sección', 'Horario', 'Aula', 'Cupos Disp.', 
            'Cupos Ocup.', 'Cupos Totales', 'Estado', 'Fecha Creación'
        ]);
        
        // Datos
        foreach ($ofertas as $oferta) {
            fputcsv($output, [
                $oferta['id'],
                $oferta['codigo'],
                $oferta['carrera'],
                $oferta['periodo'],
                $oferta['asignatura'],
                $oferta['docente'],
                $oferta['seccion'],
                $oferta['horario'],
                $oferta['aula'],
                $oferta['cupos_disponibles'],
                $oferta['cupos_ocupados'],
                $oferta['cupos_totales'],
                $this->getEstadoTexto($oferta['estado']),
                $oferta['fecha_creacion']
            ]);
        }
        
        fclose($output);
        exit();
    }
    
    /**
     * Genera Excel con formato profesional (usando HTML table)
     */
    public function generarExcelOferta($filtros = []) {
        $ofertas = $this->obtenerDatosOferta($filtros);
        
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="oferta-academica-' . date('Y-m-d-His') . '.xls"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo $this->generarHTMLExcel($ofertas, $filtros);
        exit();
    }
    
    /**
     * Obtiene datos de la oferta académica con filtros
     */
    private function obtenerDatosOferta($filtros = []) {
        $sql = "SELECT 
                    o.id,
                    o.codigo,
                    s.strand_name as carrera,
                    p.periodo_nombre as periodo,
                    a.asignatura_nombre as asignatura,
                    CONCAT(ui.firstname, ' ', ui.lastname) as docente,
                    sec.section_name as seccion,
                    o.horario,
                    o.aula,
                    o.cupos_disponibles,
                    o.cupos_ocupados,
                    o.cupos_totales,
                    o.estado,
                    o.created_at as fecha_creacion
                FROM oferta_academica o
                LEFT JOIN strands s ON o.strand_id = s.strand_id
                LEFT JOIN periodos_academicos p ON o.periodo_id = p.id
                LEFT JOIN asignaturas a ON o.asignatura_id = a.id
                LEFT JOIN user_information ui ON o.docente_id = ui.id
                LEFT JOIN sections sec ON o.section_id = sec.id
                WHERE 1=1";
        
        $params = [];
        $types = "";
        
        if (!empty($filtros['periodo_id'])) {
            $sql .= " AND o.periodo_id = ?";
            $params[] = $filtros['periodo_id'];
            $types .= "i";
        }
        
        if (!empty($filtros['strand_id'])) {
            $sql .= " AND o.strand_id = ?";
            $params[] = $filtros['strand_id'];
            $types .= "i";
        }
        
        if (!empty($filtros['estado'])) {
            $sql .= " AND o.estado = ?";
            $params[] = $filtros['estado'];
            $types .= "s";
        }
        
        $sql .= " ORDER BY o.created_at DESC";
        
        $stmt = $this->con->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $ofertas = [];
        while ($row = $result->fetch_assoc()) {
            $ofertas[] = $row;
        }
        
        return $ofertas;
    }
    
    /**
     * Genera HTML para impresión a PDF (usando browser print)
     */
    private function generarHTMLPDF($ofertas, $filtros) {
        $totalOfertas = count($ofertas);
        $totalCupos = array_sum(array_column($ofertas, 'cupos_totales'));
        $cuposDisponibles = array_sum(array_column($ofertas, 'cupos_disponibles'));
        
        $html = '<!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Reporte de Oferta Académica - UFHEC</title>
            <style>
                @page { size: landscape; margin: 1cm; }
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { 
                    font-family: "Segoe UI", Arial, sans-serif; 
                    font-size: 10px; 
                    color: #333;
                    line-height: 1.4;
                }
                .header { 
                    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
                    color: white;
                    padding: 20px;
                    text-align: center;
                    border-bottom: 5px solid #f39c12;
                }
                .header h1 { font-size: 24px; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 2px; }
                .header p { font-size: 12px; opacity: 0.9; }
                .timestamp { text-align: right; font-size: 9px; color: #666; margin: 10px 20px; font-style: italic; }
                .stats-container { display: flex; justify-content: space-around; margin: 15px 20px; gap: 10px; }
                .stat-box { 
                    background: #f8f9fa; 
                    border: 2px solid #2a5298; 
                    border-radius: 8px; 
                    padding: 15px; 
                    text-align: center; 
                    flex: 1;
                }
                .stat-number { font-size: 28px; font-weight: bold; color: #2a5298; }
                .stat-label { font-size: 10px; color: #666; text-transform: uppercase; margin-top: 5px; }
                table { 
                    width: 95%; 
                    margin: 20px auto; 
                    border-collapse: collapse; 
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                }
                thead { background: #2a5298; color: white; }
                th { 
                    padding: 12px 8px; 
                    text-align: left; 
                    font-size: 10px; 
                    text-transform: uppercase; 
                    letter-spacing: 0.5px;
                    border-bottom: 3px solid #f39c12;
                }
                td { 
                    padding: 10px 8px; 
                    border-bottom: 1px solid #ddd; 
                    font-size: 9px; 
                }
                tbody tr:nth-child(even) { background-color: #f8f9fa; }
                .badge { 
                    padding: 4px 8px; 
                    border-radius: 12px; 
                    font-size: 8px; 
                    font-weight: bold; 
                    text-transform: uppercase;
                }
                .badge-activa { background: #d4edda; color: #155724; }
                .badge-cerrada { background: #f8d7da; color: #721c24; }
                .badge-proceso { background: #fff3cd; color: #856404; }
                .footer { 
                    position: fixed; 
                    bottom: 0; 
                    width: 100%; 
                    background: #2a5298; 
                    color: white; 
                    text-align: center; 
                    padding: 10px;
                    font-size: 9px;
                }
                @media print {
                    .no-print { display: none; }
                    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                }
                .print-button {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: #2a5298;
                    color: white;
                    border: none;
                    padding: 10px 20px;
                    border-radius: 5px;
                    cursor: pointer;
                    font-size: 12px;
                    z-index: 1000;
                }
                .print-button:hover { background: #1e3c72; }
            </style>
        </head>
        <body>
            <button class="print-button no-print" onclick="window.print()">
                <i class="fas fa-print"></i> Imprimir / Guardar como PDF
            </button>
            
            <div class="header">
                <h1>Universidad Federico Henríquez y Carvajal</h1>
                <p>Sistema de Gestión Académica - Reporte de Oferta Académica</p>
            </div>
            
            <div class="timestamp">
                Generado el: ' . date('d/m/Y H:i:s') . ' | Usuario: ' . $_SESSION['user_name'] . '
            </div>

            <div class="stats-container">
                <div class="stat-box">
                    <div class="stat-number">' . $totalOfertas . '</div>
                    <div class="stat-label">Total Ofertas</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number">' . $cuposDisponibles . '</div>
                    <div class="stat-label">Cupos Disponibles</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number">' . $totalCupos . '</div>
                    <div class="stat-label">Cupos Totales</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number">' . round(($totalCupos > 0 ? ($cuposDisponibles/$totalCupos)*100 : 0), 1) . '%</div>
                    <div class="stat-label">Disponibilidad</div>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Código</th>
                        <th>Carrera/Strand</th>
                        <th>Periodo</th>
                        <th>Asignatura</th>
                        <th>Docente</th>
                        <th>Sección</th>
                        <th>Horario</th>
                        <th>Aula</th>
                        <th>Cupos</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($ofertas as $index => $oferta) {
            $badgeClass = 'badge-' . ($oferta['estado'] == 'activa' ? 'activa' : ($oferta['estado'] == 'cerrada' ? 'cerrada' : 'proceso'));
            $html .= '<tr>
                <td>' . ($index + 1) . '</td>
                <td><strong>' . htmlspecialchars($oferta['codigo']) . '</strong></td>
                <td>' . htmlspecialchars($oferta['carrera']) . '</td>
                <td>' . htmlspecialchars($oferta['periodo']) . '</td>
                <td>' . htmlspecialchars($oferta['asignatura']) . '</td>
                <td>' . htmlspecialchars($oferta['docente']) . '</td>
                <td>' . htmlspecialchars($oferta['seccion']) . '</td>
                <td>' . htmlspecialchars($oferta['horario']) . '</td>
                <td>' . htmlspecialchars($oferta['aula']) . '</td>
                <td>' . $oferta['cupos_disponibles'] . '/' . $oferta['cupos_totales'] . '</td>
                <td><span class="badge ' . $badgeClass . '">' . ucfirst($oferta['estado']) . '</span></td>
            </tr>';
        }
        
        $html .= '</tbody>
            </table>

            <div class="footer">
                UFHEC - Sistema Académico © ' . date('Y') . ' | Página 1 de 1
            </div>
            
            <script>
                // Auto-print option
                // window.onload = function() { window.print(); }
            </script>
        </body>
        </html>';
        
        return $html;
    }
    
    /**
     * Genera HTML para Excel
     */
    private function generarHTMLExcel($ofertas, $filtros) {
        $html = '<table border="1" style="border-collapse: collapse;">';
        $html .= '<thead style="background-color: #2E5090; color: white; font-weight: bold;">
                    <tr>
                        <th style="padding: 10px; border: 1px solid #000;">ID</th>
                        <th style="padding: 10px; border: 1px solid #000;">Código</th>
                        <th style="padding: 10px; border: 1px solid #000;">Carrera/Strand</th>
                        <th style="padding: 10px; border: 1px solid #000;">Periodo</th>
                        <th style="padding: 10px; border: 1px solid #000;">Asignatura</th>
                        <th style="padding: 10px; border: 1px solid #000;">Docente</th>
                        <th style="padding: 10px; border: 1px solid #000;">Sección</th>
                        <th style="padding: 10px; border: 1px solid #000;">Horario</th>
                        <th style="padding: 10px; border: 1px solid #000;">Aula</th>
                        <th style="padding: 10px; border: 1px solid #000;">Cupos Disponibles</th>
                        <th style="padding: 10px; border: 1px solid #000;">Cupos Ocupados</th>
                        <th style="padding: 10px; border: 1px solid #000;">Cupos Totales</th>
                        <th style="padding: 10px; border: 1px solid #000;">Estado</th>
                        <th style="padding: 10px; border: 1px solid #000;">Fecha Creación</th>
                    </tr>
                  </thead>
                  <tbody>';
        
        foreach ($ofertas as $oferta) {
            $html .= '<tr>
                <td style="padding: 8px; border: 1px solid #000;">' . $oferta['id'] . '</td>
                <td style="padding: 8px; border: 1px solid #000;">' . htmlspecialchars($oferta['codigo']) . '</td>
                <td style="padding: 8px; border: 1px solid #000;">' . htmlspecialchars($oferta['carrera']) . '</td>
                <td style="padding: 8px; border: 1px solid #000;">' . htmlspecialchars($oferta['periodo']) . '</td>
                <td style="padding: 8px; border: 1px solid #000;">' . htmlspecialchars($oferta['asignatura']) . '</td>
                <td style="padding: 8px; border: 1px solid #000;">' . htmlspecialchars($oferta['docente']) . '</td>
                <td style="padding: 8px; border: 1px solid #000;">' . htmlspecialchars($oferta['seccion']) . '</td>
                <td style="padding: 8px; border: 1px solid #000;">' . htmlspecialchars($oferta['horario']) . '</td>
                <td style="padding: 8px; border: 1px solid #000;">' . htmlspecialchars($oferta['aula']) . '</td>
                <td style="padding: 8px; border: 1px solid #000; text-align: center;">' . $oferta['cupos_disponibles'] . '</td>
                <td style="padding: 8px; border: 1px solid #000; text-align: center;">' . $oferta['cupos_ocupados'] . '</td>
                <td style="padding: 8px; border: 1px solid #000; text-align: center;">' . $oferta['cupos_totales'] . '</td>
                <td style="padding: 8px; border: 1px solid #000;">' . ucfirst($oferta['estado']) . '</td>
                <td style="padding: 8px; border: 1px solid #000;">' . date('d/m/Y H:i', strtotime($oferta['fecha_creacion'])) . '</td>
            </tr>';
        }
        
        $html .= '</tbody></table>';
        return $html;
    }
    
    /**
     * Genera PDF usando browser print (método alternativo sin librerías externas)
     */
    private function generarPDFConBrowser($html, $filename) {
        // Guardar en sesión para mostrar en vista previa
        $_SESSION['pdf_content'] = $html;
        $_SESSION['pdf_filename'] = $filename;
        
        header('Location: reporte-vista-previa.php');
        exit();
    }
    
    /**
     * Obtiene texto del estado
     */
    private function getEstadoTexto($estado) {
        $estados = [
            'activa' => 'Activa',
            'cerrada' => 'Cerrada',
            'cancelada' => 'Cancelada',
            'en_proceso' => 'En Proceso'
        ];
        return $estados[$estado] ?? $estado;
    }
}

// ============================================
// MANEJO DE PETICIONES
// ============================================

$reporteController = new ReporteController();

// Generar PDF
if (isset($_GET['action']) && $_GET['action'] == 'pdf') {
    $filtros = [
        'periodo_id' => $_GET['periodo_id'] ?? null,
        'strand_id' => $_GET['strand_id'] ?? null,
        'estado' => $_GET['estado'] ?? null
    ];
    $reporteController->generarPDFOferta($filtros);
}

// Generar CSV
if (isset($_GET['action']) && $_GET['action'] == 'csv') {
    $filtros = [
        'periodo_id' => $_GET['periodo_id'] ?? null,
        'strand_id' => $_GET['strand_id'] ?? null,
        'estado' => $_GET['estado'] ?? null
    ];
    $reporteController->generarCSVOferta($filtros);
}

// Generar Excel
if (isset($_GET['action']) && $_GET['action'] == 'excel') {
    $filtros = [
        'periodo_id' => $_GET['periodo_id'] ?? null,
        'strand_id' => $_GET['strand_id'] ?? null,
        'estado' => $_GET['estado'] ?? null
    ];
    $reporteController->generarExcelOferta($filtros);
}

?>