<?php
require_once 'lib/DockerClient.php';
$docker = new DockerClient();

// Handle Actions
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['project'])) {
    $action = $_POST['action'];
    $project = $_POST['project'];
    
    if ($action === 'start') {
        $docker->startStack($project);
        $message = "Stack '$project' wird gestartet...";
    } elseif ($action === 'stop') {
        $docker->stopStack($project);
        $message = "Stack '$project' wird gestoppt...";
    } elseif ($action === 'restart') {
        $docker->restartStack($project);
        $message = "Stack '$project' wird neu gestartet...";
    }
}

$stacks = $docker->getStacks();

include 'header.php';
?>

<div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
    <div>
        <h1 class="gradient-text" style="margin-bottom: 8px;">Docker Stacks</h1>
        <p style="color: var(--text-muted); margin: 0;">Verwalten Sie Ihre Container-Umgebungen auf dieser Maschine.</p>
    </div>
    <div class="header-actions">
        <button class="btn btn-secondary" onclick="window.location.reload();">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 8px; vertical-align: middle;"><path d="M23 4v6h-6"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
            Aktualisieren
        </button>
    </div>
</div>

<?php if ($message): ?>
    <div class="glass-card" style="padding: 16px 24px; margin-bottom: 24px; background: rgba(37, 99, 235, 0.1); border-left: 4px solid var(--primary-color);">
        <p style="margin: 0; color: var(--primary-color); font-weight: 600;"><?php echo htmlspecialchars($message); ?></p>
    </div>
<?php endif; ?>

<?php 
$dockerHost = getenv('DOCKER_HOST') ?: 'unix:///var/run/docker.sock';
if (strpos($dockerHost, 'unix://') === 0 && !is_readable(substr($dockerHost, 7))): 
    $socketPath = substr($dockerHost, 7);
?>
    <div class="glass-card" style="padding: 24px; margin-bottom: 24px; background: rgba(239, 68, 68, 0.1); border-left: 4px solid var(--danger-color);">
        <h3 style="color: var(--danger-color); margin-top: 0;">Verbindungsproblem</h3>
        <p style="margin: 0; color: var(--text-main);">Der Docker-Socket (<code><?php echo htmlspecialchars($socketPath); ?></code>) ist nicht lesbar.</p>
        <p style="margin: 8px 0 0; font-size: 0.9rem; color: var(--text-muted);">Tipp: Führen Sie <code>sudo chmod 666 <?php echo htmlspecialchars($socketPath); ?></code> auf dem Host aus oder verwenden Sie eine TCP-Verbindung über die <code>DOCKER_HOST</code> Variable.</p>
    </div>
<?php endif; ?>

<?php if (empty($stacks)): ?>
    <div class="glass-card" style="padding: 48px; text-align: center;">
        <div class="stat-icon" style="margin: 0 auto 24px; background: rgba(113, 128, 150, 0.1); color: var(--text-muted);">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"/><rect x="2" y="14" width="20" height="8" rx="2" ry="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/></svg>
        </div>
        <h3>Keine Docker-Projekte gefunden</h3>
        <p style="color: var(--text-muted);">Stellen Sie sicher, dass der Docker-Socket korrekt eingebunden ist und Container laufen.</p>
    </div>
<?php else: ?>
    <div class="dashboard-grid" id="docker-grid">
        <?php foreach ($stacks as $name => $stack): ?>
            <div class="glass-card docker-tile">
                <div class="tile-header">
                    <div class="stack-info">
                        <div class="stack-icon <?php echo $stack['status'] === 'running' ? 'active' : ($stack['status'] === 'partial' ? 'partial' : 'inactive'); ?>">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                        </div>
                        <div>
                            <h3 style="margin: 0; font-size: 1.1rem;"><?php echo htmlspecialchars($name); ?></h3>
                            <span class="badge <?php 
                                echo $stack['status'] === 'running' ? 'badge-success' : 
                                    ($stack['status'] === 'partial' ? 'badge-warning' : 'badge-danger'); 
                            ?>">
                                <?php 
                                    if ($stack['status'] === 'running') echo 'Aktiv';
                                    elseif ($stack['status'] === 'partial') echo 'Teilweise aktiv';
                                    else echo 'Inaktiv';
                                ?>
                                (<?php echo $stack['active_count']; ?>/<?php echo $stack['total_count']; ?>)
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="tile-body">
                    <div class="container-list">
                        <?php foreach ($stack['containers'] as $container): ?>
                            <div class="container-item">
                                <span class="container-name"><?php echo htmlspecialchars(ltrim($container['Names'][0], '/')); ?></span>
                                <span class="container-status <?php echo $container['State']; ?>"><?php echo $container['Status']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="tile-footer">
                    <form method="POST" style="display: flex; gap: 8px; width: 100%;">
                        <input type="hidden" name="project" value="<?php echo htmlspecialchars($name); ?>">
                        <?php if ($stack['status'] !== 'running'): ?>
                            <button type="submit" name="action" value="start" class="btn btn-primary btn-sm" style="flex: 1;">
                                Starten
                            </button>
                        <?php endif; ?>
                        <?php if ($stack['status'] !== 'stopped'): ?>
                            <button type="submit" name="action" value="stop" class="btn btn-danger btn-sm" style="flex: 1;">
                                Stoppen
                            </button>
                        <?php endif; ?>
                        <button type="submit" name="action" value="restart" class="btn btn-secondary btn-sm" style="padding: 8px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 4v6h-6"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<style>
.docker-tile {
    display: flex;
    flex-direction: column;
    padding: 24px;
    height: 100%;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.docker-tile:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.12);
}

.tile-header {
    margin-bottom: 24px;
}

.stack-info {
    display: flex;
    align-items: center;
    gap: 16px;
}

.stack-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.stack-icon.active { 
    background: rgba(16, 185, 129, 0.15); 
    color: var(--success-color); 
    box-shadow: 0 0 20px rgba(16, 185, 129, 0.2);
    animation: pulse-green 2s infinite;
}

.stack-icon.partial { background: rgba(245, 158, 11, 0.15); color: var(--warning-color); }
.stack-icon.inactive { background: rgba(239, 68, 68, 0.15); color: var(--danger-color); }

@keyframes pulse-green {
    0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
    70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
    100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}

.tile-body {
    flex: 1;
    margin-bottom: 24px;
}

.container-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.container-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
    padding: 8px 12px;
    background: rgba(255, 255, 255, 0.3);
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.container-name {
    color: var(--text-main);
    font-weight: 600;
}

.container-status {
    font-size: 11px;
    color: var(--text-muted);
    padding: 2px 8px;
    background: rgba(0,0,0,0.05);
    border-radius: 10px;
}

.container-status.running { 
    color: var(--success-color); 
    background: rgba(16, 185, 129, 0.1);
}

.tile-footer {
    padding-top: 20px;
    border-top: 1px solid rgba(0,0,0,0.05);
}

.btn-sm {
    padding: 10px 16px;
    font-size: 13px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-danger {
    background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
    color: white;
}

.btn-danger:hover {
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    transform: translateY(-2px);
}

.btn-primary {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
}

.badge-danger {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger-color);
}
</style>

<?php include 'footer.php'; ?>
