<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'payment_method',
        'transaction_id',
        'amount',
        'status',
        'metadata',
        'receipt_path',
        'receipt_uploaded_at',
        'paid_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'paid_at' => 'datetime',
        'receipt_uploaded_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function methodLabel(): string
    {
        return config('payments.methods.'.$this->payment_method, $this->payment_method);
    }

    public function allowsReceipt(): bool
    {
        return ! in_array($this->payment_method, config('payments.no_receipt', []), true);
    }

    /** هل ما زال يحتاج إيصال (ما رفع بعد وطريقة الدفع تسمح) */
    public function needsReceipt(): bool
    {
        return $this->allowsReceipt()
            && $this->status !== 'paid'
            && empty($this->receipt_path);
    }

    public function transferCode(): ?string
    {
        return $this->transaction_id
            ?: ($this->metadata['transfer_code'] ?? null);
    }

    public function needsTransferCode(): bool
    {
        $rules = config('payments.rules.'.$this->payment_method, []);
        $required = $rules['require'] ?? [];

        return in_array('transfer_code', $required, true)
            && $this->status !== 'paid'
            && ! $this->transferCode();
    }

    public static function validateProof(string $method, ?string $transferCode, bool $hasReceipt): ?string
    {
        $rules = config('payments.rules.'.$method);
        if (! $rules) {
            return 'طريقة دفع غير مدعومة.';
        }

        $code = trim((string) $transferCode);
        $hasCode = $code !== '';

        if (! empty($rules['require'])) {
            foreach ($rules['require'] as $field) {
                if ($field === 'transfer_code' && ! $hasCode) {
                    return 'كود تأكيد الدفع إلزامي لشام كاش.';
                }
                if ($field === 'receipt' && ! $hasReceipt) {
                    return 'صورة الوصل إلزامية.';
                }
            }
        }

        if (! empty($rules['require_one_of'])) {
            $ok = false;
            foreach ($rules['require_one_of'] as $field) {
                if ($field === 'transfer_code' && $hasCode) {
                    $ok = true;
                }
                if ($field === 'receipt' && $hasReceipt) {
                    $ok = true;
                }
            }
            if (! $ok) {
                return 'لازم ترفعي صورة الوصل أو تدخلي كود التحويل (واحد منهم إلزامي).';
            }
        }

        return null;
    }
}