<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Indonesian language strings for local_xentreeconnect.
 *
 * @package    local_xentreeconnect
 * @copyright  2026 PT Global Otomat Digital
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['audience'] = 'Audience';
$string['audiencedesc'] = 'Nilai audience yang didaftarkan di Xentree. Umumnya xentree-connect.';
$string['baseurl'] = 'URL dasar Xentree';
$string['baseurldesc'] = 'Tujuan pengguna diarahkan, misalnya https://my.xentree.ai atau domain khusus organisasi Anda. Wajib HTTPS.';
$string['baseurlinvalid'] = 'Masukkan URL HTTPS absolut tanpa kredensial, query string, atau fragment.';
$string['buttonheader'] = 'Tombol Connect';
$string['buttonheaderdesc'] = 'Pilih halaman tempat tombol muncul otomatis. Untuk menaruhnya di tempat lain (item menu, HTML khusus, tema), arahkan tautan ke <code>{$a}</code>.';
$string['buttonnotconfigured'] = 'Tombol Xentree Connect sudah diaktifkan tetapi plugin belum dikonfigurasi lengkap, sehingga pengguna tidak melihatnya.';
$string['buttontext'] = 'Teks tombol';
$string['buttontextdesc'] = 'Kosongkan untuk memakai teks bawaan, yang mengikuti bahasa pengguna.';
$string['clientid'] = 'Client ID';
$string['clientiddesc'] = 'Client ID yang didaftarkan di Xentree untuk instans Moodle ini.';
$string['continuetoxentree'] = 'Lanjut ke Xentree';
$string['credential_privatekeypem'] = 'Kunci privat (penandatanganan peluncuran)';
$string['credential_webhooksecret'] = 'Secret webhook';
$string['credential_webhooksecretprev'] = 'Secret webhook sebelumnya';
$string['credentialfiledesc'] = 'Path absolut file <strong>di luar web root</strong> yang berisi nilainya. Lebih disarankan daripada menyimpannya di database. Nilai yang dipaksakan di config.php lebih diutamakan daripada file, dan file lebih diutamakan daripada database. Contoh untuk config.php: <code>$CFG->forced_plugin_settings[\'local_xentreeconnect\'] = [\'privatekeypem\' => file_get_contents(\'/etc/moodle/xentree.pem\')];</code>';
$string['credentialnotset'] = 'belum diisi';
$string['credentialset'] = 'terisi';
$string['credentialsource'] = '{$a->name}: {$a->state} (sumber: {$a->source})';
$string['credentialstatus'] = 'Kredensial';
$string['dataheader'] = 'Aktivitas belajar dari Xentree';
$string['dataheaderdesc'] = 'Saat Xentree mengirim aktivitas belajar anggota yang setuju membagikannya, data itu hanya disimpan di tabel plugin. Data tidak pernah ditulis ke nilai atau penyelesaian kursus.';
$string['deleteallconfirm'] = 'Hapus semua aktivitas belajar yang diterima dari Xentree? Tindakan ini tidak dapat dibatalkan.';
$string['deletealldata'] = 'Hapus semua data Xentree';
$string['deletealldatadesc'] = 'Menghapus semua catatan aktivitas yang diterima dari Xentree. Gunakan saat integrasi dimatikan dan pemberitahuan pencabutan tidak lagi dikirim.';
$string['deletealldone'] = '{$a} catatan aktivitas dihapus.';
$string['events24h'] = 'Catatan aktivitas diterima';
$string['externalserviceheader'] = 'Layanan eksternal: Xentree';
$string['externalservicenotice'] = 'Plugin ini menghubungkan situs Moodle Anda ke Xentree, layanan pihak ketiga eksternal, dan tidak berfungsi tanpanya. Diperlukan organisasi Xentree dengan hak Connect dan integrasi yang didaftarkan di sana untuk situs ini (Client ID, Issuer, Audience, Organization ID, key ID, dan JWKS publik dari kunci Anda). Saat pengguna memulai alur Connect, plugin mengirim ID pengguna Moodle dan, jika valid, alamat email mereka ke Xentree. Jika Anda mendaftarkan webhook, Xentree mengirim peristiwa aktivitas belajar dan penarikan persetujuan untuk anggota yang setuju membagikannya, dan situs ini menyimpannya seperti dijelaskan pada bagian Privasi. Cara mendapat akses, biaya, dan kebutuhan akun: <a href="{$a}" target="_blank" rel="noopener">Persyaratan layanan eksternal</a> di README plugin.';
$string['filereason_empty'] = 'kosong';
$string['filereason_insidewebroot'] = 'berada di dalam web root';
$string['filereason_unreadable'] = 'tidak dapat dibaca';
$string['issuer'] = 'Issuer';
$string['issuerdesc'] = 'Issuer yang didaftarkan di Xentree. Biasanya alamat situs ini: {$a}';
$string['kid'] = 'Key ID (kid)';
$string['kiddesc'] = 'Pengenal kunci penandatangan. Dikirim di header JWT dan harus sama dengan "kid" pada JWKS yang didaftarkan di Xentree.';
$string['last24h'] = '24 jam terakhir';
$string['launchbutton'] = 'Hubungkan dengan Xentree';
$string['launchunavailable'] = 'Xentree Connect sedang tidak tersedia. Silakan hubungi administrator.';
$string['opensettings'] = 'Buka pengaturan';
$string['organizationid'] = 'ID Organisasi';
$string['organizationiddesc'] = 'ID organisasi di Xentree yang memiliki integrasi ini.';
$string['outcomedetail'] = 'Semua hasil';
$string['placement_course'] = 'Halaman kursus';
$string['placement_dashboard'] = 'Dasbor';
$string['placement_login'] = 'Halaman login';
$string['placements'] = 'Tampilkan tombol di';
$string['placementsdesc'] = 'Halaman tempat tombol ditambahkan otomatis. Tidak ada yang ditampilkan sampai Anda memilih setidaknya satu.';
$string['pluginname'] = 'Xentree Connect';
$string['privacy:metadata:event'] = 'Aktivitas belajar yang diterima dari Xentree untuk anggota yang setuju membagikannya. Satu baris per sesi belajar yang selesai. Disimpan hanya di tabel plugin ini dan tidak pernah ditulis ke nilai atau penyelesaian kursus.';
$string['privacy:metadata:event:deliveryid'] = 'Pengenal pengiriman tempat Xentree mengirim aktivitas, dipakai agar setiap pengiriman hanya disimpan sekali.';
$string['privacy:metadata:event:eventid'] = 'Pengenal yang diberikan Xentree pada peristiwa, dipakai agar setiap peristiwa hanya disimpan sekali.';
$string['privacy:metadata:event:eventtype'] = 'Jenis aktivitas, misalnya sesi belajar yang selesai.';
$string['privacy:metadata:event:occurredat'] = 'Waktu Xentree mencatat aktivitas.';
$string['privacy:metadata:event:receivedat'] = 'Waktu situs ini menerimanya.';
$string['privacy:metadata:event:sessionseconds'] = 'Lama sesi belajar dalam detik.';
$string['privacy:metadata:event:sessionstatus'] = 'Status sesi belajar saat berakhir.';
$string['privacy:metadata:event:userid'] = 'Pengguna Moodle pemilik aktivitas.';
$string['privacy:metadata:xentree'] = 'Saat pengguna memulai alur Connect (misalnya dengan tombol "Hubungkan dengan Xentree"), plugin ini mengirim data berikut ke Xentree, layanan belajar eksternal.';
$string['privacy:metadata:xentree:email'] = 'Alamat email pengguna, dikirim hanya jika alamatnya valid.';
$string['privacy:metadata:xentree:userid'] = 'ID pengguna Moodle, dikirim sebagai pengenal "moodle-user-<ID>".';
$string['privacy:path:activity'] = 'Aktivitas belajar Xentree';
$string['privatekeyfile'] = 'File kunci privat (opsional)';
$string['privatekeyinvalid'] = 'Kunci privat tidak dapat dibaca. Tempel kunci privat RSA berformat PEM.';
$string['privatekeynotrsa'] = 'Hanya kunci privat RSA yang didukung.';
$string['privatekeypem'] = 'Kunci privat (PEM)';
$string['privatekeypemdesc'] = 'Kunci privat RSA untuk menandatangani assertion peluncuran. Kunci publik (JWKS) yang cocok ditampilkan di halaman status. Hanya untuk demo: kunci disimpan tanpa enkripsi di database Moodle.';
$string['problem_invalid'] = 'tidak valid';
$string['problem_missing'] = 'belum diisi';
$string['publicjwks'] = 'JWKS publik';
$string['receiverurl'] = 'URL webhook yang didaftarkan di Xentree';
$string['receiverurlhelp'] = 'Harus dapat dijangkau dari internet lewat HTTPS port 443 dengan nama domain publik. Situs Moodle yang hanya dapat diakses dari intranet atau lewat alamat IP tidak didukung.';
$string['redirecting'] = 'Mengarahkan Anda ke Xentree…';
$string['registerheading'] = 'Daftarkan situs ini di Xentree';
$string['registerintro'] = 'Buat integrasi Connect di Xentree (pengaturan organisasi) dengan nilai berikut dan JWKS publik di bawah.';
$string['rejected24h'] = 'Permintaan ditolak (tanda tangan salah, waktu kedaluwarsa, payload rusak, terlalu besar)';
$string['retentiondays'] = 'Simpan aktivitas selama (hari)';
$string['retentiondaysdesc'] = 'Aktivitas yang lebih lama dari ini dihapus setiap malam. 0 menyimpannya sampai Anda menghapusnya sendiri. Bawaan sementara 365 hari: pastikan masa simpan dengan penasihat perlindungan data Anda.';
$string['retentiondaysinvalid'] = 'Masukkan bilangan bulat hari, 0 atau lebih.';
$string['retentionnow'] = 'Aktivitas disimpan selama {$a} hari.';
$string['settingsheader'] = 'Pengaturan Xentree Connect';
$string['settingsheaderdesc'] = 'Nilai harus sama dengan integrasi yang terdaftar di Xentree. <a href="{$a}">Halaman status</a> menampilkan apa yang perlu didaftarkan di sana.';
$string['source_configphp'] = 'config.php (setelan yang dipaksakan)';
$string['source_database'] = 'database Moodle';
$string['source_file'] = 'file di luar web root';
$string['source_none'] = 'tidak ada';
$string['statusincomplete'] = 'Xentree Connect belum siap. Perbaiki pengaturan berikut:';
$string['statuspage'] = 'Status Xentree Connect';
$string['statusready'] = 'Xentree Connect sudah terkonfigurasi.';
$string['statusreceiving'] = 'Menerima dari Xentree';
$string['task_purge'] = 'Hapus aktivitas Xentree yang lama';
$string['testlaunch'] = 'Uji peluncuran';
$string['warning_filefailed'] = '{$a->name}: file yang dikonfigurasi tidak dapat dipakai ({$a->reason}).';
$string['warning_keyindatabase'] = 'Kunci privat disimpan di database Moodle. Pindahkan ke config.php atau file di luar web root.';
$string['warning_retentionzero'] = 'Penghapusan otomatis nonaktif (retensi 0): aktivitas dari Xentree disimpan sampai Anda menghapusnya. Tetapkan masa simpan setelah memastikannya dengan penasihat perlindungan data Anda.';
$string['warning_secretindatabase'] = 'Secret webhook disimpan di database Moodle. Sebaiknya gunakan config.php atau file di luar web root.';
$string['warning_secretmissing'] = 'Secret webhook belum diisi: pengiriman dari Xentree ditolak dengan HTTP 503 sampai Anda mengisinya.';
$string['webhookheader'] = 'Menerima aktivitas dari Xentree';
$string['webhookheaderdesc'] = 'Daftarkan alamat ini sebagai URL webhook di Xentree: <code>{$a}</code>. Alamat harus dapat dijangkau dari internet lewat HTTPS port 443 dengan nama domain publik, dan memakai nama host yang sama dengan alamat situs ini.';
$string['webhooksecret'] = 'Secret webhook';
$string['webhooksecretdesc'] = 'Secret penandatanganan yang ditampilkan Xentree saat webhook dibuat. Tidak ditampilkan lagi setelah disimpan.';
$string['webhooksecretfile'] = 'File secret webhook (opsional)';
$string['webhooksecretprev'] = 'Secret webhook sebelumnya (saat rotasi)';
$string['webhooksecretprevdesc'] = 'Setelah secret dirotasi di Xentree, secret lama tetap berlaku di sana selama 24 jam. Isi di sini agar pengiriman yang ditandatangani salah satu secret tetap diterima, lalu kosongkan.';
$string['webhooksecretprevfile'] = 'File secret webhook sebelumnya (opsional)';
