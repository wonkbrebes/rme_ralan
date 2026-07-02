# 📊 Diagram Sistem SIMRS Rawat Jalan RSUD Puruk Cahu untuk Laporan Praktikum

Dokumen ini berisi kumpulan diagram analisis dan perancangan sistem yang lengkap dan formal, dirancang khusus untuk kebutuhan **Laporan Praktikum, Tugas Akhir, atau Laporan Magang**. Seluruh diagram dibuat menggunakan standar **Mermaid.js** yang bersih dan informatif.

Di bagian akhir dokumen ini juga tersedia **Panduan Mudah** untuk mengubah diagram ini menjadi gambar berkualitas tinggi (**PNG / JPEG / SVG**) agar bisa langsung disalin (*copy-paste*) ke Microsoft Word atau Google Docs.

---

## 1. Use Case Diagram (Diagram Interaksi Aktor & Sistem)
Diagram ini menggambarkan siapa saja aktor yang berinteraksi dengan sistem SIMRS dan fitur-fitur utama apa saja yang dapat mereka gunakan.

```mermaid
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
```

> **💡 Deskripsi untuk Laporan:** *Use Case Diagram di atas menunjukkan tiga aktor utama yaitu Pasien, Dokter, dan Admin RS. Pasien memiliki hak akses untuk melakukan pendaftaran mandiri, melihat jadwal dokter, melakukan reservasi antrian, melakukan konfirmasi pembayaran, serta menerima bukti transaksi otomatis melalui WhatsApp dan Email.*

---

## 2. Activity Diagram (Alur Reservasi dan Konfirmasi Pembayaran)
Diagram aktivitas ini memetakan urutan langkah (*workflow*) dari sudut pandang pasien mulai dari membuka situs web hingga menyelesaikan pelayanan poliklinik.

```mermaid
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
```

> **💡 Deskripsi untuk Laporan:** *Activity Diagram menggambarkan alur logika sistem dalam menangani reservasi. Terdapat logika pengecekan kondisi pada metode pembayaran: pasien BPJS akan melewati verifikasi nomor kartu, sedangkan pasien umum/QRIS melakukan konfirmasi pembayaran sebelum sistem mencetak bukti dan mengirimkan notifikasi.*

---

## 3. Entity Relationship Diagram (ERD - Desain Database Supabase)
Diagram ini merepresentasikan struktur tabel dalam database **Supabase PostgreSQL** beserta relasi antar tabel (Primary Key & Foreign Key).

```mermaid
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

> **💡 Deskripsi untuk Laporan:** *ERD menunjukkan skema relasional database aplikasi. Tabel `reservasi` menjadi pusat transaksi yang menghubungkan `pasien` dengan `dokter`, serta berkorelasi satu-ke-satu dengan tabel `pembayaran` dan memicu rekam jejak pada tabel `notifikasi`.*

---

## 4. Sequence Diagram (Proses Konfirmasi & Pengiriman Notifikasi)
Diagram sekuensial ini menjelaskan komunikasi antar komponen (*Frontend*, *Controller*, *Database*, dan *Gateway Notifikasi*) berdasarkan urutan waktu (sistem waktu nyata).

```mermaid
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

> **💡 Deskripsi untuk Laporan:** *Sequence Diagram mengilustrasikan mekanisme asinkron dan paralel pada saat pasien melakukan konfirmasi pembayaran. Setelah database Supabase berhasil diperbarui, controller Laravel secara bersamaan memanggil layanan pengiriman pesan WhatsApp dan email SMTP untuk memberikan konfirmasi instan kepada pasien.*

---

## 5. System Architecture / Deployment Diagram (Arsitektur Cloud)
Diagram ini memperlihatkan infrastruktur teknologi yang digunakan untuk menata aplikasi dari perangkat pengguna hingga deployment di domain kustom.

```mermaid
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
```

> **💡 Deskripsi untuk Laporan:** *Arsitektur sistem SIMRS dibangun dengan pendekatan moderncloud-native. Aplikasi berbasis Laravel 13 dipasang pada lingkungan serverless Vercel dengan domain kustom `wonkbrebes.web.id`. Penyimpanan data dipercayakan pada cloud database Supabase PostgreSQL, dan terhubung dengan gateway notifikasi eksternal.*

---

## 🛠️ Panduan Mengubah Diagram Menjadi Gambar (PNG / JPG) untuk Word

Agar Anda dapat dengan mudah memasukkan diagram di atas ke dalam file **Microsoft Word** atau **Google Docs**, pilih salah satu dari 3 cara termudah berikut:

### Cara 1: Menggunakan Mermaid Live Editor (Paling Mudah & Rekomendasi)
1. Buka situs web resmi Mermaid Editor: **[https://mermaid.live](https://mermaid.live)**
2. Salin (*copy*) kode diagram di atas (hanya bagian teks di dalam blok ````mermaid ... ````, tanpa tanda backtick ````).
3. Tempel (*paste*) kode tersebut di kotak kolom sebelah kiri situs Mermaid Live.
4. Diagram akan langsung otomatis tergambar di kolom sebelah kanan!
5. Klik tombol **Actions** (di sudut kanan atas) -> pilih **Export PNG** atau **Export SVG**.
6. Simpan gambar di laptop Anda, lalu tinggal *Insert -> Picture* di Microsoft Word!

### Cara 2: Menggunakan Visual Studio Code (VS Code)
1. Jika Anda membuka file markdown ini di VS Code, install ekstensi gratis bernama **"Markdown Preview Mermaid Support"** atau **"Mermaid Export"**.
2. Klik kanan pada blok diagram Mermaid -> pilih **"Export Diagram as PNG"**.
3. Gambar siap disalin ke laporan Anda.

### Cara 3: Screenshot Langsung dari Layar
1. Karena diagram di atas akan dirender secara visual dalam antarmuka obrolan ini (atau di preview Markdown GitHub/VS Code), Anda dapat langsung membesarkan tampilan layar.
2. Gunakan **Snipping Tool** (Windows + Shift + S) di Windows Anda untuk memotong gambar diagram dengan rapi.
3. Tempel (*Paste* / Ctrl + V) langsung ke dokumen Word laporan praktikum Anda.
