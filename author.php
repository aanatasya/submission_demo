<?php
require 'db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'author') {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Add / Update Article
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_article'])) {
    $category = $_POST['category'];
    $title = $_POST['title'];
    $content = $_POST['content'];
    $article_id = $_POST['article_id'] ?? '';

    if (!empty($article_id)) {
        $stmt = $conn->prepare("UPDATE articles SET category = ?, title = ?, content = ? WHERE id = ? AND author_id = ? AND status = 'Editing'");
        $stmt->bind_param("sssii", $category, $title, $content, $article_id, $user_id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("INSERT INTO articles (author_id, category, title, content, status) VALUES (?, ?, ?, ?, 'Editing')");
        $stmt->bind_param("isss", $user_id, $category, $title, $content);
        $stmt->execute();
    }
    header("Location: author.php");
    exit;
}


if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM articles WHERE id = ? AND author_id = ? AND status = 'Editing'");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    header("Location: author.php");
    exit;
}


if (isset($_GET['submit_review'])) {
    $id = $_GET['submit_review'];
    $stmt = $conn->prepare("UPDATE articles SET status = 'Under Review' WHERE id = ? AND author_id = ? AND status = 'Editing'");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    header("Location: author.php");
    exit;
}

$edit_data = ['id' => '', 'category' => '', 'title' => '', 'content' => ''];
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM articles WHERE id = ? AND author_id = ? AND status = 'Editing'");
    $stmt->bind_param("ii", $_GET['edit'], $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $edit_data = $row;
    }
}

$stmt = $conn->prepare("SELECT * FROM articles WHERE author_id = ? ORDER BY id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$articles = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Author Panel - Submission System</title>
    <style>
        body { font-family: sans-serif; margin: 30px; background: #f9f9f9; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #6b8e78; color: white; padding: 15px 20px; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #f2f2f2; }
        form.card { background: white; padding: 20px; margin-top: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        input, textarea { width: 100%; padding: 8px; margin: 6px 0 12px 0; box-sizing: border-box; }
        button { background: #6b8e78; color: white; border: none; padding: 10px 15px; cursor: pointer; border-radius: 4px; }
        a { text-decoration: none; color: #333; }
        .btn-del { color: red; }
        .btn-sub { color: green; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Author Panel (Welcome, <?= htmlspecialchars($_SESSION['username']) ?>)</h2>
        <a href="logout.php" style="color: white;">Logout</a>
    </div>

    <form class="card" method="POST">
        <h3><?= $edit_data['id'] ? 'Edit Article' : 'New Submission' ?></h3>
        <input type="hidden" name="article_id" value="<?= $edit_data['id'] ?>">
        <label>Category:</label>
        <input type="text" name="category" value="<?= htmlspecialchars($edit_data['category']) ?>" required>
        <label>Title:</label>
        <input type="text" name="title" value="<?= htmlspecialchars($edit_data['title']) ?>" required>
        <label>Content:</label>
        <textarea name="content" rows="5" required><?= htmlspecialchars($edit_data['content']) ?></textarea>
        <button type="submit" name="save_article">Save Article</button>
    </form>

    <h3>My Submissions List</h3>
    <table>
        <tr>
            <th>Category</th>
            <th>Title</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php while($row = $articles->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['category']) ?></td>
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td><b><?= $row['status'] ?></b></td>
            <td>
                <?php if ($row['status'] === 'Editing'): ?>
                    <a href="author.php?edit=<?= $row['id'] ?>">Edit</a> | 
                    <a href="author.php?delete=<?= $row['id'] ?>" class="btn-del" onclick="return confirm('Are you sure you want to delete this?')">Delete</a> | 
                    <a href="author.php?submit_review=<?= $row['id'] ?>" class="btn-sub">Submit for Review</a>
                <?php else: ?>
                    <span style="color: gray;">Locked</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
