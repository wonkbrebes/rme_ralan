# 📊 Diagram Sistem SIMRS Rawat Jalan RSUD Puruk Cahu (Latar Belakang Putih)

Dokumen ini berisi kumpulan diagram analisis dan perancangan sistem yang telah dikonfigurasi khusus dengan **Latar Belakang Putih Bersih (Solid White Background)** dan kontras teks gelap. Format ini sangat wajib dan ideal untuk dipasang pada **Laporan Praktikum, Tugas Akhir, atau Skripsi** yang dicetak pada kertas putih.

---

## 💡 Cara Paling Cepat Export Gambar Berlatar Putih di [mermaid.live](https://mermaid.live):
1. Salin kode diagram di bawah ini (tanpa tanda ````mermaid).
2. Tempel di kolom kiri situs **[https://mermaid.live](https://mermaid.live)**.
3. **PENTING:** Di panel bawah atau menu **Theme / Background**, pastikan Anda memilih **"Light"** atau **"White"** (jangan *Dark* atau *Transparent*).
4. Klik tombol **Actions** (sudut kanan atas) -> pilih **Export PNG**. Gambar PNG berlatar putih bersih siap disalin ke Word!

---

## 1. Use Case Diagram (Latar Putih Formal)

```mermaid
%%{init: {'theme': 'default', 'themeVariables': { 'background': '#ffffff', 'primaryColor': '#eff6ff', 'primaryTextColor': '#1e293b', 'primaryBorderColor': '#3b82f6', 'lineColor': '#64748b', 'clusterBkg': '#f8fafc', 'clusterBorder': '#cbd5e1' }}}%%
graph LR
    subgraph Aktor["👥 Aktor Sistem"]
        Pasien["🧑‍🤝‍🧑 Pasien / Masyarakat"]
        Dokter["👨‍⚕️ Dokter Poliklinik"]
        Admin["🖥️ Admin / Kasir RS"]
    end

    subgraph SIMRS["🏥 Sistem Informasi Manajemen Rawat Jalan (SIMRS)"]
        UC1["📋 Registrasi & Manajemen Akun"]
        UC2["🔑 Login Sistem Aman (NIK & Password)"]
        UC3["🔍 Lihat Jadwal & Profil Dokter Poliklinik"]
        UC4["📅 Buat Reservasi & Ambil Nomor Antrian"]
        UC5["💳 Pilih & Konfirmasi Pembayaran Online"]
        UC6["💬 Terima Notifikasi WhatsApp & Email"]
        UC7["🩺 Kelola Jadwal & Pemeriksaan Pasien"]
        UC8["📊 Verifikasi Transaksi & Laporan RS"]
    end

    Pasien --> UC1
    Pasien --> UC2
    Pasien --> UC3
    Pasien --> UC4
    Pasien --> UC5
    Pasien --> UC6

    Dokter --> UC2
    Dokter --> UC3
    Dokter --> UC7

    Admin --> UC2
    Admin --> UC7
    Admin --> UC8

    style Aktor fill:#f1f5f9,stroke:#94a3b8,stroke-width:2px,color:#0f172a
    style SIMRS fill:#f8fafc,stroke:#3b82f6,stroke-width:2px,color:#0f172a
    style Pasien fill:#dbeafe,stroke:#2563eb,color:#1e3a8a
    style Dokter fill:#dbeafe,stroke:#2563eb,color:#1e3a8a
    style Admin fill:#dbeafe,stroke:#2563eb,color:#1e3a8a
```

---

## 2. Activity Diagram (Alur Reservasi & Konfirmasi Pembayaran)

```mermaid
%%{init: {'theme': 'default', 'themeVariables': { 'background': '#ffffff', 'primaryColor': '#f0fdf4', 'primaryTextColor': '#14532d', 'primaryBorderColor': '#22c55e', 'lineColor': '#475569' }}}%%
graph TD
    Start(["🟢 Mulai"]) --> A["Pasien Membuka Web SIMRS RSUD Puruk Cahu"]
    A --> B{"Apakah Sudah Punya Akun?"}
    B -- "Belum" --> C["Registrasi Akun (Input NIK, Nama, WA, Email)"]
    C --> D["Login ke Portal Pasien"]
    B -- "Sudah" --> D
    D --> E["Pilih Poliklinik & Dokter Spesialis Tujuan"]
    E --> F["Pilih Tanggal & Waktu Kunjungan"]
    F --> G["Sistem Menerbitkan Kode Reservasi & Nomor Antrian"]
    G --> H["Pilih Metode Pembayaran (BPJS / QRIS / Transfer / Tunai)"]
    H --> I{"Metode Pembayaran?"}
    I -- "BPJS Kesehatan" --> J["Input Nomor BPJS & Verifikasi Otomatis Sistem"]
    I -- "QRIS / Transfer / Tunai" --> K["Konfirmasi Pembayaran & Upload Bukti Transaksi"]
    J --> L["Status Reservasi: TERKONFIRMASI (LUNAS)"]
    K --> L
    L --> M["Sistem Mengirim Notifikasi via WhatsApp & Email SMTP"]
    M --> N["Pasien Datang ke Poliklinik Sesuai Nomor Antrian"]
    N --> End(["🔴 Selesai / Pelayanan Medis"])

    style Start fill:#bbf7d0,stroke:#16a34a,color:#14532d,stroke-width:2px
    style End fill:#fecaca,stroke:#dc2626,color:#7f1d1d,stroke-width:2px
    style B fill:#fef3c7,stroke:#d97706,color:#78350f
    style I fill:#fef3c7,stroke:#d97706,color:#78350f
```

---

## 3. Entity Relationship Diagram (ERD - Skema Database Supabase)

```mermaid
%%{init: {'theme': 'default', 'themeVariables': { 'background': '#ffffff', 'primaryColor': '#eff6ff', 'primaryTextColor': '#0f172a', 'primaryBorderColor': '#3b82f6', 'lineColor': '#475569' }}}%%
erDiagram
    USERS ||--o{ PASIEN : "memiliki profil"
    USERS ||--o{ DOKTER : "memiliki profil"
    USERS ||--o{ NOTIFIKASI : "menerima"
    PASIEN ||--o{ RESERVASI : "melakukan"
    DOKTER ||--o{ JADWAL_DOKTER : "memiliki"
    DOKTER ||--o{ RESERVASI : "melayani"
    RESERVASI ||--|| PEMBAYARAN : "memerlukan"
    RESERVASI ||--o{ NOTIFIKASI : "memicu"

    USERS {
        uuid id PK "Supabase Auth ID"
        string email
        string encrypted_password
        string role "pasien / dokter / admin"
        timestamp created_at
    }
    PASIEN {
        int id PK
        uuid user_id FK
        string nik "Nomor Induk Kependudukan"
        string nama_lengkap
        string no_whatsapp
        string no_bpjs
        text alamat
    }
    DOKTER {
        int id PK
        uuid user_id FK
        string nama_dokter
        string spesialisasi
        string poli
        string no_sip
        int kuota_harian
    }
    JADWAL_DOKTER {
        int id PK
        int dokter_id FK
        string hari
        time jam_mulai
        time jam_selesai
        boolean status_aktif
    }
    RESERVASI {
        int id PK
        string kode_reservasi "Unik Antrian"
        int pasien_id FK
        int dokter_id FK
        date tanggal_kunjungan
        int nomor_antrian
        string status "menunggu / terkonfirmasi / selesai"
    }
    PEMBAYARAN {
        int id PK
        int reservasi_id FK
        string metode "BPJS / QRIS / Transfer / Tunai"
        decimal jumlah_biaya
        string status_pembayaran
        timestamp waktu_konfirmasi
    }
    NOTIFIKASI {
        int id PK
        uuid user_id FK
        int reservasi_id FK
        string saluran "WhatsApp / Email"
        text pesan
        string status_kirim "terkirim / gagal"
        timestamp sent_at
    }
```

---

## 4. Sequence Diagram (Proses Konfirmasi & Pengiriman Notifikasi)

```mermaid
%%{init: {'theme': 'default', 'themeVariables': { 'background': '#ffffff', 'actorBkg': '#eff6ff', 'actorBorder': '#3b82f6', 'actorTextColor': '#1e3a8a', 'signalColor': '#334155', 'signalTextColor': '#0f172a', 'noteBkgColor': '#fef3c7', 'noteTextColor': '#78350f' }}}%%
sequenceDiagram
    autonumber
    actor P as 🧑‍🤝‍🧑 Pasien
    participant W as 💻 Web Frontend (Blade/Vite)
    participant C as ⚙️ Laravel Controller / API
    participant S as 🗄️ Supabase Database
    participant M as 📨 Gateway Notifikasi (WA & Email)

    P->>W: Mengisi Form Reservasi & Pilih Jadwal Dokter
    W->>C: POST /api/reservasi (pasien_id, dokter_id, tanggal)
    C->>S: Query Cek Kuota & Generate Nomor Antrian
    S-->>C: Data Reservasi Berhasil Disimpan
    C-->>W: Tampilkan Halaman Konfirmasi Pembayaran

    P->>W: Pilih Metode Pembayaran & Klik "Konfirmasi Pembayaran"
    W->>C: POST /api/pembayaran/konfirmasi
    C->>S: Update status_pembayaran = 'LUNAS' & status = 'TERKONFIRMASI'
    S-->>C: Database Terupdate Berhasil

    par Pengiriman Notifikasi Paralel
        C->>M: Kirim Pesan WhatsApp (Kode Reservasi & Antrian via .env)
        M-->>P: 📱 Notifikasi WA Diterima Pasien
    and
        C->>M: Kirim Email Bukti Transaksi via SMTP (.env)
        M-->>P: 📧 Email Bukti Reservasi Diterima Pasien
    end

    C-->>W: Response Berhasil (JSON / Redirect)
    W-->>P: Tampilkan Bukti Reservasi & Nomor Antrian Poliklinik
```

---

## 5. System Architecture / Deployment Diagram (Arsitektur Cloud)

```mermaid
%%{init: {'theme': 'default', 'themeVariables': { 'background': '#ffffff', 'primaryColor': '#f8fafc', 'primaryTextColor': '#0f172a', 'primaryBorderColor': '#64748b', 'lineColor': '#475569', 'clusterBkg': '#ffffff', 'clusterBorder': '#94a3b8' }}}%%
flowchart TB
    subgraph Klien["📱 Perangkat Pengguna"]
        B["🌐 Browser Pasien / Dokter / Admin (HP / Tablet / PC)"]
    end

    subgraph Hosting["☁️ Cloud Hosting & Domain"]
        D["🌍 Domain Custom: wonkbrebes.web.id"]
        V["⚡ Vercel Serverless Platform (PHP 8.3 Runtime)"]
    end

    subgraph Backend["⚙️ Aplikasi Server (Laravel 13)"]
        R["🗺️ Laravel Routing & Serverless Entrypoint"]
        CT["🧠 Controllers & Business Logic"]
        SVR["🛠️ Configuration & Helper (.env Configuration)"]
    end

    subgraph Eksternal["🔗 Layanan & Database Eksternal"]
        SB["🗄️ Supabase PostgreSQL Database (Cloud)"]
        WA["💬 WhatsApp API Gateway Server"]
        SMTP["📧 SMTP Email Server (Gmail / Mailgun)"]
    end

    B -->|"HTTPS Request"| D
    D --> V
    V --> R
    R --> CT
    CT --> SVR
    CT <-->|"SQL Connection / REST API"| SB
    SVR -->|"Trigger Pesan WA"| WA
    SVR -->|"Kirim Bukti Email"| SMTP

    style Klien fill:#f1f5f9,stroke:#64748b,stroke-width:2px,color:#0f172a
    style Hosting fill:#eff6ff,stroke:#3b82f6,stroke-width:2px,color:#1e3a8a
    style Backend fill:#f0fdf4,stroke:#22c55e,stroke-width:2px,color:#14532d
    style Eksternal fill:#fef3c7,stroke:#d97706,stroke-width:2px,color:#78350f
```
