<?php
/**
 * PasarKita - Pengujian end-to-end otomatis (HTTP + verifikasi database).
 *
 * Cara pakai (CLI):
 *   php tests/run_tests.php http://localhost/PasarKita
 *
 * Skrip ini membuat data uji sendiri (produk & akun uji) lalu menghapusnya
 * kembali di akhir, sehingga data dummy bawaan tidak berubah.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$base = rtrim($argv[1] ?? 'http://localhost/PasarKita', '/');
$config = require __DIR__ . '/../config/config.php';
date_default_timezone_set($config['app']['timezone'] ?? 'Asia/Jakarta');
$tmpDir = sys_get_temp_dir() . '/pasarkita_test_' . bin2hex(random_bytes(4));
mkdir($tmpDir);

/* ------------------------------------------------------------------ */
/* Utilitas                                                            */
/* ------------------------------------------------------------------ */
$results = [];
$section = '';
function section(string $name): void
{
    global $section;
    $section = $name;
    echo "\n== $name ==\n";
}
function check(string $label, bool $ok, string $info = ''): void
{
    global $results, $section;
    $results[] = [$section, $label, $ok];
    echo ($ok ? '  [PASS] ' : '  [FAIL] ') . $label . ($info !== '' ? "  ($info)" : '') . "\n";
}

class Client
{
    public $jar;
    public $base;
    public $last = ['code' => 0, 'body' => '', 'location' => ''];

    public function __construct(string $base, string $jar)
    {
        $this->base = $base;
        $this->jar = $jar;
    }

    public function request(string $method, string $path, array $data = [], bool $multipart = false): array
    {
        $ch = curl_init($this->base . '/' . ltrim($path, '/'));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_COOKIEJAR => $this->jar,
            CURLOPT_COOKIEFILE => $this->jar,
            CURLOPT_TIMEOUT => 30,
        ]);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $multipart ? $data : http_build_query($data));
        }
        $raw = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($raw, 0, $headerSize);
        $location = preg_match('/^Location:\s*(.+)$/mi', $headers, $m) ? trim($m[1]) : '';
        $this->last = ['code' => $code, 'body' => substr($raw, $headerSize), 'location' => $location];
        return $this->last;
    }

    public function get(string $path): array
    {
        return $this->request('GET', $path);
    }

    /** POST dengan token CSRF yang diambil dari halaman $formPage. */
    public function post(string $path, array $data, ?string $formPage = null, bool $multipart = false): array
    {
        $data['csrf_token'] = $this->token($formPage ?? 'index.php');
        return $this->request('POST', $path, $data, $multipart);
    }

    public function token(string $page): string
    {
        $r = $this->get($page);
        return preg_match('/name="csrf_token" value="([a-f0-9]{64})"/', $r['body'], $m) ? $m[1] : '';
    }

    public function sessionId(): string
    {
        if (!is_file($this->jar)) {
            return '';
        }
        foreach (file($this->jar) as $line) {
            $parts = explode("\t", trim($line));
            if (count($parts) === 7 && $parts[5] === 'PASARKITASESSID') {
                return $parts[6];
            }
        }
        return '';
    }
}

/* ------------------------------------------------------------------ */
/* 1. Koneksi & struktur database                                      */
/* ------------------------------------------------------------------ */
section('1. Koneksi database & struktur');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $db = new mysqli($config['db']['host'], $config['db']['username'], $config['db']['password'], $config['db']['database'], (int) $config['db']['port']);
    $db->set_charset('utf8mb4');
    check('Koneksi MySQLi ke database "' . $config['db']['database'] . '"', true);
} catch (Throwable $e) {
    check('Koneksi MySQLi', false, $e->getMessage());
    exit(1);
}
function q1(string $sql, string $types = '', array $params = [])
{
    global $db;
    $st = $db->prepare($sql);
    if ($types) {
        $st->bind_param($types, ...$params);
    }
    $st->execute();
    $res = $st->get_result();
    return $res ? $res->fetch_assoc() : null;
}
function qv(string $sql, string $types = '', array $params = [])
{
    $r = q1($sql, $types, $params);
    return $r ? reset($r) : null;
}

