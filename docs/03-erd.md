# 3. Entity Relationship Diagram (ERD)

Diagram berikut ditulis dalam format **Mermaid** dan sesuai dengan struktur pada `database/pasarkita.sql`. Dapat dirender langsung di GitHub, VS Code (ekstensi Markdown Preview Mermaid), atau https://mermaid.live.

```mermaid
erDiagram
    USERS ||--o{ ORDERS : "membuat"
    CATEGORIES ||--o{ PRODUCTS : "mengelompokkan"
    ORDERS ||--|{ ORDER_DETAILS : "berisi"
    PRODUCTS |o--o{ ORDER_DETAILS : "dipesan sebagai"

    USERS {
        int id PK
        varchar name
        varchar email UK
        varchar password "hash password_hash()"
        varchar phone "nullable"
        text address "nullable"
        enum role "admin | pembeli"
        timestamp created_at
        timestamp updated_at
    }

    CATEGORIES {
        int id PK
        varchar name UK
        varchar slug UK
        varchar description "nullable"
        varchar icon
        timestamp created_at
    }

    PRODUCTS {
        int id PK
        int category_id FK "ON DELETE RESTRICT"
        varchar name
        varchar slug UK
        text description
        decimal price "CHECK >= 0"
        int stock "UNSIGNED, CHECK >= 0"
        varchar unit
        varchar origin "nullable"
        varchar seller_name "nullable"
        varchar image "nullable"
        tinyint is_active
        timestamp created_at
        timestamp updated_at
    }

    ORDERS {
        int id PK
        varchar order_code UK
        int user_id FK "ON DELETE RESTRICT"
        varchar recipient_name
        varchar phone
        text shipping_address
        varchar notes "nullable"
        enum payment_method "transfer | cod"
        int total_items
        decimal subtotal
        decimal shipping_cost
        decimal total_amount "= subtotal + shipping_cost"
        enum status "pending | diproses | dikirim | selesai | dibatalkan"
        timestamp created_at
        timestamp updated_at
    }

    ORDER_DETAILS {
        int id PK
        int order_id FK "ON DELETE CASCADE"
        int product_id FK "nullable, ON DELETE SET NULL"
        varchar product_name "snapshot"
        decimal price "snapshot harga"
        int quantity "CHECK > 0"
        decimal subtotal "= price * quantity"
    }
```

## Keterangan Notasi

| Simbol | Arti |
|---|---|
| `\|\|--o{` | satu ke nol-atau-banyak |
| `\|\|--\|{` | satu ke satu-atau-banyak (pesanan minimal punya 1 item) |
| `\|o--o{` | nol-atau-satu ke nol-atau-banyak (`product_id` boleh NULL bila produk dihapus) |
| PK / FK / UK | Primary Key / Foreign Key / Unique Key |
