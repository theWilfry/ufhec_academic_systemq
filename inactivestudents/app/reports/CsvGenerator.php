<?php
// /app/reports/CsvGenerator.php

class CsvGenerator {
    
    /**
     * Descarga datos como archivo CSV
     * 
     * @param array $data Datos a exportar
     * @param string $filename Nombre del archivo sin extensión
     * @param array $columns Mapeo de columnas (opcional)
     */
    public function download($data, $filename = 'export', $columns = []) {
        // Sanitizar nombre de archivo
        $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filename);
        $filename .= '.csv';
        
        // Establecer headers para descarga
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Agregar BOM UTF-8 para Excel
        echo "\xEF\xBB\xBF";
        
        // Abrir output
        $output = fopen('php://output', 'w');
        
        if ($output === false) {
            throw new Exception('Error al crear el archivo CSV');
        }
        
        try {
            if (!empty($data)) {
                // Determinar las columnas a usar
                if (empty($columns)) {
                    // Usar las claves del primer registro
                    $columns = array_keys($data[0]);
                }
                
                // Escribir headers
                fputcsv($output, $columns);
                
                // Escribir filas de datos
                foreach ($data as $row) {
                    $rowData = [];
                    foreach ($columns as $column) {
                        $rowData[] = $this->sanitizeValue($row[$column] ?? '');
                    }
                    fputcsv($output, $rowData);
                }
            } else {
                // Escribir mensaje de no datos
                fputcsv($output, ['No hay datos disponibles para exportar']);
            }
        } finally {
            fclose($output);
        }
        
        exit;
    }
    
    /**
     * Genera y descarga un reporte de estudiantes
     * 
     * @param array $students Lista de estudiantes
     * @param string $filename Nombre del archivo
     */
    public function downloadStudentReport($students, $filename = 'estudiantes') {
        $columns = [
            'ID Estudiante',
            'LRN',
            'Nombre Completo',
            'Género',
            'Teléfono',
            'Correo',
            'Carrera',
            'Sección',
            'Estado',
            'Fecha Nacimiento'
        ];
        
        $this->download($students, $filename, $columns);
    }
    
    /**
     * Genera y descarga un reporte de oferta académica
     * 
     * @param array $courses Lista de cursos
     * @param string $filename Nombre del archivo
     */
    public function downloadCourseOfferReport($courses, $filename = 'oferta_academica') {
        $columns = [
            'Carrera',
            'Nivel',
            'Materia',
            'Semestre',
            'Tipo'
        ];
        
        $this->download($courses, $filename, $columns);
    }
    
    /**
     * Genera y descarga un reporte de calificaciones
     * 
     * @param array $grades Lista de calificaciones
     * @param string $filename Nombre del archivo
     */
    public function downloadGradesReport($grades, $filename = 'calificaciones') {
        $columns = [
            'ID Estudiante',
            'Estudiante',
            'LRN',
            'Carrera',
            'Sección',
            'Materia',
            'Semestre',
            'Periodo 1',
            'Periodo 2',
            'Promedio Final',
            'Estado'
        ];
        
        $this->download($grades, $filename, $columns);
    }
    
    /**
     * Genera y descarga datos completos de un estudiante
     * 
     * @param array $studentData Datos del estudiante
     * @param string $filename Nombre del archivo
     */
    public function downloadStudentFullData($studentData, $filename = 'datos_estudiante') {
        // Convertir datos anidados a formato plano
        $flattenedData = $this->flattenStudentData($studentData);
        $columns = array_keys($flattenedData[0] ?? ['Sin datos']);
        
        $this->download($flattenedData, $filename, $columns);
    }
    
    /**
     * Convierte datos anidados de estudiante a formato plano
     * 
     * @param array $studentData Datos anidados
     * @return array Datos aplanados
     */
    private function flattenStudentData($studentData) {
        $result = [];
        
        foreach ($studentData as $sectionName => $sectionData) {
            foreach ($sectionData as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $subKey => $subValue) {
                        $result[$key . '_' . $subKey] = $subValue;
                    }
                } else {
                    $result[$key] = $value;
                }
            }
        }
        
        return [$result];
    }
    
    /**
     * Sanitiza un valor para CSV
     * 
     * @param mixed $value Valor a sanitizar
     * @return string Valor sanitizado
     */
    private function sanitizeValue($value) {
        if (is_null($value)) {
            return '';
        }
        
        if (is_bool($value)) {
            return $value ? 'Sí' : 'No';
        }
        
        if (is_numeric($value)) {
            return $value;
        }
        
        // Convertir a string y limpiar
        $value = (string) $value;
        
        // Escapar comillas dobles
        $value = str_replace('"', '""', $value);
        
        // Eliminar caracteres no imprimibles excepto saltos de línea
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
        
        return $value;
    }
    
    /**
     * Genera un array de columnas desde los datos
     * 
     * @param array $data Datos
     * @return array Nombres de columnas
     */
    public function getColumnsFromData($data) {
        if (empty($data)) {
            return [];
        }
        
        $columns = [];
        $this->extractColumns($data, $columns);
        
        return array_unique($columns);
    }
    
    /**
     * Extrae columnas recursivamente de los datos
     * 
     * @param array $data Datos
     * @param array &$columns Referencia al array de columnas
     */
    private function extractColumns($data, &$columns) {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $this->extractColumns($value, $columns);
            } else {
                $columns[] = $key;
            }
        }
    }
}
