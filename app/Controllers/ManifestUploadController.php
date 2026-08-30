<?php

namespace App\Controllers;

use App\Models\ManifestUploadModel;
use App\Models\ManifestTicketModel;
use App\Models\ManifestBaggageModel;
use App\Models\BoatModel;
use App\Models\ScheduleModel;

/**
 * ManifestUploadController
 *
 * Endpoints:
 *  POST   /api/admin/manifest/upload          — parse Excel, auto-assign seats, persist
 *  GET    /api/admin/manifest/uploads          — list uploads (optionally filter by schedule_id)
 *  GET    /api/admin/manifest/uploads/{id}     — single upload detail + tickets
 *  GET    /api/admin/manifest/tickets/{id}     — all tickets for an upload
 *  POST   /api/admin/manifest/uploads/{id}/confirm — confirm draft → confirmed
 *  DELETE /api/admin/manifest/uploads/{id}     — delete upload + tickets + baggage
 *  GET    /api/admin/manifest/baggage/{uploadId}   — list baggage for upload
 *  POST   /api/admin/manifest/baggage           — add baggage item
 *  PUT    /api/admin/manifest/baggage/{id}      — edit baggage item
 *  DELETE /api/admin/manifest/baggage/{id}      — delete baggage item
 *  POST   /api/admin/manifest/baggage/{id}/mark-printed — mark guest tag printed
 *  GET    /api/admin/manifest/boats             — list boats with captain + crew
 *  PUT    /api/admin/manifest/boats/{id}/crew   — update captain + abk_names on a boat
 */
class ManifestUploadController extends ApiController
{

    // ═══════════════════════════════════════════
    // EXCEL PARSING — native ZipArchive + SimpleXML
    // No PhpSpreadsheet dependency needed.
    // ═══════════════════════════════════════════

