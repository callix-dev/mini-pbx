<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SipSecurityLog extends Model
{
    protected $fillable = [
        'event_time',
        'event_type',
        'direction',
        'source_ip',
        'source_port',
        'destination_ip',
        'destination_port',
        'from_uri',
        'to_uri',
        'caller_id',
        'caller_name',
        'callee_id',
        'carrier_id',
        'endpoint',
        'status',
        'reject_reason',
        'sip_response_code',
        'call_id',
        'uniqueid',
        'metadata',
    ];

    protected $casts = [
        'event_time' => 'datetime',
        'source_port' => 'integer',
        'destination_port' => 'integer',
        'sip_response_code' => 'integer',
        'metadata' => 'array',
    ];

    // Status constants
    const STATUS_ALLOWED = 'ALLOWED';
    const STATUS_REJECTED = 'REJECTED';
    const STATUS_FAILED = 'FAILED';
    const STATUS_UNKNOWN = 'UNKNOWN';

    // Event type constants
    const EVENT_INVITE = 'INVITE';
    const EVENT_REGISTER = 'REGISTER';
    const EVENT_OPTIONS = 'OPTIONS';
    const EVENT_BYE = 'BYE';
    const EVENT_ACK = 'ACK';
    const EVENT_CANCEL = 'CANCEL';

    // Direction constants
    const DIRECTION_INBOUND = 'inbound';
    const DIRECTION_OUTBOUND = 'outbound';

    /**
     * Get the carrier associated with this log entry.
     */
    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    /**
     * Scope for rejected calls.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Scope for allowed calls.
     */
    public function scopeAllowed($query)
    {
        return $query->where('status', self::STATUS_ALLOWED);
    }

    /**
     * Scope for inbound calls.
     */
    public function scopeInbound($query)
    {
        return $query->where('direction', self::DIRECTION_INBOUND);
    }

    /**
     * Scope for outbound calls.
     */
    public function scopeOutbound($query)
    {
        return $query->where('direction', self::DIRECTION_OUTBOUND);
    }

    /**
     * Scope for filtering by source IP.
     */
    public function scopeFromIp($query, string $ip)
    {
        return $query->where('source_ip', $ip);
    }

    /**
     * Scope for filtering by event type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('event_type', $type);
    }

    /**
     * Scope for INVITE events (calls).
     */
    public function scopeCalls($query)
    {
        return $query->where('event_type', self::EVENT_INVITE);
    }

    /**
     * Scope for events within a time range.
     */
    public function scopeBetween($query, $start, $end)
    {
        return $query->whereBetween('event_time', [$start, $end]);
    }

    /**
     * Log an inbound SIP event.
     */
    public static function logInbound(array $data): self
    {
        return self::create(array_merge($data, [
            'direction' => self::DIRECTION_INBOUND,
            'event_time' => now(),
        ]));
    }

    /**
     * Log an outbound SIP event.
     */
    public static function logOutbound(array $data): self
    {
        return self::create(array_merge($data, [
            'direction' => self::DIRECTION_OUTBOUND,
            'event_time' => now(),
        ]));
    }

    /**
     * Log a rejected call attempt.
     */
    public static function logRejected(array $data, string $reason, int $sipCode = 404): self
    {
        return self::create(array_merge($data, [
            'status' => self::STATUS_REJECTED,
            'reject_reason' => $reason,
            'sip_response_code' => $sipCode,
            'event_time' => now(),
        ]));
    }

    /**
     * Get formatted log line for file logging.
     */
    public function toLogLine(): string
    {
        $timestamp = $this->event_time->format('Y-m-d H:i:s');
        $source = "{$this->source_ip}:{$this->source_port}";
        $destination = $this->callee_id ?? $this->to_uri ?? 'unknown';
        $reason = $this->reject_reason ?? ($this->status === self::STATUS_ALLOWED ? 'Accepted' : 'Unknown');
        
        return "[{$timestamp}] {$this->status} {$this->event_type} from {$source} to {$destination} - {$reason}";
    }
}

