<?php

namespace App\Services;

/**
 * Membuat "AI Summary" (RFP: 80–150 kata) secara deterministik. Tidak memanggil
 * API eksternal.
 *
 * UNIVERSAL (multi-niche): kalimat pembuka/jembatan/penutup/pengisi TIDAK lagi
 * hardcoded untuk satu jenis bisnis — diambil dari pool Variasi Konten
 * (section summary_open / summary_bridge / summary_close / summary_filler)
 * sehingga bisa disetel untuk jasa, herbal, properti, pendidikan, dan lainnya.
 * Bila pool kosong, dipakai kalimat netral yang cocok untuk bisnis apa pun.
 *
 * Token yang tersedia di kalimat pool: {{layanan}} {{kota}} {{kecamatan}}
 * {{kelurahan}} {{brand}} {{usp_text}} — plus semua kolom data lokal CSV.
 */
class SummaryGenerator
{
    public function __construct(private readonly ContentRepository $content)
    {
    }

    /**
     * @param array<string,string> $tokens layanan, kota, kecamatan, kelurahan, ...
     * @param array<int,string> $uspPoints daftar keunggulan (sudah resolve token)
     * @param array<string,mixed> $extra data lokal per halaman (kolom CSV opsional)
     */
    public function generate(array $tokens, array $uspPoints, VariationEngine $engine, array $extra = []): string
    {
        $usp = array_slice($uspPoints, 0, 2);
        $uspText = $this->joinNatural(array_map(fn ($u) => rtrim(mb_strtolower($u), '.'), $usp));

        $vars = array_merge($tokens, ['usp_text' => $uspText]);
        $blocks = $this->content->blocksBySection();

        $openers = $blocks['summary_open'] ?? $this->defaultOpeners();
        $bridges = $blocks['summary_bridge'] ?? $this->defaultBridges();
        $closers = $blocks['summary_close'] ?? $this->defaultClosers();
        $fillers = $blocks['summary_filler'] ?? $this->defaultFillers();

        $pick = fn (string $label, array $pool): string => TokenReplacer::apply(
            (string) ($engine->pick($label, $pool) ?? ''),
            $vars
        );

        // Fakta lokal ditempatkan TEPAT setelah pembuka agar ikut terbawa ke
        // meta description (155 karakter pertama) — di situlah keunikan per
        // halaman paling bernilai.
        $sentence = trim(implode(' ', array_filter([
            $pick('summary.open', $openers),
            $this->localFacts($extra, $tokens['kelurahan'] ?? ''),
            $pick('summary.bridge', $bridges),
            $pick('summary.close', $closers),
        ])));

        $fillersResolved = array_map(fn ($f) => TokenReplacer::apply($f, $vars), $fillers);

        return $this->clampWords((string) preg_replace('/\s+/', ' ', $sentence), 80, 150, $fillersResolved);
    }

    /**
     * Kalimat FAKTA LOKAL dari data CSV opsional — angka & fakta yang berbeda
     * per lokasi. Kunci generik berlaku lintas niche.
     *
     * @param array<string,mixed> $extra
     */
    private function localFacts(array $extra, string $kelurahan): string
    {
        $area = $kelurahan !== '' ? $kelurahan : 'ini';
        $facts = [];

        if (! empty($extra['harga'])) {
            $facts[] = 'Harga mulai dari ' . $extra['harga'] . '.';
        }
        if (! empty($extra['stok'])) {
            $facts[] = 'Stok tersedia ' . $extra['stok'] . '.';
        }
        if (! empty($extra['jumlah_tutor'])) {
            $facts[] = 'Saat ini ' . $extra['jumlah_tutor'] . ' tenaga pengajar aktif di area ' . $area . '.';
        }
        if (! empty($extra['landmark'])) {
            $facts[] = 'Lokasi mudah dijangkau dari kawasan ' . $extra['landmark'] . '.';
        }
        if (! empty($extra['sekolah'])) {
            $facts[] = 'Kami juga menjangkau sekitar ' . $extra['sekolah'] . '.';
        }
        if (! empty($extra['garansi'])) {
            $facts[] = 'Bergaransi ' . $extra['garansi'] . '.';
        }
        if (! empty($extra['legalitas'])) {
            $facts[] = 'Legalitas ' . $extra['legalitas'] . '.';
        }

        return implode(' ', $facts);
    }

    // ----- Fallback NETRAL (dipakai hanya bila pool DB kosong) -----

    /** @return array<int,string> */
    private function defaultOpeners(): array
    {
        return [
            '{{brand}} menyediakan {{layanan}} untuk wilayah {{kelurahan}}, {{kecamatan}}, {{kota}}.',
            'Mencari {{layanan}} di {{kelurahan}}? {{brand}} siap melayani seluruh area {{kecamatan}}, {{kota}}.',
            '{{brand}} hadir melayani kebutuhan {{layanan}} warga {{kelurahan}}, {{kecamatan}}, {{kota}}.',
            '{{layanan}} kini tersedia di {{kelurahan}} dan sekitarnya — {{brand}} menjangkau seluruh {{kecamatan}}, {{kota}}.',
        ];
    }

    /** @return array<int,string> */
    private function defaultBridges(): array
    {
        return [
            'Kami dikenal dengan {{usp_text}}.',
            'Layanan kami didukung {{usp_text}}.',
            'Setiap pelanggan kami layani dengan {{usp_text}}.',
            'Keunggulan kami mencakup {{usp_text}}.',
        ];
    }

    /** @return array<int,string> */
    private function defaultClosers(): array
    {
        return [
            'Hubungi kami melalui WhatsApp untuk konsultasi, informasi harga, dan ketersediaan di {{kelurahan}}.',
            'Tim kami siap menjawab pertanyaan Anda seputar {{layanan}} di {{kelurahan}} kapan saja lewat WhatsApp.',
            'Silakan chat WhatsApp untuk mendapatkan penawaran terbaik bagi warga {{kelurahan}} dan sekitarnya.',
            'Konsultasi awal gratis — sampaikan kebutuhan Anda dan kami bantu carikan solusi terbaik di {{kelurahan}}.',
        ];
    }

    /** @return array<int,string> */
    private function defaultFillers(): array
    {
        return [
            'Kami mengutamakan kepuasan dan kepercayaan setiap pelanggan.',
            'Anda dapat berkonsultasi terlebih dahulu secara gratis sebelum memutuskan.',
            'Respons cepat di jam operasional, langsung ditangani tim kami.',
            'Layanan kami menjangkau seluruh wilayah sekitar dengan proses yang mudah.',
        ];
    }

    /**
     * Pastikan panjang ringkasan berada di rentang kata yang diminta (RFP: 80–150).
     * Bila kurang, tambah kalimat pengisi satu per satu hingga memenuhi minimum;
     * bila lebih, potong ke maksimum.
     *
     * @param array<int,string> $fillers
     */
    private function clampWords(string $text, int $min, int $max, array $fillers): string
    {
        $i = 0;
        while (str_word_count(strip_tags($text)) < $min && $i < count($fillers)) {
            $text = rtrim($text) . ' ' . $fillers[$i];
            $i++;
        }

        $words = preg_split('/\s+/', trim($text)) ?: [];
        if (count($words) > $max) {
            return rtrim(implode(' ', array_slice($words, 0, $max)), ',.') . '.';
        }

        return $text;
    }

    /**
     * @param array<int,string> $items
     */
    private function joinNatural(array $items): string
    {
        $items = array_values(array_filter($items));
        if (count($items) <= 1) {
            return $items[0] ?? '';
        }
        $last = array_pop($items);

        return implode(', ', $items) . ' dan ' . $last;
    }
}
