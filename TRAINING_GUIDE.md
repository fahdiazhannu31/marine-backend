# Panduan Operasional NAMA Marine System
## Training Guide — Admin, Captain, GRO, ABK, Staff

---

## ROLE & AKSES

| Role | Akses System | Tugas Utama |
|------|-------------|-------------|
| **Admin** | Full access — dashboard, manifest, crew, booking | Upload manifest, manage semua data |
| **Captain** | Scan QR check-in (via PDA/HP) | Validasi penumpang naik kapal |
| **GRO** | Scan QR check-in (via HP) | Assist check-in di dermaga |
| **ABK** | Scan QR check-in (opsional) | Bantu boarding |
| **Staff** | Lihat manifest (opsional) | Operasional darat |
| **Lead Group** | Scan QR grup → download boarding pass | Self-service cetak boarding pass |

---

## 1. FLOW ADMIN — Hari Trip

```mermaid
flowchart TD
    A([🖥️ Login Admin]) --> B[Dashboard\nCek jadwal hari ini]
    B --> C[Buka Manifest Upload]
    C --> D[Upload Excel Manifest\nPilih schedule + kapal]
    D --> E{Captain auto-assign?}
    E -->|✅ Ditemukan| F[Captain di-assign\notomatis ke schedule]
    E -->|❌ Tidak ditemukan| G[Toast warning:\nTambah captain dulu\ndi halaman Crew]
    G --> H[Buka Crew Management\nTambah captain baru]
    H --> I[Assign manual\nke schedule]
    F --> J[QR grup ter-generate\notomatis]
    I --> J
    J --> K{Kirim QR ke grup?}
    K -->|Ya| L[Klik Send All Group QR\nEmail ter-blast otomatis]
    K -->|Manual| M[Klik View QR Codes\nDownload per grup\nKirim via WhatsApp]
    L --> N[Cetak Boarding Pass\nuntuk semua / filtered]
    M --> N
    N --> O[Print PDF\nBagikan ke penumpang]
    O --> P([✅ Siap berangkat])
```

---

## 2. FLOW LEAD GROUP — Ambil Boarding Pass Sendiri

```mermaid
flowchart TD
    A([📱 Terima QR via Email/WA]) --> B[Scan QR dengan kamera HP]
    B --> C[Browser buka halaman\nnamamarine.cloud/boarding-pass?t=...]
    C --> D[Halaman tampil:\nNama grup + daftar anggota]
    D --> E{Pilih aksi}
    E -->|Per orang| F[Tap 🎫 Unduh Boarding Pass\nPDF terbuka di tab baru]
    E -->|Semua sekaligus| G[Tap Cetak Semua\nSemua PDF terbuka]
    F --> H[Screenshot / Print / Download]
    G --> H
    H --> I([✅ Boarding pass siap\ndibawa ke dermaga])
```

---

## 3. FLOW CHECK-IN DI DERMAGA

```mermaid
flowchart TD
    A([🏁 Penumpang tiba\ndi dermaga]) --> B{Punya boarding pass?}
    B -->|Ya| C[Tunjukkan BP ke petugas]
    B -->|Belum| D[Hubungi Lead Group\natau Admin]
    C --> E[Petugas scan QR\ndi boarding pass]
    E --> F{Tipe QR?}
    F -->|QR Grup| G[System cek:\nsudah check-in hari ini?]
    F -->|QR Individual| H[Langsung check-in\npenumpang bersangkutan]
    G -->|Belum| I[✅ Check-in berhasil\nSemua anggota grup tercatat]
    G -->|Sudah hari ini| J[❌ Blocked\n'Sudah check-in hari ini'\nTunggu besok untuk pulang]
    G -->|Besok = return date| K[✅ Check-in RETURN\nbadge 🌙 tampil]
    H --> L[Toast: Penumpang checked in]
    I --> L
    K --> L
    L --> M([⛵ Penumpang naik kapal])
```

---

## 4. FLOW CREW CHECK-IN

```mermaid
flowchart TD
    A([👨‍✈️ Crew tiba\ndi dermaga]) --> B[Tunjukkan QR ID Card]
    B --> C[Admin / GRO scan\nQR menggunakan Check-in Scanner]
    C --> D{Sudah check-in\nhari ini?}
    D -->|Belum| E[✅ Auto check-in\nNama + role tampil]
    D -->|Sudah| F[ℹ️ Info: sudah check-in\nTampil waktu check-in sebelumnya]
    E --> G[Log tercatat\ndi crew_checkins]
    F --> G
    G --> H([✅ Selesai])
```

---

## 5. FLOW ADMIN — Kelola Data Crew