$tables = [];
foreach ($db->query('SHOW TABLES')->fetch_all() as $t) {
    $tables[] = $t[0];
}
foreach (['users', 'categories', 'products', 'orders', 'order_details'] as $t) {
    check("Tabel $t tersedia", in_array($t, $tables, true));
}
$fkCount = (int) qv("SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE()");
check('Foreign key terpasang (4 relasi)', $fkCount === 4, "ditemukan $fkCount");
try {
    $db->query('UPDATE products SET stock = stock - 1000000 WHERE id = (SELECT id FROM (SELECT MIN(id) id FROM products) x)');
    check('Database menolak stok negatif', false);
} catch (mysqli_sql_exception $e) {
    check('Database menolak stok negatif', true, 'error MySQL ' . $e->getCode());
}
try {
    $db->query("INSERT INTO products (category_id, name, slug, description, price, stock) VALUES (99999, 'x', 'fk-test-x', 'x', 1, 1)");
    check('Foreign key menolak kategori yang tidak ada', false);
    $db->query("DELETE FROM products WHERE slug = 'fk-test-x'");
} catch (mysqli_sql_exception $e) {
    check('Foreign key menolak kategori yang tidak ada', true, 'error MySQL ' . $e->getCode());
}
try {
    $db->query('DELETE FROM categories WHERE id = (SELECT category_id FROM products LIMIT 1)');
    check('Kategori yang masih dipakai produk tidak bisa dihapus (RESTRICT)', false);
} catch (mysqli_sql_exception $e) {
    check('Kategori yang masih dipakai produk tidak bisa dihapus (RESTRICT)', true);
}

/* ------------------------------------------------------------------ */
/* 2. Halaman publik                                                   */
/* ------------------------------------------------------------------ */
section('2. Halaman publik, daftar produk, pencarian, filter, detail');
$guest = new Client($base, "$tmpDir/guest.txt");
$r = $guest->get('index.php');
check('Homepage dapat dibuka (200)', $r['code'] === 200 && strpos($r['body'], 'Produk Terbaru') !== false);
$r = $guest->get('products/index.php');
$activeCount = (int) qv('SELECT COUNT(*) FROM products WHERE is_active = 1');
check('Daftar produk tampil dengan jumlah benar', $r['code'] === 200 && strpos($r['body'], "dari <strong>$activeCount</strong> produk") !== false, "$activeCount produk aktif");
$r = $guest->get('products/index.php?q=kopi');
check('Pencarian "kopi" menemukan Kopi Gayo', strpos($r['body'], 'Kopi Gayo Arabika') !== false);
check('Pencarian "kopi" tidak menampilkan produk lain (Batik)', strpos($r['body'], 'Batik Tulis Motif Kawung') === false);
$r = $guest->get('products/index.php?q=' . urlencode("' OR 1=1 -- "));
check('Pencarian dengan input injeksi SQL aman (0 hasil, tanpa error)', $r['code'] === 200 && strpos($r['body'], 'Produk tidak ditemukan') !== false);
$r = $guest->get('products/index.php?kategori=jamu-herbal');
check('Filter kategori Jamu & Herbal hanya menampilkan produk jamu', strpos($r['body'], 'Jamu Kunyit Asam') !== false && strpos($r['body'], 'Rendang') === false);
$r = $guest->get('products/index.php?urut=termurah');
preg_match_all('/<div class="price fs-6">Rp ([\d.]+)<\/div>/', $r['body'], $m);
$prices = array_map(fn ($p) => (int) str_replace('.', '', $p), $m[1]);
$sorted = $prices;
sort($sorted);
check('Urutan harga termurah berfungsi', $r['code'] === 200 && count($prices) > 1 && $prices === $sorted, 'harga pertama ' . ($prices[0] ?? '-'));
$r = $guest->get('products/detail.php?slug=kopi-gayo-arabika-250-g');
check('Detail produk tampil (nama, harga, stok)', $r['code'] === 200 && strpos($r['body'], 'Rp 95.000') !== false && strpos($r['body'], 'Stok tersedia') !== false);
$r = $guest->get('products/detail.php?slug=tidak-ada-produk');
check('Slug produk tidak dikenal menghasilkan 404', $r['code'] === 404);
$r = $guest->get('products/index.php?q=' . urlencode('<script>alert(1)</script>'));
check('Output pencarian di-escape (anti XSS)', strpos($r['body'], '<script>alert(1)</script>') === false && strpos($r['body'], '&lt;script&gt;') !== false);

