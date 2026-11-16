<?php
require 'db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'editor') {
    header("Location: index.php");
    exit;
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $action = $_GET['action'];

    if ($action === 'pass') {
        $stmt = $conn->prepare("UPDATE articles SET status = 'Approved' WHERE id = ? AND status = 'Under Review'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    } elseif ($action === 'return') {
        $stmt = $conn->prepare("UPDATE articles SET status = 'Editing' WHERE id = ? AND status = 'Under Review'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
    header("Location: editor.php");
    exit;
}

$query = "SELECT articles.*, users.username AS author_name 
          FROM articles 
          JOIN users ON articles.author_id = users.id 
          WHERE articles.status = 'Under Review' 
          ORDER BY articles.id DESC";
$pending_articles = $conn->query($query);

$view_article = null;
if (isset($_GET['view'])) {
    $stmt = $conn->prepare("SELECT articles.*, users.username AS author_name FROM articles JOIN users ON articles.author_id = users.id WHERE articles.id = ?");
    $stmt->bind_param("i", $_GET['view']);
    $stmt->execute();
    $view_article = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Editor Panel - Submission System</title>
    <style>
        body { font-family: sans-serif; margin: 30px; background: #f9f9f9; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #4a6b5d; color: white; padding: 15px 20px; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #f2f2f2; }
        .card { background: white; padding: 20px; margin-top: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        a { text-decoration: none; color: #2b503e; font-weight: bold; }
        .btn-pass { color: green; }
        .btn-return { color: orange; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Editor Panel (Welcome, <?= htmlspecialchars($_SESSION['username']) ?>)</h2>
        <a href="logout.php" style="color: white;">Logout</a>
    </div>

    <?php if ($view_article): ?>
    <div class="card">
        <h3>Article Details</h3>
        <p><b>Author:</b> <?= htmlspecialchars($view_article['author_name']) ?></p>
        <p><b>Category:</b> <?= htmlspecialchars($view_article['category']) ?></p>
        <p><b>Title:</b> <?= htmlspecialchars($view_article['title']) ?></p>
        <p><b>Status:</b> <?= htmlspecialchars($view_article['status']) ?></p>
        <hr>
        <p><b>Content:</b></p>
        <p><?= nl2br(htmlspecialchars($view_article['content'])) ?></p>
        <br>
        <a href="editor.php">Close Details</a>
    </div>
    <?php endif; ?>

    <h3>Pending Review List</h3>
    <table>
        <tr>
            <th>Author</th>
            <th>Category</th>
            <th>Title</th>
            <th>Action</th>
        </tr>
        <?php while($row = $pending_articles->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['author_name']) ?></td>
            <td><?= htmlspecialchars($row['category']) ?></td>
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td>
                <a href="editor.php?view=<?= $row['id'] ?>">View Content</a> | 
                <a href="editor.php?action=pass&id=<?= $row['id'] ?>" class="btn-pass" onclick="return confirm('Approve this article?')">Approve</a> | 
                <a href="editor.php?action=return&id=<?= $row['id'] ?>" class="btn-return" onclick="return confirm('Return back to author?')">Return</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>