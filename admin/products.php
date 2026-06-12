<?php
require_once __DIR__ . '/../db.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$successMsg = '';
$errorMsg = '';

// Load all categories for select dropdown
$categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

// Load Product for Edit
$product = null;
if (($action === 'edit' || isset($_POST['update'])) && $id > 0) {
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    if (!$product) {
        header("Location: index.php?msg=not_found");
        exit;
    }
}

// Handle Add Product Form submission
if (isset($_POST['insert'])) {
    $name = trim($_POST['name']);
    $category_id = (int)$_POST['category_id'];
    $description = trim($_POST['description']);
    $rate = trim($_POST['rate']);
    
    if (empty($name)) {
        $errorMsg = "Product name cannot be empty.";
    } elseif ($category_id <= 0) {
        $errorMsg = "Please select a valid category.";
    } elseif (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = "Please upload a valid product image.";
    } else {
        // Sanitize and rename image file
        $sanitizedName = sanitize_filename($name);
        $fileExt = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $newFileName = $sanitizedName . '.' . strtolower($fileExt);
        $targetPath = 'uploads/products/' . $newFileName;
        
        // Move file
        if (move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../' . $targetPath)) {
            // Save in DB
            $stmt = $db->prepare("INSERT INTO products (category_id, name, image, description, rate) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$category_id, $name, $targetPath, $description, $rate])) {
                header("Location: index.php?msg=prod_added");
                exit;
            } else {
                $errorMsg = "Failed to insert product into database.";
            }
        } else {
            $errorMsg = "Failed to move uploaded image to target folder.";
        }
    }
}

// Handle Edit Product Form submission
if (isset($_POST['update']) && $id > 0) {
    $name = trim($_POST['name']);
    $category_id = (int)$_POST['category_id'];
    $description = trim($_POST['description']);
    $rate = trim($_POST['rate']);
    
    if (empty($name)) {
        $errorMsg = "Product name cannot be empty.";
    } elseif ($category_id <= 0) {
        $errorMsg = "Please select a valid category.";
    } else {
        $targetPath = $product['image']; // Default to old image path
        
        // Check if a new image was uploaded
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            // Delete old file first
            if (file_exists(__DIR__ . '/../' . $product['image'])) {
                @unlink(__DIR__ . '/../' . $product['image']);
            }
            
            // Sanitize and rename new file
            $sanitizedName = sanitize_filename($name);
            $fileExt = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $newFileName = $sanitizedName . '.' . strtolower($fileExt);
            $targetPath = 'uploads/products/' . $newFileName;
            
            move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../' . $targetPath);
        } else {
            // If the name changed, rename the existing file to match the new name
            $oldSanitized = sanitize_filename($product['name']);
            $newSanitized = sanitize_filename($name);
            
            if ($oldSanitized !== $newSanitized && file_exists(__DIR__ . '/../' . $product['image'])) {
                $fileExt = pathinfo($product['image'], PATHINFO_EXTENSION);
                $newFileName = $newSanitized . '.' . strtolower($fileExt);
                $newPath = 'uploads/products/' . $newFileName;
                
                if (rename(__DIR__ . '/../' . $product['image'], __DIR__ . '/../' . $newPath)) {
                    $targetPath = $newPath;
                }
            }
        }
        
        // Update in DB
        $stmt = $db->prepare("UPDATE products SET category_id = ?, name = ?, image = ?, description = ?, rate = ? WHERE id = ?");
        if ($stmt->execute([$category_id, $name, $targetPath, $description, $rate, $id])) {
            header("Location: index.php?msg=prod_updated");
            exit;
        } else {
            $errorMsg = "Failed to update product in database.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salvin India - Manage Product</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <header class="admin-header">
        <a href="index.php" class="logo-link">
            <h1>SALVIN INDIA <span style="font-weight: 300; font-size: 0.95rem; opacity: 0.8;">| Admin</span></h1>
        </a>
        <nav class="admin-nav">
            <a href="index.php">Dashboard</a>
            <a href="categories.php">Categories</a>
            <a href="products.php" class="active">Products</a>
            <a href="../products.php" target="_blank">View Site &rarr;</a>
        </nav>
    </header>

    <div class="admin-container">
        <div class="page-header">
            <h2 class="page-title"><?= ($action === 'edit') ? 'Edit Product' : 'Add New Product' ?></h2>
            <a href="index.php" class="btn btn-outline btn-sm">&larr; Back to List</a>
        </div>

        <?php if (!empty($errorMsg)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>

        <div class="form-card">
            <form action="products.php?id=<?= $id ?>" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name" class="form-label">Product Name</label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Turmeric Powder" value="<?= $product ? htmlspecialchars($product['name']) : '' ?>" required>
                </div>

                <div class="form-group">
                    <label for="category_id" class="form-label">Category</label>
                    <select name="category_id" id="category_id" class="form-control" required>
                        <option value="">Select a Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($product && $product['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="rate" class="form-label">Rate / Packaging Weight</label>
                    <input type="text" name="rate" id="rate" class="form-control" placeholder="e.g. 500g Pouch, 340g Jar" value="<?= $product ? htmlspecialchars($product['rate']) : '' ?>">
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">Product Description</label>
                    <textarea name="description" id="description" class="form-control" placeholder="Brief description of the product..."><?= $product ? htmlspecialchars($product['description']) : '' ?></textarea>
                </div>

                <div class="form-group">
                    <label for="image" class="form-label">Product Package Image (SVG/PNG/JPG)</label>
                    <input type="file" name="image" id="image" class="form-control" accept="image/*" <?= ($action !== 'edit') ? 'required' : '' ?>>
                    <p style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.4rem;">
                        Note: The uploaded image will be automatically renamed to match the product name format (e.g. <code>turmeric_powder.png</code>).
                    </p>
                    
                    <?php if ($product): ?>
                        <div class="current-image-preview">
                            <div>
                                <span class="form-label" style="font-size: 0.8rem; margin-bottom: 0.25rem;">Current Image:</span>
                                <img src="../<?= htmlspecialchars($product['image']) ?>" alt="Current Product Image" style="object-fit: contain; background: #f8fafc; padding: 5px;">
                            </div>
                            <span style="font-family: monospace; font-size: 0.78rem; color: var(--text-muted);"><?= htmlspecialchars($product['image']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <?php if ($action === 'edit'): ?>
                        <button type="submit" name="update" class="btn btn-primary">Update Product</button>
                    <?php else: ?>
                        <button type="submit" name="insert" class="btn btn-primary">Save Product</button>
                    <?php endif; ?>
                    <a href="index.php" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
