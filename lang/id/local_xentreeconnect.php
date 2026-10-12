<?php

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Xentree Connect';
$string['statuspage'] = 'Status Xentree Connect';
$string['settingsheader'] = 'Pengaturan Xentree Connect';
$string['settingsheaderdesc'] = 'Nilai harus sama dengan integrasi yang terdaftar di Xentree. <a href="{$a}">Halaman status</a> menampilkan apa yang perlu didaftarkan di sana.';
$string['baseurl'] = 'URL dasar Xentree';
$string['baseurldesc'] = 'Tujuan pengguna diarahkan, misalnya https://my.xentree.ai atau domain khusus organisasi Anda. Wajib HTTPS.';
$string['baseurlinvalid'] = 'Masukkan URL HTTPS absolut tanpa kredensial, query string, atau fragment.';
$string['clientid'] = 'Client ID';
$string['clientiddesc'] = 'Client ID yang didaftarkan di Xentree untuk instans Moodle ini.';
$string['issuer'] = 'Issuer';
$string['issuerdesc'] = 'Issuer yang didaftarkan di Xentree. Biasanya alamat situs ini: {$a}';
$string['audience'] = 'Audience';
$string['audiencedesc'] = 'Nilai audience yang didaftarkan di Xentree. Umumnya xentree-connect.';
$string['organizationid'] = 'ID Organisasi';
$string['organizationiddesc'] = 'ID organisasi di Xentree yang memiliki integrasi ini.';
$string['kid'] = 'Key ID (kid)';
$string['kiddesc'] = 'Pengenal kunci penandatangan. Dikirim di header JWT dan harus sama dengan "kid" pada JWKS yang didaftarkan di Xentree.';
$string['privatekeypem'] = 'Kunci privat (PEM)';
$string['privatekeypemdesc'] = 'Kunci privat RSA untuk menandatangani assertion peluncuran. Kunci publik (JWKS) yang cocok ditampilkan di halaman status. Hanya untuk demo: kunci disimpan tanpa enkripsi di database Moodle.';
$string['privatekeyinvalid'] = 'Kunci privat tidak dapat dibaca. Tempel kunci privat RSA berformat PEM.';
$string['privatekeynotrsa'] = 'Hanya kunci privat RSA yang didukung.';
$string['problem_missing'] = 'belum diisi';
$string['problem_invalid'] = 'tidak valid';
$string['statusincomplete'] = 'Xentree Connect belum siap. Perbaiki pengaturan berikut:';
$string['statusready'] = 'Xentree Connect sudah terkonfigurasi.';
$string['opensettings'] = 'Buka pengaturan';
$string['registerheading'] = 'Daftarkan situs ini di Xentree';
$string['registerintro'] = 'Buat integrasi Connect di Xentree (pengaturan organisasi) dengan nilai berikut dan JWKS publik di bawah.';
$string['publicjwks'] = 'JWKS publik';
