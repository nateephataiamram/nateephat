<?php
// ==========================================
// 1. DATABASE CONNECTION (PDO + PREPARED STATEMENT)
// ==========================================
$host = 'localhost';
$db   = 'motorcycle_db';
$user = 'root';
$pass = '';

$db_connected = false;
$pdo = null;

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $user, $pass, $options);
    $db_connected = true;

    // สร้างตารางอัตโนมัติหากยังไม่มีใน SQL
    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sku VARCHAR(50) NOT NULL UNIQUE,
        name VARCHAR(255) NOT NULL,
        category VARCHAR(100) NOT NULL,
        compatible_model VARCHAR(255) DEFAULT NULL,
        price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
        stock INT NOT NULL DEFAULT 0,
        image_url TEXT DEFAULT NULL,
        description TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // ตรวจสอบข้อมูลเริ่มต้น
    $check_count = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    if ($check_count == 0) {
        $init_stmt = $pdo->prepare("INSERT INTO products (sku, name, category, compatible_model, price, stock, image_url, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        $init_data = [
            ['BRK-101', 'ปั๊มเบรกบนแต่ง Brembo Corsa Corta 19RCS CNC', 'ปั๊มเบรก', 'ทุกรุ่น (Universal)', 8500.00, 10, 'https://images.unsplash.com/photo-1609630875171-b1321377ee65?auto=format&fit=crop&w=600&q=80', 'ปั๊มลอยงาน CNC อลูมิเนียมเกรดพรีเมียม ไล่ลมง่าย ระยะเบรกกระชับ นุ่มมือ'],
            ['BRK-102', 'ปั๊มเบรกล่าง Brembo เม้าท์สี่พอร์ต (4 Pot) CNC', 'ปั๊มเบรก', 'Honda Wave / Kr150 / Forza', 5900.00, 8, 'https://images.unsplash.com/photo-1558981403-c5f9899a28bc?auto=format&fit=crop&w=600&q=80', 'คาลิปเปอร์เบรกแต่ง 4 พอร์ต งาน CNC สีอนอไดซ์ เพิ่มประสิทธิภาพการหยุดรถ'],
            ['EXH-201', 'ท่อไอเสียสแตนเลสทรงเชง คอท่อ 32 มม. ลายเชื่อม CNC', 'ท่อไอเสีย', 'Honda Wave 110i / Wave 125i', 2800.00, 12, 'https://images.unsplash.com/photo-1568772585407-9361f9bf3a87?auto=format&fit=crop&w=600&q=80', 'คอท่อสแตนเลส 304 แท้ งานดัด CNC ปลายท่อเสียงนุ่ม อัตราเร่งดีขึ้นชัดเจน'],
            ['EXH-202', 'ท่อแต่งไทเทเนียม Slip-On สายแข่งทรงกระป๋อง', 'ท่อไอเสีย', 'Yamaha YZF-R15 / MT-15', 4500.00, 6, 'https://images.unsplash.com/photo-1558981806-ec527fa84c39?auto=format&fit=crop&w=600&q=80', 'ปลายท่อวัสดุไทเทเนียมแท้น้ำหนักเบา ระบายไอเสียได้รวดเร็ว เสียงกระหึ่ม'],
            ['ENG-301', 'ชุดเสื้อสูบแต่งพร้อมลูกสูบฟอร์จ CNC ขนาด 53 มม.', 'เสื้อสูบ-ฝาสูบCNC', 'Honda Wave 110i / Dream Super Cub', 3200.00, 7, 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=600&q=80', 'เสื้อสูบลื่นพิเศษ ปลอกหนา รูน้ำมันเครื่องแกะสลักด้วยระบบ CNC ทนความร้อนสูง'],
            ['ENG-302', 'ฝาสูบแต่ง 4 วาล์ว CNC พร้อมแคมแต่งตรงรุ่น', 'เสื้อสูบ-ฝาสูบCNC', 'Honda Wave 125i / MSX 125', 4800.00, 5, 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?auto=format&fit=crop&w=600&q=80', 'ฝาสูบงานแยงไอดี-ไอเสียระบบ CNC ความแม่นยำสูง เพิ่มรอบเครื่องยนต์และแรงบิด'],
            ['NOT-401', 'ชุดน็อตแคร้งเครื่องเลส CNC สแตนเลสหัวเฟือง', 'น็อตเลส CNC', 'Honda Wave 110i / 125i', 450.00, 20, 'https://images.unsplash.com/photo-1609630875171-b1321377ee65?auto=format&fit=crop&w=600&q=80', 'ชุดน็อตเลสแท้ CNC เงาแวววาว ไม่เป็นสนิม แข็งแรงทนทาน']
        ];

        foreach ($init_data as $row) {
            $init_stmt->execute($row);
        }
    }

} catch (\PDOException $e) {
    $db_connected = false;
}

// ==========================================
// 2. API ENDPOINTS (AJAX / JSON HANDLING)
// ==========================================
if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    if (!$db_connected) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูล SQL ได้']);
        exit;
    }

    $action = $_GET['action'];

    if ($action === 'get_products') {
        $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        exit;
    }

    if ($action === 'save_product' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $id       = $data['id'] ?? null;
        $sku      = trim($data['sku']);
        $name     = trim($data['name']);
        $category = trim($data['category']);
        $model    = trim($data['compatible_model']);
        $price    = $data['price'];
        $stock    = $data['stock'];
        $image    = trim($data['image_url']);
        $desc     = trim($data['description']);

        try {
            if ($id) {
                $stmt = $pdo->prepare("UPDATE products SET sku=?, name=?, category=?, compatible_model=?, price=?, stock=?, image_url=?, description=? WHERE id=?");
                $stmt->execute([$sku, $name, $category, $model, $price, $stock, $image, $desc, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO products (sku, name, category, compatible_model, price, stock, image_url, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$sku, $name, $category, $model, $price, $stock, $image, $desc]);
            }
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'delete_product' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        try {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$data['id']]);
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KCBRU MotoParts - อะไหล่แต่งมอเตอร์ไซค์ </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Kanit', sans-serif; }
        .glass-panel {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(124, 252, 156, 0.08);
        }
        .nav-tab-active {
            background-color: #0d9488 !important;
            color: #ffffff !important;
            border-color: #14b8a6 !important;
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.3);
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col antialiased selection:bg-teal-500 selection:text-slate-900">

    <!-- HEADER / BANNER -->
    <header class="relative bg-slate-900 border-b border-slate-800 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-r from-teal-900/40 via-slate-900/90 to-amber-900/30 pointer-events-none"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 relative z-10 flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="flex items-center space-x-4">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-amber-500 to-teal-500 p-0.5 shadow-xl shadow-teal-500/10">
                    <div class="w-full h-full bg-slate-900 rounded-[14px] flex items-center justify-center">
                        <img src="images.jpg" class="w-full h-full object-cover rounded-[14px]" alt="Logo">
                    </div>
                </div>
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-wide flex items-center gap-2">
                        MOTOPARTS <span class="text-teal-400">Racing</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-400 mt-0.5">สินค้าอะไหล่แต่งมอเตอร์ไซค์ </p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="glass-panel px-3.5 py-2 rounded-xl flex items-center space-x-2 text-xs">
                    <span class="w-2.5 h-2.5 rounded-full <?php echo $db_connected ? 'bg-emerald-400 animate-pulse' : 'bg-amber-500'; ?>"></span>
                    <span class="text-slate-300"><?php echo $db_connected ? 'SQL Database: Connected' : 'Demo Mode (Local Storage)'; ?></span>
                </div>

                <div class="glass-panel px-3.5 py-2 rounded-xl flex items-center space-x-3">
                    <span id="admin-status-text" class="text-xs text-slate-300">สถานะ: ผู้ชมทั่วไป</span>
                    <button onclick="toggleAdminMode()" id="admin-toggle-btn" class="bg-teal-600 hover:bg-teal-500 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition-all shadow-md">
                        เข้าสู่ระบบ Admin
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- NAVIGATION BAR -->
    <nav class="bg-slate-900/90 sticky top-0 z-40 border-b border-slate-800 backdrop-blur-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex space-x-2 py-2.5 items-center">
                <button onclick="switchTab('home')" id="nav-home" class="px-5 py-2 rounded-lg text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-800 transition-all flex items-center space-x-2">
                    <i data-lucide="home" class="w-4 h-4"></i>
                    <span>หน้าแรก</span>
                </button>
                <button onclick="switchTab('products')" id="nav-products" class="px-5 py-2 rounded-lg text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-800 transition-all flex items-center space-x-2">
                    <i data-lucide="package" class="w-4 h-4"></i>
                    <span>รายการอะไหล่แต่ง</span>
                </button>
                
                <button onclick="switchTab('add-product')" id="nav-add-product" class="hidden px-5 py-2 rounded-lg text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-800 transition-all flex items-center space-x-2">
                    <i data-lucide="plus-circle" class="w-4 h-4 text-amber-400"></i>
                    <span class="text-amber-400">เพิ่มอะไหล่แต่ง (Admin)</span>
                </button>
            </div>
        </div>
    </nav>

    <!-- MAIN BODY -->
    <main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- PAGE 1: HOME PAGE -->
        <section id="page-home" class="space-y-8">
            <div class="relative rounded-3xl overflow-hidden border border-slate-800 shadow-2xl bg-slate-900 h-64 sm:h-80 flex items-center justify-center">
                <img src="ChatGPT Image 25 ก.ย. 2569 01_22_36.png" class="absolute inset-0 w-full h-full object-cover opacity-40 filter brightness-75">
                <div class="relative z-10 text-center max-w-2xl px-4">
                    <span class="bg-teal-500/20 text-teal-300 text-xs font-semibold px-3 py-1 rounded-full border border-teal-500/30">
                        ยินดีต้อนรับสู่ MotoRacing
                    </span>
                    <h2 class="text-2xl sm:text-4xl font-extrabold text-white mt-3">ศูนย์รวมอะไหล่แต่งมอเตอร์ไซค์ระดับพรีเมียม</h2>
                    <p class="text-sm text-slate-300 mt-2">ปั๊มเบรก CNC, ท่อไอเสียสแตนเลส, ชุดเสื้อสูบ-ฝาสูบ CNC และน็อตเลส CNC</p>
                    <button onclick="switchTab('products')" class="mt-5 bg-teal-500 hover:bg-teal-600 text-slate-950 font-bold px-6 py-2.5 rounded-xl text-sm transition-all shadow-lg shadow-teal-500/20">
                        เลือกชมอะไหล่แต่งทั้งหมด
                    </button>
                </div>
            </div>

            <div>
                <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                    <i data-lucide="grid" class="w-5 h-5 text-teal-400"></i> หมวดหมู่อะไหล่
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div onclick="filterByCategory('ปั๊มเบรก')" class="glass-panel p-5 rounded-2xl hover:border-teal-500/50 cursor-pointer transition-all text-center group">
                        <div class="w-14 h-14 bg-teal-500/10 text-teal-400 rounded-2xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                            <img src="ri.jpg" class="w-full h-full object-cover rounded-2xl scale-110 group-hover:scale-125 transition-transform duration-300" alt="ปั๊มเบรก">
                        </div>
                        <h4 class="text-base font-semibold text-white">ปั๊มเบรก</h4>
                        <p class="text-xs text-slate-400 mt-1">ปั๊มลอย Corsa Corta / คาลิปเปอร์ 4 Pot CNC</p>
                    </div>
                    <div onclick="filterByCategory('ท่อไอเสีย')" class="glass-panel p-5 rounded-2xl hover:border-teal-500/50 cursor-pointer transition-all text-center group">
                        <div class="w-14 h-14 bg-amber-500/10 text-amber-400 rounded-2xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                            <img src="To.png" class="w-full h-full object-cover rounded-2xl scale-110 group-hover:scale-125 transition-transform duration-300" alt="ท่อไอเสีย">
                        </div>
                        <h4 class="text-base font-semibold text-white">ท่อไอเสีย</h4>
                        <p class="text-xs text-slate-400 mt-1">คอท่อสแตนเลสทรงเชง / ปลายไทเทเนียม Slip-On</p>
                    </div>
                    <div onclick="filterByCategory('เสื้อสูบ-ฝาสูบCNC')" class="glass-panel p-5 rounded-2xl hover:border-teal-500/50 cursor-pointer transition-all text-center group">
                        <div class="w-14 h-14 bg-sky-500/10 text-sky-400 rounded-2xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                            <img src="เสื้อ.jpg" class="w-full h-full object-cover rounded-2xl scale-110 group-hover:scale-125 transition-transform duration-300" alt="เสื้อสูบ-ฝาสูบCNC">
                        </div>
                        <h4 class="text-base font-semibold text-white">เสื้อสูบ-ฝาสูบ CNC</h4>
                        <p class="text-xs text-slate-400 mt-1">ชุดเสื้อสูบลูกฟอร์จ 53mm / ฝาสูบ 4 วาล์ว CNC</p>
                    </div>
                    <div onclick="filterByCategory('น็อตเลส CNC')" class="glass-panel p-5 rounded-2xl hover:border-teal-500/50 cursor-pointer transition-all text-center group">
                        <div class="w-14 h-14 bg-purple-500/10 text-purple-400 rounded-2xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                            <img src="sg7.jpg" class="w-full h-full object-cover rounded-2xl scale-110 group-hover:scale-125 transition-transform duration-300" alt="น็อตเลสCNC">
                        </div>
                        <h4 class="text-base font-semibold text-white">น็อตเลส CNC</h4>
                        <p class="text-xs text-slate-400 mt-1">ชุดน็อตแคร้งเครื่อง / น็อตจานดิสก์ / น็อตยึดโช๊ค</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- PAGE 2: PRODUCTS CATALOG -->
        <section id="page-products" class="hidden space-y-6">
            <div class="glass-panel p-4 rounded-2xl flex flex-col md:flex-row gap-4 items-center justify-between">
                <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto flex-grow max-w-2xl">
                    <div class="relative flex-grow">
                        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 transform -translate-y-1/2"></i>
                        <input type="text" id="search-input" oninput="renderProducts()" placeholder="ค้นหาชื่ออะไหล่, รหัส SKU หรือรุ่นรถ..." class="w-full bg-slate-900 border border-slate-700 rounded-xl pl-9 pr-4 py-2 text-sm text-slate-200 focus:outline-none focus:border-teal-500">
                    </div>
                    <!-- ช่องเลือกตัวกรองหมวดหมู่ -->
                    <select id="category-filter" onchange="renderProducts()" class="bg-slate-900 border border-slate-700 text-slate-300 text-sm rounded-xl px-3 py-2 focus:outline-none focus:border-teal-500">
                        <option value="ALL">ทุกหมวดหมู่</option>
                        <option value="ปั๊มเบรก">ปั๊มเบรก</option>
                        <option value="ท่อไอเสีย">ท่อไอเสีย</option>
                        <option value="เสื้อสูบ-ฝาสูบCNC">เสื้อสูบ-ฝาสูบ CNC</option>
                        <option value="น็อตเลส CNC">น็อตเลส CNC</option>
                    </select>
                </div>
            </div>

            <div id="product-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6"></div>
        </section>

        <!-- PAGE 3: ADD/EDIT PRODUCT FORM (Admin Only) -->
        <section id="page-add-product" class="hidden max-w-2xl mx-auto">
            <div class="glass-panel p-6 sm:p-8 rounded-3xl space-y-6">
                <div class="border-b border-slate-800 pb-4">
                    <h2 id="form-header-title" class="text-xl font-bold text-white flex items-center gap-2">
                        <i data-lucide="plus-circle" class="w-6 h-6 text-amber-400"></i>
                        เพิ่มรายการอะไหล่แต่งใหม่ (Admin)
                    </h2>
                    <p class="text-xs text-slate-400 mt-1">กรอกข้อมูลรายละเอียดอะไหล่แต่งเพื่อบันทึกลงฐานข้อมูล SQL</p>
                </div>

                <form id="product-form" onsubmit="handleFormSubmit(event)" class="space-y-4">
                    <input type="hidden" id="form-id">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">รหัสสินค้า / SKU *</label>
                            <input type="text" id="form-sku" required placeholder="เช่น NOT-402" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-teal-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">หมวดหมู่อะไหล่ *</label>
                            <!-- ช่องเลือกหมวดหมู่ในฟอร์ม Admin -->
                            <select id="form-category" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-teal-500">
                                <option value="ปั๊มเบรก">ปั๊มเบรก</option>
                                <option value="ท่อไอเสีย">ท่อไอเสีย</option>
                                <option value="เสื้อสูบ-ฝาสูบCNC">เสื้อสูบ-ฝาสูบ CNC</option>
                                <option value="น็อตเลส CNC">น็อตเลส CNC</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">ชื่ออะไหล่แต่ง *</label>
                        <input type="text" id="form-name" required placeholder="เช่น ชุดน็อตแคร้งเครื่องเลส CNC" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-teal-500">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">รุ่นรถที่รองรับ</label>
                        <input type="text" id="form-model" placeholder="เช่น Honda Wave 110i / Wave 125i" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-teal-500">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">ราคาขาย (บาท) *</label>
                            <input type="number" id="form-price" step="0.01" min="0" required placeholder="0.00" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-teal-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">จำนวนคงเหลือในสต็อก *</label>
                            <input type="number" id="form-stock" min="0" required placeholder="0" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-teal-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">URL ลิงก์รูปภาพสินค้า *</label>
                        <input type="text" id="form-image" oninput="previewImageURL()" placeholder="https://example.com/image.jpg หรือชื่อไฟล์ในเครื่อง" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-teal-500">
                        <div class="mt-2 h-36 bg-slate-900/60 rounded-xl border border-slate-800 flex items-center justify-center overflow-hidden">
                            <img id="image-preview" src="" class="h-full object-contain hidden">
                            <span id="image-preview-text" class="text-xs text-slate-500">ตัวอย่างรูปภาพจะแสดงที่นี่</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">รายละเอียดสินค้า</label>
                        <textarea id="form-desc" rows="3" placeholder="ระบุสเปกสินค้า วัสดุ คุณสมบัติการใช้งาน..." class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-teal-500"></textarea>
                    </div>

                    <div class="pt-4 flex justify-end space-x-3">
                        <button type="button" onclick="switchTab('products')" class="px-5 py-2.5 text-sm text-slate-400 hover:text-white transition-colors">ยกเลิก</button>
                        <button type="submit" class="bg-teal-500 hover:bg-teal-600 text-slate-950 font-bold px-6 py-2.5 rounded-xl text-sm transition-all shadow-lg shadow-teal-500/20">
                            บันทึกข้อมูลลง SQL
                        </button>
                    </div>
                </form>
            </div>
        </section>

    </main>

    <!-- FOOTER -->
    <footer class="bg-slate-900 border-t border-slate-800 py-8 mt-auto">
        <div class="max-w-7xl mx-auto px-4 text-center space-y-2">
            <h4 class="text-sm font-bold text-slate-300">ข้อมูลผู้ทำเว็บไซต์</h4>
            <p class="text-xs text-slate-400">
                นายณะีพัฒน์ เอี่ยมรัมย์ 
            </p>
             <p class="text-xs text-slate-400">
                670112358007
            </p>
            <p class="text-xs text-slate-500 pt-2 border-t border-slate-800/60 max-w-md mx-auto">
                © 2026 KCBRU MotoParts Store Management | พัฒนาและออกแบบเพื่อการศึกษา
            </p>
        </div>
    </footer>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        let isAdmin = false;
        let isDBConnected = <?php echo $db_connected ? 'true' : 'false'; ?>;
        let products = [];

        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
            switchTab('home');
            loadProductsFromSQL();
        });

        async function loadProductsFromSQL() {
            if (!isDBConnected) {
                const local = localStorage.getItem('kcbru_products');
                if (local) {
                    products = JSON.parse(local);
                }
                renderProducts();
                return;
            }

            try {
                const res = await fetch('index.php?action=get_products');
                const result = await res.json();
                if (result.status === 'success') {
                    products = result.data.map(p => ({
                        ...p,
                        price: parseFloat(p.price),
                        stock: parseInt(p.stock)
                    }));
                }
            } catch (e) {
                console.log('Error loading SQL data');
            }
            renderProducts();
        }

        function switchTab(tabId) {
            ['home', 'products', 'add-product'].forEach(p => {
                const el = document.getElementById(`page-${p}`);
                const btn = document.getElementById(`nav-${p}`);
                if (el) el.classList.add('hidden');
                if (btn) btn.classList.remove('nav-tab-active');
            });

            document.getElementById(`page-${tabId}`)?.classList.remove('hidden');
            document.getElementById(`nav-${tabId}`)?.classList.add('nav-tab-active');
        }

        function toggleAdminMode() {
            isAdmin = !isAdmin;
            const btn = document.getElementById('admin-toggle-btn');
            const text = document.getElementById('admin-status-text');

            if (isAdmin) {
                text.innerText = 'สถานะ: ผู้ดูแลระบบ (Admin)';
                btn.innerText = 'ออกจากระบบ Admin';
                btn.className = 'bg-amber-600 hover:bg-amber-500 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition-all shadow-md';
                document.getElementById('nav-add-product').classList.remove('hidden');
            } else {
                text.innerText = 'สถานะ: ผู้ชมทั่วไป';
                btn.innerText = 'เข้าสู่ระบบ Admin';
                btn.className = 'bg-teal-600 hover:bg-teal-500 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition-all shadow-md';
                document.getElementById('nav-add-product').classList.add('hidden');
                switchTab('home');
            }
            renderProducts();
        }

        function filterByCategory(cat) {
            switchTab('products');
            document.getElementById('category-filter').value = cat;
            renderProducts();
        }

        function renderProducts() {
            const search = document.getElementById('search-input').value.toLowerCase().trim();
            const cat = document.getElementById('category-filter').value.trim();
            const grid = document.getElementById('product-grid');

            const filtered = products.filter(p => {
                const matchesSearch = p.name.toLowerCase().includes(search) || 
                                      p.sku.toLowerCase().includes(search) ||
                                      (p.compatible_model && p.compatible_model.toLowerCase().includes(search));
                
                // ระบบเปรียบเทียบชื่อหมวดหมู่อย่างแม่นยำ
                const matchesCat = (cat === 'ALL') || (p.category.trim() === cat);
                return matchesSearch && matchesCat;
            });

            if (filtered.length === 0) {
                grid.innerHTML = `<div class="col-span-full text-center py-12 glass-panel rounded-2xl text-slate-400">ไม่พบรายการอะไหล่แต่งในหมวดหมู่นี้</div>`;
                return;
            }

            grid.innerHTML = filtered.map(p => `
                <div class="glass-panel rounded-2xl overflow-hidden shadow-xl flex flex-col justify-between hover:border-slate-700 transition-all">
                    <div>
                        <div class="relative h-48 bg-slate-900 overflow-hidden">
                            <img src="${p.image_url}" alt="${p.name}" class="w-full h-full object-cover" onerror="this.src='https://images.unsplash.com/photo-1558981403-c5f9899a28bc?auto=format&fit=crop&w=600&q=80'">
                            <span class="absolute top-3 left-3 bg-slate-900/80 border border-slate-700 text-teal-400 text-xs px-2 py-0.5 rounded font-mono">SKU: ${p.sku}</span>
                            <span class="absolute top-3 right-3 bg-amber-500 text-slate-950 font-bold text-xs px-2.5 py-0.5 rounded-full">${p.category}</span>
                        </div>
                        <div class="p-5 space-y-2">
                            <h3 class="font-bold text-base text-white line-clamp-1">${p.name}</h3>
                            <p class="text-xs text-slate-400">รุ่นที่รองรับ: ${p.compatible_model || 'ทุกรุ่น'}</p>
                            <p class="text-xs text-slate-400 line-clamp-2">${p.description || ''}</p>
                            <div class="flex items-center justify-between pt-3 border-t border-slate-800">
                                <div>
                                    <span class="text-[10px] text-slate-500 block">ราคา</span>
                                    <span class="text-base font-bold text-amber-400">฿${p.price.toLocaleString('th-TH', {minimumFractionDigits: 2})}</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-[10px] text-slate-500 block">สต็อก SQL</span>
                                    <span class="text-xs font-semibold ${p.stock > 0 ? 'text-emerald-400' : 'text-red-400'}">${p.stock} ชิ้น</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    ${isAdmin ? `
                    <div class="p-4 bg-slate-900/80 border-t border-slate-800 flex items-center justify-end space-x-2">
                        <button onclick="editProduct(${p.id})" class="p-1.5 text-slate-400 hover:text-amber-400 transition-colors" title="แก้ไข"><i data-lucide="edit-3" class="w-4 h-4"></i></button>
                        <button onclick="deleteProduct(${p.id})" class="p-1.5 text-slate-400 hover:text-red-400 transition-colors" title="ลบ"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                    </div>` : ''}
                </div>
            `).join('');

            lucide.createIcons();
        }

        function previewImageURL() {
            const url = document.getElementById('form-image').value;
            const img = document.getElementById('image-preview');
            const txt = document.getElementById('image-preview-text');
            if (url) {
                img.src = url;
                img.classList.remove('hidden');
                txt.classList.add('hidden');
            } else {
                img.classList.add('hidden');
                txt.classList.remove('hidden');
            }
        }

        async function handleFormSubmit(e) {
            e.preventDefault();
            const productData = {
                id: document.getElementById('form-id').value || null,
                sku: document.getElementById('form-sku').value,
                category: document.getElementById('form-category').value,
                name: document.getElementById('form-name').value,
                compatible_model: document.getElementById('form-model').value,
                price: parseFloat(document.getElementById('form-price').value),
                stock: parseInt(document.getElementById('form-stock').value),
                image_url: document.getElementById('form-image').value || 'https://images.unsplash.com/photo-1558981403-c5f9899a28bc?auto=format&fit=crop&w=600&q=80',
                description: document.getElementById('form-desc').value
            };

            if (isDBConnected) {
                const res = await fetch('index.php?action=save_product', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(productData)
                });
                const result = await res.json();
                if (result.status === 'success') {
                    alert('บันทึกข้อมูลลง SQL เรียบร้อยแล้ว!');
                    loadProductsFromSQL();
                } else {
                    alert('เกิดข้อผิดพลาด: ' + result.message);
                }
            } else {
                if (productData.id) {
                    const idx = products.findIndex(p => p.id == productData.id);
                    if (idx !== -1) products[idx] = {...productData, id: parseInt(productData.id)};
                } else {
                    productData.id = Date.now();
                    products.push(productData);
                }
                localStorage.setItem('kcbru_products', JSON.stringify(products));
                alert('บันทึกข้อมูลจำลองเรียบร้อย!');
                renderProducts();
            }

            document.getElementById('product-form').reset();
            document.getElementById('form-id').value = '';
            document.getElementById('form-header-title').innerHTML = `<i data-lucide="plus-circle" class="w-6 h-6 text-amber-400"></i> เพิ่มรายการอะไหล่แต่งใหม่ (Admin)`;
            previewImageURL();
            switchTab('products');
        }

        function editProduct(id) {
            const p = products.find(prod => prod.id == id);
            if (!p) return;

            switchTab('add-product');
            document.getElementById('form-header-title').innerHTML = `<i data-lucide="edit-3" class="w-6 h-6 text-amber-400"></i> แก้ไขข้อมูลอะไหล่แต่ง (Admin)`;
            document.getElementById('form-id').value = p.id;
            document.getElementById('form-sku').value = p.sku;
            document.getElementById('form-category').value = p.category;
            document.getElementById('form-name').value = p.name;
            document.getElementById('form-model').value = p.compatible_model;
            document.getElementById('form-price').value = p.price;
            document.getElementById('form-stock').value = p.stock;
            document.getElementById('form-image').value = p.image_url;
            document.getElementById('form-desc').value = p.description;
            previewImageURL();
        }

        async function deleteProduct(id) {
            if (!confirm('คุณแน่ใจหรือไม่ว่าต้องการลบสินค้าชิ้นนี้ออกจาก SQL?')) return;

            if (isDBConnected) {
                const res = await fetch('index.php?action=delete_product', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({id: id})
                });
                const result = await res.json();
                if (result.status === 'success') {
                    alert('ลบสินค้าออกจาก SQL เรียบร้อยแล้ว');
                    loadProductsFromSQL();
                } else {
                    alert('เกิดข้อผิดพลาดในการลบ: ' + result.message);
                }
            } else {
                products = products.filter(p => p.id != id);
                localStorage.setItem('kcbru_products', JSON.stringify(products));
                renderProducts();
            }
        }
    </script>
</body>
</html>