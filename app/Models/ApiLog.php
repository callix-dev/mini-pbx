<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'api_key_id',
        'method',
        'endpoint',
        'ip_address',
        'response_code',
        'response_time',
        'request_data',
        'created_at',
    ];

    protected $casts = [
        'request_data' => 'array',
        'created_at' => 'datetime',
    ];

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class);
    }

    public static function log(ApiKey $apiKey, string $method, string $endpoint, string $ip, int $responseCode, int $responseTime, ?array $requestData = null): self
    {
        return self::create([
            'api_key_id' => $apiKey->id,
            'method' => $method,
            'endpoint' => $endpoint,
            'ip_address' => $ip,
            'response_code' => $responseCode,
            'response_time' => $responseTime,
            'request_data' => $requestData,
            'created_at' => now(),
        ]);
    }
}







