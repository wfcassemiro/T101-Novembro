<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Verificar se é admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: /login.php');
    exit;
}

$page_title = 'Palestras Agendadas - Admin';
$message = '';
$error = '';

// Buscar TODAS as palestras agendadas
try {
    $stmt = $pdo->query("
        SELECT id, title, speaker, announcement_date, lecture_time, description, image_path, is_active, created_at
        FROM upcoming_announcements
        ORDER BY announcement_date ASC, lecture_time ASC
    ");
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Estatísticas
    $stmt = $pdo->query("SELECT COUNT(*) FROM upcoming_announcements WHERE is_active = 1 AND announcement_date >= CURDATE()");
    $active_count = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM upcoming_announcements WHERE announcement_date >= CURDATE()");
    $upcoming_count = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM upcoming_announcements WHERE announcement_date < CURDATE()");
    $past_count = $stmt->fetchColumn();

} catch (PDOException $e) {
    $announcements = [];
    $active_count = 0;
    $upcoming_count = 0;
    $past_count = 0;
    $error = 'Erro ao carregar palestras: ' . $e->getMessage();
}

include __DIR__ . '/../vision/includes/head.php';
include __DIR__ . '/../vision/includes/header.php';
include __DIR__ . '/../vision/includes/sidebar.php';
?>

<div class="main-content">
    <div class="glass-hero">
        <div class="hero-content">
            <h1><i class="fas fa-calendar-alt"></i> Palestras Agendadas</h1>
            <p>Gerencie os anúncios de próximas palestras da plataforma</p>
            <div style="display: flex; gap: 1rem;">
                <a href="index.php" class="cta-btn">
                    <i class="fas fa-arrow-left"></i> Voltar ao Admin
                </a>
                <button class="cta-btn" onclick="openAddLectureModal()" style="background: linear-gradient(135deg, #4CAF50, #388E3C);">
                    <i class="fas fa-plus"></i> Nova Palestra
                </button>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="alert-success">
        <i class="fas fa-check-circle"></i>
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert-error">
        <i class="fas fa-exclamation-triangle"></i>
        <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <!-- Estatísticas -->
    <div class="stats-grid">
        <div class="video-card stats-card">
            <div class="stats-content">
                <div class="stats-info">
                    <h3>Ativas</h3>
                    <span class="stats-number"><?php echo number_format($active_count); ?></span>
                </div>
                <div class="stats-icon stats-icon-green">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>

        <div class="video-card stats-card">
            <div class="stats-content">
                <div class="stats-info">
                    <h3>Próximas</h3>
                    <span class="stats-number"><?php echo number_format($upcoming_count); ?></span>
                </div>
                <div class="stats-icon stats-icon-blue">
                    <i class="fas fa-calendar-check"></i>
                </div>
            </div>
        </div>

        <div class="video-card stats-card">
            <div class="stats-content">
                <div class="stats-info">
                    <h3>Passadas</h3>
                    <span class="stats-number"><?php echo number_format($past_count); ?></span>
                </div>
                <div class="stats-icon stats-icon-orange">
                    <i class="fas fa-history"></i>
                </div>
            </div>
        </div>

        <div class="video-card stats-card">
            <div class="stats-content">
                <div class="stats-info">
                    <h3>Total</h3>
                    <span class="stats-number"><?php echo count($announcements); ?></span>
                </div>
                <div class="stats-icon stats-icon-purple">
                    <i class="fas fa-list"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Palestras -->
    <div class="video-card">
        <h2><i class="fas fa-list"></i> Todas as Palestras</h2>

        <?php if (empty($announcements)): ?>
        <div style="text-align: center; padding: 60px 20px;">
            <i class="fas fa-calendar-times" style="font-size: 4rem; color: #666; margin-bottom: 20px;"></i>
            <h3 style="font-size: 1.5rem; margin-bottom: 15px;">Nenhuma palestra cadastrada</h3>
            <p style="color: #ccc; margin-bottom: 30px;">Adicione a primeira palestra clicando no botão abaixo.</p>
            <button class="cta-btn" onclick="openAddLectureModal()">
                <i class="fas fa-plus"></i> Adicionar Palestra
            </button>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th><i class="fas fa-image"></i> Imagem</th>
                        <th><i class="fas fa-chalkboard-teacher"></i> Palestra</th>
                        <th><i class="fas fa-user"></i> Palestrante</th>
                        <th><i class="fas fa-calendar"></i> Data</th>
                        <th><i class="fas fa-clock"></i> Horário</th>
                        <th><i class="fas fa-toggle-on"></i> Status</th>
                        <th><i class="fas fa-cogs"></i> Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($announcements as $announcement): 
                        $isPast = strtotime($announcement['announcement_date']) < strtotime('today');
                        $isToday = strtotime($announcement['announcement_date']) == strtotime('today');
                    ?>
                    <tr style="<?php echo $isPast ? 'opacity: 0.6;' : ''; ?>">
                        <td>
                            <?php if ($announcement['image_path']): ?>
                                <img src="<?php echo htmlspecialchars($announcement['image_path']); ?>" 
                                     alt="Preview" 
                                     style="width: 80px; height: 60px; object-fit: cover; border-radius: 6px; border: 1px solid rgba(255,255,255,0.1);">
                            <?php else: ?>
                                <div style="width: 80px; height: 60px; background: rgba(255,255,255,0.05); border-radius: 6px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-image" style="font-size: 1.5rem; opacity: 0.3;"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="project-info">
                                <span class="text-primary"><?php echo htmlspecialchars($announcement['title']); ?></span>
                                <span class="project-client"><?php echo htmlspecialchars(substr($announcement['description'] ?? '', 0, 80)) . (strlen($announcement['description'] ?? '') > 80 ? '...' : ''); ?></span>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($announcement['speaker']); ?></td>
                        <td>
                            <div>
                                <?php echo date('d/m/Y', strtotime($announcement['announcement_date'])); ?>
                                <?php if ($isToday): ?>
                                    <span class="status-badge" style="background: rgba(255, 193, 7, 0.2); border: 1px solid rgba(255, 193, 7, 0.5); color: #FFD54F; margin-left: 5px;">
                                        HOJE
                                    </span>
                                <?php elseif ($isPast): ?>
                                    <span class="status-badge status-cancelled" style="margin-left: 5px;">
                                        PASSADA
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($announcement['lecture_time'] ?? '19:00'); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $announcement['is_active'] ? 'completed' : 'cancelled'; ?>">
                                <?php echo $announcement['is_active'] ? 'Ativa' : 'Inativa'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="page-btn" 
                                        onclick="editLecture('<?php echo htmlspecialchars($announcement['id']); ?>')"
                                        title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                
                                <button class="page-btn" 
                                        onclick="toggleStatus('<?php echo htmlspecialchars($announcement['id']); ?>', <?php echo $announcement['is_active'] ? 'false' : 'true'; ?>)"
                                        title="<?php echo $announcement['is_active'] ? 'Desativar' : 'Ativar'; ?>">
                                    <i class="fas fa-toggle-<?php echo $announcement['is_active'] ? 'on' : 'off'; ?>"></i>
                                </button>

                                <button class="page-btn btn-danger" 
                                        onclick="deleteLecture('<?php echo htmlspecialchars($announcement['id']); ?>')"
                                        title="Excluir">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de Adicionar/Editar Palestra -->
<div id="lectureModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h2 id="modalTitle"><i class="fas fa-plus-circle"></i> Nova Palestra</h2>
            <button class="modal-close" onclick="closeLectureModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="lectureForm" enctype="multipart/form-data">
            <input type="hidden" id="lectureId" name="lectureId">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                <!-- Título -->
                <div style="grid-column: span 2;">
                    <label style="font-weight: 600; font-size: 0.95rem; color: #fff; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <i class="fas fa-heading" style="color: #FFD700;"></i> Título da Palestra *
                    </label>
                    <input type="text" id="lectureTitle" name="lectureTitle" required
                           style="width: 100%; padding: 0.75rem 1rem; border-radius: 8px; border: 1px solid rgba(255, 255, 255, 0.2); background: rgba(255, 255, 255, 0.05); color: #fff; font-size: 0.95rem;">
                </div>

                <!-- Palestrante -->
                <div style="grid-column: span 2;">
                    <label style="font-weight: 600; font-size: 0.95rem; color: #fff; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <i class="fas fa-user" style="color: #FFD700;"></i> Palestrante *
                    </label>
                    <input type="text" id="lectureSpeaker" name="lectureSpeaker" required
                           style="width: 100%; padding: 0.75rem 1rem; border-radius: 8px; border: 1px solid rgba(255, 255, 255, 0.2); background: rgba(255, 255, 255, 0.05); color: #fff; font-size: 0.95rem;">
                </div>

                <!-- Data -->
                <div>
                    <label style="font-weight: 600; font-size: 0.95rem; color: #fff; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <i class="fas fa-calendar" style="color: #FFD700;"></i> Data *
                    </label>
                    <input type="date" id="lectureDate" name="lectureDate" required
                           style="width: 100%; padding: 0.75rem 1rem; border-radius: 8px; border: 1px solid rgba(255, 255, 255, 0.2); background: rgba(255, 255, 255, 0.05); color: #fff; font-size: 0.95rem;">
                </div>

                <!-- Horário -->
                <div>
                    <label style="font-weight: 600; font-size: 0.95rem; color: #fff; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <i class="fas fa-clock" style="color: #FFD700;"></i> Horário *
                    </label>
                    <input type="time" id="lectureTime" name="lectureTime" value="19:00" required
                           style="width: 100%; padding: 0.75rem 1rem; border-radius: 8px; border: 1px solid rgba(255, 255, 255, 0.2); background: rgba(255, 255, 255, 0.05); color: #fff; font-size: 0.95rem;">
                </div>

                <!-- Imagem -->
                <div style="grid-column: span 2;">
                    <label style="font-weight: 600; font-size: 0.95rem; color: #fff; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <i class="fas fa-image" style="color: #FFD700;"></i> Imagem da Palestra
                    </label>
                    <input type="file" id="lectureImage" name="lectureImage" accept="image/*"
                           style="width: 100%; padding: 0.75rem 1rem; border-radius: 8px; border: 1px solid rgba(255, 255, 255, 0.2); background: rgba(255, 255, 255, 0.05); color: #fff; font-size: 0.95rem;">
                    <small style="color: rgba(255, 255, 255, 0.6); font-size: 0.85em; display: block; margin-top: 0.5rem;">
                        Formatos aceitos: JPG, PNG, WEBP (Recomendado: 800x600px)
                    </small>
                    <div id="imagePreview" style="margin-top: 1rem;"></div>
                </div>

                <!-- Descrição -->
                <div style="grid-column: span 2;">
                    <label style="font-weight: 600; font-size: 0.95rem; color: #fff; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <i class="fas fa-file-alt" style="color: #FFD700;"></i> Descrição
                    </label>
                    <textarea id="lectureSummary" name="lectureSummary" rows="4"
                              style="width: 100%; padding: 0.75rem 1rem; border-radius: 8px; border: 1px solid rgba(255, 255, 255, 0.2); background: rgba(255, 255, 255, 0.05); color: #fff; font-size: 0.95rem; resize: vertical; min-height: 100px; line-height: 1.6;"></textarea>
                </div>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" class="page-btn" onclick="closeLectureModal()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" class="cta-btn">
                    <i class="fas fa-save"></i> Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Abrir modal para adicionar
function openAddLectureModal() {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus-circle"></i> Nova Palestra';
    document.getElementById('lectureForm').reset();
    document.getElementById('lectureId').value = '';
    document.getElementById('imagePreview').innerHTML = '';
    document.getElementById('lectureModal').style.display = 'flex';
}

// Fechar modal
function closeLectureModal() {
    document.getElementById('lectureModal').style.display = 'none';
}

// Preview de imagem
document.getElementById('lectureImage').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('imagePreview');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" style="max-width: 100%; height: auto; border-radius: 8px; border: 1px solid rgba(255,255,255,0.2);">`;
        };
        reader.readAsDataURL(file);
    } else {
        preview.innerHTML = '';
    }
});

