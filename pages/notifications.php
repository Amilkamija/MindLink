<?php
$extra_css = '/assets/css/projects.css';
require_once __DIR__ . '/../includes/header2.php';
require_once __DIR__ . '/../config/db.php';

date_default_timezone_set('Europe/Dublin');

if (!isset($_SESSION['user_id'])) {
    header("Location: /pages/login.php");
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];

function getOrCreateProjectConversation($conn, $project_id) {
    $stmt = $conn->prepare("
        SELECT conversation_id
        FROM Conversations
        WHERE type = 'project' AND project_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing) {
        return (int)$existing['conversation_id'];
    }

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("
            INSERT INTO Conversations (type, project_id, created_at)
            VALUES ('project', ?, NOW())
        ");
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $conversation_id = (int)$conn->insert_id;
        $stmt->close();

        $users = [];

        $stmt = $conn->prepare("SELECT owner_id FROM Projects WHERE project_id = ? LIMIT 1");
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $owner = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($owner) {
            $users[] = (int)$owner['owner_id'];
        }

        $stmt = $conn->prepare("
            SELECT user_id
            FROM TeamMembership
            WHERE project_id = ? AND status = 'active'
        ");
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $members = $stmt->get_result();

        while ($m = $members->fetch_assoc()) {
            $users[] = (int)$m['user_id'];
        }

        $stmt->close();

        $users = array_unique($users);

        $stmt = $conn->prepare("
            INSERT IGNORE INTO ConversationParticipants (conversation_id, user_id)
            VALUES (?, ?)
        ");

        foreach ($users as $uid) {
            $stmt->bind_param("ii", $conversation_id, $uid);
            $stmt->execute();
        }

        $stmt->close();
        $conn->commit();

        return $conversation_id;
    } catch (Exception $e) {
        $conn->rollback();
        return 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $stmt = $conn->prepare("UPDATE Notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->bind_param("i", $current_user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: /pages/notifications.php");
    exit();
}

$stmt = $conn->prepare("
    SELECT notification_id, project_id, application_id, type, message, is_read, created_at
    FROM Notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="col-lg-10 content-area">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="section-pill">Notifications</div>

        <form method="POST">
            <button type="submit" name="mark_read" class="details-btn">Mark all as read</button>
        </form>
    </div>

    <div class="section-line"></div>

    <?php if ($result->num_rows > 0): ?>
        <?php while ($n = $result->fetch_assoc()): ?>
            <?php
            $type = $n['type'];
            $project_id = (int)($n['project_id'] ?? 0);
            $conversation_id = 0;

            if ($type === 'application_accepted' && $project_id > 0) {
                $conversation_id = getOrCreateProjectConversation($conn, $project_id);
            }
            ?>

            <div class="project-card" style="<?php echo ((int)$n['is_read'] === 0) ? 'border-left: 6px solid #7b8454;' : 'opacity: 0.75;'; ?>">
                <div class="project-card-header">
                    <h2><?php echo ((int)$n['is_read'] === 0) ? 'New Notification' : 'Notification'; ?></h2>
                    <span>
    <?php
    $timestamp = strtotime($n['created_at']);
    echo $timestamp
        ? htmlspecialchars(date('d M Y, H:i', $timestamp), ENT_QUOTES, 'UTF-8')
        : htmlspecialchars($n['created_at'], ENT_QUOTES, 'UTF-8');
    ?>
</span>
                </div>

                <div class="project-card-body">
                    <p><?php echo htmlspecialchars($n['message'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>

                <div class="project-card-actions">
                    <?php if ($type === 'new_application'): ?>
                        <a href="/pages/applications.php" class="details-btn">Applications</a>

                    <?php elseif ($type === 'application_accepted' && $conversation_id > 0): ?>
                        <a href="/pages/messages.php?conversation_id=<?php echo (int)$conversation_id; ?>" class="details-btn">Message</a>

                    <?php elseif ($project_id > 0): ?>
                        <a href="/pages/project_details.php?project_id=<?php echo $project_id; ?>" class="details-btn">View Project</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="section-line mt-4"></div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No notifications yet.</p>
    <?php endif; ?>

</div>

<?php
$stmt->close();
$conn->close();
require_once __DIR__ . '/../includes/footer.php';
?>