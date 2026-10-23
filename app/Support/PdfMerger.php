<?php

namespace App\Support;

use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;
use Throwable;

/**
 * Menggabungkan beberapa sumber menjadi satu PDF: PDF (string hasil dompdf atau file), gambar JPG/PNG
 * (satu halaman A4, diperkecil supaya muat), atau halaman keterangan untuk file yang tidak bisa disisipkan.
 * Halaman PDF sumber disalin apa adanya (ukuran & orientasi asli).
 */
class PdfMerger
{
    private Fpdi $pdf;

    public function __construct()
    {
        $this->pdf = new Fpdi();
        $this->pdf->SetAutoPageBreak(false);
    }

    public function addPdfString(string $content): void
    {
        $this->importPages(StreamReader::createByString($content));
    }

    /** Lampiran: PDF, JPG/PNG; tipe lain atau file yang gagal dibaca diganti halaman keterangan. */
    public function addAttachment(string $path, string $fileName): void
    {
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        try {
            match ($ext) {
                'pdf' => $this->importPages(StreamReader::createByFile($path)),
                'jpg', 'jpeg', 'png' => $this->addImage($path, $ext),
                default => $this->addNotice($fileName, 'This file type cannot be included in the print. Please open it from the APR system.'),
            };
        } catch (Throwable $e) {
            report($e);
            $this->addNotice($fileName, 'This file could not be included in the print (it may be encrypted or damaged). Please open it from the APR system.');
        }
    }

    public function output(): string
    {
        return $this->pdf->Output('S');
    }

    private function importPages(StreamReader $reader): void
    {
        // PDF terenkripsi / rusak umumnya sudah gagal di sini, sebelum ada halaman yang ditambahkan.
        $count = $this->pdf->setSourceFile($reader);
        for ($i = 1; $i <= $count; $i++) {
            $tpl = $this->pdf->importPage($i);
            $size = $this->pdf->getTemplateSize($tpl);
            $this->pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $this->pdf->useTemplate($tpl);
        }
    }

    private function addImage(string $path, string $ext): void
    {
        $info = @getimagesize($path);
        if ($info === false) {
            throw new \RuntimeException("Not a valid image: {$path}");
        }
        [$w, $h] = $info;
        $landscape = $w > $h;
        $pageW = $landscape ? 297 : 210;
        $pageH = $landscape ? 210 : 297;
        $margin = 10;
        $scale = min(($pageW - 2 * $margin) / $w, ($pageH - 2 * $margin) / $h);
        $drawW = $w * $scale;
        $drawH = $h * $scale;

        $this->pdf->AddPage($landscape ? 'L' : 'P', 'A4');
        $this->pdf->Image($this->fpdfImage($path, $ext), ($pageW - $drawW) / 2, ($pageH - $drawH) / 2, $drawW, $drawH, $this->type($ext));
    }

    /** FPDF tidak bisa membaca PNG interlaced / beberapa varian; ubah dulu ke PNG standar lewat GD kalau perlu. */
    private function fpdfImage(string $path, string $ext): string
    {
        if ($this->type($ext) === 'JPG') {
            return $path;
        }
        $img = @imagecreatefrompng($path);
        if ($img === false) {
            return $path;
        }
        imageinterlace($img, false);
        imagesavealpha($img, true);
        $tmp = tempnam(sys_get_temp_dir(), 'apr') . '.png';
        imagepng($img, $tmp);
        register_shutdown_function(fn () => @unlink($tmp));

        return $tmp;
    }

    private function type(string $ext): string
    {
        return $ext === 'png' ? 'PNG' : 'JPG';
    }

    private function addNotice(string $fileName, string $message): void
    {
        $this->pdf->AddPage('P', 'A4');
        $this->pdf->SetFont('Helvetica', 'B', 12);
        $this->pdf->SetXY(20, 30);
        $this->pdf->Cell(0, 8, 'Attachment', 0, 1);
        $this->pdf->SetFont('Helvetica', '', 10);
        $this->pdf->SetX(20);
        $this->pdf->MultiCell(170, 6, $this->latin1($fileName));
        $this->pdf->Ln(2);
        $this->pdf->SetX(20);
        $this->pdf->SetTextColor(110, 110, 110);
        $this->pdf->MultiCell(170, 6, $message);
        $this->pdf->SetTextColor(0, 0, 0);
    }

    /** Font inti FPDF hanya Latin-1. */
    private function latin1(string $text): string
    {
        $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);

        return $converted === false ? $text : $converted;
    }
}