// Submit do formulário
document.getElementById('lectureForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    try {
        const response = await fetch('/manage_announcements.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Palestra salva com sucesso!');
            window.location.reload();
        } else {
            alert('Erro ao salvar palestra: ' + (data.error || 'Erro desconhecido'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Erro ao processar requisição');
    }
});

// Editar palestra
async function editLecture(id) {
    try {
        const response = await fetch(`/manage_announcements.php?id=${id}`);
        const data = await response.json();
        
        if (data.error) {
            alert('Erro ao carregar palestra: ' + data.error);
            return;
        }
        
        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Editar Palestra';
        document.getElementById('lectureId').value = data.id;
        document.getElementById('lectureTitle').value = data.title || '';
        document.getElementById('lectureSpeaker').value = data.speaker || '';
        document.getElementById('lectureDate').value = data.lecture_date || '';
        document.getElementById('lectureTime').value = data.lecture_time || '19:00';
        document.getElementById('lectureSummary').value = data.description || '';
        
        const preview = document.getElementById('imagePreview');
        if (data.image_path) {
            preview.innerHTML = `<img src="${data.image_path}" style="max-width: 100%; height: auto; border-radius: 8px; border: 1px solid rgba(255,255,255,0.2);">`;
        } else {
            preview.innerHTML = '';
        }
        
        document.getElementById('lectureModal').style.display = 'flex';
    } catch (error) {
        console.error('Error:', error);
        alert('Erro ao carregar dados da palestra');
    }
}

