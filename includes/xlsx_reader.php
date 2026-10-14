<?php
// Pembaca XLSX minimal untuk sheet pertama.
// Mendukung tipe shared string (t="s") dan nilai numerik/teks sederhana.
function xlsx_read_rows($filePath) {
  $rows = [];
  // Jika ekstensi ZipArchive tidak tersedia, kembalikan kosong agar handler bisa memberi pesan yang tepat
  if (!class_exists('ZipArchive')) { return $rows; }
  $zip = new ZipArchive();
  if ($zip->open($filePath) !== true) { return $rows; }
  // Ambil shared strings jika ada
  $sharedStrings = [];
  $ssIndex = $zip->locateName('xl/sharedStrings.xml');
  if ($ssIndex !== false) {
    $xml = simplexml_load_string($zip->getFromIndex($ssIndex));
    if ($xml && isset($xml->si)) {
      foreach ($xml->si as $si) {
        // Gabungkan teks dalam t atau r
        if (isset($si->t)) {
          $sharedStrings[] = (string)$si->t;
        } elseif (isset($si->r)) {
          $buf = '';
          foreach ($si->r as $r) { $buf .= (string)$r->t; }
          $sharedStrings[] = $buf;
        } else {
          $sharedStrings[] = '';
        }
      }
    }
  }
  // Baca sheet1 (asumsi pertama)
  $sheetIndex = $zip->locateName('xl/worksheets/sheet1.xml');
  if ($sheetIndex === false) { $zip->close(); return $rows; }
  $sheet = simplexml_load_string($zip->getFromIndex($sheetIndex));
  $zip->close();
  if (!$sheet) { return $rows; }
  // Navigasi ke sheetData
  if (!isset($sheet->sheetData->row)) { return $rows; }
  foreach ($sheet->sheetData->row as $row) {
    $cells = [];
    foreach ($row->c as $c) {
      $t = (string)($c['t'] ?? '');
      $v = '';
      if (isset($c->v)) {
        $raw = (string)$c->v;
        if ($t === 's') {
          $idx = intval($raw);
          $v = $sharedStrings[$idx] ?? '';
        } else {
          $v = $raw;
        }
      } elseif (isset($c->is) && isset($c->is->t)) {
        $v = (string)$c->is->t;
      }
      $cells[] = $v;
    }
    // Hilangkan trailing kosong
    while (!empty($cells) && $cells[count($cells)-1] === '') { array_pop($cells); }
    $rows[] = $cells;
  }
  return $rows;
}
?>