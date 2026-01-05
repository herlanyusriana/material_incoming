# ERD (Database Diagram)

Di bawah ini ERD utama untuk domain `Incoming` (berdasarkan migration + model Eloquent).

```mermaid
erDiagram
  USERS {
    bigint id PK
    string name
    string email
  }

  VENDORS {
    bigint id PK
    string vendor_name
    string country_code
    text address
    string bank_account
    string phone
    string signature_path
    enum status
    datetime deleted_at
  }

  PARTS {
    bigint id PK
    string register_no
    string part_no "UNIQUE"
    string part_name_vendor
    string part_name_gci
    string hs_code
    bigint vendor_id FK
    enum status
  }

  TRUCKING_COMPANIES {
    bigint id PK
    string company_name
    text address
    string phone
    string email
    string contact_person
    string status
  }

  ARRIVALS {
    bigint id PK
    string arrival_no "UNIQUE"
    string invoice_no
    date invoice_date
    bigint vendor_id FK
    bigint trucking_company_id FK "NULLABLE"
    bigint created_by FK
    string vessel
    date ETD
    date ETA
    string bill_of_lading
    string price_term
    string hs_code
    text hs_codes
    string port_of_loading
    string country
    text container_numbers "legacy"
    string seal_code "legacy"
    string currency
    text notes
  }

  ARRIVAL_ITEMS {
    bigint id PK
    bigint arrival_id FK
    bigint part_id FK
    string material_group
    string size
    int qty_bundle
    string unit_bundle
    decimal qty_goods
    string unit_goods
    decimal weight_nett
    string unit_weight
    decimal weight_gross
    decimal price
    decimal total_price
    text notes
  }

  RECEIVES {
    bigint id PK
    bigint arrival_item_id FK
    string tag
    int qty
    string bundle_unit
    int bundle_qty
    datetime ata_date
    string qc_status
    decimal weight
    decimal net_weight
    decimal gross_weight
    string qty_unit
    string jo_po_number
    string location_code
  }

  ARRIVAL_CONTAINERS {
    bigint id PK
    bigint arrival_id FK
    string container_no
    string seal_code
    "UNIQUE(arrival_id, container_no)"
  }

  ARRIVAL_CONTAINER_INSPECTIONS {
    bigint id PK
    bigint arrival_container_id FK "UNIQUE"
    string status "ok|damage"
    string seal_code
    text notes
    json issues_left
    json issues_right
    json issues_front
    json issues_back
    string photo_left
    string photo_right
    string photo_front
    string photo_back
    string photo_inside
    string photo_seal
    bigint inspected_by FK "NULLABLE"
  }

  ARRIVAL_INSPECTIONS {
    bigint id PK
    bigint arrival_id FK "UNIQUE (legacy)"
    bigint inspected_by FK "NULLABLE"
    string status "ok|damage"
    text notes
    json issues_left
    json issues_right
    json issues_front
    json issues_back
    string photo_left
    string photo_right
    string photo_front
    string photo_back
    string photo_inside
  }

  VENDORS ||--o{ PARTS : has
  VENDORS ||--o{ ARRIVALS : has
  TRUCKING_COMPANIES ||--o{ ARRIVALS : ships
  USERS ||--o{ ARRIVALS : created_by
  ARRIVALS ||--o{ ARRIVAL_ITEMS : has
  PARTS ||--o{ ARRIVAL_ITEMS : used_in
  ARRIVAL_ITEMS ||--o{ RECEIVES : receives
  ARRIVALS ||--o{ ARRIVAL_CONTAINERS : has
  ARRIVAL_CONTAINERS ||--o| ARRIVAL_CONTAINER_INSPECTIONS : inspection
  USERS ||--o{ ARRIVAL_CONTAINER_INSPECTIONS : inspected_by
  ARRIVALS ||--o| ARRIVAL_INSPECTIONS : legacy_inspection
  USERS ||--o{ ARRIVAL_INSPECTIONS : inspected_by
```

**Catatan**
- `arrival_inspections` = legacy (per-invoice). Implementasi terbaru inspeksi ada di `arrival_container_inspections` (per container).
- Kolom `arrivals.container_numbers` dan `arrivals.seal_code` juga legacy (sekarang lebih akurat pakai `arrival_containers`).