/* ------------------------------------------------------------------ */
/* 3. Akses berdasarkan role (tamu)                                    */
/* ------------------------------------------------------------------ */
section('3. Proteksi halaman untuk tamu');
foreach (['admin/index.php', 'admin/products/index.php', 'admin/orders/index.php', 'checkout/index.php', 'account/orders.php'] as $p) {
    $r = $guest->get($p);
    check("Tamu diarahkan ke login saat membuka $p", $r['code'] === 302 && strpos($r['location'], 'auth/login.php') !== false);
}
$r = $guest->request('POST', 'cart/add.php', ['product_id' => 1, 'quantity' => 1]);
check('POST tanpa token CSRF ditolak (403)', $r['code'] === 403);
$r = $guest->request('POST', 'cart/add.php', ['product_id' => 1, 'quantity' => 1, 'csrf_token' => str_repeat('a', 64)]);
check('POST dengan token CSRF palsu ditolak (403)', $r['code'] === 403);

/* ------------------------------------------------------------------ */
/* 4. Login admin & CRUD produk                                        */
/* ------------------------------------------------------------------ */
section('4. Login admin & CRUD produk (termasuk upload gambar)');
$admin = new Client($base, "$tmpDir/admin.txt");
$r = $admin->post('auth/login.php', ['email' => 'admin@pasarkita.test', 'password' => 'salahsalah1'], 'auth/login.php');
check('Login dengan password salah ditolak', $r['code'] === 200 && strpos($r['body'], 'Email atau password salah') !== false);
$admin->get('auth/login.php');
$sidBefore = $admin->sessionId();
$r = $admin->post('auth/login.php', ['email' => 'admin@pasarkita.test', 'password' => 'admin123'], 'auth/login.php');
check('Login admin berhasil & diarahkan ke dashboard', $r['code'] === 302 && strpos($r['location'], 'admin/index.php') !== false);
check('Session ID diregenerasi setelah login', $sidBefore !== '' && $admin->sessionId() !== '' && $sidBefore !== $admin->sessionId());
$r = $admin->get('admin/index.php');
$totalProducts = (int) qv('SELECT COUNT(*) FROM products');
$totalOrders = (int) qv('SELECT COUNT(*) FROM orders');
check('Dashboard admin menampilkan statistik jumlah produk', $r['code'] === 200 && preg_match('/data-stat="total-produk">' . $totalProducts . '</', $r['body']) === 1, "$totalProducts produk");
check('Dashboard admin menampilkan statistik jumlah pesanan', preg_match('/data-stat="total-pesanan">' . $totalOrders . '</', $r['body']) === 1, "$totalOrders pesanan");
$r = $admin->get('admin/orders/index.php');
check('Daftar transaksi admin dapat dibuka', $r['code'] === 200 && strpos($r['body'], 'PK261110A1B2C3') !== false);

// Buat gambar PNG uji
$png = "$tmpDir/uji.png";
$img = imagecreatetruecolor(400, 300);
imagefill($img, 0, 0, imagecolorallocate($img, 180, 83, 42));
imagepng($img, $png);
unset($img);
$fake = "$tmpDir/shell.jpg";
file_put_contents($fake, '<?php echo "hack"; ?>');

