<?php
require_once __DIR__ . '/../db.php';

// Fetch Statistics
$catCount = $db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$prodCount = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();

// Fetch Categories
$categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

// Fetch Products
$products = $db->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.name ASC")->fetchAll();

// Handle deletes directly from dashboard for convenience
$successMsg = '';
$errorMsg = '';

if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    if (isset($_GET['type']) && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        if ($_GET['type'] === 'category') {
            // Delete category image first
            $stmt = $db->prepare("SELECT image FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            $imgPath = $stmt->fetchColumn();
            if ($imgPath && file_exists(__DIR__ . '/../' . $imgPath)) {
                @unlink(__DIR__ . '/../' . $imgPath);
            }
            
            // Delete record (cascade deletes products due to InnoDB constraint)
            $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
            if ($stmt->execute([$id])) {
                header("Location: index.php?msg=cat_deleted");
                exit;
            }
        } elseif ($_GET['type'] === 'product') {
            // Delete product image first
            $stmt = $db->prepare("SELECT image FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $imgPath = $stmt->fetchColumn();
            if ($imgPath && file_exists(__DIR__ . '/../' . $imgPath)) {
                @unlink(__DIR__ . '/../' . $imgPath);
            }
            
            // Delete record
            $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
            if ($stmt->execute([$id])) {
                header("Location: index.php?msg=prod_deleted");
                exit;
            }
        }
    }
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'cat_deleted') {
        $successMsg = "Category deleted successfully!";
    } elseif ($_GET['msg'] === 'prod_deleted') {
        $successMsg = "Product deleted successfully!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nera Foods - Admin Panel</title>
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
            <a href="index.php" class="active">Dashboard</a>
            <a href="categories.php">Categories</a>
            <a href="products.php">Products</a>
            <a href="../products.php" target="_blank">View Site &rarr;</a>
        </nav>
    </header>

    <div class="admin-container">
        <div class="page-header">
            <h2 class="page-title">Dashboard Overview</h2>
        </div>

        <?php if (!empty($successMsg)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div>
        <?php endif; ?>
        <?php if (!empty($errorMsg)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>

        <!-- Dashboard Stats Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
            <div class="table-card" style="padding: 1.5rem; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <h4 style="color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase;">Total Categories</h4>
                    <p style="font-size: 2.2rem; font-weight: 700; color: var(--primary); margin-top: 0.25rem;"><?= $catCount ?></p>
                </div>
                <a href="categories.php" class="btn btn-outline btn-sm">Manage</a>
            </div>
            <div class="table-card" style="padding: 1.5rem; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <h4 style="color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase;">Total Products</h4>
                    <p style="font-size: 2.2rem; font-weight: 700; color: var(--primary); margin-top: 0.25rem;"><?= $prodCount ?></p>
                </div>
                <a href="products.php" class="btn btn-outline btn-sm">Manage</a>
            </div>
        </div>

        <!-- Categories Table Block -->
        <div style="margin-bottom: 3rem;">
            <div class="page-header" style="margin-bottom: 1rem;">
                <h3 class="page-title" style="font-size: 1.4rem;">Product Categories</h3>
                <a href="categories.php?action=add" class="btn btn-primary btn-sm">+ Add Category</a>
            </div>
            <div class="table-card">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Image Path</th>
                            <th style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 2rem;">No categories found. Click Add Category to create one!</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $cat): ?>
                                <tr>
                                     <td>
                                         <img src="../<?= htmlspecialchars($cat['image']) ?>?t=<?= time() ?>" alt="<?= htmlspecialchars($cat['name']) ?>" class="td-thumbnail">
                                     </td>
                                    <td style="font-weight: 600; color: var(--primary);"><?= htmlspecialchars($cat['name']) ?></td>
                                    <td style="font-family: monospace; font-size: 0.8rem; color: var(--text-muted);"><?= htmlspecialchars($cat['image']) ?></td>
                                    <td>
                                        <div class="actions-cell">
                                            <a href="categories.php?action=edit&id=<?= $cat['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
                                            <a href="index.php?action=delete&type=category&id=<?= $cat['id'] ?>" onclick="return confirm('Are you sure you want to delete this category? All products in this category will be deleted too.');" class="btn btn-danger btn-sm">Delete</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Products Table Block -->
        <div>
            <div class="page-header" style="margin-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
                <h3 class="page-title" style="font-size: 1.4rem;">Product List</h3>
                <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                    <input type="text" id="productSearch" class="form-control" placeholder="Search products..." style="width: 240px; padding: 0.4rem 0.75rem; font-size: 0.85rem; height: 36px;">
                    <select id="categoryFilter" class="form-control" style="width: 180px; padding: 0.4rem 0.75rem; font-size: 0.85rem; height: 36px; cursor: pointer;">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['name']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <a href="products.php?action=add" class="btn btn-primary btn-sm" style="height: 36px; padding: 0 1rem; font-size: 0.85rem;">+ Add Product</a>
                </div>
            </div>
            <div class="table-card">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Rate/Weight</th>
                            <th style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="productTableBody">
                        <?php if (empty($products)): ?>
                            <tr class="no-products-row">
                                <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">No products found. Click Add Product to create one!</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $prod): ?>
                                <tr>
                                     <td>
                                         <img src="../<?= htmlspecialchars($prod['image']) ?>?t=<?= time() ?>" alt="<?= htmlspecialchars($prod['name']) ?>" class="td-thumbnail" style="object-fit: contain; background: #f8fafc; padding: 2px;">
                                     </td>
                                    <td class="product-name" style="font-weight: 600; color: var(--primary);"><?= htmlspecialchars($prod['name']) ?></td>
                                    <td class="product-category"><span class="badge badge-category"><?= htmlspecialchars($prod['category_name']) ?></span></td>
                                    <td class="product-description" style="color: var(--text-muted); max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($prod['description']) ?></td>
                                    <td style="font-weight: 500;"><?= htmlspecialchars($prod['rate']) ?></td>
                                    <td>
                                        <div class="actions-cell">
                                            <a href="products.php?action=edit&id=<?= $prod['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
                                            <a href="index.php?action=delete&type=product&id=<?= $prod['id'] ?>" onclick="return confirm('Are you sure you want to delete this product?');" class="btn btn-danger btn-sm">Delete</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Client-side product filtering script -->
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const productSearch = document.getElementById('productSearch');
        const categoryFilter = document.getElementById('categoryFilter');
        const productTableBody = document.getElementById('productTableBody');
        const productRows = productTableBody.querySelectorAll('tr');

        // Create and append a "no matching products" fallback row
        const noMatchRow = document.createElement('tr');
        noMatchRow.id = 'no-match-row';
        noMatchRow.style.display = 'none';
        noMatchRow.innerHTML = '<td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">No matching products found.</td>';
        productTableBody.appendChild(noMatchRow);

        function filterProducts() {
            const searchVal = productSearch.value.toLowerCase().trim();
            const categoryVal = categoryFilter.value.toLowerCase().trim();
            let visibleCount = 0;

            productRows.forEach(row => {
                // Skip the custom fallback rows
                if (row.classList.contains('no-products-row') || row.id === 'no-match-row') return;

                const nameEl = row.querySelector('.product-name');
                const categoryEl = row.querySelector('.product-category');
                const descEl = row.querySelector('.product-description');

                const name = nameEl ? nameEl.textContent.toLowerCase() : '';
                const category = categoryEl ? categoryEl.textContent.toLowerCase() : '';
                const description = descEl ? descEl.textContent.toLowerCase() : '';

                const matchesSearch = name.includes(searchVal) || description.includes(searchVal);
                const matchesCategory = categoryVal === '' || category.includes(categoryVal);

                if (matchesSearch && matchesCategory) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Handle fallback display logic
            const hasNoProductsAtAll = productTableBody.querySelector('.no-products-row');
            if (!hasNoProductsAtAll) {
                if (visibleCount === 0) {
                    noMatchRow.style.display = '';
                } else {
                    noMatchRow.style.display = 'none';
                }
            }
        }

        if (productSearch && categoryFilter) {
            productSearch.addEventListener('input', filterProducts);
            categoryFilter.addEventListener('change', filterProducts);
        }
    });
    </script>
</body>
</html>
