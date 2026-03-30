<?php

namespace App\Support;

class WaMessageTrigger
{
    public const BILLING_REMINDER = 'billing_reminder';

    public const BILLING_TOTAL = 'billing_total';

    public const PAYMENT_RECEIPT = 'payment_receipt';

    public const WELCOME_REGISTRATION = 'welcome_registration';

    public const INVOICE_LINK = 'invoice_link';

    public const BROADCAST = 'broadcast';

    public static function options(): array
    {
        return [
            self::BILLING_REMINDER => 'Tagihan Belum Bayar',
            self::BILLING_TOTAL => 'Kirim Total Tagihan',
            self::PAYMENT_RECEIPT => 'Bukti Pembayaran',
            self::WELCOME_REGISTRATION => 'Pelanggan Baru',
            self::INVOICE_LINK => 'Kirim Invoice',
            self::BROADCAST => 'Broadcast',
        ];
    }

    public static function normalize(string $value): string
    {
        $v = strtolower(trim($value));

        return match ($v) {
            'tagihan', 'kirim_tagihan', self::BILLING_REMINDER => self::BILLING_REMINDER,
            'total_tagihan', 'kirim_total_tagihan', 'total tagihan', self::BILLING_TOTAL => self::BILLING_TOTAL,
            'bayar', 'bukti_pembayaran', self::PAYMENT_RECEIPT => self::PAYMENT_RECEIPT,
            'daftar', 'pendaftaran', self::WELCOME_REGISTRATION => self::WELCOME_REGISTRATION,
            'invoice', 'kirim_invoice', self::INVOICE_LINK => self::INVOICE_LINK,
            'broadcast', self::BROADCAST => self::BROADCAST,
            default => $v,
        };
    }

    public static function candidates(string $value): array
    {
        $normalized = self::normalize($value);

        return match ($normalized) {
            self::BILLING_REMINDER => [self::BILLING_REMINDER, 'tagihan'],
            self::BILLING_TOTAL => [self::BILLING_TOTAL, 'total_tagihan', 'total tagihan'],
            self::PAYMENT_RECEIPT => [self::PAYMENT_RECEIPT, 'bayar'],
            self::WELCOME_REGISTRATION => [self::WELCOME_REGISTRATION, 'daftar'],
            self::INVOICE_LINK => [self::INVOICE_LINK, 'invoice'],
            self::BROADCAST => [self::BROADCAST],
            default => [$normalized],
        };
    }
}