$catId = (int) qv("SELECT id FROM categories WHERE slug = 'kerajinan-tangan'");
$baseProduct = ['category_id' => $catId, 'unit' => 'pcs', 'origin' => 'Kota Uji', 'seller_name' => 'UMKM Uji', 'description' => 'Produk ini dibuat otomatis oleh skrip pengujian.', 'is_active' => '1'];

$r = $admin->post('admin/products/create.php', $baseProduct + ['name' => 'Produk Uji Invalid', 'price' => 'abc', 'stock' => '-5'], 'admin/products/create.php');
check('Validasi menolak harga bukan angka & stok negatif', $r['code'] === 200 && strpos($r['body'], 'Harga harus berupa angka') !== false && strpos($r['body'], 'tidak boleh negatif') !== false);
check('Produk invalid tidak tersimpan', (int) qv("SELECT COUNT(*) FROM products WHERE name = 'Produk Uji Invalid'") === 0);

$r = $admin->post('admin/products/create.php', $baseProduct + ['name' => 'Produk Uji Shell', 'price' => '10000', 'stock' => '1', 'image' => new CURLFile($fake, 'image/jpeg', 'shell.jpg')], 'admin/products/create.php', true);
check('Upload file PHP yang menyamar sebagai .jpg ditolak', strpos($r['body'], 'Format gambar harus JPG, PNG, atau WEBP') !== false && (int) qv("SELECT COUNT(*) FROM products WHERE name = 'Produk Uji Shell'") === 0);

$r = $admin->post('admin/products/create.php', $baseProduct + ['name' => 'Produk Uji Otomatis A', 'price' => '10000', 'stock' => '5', 'image' => new CURLFile($png, 'image/png', 'uji.png')], 'admin/products/create.php', true);
$pA = q1("SELECT * FROM products WHERE name = 'Produk Uji Otomatis A'");
check('Tambah produk A (dengan gambar) berhasil', $r['code'] === 302 && $pA !== null);
check('Gambar tersimpan di uploads/products dengan nama acak', $pA && strpos($pA['image'], 'uploads/products/') === 0 && is_file(__DIR__ . '/../' . $pA['image']) && strpos($pA['image'], 'uji') === false);
$r = $admin->post('admin/products/create.php', $baseProduct + ['name' => 'Produk Uji Otomatis B', 'price' => '25.000', 'stock' => '3'], 'admin/products/create.php');
$pB = q1("SELECT * FROM products WHERE name = 'Produk Uji Otomatis B'");
check('Tambah produk B (tanpa gambar, harga format "25.000") berhasil', $r['code'] === 302 && $pB && (float) $pB['price'] === 25000.0);
$idA = (int) $pA['id'];
$idB = (int) $pB['id'];
$r = $admin->get('admin/products/index.php?q=Produk+Uji');
check('Produk baru tampil di daftar produk admin', strpos($r['body'], 'Produk Uji Otomatis A') !== false && strpos($r['body'], 'Produk Uji Otomatis B') !== false);
$r = $admin->post("admin/products/edit.php?id=$idB", $baseProduct + ['name' => 'Produk Uji Otomatis B', 'price' => '25000', 'stock' => '4'], "admin/products/edit.php?id=$idB");
check('Edit stok produk B menjadi 4', $r['code'] === 302 && (int) qv('SELECT stock FROM products WHERE id = ?', 'i', [$idB]) === 4);
$r = $admin->post("admin/products/edit.php?id=$idB", $baseProduct + ['name' => 'Produk Uji Otomatis B', 'price' => '25000', 'stock' => '3'], "admin/products/edit.php?id=$idB");
check('Edit stok produk B kembali menjadi 3', (int) qv('SELECT stock FROM products WHERE id = ?', 'i', [$idB]) === 3);

