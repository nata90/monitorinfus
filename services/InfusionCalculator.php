<?php

namespace app\services;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

/**
 * Perhitungan durasi dan sisa waktu infus.
 *
 *   durasi (menit)       = faktor tetes (tetes/mL) x volume (mL) / TPM (tetes/menit)
 *   perkiraan habis      = waktu mulai + durasi
 *   sisa (menit)         = ceil((perkiraan habis - sekarang) / 60 detik), minimal 0
 */
class InfusionCalculator
{
    /**
     * @param float|int|string $dropFactor tetes per mL
     * @param float|int|string $volumeMl   volume dalam mL
     * @param float|int|string $tpm        tetes per menit
     * @return float durasi dalam menit
     */
    public function durationMinutes($dropFactor, $volumeMl, $tpm): float
    {
        $dropFactor = (float) $dropFactor;
        $volumeMl = (float) $volumeMl;
        $tpm = (float) $tpm;

        if ($dropFactor <= 0 || $volumeMl <= 0 || $tpm <= 0) {
            throw new InvalidArgumentException('Faktor tetes, volume, dan TPM harus lebih besar dari nol.');
        }

        return ($dropFactor * $volumeMl) / $tpm;
    }

    public function estimatedEnd(DateTimeInterface $startedAt, float $durationMinutes): DateTimeImmutable
    {
        $start = $startedAt instanceof DateTimeImmutable
            ? $startedAt
            : DateTimeImmutable::createFromMutable($startedAt);

        // Presisi hingga detik.
        $seconds = (int) round($durationMinutes * 60);

        return $start->modify(sprintf('%+d seconds', $seconds));
    }

    /**
     * Sisa menit dibulatkan ke atas, tidak pernah negatif.
     */
    public function remainingMinutes(DateTimeInterface $estimatedEndAt, DateTimeInterface $now): int
    {
        $diff = $estimatedEndAt->getTimestamp() - $now->getTimestamp();
        if ($diff <= 0) {
            return 0;
        }

        return (int) ceil($diff / 60);
    }

    /**
     * Sisa detik, tidak pernah negatif.
     */
    public function remainingSeconds(DateTimeInterface $estimatedEndAt, DateTimeInterface $now): int
    {
        return max(0, $estimatedEndAt->getTimestamp() - $now->getTimestamp());
    }
}