```mermaid
flowchart TD
    A([Buka Crew Management]) --> B{Aksi?}
    B -->|Tambah| C[Klik + Tambah Crew\nIsi nama, role, HP, NIK]
    B -->|Lihat Jadwal| D[Tab Assignments\nKalender bulanan]
    B -->|Check-in Manual| E[Tab QR Check-in\nScan atau input kode]
    C --> F[QR permanent\ndigenerate otomatis]
    F --> G[Print QR ID Card\nLaminasi untuk crew]
    G --> H([✅ Crew siap bertugas])
    D --> I[Klik tanggal\nyang ada dot warna]
    I --> J[Modal detail:\nDaftar crew + kapal\n+ status check-in]
    J --> K([✅ Pantau assignment])
    E --> L[Scan QR ID crew]
    L --> M[Tampil info crew\n+ tombol Check-In]
    M --> N([✅ Check-in tercatat])
```

---

## 6. FLOW MANIFEST FINAL & EXPORT

```mermaid
flowchart TD
    A([Buka Manifest Final]) --> B[Pilih upload dari dropdown]
    B --> C{View mode?}
    C -->|☀️ Departure| D[Tampil semua penumpang\nper kategori KET]
    C -->|🌙 Return| E[Filter OVERNIGHT saja\nArah dibalik]
    D --> F{Aksi?}
    E --> F
    F -->|Print| G[🖨️ Cetak manifest\nFormat A4 / thermal]
    F -->|Export| H[📥 Download Excel\nFormat manifest resmi]
    G --> I([✅ Dokumen siap])
    H --> I
```

---

## 7. ALUR HARIAN LENGKAP (TIMELINE)

```
06:00  Admin upload manifest Excel
       → Captain auto-assign ke schedule
       → QR grup ter-generate
       ↓
06:30  Admin kirim QR ke lead group (email / WA)
       ↓
07:00  Lead group scan QR → download boarding pass semua anggota
       ↓
07:30  Penumpang tiba di dermaga
       → GRO / Captain scan boarding pass
       → Check-in departure tercatat
       ↓
08:00  Kapal berangkat
       ↓
---  Overnight trip ---
       ↓
Next day 08:00  Penumpang scan QR yang sama
                → Sistem deteksi: beda tanggal = return trip
                → Check-in return tercatat (badge 🌙 RETURN)
       ↓
09:00  Kapal kembali ke asal
```

---

## 8. PANDUAN SCANNER (Captain / GRO)

### Pakai HP / Tablet (Camera Mode)
1. Buka `namamarine.cloud/admin/checkin`
2. Login dengan akun admin
3. Tap **Start** di QR Scanner
4. Arahkan kamera ke QR boarding pass penumpang
5. Sistem auto check-in — dengar bunyi *beep* tanda berhasil

### Pakai PDA Scanner (Hardware Mode)
1. Buka halaman check-in
2. Klik tombol **PDA Scanner** (kanan atas)
3. Input field sudah fokus otomatis
4. Arahkan PDA ke QR — scanner langsung input kode
5. Sistem auto check-in tanpa perlu klik apapun

### Troubleshooting
| Masalah | Solusi |
|---------|--------|
| QR tidak terbaca | Pastikan pencahayaan cukup, coba input manual |
| "Sudah check-in hari ini" | Normal — penumpang sudah scan sebelumnya |
| "Ticket not found" | QR rusak/salah — cek manual di manifest |
| Kamera tidak aktif | Izinkan akses kamera di browser |

---

## 9. PANDUAN LEAD GROUP

### Cara Ambil Boarding Pass
1. Cek email / WhatsApp dari NAMA Marine
2. Ada QR code dengan nama grup kamu
3. Scan QR menggunakan kamera HP
4. Browser otomatis buka halaman boarding pass
5. Tap **🎫 Unduh Boarding Pass** per anggota
6. Atau tap **Cetak Semua** untuk semua anggota sekaligus
7. Screenshot / print / simpan PDF

### Catatan Penting
- Satu QR untuk **semua anggota grup** — cukup lead group yang pegang
- QR berlaku untuk **departure dan return** (overnight)
- Setelah scan departure → QR tidak bisa scan lagi di hari yang sama
- Besok (return day) → scan QR yang sama untuk boarding pulang

---

## 10. KONTAK & ESKALASI

| Masalah | Hubungi |
|---------|---------|
| Penumpang tidak ada di manifest | Admin NAMA Marine |
| QR tidak bisa dibuka | Admin — minta resend QR |
| Kursi salah / ganda | Admin — switch seat di manifest |
| Kapal overbooked | Captain → Admin segera |

---

*Dokumen ini dibuat otomatis oleh sistem NAMA Marine.*
*Last updated: September 2026*
