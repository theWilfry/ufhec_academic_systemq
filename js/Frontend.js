// Funciones JavaScript para descargar reportes

// Descargar PDF de oferta académica
function downloadCourseOfferPdf(careerId = 0, semester = 0) {
    window.open(`/api/reports.php?action=course-offer&format=pdf&career=${careerId}&semester=${semester}`, '_blank');
}

// Descargar CSV de estudiantes
function downloadStudentsCsv(sectionId = 0, careerId = 0) {
    window.location.href = `/api/reports.php?action=student-list&format=csv&section=${sectionId}&career=${careerId}`;
}

// Descargar reporte de calificaciones
function downloadGradesReport(sectionId, format = 'pdf') {
    window.open(`/api/reports.php?action=grades&format=${format}&section=${sectionId}`, '_blank');
}
