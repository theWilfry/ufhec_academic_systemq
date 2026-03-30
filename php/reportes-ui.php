<?php
/**
 * UI para generación de reportes - Incluir en páginas de facultad
 */

function mostrarPanelReportes() {
    $con = connect();
    
    // Obtener periodos académicos
    $periodos = $con->query("SELECT id, periodo_nombre FROM periodos_academicos ORDER BY id DESC");
    
    // Obtener strands/carreras
    $strands = $con->query("SELECT strand_id, strand_name, strand_grade FROM strands ORDER BY strand_grade, strand_name");
    ?>
    
    <div class="reportes-panel" style="background: #fff; border-radius: 10px; padding: 20px; margin: 20px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h2 style="color: #2a5298; margin-bottom: 20px; border-bottom: 3px solid #f39c12; padding-bottom: 10px;">
            <i class="fas fa-chart-bar"></i> Reportes de Oferta Académica
        </h2>
        
        <form method="GET" action="php/reportes-controller.php" target="_blank" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: end;">
            
            <div style="flex: 1; min-width: 200px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #555;">Periodo Académico</label>
                <select name="periodo_id" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                    <option value="">Todos los periodos</option>
                    <?php while($p = $periodos->fetch_assoc()): ?>
                        <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['periodo_nombre']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div style="flex: 1; min-width: 200px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #555;">Carrera/Strand</label>
                <select name="strand_id" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                    <option value="">Todas las carreras</option>
                    <?php while($s = $strands->fetch_assoc()): ?>
                        <option value="<?php echo $s['strand_id']; ?>">
                            Grado <?php echo $s['strand_grade']; ?> - <?php echo htmlspecialchars($s['strand_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div style="flex: 1; min-width: 200px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold; color: #555;">Estado</label>
                <select name="estado" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                    <option value="">Todos</option>
                    <option value="activa">Activa</option>
                    <option value="cerrada">Cerrada</option>
                    <option value="en_proceso">En Proceso</option>
                </select>
            </div>
            
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <button type="submit" name="action" value="pdf" style="background: #dc3545; color: white; border: none; padding: 12px 20px; border-radius: 5px; cursor: pointer; font-weight: bold; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-file-pdf"></i> PDF
                </button>
                
                <button type="submit" name="action" value="excel" style="background: #28a745; color: white; border: none; padding: 12px 20px; border-radius: 5px; cursor: pointer; font-weight: bold; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-file-excel"></i> Excel
                </button>
                
                <button type="submit" name="action" value="csv" style="background: #17a2b8; color: white; border: none; padding: 12px 20px; border-radius: 5px; cursor: pointer; font-weight: bold; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-file-csv"></i> CSV
                </button>
            </div>
        </form>
        
        <div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 5px; font-size: 12px; color: #666;">
            <i class="fas fa-info-circle"></i> 
            <strong>Tip:</strong> Los reportes incluyen estadísticas de cupos y pueden filtrarse por periodo, carrera y estado.
        </div>
    </div>
    
    <?php
}

// Función para mostrar botones de exportación rápida en tablas
function botonesExportacionRapida($urlBase, $params = []) {
    $queryString = http_build_query($params);
    ?>
    <div style="margin: 10px 0; display: flex; gap: 10px;">
        <a href="<?php echo $urlBase; ?>?action=pdf&<?php echo $queryString; ?>" target="_blank" class="btn-export-pdf" style="background: #dc3545; color: white; padding: 8px 15px; border-radius: 4px; text-decoration: none; font-size: 12px;">
            <i class="fas fa-file-pdf"></i> PDF
        </a>
        <a href="<?php echo $urlBase; ?>?action=excel&<?php echo $queryString; ?>" class="btn-export-excel" style="background: #28a745; color: white; padding: 8px 15px; border-radius: 4px; text-decoration: none; font-size: 12px;">
            <i class="fas fa-file-excel"></i> Excel
        </a>
        <a href="<?php echo $urlBase; ?>?action=csv&<?php echo $queryString; ?>" class="btn-export-csv" style="background: #17a2b8; color: white; padding: 8px 15px; border-radius: 4px; text-decoration: none; font-size: 12px;">
            <i class="fas fa-file-csv"></i> CSV
        </a>
    </div>
    <?php
}
?>