// Toggle status
async function toggleStatus(id, newStatus) {
    if (!confirm('Deseja alterar o status desta palestra?')) return;
    
    // Implementar toggle de status via API
    alert('Funcionalidade de toggle em desenvolvimento. Use editar para alterar status.');
}

// Deletar palestra
async function deleteLecture(id) {
    if (!confirm('Tem certeza que deseja excluir esta palestra? Esta ação não pode ser desfeita.')) return;
    
    try {
        const response = await fetch(`/manage_announcements.php?id=${id}`, {
            method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Palestra excluída com sucesso!');
            window.location.reload();
        } else {
            alert('Erro ao excluir palestra: ' + (data.error || 'Erro desconhecido'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Erro ao processar requisição');
    }
}

// Fechar modal ao clicar fora
document.getElementById('lectureModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeLectureModal();
    }
});
</script>

<style>
.modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(5px);
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: linear-gradient(135deg, rgba(30, 30, 30, 0.95), rgba(20, 20, 20, 0.95));
    margin: auto;
    padding: 0;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 16px;
    width: 90%;
    max-width: 700px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
}

.modal-header {
    background: linear-gradient(135deg, #7B61FF, #483D8B);
    color: white;
    padding: 1.5rem 2rem;
    border-radius: 16px 16px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
    font-size: 1.3rem;
}

.modal-close {
    background: transparent;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    opacity: 0.8;
    transition: opacity 0.3s;
}

.modal-close:hover {
    opacity: 1;
}

.modal-content form {
    padding: 2rem;
}
</style>

<?php include __DIR__ . '/../vision/includes/footer.php'; ?>