    /**
     * Parse an .xlsx file and return every non-empty row as an array of
     * cell values (string). Reads the first worksheet.
     *
     * Strategy: strip all namespace declarations from the XML before loading
     * into SimpleXML so that elements are accessible by plain local-name
     * without any XPath namespace prefix juggling.
     *
     * @param string $filePath  Absolute path to the .xlsx file.
     * @return array[]          [ [cellA, cellB, ...], ... ]
     */
    private function parseXlsx(string $filePath): array
    {
        if (!class_exists('ZipArchive')) {
            throw new \RuntimeException('ZipArchive extension is not available.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new \RuntimeException('Cannot open xlsx file as zip.');
        }

        // ── Helper: strip XML namespace declarations so SimpleXML can
        //   access elements by local name without prefix tricks ──────
        $stripNs = function (string $xml): string {
            // Remove xmlns="..." and xmlns:prefix="..." attributes
            $xml = preg_replace('/\s+xmlns(?::\w+)?="[^"]*"/', '', $xml);
            // Remove namespace prefixes from element/attribute names: <ns:foo> → <foo>
            $xml = preg_replace('/<(\/?)\w+:/', '<$1', $xml);
            // Remove namespace prefixes from attributes: ns:attr="x" → attr="x"
            $xml = preg_replace('/\s\w+:(\w+)="/', ' $1="', $xml);
            return $xml;
        };

        // ── Load shared strings ──────────────────────────────────────
        $sharedStrings = [];
        $ssXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssXml !== false) {
            $ss = simplexml_load_string($stripNs($ssXml), 'SimpleXMLElement', LIBXML_NOCDATA);
            if ($ss !== false) {
                foreach ($ss->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string) $si->t;
                    } else {
                        $text = '';
                        foreach ($si->r as $r) {
                            $text .= (string) $r->t;
                        }
                        $sharedStrings[] = $text;
                    }
                }
            }
        }

        // ── Locate the first worksheet ───────────────────────────────
        // Try sheet1.xml first (most common), then resolve via workbook
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            // Resolve the actual first sheet name from workbook.xml
            $wbXml = $zip->getFromName('xl/workbook.xml');
            $sheetName = 'sheet1';
            if ($wbXml !== false) {
                $wb = simplexml_load_string($stripNs($wbXml), 'SimpleXMLElement', LIBXML_NOCDATA);
                if ($wb !== false) {
                    // <sheets><sheet name="..." .../></sheets>
                    $sheetsNode = $wb->sheets ?? null;
                    if ($sheetsNode) {
                        $first = $sheetsNode->sheet[0] ?? null;
                        if ($first) {
                            $name = (string) ($first['name'] ?? '');
                            if ($name !== '') {
                                $sheetName = $name;
                            }
                        }
                    }
                }
            }
            $sheetXml = $zip->getFromName("xl/worksheets/{$sheetName}.xml");
        }
        $zip->close();

        if ($sheetXml === false) {
            throw new \RuntimeException('Could not find worksheet XML in the xlsx file.');
        }

        // ── Parse rows ──────────────────────────────────────────────
        $ws = simplexml_load_string($stripNs($sheetXml), 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($ws === false) {
            throw new \RuntimeException('Failed to parse worksheet XML.');
        }

        // Column reference (e.g. "AB") → 0-based index
        $colIdx = function (string $ref): int {
            preg_match('/^([A-Z]+)/', strtoupper($ref), $m);
            if (empty($m[1])) return 0;
            $col = 0;
            foreach (str_split($m[1]) as $ch) {
                $col = $col * 26 + (ord($ch) - 64);
            }
            return $col - 1;
        };

        $rows = [];

        // Navigate: <worksheet><sheetData><row ...><c ...>
        $sheetData = $ws->sheetData ?? null;
        if ($sheetData === null) {
            // Some generators nest differently — try direct children
            foreach ($ws->children() as $child) {
                if ($child->getName() === 'sheetData') {
                    $sheetData = $child;
                    break;
                }
            }
        }
        if ($sheetData === null) {
            return [];
        }

        foreach ($sheetData->row as $row) {
            $rowNum = (int) ($row['r'] ?? 0);
            $cells  = [];

            foreach ($row->c as $cell) {
                $ref   = (string) ($cell['r'] ?? '');
                $type  = (string) ($cell['t'] ?? '');
                $idx   = $colIdx($ref);
                $v     = isset($cell->v) ? trim((string) $cell->v) : '';

                if ($type === 's') {
                    $v = $sharedStrings[(int) $v] ?? '';
                } elseif ($type === 'inlineStr') {
                    $v = isset($cell->is->t) ? (string) $cell->is->t : '';
                }

                // Pad sparse cells with empty strings so indices are contiguous
                while (count($cells) < $idx) {
                    $cells[] = '';
                }
                $cells[$idx] = trim((string) $v);
            }

            if (!empty(array_filter($cells, fn ($c) => $c !== ''))) {
                $rows[$rowNum] = $cells;
            }
        }

        return array_values($rows);
    }

    // ═══════════════════════════════════════════
    // SEAT AUTO-ASSIGNMENT (adjacent per group)
    // ═══════════════════════════════════════════

    /**
     * Assign seats to a list of passengers grouped by group_name.
     * Rules:
     *  - Groups sit together (adjacent seats within the same row).
     *  - Seat order follows the natural order from the `seat` table
     *    (sorted by seat_number ASC).
     *  - Already-booked seats (status='booked') are skipped.
     *  - If a full group can't fit in one row it wraps to the next
     *    available contiguous block.
     *
     * @param int     $boatId
     * @param array[] $groups  [ ['group_name'=>..., 'passengers'=>[ticketRows]] ]
     * @return array           Map of ticket->seq_no => ['seat_id', 'seat_number']
     */
    private function assignSeats(int $boatId, array $groups): array
    {
        $db = \Config\Database::connect();

        // Fetch all available seats for this boat, ordered naturally
        $availableSeats = $db->table('seat')
            ->where('boat_id', $boatId)
            ->where('status', 'available')
            ->orderBy('CAST(seat_number AS UNSIGNED)', 'ASC')   // numeric prefix
            ->orderBy('seat_number', 'ASC')                     // then alpha suffix
            ->get()
            ->getResultArray();

        // DEBUG: Log available seats count
        log_message('info', "=== SEAT ASSIGNMENT DEBUG ===");
        log_message('info', "Boat ID: {$boatId}");
        log_message('info', "Available seats found: " . count($availableSeats));
        log_message('info', "Groups to assign: " . count($groups));

        if (empty($availableSeats)) {
            log_message('warning', "No available seats found for boat {$boatId}!");
            return [];
        }

        // Build a pool: [ {id, seat_number, row_label} ]
        // Row label = leading digits of seat_number (e.g. "3" from "3A")
        $pool = array_map(function ($s) {
            preg_match('/^(\d+)/', $s['seat_number'], $m);
            return [
                'id'          => (int) $s['id'],
                'seat_number' => $s['seat_number'],
                'row'         => $m[1] ?? '0',
            ];
        }, $availableSeats);

        // Group pool by row so we can check adjacency
        $byRow = [];
        foreach ($pool as $s) {
            $byRow[$s['row']][] = $s;
        }

        $assignments = [];   // seq_key => {seat_id, seat_number}
        $usedIds     = [];   // seat IDs already assigned this run

        /**
         * Find the first contiguous block of $need free seats across rows.
         * Returns array of seat nodes, or [].
         */
        $findBlock = function (int $need) use (&$byRow, &$usedIds): array {
            foreach ($byRow as $rowSeats) {
                $freeInRow = array_values(
                    array_filter($rowSeats, fn ($s) => !in_array($s['id'], $usedIds, true))
                );
                if (count($freeInRow) >= $need) {
                    return array_slice($freeInRow, 0, $need);
                }
            }

            // Could not fit in one row — take from the pool linearly
            $free = [];
            foreach ($byRow as $rowSeats) {
                foreach ($rowSeats as $s) {
                    if (!in_array($s['id'], $usedIds, true)) {
                        $free[] = $s;
                        if (count($free) === $need) {
                            return $free;
                        }
                    }
                }
            }
            return $free; // might be fewer than $need if boat is nearly full
        };

        foreach ($groups as $group) {
            $passengers = $group['passengers'] ?? [];
            $need       = count($passengers);
            if ($need === 0) {
                continue;
            }

            log_message('info', "Assigning group '{$group['group_name']}' - needs {$need} seats");

            $block = $findBlock($need);
            
            log_message('info', "Found block with " . count($block) . " seats");
            
            foreach ($passengers as $i => $ticket) {
                if (!isset($block[$i])) {
                    log_message('warning', "No seat available for passenger {$i} in group {$group['group_name']}");
                    break; // no seat left
                }
                $seat = $block[$i];
                $usedIds[] = $seat['id'];
                $assignments[$ticket['_key']] = [
                    'seat_id'     => $seat['id'],
                    'seat_number' => $seat['seat_number'],
                ];
                log_message('info', "Assigned seat {$seat['seat_number']} (ID: {$seat['id']}) to passenger seq {$ticket['seq_no']}");
            }
        }

        log_message('info', "Total seats assigned: " . count($assignments));
        log_message('info', "=== END SEAT ASSIGNMENT DEBUG ===");

        return $assignments;
    }

    // ═══════════════════════════════════════════
    // MANIFEST PARSING HELPERS
    // ═══════════════════════════════════════════

    /**
     * Detect which row in the parsed xlsx contains the data header
     * (the row with "NO", "KET", "NAMA" style headers).
     * Returns the 0-based row index, or -1 if not found.
     */
    private function findHeaderRow(array $rows): int
    {
        foreach ($rows as $i => $row) {
            $flat = implode(' ', array_map('strtoupper', $row));
            // The manifest header has "NO" and "NAMA" in close proximity
            if (
                (str_contains($flat, 'NO') && str_contains($flat, 'NAMA')) ||
                (str_contains($flat, 'KET') && str_contains($flat, 'GRUP'))
            ) {
                return $i;
            }
        }
        return -1;
    }

    /**
     * Parse metadata from the pre-header rows of a NAMA Marine manifest.
     *
     * The Excel looks like (0-indexed rows before the column-header row):
     *   row 0: "MANIFEST TO TICKET" / "MANIFEST PENUMPANG KEBERANGKATAN"
     *   row 1: [date string]
     *   row 2: "KAPAL" <boat> ... "ASAL" <origin> ... "NAHKODA" <captain>  |  OVERNIGHT <n>
     *   row 3: "GT"          ... "TUJUAN" <dest>                           |  DAY TRIP  <n>
     *   row 4: "BENDERA"     ...                    "CREW"   <crew>        |  STAFF     <n>
     *   row 5:                                      "GRO"    <gro>         |  FOC       <n>
     *   row 6:                                                              |  VENDOR    <n>
     *
     * Returns an array with keys:
     *   origin, destination, captain_name, crew_names, gro_name,
     *   overnight_count, daytrip_count, staff_count, foc_count, vendor_count
     *
     * Any field not found is returned as null / 0.
     *
     * @param array[] $rows  All rows BEFORE the column-header row (0-indexed).
     */
    private function parseManifestHeader(array $preHeaderRows): array
    {
        $meta = [
            'origin'          => null,
            'destination'     => null,
            'captain_name'    => null,
            'crew_names'      => null,
            'gro_name'        => null,
            'overnight_count' => 0,
            'daytrip_count'   => 0,
            'staff_count'     => 0,
            'foc_count'       => 0,
            'vendor_count'    => 0,
        ];

        // Flatten every pre-header row into one big "key→value" scan.
        // We look for cells that match known labels and grab the next non-empty cell.
        $labelMap = [
            'ASAL'       => 'origin',
            'TUJUAN'     => 'destination',
            'NAHKODA'    => 'captain_name',
            'CREW'       => 'crew_names',
            'GRO'        => 'gro_name',
        ];

        // Count labels: the *value* cell is the next non-empty cell after the label
        $countMap = [
            'OVERNIGHT'        => 'overnight_count',
            'DAY TRIP'         => 'daytrip_count',
            'DAYTRIP'          => 'daytrip_count',
            'STAFF'            => 'staff_count',
            'FOC'              => 'foc_count',
            'VENDOR'           => 'vendor_count',
        ];

        foreach ($preHeaderRows as $row) {
            if (empty($row)) continue;
            $cells = array_values($row);   // ensure 0-based index
            $n     = count($cells);

            for ($i = 0; $i < $n; $i++) {
                $cell = strtoupper(trim((string)($cells[$i] ?? '')));
                if ($cell === '') continue;

                // ── Text labels (ASAL, TUJUAN, NAHKODA, CREW, GRO) ──
                foreach ($labelMap as $keyword => $metaKey) {
                    if ($cell === $keyword) {
                        // next non-empty cell in the same row
                        for ($j = $i + 1; $j < $n; $j++) {
                            $val = trim((string)($cells[$j] ?? ''));
                            if ($val !== '') {
                                $meta[$metaKey] = $val;
                                break;
                            }
                        }
                    }
                }

                // ── Count labels (OVERNIGHT, DAY TRIP, STAFF, FOC, VENDOR) ──
                // Some appear as a single cell "OVERNIGHT" and the count is the
                // next non-empty cell; others may be "OVERNIGHT 137" in one cell.
                foreach ($countMap as $keyword => $metaKey) {
                    // Case 1: cell contains "OVERNIGHT 137" or "DAY TRIP   41"
                    if (preg_match('/^' . preg_quote($keyword, '/') . '\s+(\d+)$/i', $cell, $m)) {
                        $meta[$metaKey] = (int) $m[1];
                        break;
                    }
                    // Case 2: cell is exactly the keyword, value in next non-empty cell
                    if ($cell === $keyword) {
                        for ($j = $i + 1; $j < $n; $j++) {
                            $val = trim((string)($cells[$j] ?? ''));
                            if ($val !== '' && is_numeric($val)) {
                                $meta[$metaKey] = (int) $val;
                                break;
                            }
                            // also allow "137 PAX" style
                            if (preg_match('/^(\d+)/', $val, $m2)) {
                                $meta[$metaKey] = (int) $m2[1];
                                break;
                            }
                        }
                        break;
                    }
                }
            }
        }

        return $meta;
    }

    /**
     * Map column header text to a semantic key.
     * Returns array: [colIndex => 'key']
     */
    private function mapColumns(array $headerRow): array
    {
        $map = [
            'NO'        => 'seq_no',
            'KET'       => 'ket',
            'NAMA'      => 'passenger_name',
            'GRUP'      => 'group_name',
            'AGENT'     => 'agent',
            'PACKAGE'   => 'package',
            'PAX'       => 'pax_count',
            'NOTES'     => 'notes',
            'UMUR'      => 'age',
            'GENDER'    => 'gender',
            'DOMISILI'  => 'domicile',
            'ID'        => 'id_passport',
            'PASPORT'   => 'id_passport',
            'PASSPORT'  => 'id_passport',
        ];

        $result = [];
        foreach ($headerRow as $i => $cell) {
            $upper = strtoupper(trim($cell));
            foreach ($map as $keyword => $key) {
                if (str_contains($upper, $keyword)) {
                    $result[$i] = $key;
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * Convert a parsed row array + column map into a structured ticket array.
     */
    private function rowToTicket(array $row, array $colMap, int $seq): array
    {
        $ticket = [
            'seq_no'         => $seq,
            'ket'            => null,
            'passenger_name' => '',
            'group_name'     => null,
            'agent'          => null,
            'package'        => null,
            'pax_count'      => 1,
            'notes'          => null,
            'age'            => null,
            'gender'         => null,
            'domicile'       => null,
            'id_passport'    => null,
        ];

        foreach ($colMap as $colIdx => $key) {
            $val = trim($row[$colIdx] ?? '');
            if ($val === '' || strtoupper($val) === 'N/A') {
                continue;
            }
            if ($key === 'pax_count') {
                $ticket[$key] = max(1, (int) $val);
            } else {
                $ticket[$key] = $val;
            }
        }

        return $ticket;
    }

    // ═══════════════════════════════════════════
    // ENDPOINT: POST /api/admin/manifest/upload
    // ═══════════════════════════════════════════

    /**
     * Accept a multipart upload (xlsx file + schedule/boat meta),
     * parse the manifest, auto-assign seats, and persist everything.
     *
     * Form fields:
     *   file          — the .xlsx manifest file (required)
     *   schedule_id   — schedule.id (required)
     *   direction     — DEPARTURE | RETURN (default DEPARTURE)
     *   captain_name  — optional, saved on boat row
     *   abk_names     — optional JSON array string, saved on boat row
     *   notes         — optional notes for this upload
     */
    public function upload()
    {
        if (!$this->isAdminUser()) {
            return $this->jsonResponse(['error' => 'Forbidden.'], 403);
        }

        // ── 1. Validate the uploaded file ────────────────────────────
        $file = $this->request->getFile('file');
        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return $this->jsonResponse(['error' => 'No valid file attached. Please upload an .xlsx file.'], 422);
        }

        $ext = strtolower($file->getClientExtension());
        if ($ext !== 'xlsx') {
            return $this->jsonResponse(['error' => 'Only .xlsx files are supported.'], 422);
        }
        if ($file->getSizeByUnit('mb') > 10) {
            return $this->jsonResponse(['error' => 'File too large (max 10 MB).'], 422);
        }

        // ── 2. Resolve schedule + boat ────────────────────────────────
        $scheduleId   = (int) $this->request->getPost('schedule_id');
        $direction    = $this->request->getPost('direction') ?? 'DEPARTURE';
        $captainName  = trim($this->request->getPost('captain_name') ?? '');
        $abkNamesRaw  = trim($this->request->getPost('abk_names') ?? '');
        $notes        = trim($this->request->getPost('notes') ?? '');

        if (!$scheduleId) {
            return $this->jsonResponse(['error' => 'schedule_id is required.'], 422);
        }

        $db = \Config\Database::connect();
        $schedule = $db->table('schedule')
            ->select('schedule.id, schedule.type, schedule.date, schedule.boat_id, boat.boat_name, boat.capacity')
            ->join('boat', 'boat.id = schedule.boat_id', 'left')
            ->where('schedule.id', $scheduleId)
            ->get()
            ->getFirstRow('array');

        if (!$schedule) {
            return $this->jsonResponse(['error' => 'Schedule not found.'], 404);
        }

        $boatId   = (int) $schedule['boat_id'];
        $tripDate = substr($schedule['date'], 0, 10);

        // ── 3. Update captain / ABK on boat if provided ───────────────
        if ($boatId) {
            $boatUpdate = [];
            if ($captainName !== '') {
                $boatUpdate['captain_name'] = $captainName;
            }
            if ($abkNamesRaw !== '') {
                // Accept either plain text (newline-separated) or JSON array
                if ($abkNamesRaw[0] === '[') {
                    $boatUpdate['abk_names'] = $abkNamesRaw;
                } else {
                    $names = array_filter(array_map('trim', explode("\n", $abkNamesRaw)));
                    $boatUpdate['abk_names'] = json_encode(array_values($names));
                }
            }
            if (!empty($boatUpdate)) {
                $db->table('boat')->update($boatUpdate, ['id' => $boatId]);
            }
        }

        // ── 4. Save file ─────────────────────────────────────────────
        $uploadDir = FCPATH . 'uploads/manifests/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $newFileName = $file->getRandomName();
        $file->move($uploadDir, $newFileName);
        $savedPath = $uploadDir . $newFileName;

        // ── 5. Parse xlsx ────────────────────────────────────────────
        try {
            $allRows = $this->parseXlsx($savedPath);
        } catch (\Exception $e) {
            @unlink($savedPath);
            return $this->jsonResponse(['error' => 'Failed to parse xlsx: ' . $e->getMessage()], 422);
        }

        if (count($allRows) < 2) {
            @unlink($savedPath);
            return $this->jsonResponse(['error' => 'Excel file appears empty or unreadable.'], 422);
        }

        // Find the header row (row with "NO", "NAMA", etc.)
        $headerIdx = $this->findHeaderRow($allRows);
        if ($headerIdx < 0) {
            // Fallback: assume first row is header
            $headerIdx = 0;
        }

        // ── 5b. Parse metadata from pre-header rows ──────────────────
        // Rows 0..(headerIdx-1) contain boat name, origin, destination,
        // nahkoda, crew, GRO, and category counts (OVERNIGHT/DAY TRIP/STAFF/FOC/VENDOR)
        $preHeaderRows = array_slice($allRows, 0, $headerIdx);
        $headerMeta    = $this->parseManifestHeader($preHeaderRows);

        // Override captain / abk from form fields if they were explicitly provided,
        // otherwise fall back to what we found in the Excel header.
        if ($captainName === '' && !empty($headerMeta['captain_name'])) {
            $captainName = $headerMeta['captain_name'];
            // Also save to boat table
            if ($boatId) {
                $db->table('boat')->update(['captain_name' => $captainName], ['id' => $boatId]);
            }
        }
        if ($abkNamesRaw === '' && !empty($headerMeta['crew_names'])) {
            $abkNamesRaw = $headerMeta['crew_names'];
            if ($boatId) {
                $db->table('boat')->update(
                    ['abk_names' => json_encode([$headerMeta['crew_names']])],
                    ['id' => $boatId]
                );
            }
        }

        $colMap   = $this->mapColumns($allRows[$headerIdx]);
        $dataRows = array_slice($allRows, $headerIdx + 1);

        // ── 6. Build ticket structs ───────────────────────────────────
        // KET values that are real passenger categories (not summary markers)
        $validKets = ['OVERNIGHT', 'DAY TRIP', 'DAYTRIP', 'STAFF', 'FOC', 'VENDOR',
                      'OVERNIGHT CANCEL', 'DAY TRIP CANCEL', 'TRANSPORT ONLY'];

        $ticketRows   = [];
        $currentGroup = null;
        $currentKet   = null;
        $seq = 0;

        foreach ($dataRows as $row) {
            $ticket = $this->rowToTicket($row, $colMap, ++$seq);

            $rawName = trim($ticket['passenger_name'] ?? '');
            $rawKet  = strtoupper(trim($ticket['ket'] ?? ''));

            // ── Detect section-header rows ────────────────────────────
            // e.g. "MENGINAP   137 PAX" or "DAY TRIP   41 PAX"
            // These rows carry a PAX count but no individual passenger name.
            if (preg_match('/\d+\s*PAX\s*$/i', $rawName)) {
                // Extract ket from this section header to carry forward
                // e.g. "MENGINAP" → OVERNIGHT, "DAY TRIP" → DAY TRIP
                if (str_contains(strtoupper($rawName), 'MENGINAP')) {
                    $currentKet = 'OVERNIGHT';
                } elseif (str_contains(strtoupper($rawName), 'DAY TRIP') || str_contains(strtoupper($rawName), 'DAYTRIP')) {
                    $currentKet = 'DAY TRIP';
                } elseif (str_contains(strtoupper($rawName), 'STAFF')) {
                    $currentKet = 'STAFF';
                } elseif (str_contains(strtoupper($rawName), 'FOC')) {
                    $currentKet = 'FOC';
                } elseif (str_contains(strtoupper($rawName), 'VENDOR')) {
                    $currentKet = 'VENDOR';
                }
                // Reset group when section changes
                $currentGroup = null;
                continue;
            }

            // ── Skip completely empty rows ────────────────────────────
            if ($rawName === '' && $rawKet === '') {
                continue;
            }

            // ── Carry forward KET from section header if missing ──────
            if ($rawKet === '' && $currentKet !== null) {
                $ticket['ket'] = $currentKet;
            } elseif ($rawKet !== '') {
                // Normalise "DAYTRIP" → "DAY TRIP"
                if ($rawKet === 'DAYTRIP') {
                    $ticket['ket'] = 'DAY TRIP';
                }
                $currentKet = $ticket['ket'];
            }

            // ── Carry forward group name ──────────────────────────────
            if (!empty($ticket['group_name'])) {
                $currentGroup = $ticket['group_name'];
            } elseif ($currentGroup !== null) {
                $ticket['group_name'] = $currentGroup;
            }

            // ── Skip rows with no name and no seq_no ─────────────────
            if ($rawName === '') {
                continue;
            }

            // Internal key used for seat assignment lookup
            $ticket['_key']      = $seq;
            $ticket['upload_id'] = 0;
            $ticket['schedule_id'] = $scheduleId;
            $ticket['boat_id']     = $boatId;
            $ticket['direction']   = $direction;
            $ticket['trip_date']   = $tripDate;

            $ticketRows[] = $ticket;
        }

        if (empty($ticketRows)) {
            @unlink($savedPath);
            return $this->jsonResponse(['error' => 'No passenger rows found in the manifest.'], 422);
        }

        // ── 7. Auto-assign seats ──────────────────────────────────────
        // Only assign seats to paying passengers (OVERNIGHT / DAY TRIP).
        // STAFF, FOC, VENDOR get seats too if available, but are last priority.
        $noSeatKets = ['STAFF', 'FOC', 'VENDOR']; // change to [] if they need seats too

        $grouped = [];
        foreach ($ticketRows as $t) {
            // STAFF / FOC / VENDOR skip seat assignment
            if (in_array(strtoupper($t['ket'] ?? ''), $noSeatKets, true)) {
                continue;
            }
            $grp = $t['group_name'] ?? '__solo__' . $t['_key'];
            if (!isset($grouped[$grp])) {
                $grouped[$grp] = ['group_name' => $grp, 'passengers' => []];
            }
            $grouped[$grp]['passengers'][] = $t;
        }

        $seatAssignments = [];
        if ($boatId) {
            $seatAssignments = $this->assignSeats($boatId, array_values($grouped));
        }

        // ── 8. Create manifest_uploads row ───────────────────────────
        // Count per-category from the actual parsed tickets
        $ketCounts = ['OVERNIGHT' => 0, 'DAY TRIP' => 0, 'STAFF' => 0, 'FOC' => 0, 'VENDOR' => 0];
        foreach ($ticketRows as $t) {
            $k = strtoupper(trim($t['ket'] ?? ''));
            if (isset($ketCounts[$k])) $ketCounts[$k]++;
        }

        // Prefer Excel-header counts if available (they include cancelled rows not in data)
        $overnightCount = $headerMeta['overnight_count'] ?: $ketCounts['OVERNIGHT'];
        $daytripCount   = $headerMeta['daytrip_count']   ?: $ketCounts['DAY TRIP'];
        $staffCount     = $headerMeta['staff_count']     ?: $ketCounts['STAFF'];
        $focCount       = $headerMeta['foc_count']       ?: $ketCounts['FOC'];
        $vendorCount    = $headerMeta['vendor_count']    ?: $ketCounts['VENDOR'];

        $uploadModel = new ManifestUploadModel();
        $uploadId = $uploadModel->insert([
            'schedule_id'     => $scheduleId,
            'boat_id'         => $boatId,
            'direction'       => $direction,
            'trip_date'       => $tripDate,
            'boat_name'       => $schedule['boat_name'] ?? '',
            'origin'          => $headerMeta['origin'] ?: null,
            'destination'     => $headerMeta['destination'] ?: null,
            'captain_name'    => $captainName ?: null,
            'abk_names'       => $abkNamesRaw ?: null,
            'gro_name'        => $headerMeta['gro_name'] ?: null,
            'uploaded_by'     => $this->getAuthUserId(),
            'original_file'   => $newFileName,
            'total_pax'       => count($ticketRows),
            'overnight_count' => $overnightCount,
            'daytrip_count'   => $daytripCount,
            'staff_count'     => $staffCount,
            'foc_count'       => $focCount,
            'vendor_count'    => $vendorCount,
            'status'          => 'draft',
            'notes'           => $notes ?: null,
        ]);

        if (!$uploadId) {
            @unlink($savedPath);
            return $this->jsonResponse(['error' => 'Failed to save manifest upload record.'], 500);
        }

        // ── 9. Persist tickets ───────────────────────────────────────
        $ticketModel    = new ManifestTicketModel();
        $dbTicketRows   = [];

        foreach ($ticketRows as $t) {
            $assignment = $seatAssignments[$t['_key']] ?? null;
            $dbTicketRows[] = [
                'upload_id'      => $uploadId,
                'schedule_id'    => $scheduleId,
                'boat_id'        => $boatId,
                'direction'      => $direction,
                'trip_date'      => $tripDate,
                'seq_no'         => $t['seq_no'],
                'ket'            => $t['ket'] ?? null,
                'passenger_name' => $t['passenger_name'],
                'group_name'     => $t['group_name'] ?? null,
                'agent'          => $t['agent'] ?? null,
                'package'        => $t['package'] ?? null,
                'pax_count'      => $t['pax_count'] ?? 1,
                'notes'          => $t['notes'] ?? null,
                'age'            => $t['age'] ?? null,
                'gender'         => $t['gender'] ?? null,
                'domicile'       => $t['domicile'] ?? null,
                'id_passport'    => $t['id_passport'] ?? null,
                'seat_id'        => $assignment['seat_id'] ?? null,
                'seat_number'    => $assignment['seat_number'] ?? null,
                'ticket_code'    => strtoupper('TKT-' . $uploadId . '-' . str_pad($t['seq_no'], 4, '0', STR_PAD_LEFT)),
                'checked_in'     => 0,
                'cancelled'      => 0,
            ];
        }

        $ticketModel->bulkInsert($dbTicketRows);

        // Mark assigned seats as booked in the seat table
        $assignedSeatIds = array_column(array_values($seatAssignments), 'seat_id');
        if (!empty($assignedSeatIds)) {
            $db->table('seat')
               ->whereIn('id', $assignedSeatIds)
               ->update(['status' => 'booked']);
        }

        // ── 10. Generate group QR codes ──────────────────────────────
        // Create unique QR per group for self-service boarding pass generation
        $groupQrCodes = $this->generateGroupQrCodes($uploadId, $ticketRows);

        // Update manifest_uploads with generated QR codes
        $uploadModel->update($uploadId, [
            'group_qr_codes'       => json_encode($groupQrCodes),
            'qr_generation_status' => !empty($groupQrCodes) ? 'generated' : 'failed',
        ]);

        return $this->jsonResponse([
            'message'         => 'Manifest uploaded and seats assigned successfully.',
            'upload_id'       => $uploadId,
            'total_pax'       => count($ticketRows),
            'seats_assigned'  => count($seatAssignments),
            'overnight'       => $overnightCount,
            'daytrip'         => $daytripCount,
            'staff'           => $staffCount,
            'foc'             => $focCount,
            'vendor'          => $vendorCount,
            'origin'          => $headerMeta['origin'],
            'destination'     => $headerMeta['destination'],
            'captain_name'    => $captainName ?: null,
            'crew_names'      => $abkNamesRaw ?: null,
            'gro_name'        => $headerMeta['gro_name'],
            'group_qr_codes'  => $groupQrCodes,
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────
    // Helper: extract authenticated user_id from Bearer token
    // ─────────────────────────────────────────────────────────────
    private function getAuthUserId(): int
    {
        $authHeader = $this->request->getHeaderLine('Authorization');
        if (!str_starts_with($authHeader, 'Bearer ')) {
            return 0;
        }
        $rawToken = substr($authHeader, 7);
        $db       = \Config\Database::connect();
        $row      = $db->table('api_tokens')
            ->where('token', $rawToken)
            ->orWhere('token', hash('sha256', $rawToken))
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->get()
            ->getFirstRow('array');
        return $row ? (int) $row['user_id'] : 0;
    }

    // ═══════════════════════════════════════════
    // ENDPOINT: GET /api/admin/manifest/uploads
    // ═══════════════════════════════════════════
    public function listUploads()
    {
        if (!$this->isAdminUser()) {
            return $this->jsonResponse(['error' => 'Forbidden.'], 403);
        }
        $scheduleId = (int) ($this->request->getVar('schedule_id') ?? 0);
        $model      = new ManifestUploadModel();

        if ($scheduleId) {
            $rows = $model->listBySchedule($scheduleId);
        } else {
            $db   = \Config\Database::connect();
            $rows = $db->table('manifest_uploads mu')
                ->select('mu.*, COUNT(mt.id) AS ticket_count')
                ->join('manifest_tickets mt', 'mt.upload_id = mu.id', 'left')
                ->groupBy('mu.id')
                ->orderBy('mu.id', 'DESC')
                ->limit(100)
                ->get()
                ->getResultArray();
        }
        return $this->jsonResponse($rows ?? []);
    }

    // ═══════════════════════════════════════════
    // ENDPOINT: GET /api/admin/manifest/uploads/{id}
    // ═══════════════════════════════════════════
    public function getUpload(int $id)
    {
        if (!$this->isAdminUser()) {
            return $this->jsonResponse(['error' => 'Forbidden.'], 403);
        }
        $upload = (new ManifestUploadModel())->getWithDetail($id);
        if (!$upload) {
            return $this->jsonResponse(['error' => 'Upload not found.'], 404);
        }
        $tickets = (new ManifestTicketModel())->getByUpload($id);
        $baggage = (new ManifestBaggageModel())->getByUpload($id);
        return $this->jsonResponse([
            'upload'  => $upload,
            'tickets' => $tickets,
            'baggage' => $baggage,
        ]);
    }

    // ═══════════════════════════════════════════
    // ENDPOINT: GET /api/admin/manifest/tickets/{uploadId}
    // ═══════════════════════════════════════════
    public function getTickets(int $uploadId)
    {
        if (!$this->isAdminUser()) {
            return $this->jsonResponse(['error' => 'Forbidden.'], 403);
        }
        $tickets = (new ManifestTicketModel())->getByUpload($uploadId);
        return $this->jsonResponse($tickets ?? []);
    }

    // ═══════════════════════════════════════════
    // ENDPOINT: POST /api/admin/manifest/uploads/{id}/confirm
    // ═══════════════════════════════════════════
    public function confirmUpload(int $id)
    {
        if (!$this->isAdminUser()) {
            return $this->jsonResponse(['error' => 'Forbidden.'], 403);
        }
        $model  = new ManifestUploadModel();
        $upload = $model->find($id);
        if (!$upload) {
            return $this->jsonResponse(['error' => 'Upload not found.'], 404);
        }
        $model->update($id, ['status' => 'confirmed']);
        return $this->jsonResponse(['message' => 'Manifest confirmed.', 'upload_id' => $id]);
    }

    // ═══════════════════════════════════════════
    // ENDPOINT: DELETE /api/admin/manifest/uploads/{id}
    // ═══════════════════════════════════════════
    public function deleteUpload(int $id)
    {
        if (!$this->isAdminUser()) {
            return $this->jsonResponse(['error' => 'Forbidden.'], 403);
        }
        $model  = new ManifestUploadModel();
        $upload = $model->find($id);
        if (!$upload) {
            return $this->jsonResponse(['error' => 'Upload not found.'], 404);
        }

        $db = \Config\Database::connect();
        
        // RELEASE BOOKED SEATS: get all seat_ids assigned to this manifest's tickets
        $tickets = $db->table('manifest_tickets')
            ->select('seat_id')
            ->where('upload_id', $id)
            ->where('seat_id IS NOT NULL')
            ->get()
            ->getResultArray();
        
        $seatIds = array_column($tickets, 'seat_id');
        
        // Update seats to available (only if they were booked)
        if (!empty($seatIds)) {
            $db->table('seat')
                ->whereIn('id', $seatIds)
                ->where('status', 'booked')
                ->set(['status' => 'available'])
                ->update();
        }
        
        // Now delete tickets and baggage
        $db->table('manifest_tickets')->where('upload_id', $id)->delete();
        $db->table('manifest_baggage')->where('upload_id', $id)->delete();
        $model->delete($id);

        // Best-effort: remove the xlsx file
        $filePath = FCPATH . 'uploads/manifests/' . $upload['original_file'];
        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        return $this->jsonResponse(['message' => 'Upload deleted.']);
    }

    // ═══════════════════════════════════════════
    // BAGGAGE ENDPOINTS
    // ═══════════════════════════════════════════

    // GET /api/admin/manifest/baggage/{uploadId}
    public function listBaggage(int $uploadId)
    {
        if (!$this->isAdminUser()) {
            return $this->jsonResponse(['error' => 'Forbidden.'], 403);
        }
        return $this->jsonResponse((new ManifestBaggageModel())->getByUpload($uploadId) ?? []);
    }

    // POST /api/admin/manifest/baggage
    // Body JSON: { upload_id, group_name, bag_label, weight_kg, bag_count, description, direction }
    public function addBaggage()
    {
        if (!$this->isAdminUser()) {
            return $this->jsonResponse(['error' => 'Forbidden.'], 403);
        }
        $body     = $this->request->getJSON(true) ?? [];
        $uploadId = (int) ($body['upload_id'] ?? 0);
        $group    = trim($body['group_name'] ?? '');

        if (!$uploadId || $group === '') {
            return $this->jsonResponse(['error' => 'upload_id and group_name are required.'], 422);
        }

        $upload = (new ManifestUploadModel())->find($uploadId);
        if (!$upload) {
            return $this->jsonResponse(['error' => 'Upload not found.'], 404);
        }

        $model = new ManifestBaggageModel();
        $id = $model->insert([
            'upload_id'   => $uploadId,
            'schedule_id' => $upload['schedule_id'],
            'trip_date'   => $upload['trip_date'],
            'group_name'  => $group,
            'bag_label'   => trim($body['bag_label'] ?? ''),
            'weight_kg'   => isset($body['weight_kg']) && $body['weight_kg'] !== '' ? (float) $body['weight_kg'] : null,
            'bag_count'   => max(1, (int) ($body['bag_count'] ?? 1)),
            'description' => trim($body['description'] ?? ''),
            'direction'   => $body['direction'] ?? $upload['direction'],
            'tag_printed' => 0,
        ]);

        return $this->jsonResponse(['message' => 'Baggage added.', 'id' => $id], 201);
    }

    // PUT /api/admin/manifest/baggage/{id}
    public function updateBaggage(int $id)
    {
        if (!$this->isAdminUser()) {
            return $this->jsonResponse(['error' => 'Forbidden.'], 403);
        }
        $model   = new ManifestBaggageModel();
        $baggage = $model->find($id);
        if (!$baggage) {
            return $this->jsonResponse(['error' => 'Baggage item not found.'], 404);
        }
        $body = $this->request->getJSON(true) ?? [];
        $update = [];
        foreach (['bag_label','weight_kg','bag_count','description','direction','group_name'] as $field) {
            if (isset($body[$field])) {
                $update[$field] = $body[$field];
            }
        }
        if (!empty($update)) {
            $model->update($id, $update);
        }
        return $this->jsonResponse(['message' => 'Baggage updated.']);
    }

    // DELETE /api/admin/manifest/baggage/{id}
    public function deleteBaggage(int $id)
    {
        if (!$this->isAdminUser()) {
            return $this->jsonResponse(['error' => 'Forbidden.'], 403);
        }
        $model = new ManifestBaggageModel();
        if (!$model->find($id)) {
            return $this->jsonResponse(['error' => 'Baggage item not found.'], 404);
        }
        $model->delete($id);
        return $this->jsonResponse(['message' => 'Baggage deleted.']);
    }

    // POST /api/admin/manifest/baggage/{id}/mark-printed
    public function markBaggagePrinted(int $id)
    {
        if (!$this->isAdminUser()) {
            return $this->jsonResponse(['error' => 'Forbidden.'], 403);
        }
        $model = new ManifestBaggageModel();
        if (!$model->find($id)) {
            return $this->jsonResponse(['error' => 'Baggage item not found.'], 404);
        }
        $model->markPrinted($id);
        return $this->jsonResponse(['message' => 'Tagged as printed.']);
    }

    // ═══════════════════════════════════════════════════════════════════
    // BAGGAGE TAG PDF
    // GET /api/admin/manifest/baggage-tag-pdf/{id}
    // Streams an 85.6×54mm landscape PDF tag (credit card size) — one page per bag count.
    // ═══════════════════════════════════════════════════════════════════
    public function baggageTagPdf(int $id)
    {
        if (!$this->isAdminUser()) {
            return $this->jsonResponse(['error' => 'Forbidden.'], 403);
        }

        $db  = \Config\Database::connect();

        // ── Load baggage row ─────────────────────────────────────────
        $bag = $db->table('manifest_baggage')
            ->where('id', $id)
            ->get()
            ->getFirstRow('array');

        if (!$bag) {
            return $this->response->setStatusCode(404)
                ->setJSON(['error' => 'Baggage item not found.']);
        }

        // ── Load upload (for boat name, trip date, direction) ────────
        $upload = $db->table('manifest_uploads')
            ->where('id', $bag['upload_id'])
            ->get()
            ->getFirstRow('array');

            //CODE JANU

        $boatName    = $upload['boat_name']    ?? '';
        $tripDate    = $upload['trip_date']    ?? null;
        $direction   = $upload['direction']    ?? 'DEPARTURE';
        $origin      = $upload['origin']       ?? '';
        $destination = $upload['destination']  ?? '';

        // ── Boat brand color ─────
        $boatColors = [
            'la luna'   => [24, 0, 173],
            'la vela'   => [191, 0, 0],
            'labrisa'  => [255, 87, 208],
            'mola mola' => [255, 145, 77],
            'mola-mola' => [255, 145, 77],
            'la casa'   => [167, 122, 255],
            'alma'      => [255, 222, 89],
        ];
        $brandColor  = [242, 136, 28]; // default orange
        $boatLower   = strtolower($boatName);
        foreach ($boatColors as $key => $rgb) {
            if (str_contains($boatLower, $key)) { $brandColor = $rgb; break; }
        }

        // Decide text color on header (dark brand = white text, light = black)
        $luminance = 0.299 * $brandColor[0] + 0.587 * $brandColor[1] + 0.114 * $brandColor[2];
        $headerTextColor = $luminance < 160 ? [255, 255, 255] : [20, 20, 20];

        // ── Date formatting ──────────────────────────────────────────
        $months = ['January'=>'Januari','February'=>'Februari','March'=>'Maret',
            'April'=>'April','May'=>'Mei','June'=>'Juni','July'=>'Juli',
            'August'=>'Agustus','September'=>'September','October'=>'Oktober',
            'November'=>'November','December'=>'Desember'];

        $tripDateFmt = $tripDate
            ? strtr(date('d M Y', strtotime($tripDate)), $months)
            : 'N/A';
        $dirLabel = ($direction === 'RETURN') ? 'RETURN' : 'DEPARTURE';

        // ── Auto-generate bag_label if empty ──────────────────────────
        // Format: first 3 words of group name (max 10 chars) + -001, -002, ...
        // e.g. "NOVA MARLINA SITORUS" with 3 bags → NOVA-MAR-001, NOVA-MAR-002, NOVA-MAR-003
        if (empty($bag['bag_label'])) {
            $groupWords = preg_split('/\s+/', strtoupper(trim($bag['group_name'] ?? 'BAG')));
            $prefix     = implode('-', array_slice(array_map(fn($w) => substr($w, 0, 4), $groupWords), 0, 2));
            $prefix     = preg_replace('/[^A-Z0-9\-]/', '', $prefix);
            $prefix     = rtrim(substr($prefix, 0, 10), '-');
            // Save the generated label back to DB so future prints are consistent
            $db->table('manifest_baggage')->where('id', $id)->update(['bag_label' => $prefix . '-001']);
            $bag['bag_label'] = $prefix;  // per-page suffix added in loop
            $autoLabel = true;
        } else {
            $autoLabel = false;
        }

        // ── Build QR content ─────────────────────────────────────────
        $qrDir = WRITEPATH . 'uploads/qr_codes/';
        if (!is_dir($qrDir)) mkdir($qrDir, 0775, true);

        $qrContent  = 'NAMA_BAG_' . $bag['upload_id'] . '_' . $id . '_' . strtoupper(str_replace(' ', '_', $bag['group_name']));
        $qrFilePath = $qrDir . uniqid('bag_') . '.png';
        $qrOk       = false;
        try {
            $writer = new \Endroid\QrCode\Writer\PngWriter();
            $qrCode = \Endroid\QrCode\QrCode::create($qrContent)
                ->setEncoding(new \Endroid\QrCode\Encoding\Encoding('UTF-8'))
                ->setSize(250)->setMargin(4)
                ->setForegroundColor(new \Endroid\QrCode\Color\Color(0, 0, 0))
                ->setBackgroundColor(new \Endroid\QrCode\Color\Color(255, 255, 255));
            $writer->write($qrCode)->saveToFile($qrFilePath);
            $qrOk = true;
        } catch (\Exception $e) {
            $qrFilePath = null;
        }

        // ── PDF: 105 x 40 mm landscape (long strip for luggage wrap) ─
        $pdf = new \App\Libraries\BoardingPassPDF('L', 'mm', [105, 40]);
        $pdf->SetAutoPageBreak(false);
        $pdf->SetTitle('Baggage-Tag-' . $id);
        $pdf->SetMargins(0, 0, 0);

        $bagCount = max(1, (int)($bag['bag_count'] ?? 1));

        for ($page = 1; $page <= $bagCount; $page++) {
            $pdf->AddPage();

            $W = 105;   // page width mm
            $H = 40;    // page height mm

            // ── Outer border ──────────────────────────────────────────
            $pdf->SetDrawColor(210, 210, 210);
            $pdf->SetLineWidth(0.35);
            $pdf->Rect(1, 1, $W - 2, $H - 2);

            // ── Left header band (brand color, full height) ───────────
            $bandW = 22;
            $pdf->SetFillColor(...$brandColor);
            $pdf->Rect(1, 1, $bandW, $H - 2, 'F');

            // Yacht icon in band (centred vertically)
            $pdf->YachtIconAuto(3, 3, 9, 8);

            // "NAMA Marine" vertical text replaced with stacked lines
            $pdf->SetFont('Arial', 'B', 7);
            $pdf->SetTextColor(...$headerTextColor);
            $pdf->SetXY(2, 13);
            $pdf->Cell($bandW - 2, 4, 'NAMA', 0, 1, 'C');
            $pdf->SetXY(2, 17);
            $pdf->Cell($bandW - 2, 4, 'Marine', 0, 1, 'C');

            // Direction badge at bottom of band
            $pdf->SetFont('Arial', 'B', 5.5);
            $pdf->SetXY(2, $H - 9);
            $pdf->Cell($bandW - 2, 3.5, $dirLabel, 0, 0, 'C');

            // Bag counter at very bottom of band
            $pdf->SetFont('Arial', 'B', 6);
            $pdf->SetXY(2, $H - 5.5);
            $pdf->Cell($bandW - 2, 3.5, $page . '/' . $bagCount, 0, 0, 'C');

            $pdf->SetTextColor(30, 30, 30);

            // ── Right QR block ────────────────────────────────────────
            $qrSize = 26;
            $qrX    = $W - $qrSize - 3;
            $qrY    = 2;              // pin QR to top, not centred

            if ($qrOk && $qrFilePath && file_exists($qrFilePath)) {
                $pdf->Image($qrFilePath, $qrX, $qrY, $qrSize, $qrSize, 'PNG');
            }

            // Dotted separator left of QR — full card height
            $pdf->SetFillColor(200, 200, 200);
            $pdf->DottedLine($qrX - 2, 2, $qrX - 2, $H - 2, 0.25, 1.2);

            // ── Middle content area (strictly stays left of separator) ─
            $cx  = $bandW + 4;
            $cw  = $qrX - $cx - 4;   // hard stop before dotted line
            $cy  = 3;

            // Guest name
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetTextColor(20, 20, 20);
            $pdf->SetXY($cx, $cy);
            $guestName = strtoupper($bag['group_name'] ?? '');
            if (mb_strlen($guestName) > 22) $guestName = mb_substr($guestName, 0, 19) . '...';
            $pdf->Cell($cw, 6, $guestName, 0, 0, 'L');

            // Route
            $cy += 6.5;
            $pdf->SetFont('Arial', '', 6.5);
            $pdf->SetTextColor(100, 100, 100);
            $routeText = strtoupper($origin) . ' >> ' . strtoupper($destination);
            if (mb_strlen($routeText) > 32) $routeText = mb_substr($routeText, 0, 29) . '...';
            $pdf->SetXY($cx, $cy);
            $pdf->Cell($cw, 3.5, $routeText, 0, 0, 'L');

            // Thin divider
            $cy += 4.5;
            $pdf->SetDrawColor(220, 220, 220);
            $pdf->SetLineWidth(0.2);
            $pdf->Line($cx, $cy, $cx + $cw, $cy);
            $cy += 2.5;

            // Fields: single-column stacked (no 2-col overlap risk)
            $lw   = 12;    // label column width
            $vw   = $cw - $lw;  // value column width
            $rowH = 4.0;

            $fields = [
                ['Boat',  strtoupper(mb_substr($boatName, 0, 20))],
                ['Label', $autoLabel
                    ? strtoupper($bag['bag_label'] . '-' . str_pad($page, 3, '0', STR_PAD_LEFT))
                    : strtoupper($bag['bag_label'] ?? '-')],
            ];
            if (!empty($bag['weight_kg'])) {
                $fields[] = ['Wt', $bag['weight_kg'] . ' kg'];
            }
            $fields[] = ['Date',  $tripDateFmt];
            $fields[] = ['Bags',  ($bag['bag_count'] ?? 1) . ' pcs'];

            foreach ($fields as [$label, $val]) {
                if ($cy > $H - 8) break;
                $pdf->SetFont('Arial', '', 5.5);
                $pdf->SetTextColor(130, 130, 130);
                $pdf->SetXY($cx, $cy);
                $pdf->Cell($lw, $rowH, $label, 0, 0, 'L');

                if (mb_strlen($val) > 22) $val = mb_substr($val, 0, 20) . '..';
                $pdf->SetFont('Arial', 'B', 6.5);
                $pdf->SetTextColor(20, 20, 20);
                $pdf->SetXY($cx + $lw, $cy);
                $pdf->Cell($vw, $rowH, $val, 0, 0, 'L');
                $cy += $rowH;
            }

            // Bag label at bottom of content
            $bagLabel = $autoLabel
                ? strtoupper($bag['bag_label'] . '-' . str_pad($page, 3, '0', STR_PAD_LEFT))
                : strtoupper($bag['bag_label']);
            $pdf->SetFont('Arial', 'B', 6);
            $pdf->SetTextColor(...$brandColor);
            $pdf->SetXY($cx, $H - 5.5);
            $pdf->Cell($cw, 4, $bagLabel, 0, 0, 'L');
        }

        // ── Cleanup QR temp ──────────────────────────────────────────
        if ($qrFilePath && file_exists($qrFilePath)) {
            @unlink($qrFilePath);
        }

        // ── Mark as printed ──────────────────────────────────────────
        $db->table('manifest_baggage')
            ->where('id', $id)
            ->update(['tag_printed' => 1]);

        // ── Output ───────────────────────────────────────────────────
        $pdfContent = $pdf->Output('S');

        while (ob_get_level() > 0) { ob_end_clean(); }

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="baggage-tag-' . $id . '.pdf"')
            ->setHeader('Cache-Control', 'max-age=0')
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Expose-Headers', 'Content-Disposition')
            ->setStatusCode(200)
            ->setBody($pdfContent);
    }

    // ═══════════════════════════════════════════
    // BOAT CREW ENDPOINTS
    // ═══════════════════════════════════════════

    // GET /api/admin/manifest/boats
    public function listBoats()
    {
        if (!$this->isAdminUser()) {
            return $this->jsonResponse(['error' => 'Forbidden.'], 403);
        }
        $db   = \Config\Database::connect();
        $rows = $db->table('boat')
            ->select('id, boat_name, capacity, captain_name, abk_names')
            ->orderBy('boat_name', 'ASC')
            ->get()
            ->getResultArray();
        return $this->jsonResponse($rows ?? []);
    }

    // PUT /api/admin/manifest/boats/{id}/crew
    // Body: { captain_name, abk_names }  (abk_names = array or newline string)
    public function updateBoatCrew(int $id)
    {
        if (!$this->isAdminUser()) {
            return $this->jsonResponse(['error' => 'Forbidden.'], 403);
        }
        $db   = \Config\Database::connect();
        $boat = $db->table('boat')->where('id', $id)->get()->getFirstRow('array');
        if (!$boat) {
            return $this->jsonResponse(['error' => 'Boat not found.'], 404);
        }
        $body  = $this->request->getJSON(true) ?? [];
        $update = [];
        if (isset($body['captain_name'])) {
            $update['captain_name'] = trim($body['captain_name']);
        }
        if (isset($body['abk_names'])) {
            $raw = $body['abk_names'];
            if (is_array($raw)) {
                $update['abk_names'] = json_encode(array_values($raw));
            } else {
                $str = trim((string) $raw);
                $update['abk_names'] = ($str !== '' && $str[0] === '[') ? $str
                    : json_encode(array_values(array_filter(array_map('trim', explode("\n", $str)))));
            }
        }
        if (!empty($update)) {
            $db->table('boat')->update($update, ['id' => $id]);
        }
        return $this->jsonResponse(['message' => 'Boat crew updated.']);
    }

    // ═══════════════════════════════════════════════════════════════════
    // BOARDING PASS PDF — from manifest tickets
    //
    // GET /api/admin/manifest/boarding-pass/{uploadId}
    //   ?ticket_ids=1,2,3   print only these ticket IDs (optional)
    //   if no ticket_ids → prints ALL tickets for the upload
    //
    // Streams a PDF directly (Content-Type: application/pdf).
    // No auth header needed for GET — the uploadId is the secret.
    // ═══════════════════════════════════════════════════════════════════


    

public function boardingPass(int $uploadId)
{
    $db = \Config\Database::connect();

    // ── Resolve upload meta ──────────────────────────────────
    $upload = $db->table('manifest_uploads')
        ->where('id', $uploadId)
        ->get()
        ->getFirstRow('array');

    if (!$upload) {
        return $this->response->setStatusCode(404)
            ->setJSON(['error' => 'Upload not found.']);
    }

    // ── Resolve boat + captain ───────────────────────────────
    $boat = $db->table('boat')
        ->where('id', $upload['boat_id'])
        ->get()
        ->getFirstRow('array');

    $boatName = $boat['boat_name']
        ?? $upload['boat_name']
        ?? 'NAMA KAPAL';

    $captainName = $upload['captain_name']
        ?: ($boat['captain_name'] ?? '');

    // ── Load tickets ─────────────────────────────────────────
    $ticketIdsRaw = $this->request->getVar('ticket_ids');

    $qb = $db->table('manifest_tickets')
        ->where('upload_id', $uploadId);

    if ($ticketIdsRaw) {
        $ids = array_filter(
            array_map('intval', explode(',', $ticketIdsRaw))
        );

        if (!empty($ids)) {
            $qb->whereIn('id', $ids);
        }
    }

    $tickets = $qb
        ->orderBy('seq_no', 'ASC')
        ->get()
        ->getResultArray();

    if (empty($tickets)) {
        return $this->response->setStatusCode(404)
            ->setJSON(['error' => 'No tickets found for this upload.']);
    }

    // ── Date / time from upload ──────────────────────────────
    $tripDate = $upload['trip_date'] ?? null;

    $formattedDate = $tripDate
        ? strtoupper(date('d F Y', strtotime($tripDate)))
        : 'N/A';

    $boardingTime = 'N/A';

    $schedule = $db->table('schedule')
        ->where('id', $upload['schedule_id'])
        ->get()
        ->getFirstRow('array');

    if ($schedule && !empty($schedule['date'])) {
        $boardingTime = date('H:i', strtotime($schedule['date']));
    }

    // ── QR directory ─────────────────────────────────────────
    $qrDir = WRITEPATH . 'uploads/qr_codes/';

    if (!is_dir($qrDir)) {
        mkdir($qrDir, 0775, true);
    }

    /*
    |--------------------------------------------------------------------------
    | PDF — sized for 4-inch (100mm) thermal label, 203DPI
    |--------------------------------------------------------------------------
    | Page: 100mm x 100mm, portrait, one ticket per page.
    | (If you actually need 3-inch/80mm stock, change PAGE_W below to 80
    | and shrink the field widths proportionally — just ask and I'll do it.)
    */
    // A7 landscape: 105 x 74 mm
    $pageW = 105;
    $pageH = 74;

    $pdf = new \App\Libraries\BoardingPassPDF(
        'L',
        'mm',
        [$pageW, $pageH]
    );

    $pdf->SetAutoPageBreak(false);
    $pdf->SetTitle('Boarding-Pass-Upload-' . $uploadId);

    // ── Colors berdasarkan boat ──────────────────────────────
    $boatNameLower = strtolower($boatName);

    $boatColors = [
        'la luna'   => [24, 0, 173],
        'la vela'   => [191, 0, 0],
        'labrisa'   => [255, 87, 208],
        'mola mola' => [255, 145, 77],
        'mola-mola' => [255, 145, 77],
        'la casa'   => [167, 122, 255],
        'alma'      => [255, 222, 89],
    ];

    $headerColor = [242, 136, 28]; // default orange
    foreach ($boatColors as $boatKey => $rgb) {
        if (stripos($boatNameLower, $boatKey) !== false) {
            $headerColor = $rgb;
            break;
        }
    }

    $borderGray = [220, 222, 226];
    $labelGray  = [150, 155, 160];

    $qrFiles = [];

    /*
    |--------------------------------------------------------------------------
    | Helper: wrap text
    |--------------------------------------------------------------------------
    */
    $wrapText = function ($text, $maxWidth) use ($pdf) {

        $text = trim((string) $text);

        if ($text === '') {
            return [''];
        }

        $words = preg_split('/\s+/u', $text);

        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {

            $testLine = $currentLine === '' ? $word : $currentLine . ' ' . $word;

            if ($pdf->GetStringWidth($testLine) <= $maxWidth) {
                $currentLine = $testLine;
                continue;
            }

            if ($currentLine !== '') {
                $lines[] = $currentLine;
                $currentLine = '';
            }

            if ($pdf->GetStringWidth($word) > $maxWidth) {

                $chars = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY);
                $part = '';

                foreach ($chars as $char) {
                    $testPart = $part . $char;

                    if ($pdf->GetStringWidth($testPart) <= $maxWidth) {
                        $part = $testPart;
                    } else {
                        if ($part !== '') {
                            $lines[] = $part;
                        }
                        $part = $char;
                    }
                }

                $currentLine = $part;

            } else {
                $currentLine = $word;
            }
        }

        if ($currentLine !== '') {
            $lines[] = $currentLine;
        }

        return !empty($lines) ? $lines : [''];
    };

    /*
    |--------------------------------------------------------------------------
    | Helper: stacked field (label on top, value below) — full width
    |--------------------------------------------------------------------------
    */
    $fieldStacked = function (
        $x, $y, $w, $label, $value, $valueColor = [26, 26, 26],
        $lblSz = 6.0, $valSz = 9.0
    ) use ($pdf, $wrapText) {

        $pdf->SetFont('Arial', '', $lblSz);
        $pdf->SetTextColor(150, 155, 160);
        $pdf->SetXY($x, $y);
        $pdf->Cell($w, 3, strtoupper($label), 0, 1);

        $pdf->SetFont('Arial', 'B', $valSz);
        $pdf->SetTextColor(...$valueColor);

        $lines = $wrapText($value, $w);
        $lineHeight = $valSz * 0.5;
        $valueY = $y + 3.3;

        foreach ($lines as $line) {
            $pdf->SetXY($x, $valueY);
            $pdf->Cell($w, $lineHeight, $line, 0, 1, 'L');
            $valueY += $lineHeight;
        }

        $pdf->SetTextColor(0, 0, 0);

        return 3.3 + (count($lines) * $lineHeight);
    };

    foreach ($tickets as $ticket) {

        $passengerName = $ticket['passenger_name'] ?: 'Passenger';
        $groupName     = $ticket['group_name'] ?: $passengerName;
        $seatNumber    = $ticket['seat_number'] ?: '-';
        $ticketCode    = $ticket['ticket_code'] ?: ('TKT-' . $uploadId . '-' . $ticket['id']);
        $ket           = strtoupper($ticket['ket'] ?? '');

        // ── QR code ───────────────────────────────────────────
        $qrContent = 'NAMA_MARINE_MANIFEST_' . $ticketCode;
        $qrFilePath = $qrDir . uniqid('mp_') . '.png';

        try {
            $writer = new \Endroid\QrCode\Writer\PngWriter();

            $qrCode = \Endroid\QrCode\QrCode::create($qrContent)
                ->setEncoding(new \Endroid\QrCode\Encoding\Encoding('UTF-8'))
                ->setSize(300)
                ->setMargin(8)
                ->setForegroundColor(new \Endroid\QrCode\Color\Color(0, 0, 0))
                ->setBackgroundColor(new \Endroid\QrCode\Color\Color(255, 255, 255));

            $writer->write($qrCode)->saveToFile($qrFilePath);

            $qrFiles[] = $qrFilePath;

        } catch (\Exception $e) {
            $qrFilePath = null;
        }

        // ── New page (one label per page) ───────────────────
        $pdf->AddPage();

        $marginX = 3;
        $marginY = 3;

        $cardX = $marginX;
        $cardY = $marginY;
        $cardW = $pageW - ($marginX * 2); // 99mm
        $cardH = $pageH - ($marginY * 2); // 68mm

        // Outer border
        $pdf->SetDrawColor(...$borderGray);
        $pdf->SetLineWidth(0.4);
        $pdf->Rect($cardX, $cardY, $cardW, $cardH);

        // ── Header bar ───────────────────────────────────────
        $headerH = 11;

        $pdf->SetFillColor(...$headerColor);
        $pdf->Rect($cardX, $cardY, $cardW, $headerH, 'F');

        $pdf->YachtIconAuto($cardX + 2.5, $cardY + 1.5, 8, 7);

        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetXY($cardX + 12, $cardY + 2.5);
        $pdf->Cell(50, 5.5, 'Boarding Pass', 0, 0, 'L');

        // Boat name in header (right of title)
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetXY($cardX + 60, $cardY + 2);
        $pdf->Cell($cardW - 62, 4, strtoupper($boatName), 0, 0, 'C');

        if ($ket) {
            $pdf->SetFont('Arial', 'B', 6.5);
            $pdf->SetXY($cardX + 60, $cardY + 6.5);
            $pdf->Cell($cardW - 62, 3.5, $ket, 0, 0, 'C');
        }

        $pdf->SetTextColor(0, 0, 0);

        $bodyTop  = $cardY + $headerH + 2;

        // ── A7 landscape: LEFT column (info) | RIGHT column (QR) ─────
        $qrSize   = 28;
        $qrX      = $cardX + $cardW - $qrSize - 2;
        $qrY      = $cardY + $headerH + 2;

        // QR code first (right side)
        if ($qrFilePath && file_exists($qrFilePath)) {
            $pdf->Image($qrFilePath, $qrX, $qrY, $qrSize, $qrSize, 'PNG');

            $pdf->SetFont('Arial', 'B', 5.5);
            $pdf->SetTextColor(...$labelGray);
            $pdf->SetXY($qrX, $qrY + $qrSize + 0.5);
            $pdf->Cell($qrSize, 3, 'SCAN TO CHECK-IN', 0, 0, 'C');
            $pdf->SetTextColor(0, 0, 0);
        }

        // ── Dotted divider between info & QR ─────────────────
        $divX = $qrX - 3;
        $pdf->SetFillColor(...$borderGray);
        $pdf->DottedLine($divX, $bodyTop, $divX, $cardY + $cardH - 3, 0.3, 1.4);

        // ── Left info column ──────────────────────────────────
        $contentX = $cardX + 3;
        $infoW    = $divX - $contentX - 3;
        $halfW    = ($infoW - 3) / 2;
        $col2X    = $contentX + $halfW + 3;

        $y = $bodyTop;

        // GROUP
        $h = $fieldStacked($contentX, $y, $infoW, 'Group', $groupName, [26,26,26], 5.5, 8.5);
        $y += max(8, $h + 1);

        // NAME
        $h = $fieldStacked($contentX, $y, $infoW, 'Passenger', $passengerName, [26,26,26], 5.5, 8.0);
        $y += max(8, $h + 1);

        // DATE | BOARDING
        $h1 = $fieldStacked($contentX, $y, $halfW, 'Date', $formattedDate, [26,26,26], 5.5, 7.5);
        $h2 = $fieldStacked($col2X,    $y, $halfW, 'Boarding', $boardingTime, [26,26,26], 5.5, 7.5);
        $y += max(8, max($h1, $h2) + 1);

        // FROM | TO / DIRECTION
        $direction   = ($upload['direction'] === 'RETURN') ? 'Return' : 'Departure';
        $origin      = $upload['origin'] ?: 'Baywalk';
        $destination = $upload['destination'] ?: 'N/A';

        $h1 = $fieldStacked($contentX, $y, $halfW, 'From', $origin, [26,26,26], 5.5, 7.5);
        $h2 = ($destination !== 'N/A')
            ? $fieldStacked($col2X, $y, $halfW, 'To', $destination, [26,26,26], 5.5, 7.5)
            : $fieldStacked($col2X, $y, $halfW, 'Direction', $direction, [26,26,26], 5.5, 7.5);
        $y += max(8, max($h1, $h2) + 1);

        // SEAT | TICKET CODE
        $fieldStacked($contentX, $y, $halfW, 'Seat No.', $seatNumber, $headerColor, 5.5, 9.0);
        $fieldStacked($col2X,    $y, $halfW, 'Ticket', $ticketCode, [26,26,26], 5.5, 6.5);

        // ── Captain footer ─────────────────────────────────────
        if ($captainName) {
            $pdf->SetFont('Arial', '', 5.5);
            $pdf->SetTextColor(150, 155, 160);
            $pdf->SetXY($cardX, $cardY + $cardH - 4);
            $pdf->Cell($cardW, 3, 'Capt. ' . $captainName, 0, 0, 'C');
            $pdf->SetTextColor(0, 0, 0);
        }

        // ── OVERNIGHT: add return boarding pass page ──────────
        if (str_contains($ket, 'OVERNIGHT')) {

            $returnTicketCode = $ticketCode . '-R';
            $returnQrContent  = 'NAMA_MARINE_MANIFEST_' . $returnTicketCode;
            $returnQrPath     = $qrDir . uniqid('mp_ret_') . '.png';
            $returnQrOk       = false;

            try {
                $writer2  = new \Endroid\QrCode\Writer\PngWriter();
                $qrCode2  = \Endroid\QrCode\QrCode::create($returnQrContent)
                    ->setEncoding(new \Endroid\QrCode\Encoding\Encoding('UTF-8'))
                    ->setSize(300)->setMargin(8)
                    ->setForegroundColor(new \Endroid\QrCode\Color\Color(0, 0, 0))
                    ->setBackgroundColor(new \Endroid\QrCode\Color\Color(255, 255, 255));
                $writer2->write($qrCode2)->saveToFile($returnQrPath);
                $qrFiles[]   = $returnQrPath;
                $returnQrOk  = true;
            } catch (\Exception $e) {
                $returnQrPath = null;
            }

            $pdf->AddPage();

            // Same card dimensions
            $pdf->SetDrawColor(...$borderGray);
            $pdf->SetLineWidth(0.4);
            $pdf->Rect($cardX, $cardY, $cardW, $cardH);

            // Header (same color)
            $pdf->SetFillColor(...$headerColor);
            $pdf->Rect($cardX, $cardY, $cardW, $headerH, 'F');

            $pdf->YachtIconAuto($cardX + 2.5, $cardY + 1.5, 8, 7);

            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetXY($cardX + 12, $cardY + 2.5);
            $pdf->Cell(50, 5.5, 'Boarding Pass', 0, 0, 'L');

            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetXY($cardX + 60, $cardY + 2);
            $pdf->Cell($cardW - 62, 4, strtoupper($boatName), 0, 0, 'C');

            // Return label
            $pdf->SetFont('Arial', 'B', 6.5);
            $pdf->SetXY($cardX + 60, $cardY + 6.5);
            $pdf->Cell($cardW - 62, 3.5, 'OVERNIGHT - RETURN', 0, 0, 'C');

            $pdf->SetTextColor(0, 0, 0);

            // QR (right)
            if ($returnQrOk && $returnQrPath && file_exists($returnQrPath)) {
                $pdf->Image($returnQrPath, $qrX, $qrY, $qrSize, $qrSize, 'PNG');
                $pdf->SetFont('Arial', 'B', 5.5);
                $pdf->SetTextColor(...$labelGray);
                $pdf->SetXY($qrX, $qrY + $qrSize + 0.5);
                $pdf->Cell($qrSize, 3, 'SCAN TO CHECK-IN', 0, 0, 'C');
                $pdf->SetTextColor(0, 0, 0);
            }

            // Dotted divider
            $pdf->SetFillColor(...$borderGray);
            $pdf->DottedLine($divX, $bodyTop, $divX, $cardY + $cardH - 3, 0.3, 1.4);

            // Info column
            $ry = $bodyTop;

            $h = $fieldStacked($contentX, $ry, $infoW, 'Group', $groupName, [26,26,26], 5.5, 8.5);
            $ry += max(8, $h + 1);

            $h = $fieldStacked($contentX, $ry, $infoW, 'Passenger', $passengerName, [26,26,26], 5.5, 8.0);
            $ry += max(8, $h + 1);

            // Return date (same date for now — overnight is same trip date)
            $h1 = $fieldStacked($contentX, $ry, $halfW, 'Date', $formattedDate, [26,26,26], 5.5, 7.5);
            $h2 = $fieldStacked($col2X,    $ry, $halfW, 'Type', 'Return', [26,26,26], 5.5, 7.5);
            $ry += max(8, max($h1, $h2) + 1);

            // Swapped route: destination → origin
            $retFrom = $destination !== 'N/A' ? $destination : 'Sepa';
            $retTo   = $origin;

            $h1 = $fieldStacked($contentX, $ry, $halfW, 'From', $retFrom, [26,26,26], 5.5, 7.5);
            $h2 = $fieldStacked($col2X,    $ry, $halfW, 'To',   $retTo,   [26,26,26], 5.5, 7.5);
            $ry += max(8, max($h1, $h2) + 1);

            // Seat (same seat) | Return ticket code
            $fieldStacked($contentX, $ry, $halfW, 'Seat No.', $seatNumber, $headerColor, 5.5, 9.0);
            $fieldStacked($col2X,    $ry, $halfW, 'Ticket', $returnTicketCode, [26,26,26], 5.5, 6.5);

            if ($captainName) {
                $pdf->SetFont('Arial', '', 5.5);
                $pdf->SetTextColor(150, 155, 160);
                $pdf->SetXY($cardX, $cardY + $cardH - 4);
                $pdf->Cell($cardW, 3, 'Capt. ' . $captainName, 0, 0, 'C');
                $pdf->SetTextColor(0, 0, 0);
            }
        }
    }

    // ── Clean up temporary QR files ──────────────────────────
    foreach ($qrFiles as $f) {
        @unlink($f);
    }

    // ── Output PDF ───────────────────────────────────────────
    $this->response->setContentType('application/pdf');

    $pdf->Output(
        'I',
        'boarding-pass-manifest-' . $uploadId . '.pdf'
    );
}

    public function boardingPassOld(int $uploadId)
    {
        $db = \Config\Database::connect();

        // ── Resolve upload meta ──────────────────────────────────
        $upload = $db->table('manifest_uploads')
            ->where('id', $uploadId)
            ->get()
            ->getFirstRow('array');

        if (!$upload) {
            return $this->response->setStatusCode(404)
                ->setJSON(['error' => 'Upload not found.']);
        }

        // ── Resolve boat + captain ───────────────────────────────
        $boat = $db->table('boat')
            ->where('id', $upload['boat_id'])
            ->get()
            ->getFirstRow('array');

        $boatName    = $boat['boat_name']    ?? $upload['boat_name'] ?? 'NAMA KAPAL';
        $captainName = $upload['captain_name'] ?: ($boat['captain_name'] ?? '');

        // ── Load tickets ─────────────────────────────────────────
        $ticketIdsRaw = $this->request->getVar('ticket_ids');
        $qb = $db->table('manifest_tickets')->where('upload_id', $uploadId);

        if ($ticketIdsRaw) {
            $ids = array_filter(array_map('intval', explode(',', $ticketIdsRaw)));
            if (!empty($ids)) {
                $qb->whereIn('id', $ids);
            }
        }

        $tickets = $qb->orderBy('seq_no', 'ASC')->get()->getResultArray();

        if (empty($tickets)) {
            return $this->response->setStatusCode(404)
                ->setJSON(['error' => 'No tickets found for this upload.']);
        }

        // ── Date / time from upload ──────────────────────────────
        $tripDate      = $upload['trip_date'] ?? null;
        $formattedDate = $tripDate
            ? strtoupper(date('d F Y', strtotime($tripDate)))
            : 'N/A';
        $boardingTime  = 'N/A';

        // Try to get a time from the schedule row
        $schedule = $db->table('schedule')->where('id', $upload['schedule_id'])->get()->getFirstRow('array');
        if ($schedule && !empty($schedule['date'])) {
            $boardingTime = date('H:i', strtotime($schedule['date']));
        }

        // ── QR directory ─────────────────────────────────────────
        $qrDir = WRITEPATH . 'uploads/qr_codes/';
        if (!is_dir($qrDir)) {
            mkdir($qrDir, 0775, true);
        }

        // ── Reuse same BoardingPassPDF style as Admin::printBoardingPassPdf ──
        $pdf = new \App\Libraries\BoardingPassPDF('L', 'mm', [210, 95]);
        $pdf->SetAutoPageBreak(false);
        $pdf->SetTitle('Boarding-Pass-Upload-' . $uploadId);

        // Boat color for boardingPassOld
        $boatNameLowerOld = strtolower($boatName);
        $boatColorsOld = [
            'la luna'   => [24, 0, 173],
            'la vela'   => [191, 0, 0],
            'labrisa'  => [255, 87, 208],
            'mola mola' => [255, 145, 77],
            'mola-mola' => [255, 145, 77],
            'la casa'   => [167, 122, 255],
            'alma'      => [255, 222, 89],
        ];
        $orange = [242, 136, 28]; // default
        foreach ($boatColorsOld as $k => $rgb) {
            if (stripos($boatNameLowerOld, $k) !== false) {
                $orange = $rgb;
                break;
            }
        }
        $borderGray  = [220, 222, 226];
        $labelGray   = [150, 155, 160];

        $qrFiles = [];   // temp files to clean up

        foreach ($tickets as $ticket) {
            $passengerName = $ticket['passenger_name'] ?: 'Passenger';
            $groupName     = $ticket['group_name']     ?: $passengerName;
            $seatNumber    = $ticket['seat_number']    ?: '-';
            $ticketCode    = $ticket['ticket_code']    ?: ('TKT-' . $uploadId . '-' . $ticket['id']);
            $ket           = strtoupper($ticket['ket'] ?? '');

            // ── QR code per ticket ───────────────────────────────
            $qrContent  = 'NAMA_MARINE_MANIFEST_' . $ticketCode;
            $qrFilePath = $qrDir . uniqid('mp_') . '.png';

            try {
                $writer = new \Endroid\QrCode\Writer\PngWriter();
                $qrCode = \Endroid\QrCode\QrCode::create($qrContent)
                    ->setEncoding(new \Endroid\QrCode\Encoding\Encoding('UTF-8'))
                    ->setSize(300)
                    ->setMargin(8)
                    ->setForegroundColor(new \Endroid\QrCode\Color\Color(0, 0, 0))
                    ->setBackgroundColor(new \Endroid\QrCode\Color\Color(255, 255, 255));
                $writer->write($qrCode)->saveToFile($qrFilePath);
                $qrFiles[] = $qrFilePath;
            } catch (\Exception $e) {
                $qrFilePath = null;
            }

            $pdf->AddPage();

            $marginX  = 6;  $marginY  = 6;
            $cardX    = $marginX;  $cardY = $marginY;
            $cardW    = 210 - ($marginX * 2);
            $cardH    = 95  - ($marginY * 2);
            $vStripW  = 9;
            $perfX    = $cardX + 130;

            // Outer border
            $pdf->SetDrawColor(...$borderGray);
            $pdf->SetLineWidth(0.4);
            $pdf->Rect($cardX, $cardY, $cardW, $cardH);

            // Orange header
            $headerH = 15;
            $pdf->SetFillColor(...$orange);
            $pdf->Rect($cardX, $cardY, $cardW, $headerH, 'F');

            $pdf->YachtIconAuto($cardX + 4, $cardY + 2.5, 11, 10);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->SetXY($cardX + 17, $cardY + 4);
            $pdf->Cell($cardW - 20, 7, 'Boarding Pass', 0, 0, 'L');

            // KET badge (OVERNIGHT / DAY TRIP) top-right of header
            if ($ket) {
                $pdf->SetFont('Arial', 'B', 8);
                $pdf->SetXY($cardX + $cardW - 38, $cardY + 5);
                $pdf->Cell(34, 5, $ket, 0, 0, 'R');
            }
            $pdf->SetTextColor(0, 0, 0);

            $bodyTop = $cardY + $headerH;

            // Dotted separator
            $pdf->SetFillColor(...$borderGray);
            $pdf->DottedLine($perfX, $bodyTop + 2, $perfX, $cardY + $cardH - 2);

            // Vertical boat-name strip
            $pdf->SetDrawColor(...$borderGray);
            $pdf->SetLineWidth(0.3);
            $pdf->Line($cardX + $vStripW, $bodyTop, $cardX + $vStripW, $cardY + $cardH);
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetTextColor(60, 60, 60);
            $stripCenterY = $bodyTop + (($cardY + $cardH) - $bodyTop) / 2;
            $pdf->RotatedText($cardX + $vStripW - 2.5, $stripCenterY + 5, strtoupper($boatName), 90);
            $pdf->SetTextColor(0, 0, 0);

            // Content columns
            $contentX   = $cardX + $vStripW + 4;
            $col1X      = $contentX;
            $col2X      = $col1X + 50;           // shifted right (+4) for more breathing room
            $qrSize     = 26;
            $qrX        = $perfX - $qrSize - 5;
            $lblSz      = 6.0;                   // label font: slightly smaller
            $valSz      = 8.5;                   // value font: reduced from 9.5 → 8.5
            $colW       = 48;                    // wider cell to prevent clipping

            $fieldMain = function ($x, $y, $label, $value, $valueColor = [26, 26, 26])
                use ($pdf, $lblSz, $valSz, $colW) {
                $pdf->SetFont('Arial', '', $lblSz);
                $pdf->SetTextColor(150, 155, 160);
                $pdf->SetXY($x, $y);
                $pdf->Cell($colW, 3.2, strtoupper($label), 0, 2);
                $pdf->SetFont('Arial', 'B', $valSz);
                $pdf->SetTextColor(...$valueColor);
                $pdf->SetXY($x, $y + 3.6);
                $pdf->Cell($colW, 5.0, $value, 0, 2);
                $pdf->SetTextColor(0, 0, 0);
            };

            $rowH = 11.5;   // row height — reduced from 13.5 so 4 rows + captain fit

            $y = $bodyTop + 5;
            $fieldMain($col1X, $y, 'Group',    $groupName);      $y += $rowH;
            $fieldMain($col1X, $y, 'Name',     $passengerName);  $y += $rowH;
            $fieldMain($col1X, $y, 'Boarding', $boardingTime);   $y += $rowH;

            // Seat + ticket code on same row
            $pdf->SetFont('Arial', '', $lblSz);
            $pdf->SetTextColor(150, 155, 160);
            $pdf->SetXY($col1X, $y);
            $pdf->Cell(22, 3.2, 'SEAT NO.', 0, 0);
            $pdf->SetXY($col1X + 24, $y);
            $pdf->Cell(22, 3.2, 'TICKET', 0, 0);

            $pdf->SetFont('Arial', 'B', $valSz);
            $pdf->SetTextColor(...$orange);
            $pdf->SetXY($col1X, $y + 3.6);
            $pdf->Cell(22, 5.0, $seatNumber, 0, 0);
            $pdf->SetTextColor(26, 26, 26);
            $pdf->SetXY($col1X + 24, $y + 3.6);
            $pdf->Cell(22, 5.0, $ticketCode, 0, 0);
            $pdf->SetTextColor(0, 0, 0);

            // Right column — uses slightly tighter spacing so captain fits below
            $y = $bodyTop + 5;
            $direction = ($upload['direction'] === 'RETURN') ? 'Return' : 'Departure';
            $fieldMain($col2X, $y, 'Date',      $formattedDate);   $y += $rowH;
            $fieldMain($col2X, $y, 'From',      'Baywalk');         $y += $rowH;
            $fieldMain($col2X, $y, 'Boat',      $boatName);         $y += $rowH;
            $fieldMain($col2X, $y, 'Direction', $direction);

            // Captain name — sits directly below Direction with proper gap
            if ($captainName) {
                $pdf->SetFont('Arial', '', 5.5);
                $pdf->SetTextColor(180, 180, 180);
                $pdf->SetXY($col2X, $y + 10.5);   // below Direction value
                $pdf->Cell($colW, 3.5, 'Capt. ' . $captainName, 0, 0);
                $pdf->SetTextColor(0, 0, 0);
            }

            // QR code
            if ($qrFilePath && file_exists($qrFilePath)) {
                $pdf->Image($qrFilePath, $qrX, $bodyTop + 4, $qrSize, $qrSize, 'PNG');
                $pdf->SetFont('Arial', 'B', 6.5);
                $pdf->SetTextColor(150, 155, 160);
                $pdf->SetXY($qrX, $bodyTop + 4 + $qrSize + 2);
                $pdf->Cell($qrSize, 3.5, 'SCAN TO CHECK-IN', 0, 0, 'C');
                $pdf->SetTextColor(0, 0, 0);
            }

            // ── STUB (right of dotted line) ───────────────────────
            $stubX  = $perfX + 4;
            $stubW  = ($cardX + $cardW) - $stubX - 4;
            $stubHalf = ($stubW - 3) / 2;
            $lblSS  = 5.6;
            $valSS  = 7.6;

            $fieldStub = function ($x, $y, $w, $label, $value, $vc = [26, 26, 26])
                use ($pdf, $lblSS, $valSS, $labelGray) {
                $pdf->SetFont('Arial', '', $lblSS);
                $pdf->SetTextColor(...$labelGray);
                $pdf->SetXY($x, $y);
                $pdf->Cell($w, 3, strtoupper($label), 0, 2);
                $pdf->SetFont('Arial', 'B', $valSS);
                $pdf->SetTextColor(...$vc);
                $pdf->SetXY($x, $y + 3.4);
                $pdf->Cell($w, 4.5, $value, 0, 2);
                $pdf->SetTextColor(0, 0, 0);
            };

            $sy = $bodyTop + 5;
            $fieldStub($stubX, $sy, $stubW, 'Group',     $groupName);           $sy += 10.5;
            $fieldStub($stubX, $sy, $stubW, 'Passenger', $passengerName);        $sy += 10.5;
            $fieldStub($stubX,              $sy, $stubHalf, 'Boarding', $boardingTime);
            $fieldStub($stubX + $stubHalf + 3, $sy, $stubHalf, 'Type', $ket ?: $direction);
            $sy += 10.5;
            $fieldStub($stubX, $sy, $stubHalf, 'Boat', $boatName);
            $fieldStub($stubX + $stubHalf + 3, $sy, $stubHalf, 'Seat', $seatNumber, $orange);
            $sy += 10.5;
            $fieldStub($stubX, $sy, $stubW, 'Ticket Code', $ticketCode);
        }

        // Clean up temp QR files
        foreach ($qrFiles as $f) {
            @unlink($f);
        }

        $this->response->setContentType('application/pdf');
        $pdf->Output('I', 'boarding-pass-manifest-' . $uploadId . '.pdf');
    }

    // ═══════════════════════════════════════════
    // TICKET EDIT ENDPOINTS
    // ═══════════════════════════════════════════

    /**
     * PUT /api/admin/manifest/tickets/{ticketId}
     * Update a ticket: seat assignment, cancel status, etc.
     * 
     * Body JSON: {
     *   seat_id: int (optional),          // reassign to a specific seat
     *   seat_number: string (optional),   // manual seat override
     *   cancelled: 0|1 (optional),        // toggle cancelled status
     *   checked_in: 0|1 (optional),       // toggle checked-in status
     *   passenger_name: string (optional),
     *   notes: string (optional)
     * }
     */
    public function updateTicket(int $ticketId)
    {
        if (!$this->isAdminUser()) {
            return $this->jsonResponse(['error' => 'Forbidden.'], 403);
        }

        $model = new ManifestTicketModel();
        $ticket = $model->find($ticketId);

        if (!$ticket) {
            return $this->jsonResponse(['error' => 'Ticket not found.'], 404);
        }

        $db = \Config\Database::connect();
        $body = $this->request->getJSON(true) ?? [];
        $updates = [];

        // Handle seat reassignment
        if (isset($body['seat_id']) && $body['seat_id'] !== null) {
            $newSeatId = (int) $body['seat_id'];
            $newSeat = $db->table('seat')
                ->where('id', $newSeatId)
                ->get()
                ->getFirstRow('array');

            if (!$newSeat) {
                return $this->jsonResponse(['error' => 'Seat not found.'], 404);
            }

            // Release old seat if it was assigned
            if ($ticket['seat_id']) {
                $db->table('seat')
                   ->update(['status' => 'available'], ['id' => $ticket['seat_id']]);
            }

            // Mark new seat as booked
            $db->table('seat')
               ->update(['status' => 'booked'], ['id' => $newSeatId]);

            $updates['seat_id'] = $newSeatId;
            $updates['seat_number'] = $newSeat['seat_number'];
        }

        // Manual seat number override (for display)
        if (isset($body['seat_number'])) {
            $updates['seat_number'] = trim($body['seat_number']);
        }

        // Toggle cancelled status
        if (isset($body['cancelled'])) {
            $updates['cancelled'] = (int) $body['cancelled'] ? 1 : 0;
        }

        // Toggle checked-in status
        if (isset($body['checked_in'])) {
            $updates['checked_in'] = (int) $body['checked_in'] ? 1 : 0;
        }

        // Update other fields
        foreach (['passenger_name', 'notes', 'group_name', 'age', 'gender', 'domicile', 'id_passport'] as $field) {
            if (isset($body[$field])) {
                $updates[$field] = trim($body[$field]);
            }
        }

        if (!empty($updates)) {
            $model->update($ticketId, $updates);
        }

        return $this->jsonResponse([
            'message' => 'Ticket updated.',
            'ticket_id' => $ticketId,
        ]);
    }

    /**
     * GET /api/admin/manifest/available-seats/{uploadId}
     * Return all available seats for the boat associated with this upload.
     * Used for seat reassignment UI.
     */
    public function getAvailableSeats(int $uploadId)
    {
        if (!$this->isAdminUser()) {
            return $this->jsonResponse(['error' => 'Forbidden.'], 403);
        }

        $db = \Config\Database::connect();
        $upload = $db->table('manifest_uploads')
            ->where('id', $uploadId)
            ->get()
            ->getFirstRow('array');

        if (!$upload) {
            return $this->jsonResponse(['error' => 'Upload not found.'], 404);
        }

        $seats = $db->table('seat')
            ->where('boat_id', $upload['boat_id'])
            ->orderBy('CAST(seat_number AS UNSIGNED)', 'ASC')
            ->orderBy('seat_number', 'ASC')
            ->get()
            ->getResultArray();

        return $this->jsonResponse([
            'boat_id' => $upload['boat_id'],
            'seats' => $seats,
        ]);
    }

    /**
     * POST /api/admin/manifest/tickets/{ticketId}/toggle-cancel
     * Toggle the cancelled status on a ticket
     */
    public function toggleCancel(int $ticketId)
    {
        if (!$this->isAdminUser()) {
            return $this->jsonResponse(['error' => 'Forbidden.'], 403);
        }

        $model = new ManifestTicketModel();
        $ticket = $model->find($ticketId);

        if (!$ticket) {
            return $this->jsonResponse(['error' => 'Ticket not found.'], 404);
        }

        $newStatus = $ticket['cancelled'] ? 0 : 1;
        $model->update($ticketId, ['cancelled' => $newStatus]);

        return $this->jsonResponse([
            'message' => 'Ticket cancel status toggled.',
            'ticket_id' => $ticketId,
            'cancelled' => (bool) $newStatus,
        ]);
    }

    /**
     * POST /api/admin/manifest/uploads/{uploadId}/force-assign-seats
     * Force seat assignment for an upload that has 0 seats assigned
     * This is a workaround for debugging seat assignment issues
     */
    public function forceAssignSeats(int $uploadId)
    {
        if (!$this->isAdminUser()) {
            return $this->jsonResponse(['error' => 'Forbidden.'], 403);
        }

        $db = \Config\Database::connect();
        
        // Get upload info
        $upload = $db->table('manifest_uploads')->find($uploadId);
        if (!$upload) {
            return $this->jsonResponse(['error' => 'Upload not found.'], 404);
        }

        $boatId = (int) $upload['boat_id'];

        // Count available seats
        $availableCount = $db->table('seat')
            ->where('boat_id', $boatId)
            ->where('status', 'available')
            ->countAllResults();

        log_message('info', "Force assign seats - Boat ID: {$boatId}, Available seats: {$availableCount}");

        if ($availableCount === 0) {
            return $this->jsonResponse([
                'error' => 'No available seats found',
                'boat_id' => $boatId,
                'available_seats' => 0,
                'solution' => 'Run RUN_ME_FIRST.sql to generate seats'
            ], 422);
        }

        // Get passengers that need seats
        $passengers = $db->table('manifest_tickets')
            ->where('upload_id', $uploadId)
            ->where('cancelled', 0)
            ->whereNull('seat_id')
            ->orderBy('seq_no', 'ASC')
            ->get()
            ->getResultArray();

        if (empty($passengers)) {
            return $this->jsonResponse([
                'message' => 'All passengers already have seats assigned',
                'upload_id' => $uploadId
            ]);
        }

        // Get available seats ordered naturally
        $availableSeats = $db->table('seat')
            ->where('boat_id', $boatId)
            ->where('status', 'available')
            ->orderBy('CAST(REGEXP_REPLACE(seat_number, "[A-Z]+", "") AS UNSIGNED)', 'ASC')
            ->orderBy('seat_number', 'ASC')
            ->get()
            ->getResultArray();

        log_message('info', "Force assign - Passengers needing seats: " . count($passengers) . ", Available: " . count($availableSeats));

        // Assign seats sequentially
        $assigned = 0;
        $seatIds = [];

        foreach ($passengers as $i => $passenger) {
            if (!isset($availableSeats[$i])) {
                break; // no more seats
            }

            $seat = $availableSeats[$i];
            
            // Update ticket
            $db->table('manifest_tickets')
                ->where('id', $passenger['id'])
                ->update([
                    'seat_id' => $seat['id'],
                    'seat_number' => $seat['seat_number']
                ]);

            $seatIds[] = $seat['id'];
            $assigned++;

            log_message('info', "Assigned seat {$seat['seat_number']} to passenger {$passenger['passenger_name']} (seq {$passenger['seq_no']})");
        }

        // Mark seats as booked
        if (!empty($seatIds)) {
            $db->table('seat')
                ->whereIn('id', $seatIds)
                ->update(['status' => 'booked']);
        }

        return $this->jsonResponse([
            'message' => 'Seats assigned successfully',
            'upload_id' => $uploadId,
            'passengers_assigned' => $assigned,
            'total_passengers' => count($passengers)
        ]);
    }

    // ═══════════════════════════════════════════
    // MANIFEST FINAL & EXPORT
    // ═══════════════════════════════════════════

    // GET /api/admin/manifest/final/{uploadId}
    // Returns manifest data in final format grouped by KET section
    // ?view=return  →  only OVERNIGHT passengers, return route (origin↔destination swapped)
    public function getManifestFinal(int $uploadId)
    {
        if (!$this->isAdminUser()) {
            return $this->jsonResponse(['error' => 'Forbidden.'], 403);
        }

        $db   = \Config\Database::connect();
        $view = $this->request->getVar('view') ?? 'departure'; // 'departure' | 'return'

        // Upload meta
        $upload = $db->table('manifest_uploads as u')
            ->select('u.*, b.boat_name as boat_name_ref')
            ->join('boat b', 'u.boat_id = b.id', 'left')
            ->where('u.id', $uploadId)
            ->get()
            ->getFirstRow('array');

        if (!$upload) {
            return $this->jsonResponse(['error' => 'Upload not found.'], 404);
        }

        // All tickets ordered by ket section then seq_no
        $qb = $db->table('manifest_tickets')
            ->where('upload_id', $uploadId)
            ->where('cancelled', 0)
            ->orderBy('seq_no', 'ASC');

        // Return view: only OVERNIGHT passengers
        if ($view === 'return') {
            $qb->like('ket', 'OVERNIGHT', 'both');
        }

        $tickets = $qb->get()->getResultArray();

        // For return view, swap origin/destination in upload meta
        if ($view === 'return') {
            $origOrigin      = $upload['origin'];
            $upload['origin']      = $upload['destination'];
            $upload['destination'] = $origOrigin;
            $upload['direction']   = 'RETURN';
        }

        // Group tickets by KET section
        $overnight = [];
        $daytrip   = [];
        $staff     = [];
        $foc       = [];
        $vendor    = [];
        $other     = [];

        foreach ($tickets as $t) {
            $ket = strtoupper(trim($t['ket'] ?? ''));
            if (str_contains($ket, 'OVERNIGHT'))    $overnight[] = $t;
            elseif (str_contains($ket, 'DAY'))      $daytrip[]   = $t;
            elseif ($ket === 'STAFF')               $staff[]     = $t;
            elseif ($ket === 'FOC')                 $foc[]       = $t;
            elseif ($ket === 'VENDOR')              $vendor[]    = $t;
            else                                    $other[]     = $t;
        }

        // Parse abk_names
        $abkNames = [];
        if (!empty($upload['abk_names'])) {
            $decoded  = json_decode($upload['abk_names'], true);
            $abkNames = is_array($decoded) ? $decoded : [$upload['abk_names']];
        }

        $sections = $view === 'return'
            // Return manifest: only overnight section
            ? [['ket' => 'OVERNIGHT (RETURN)', 'tickets' => $overnight, 'count' => count($overnight)]]
            // Departure manifest: all sections
            : [
                ['ket' => 'OVERNIGHT', 'tickets' => $overnight, 'count' => count($overnight)],
                ['ket' => 'DAY TRIP',  'tickets' => $daytrip,   'count' => count($daytrip)],
                ['ket' => 'STAFF',     'tickets' => $staff,     'count' => count($staff)],
                ['ket' => 'FOC',       'tickets' => $foc,       'count' => count($foc)],
                ['ket' => 'VENDOR',    'tickets' => $vendor,    'count' => count($vendor)],
            ];

        return $this->jsonResponse([
            'upload'   => array_merge($upload, ['abk_names_array' => $abkNames]),
            'sections' => $sections,
            'total'    => count($tickets),
            'view'     => $view,
        ]);
    }

    // GET /api/admin/manifest/export-excel/{uploadId}
    // Streams an .xlsx file matching the manifest template format
    public function exportExcel(int $uploadId)
    {
        if (!$this->isAdminUser()) {
            return $this->jsonResponse(['error' => 'Forbidden.'], 403);
        }

        $db = \Config\Database::connect();

        $upload = $db->table('manifest_uploads as u')
            ->select('u.*, b.boat_name as boat_name_ref')
            ->join('boat b', 'u.boat_id = b.id', 'left')
            ->where('u.id', $uploadId)
            ->get()
            ->getFirstRow('array');

        if (!$upload) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Upload not found.']);
        }

        $tickets = $db->table('manifest_tickets')
            ->where('upload_id', $uploadId)
            ->orderBy('seq_no', 'ASC')
            ->get()
            ->getResultArray();

        // Sort by section order so sections don't repeat
        $sectionOrder = ['OVERNIGHT' => 0, 'DAY TRIP' => 1, 'DAYTRIP' => 1,
                         'STAFF' => 2, 'FOC' => 3, 'VENDOR' => 4];
        usort($tickets, function($a, $b) use ($sectionOrder) {
            $ketA = strtoupper(trim($a['ket'] ?? ''));
            $ketB = strtoupper(trim($b['ket'] ?? ''));
            $keyA = str_contains($ketA, 'DAY') ? 'DAY TRIP'
                  : (str_contains($ketA, 'OVERNIGHT') ? 'OVERNIGHT' : $ketA);
            $keyB = str_contains($ketB, 'DAY') ? 'DAY TRIP'
                  : (str_contains($ketB, 'OVERNIGHT') ? 'OVERNIGHT' : $ketB);
            $orderA = $sectionOrder[$keyA] ?? 99;
            $orderB = $sectionOrder[$keyB] ?? 99;
            if ($orderA !== $orderB) return $orderA - $orderB;
            // Within same section, keep original seq_no order
            return (int)$a['seq_no'] - (int)$b['seq_no'];
        });

        // ── Setup ────────────────────────────────────────────────────
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Manifest');

        // Boat color (ARGB)
        $boatColors = [
            'la luna'   => 'FF1800AD',
            'la vela'   => 'FFBF0000',
            'labrisa'  => 'FFFF57D0',
            'mola mola' => 'FFFF914D',
            'mola-mola' => 'FFFF914D',
            'la casa'   => 'FFA77AFF',
            'alma'      => 'FFFFDE59',
        ];
        $boatLower  = strtolower($upload['boat_name'] ?? '');
        $accentARGB = 'FFF2881C';
        foreach ($boatColors as $k => $v) {
            if (str_contains($boatLower, $k)) { $accentARGB = $v; break; }
        }

        // ── Counts ───────────────────────────────────────────────────
        $abkNames = [];
        if (!empty($upload['abk_names'])) {
            $decoded = json_decode($upload['abk_names'], true);
            $abkNames = is_array($decoded)
                ? $decoded
                : array_filter(array_map('trim', explode(',', $upload['abk_names'])));
        }
        $crewStr = implode(', ', $abkNames);

        $allTickets = $tickets;
        $non = array_filter($allTickets, fn($t) => (int)$t['cancelled'] !== 1);
        $ovCount  = count(array_filter($non, fn($t) => str_contains(strtoupper($t['ket']??''), 'OVERNIGHT')));
        $dtCount  = count(array_filter($non, fn($t) => str_contains(strtoupper($t['ket']??''), 'DAY')));
        $stCount  = count(array_filter($non, fn($t) => strtoupper($t['ket']??'') === 'STAFF'));
        $focCount = count(array_filter($non, fn($t) => strtoupper($t['ket']??'') === 'FOC'));
        $vnCount  = count(array_filter($non, fn($t) => strtoupper($t['ket']??'') === 'VENDOR'));

        $ovCancel = count(array_filter($allTickets, fn($t) =>
            (int)$t['cancelled'] === 1 && str_contains(strtoupper($t['ket']??''), 'OVERNIGHT')));
        $dtCancel = count(array_filter($allTickets, fn($t) =>
            (int)$t['cancelled'] === 1 && str_contains(strtoupper($t['ket']??''), 'DAY')));
        $totalCount = count($non);

        // ── Helpers ──────────────────────────────────────────────────
        $S = fn($cell) => $sheet->getStyle($cell);

        $fill = function(string $range, string $argb) use ($S) {
            $S($range)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB($argb);
        };
        $bold = function(string $range, int $sz = 10, string $color = 'FF000000') use ($S) {
            $S($range)->getFont()->setBold(true)->setSize($sz);
            $S($range)->getFont()->getColor()->setARGB($color);
        };
        $font = function(string $range, int $sz = 10, string $color = 'FF000000', bool $b = false) use ($S) {
            $S($range)->getFont()->setBold($b)->setSize($sz);
            $S($range)->getFont()->getColor()->setARGB($color);
        };
        $align = function(string $range, string $h = 'left', string $v = 'center') use ($S) {
            $S($range)->getAlignment()->setHorizontal($h)->setVertical($v)->setWrapText(true);
        };
        $border = function(string $range, string $style = 'thin') use ($S) {
            $S($range)->getBorders()->getAllBorders()
                ->setBorderStyle($style);
        };
        $hBorder = function(string $range) use ($S) {
            // Only bottom border for section separators
            $S($range)->getBorders()->getBottom()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        };

        $set = fn(string $cell, $val) => $sheet->setCellValue($cell, $val);
        $merge = fn(string $range) => $sheet->mergeCells($range);

        // ── Row heights ──────────────────────────────────────────────
        $sheet->getDefaultRowDimension()->setRowHeight(15);

        // ════════════════════════════════════════════════════════════
        // ROW 1 — Title
        // ════════════════════════════════════════════════════════════
        $merge('A1:L1');
        $titleText = 'MANIFEST PENUMPANG '
            . ($upload['direction'] === 'RETURN' ? 'KEPULANGAN' : 'KEBERANGKATAN');
        $set('A1', $titleText);
        $fill('A1:L1', $accentARGB);
        $bold('A1', 14, 'FFFFFFFF');
        $align('A1', 'center', 'center');
        $sheet->getRowDimension(1)->setRowHeight(28);

        // ════════════════════════════════════════════════════════════
        // ROW 2 — Date
        // ════════════════════════════════════════════════════════════
        $merge('A2:L2');
        $dateLabel = $upload['trip_date']
            ? strtoupper(date('l, d F Y', strtotime($upload['trip_date'])))
            : '';
        // Translate day names to Indonesian
        $dateLabel = str_replace(
            ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'],
            ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'],
            $dateLabel
        );
        $set('A2', $dateLabel);
        $fill('A2:L2', $accentARGB);
        $font('A2', 12, 'FFFFFFFF');
        $align('A2', 'center', 'center');
        $sheet->getRowDimension(2)->setRowHeight(22);

        // ════════════════════════════════════════════════════════════
        // ROWS 3-8 — Meta (left: boat info, right: KET counts)
        // Layout matches template exactly:
        //   A    B            C   D      E   F..H    I              J
        //   KAPAL MARINA...        ASAL   BAYWALK     OVERNIGHT      137
        //   GT    (blank)          TUJUAN SEPA        DAY TRIP       41
        //   BENDERA INDONESIA      NAHKODA ROZIM      STAFF          0
        //                          CREW   JEK JULIO   FOC            0
        //                          GRO    ANDI        VENDOR         0
        //                                             OV CANCEL      0
        //                                             DT CANCEL      0
        //                                             TOTAL          178
        // ════════════════════════════════════════════════════════════
        $boatName  = $upload['boat_name']    ?? '';
        $origin    = $upload['origin']       ?? '';
        $dest      = $upload['destination']  ?? '';
        $captain   = $upload['captain_name'] ?? '';
        $gro       = $upload['gro_name']     ?? '';

        $metaLeft = [
            3 => ['A' => 'KAPAL',   'B' => strtoupper($boatName)],
            4 => ['A' => 'GT',      'B' => ''],
            5 => ['A' => 'BENDERA', 'B' => 'INDONESIA'],
            6 => ['A' => '',        'B' => ''],
            7 => ['A' => '',        'B' => ''],
            8 => ['A' => '',        'B' => ''],
        ];

        $metaMid = [
            3 => ['D' => 'ASAL',    'E' => strtoupper($origin)],
            4 => ['D' => 'TUJUAN',  'E' => strtoupper($dest)],
            5 => ['D' => 'NAHKODA', 'E' => strtoupper($captain)],
            6 => ['D' => 'CREW',    'E' => strtoupper($crewStr)],
            7 => ['D' => 'GRO',     'E' => strtoupper($gro)],
            8 => ['D' => '',        'E' => ''],
        ];

        $metaRight = [
            3  => ['I' => 'OVERNIGHT',      'J' => $ovCount],
            4  => ['I' => 'DAY TRIP',        'J' => $dtCount],
            5  => ['I' => 'STAFF',           'J' => $stCount],
            6  => ['I' => 'FOC',             'J' => $focCount],
            7  => ['I' => 'VENDOR',          'J' => $vnCount],
            8  => ['I' => 'OVERNIGHT CANCEL','J' => $ovCancel],
            9  => ['I' => 'DAY TRIP CANCEL', 'J' => $dtCancel],
            10 => ['I' => 'TOTAL',           'J' => $totalCount],
        ];

        // Write meta left
        foreach ($metaLeft as $row => $cols) {
            if ($cols['A']) {
                $set("A{$row}", $cols['A']);
                $bold("A{$row}", 9);
                $align("A{$row}", 'right');
            }
            if ($cols['B']) {
                $merge("B{$row}:C{$row}");
                $set("B{$row}", $cols['B']);
                $font("B{$row}", 9, 'FF000000', true);
            }
        }

        // Write meta mid
        foreach ($metaMid as $row => $cols) {
            if ($cols['D']) {
                $set("D{$row}", $cols['D']);
                $bold("D{$row}", 9);
                $align("D{$row}", 'right');
            }
            if ($cols['E']) {
                $merge("E{$row}:H{$row}");
                $set("E{$row}", $cols['E']);
                $font("E{$row}", 9, 'FF000000', true);
            }
        }

        // Write meta right (counts)
        foreach ($metaRight as $row => $cols) {
            $set("I{$row}", $cols['I']);
            $set("J{$row}", $cols['J']);
            $bold("I{$row}", 9);
            $align("I{$row}", 'right');
            $bold("J{$row}", 9);
            $align("J{$row}", 'center');
        }

        // Add TOTAL row background
        $fill('I10:J10', 'FFFFE0B2');

        // ════════════════════════════════════════════════════════════
        // ROW 11 — Column headers
        // ════════════════════════════════════════════════════════════
        $headerRow = 11;
        $cols = ['A'=>'NO','B'=>'KET','C'=>'NAMA','D'=>'GRUP','E'=>'AGENT',
                 'F'=>'PACKAGE','G'=>'PAX','H'=>'NOTES','I'=>'UMUR',
                 'J'=>'GENDER','K'=>'DOMISILI','L'=>'ID / PASPORT'];

        foreach ($cols as $col => $label) {
            $cell = "{$col}{$headerRow}";
            $set($cell, $label);
            $fill($cell, $accentARGB);
            $bold($cell, 9, 'FFFFFFFF');
            $align($cell, 'center', 'center');
            $border($cell);
        }
        $sheet->getRowDimension($headerRow)->setRowHeight(18);

        // ════════════════════════════════════════════════════════════
        // ROWS 12+ — Data rows grouped by KET section
        // ════════════════════════════════════════════════════════════
        $dataRow = $headerRow + 1;
        $currentKet = null;

        // Section colors (lighter than header)
        $sectionBg = [
            'OVERNIGHT' => 'FFDCE6F1',
            'DAY TRIP'  => 'FFFFEBCC',
            'DAYTRIP'   => 'FFFFEBCC',
            'STAFF'     => 'FFD9EAD3',
            'FOC'       => 'FFE1D5E7',
            'VENDOR'    => 'FFD5C5B8',
        ];
        $sectionLabels = [
            'OVERNIGHT' => 'MENGINAP',
            'DAY TRIP'  => 'DAY TRIP',
            'STAFF'     => 'STAFF',
            'FOC'       => 'FOC',
            'VENDOR'    => 'VENDOR',
        ];
        $sectionCounts = [
            'OVERNIGHT' => $ovCount,
            'DAY TRIP'  => $dtCount,
            'STAFF'     => $stCount,
            'FOC'       => $focCount,
            'VENDOR'    => $vnCount,
        ];

        foreach ($tickets as $t) {
            $ket = strtoupper(trim($t['ket'] ?? ''));
            $ketKey = str_contains($ket, 'DAY') ? 'DAY TRIP'
                    : (str_contains($ket, 'OVERNIGHT') ? 'OVERNIGHT' : $ket);

            // Section header row when section changes
            if ($ketKey !== $currentKet && isset($sectionLabels[$ketKey])) {
                $currentKet = $ketKey;
                $cnt = $sectionCounts[$ketKey] ?? 0;
                $sLabel = $sectionLabels[$ketKey];

                $merge("A{$dataRow}:L{$dataRow}");
                $set("A{$dataRow}", "{$sLabel}                    {$cnt}   PAX");
                $bgColor = $sectionBg[$ketKey] ?? 'FFF5F5F5';
                $fill("A{$dataRow}:L{$dataRow}", $bgColor);
                $bold("A{$dataRow}", 10);
                $align("A{$dataRow}", 'left', 'center');
                $border("A{$dataRow}");
                $sheet->getRowDimension($dataRow)->setRowHeight(16);
                $dataRow++;
            }

            $isCancelled = (int)$t['cancelled'] === 1;
            $bgColor = $isCancelled ? 'FFFFCCCC' : ($sectionBg[$ketKey] ?? 'FFFFFFFF');

            $rowData = [
                'A' => $t['seq_no']        ?? '',
                'B' => $t['ket']           ?? '',
                'C' => $t['passenger_name']?? '',
                'D' => $t['group_name']    ?? '',
                'E' => $t['agent']         ?? '',
                'F' => $t['package']       ?? '',
                'G' => $t['pax_count']     ?? '',
                'H' => $t['notes']         ?? '',
                'I' => $t['age']           ?? '',
                'J' => $t['gender']        ?? '',
                'K' => $t['domicile']      ?? '',
                'L' => $t['id_passport']   ?? '',
            ];

            foreach ($rowData as $col => $val) {
                $cell = "{$col}{$dataRow}";
                $sheet->setCellValueExplicit(
                    $cell,
                    (string)$val,
                    \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                );
                $fill($cell, $bgColor);
                $font($cell, 9);
                $border($cell, \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_HAIR);
                if ($isCancelled) {
                    $S($cell)->getFont()->setStrikethrough(true);
                    $S($cell)->getFont()->getColor()->setARGB('FF999999');
                }
            }
            // NO column center + bold
            $align("A{$dataRow}", 'center', 'center');
            $align("G{$dataRow}", 'center', 'center');
            $sheet->getRowDimension($dataRow)->setRowHeight(14);
            $dataRow++;
        }

        // ════════════════════════════════════════════════════════════
        // Footer — Date + Signature
        // ════════════════════════════════════════════════════════════
        $footerRow = $dataRow + 2;
        $dateStr = $upload['trip_date']
            ? date('d F Y', strtotime($upload['trip_date']))
            : '';
        // Translate month
        $months = ['January'=>'Januari','February'=>'Februari','March'=>'Maret',
            'April'=>'April','May'=>'Mei','June'=>'Juni','July'=>'Juli',
            'August'=>'Agustus','September'=>'September','October'=>'Oktober',
            'November'=>'November','December'=>'Desember'];
        $dateStr = strtr($dateStr, $months);

        $merge("I{$footerRow}:L{$footerRow}");
        $set("I{$footerRow}", $dateStr);
        $align("I{$footerRow}", 'center', 'center');
        $font("I{$footerRow}", 10);

        $sigRow = $footerRow + 4;
        $merge("I{$sigRow}:L{$sigRow}");
        $set("I{$sigRow}", strtoupper($upload['captain_name'] ?? ''));
        $bold("I{$sigRow}", 11);
        $align("I{$sigRow}", 'center', 'center');
        $S("I{$sigRow}")->getBorders()->getTop()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM);

        // ════════════════════════════════════════════════════════════
        // Column widths (A–L)
        // ════════════════════════════════════════════════════════════
        $colWidths = ['A'=>5,'B'=>13,'C'=>30,'D'=>25,'E'=>18,'F'=>14,
                      'G'=>5,'H'=>22,'I'=>7,'J'=>8,'K'=>20,'L'=>22];
        foreach ($colWidths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        // Freeze header row
        $sheet->freezePane('A12');

        // ── Stream ────────────────────────────────────────────────────
        $writer   = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'manifest-' . strtolower(str_replace(' ', '-', $boatName))
                  . '-' . ($upload['trip_date'] ?? date('Ymd'))
                  . '.xlsx';

        $tempFile = tempnam(sys_get_temp_dir(), 'manifest_xlsx_');
        $writer->save($tempFile);
        $fileContent = file_get_contents($tempFile);
        @unlink($tempFile);

        while (ob_get_level() > 0) { ob_end_clean(); }

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Cache-Control', 'max-age=0, no-store')
            ->setHeader('Pragma', 'public')
            ->setHeader('Content-Length', (string) strlen($fileContent))
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Expose-Headers', 'Content-Disposition')
            ->setStatusCode(200)
            ->setBody($fileContent);
    }


    // ═══════════════════════════════════════════
    // CHECK-IN ENDPOINTS
    // ═══════════════════════════════════════════

    // GET /api/admin/manifest/group-by-code/{code}
    // Handles both passenger QR (NAMA_MARINE_MANIFEST_*) and crew QR (CREW_*)
    public function getGroupByCode(string $code)
    {
        if (!$this->isAdminUser()) {
            return $this->jsonResponse(['error' => 'Forbidden.'], 403);
        }

        $db = \Config\Database::connect();

        // ── CREW QR: starts with CREW_ ────────────────────────────────────
        if (str_starts_with(strtoupper($code), 'CREW_')) {
            $crew = $db->table('crew')
                ->where('qr_code', $code)
                ->get()->getFirstRow('array');

            if (!$crew) {
                return $this->jsonResponse(['error' => 'Crew QR code tidak dikenali.'], 404);
            }

            $today = date('Y-m-d');
            $now   = date('Y-m-d H:i:s');

            // Today's assignments
            $assignments = $db->table('crew_assignments ca')
                ->select('ca.*, b.boat_name, s.date as schedule_date')
                ->join('boat b', 'b.id = ca.boat_id', 'left')
                ->join('schedule s', 's.id = ca.schedule_id', 'left')
                ->where('ca.crew_id', $crew['id'])
                ->where('ca.trip_date', $today)
                ->get()->getResultArray();

            // Already checked in today?
            $existingCheckin = $db->table('crew_checkins')
                ->where('crew_id', $crew['id'])
                ->where('DATE(checked_in_at)', $today)
                ->orderBy('id', 'DESC')
                ->get()->getFirstRow('array');

            $alreadyIn = $existingCheckin !== null;

            // Auto check-in if not already done
            if (!$alreadyIn) {
                $assignment = !empty($assignments) ? $assignments[0] : null;
                $db->table('crew_checkins')->insert([
                    'crew_id'       => $crew['id'],
                    'assignment_id' => $assignment['id'] ?? null,
                    'schedule_id'   => $assignment['schedule_id'] ?? null,
                    'checked_in_at' => $now,
                    'note'          => 'Auto check-in via scanner',
                ]);
            }

            // Fetch fresh checkins
            $checkinsToday = $db->table('crew_checkins')
                ->where('crew_id', $crew['id'])
                ->where('DATE(checked_in_at)', $today)
                ->orderBy('checked_in_at', 'DESC')
                ->get()->getResultArray();

            return $this->jsonResponse([
                'type'               => 'crew',
                'crew'               => $crew,
                'today_assignments'  => $assignments,
                'checkins_today'     => $checkinsToday,
                'already_checked_in' => $alreadyIn,
                'just_checked_in'    => !$alreadyIn,
                'checked_in_at'      => $alreadyIn
                    ? $existingCheckin['checked_in_at']
                    : $now,
            ]);
        }

        // ── PASSENGER QR ──────────────────────────────────────────────────
        // Strip QR prefix: "NAMA_MARINE_MANIFEST_TKT-123-0001" → "TKT-123-0001"
        $cleanCode = preg_replace('/^NAMA_MARINE_MANIFEST_/i', '', $code);

        // Detect return boarding pass: ticket code ends in -R (e.g. TKT-5-0001-R)
        $isReturn  = preg_match('/-R$/i', $cleanCode);
        // Strip the -R suffix so we find the base ticket in the database
        $lookupCode = preg_replace('/-R$/i', '', $cleanCode);

        // Try: ticket_code, id, id_passport, group_name
        $ticket = $db->table('manifest_tickets')
            ->groupStart()
                ->where('ticket_code', $lookupCode)
                ->orWhere('ticket_code', $cleanCode)  // fallback: try with -R too
                ->orWhere('ticket_code', $code)
                ->orWhere('id', (int)$code)
                ->orWhere('id_passport', $lookupCode)
                ->orWhere('group_name', $lookupCode)
            ->groupEnd()
            ->orderBy('id', 'DESC')
            ->get()
            ->getFirstRow('array');

        if (!$ticket) {
            return $this->jsonResponse(['error' => 'Ticket or group not found.'], 404);
        }

        // Get upload info - trip_date already stored in manifest_uploads
        $upload = $db->table('manifest_uploads as u')
            ->select('u.*, b.boat_name')
            ->join('boat b', 'u.boat_id = b.id', 'left')
            ->where('u.id', $ticket['upload_id'])
            ->get()
            ->getFirstRow('array');

        if (!$upload) {
            return $this->jsonResponse(['error' => 'Upload not found.'], 404);
        }

        // Get all tickets in the same group
        $tickets = $db->table('manifest_tickets')
            ->where('upload_id', $ticket['upload_id'])
            ->where('group_name', $ticket['group_name'])
            ->orderBy('seq_no', 'ASC')
            ->get()
            ->getResultArray();

        return $this->jsonResponse([
            'type'              => 'passenger',
            'scanned_ticket_id' => (int)$ticket['id'],
            'is_return'         => (bool)$isReturn,   // true when scanned from return boarding pass
            'group_name'        => $ticket['group_name'],
            'upload_id'         => $ticket['upload_id'],
            'boat_name'         => $upload['boat_name'],
            'trip_date'         => $upload['trip_date'],
            'direction'         => $isReturn ? 'RETURN' : $upload['direction'],
            'tickets'           => $tickets,
        ]);
    }

    // POST /api/admin/manifest/checkin-bulk
    // Bulk check-in multiple tickets
    public function checkinBulk()
    {
        if (!$this->isAdminUser()) {
            return $this->jsonResponse(['error' => 'Forbidden.'], 403);
        }

        $json = $this->request->getJSON(true);
        $ticketIds = $json['ticket_ids'] ?? [];

        if (empty($ticketIds) || !is_array($ticketIds)) {
            return $this->jsonResponse(['error' => 'ticket_ids array is required.'], 400);
        }

        $db = \Config\Database::connect();
        
        // Update all tickets to checked_in = 1
        $db->table('manifest_tickets')
            ->whereIn('id', $ticketIds)
            ->where('cancelled', 0) // Only check-in non-cancelled tickets
            ->update(['checked_in' => 1]);

        $affectedRows = $db->affectedRows();

        return $this->jsonResponse([
            'message' => "Checked in {$affectedRows} passenger(s).",
            'checked_in_count' => $affectedRows
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // OPTIONS preflight (called by browser for all /api/admin/manifest/* )
    // ─────────────────────────────────────────────────────────────
    public function preflight()
    {
        return $this->options();
    }

    // ═══════════════════════════════════════════════════════════════════
    // GET /api/admin/manifest/crew-checkins/{scheduleId}
    // Returns crew members assigned to this schedule + their check-in status
    // ═══════════════════════════════════════════════════════════════════
    public function getCrewCheckins(int $scheduleId)
    {
        if (!$this->isAdminUser()) {
            return $this->jsonResponse(['error' => 'Forbidden.'], 403);
        }

        $db    = \Config\Database::connect();
        $today = date('Y-m-d');

        // Get schedule info to know trip_date
        $schedule = $db->table('schedule')
            ->where('id', $scheduleId)
            ->get()->getFirstRow('array');

        $tripDate = $schedule ? substr($schedule['date'], 0, 10) : $today;

        // All crew assigned to this schedule OR same trip_date
        $assignments = $db->table('crew_assignments ca')
            ->select('ca.id as assignment_id, ca.direction, ca.notes as assignment_notes,
                      c.id as crew_id, c.name, c.role, c.phone, c.qr_code,
                      b.boat_name')
            ->join('crew c',  'c.id = ca.crew_id',  'left')
            ->join('boat b',  'b.id = ca.boat_id',  'left')
            ->groupStart()
                ->where('ca.schedule_id', $scheduleId)
                ->orWhere('ca.trip_date',  $tripDate)
            ->groupEnd()
            ->where('c.active', 1)
            ->orderBy('c.role', 'ASC')
            ->orderBy('c.name', 'ASC')
            ->get()->getResultArray();

        foreach ($assignments as &$row) {
            $checkin = $db->table('crew_checkins')
                ->where('crew_id', $row['crew_id'])
                ->groupStart()
                    ->where('assignment_id', $row['assignment_id'])
                    ->orWhere('DATE(checked_in_at)', $tripDate)
                ->groupEnd()
                ->orderBy('id', 'DESC')
                ->get()->getFirstRow('array');

            $row['checked_in']    = $checkin ? true : false;
            $row['checked_in_at'] = $checkin['checked_in_at'] ?? null;
        }
        unset($row);

        return $this->jsonResponse([
            'schedule_id' => $scheduleId,
            'trip_date'   => $tripDate,
            'crew'        => $assignments,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════
    // POST /api/admin/manifest/tickets/switch-seat
    // Body: { ticket_a_id, ticket_b_id }
    // Swaps the seat assignments between two passengers.
    // ═══════════════════════════════════════════════════════════════════
    public function switchSeat()
    {
        if (!$this->isAdminUser()) {
            return $this->jsonResponse(['error' => 'Forbidden.'], 403);
        }

        $db   = \Config\Database::connect();
        $body = $this->request->getJSON(true) ?? [];

        $idA = (int) ($body['ticket_a_id'] ?? 0);
        $idB = (int) ($body['ticket_b_id'] ?? 0);

        if (!$idA || !$idB || $idA === $idB) {
            return $this->jsonResponse(['error' => 'ticket_a_id and ticket_b_id are required and must be different.'], 422);
        }

        $ticketA = $db->table('manifest_tickets')->where('id', $idA)->get()->getFirstRow('array');
        $ticketB = $db->table('manifest_tickets')->where('id', $idB)->get()->getFirstRow('array');

        if (!$ticketA || !$ticketB) {
            return $this->jsonResponse(['error' => 'One or both tickets not found.'], 404);
        }

        // Swap seat_id + seat_number
        $db->table('manifest_tickets')->update([
            'seat_id'     => $ticketB['seat_id'],
            'seat_number' => $ticketB['seat_number'],
        ], ['id' => $idA]);

        $db->table('manifest_tickets')->update([
            'seat_id'     => $ticketA['seat_id'],
            'seat_number' => $ticketA['seat_number'],
        ], ['id' => $idB]);

        return $this->jsonResponse([
            'message'  => 'Seats switched successfully.',
            'ticket_a' => [
                'id'          => $idA,
                'name'        => $ticketA['passenger_name'],
                'old_seat'    => $ticketA['seat_number'],
                'new_seat'    => $ticketB['seat_number'],
            ],
            'ticket_b' => [
                'id'          => $idB,
                'name'        => $ticketB['passenger_name'],
                'old_seat'    => $ticketB['seat_number'],
                'new_seat'    => $ticketA['seat_number'],
            ],
        ]);
    }
}

    // ═══════════════════════════════════════════════════════════════════
    // POST /api/admin/manifest/send-group-qr-emails
    // Blast email: send group QR codes to all groups in a manifest upload
    // 
    // Body JSON: { upload_id: int, (optional) email_template: 'default'|'custom' }
    // ═══════════════════════════════════════════════════════════════════
    public function sendGroupQrEmails()
    {
        if (!$this->isAdminUser()) {
            return $this->jsonResponse(['error' => 'Forbidden.'], 403);
        }

        $db = \Config\Database::connect();
        $body = $this->request->getJSON(true) ?? [];
        $uploadId = (int) ($body['upload_id'] ?? 0);

        if (!$uploadId) {
            return $this->jsonResponse(['error' => 'upload_id is required.'], 422);
        }

        // Get upload with group QR codes
        $upload = $db->table('manifest_uploads')
            ->where('id', $uploadId)
            ->get()
            ->getFirstRow('array');

        if (!$upload) {
            return $this->jsonResponse(['error' => 'Upload not found.'], 404);
        }

        if ($upload['qr_generation_status'] !== 'generated') {
            return $this->jsonResponse([
                'error' => 'Group QR codes not generated for this upload.',
                'status' => $upload['qr_generation_status']
            ], 422);
        }

        // Parse group QR codes JSON
        $groupQrs = [];
        try {
            $groupQrs = json_decode($upload['group_qr_codes'], true) ?? [];
        } catch (\Exception $e) {
            return $this->jsonResponse(['error' => 'Failed to parse group QR codes.'], 500);
        }

        if (empty($groupQrs)) {
            return $this->jsonResponse(['error' => 'No group QR codes found.'], 404);
        }

        // Get unique group emails (from first passenger in each group)
        $groups = [];
        $tickets = $db->table('manifest_tickets')
            ->where('upload_id', $uploadId)
            ->where('cancelled', 0)
            ->orderBy('group_name', 'ASC')
            ->orderBy('seq_no', 'ASC')
            ->get()
            ->getResultArray();

        // Group tickets by group_name and collect contact info
        $groupContactMap = [];
        foreach ($tickets as $t) {
            $grp = $t['group_name'] ?? '__solo__';
            if (!isset($groupContactMap[$grp])) {
                $groupContactMap[$grp] = [
                    'group_name' => $grp,
                    'lead_name'  => $t['passenger_name'],
                    'members'    => [],
                ];
            }
            $groupContactMap[$grp]['members'][] = $t['passenger_name'];
        }

        // Send emails
        $sentCount = 0;
        $failedGroups = [];
        $emailService = \Config\Services::email();

        foreach ($groupContactMap as $groupName => $groupInfo) {
            // Get QR for this group
            $groupQrData = $groupQrs[$groupName] ?? null;
            if (!$groupQrData) {
                $failedGroups[] = $groupName;
                continue;
            }

            // TODO: Get email address for this group (from booking or form)
            // For MVP, skip if no email available
            $groupEmail = $body['group_emails'][$groupName] ?? null;
            if (!$groupEmail) {
                // Try to infer from form submission or customer booking
                // For now, log and skip
                log_message('warning', "No email found for group: {$groupName}");
                $failedGroups[] = $groupName;
                continue;
            }

            // Compose email
            $emailSubject = "🎫 Boarding Pass QR Code - {$groupName}";
            $emailBody = $this->buildGroupQrEmailBody($groupInfo, $groupQrData, $upload);

            $emailService->setTo($groupEmail);
            $emailService->setSubject($emailSubject);
            $emailService->setMessage($emailBody);
            $emailService->setMailType('html');

            // Try to send
            if ($emailService->send(false)) {
                $sentCount++;
            } else {
                log_message('error', "Failed to send email to {$groupEmail} (group: {$groupName})");
                $failedGroups[] = $groupName;
            }

            $emailService->clear();
        }

        // Update send status
        $db->table('manifest_uploads')
            ->where('id', $uploadId)
            ->update(['qr_emails_sent_at' => date('Y-m-d H:i:s')]);

        return $this->jsonResponse([
            'message' => "Sent {$sentCount} emails",
            'sent_count' => $sentCount,
            'total_groups' => count($groupContactMap),
            'failed_groups' => $failedGroups,
        ]);
    }

    // ─── Helper: build email body for group QR ───────────────────────────────
    private function buildGroupQrEmailBody(array $groupInfo, array $qrData, array $upload): string
    {
        $groupName = $groupInfo['group_name'] ?? 'Group';
        $leadName = $groupInfo['lead_name'] ?? 'Valued Guest';
        $membersList = implode(', ', $groupInfo['members'] ?? []);
        $boatName = $upload['boat_name'] ?? 'NAMA Boat';
        $origin = $upload['origin'] ?? 'Baywalk';
        $dest = $upload['destination'] ?? 'Sepa';
        $tripDate = $upload['trip_date'] ? date('d F Y', strtotime($upload['trip_date'])) : 'TBA';
        $qrImage = $qrData['qr_data_url'] ?? null;

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #F2881C; color: white; padding: 20px; text-align: center; border-radius: 4px; }
        .header h1 { margin: 0; }
        .body { background-color: #f9f9f9; padding: 20px; margin-top: 20px; }
        .qr-section { text-align: center; margin: 20px 0; }
        .qr-section img { max-width: 300px; }
        .trip-info { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #F2881C; }
        .trip-info p { margin: 5px 0; }
        .footer { text-align: center; color: #999; font-size: 12px; margin-top: 20px; }
        .btn { display: inline-block; background-color: #F2881C; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎫 Your Boarding Pass QR Code</h1>
            <p>Ready for Check-in</p>
        </div>

        <div class="body">
            <p>Dear <strong>{$leadName}</strong>,</p>

            <p>Your boarding pass QR code is ready! Please scan the QR code below at our admin counter to check in and generate your boarding pass.</p>

            <div class="trip-info">
                <p><strong>Group:</strong> {$groupName}</p>
                <p><strong>Passengers:</strong> {$membersList}</p>
                <p><strong>Boat:</strong> {$boatName}</p>
                <p><strong>Route:</strong> {$origin} → {$dest}</p>
                <p><strong>Date:</strong> {$tripDate}</p>
            </div>

            <div class="qr-section">
                <p><strong>Scan this QR code to generate boarding pass:</strong></p>
HTML;

        if ($qrImage) {
            $html .= "                <img src=\"{$qrImage}\" alt=\"Group QR Code\" />\n";
        }

        $html .= <<<HTML
            </div>

            <p><strong>How to use:</strong></p>
            <ol>
                <li>Proceed to our admin counter on the day of travel</li>
                <li>Have this email ready with the QR code visible</li>
                <li>Scan the QR code with our scanner</li>
                <li>Receive your boarding pass (physical or digital)</li>
            </ol>

            <p><strong>Questions?</strong> Contact us at our counter or call for assistance.</p>

            <p>We look forward to welcoming you aboard!</p>
            <p>Best regards,<br><strong>NAMA Marine Team</strong></p>
        </div>

        <div class="footer">
            <p>This is an automated message. Please do not reply to this email.</p>
            <p>Safe travels! 🚤</p>
        </div>
    </div>
</body>
</html>
HTML;

        return $html;
    }

    // ═══════════════════════════════════════════════════════════════════
    // POST /api/admin/manifest/boarding-pass-self-service
    // Self-service boarding pass generation: scan group QR → return BP PDF
    // 
    // Body JSON: { group_qr_code: "NAMA_GROUP_QR_..." }
    // or Query: ?code=NAMA_GROUP_QR_...
    // ═══════════════════════════════════════════════════════════════════
    public function boardingPassSelfService()
    {
        if (!$this->isAdminUser()) {
            return $this->jsonResponse(['error' => 'Forbidden.'], 403);
        }

        $db = \Config\Database::connect();

        // Get group QR code from body or query
        $body = $this->request->getJSON(true) ?? [];
        $groupQrCode = $body['group_qr_code'] ?? $this->request->getVar('code') ?? null;

        if (!$groupQrCode) {
            return $this->jsonResponse(['error' => 'group_qr_code is required.'], 422);
        }

        // Parse QR code: NAMA_GROUP_QR_{uploadId}_{base64_group_name}
        if (!preg_match('/^NAMA_GROUP_QR_(\d+)_(.+)$/i', $groupQrCode, $matches)) {
            return $this->jsonResponse(['error' => 'Invalid group QR code format.'], 422);
        }

        $uploadId = (int) $matches[1];
        $groupNameBase64 = $matches[2];

        try {
            $groupName = base64_decode($groupNameBase64, true);
            if ($groupName === false) {
                throw new \Exception('Base64 decode failed');
            }
        } catch (\Exception $e) {
            return $this->jsonResponse(['error' => 'Failed to decode group name from QR code.'], 422);
        }

        // Verify upload exists
        $upload = $db->table('manifest_uploads')
            ->where('id', $uploadId)
            ->get()
            ->getFirstRow('array');

        if (!$upload) {
            return $this->jsonResponse(['error' => 'Upload not found.'], 404);
        }

        // Get all tickets for this group in this upload
        $tickets = $db->table('manifest_tickets')
            ->where('upload_id', $uploadId)
            ->where('group_name', $groupName)
            ->where('cancelled', 0)
            ->orderBy('seq_no', 'ASC')
            ->get()
            ->getResultArray();

        if (empty($tickets)) {
            return $this->jsonResponse(['error' => 'No tickets found for this group.'], 404);
        }

        // Collect ticket IDs
        $ticketIds = array_column($tickets, 'id');

        // Generate boarding pass PDF for this group
        // Reuse the boardingPass endpoint by passing ticket_ids
        $this->request->setGlobal('GET', ['ticket_ids' => implode(',', $ticketIds)]);
        return $this->boardingPass($uploadId);
    }

    // ═══════════════════════════════════════════════════════════════════
    // HELPER: Generate unique QR codes per group
    // ═══════════════════════════════════════════════════════════════════
    private function generateGroupQrCodes(int $uploadId, array $ticketRows): array
    {
        $groupQrs = [];
        $qrDir    = WRITEPATH . 'uploads/qr_codes/';

        if (!is_dir($qrDir)) {
            mkdir($qrDir, 0775, true);
        }

        // Group tickets by group_name
        $groups = [];
        foreach ($ticketRows as $ticket) {
            $groupName = $ticket['group_name'] ?? '__solo__' . $ticket['seq_no'];
            if (!isset($groups[$groupName])) {
                $groups[$groupName] = [];
            }
            $groups[$groupName][] = $ticket;
        }

        // Generate QR per group
        foreach ($groups as $groupName => $members) {
            // QR content: unique identifier for self-service BP generation
            $qrContent = 'NAMA_GROUP_QR_' . $uploadId . '_' . base64_encode(strtoupper($groupName));

            $qrFilePath = $qrDir . uniqid('gqr_') . '.png';

            try {
                $writer = new \Endroid\QrCode\Writer\PngWriter();
                $qrCode = \Endroid\QrCode\QrCode::create($qrContent)
                    ->setEncoding(new \Endroid\QrCode\Encoding\Encoding('UTF-8'))
                    ->setSize(300)
                    ->setMargin(8)
                    ->setForegroundColor(new \Endroid\QrCode\Color\Color(0, 0, 0))
                    ->setBackgroundColor(new \Endroid\QrCode\Color\Color(255, 255, 255));

                $writer->write($qrCode)->saveToFile($qrFilePath);

                // Store as data URL (for frontend display)
                $qrData = base64_encode(file_get_contents($qrFilePath));
                $dataUrl = 'data:image/png;base64,' . $qrData;

                $groupQrs[$groupName] = [
                    'group_name' => $groupName,
                    'qr_content' => $qrContent,
                    'qr_data_url' => $dataUrl,
                    'member_count' => count($members),
                ];

                @unlink($qrFilePath);

            } catch (\Exception $e) {
                log_message('error', 'Failed to generate group QR for ' . $groupName . ': ' . $e->getMessage());
                // Continue on error — just skip this group's QR
            }
        }

        return $groupQrs;
    }
}
