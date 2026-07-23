<?php

namespace App\Services\Ai;

/**
 * Kesalahan spesifik proses AI (kunci salah, kuota habis, jaringan, respons
 * tidak valid). Dipisah agar Job bisa menandai batch gagal dengan pesan yang
 * ramah operator awam.
 */
class AiException extends \RuntimeException
{
}
