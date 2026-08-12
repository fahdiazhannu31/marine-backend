<?php

namespace App\Libraries;

use FPDF;

/**
 * Extends FPDF to add:
 *  - Rotate()/RotatedText()  -> untuk teks vertikal (nama kapal)
 *  - Polygon()/FilledCircle() -> primitif dasar untuk bentuk custom
 *  - YachtIcon()             -> ikon perahu layar sederhana (silhouette)
 *
 * Simpan file ini di: app/Libraries/BoardingPassPDF.php
 * Lalu di Admin.php tambahkan: use App\Libraries\BoardingPassPDF;
 */
class BoardingPassPDF extends FPDF
{
    protected $angle = 0;

    public function Rotate($angle, $x = -1, $y = -1)
    {
        if ($x == -1) $x = $this->x;
        if ($y == -1) $y = $this->y;
        if ($this->angle != 0) $this->_out('Q');
        $this->angle = $angle;
        if ($angle != 0) {
            $angle *= M_PI / 180;
            $c = cos($angle);
            $s = sin($angle);
            $cx = $x * $this->k;
            $cy = ($this->h - $y) * $this->k;
            $this->_out(sprintf(
                'q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',
                $c, $s, -$s, $c, $cx, $cy, -$cx, -$cy
            ));
        }
    }

    protected function _endpage()
    {
        if ($this->angle != 0) {
            $this->angle = 0;
            $this->_out('Q');
        }
        parent::_endpage();
    }

    public function RotatedText($x, $y, $txt, $angle)
    {
        $this->Rotate($angle, $x, $y);
        $this->Text($x, $y, $txt);
        $this->Rotate(0);
    }

    public function Polygon($points, $style = 'D')
    {
        $op = ($style == 'F') ? 'f' : (($style == 'FD' || $style == 'DF') ? 'B' : 'S');
        $h = $this->h;
        $k = $this->k;
        $out = '';
        for ($i = 0; $i < count($points); $i += 2) {
            $out .= sprintf(
                '%.2F %.2F %s ',
                $points[$i] * $k,
                ($h - $points[$i + 1]) * $k,
                ($i == 0) ? 'm' : 'l'
            );
        }
        $this->_out($out . $op);
    }

    public function FilledCircle($cx, $cy, $r, $style = 'F', $segments = 24)
    {
        $points = [];
        for ($i = 0; $i < $segments; $i++) {
            $theta = 2 * M_PI * $i / $segments;
            $points[] = $cx + $r * cos($theta);
            $points[] = $cy + $r * sin($theta);
        }
        $this->Polygon($points, $style);
    }

    /**
     * Garis putus berbentuk titik-titik (dotted), vertikal atau horizontal.
     * Dipakai sebagai garis "sobekan" antara boarding pass utama & stub.
     */
    public function DottedLine($x1, $y1, $x2, $y2, $dotR = 0.35, $gap = 1.6)
    {
        $dist = sqrt(($x2 - $x1) ** 2 + ($y2 - $y1) ** 2);
        if ($dist == 0) return;
        $steps = max(1, (int) round($dist / $gap));
        for ($i = 0; $i <= $steps; $i++) {
            $t = $i / $steps;
            $x = $x1 + ($x2 - $x1) * $t;
            $y = $y1 + ($y2 - $y1) * $t;
            $this->FilledCircle($x, $y, $dotR, 'F');
        }
    }

    /**
     * Ikon yacht: pakai file PNG (putih, transparan) kalau tersedia di
     * $imagePath, kalau tidak fallback ke ikon vector bawaan (YachtIcon).
     */
    public function YachtIconAuto($x, $y, $w, $h, $imagePath = 'assets/img/yacht.png', $r = 255, $g = 255, $b = 255)
    {
        if (!empty($imagePath) && is_file($imagePath)) {
            $this->Image($imagePath, $x, $y, $w, $h, 'PNG');
        } else {
            $this->YachtIcon($x, $y, $w, $h, $r, $g, $b);
        }
    }

    /**
     * Ikon perahu layar sederhana (mirip contoh gambar), digambar dari
     * primitif garis & poligon FPDF -- tidak butuh file image tambahan.
     *
     * @param float $x Top-left X
     * @param float $y Top-left Y
     * @param float $w Lebar bounding box (mm)
     * @param float $h Tinggi bounding box (mm)
     */
    public function YachtIcon($x, $y, $w, $h, $r = 255, $g = 255, $b = 255)
    {
        $this->SetDrawColor($r, $g, $b);
        $this->SetFillColor($r, $g, $b);

        $hullTop    = $y + $h * 0.60;
        $hullBottom = $y + $h * 0.84;

        // Badan kapal (hull) - trapesium
        $this->Polygon([
            $x + $w * 0.06, $hullTop,
            $x + $w * 0.94, $hullTop,
            $x + $w * 0.80, $hullBottom,
            $x + $w * 0.20, $hullBottom,
        ], 'F');

        // Tiang (mast)
        $this->SetLineWidth(0.35);
        $this->Line($x + $w * 0.52, $y + $h * 0.04, $x + $w * 0.52, $hullTop);

        // Layar (sail) - segitiga
        $this->Polygon([
            $x + $w * 0.52, $y + $h * 0.06,
            $x + $w * 0.52, $hullTop,
            $x + $w * 0.88, $hullTop,
        ], 'F');

        // Layar kecil depan
        $this->Polygon([
            $x + $w * 0.52, $y + $h * 0.22,
            $x + $w * 0.52, $hullTop,
            $x + $w * 0.22, $hullTop,
        ], 'F');

        // Ombak kecil di bawah hull
        $this->SetLineWidth(0.3);
        $waveY = $hullBottom + $h * 0.06;
        $this->Line($x, $waveY, $x + $w * 0.32, $waveY - $h * 0.05);
        $this->Line($x + $w * 0.32, $waveY - $h * 0.05, $x + $w * 0.64, $waveY);
        $this->Line($x + $w * 0.64, $waveY, $x + $w, $waveY - $h * 0.05);
    }
}