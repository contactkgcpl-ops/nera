<?php
require_once __DIR__ . '/../db.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$successMsg = '';
$errorMsg = '';

// Load Category for Edit
$category = null;
if (($action === 'edit' || isset($_POST['update'])) && $id > 0) {
    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $category = $stmt->fetch();
    if (!$category) {
        header("Location: index.php?msg=not_found");
        exit;
    }
}

// Handle Add Category Form submission
if (isset($_POST['insert'])) {
    $name = trim($_POST['name']);
    
    if (empty($name)) {
        $errorMsg = "Category name cannot be empty.";
    } elseif (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = "Please upload a valid category image.";
    } else {
        // Sanitize and rename image file
        $sanitizedName = sanitize_filename($name);
        $fileExt = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $newFileName = $sanitizedName . '.' . strtolower($fileExt);
        $targetPath = 'uploads/category/' . $newFileName;
        
        // Move file
        if (move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../' . $targetPath)) {
            // Save in DB
            $stmt = $db->prepare("INSERT INTO categories (name, image) VALUES (?, ?)");
            if ($stmt->execute([$name, $targetPath])) {
                header("Location: index.php?msg=cat_added");
                exit;
            } else {
                $errorMsg = "Failed to insert category into database.";
            }
        } else {
            $errorMsg = "Failed to move uploaded image to target folder.";
        }
    }
}

// Handle Edit Category Form submission
if (isset($_POST['update']) && $id > 0) {
    $name = trim($_POST['name']);
    
    if (empty($name)) {
        $errorMsg = "Category name cannot be empty.";
    } else {
        $targetPath = $category['image']; // Default to old image path
        $uploadOk = true;
        
        // Check if a new file upload was attempted
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
                // Sanitize and rename new file
                $sanitizedName = sanitize_filename($name);
                $fileExt = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $newFileName = $sanitizedName . '.' . strtolower($fileExt);
                $targetPath = 'uploads/category/' . $newFileName;
                
                // Move the file first, check if it succeeded
                if (move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../' . $targetPath)) {
                    // Only delete the old file if it exists and has a different filename/path
                    $oldPath = $category['image'];
                    if ($oldPath !== $targetPath && file_exists(__DIR__ . '/../' . $oldPath)) {
                        @unlink(__DIR__ . '/../' . $oldPath);
                    }
                } else {
                    $errorMsg = "Failed to save the new image. Please check folder permissions.";
                    $uploadOk = false;
                }
            } else {
                $uploadOk = false;
                switch ($_FILES['image']['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        $errorMsg = "The uploaded image is too large. Please upload a smaller image.";
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $errorMsg = "The image was only partially uploaded. Please try again.";
                        break;
                    default:
                        $errorMsg = "Failed to upload image. PHP error code: " . $_FILES['image']['error'];
                        break;
                }
            }
        } else {
            // No new image uploaded, rename existing image if category name changed
            $oldSanitized = sanitize_filename($category['name']);
            $newSanitized = sanitize_filename($name);
            
            if ($oldSanitized !== $newSanitized && file_exists(__DIR__ . '/../' . $category['image'])) {
                $fileExt = pathinfo($category['image'], PATHINFO_EXTENSION);
                $newFileName = $newSanitized . '.' . strtolower($fileExt);
                $newPath = 'uploads/category/' . $newFileName;
                
                if (rename(__DIR__ . '/../' . $category['image'], __DIR__ . '/../' . $newPath)) {
                    $targetPath = $newPath;
                }
            }
        }
        
        // Update in DB only if file validation succeeded
        if ($uploadOk) {
            $stmt = $db->prepare("UPDATE categories SET name = ?, image = ? WHERE id = ?");
            if ($stmt->execute([$name, $targetPath, $id])) {
                header("Location: index.php?msg=cat_updated");
                exit;
            } else {
                $errorMsg = "Failed to update category in database.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nera Foods - Manage Category</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <header class="admin-header">
        <a href="index.php" class="logo-link">
            <h1>NERA FOODS <span style="font-weight: 300; font-size: 0.95rem; opacity: 0.8;">| Admin</span></h1>
        </a>
        <nav class="admin-nav">
            <a href="index.php">Dashboard</a>
            <a href="categories.php" class="active">Categories</a>
            <a href="products.php">Products</a>
            <a href="../products.php" target="_blank">View Site &rarr;</a>
        </nav>
    </header>

    <div class="admin-container">
        <div class="page-header">
            <h2 class="page-title"><?= ($action === 'edit') ? 'Edit Category' : 'Add New Category' ?></h2>
            <a href="index.php" class="btn btn-outline btn-sm">&larr; Back to List</a>
        </div>

        <?php if (!empty($errorMsg)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>

        <div class="form-card">
            <form action="categories.php?id=<?= $id ?>" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name" class="form-label">Category Name</label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Spices & Seasonings" value="<?= $category ? htmlspecialchars($category['name']) : '' ?>" required>
                </div>

                <div class="form-group">
                    <label for="image" class="form-label">Category Icon / Image</label>
                    <input type="file" name="image" id="image" class="form-control" accept="image/*" <?= ($action !== 'edit') ? 'required' : '' ?>>
                    <p style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.4rem;">
                        Note: The uploaded image will be automatically renamed to match the category name format (e.g. <code>spices_seasonings.png</code>).
                    </p>
                    
                    <?php if ($category): ?>
                        <div class="current-image-preview">
                            <div>
                                <span class="form-label" style="font-size: 0.8rem; margin-bottom: 0.25rem;">Current Image:</span>
                                <img src="../<?= htmlspecialchars($category['image']) ?>?t=<?= time() ?>" alt="Current Category Image">
                            </div>
                            <span style="font-family: monospace; font-size: 0.78rem; color: var(--text-muted);"><?= htmlspecialchars($category['image']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <?php if ($action === 'edit'): ?>
                        <button type="submit" name="update" class="btn btn-primary">Update Category</button>
                    <?php else: ?>
                        <button type="submit" name="insert" class="btn btn-primary">Save Category</button>
                    <?php endif; ?>
                    <a href="index.php" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
