<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Verificação de autenticação
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /login.php');
    exit;
}

$page_title = 'Verificação de Certificados - Palestras Mapeadas';

// Buscar palestras recentemente mapeadas (últimas 24h, 7 dias, 30 dias)
$timeframes = [
    '24h' => '24 HOUR',
    '7d' => '7 DAY',
    '30d' => '30 DAY',
    'all' => 'all'
];

$selected_timeframe = isset($_GET['timeframe']) ? $_GET['timeframe'] : '7d';
$timeframe_sql = $selected_timeframe !== 'all' 
    ? "AND hlm.created_at >= NOW() - INTERVAL " . $timeframes[$selected_timeframe]
    : '';

// Inicializar variáveis
$mapped_lectures = [];
$certificate_stats = [];
$total_certificates = 0;
$total_users_with_certificates = 0;
$error_message = null;

try {
    // 1. Buscar palestras mapeadas no período
    $stmt = $pdo->query("
        SELECT 
            hlm.id as mapping_id,
            hlm.hotmart_title,
            hlm.lecture_id,
            hlm.lecture_title,
            hlm.created_at as mapped_at,
            l.speaker,
            l.duration_minutes
        FROM hotmart_lecture_mapping hlm
        LEFT JOIN lectures l ON l.id = hlm.lecture_id
        WHERE 1=1 $timeframe_sql
        ORDER BY hlm.created_at DESC
    ");
    $mapped_lectures = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Para cada palestra mapeada, verificar certificados emitidos
    $certificate_stats = [];
    $total_certificates = 0;
    $total_users_with_certificates = 0;

    foreach ($mapped_lectures as $lecture) {
        // Certificados emitidos APÓS o mapeamento
        $stmt = $pdo->prepare("
            SELECT 
                c.id,
                c.user_id,
                c.user_name,
                c.user_email,
                c.issued_at,
                c.generated_at,
                c.certificate_code,
                u.email as user_email_db,
                u.name as user_name_db
            FROM certificates c
            LEFT JOIN users u ON u.id = c.user_id
            WHERE c.lecture_id = ?
            AND c.issued_at >= ?
            ORDER BY c.issued_at DESC
        ");
        $stmt->execute([$lecture['lecture_id'], $lecture['mapped_at']]);
        $certificates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Certificados emitidos ANTES do mapeamento (para comparação)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM certificates
            WHERE lecture_id = ?
            AND issued_at < ?
        ");
        $stmt->execute([$lecture['lecture_id'], $lecture['mapped_at']]);
        $certs_before = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Usuários que visualizaram mas não têm certificado
        $stmt = $pdo->prepare("
            SELECT DISTINCT
                al.user_id,
                u.name,
                u.email,
                MAX(al.updated_at) as last_view,
                SUM(al.accumulated_watch_time) as total_watch_time
            FROM access_logs al
            LEFT JOIN users u ON u.id = al.user_id
            WHERE al.lecture_id = ?
            AND al.user_id NOT IN (
                SELECT user_id FROM certificates WHERE lecture_id = ?
            )
            GROUP BY al.user_id, u.name, u.email
            HAVING SUM(al.accumulated_watch_time) > 0
            ORDER BY total_watch_time DESC
        ");
        $stmt->execute([$lecture['lecture_id'], $lecture['lecture_id']]);
        $users_without_cert = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $certificate_stats[] = [
            'lecture' => $lecture,
            'certificates_after' => $certificates,
            'certificates_before' => $certs_before,
            'users_without_cert' => $users_without_cert
        ];

        $total_certificates += count($certificates);
        $total_users_with_certificates += count($certificates);
    }

} catch (PDOException $e) {
    $error_message = "Erro ao buscar dados: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; padding: 20px; }
        .header-card { background: white; padding: 25px; border-radius: 10px; margin-bottom: 25px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stat-number { font-size: 2.5em; font-weight: bold; margin: 10px 0; }
        .stat-label { color: #666; font-size: 0.9em; }
        .lecture-card { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .lecture-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px; border-bottom: 2px solid #e9ecef; padding-bottom: 15px; }
        .lecture-title { font-size: 1.2em; font-weight: 600; color: #333; }
        .lecture-meta { font-size: 0.85em; color: #666; margin-top: 5px; }
        .cert-badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 0.85em; font-weight: 500; margin: 5px; }
        .cert-badge.success { background-color: #d4edda; color: #155724; }
        .cert-badge.warning { background-color: #fff3cd; color: #856404; }
        .cert-badge.info { background-color: #d1ecf1; color: #0c5460; }
        .user-list { max-height: 300px; overflow-y: auto; }
        .user-item { padding: 10px; border-bottom: 1px solid #e9ecef; }
        .user-item:last-child { border-bottom: none; }
        .no-data { text-align: center; padding: 40px; color: #999; }
        .filter-section { margin-bottom: 20px; }
        .timeframe-btn { margin-right: 10px; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Header -->
        <div class="header-card">
            <h2><i class="fas fa-certificate"></i> Verificação de Certificados - Palestras Mapeadas</h2>
            <p class="mb-0 text-muted">Acompanhe os certificados emitidos para palestras que foram mapeadas recentemente</p>
        </div>

        <!-- Filtro de Período -->
        <div class="filter-section">
            <div class="btn-group" role="group">
                <a href="?timeframe=24h" class="btn btn-sm <?php echo $selected_timeframe === '24h' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    Últimas 24 horas
                </a>
                <a href="?timeframe=7d" class="btn btn-sm <?php echo $selected_timeframe === '7d' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    Últimos 7 dias
                </a>
                <a href="?timeframe=30d" class="btn btn-sm <?php echo $selected_timeframe === '30d' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    Últimos 30 dias
                </a>
                <a href="?timeframe=all" class="btn btn-sm <?php echo $selected_timeframe === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    Todos
                </a>
            </div>
        </div>

        <!-- Estatísticas Gerais -->
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-link fa-2x text-primary"></i>
                <div class="stat-number"><?php echo count($mapped_lectures); ?></div>
                <div class="stat-label">Palestras Mapeadas</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-certificate fa-2x text-success"></i>
                <div class="stat-number"><?php echo $total_certificates; ?></div>
                <div class="stat-label">Certificados Emitidos</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-users fa-2x text-info"></i>
                <div class="stat-number"><?php echo $total_users_with_certificates; ?></div>
                <div class="stat-label">Usuários Certificados</div>
            </div>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if (empty($mapped_lectures)): ?>
            <div class="lecture-card">
                <div class="no-data">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <p>Nenhuma palestra mapeada no período selecionado.</p>
                </div>
            </div>
        <?php else: ?>
            <!-- Lista de Palestras -->
            <?php foreach ($certificate_stats as $stat): 
                $lecture = $stat['lecture'];
                $certs_after = $stat['certificates_after'];
                $certs_before = $stat['certificates_before'];
                $users_without = $stat['users_without_cert'];
            ?>
                <div class="lecture-card">
                    <div class="lecture-header">
                        <div>
                            <div class="lecture-title">
                                <i class="fas fa-chalkboard-teacher"></i>
                                <?php echo htmlspecialchars($lecture['lecture_title']); ?>
                            </div>
                            <div class="lecture-meta">
                                <strong>Hotmart:</strong> <?php echo htmlspecialchars($lecture['hotmart_title']); ?><br>
                                <strong>Palestrante:</strong> <?php echo htmlspecialchars($lecture['speaker']); ?> | 
                                <strong>Duração:</strong> <?php echo $lecture['duration_minutes']; ?> min<br>
                                <strong>Mapeada em:</strong> <?php echo date('d/m/Y H:i', strtotime($lecture['mapped_at'])); ?>
                            </div>
                        </div>
                        <div>
                            <span class="cert-badge success">
                                <i class="fas fa-certificate"></i> <?php echo count($certs_after); ?> novos
                            </span>
                            <span class="cert-badge info">
                                <i class="fas fa-history"></i> <?php echo $certs_before; ?> anteriores
                            </span>
                            <?php if (count($users_without) > 0): ?>
                                <span class="cert-badge warning">
                                    <i class="fas fa-exclamation-triangle"></i> <?php echo count($users_without); ?> pendentes
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Certificados Emitidos Após Mapeamento -->
                        <div class="col-md-6">
                            <h5><i class="fas fa-check-circle text-success"></i> Certificados Emitidos</h5>
                            <?php if (empty($certs_after)): ?>
                                <p class="text-muted">Nenhum certificado emitido após o mapeamento.</p>
                            <?php else: ?>
                                <div class="user-list">
                                    <?php foreach ($certs_after as $cert): ?>
                                        <div class="user-item">
                                            <strong><?php echo htmlspecialchars($cert['user_name'] ?: $cert['user_name_db']); ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($cert['user_email'] ?: $cert['user_email_db']); ?>
                                            </small>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-clock"></i> Emitido em: <?php echo date('d/m/Y H:i', strtotime($cert['issued_at'])); ?>
                                            </small>
                                            <?php if ($cert['certificate_code']): ?>
                                                <br>
                                                <small class="text-muted">
                                                    <i class="fas fa-barcode"></i> Código: <?php echo htmlspecialchars($cert['certificate_code']); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Usuários Pendentes -->
                        <div class="col-md-6">
                            <h5><i class="fas fa-hourglass-half text-warning"></i> Usuários Sem Certificado</h5>
                            <?php if (empty($users_without)): ?>
                                <p class="text-success">
                                    <i class="fas fa-check"></i> Todos os usuários que assistiram já receberam certificado!
                                </p>
                            <?php else: ?>
                                <div class="user-list">
                                    <?php foreach ($users_without as $user): ?>
                                        <div class="user-item">
                                            <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?>
                                            </small>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-eye"></i> Última visualização: <?php echo date('d/m/Y H:i', strtotime($user['last_view'])); ?>
                                            </small>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-clock"></i> Tempo assistido: <?php echo round($user['total_watch_time'] / 60, 1); ?> min
                                            </small>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Botão Voltar -->
        <div class="text-center mt-4">
            <a href="map_lectures_interface.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Voltar para Mapeamento
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
