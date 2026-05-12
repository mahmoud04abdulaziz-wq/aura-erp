<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/db_connect.php';

// Map generated product images to specific products by category
$imageMap = [
    // Core products - unique generated images
    'FG-001' => 'images/product_marble_slab.png',      // Artificial Marble Slab 120x60
    'FG-002' => 'images/product_granite_tile.png',      // Artificial Granite Tile 60x60
    'FG-003' => 'images/product_decorative_panel.png',  // Decorative Stone Panel 100x50
    'FG-004' => 'images/product_countertop.png',        // Kitchen Countertop Slab 200x60
    'FG-005' => 'images/product_wall_cladding.png',     // Wall Cladding Tile 30x30
    'FG-006' => 'images/product_vanity_top.png',        // Bathroom Vanity Top
    
    // Crowns (تاج)
    'FG-C01' => 'images/product_cornice.png',           // Crown 50
    'FG-C02' => 'images/product_cornice.png',           // Crown 40
    
    // Bases (قاعده)
    'FG-C03' => 'images/product_column_base.png',       // Base 50
    'FG-C04' => 'images/product_column_base.png',       // Base 40
    
    // Columns (عامود)
    'FG-COL1' => 'images/product_column.png',           // Solid Column
    'FG-COL2' => 'images/product_column.png',           // Comb Column 50
    'FG-COL3' => 'images/product_column.png',           // Large Comb
    'FG-COL4' => 'images/product_column.png',           // Med Comb
    'FG-COL5' => 'images/product_column.png',           // Plain Column
    'FG-COL6' => 'images/product_column.png',           // Plain Col 40
    
    // Cornices (كرانيش)
    'FG-SC01' => 'images/product_cornice.png',          // Stair Cornice
    'FG-SC02' => 'images/product_cornice.png',          // Shell Cornice
    'FG-SC03' => 'images/product_cornice.png',          // Cornice 25
    'FG-SC04' => 'images/product_cornice.png',          // Cornice 20
    'FG-SC05' => 'images/product_cornice.png',          // Heart Cornice
    
    // Corners (زوايا)
    'FG-SCC1' => 'images/product_corner_piece.png',     // Corner 15
    'FG-SCC3' => 'images/product_corner_piece.png',     // Corner 25
    'FG-SCC4' => 'images/product_corner_piece.png',     // Corner 20
    
    // Ceramics (سراميك) - use ceramic tile or keep real photos
    'FG-ST01' => 'images/product_ceramic_tile.png',     // Patterned Tile
    'FG-ST02' => 'images/product_ceramic_tile.png',     // Sesame Tile
    'FG-ST03' => 'images/product_ceramic_tile.png',     // Sand Tile
    'FG-ST04' => 'images/product_ceramic_tile.png',     // Plain Tile
    'FG-ST05' => 'images/product_ceramic_tile.png',     // Tabiza Tile
    'FG-ST06' => 'images/product_ceramic_tile.png',     // Textured Tile
    'FG-ST07' => 'images/product_ceramic_tile.png',     // Frame Tile
    'FG-STT1' => 'images/product_ceramic_tile.png',     // Sorted Patterned
    'FG-STT2' => 'images/product_ceramic_tile.png',     // Sesame Sorted
    'FG-STT3' => 'images/product_ceramic_tile.png',     // Sand Sorted
];

$stmt = $pdo->prepare("UPDATE inventory_finished_goods SET image_path = ? WHERE item_id = ?");
$updated = 0;
foreach ($imageMap as $id => $img) {
    $stmt->execute([$img, $id]);
    if ($stmt->rowCount() > 0) $updated++;
    echo "$id => $img\n";
}
echo "\nDone! Updated $updated products.\n";
