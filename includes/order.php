<?php
/**
 * Logika transaksi pesanan.
 *
 * create_order() menjalankan seluruh proses checkout di dalam SATU database
 * transaction:
 *   1. Kunci baris produk (SELECT ... FOR UPDATE) agar stok tidak diubah
 *      transaksi lain secara bersamaan.
 *   2. Validasi produk masih aktif & stok mencukupi.
 *   3. Hitung total dari harga DI DATABASE (bukan dari browser).
 *   4. Simpan orders + order_details (snapshot nama & harga).
 *   5. Kurangi stok dengan kondisi "stock >= qty".
 *   6. COMMIT bila semua berhasil, ROLLBACK bila ada satu saja yang gagal.
 */

class OrderException extends RuntimeException
{
    /** @var string[] */
    public $errors;

    public function __construct(array $errors)
    {
        $this->errors = $errors;
        parent::__construct(implode(' ', $errors));
    }
}

function generate_order_code(): string
{
    return 'PK' . date('ymd') . strtoupper(bin2hex(random_bytes(3)));
}

/**
 * @param array $cart     [product_id => qty] dari session
 * @param array $shipping recipient_name, phone, shipping_address, notes, payment_method
 * @return array          ['id' => int, 'order_code' => string]
 * @throws OrderException bila validasi stok/produk gagal
 */
function create_order(int $userId, array $cart, array $shipping): array
{
    if (!$cart) {
        throw new OrderException(['Keranjang belanja kosong.']);
    }

    $conn = db();
    $conn->begin_transaction();

    try {
        $lines = [];
        $errors = [];
        $subtotal = 0.0;
        $totalItems = 0;

        // Urutkan ID agar urutan penguncian konsisten (mencegah deadlock)
        ksort($cart);
        foreach ($cart as $productId => $qty) {
            $productId = (int) $productId;
            $qty = (int) $qty;
            if ($productId <= 0 || $qty <= 0) {
                $errors[] = 'Data keranjang tidak valid.';
                continue;
            }
            $product = db_fetch_one(
                'SELECT id, name, price, stock, is_active FROM products WHERE id = ? FOR UPDATE',
                'i',
                [$productId]
            );
            if (!$product || !(int) $product['is_active']) {
                $errors[] = 'Produk dengan ID ' . $productId . ' sudah tidak tersedia.';
                continue;
            }
            if ((int) $product['stock'] < $qty) {
                $errors[] = 'Stok "' . $product['name'] . '" tidak mencukupi (tersisa ' . (int) $product['stock'] . ', diminta ' . $qty . ').';
                continue;
            }
            $lineSubtotal = round((float) $product['price'] * $qty, 2);
            $lines[] = [
                'product_id' => (int) $product['id'],
                'name'       => $product['name'],
                'price'      => (float) $product['price'],
                'qty'        => $qty,
                'subtotal'   => $lineSubtotal,
            ];
            $subtotal += $lineSubtotal;
            $totalItems += $qty;
        }

        if ($errors) {
            throw new OrderException($errors);
        }

        $shippingCost = (float) config('app.shipping_cost', 10000);
        $total = $subtotal + $shippingCost;
        $orderCode = generate_order_code();

        db_execute(
            'INSERT INTO orders (order_code, user_id, recipient_name, phone, shipping_address, notes, payment_method,
                                 total_items, subtotal, shipping_cost, total_amount, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'pending\')',
            'sisssssiddd',
            [
                $orderCode, $userId, $shipping['recipient_name'], $shipping['phone'], $shipping['shipping_address'],
                $shipping['notes'] !== '' ? $shipping['notes'] : null, $shipping['payment_method'],
                $totalItems, $subtotal, $shippingCost, $total,
            ]
        );
        $orderId = db_last_id();

        foreach ($lines as $line) {
            // Snapshot nama & harga produk saat dipesan
            db_execute(
                'INSERT INTO order_details (order_id, product_id, product_name, price, quantity, subtotal) VALUES (?, ?, ?, ?, ?, ?)',
                'iisdid',
                [$orderId, $line['product_id'], $line['name'], $line['price'], $line['qty'], $line['subtotal']]
            );
            // Kurangi stok; kondisi stock >= qty sebagai pengaman tambahan
            $affected = db_execute(
                'UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?',
                'iii',
                [$line['qty'], $line['product_id'], $line['qty']]
            );
            if ($affected !== 1) {
                throw new OrderException(['Stok "' . $line['name'] . '" berubah saat diproses. Silakan coba lagi.']);
            }
        }

        $conn->commit();
        return ['id' => $orderId, 'order_code' => $orderCode];
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

/**
 * Ubah status pesanan (admin). Bila dibatalkan, stok dikembalikan
 * dalam transaksi yang sama. Pesanan yang sudah dibatalkan/selesai tidak dapat diubah lagi.
 */
function update_order_status(int $orderId, string $newStatus): ?string
{
    $allowed = ['pending', 'diproses', 'dikirim', 'selesai', 'dibatalkan'];
    if (!in_array($newStatus, $allowed, true)) {
        return 'Status tidak valid.';
    }

    $conn = db();
    $conn->begin_transaction();
    try {
        $order = db_fetch_one('SELECT id, status FROM orders WHERE id = ? FOR UPDATE', 'i', [$orderId]);
        if (!$order) {
            $conn->rollback();
            return 'Pesanan tidak ditemukan.';
        }
        if (in_array($order['status'], ['dibatalkan', 'selesai'], true)) {
            $conn->rollback();
            return 'Pesanan berstatus "' . $order['status'] . '" tidak dapat diubah lagi.';
        }
        if ($order['status'] === $newStatus) {
            $conn->rollback();
            return null;
        }

        if ($newStatus === 'dibatalkan') {
            $details = db_fetch_all('SELECT product_id, quantity FROM order_details WHERE order_id = ? AND product_id IS NOT NULL', 'i', [$orderId]);
            foreach ($details as $d) {
                db_execute('UPDATE products SET stock = stock + ? WHERE id = ?', 'ii', [(int) $d['quantity'], (int) $d['product_id']]);
            }
        }
        db_execute('UPDATE orders SET status = ? WHERE id = ?', 'si', [$newStatus, $orderId]);
        $conn->commit();
        return null;
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}