/* ------------------------------------------------------------------ */
/* 5. Registrasi & login pembeli                                       */
/* ------------------------------------------------------------------ */
section('5. Registrasi & login pembeli');
$buyer = new Client($base, "$tmpDir/buyer.txt");
$email = 'uji_' . bin2hex(random_bytes(4)) . '@pasarkita.test';
$r = $buyer->post('auth/register.php', ['name' => 'Pembeli Uji', 'email' => $email, 'phone' => '081299990000', 'address' => 'Jl. Pengujian No. 1, Yogyakarta', 'password' => 'pendek', 'password_confirmation' => 'pendek'], 'auth/register.php');
check('Registrasi dengan password lemah ditolak', $r['code'] === 200 && strpos($r['body'], 'Password minimal 8') !== false);
$r = $buyer->post('auth/register.php', ['name' => 'Pembeli Uji', 'email' => 'pembeli@pasarkita.test', 'phone' => '', 'address' => '', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123'], 'auth/register.php');
check('Registrasi dengan email yang sudah terdaftar ditolak', strpos($r['body'], 'Email sudah terdaftar') !== false);
$r = $buyer->post('auth/register.php', ['name' => 'Pembeli Uji', 'email' => $email, 'phone' => '081299990000', 'address' => 'Jl. Pengujian No. 1, Yogyakarta', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123'], 'auth/register.php');
$u = q1('SELECT * FROM users WHERE email = ?', 's', [$email]);
check('Registrasi pembeli berhasil', $r['code'] === 302 && $u !== null && $u['role'] === 'pembeli');
check('Password disimpan sebagai hash (password_hash) bukan teks asli', $u && $u['password'] !== 'rahasia123' && password_verify('rahasia123', $u['password']));
$buyerId = (int) $u['id'];
$r = $buyer->post('auth/logout.php', [], 'index.php');
check('Logout (POST + CSRF) berhasil', $r['code'] === 302 && strpos($buyer->get('index.php')['body'], 'Daftar</a>') !== false);
$r = $buyer->get('auth/logout.php');
check('Logout via GET tidak memproses apa pun (hanya redirect)', $r['code'] === 302);
$r = $buyer->post('auth/login.php', ['email' => $email, 'password' => 'rahasia123'], 'auth/login.php');
check('Login pembeli dengan password_verify berhasil', $r['code'] === 302 && strpos($r['location'], 'index.php') !== false);

section('6. Akses pembeli ke halaman admin');
foreach (['admin/index.php', 'admin/products/create.php', 'admin/orders/index.php'] as $p) {
    $r = $buyer->get($p);
    check("Pembeli mendapat 403 di $p", $r['code'] === 403);
}
$r = $buyer->post('admin/products/delete.php', ['id' => $idA], 'index.php');
check('Pembeli tidak dapat menghapus produk', $r['code'] === 403 && (int) qv('SELECT COUNT(*) FROM products WHERE id = ?', 'i', [$idA]) === 1);
$r = $admin->get('checkout/index.php');
check('Admin tidak dapat checkout (403)', $r['code'] === 403);

/* ------------------------------------------------------------------ */
/* 7. Keranjang                                                        */
/* ------------------------------------------------------------------ */
section('7. Keranjang belanja');
$r = $buyer->post('cart/add.php', ['product_id' => $idA, 'quantity' => 2, 'price' => 1], 'index.php');
check('Tambah produk A x2 ke keranjang', $r['code'] === 302);
$buyer->post('cart/add.php', ['product_id' => $idB, 'quantity' => 1], 'index.php');
$r = $buyer->get('cart/index.php');
check('Keranjang menampilkan 2 produk', strpos($r['body'], 'Produk Uji Otomatis A') !== false && strpos($r['body'], 'Produk Uji Otomatis B') !== false);
check('Field "price" kiriman browser diabaikan (subtotal A = Rp 20.000)', strpos($r['body'], 'Rp 20.000') !== false);
check('Subtotal & total dihitung benar (45.000 + ongkir 10.000 = 55.000)', strpos($r['body'], 'Rp 45.000') !== false && strpos($r['body'], 'Rp 55.000') !== false);
$buyer->post('cart/add.php', ['product_id' => $idA, 'quantity' => 10], 'index.php');
$r = $buyer->get('cart/index.php');
check('Tambah melebihi stok ditolak', strpos($r['body'], 'tidak mencukupi') !== false || strpos($buyer->get('cart/index.php')['body'], 'value="2"') !== false);
$buyer->post('cart/update.php', ['product_id' => $idA, 'quantity' => 3], 'cart/index.php');
$r = $buyer->get('cart/index.php');
check('Ubah jumlah produk A menjadi 3', strpos($r['body'], 'id="qty-' . $idA . '" name="quantity" value="3"') !== false);
$buyer->post('cart/update.php', ['product_id' => $idA, 'quantity' => 99], 'cart/index.php');
$r = $buyer->get('cart/index.php');
check('Ubah jumlah melebihi stok ditolak (tetap 3)', strpos($r['body'], 'id="qty-' . $idA . '" name="quantity" value="3"') !== false);
$buyer->post('cart/update.php', ['product_id' => $idA, 'quantity' => '-1'], 'cart/index.php');
$r = $buyer->get('cart/index.php');
check('Jumlah negatif ditolak (tetap 3)', strpos($r['body'], 'id="qty-' . $idA . '" name="quantity" value="3"') !== false);
$buyer->post('cart/remove.php', ['product_id' => $idB], 'cart/index.php');
$r = $buyer->get('cart/index.php');
check('Hapus produk B dari keranjang', strpos($r['body'], 'Produk Uji Otomatis B') === false && strpos($r['body'], 'Produk Uji Otomatis A') !== false);
$buyer->post('cart/add.php', ['product_id' => $idB, 'quantity' => 1], 'index.php');

/* ------------------------------------------------------------------ */
/* 8. Checkout                                                         */
/* ------------------------------------------------------------------ */
section('8. Checkout, orders, order_details, pengurangan stok');
$ordersBefore = (int) qv('SELECT COUNT(*) FROM orders');
$r = $buyer->post('checkout/index.php', ['recipient_name' => 'Pembeli Uji', 'phone' => '12345', 'shipping_address' => 'pendek', 'payment_method' => 'bitcoin', 'notes' => ''], 'checkout/index.php');
check('Validasi checkout menolak HP/alamat/metode bayar tidak valid', $r['code'] === 200 && strpos($r['body'], 'Nomor HP tidak valid') !== false && strpos($r['body'], 'Pilih metode pembayaran') !== false);
check('Checkout invalid tidak membuat pesanan', (int) qv('SELECT COUNT(*) FROM orders') === $ordersBefore);
$r = $buyer->post('checkout/index.php', ['recipient_name' => 'Pembeli Uji', 'phone' => '081299990000', 'shipping_address' => 'Jl. Pengujian No. 1, Yogyakarta 55000', 'payment_method' => 'transfer', 'notes' => 'Uji otomatis', 'total_amount' => 1], 'checkout/index.php');
check('Checkout berhasil & diarahkan ke halaman sukses', $r['code'] === 302 && strpos($r['location'], 'checkout/success.php?kode=') !== false);
$order = q1('SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 1', 'i', [$buyerId]);
check('Data tersimpan di tabel orders', $order !== null);
check('Total dihitung server: subtotal 55.000 + ongkir 10.000 = 65.000', $order && (float) $order['subtotal'] === 55000.0 && (float) $order['shipping_cost'] === 10000.0 && (float) $order['total_amount'] === 65000.0 && (int) $order['total_items'] === 4);
$detailCount = (int) qv('SELECT COUNT(*) FROM order_details WHERE order_id = ?', 'i', [(int) $order['id']]);
check('Data tersimpan di tabel order_details (2 baris)', $detailCount === 2);
$dA = q1('SELECT * FROM order_details WHERE order_id = ? AND product_id = ?', 'ii', [(int) $order['id'], $idA]);
check('Snapshot nama & harga produk tersimpan (A: 3 x 10.000)', $dA && $dA['product_name'] === 'Produk Uji Otomatis A' && (float) $dA['price'] === 10000.0 && (int) $dA['quantity'] === 3 && (float) $dA['subtotal'] === 30000.0);
check('Stok produk A berkurang 5 -> 2', (int) qv('SELECT stock FROM products WHERE id = ?', 'i', [$idA]) === 2);
check('Stok produk B berkurang 3 -> 2', (int) qv('SELECT stock FROM products WHERE id = ?', 'i', [$idB]) === 2);
$r = $buyer->get(substr($r['location'], strpos($r['location'], 'checkout/')));
check('Halaman sukses menampilkan kode pesanan', $r['code'] === 200 && strpos($r['body'], $order['order_code']) !== false);
check('Keranjang dikosongkan setelah checkout', strpos($buyer->get('cart/index.php')['body'], 'Keranjang masih kosong') !== false);
$r = $guest->get('checkout/success.php?kode=' . $order['order_code']);
check('Tamu tidak dapat melihat halaman sukses pesanan orang lain', $r['code'] === 302);
$r = $buyer->get('account/orders.php');
check('Pesanan muncul di "Pesanan Saya"', strpos($r['body'], $order['order_code']) !== false);

section('9. Histori harga (snapshot) setelah harga produk diubah');
$admin->post("admin/products/edit.php?id=$idA", $baseProduct + ['name' => 'Produk Uji Otomatis A', 'price' => '15000', 'stock' => '2'], "admin/products/edit.php?id=$idA");
check('Harga produk A diubah menjadi 15.000', (float) qv('SELECT price FROM products WHERE id = ?', 'i', [$idA]) === 15000.0);
check('Harga di order_details tetap 10.000 (histori terjaga)', (float) qv('SELECT price FROM order_details WHERE id = ?', 'i', [(int) $dA['id']]) === 10000.0);

section('10. Validasi stok tidak mencukupi + rollback');
$buyer->post('cart/add.php', ['product_id' => $idB, 'quantity' => 2], 'index.php');
$buyer->post('cart/add.php', ['product_id' => $idA, 'quantity' => 1], 'index.php');
// Simulasikan pembeli lain membeli lebih dulu: stok B turun ke 1
$db->query("UPDATE products SET stock = 1 WHERE id = $idB");
$ordersBefore = (int) qv('SELECT COUNT(*) FROM orders');
$detailsBefore = (int) qv('SELECT COUNT(*) FROM order_details');
$r = $buyer->post('checkout/index.php', ['recipient_name' => 'Pembeli Uji', 'phone' => '081299990000', 'shipping_address' => 'Jl. Pengujian No. 1, Yogyakarta 55000', 'payment_method' => 'cod', 'notes' => ''], 'checkout/index.php');
check('Checkout ditolak karena stok tidak mencukupi', $r['code'] === 200 && strpos($r['body'], 'tidak mencukupi') !== false && strpos($r['body'], 'Pesanan gagal dibuat') !== false);
check('Tidak ada order baru tersimpan (rollback)', (int) qv('SELECT COUNT(*) FROM orders') === $ordersBefore && (int) qv('SELECT COUNT(*) FROM order_details') === $detailsBefore);
check('Stok produk A tidak ikut berkurang (rollback) tetap 2', (int) qv('SELECT stock FROM products WHERE id = ?', 'i', [$idA]) === 2);
check('Stok produk B tetap 1', (int) qv('SELECT stock FROM products WHERE id = ?', 'i', [$idB]) === 1);
$buyer->post('cart/remove.php', ['action' => 'clear'], 'cart/index.php');

section('11. Admin mengubah status & membatalkan pesanan');
$oid = (int) $order['id'];
$admin->post("admin/orders/detail.php?id=$oid", ['status' => 'diproses'], "admin/orders/detail.php?id=$oid");
check('Status pesanan diubah menjadi "diproses"', qv('SELECT status FROM orders WHERE id = ?', 'i', [$oid]) === 'diproses');
$admin->post("admin/orders/detail.php?id=$oid", ['status' => 'dibatalkan'], "admin/orders/detail.php?id=$oid");
check('Pesanan dibatalkan', qv('SELECT status FROM orders WHERE id = ?', 'i', [$oid]) === 'dibatalkan');
check('Stok dikembalikan setelah pembatalan (A: 2 -> 5, B: 1 -> 2)', (int) qv('SELECT stock FROM products WHERE id = ?', 'i', [$idA]) === 5 && (int) qv('SELECT stock FROM products WHERE id = ?', 'i', [$idB]) === 2);
$admin->post("admin/orders/detail.php?id=$oid", ['status' => 'pending'], "admin/orders/detail.php?id=$oid");
check('Pesanan yang sudah dibatalkan tidak bisa diubah lagi', qv('SELECT status FROM orders WHERE id = ?', 'i', [$oid]) === 'dibatalkan');

section('12. Hapus produk & integritas histori');
$imagePath = __DIR__ . '/../' . $pA['image'];
$r = $admin->post('admin/products/delete.php', ['id' => $idA], 'admin/products/index.php');
check('Hapus produk A berhasil', $r['code'] === 302 && (int) qv('SELECT COUNT(*) FROM products WHERE id = ?', 'i', [$idA]) === 0);
clearstatcache();
check('File gambar produk A ikut terhapus', !is_file($imagePath));
$d = q1('SELECT * FROM order_details WHERE id = ?', 'i', [(int) $dA['id']]);
check('Detail pesanan tetap ada, product_id menjadi NULL, nama & harga snapshot tetap', $d && $d['product_id'] === null && $d['product_name'] === 'Produk Uji Otomatis A' && (float) $d['price'] === 10000.0);
$r = $buyer->get('account/order_detail.php?kode=' . $order['order_code']);
check('Pembeli tetap dapat melihat detail pesanan lama (produk dihapus)', $r['code'] === 200 && strpos($r['body'], 'produk dihapus') !== false);
$r = $admin->post('admin/products/delete.php', ['id' => $idB], 'admin/products/index.php');
check('Hapus produk B berhasil', (int) qv('SELECT COUNT(*) FROM products WHERE id = ?', 'i', [$idB]) === 0);

/* ------------------------------------------------------------------ */
/* Pembersihan data uji                                                */
/* ------------------------------------------------------------------ */
section('Pembersihan data uji');
$db->query("DELETE FROM orders WHERE user_id = $buyerId");     // order_details ikut terhapus (CASCADE)
$db->query("DELETE FROM users WHERE id = $buyerId");
$db->query("DELETE FROM products WHERE name LIKE 'Produk Uji%'");
check('Data uji dihapus', (int) qv('SELECT COUNT(*) FROM users WHERE id = ?', 'i', [$buyerId]) === 0);
array_map('unlink', glob("$tmpDir/*"));
rmdir($tmpDir);

/* ------------------------------------------------------------------ */
$pass = count(array_filter($results, fn ($r) => $r[2]));
$total = count($results);
echo "\n==============================\n";
echo "HASIL: $pass / $total pengujian LULUS" . ($pass === $total ? '' : ' (' . ($total - $pass) . ' GAGAL)') . "\n";
echo 'Waktu: ' . date('Y-m-d H:i:s') . " | Target: $base | PHP " . PHP_VERSION . ' | MySQL ' . $db->server_info . "\n";
exit($pass === $total ? 0 : 1);
