<?php
session_start();
require_once __DIR__ . '/../../public_html/config/database.php';

// Verificação de autenticação
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /login.php');
    exit;
}

// Carregar dados
$hotmart_lectures = require __DIR__ . '/data_hotmart.php';
$system_lectures = require __DIR__ . '/data_lectures.php';

// Ordenar alfabeticamente
sort($hotmart_lectures);
usort($system_lectures, function($a, $b) {
    return strcasecmp($a['title'], $b['title']);
});

// Buscar mapeamentos existentes
$existing_mappings = [];
try {
    $stmt = $pdo->query("SELECT id, hotmart_title, lecture_id, lecture_title FROM hotmart_lecture_mapping ORDER BY hotmart_title");
    $existing_mappings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Erro ao buscar mapeamentos: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapeamento de Palestras - Hotmart & Sistema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .main-container { max-width: 1800px; margin: 20px auto; }
        .header-section { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .mapping-container { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; }
        .lecture-column { background: white; border-radius: 10px; padding: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); height: 70vh; overflow-y: auto; }
        .lecture-item { padding: 10px; margin-bottom: 8px; border: 1px solid #dee2e6; border-radius: 5px; cursor: pointer; transition: all 0.3s; font-size: 0.9em; }
        .lecture-item:hover { background-color: #e3f2fd; border-color: #2196F3; }
        .lecture-item.selected { background-color: #bbdefb; border-color: #1976D2; border-width: 2px; }
        .search-box { margin-bottom: 15px; }
        .stats { display: flex; gap: 20px; margin-bottom: 15px; }
        .stat-box { flex: 1; padding: 15px; border-radius: 8px; text-align: center; }
        .stat-box.hotmart { background-color: #e3f2fd; }
        .stat-box.system { background-color: #f3e5f5; }
        .stat-box.mapped { background-color: #e8f5e9; }
        .mapping-item { background: #fff3cd; padding: 10px; margin-bottom: 8px; border-radius: 5px; border-left: 4px solid #ffc107; display: flex; justify-content: space-between; align-items: start; }
        .mapping-content { flex: 1; }
        .mapping-title { font-weight: 600; color: #333; margin-bottom: 5px; }
        .mapping-subtitle { font-size: 0.85em; color: #666; }
        .btn-delete-mapping { padding: 2px 8px; font-size: 0.8em; }
        .column-title { font-weight: bold; color: #1976D2; margin-bottom: 15px; font-size: 1.1em; border-bottom: 2px solid #1976D2; padding-bottom: 8px; }
        .btn-map { position: sticky; top: 0; z-index: 10; margin-bottom: 15px; }
        .alert-fixed { position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px; }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Header -->
        <div class="header-section">
            <h2><i class="fas fa-link"></i> Mapeamento de Palestras Hotmart ↔ Sistema</h2>
            <p class="mb-0 text-muted">Selecione uma palestra da Hotmart e uma do Sistema, depois clique em "Associar" para criar o mapeamento.</p>
        </div>

        <!-- Stats -->
        <div class="stats">
            <div class="stat-box hotmart">
                <h4><?php echo count($hotmart_lectures); ?></h4>
                <small>Palestras Hotmart</small>
            </div>
            <div class="stat-box system">
                <h4><?php echo count($system_lectures); ?></h4>
                <small>Palestras Sistema</small>
            </div>
            <div class="stat-box mapped">
                <h4 id="mappedCount"><?php echo count($existing_mappings); ?></h4>
                <small>Associações Criadas</small>
            </div>
        </div>

        <!-- Mapping Interface -->
        <div class="mapping-container">
            <!-- Palestras Hotmart -->
            <div class="lecture-column">
                <div class="column-title">
                    <i class="fas fa-store"></i> Palestras Hotmart
                </div>
                <input type="text" class="form-control search-box" id="searchHotmart" placeholder="🔍 Buscar palestra Hotmart...">
                <div id="hotmart-list">
                    <?php foreach ($hotmart_lectures as $index => $lecture): ?>
                        <div class="lecture-item" data-title="<?php echo htmlspecialchars($lecture); ?>" data-index="<?php echo $index; ?>">
                            <?php echo htmlspecialchars($lecture); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Palestras do Sistema -->
            <div class="lecture-column">
                <div class="column-title">
                    <i class="fas fa-database"></i> Palestras do Sistema
                </div>
                <input type="text" class="form-control search-box" id="searchSystem" placeholder="🔍 Buscar palestra do sistema...">
                <div id="system-list">
                    <?php foreach ($system_lectures as $lecture): ?>
                        <div class="lecture-item" 
                             data-id="<?php echo htmlspecialchars($lecture['id']); ?>" 
                             data-title="<?php echo htmlspecialchars($lecture['title']); ?>">
                            <?php echo htmlspecialchars($lecture['title']); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Associações Criadas -->
            <div class="lecture-column">
                <div class="column-title">
                    <i class="fas fa-check-circle"></i> Associações Criadas
                </div>
                <button class="btn btn-success btn-map w-100" id="btnAssociate" disabled>
                    <i class="fas fa-link"></i> Associar Selecionadas
                </button>
                <div id="mappings-list">
                    <?php if (empty($existing_mappings)): ?>
                        <p class="text-muted text-center mt-3">Nenhuma associação criada ainda.</p>
                    <?php else: ?>
                        <?php foreach ($existing_mappings as $mapping): ?>
                            <div class="mapping-item" data-mapping-id="<?php echo $mapping['id']; ?>">
                                <div class="mapping-content">
                                    <div class="mapping-title">
                                        <i class="fas fa-store text-primary"></i> <?php echo htmlspecialchars($mapping['hotmart_title']); ?>
                                    </div>
                                    <div class="mapping-subtitle">
                                        <i class="fas fa-arrow-down"></i> <?php echo htmlspecialchars($mapping['lecture_title']); ?>
                                    </div>
                                </div>
                                <button class="btn btn-danger btn-sm btn-delete-mapping" 
                                        data-mapping-id="<?php echo $mapping['id']; ?>"
                                        title="Deletar associação">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Container -->
    <div id="alertContainer"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedHotmart = null;
        let selectedSystem = null;

        // Seleção de palestras
        document.querySelectorAll('#hotmart-list .lecture-item').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('#hotmart-list .lecture-item').forEach(i => i.classList.remove('selected'));
                this.classList.add('selected');
                selectedHotmart = {
                    title: this.dataset.title
                };
                updateAssociateButton();
            });
        });

        document.querySelectorAll('#system-list .lecture-item').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('#system-list .lecture-item').forEach(i => i.classList.remove('selected'));
                this.classList.add('selected');
                selectedSystem = {
                    id: this.dataset.id,
                    title: this.dataset.title
                };
                updateAssociateButton();
            });
        });

        function updateAssociateButton() {
            const btn = document.getElementById('btnAssociate');
            if (selectedHotmart && selectedSystem) {
                btn.disabled = false;
            } else {
                btn.disabled = true;
            }
        }

        // Busca
        document.getElementById('searchHotmart').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            document.querySelectorAll('#hotmart-list .lecture-item').forEach(item => {
                const title = item.dataset.title.toLowerCase();
                item.style.display = title.includes(searchTerm) ? 'block' : 'none';
            });
        });

        document.getElementById('searchSystem').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            document.querySelectorAll('#system-list .lecture-item').forEach(item => {
                const title = item.dataset.title.toLowerCase();
                item.style.display = title.includes(searchTerm) ? 'block' : 'none';
            });
        });

        // Associar
        document.getElementById('btnAssociate').addEventListener('click', function() {
            if (!selectedHotmart || !selectedSystem) return;

            const data = {
                hotmart_title: selectedHotmart.title,
                lecture_id: selectedSystem.id,
                lecture_title: selectedSystem.title
            };

            fetch('save_mapping_ajax.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showAlert('Associação criada com sucesso!', 'success');
                    addMappingToList(result.mapping_id, data);
                    clearSelections();
                    updateMappedCount();
                } else {
                    showAlert('Erro: ' + result.message, 'danger');
                }
            })
            .catch(error => {
                showAlert('Erro ao salvar associação: ' + error, 'danger');
            });
        });

        // Deletar associação
        document.addEventListener('click', function(e) {
            if (e.target.closest('.btn-delete-mapping')) {
                const btn = e.target.closest('.btn-delete-mapping');
                const mappingId = btn.dataset.mappingId;
                
                if (confirm('Tem certeza que deseja deletar esta associação?')) {
                    fetch('delete_mapping_ajax.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: mappingId })
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            showAlert('Associação deletada com sucesso!', 'success');
                            document.querySelector(`[data-mapping-id="${mappingId}"]`).remove();
                            updateMappedCount();
                        } else {
                            showAlert('Erro ao deletar: ' + result.message, 'danger');
                        }
                    })
                    .catch(error => {
                        showAlert('Erro ao deletar associação: ' + error, 'danger');
                    });
                }
            }
        });

        function addMappingToList(mappingId, data) {
            const mappingsList = document.getElementById('mappings-list');
            const mappingItem = document.createElement('div');
            mappingItem.className = 'mapping-item';
            mappingItem.dataset.mappingId = mappingId;
            mappingItem.innerHTML = `
                <div class="mapping-content">
                    <div class="mapping-title">
                        <i class="fas fa-store text-primary"></i> ${escapeHtml(data.hotmart_title)}
                    </div>
                    <div class="mapping-subtitle">
                        <i class="fas fa-arrow-down"></i> ${escapeHtml(data.lecture_title)}
                    </div>
                </div>
                <button class="btn btn-danger btn-sm btn-delete-mapping" 
                        data-mapping-id="${mappingId}"
                        title="Deletar associação">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            mappingsList.insertBefore(mappingItem, mappingsList.firstChild);
        }

        function clearSelections() {
            document.querySelectorAll('.lecture-item.selected').forEach(item => item.classList.remove('selected'));
            selectedHotmart = null;
            selectedSystem = null;
            updateAssociateButton();
        }

        function updateMappedCount() {
            const count = document.querySelectorAll('#mappings-list .mapping-item').length;
            document.getElementById('mappedCount').textContent = count;
        }

        function showAlert(message, type) {
            const alertContainer = document.getElementById('alertContainer');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} alert-dismissible fade show alert-fixed`;
            alert.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            alertContainer.appendChild(alert);
            setTimeout(() => alert.remove(), 5000);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
