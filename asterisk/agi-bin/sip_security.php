#!/usr/bin/php
<?php
/**
 * SIP Security AGI Script
 * 
 * This script validates DIDs and logs all SIP events for security monitoring.
 * 
 * Usage in dialplan:
 *   AGI(sip_security.php,<direction>,<did>,<caller_id>)
 * 
 * Example:
 *   AGI(sip_security.php,inbound,VHRBxQLotzSPTemepJak,17657370086)
 * 
 * Sets channel variables:
 *   DID_VALID = 1 if DID exists, 0 if not
 *   DID_DESTINATION_TYPE = destination type from DIDs table
 *   DID_DESTINATION_ID = destination ID from DIDs table
 */

// Database configuration
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '5432');
define('DB_NAME', 'forge');
define('DB_USER', 'forge');
define('DB_PASS', 'gz6VOESs5VvNSvn7Ud9R');

// Log file path
define('SECURITY_LOG_FILE', '/var/log/asterisk/sip_security.log');

// AGI class for communication with Asterisk
class AGI {
    private $stdin;
    private $stdout;
    private $env = [];

    public function __construct() {
        $this->stdin = fopen('php://stdin', 'r');
        $this->stdout = fopen('php://stdout', 'w');
        $this->readEnvironment();
    }

    private function readEnvironment() {
        while (($line = fgets($this->stdin)) !== false) {
            $line = trim($line);
            if (empty($line)) break;
            
            if (preg_match('/^agi_(\w+):\s*(.*)$/', $line, $matches)) {
                $this->env[$matches[1]] = $matches[2];
            }
        }
    }

    public function getEnv(string $key, $default = null) {
        return $this->env[$key] ?? $default;
    }

    public function getAllEnv(): array {
        return $this->env;
    }

    public function execute(string $command): array {
        fwrite($this->stdout, "{$command}\n");
        fflush($this->stdout);
        
        $response = fgets($this->stdin);
        $result = ['code' => 0, 'result' => '', 'data' => ''];
        
        if (preg_match('/^(\d+)\s+result=(-?\d+)(?:\s+\((.+)\))?/', $response, $matches)) {
            $result['code'] = (int)$matches[1];
            $result['result'] = (int)$matches[2];
            $result['data'] = $matches[3] ?? '';
        }
        
        return $result;
    }

    public function setVariable(string $name, string $value): void {
        $this->execute("SET VARIABLE {$name} \"{$value}\"");
    }

    public function getVariable(string $name): string {
        $result = $this->execute("GET VARIABLE {$name}");
        return $result['data'];
    }

    public function verbose(string $message, int $level = 1): void {
        $this->execute("VERBOSE \"{$message}\" {$level}");
    }
}

// Database connection
function getDbConnection(): ?PDO {
    try {
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            DB_HOST, DB_PORT, DB_NAME
        );
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        
        return $pdo;
    } catch (PDOException $e) {
        error_log("SIP Security AGI: Database connection failed: " . $e->getMessage());
        return null;
    }
}

// Check if DID exists in database
function validateDid(PDO $db, string $did): ?array {
    try {
        // Check DIDs table - match by number field
        $stmt = $db->prepare("
            SELECT id, number, name, carrier_id, destination_type, destination_id, is_active
            FROM dids 
            WHERE (number = :did OR number = :did_clean) AND is_active = true AND deleted_at IS NULL
            LIMIT 1
        ");
        
        // Clean the DID (remove + prefix if present)
        $didClean = ltrim($did, '+');
        
        $stmt->execute(['did' => $did, 'did_clean' => $didClean]);
        $result = $stmt->fetch();
        
        return $result ?: null;
    } catch (PDOException $e) {
        error_log("SIP Security AGI: DID validation error: " . $e->getMessage());
        return null;
    }
}

// Get carrier ID by endpoint name
function getCarrierIdByEndpoint(PDO $db, string $endpoint): ?int {
    try {
        $stmt = $db->prepare("
            SELECT id FROM carriers 
            WHERE LOWER(REPLACE(name, ' ', '_')) = LOWER(:endpoint)
            OR id::text = :endpoint
            LIMIT 1
        ");
        $stmt->execute(['endpoint' => $endpoint]);
        $result = $stmt->fetch();
        
        return $result ? (int)$result['id'] : null;
    } catch (PDOException $e) {
        return null;
    }
}

// Log to database
function logToDatabase(PDO $db, array $data): bool {
    try {
        $stmt = $db->prepare("
            INSERT INTO sip_security_logs (
                event_time, event_type, direction, source_ip, source_port,
                destination_ip, destination_port, from_uri, to_uri,
                caller_id, caller_name, callee_id, carrier_id, endpoint,
                status, reject_reason, sip_response_code, call_id, uniqueid,
                metadata, created_at, updated_at
            ) VALUES (
                NOW(), :event_type, :direction, :source_ip, :source_port,
                :destination_ip, :destination_port, :from_uri, :to_uri,
                :caller_id, :caller_name, :callee_id, :carrier_id, :endpoint,
                :status, :reject_reason, :sip_response_code, :call_id, :uniqueid,
                :metadata, NOW(), NOW()
            )
        ");
        
        return $stmt->execute([
            'event_type' => $data['event_type'] ?? 'INVITE',
            'direction' => $data['direction'] ?? 'inbound',
            'source_ip' => $data['source_ip'] ?? null,
            'source_port' => $data['source_port'] ?? null,
            'destination_ip' => $data['destination_ip'] ?? null,
            'destination_port' => $data['destination_port'] ?? null,
            'from_uri' => $data['from_uri'] ?? null,
            'to_uri' => $data['to_uri'] ?? null,
            'caller_id' => $data['caller_id'] ?? null,
            'caller_name' => $data['caller_name'] ?? null,
            'callee_id' => $data['callee_id'] ?? null,
            'carrier_id' => $data['carrier_id'] ?? null,
            'endpoint' => $data['endpoint'] ?? null,
            'status' => $data['status'] ?? 'UNKNOWN',
            'reject_reason' => $data['reject_reason'] ?? null,
            'sip_response_code' => $data['sip_response_code'] ?? null,
            'call_id' => $data['call_id'] ?? null,
            'uniqueid' => $data['uniqueid'] ?? null,
            'metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null,
        ]);
    } catch (PDOException $e) {
        error_log("SIP Security AGI: Database log error: " . $e->getMessage());
        return false;
    }
}

// Log to file
function logToFile(array $data): void {
    $timestamp = date('Y-m-d H:i:s');
    $source = ($data['source_ip'] ?? 'unknown') . ':' . ($data['source_port'] ?? '?');
    $destination = $data['callee_id'] ?? $data['to_uri'] ?? 'unknown';
    $status = $data['status'] ?? 'UNKNOWN';
    $reason = $data['reject_reason'] ?? ($status === 'ALLOWED' ? 'Accepted' : 'Unknown');
    $eventType = $data['event_type'] ?? 'INVITE';
    $direction = strtoupper($data['direction'] ?? 'INBOUND');
    $endpoint = $data['endpoint'] ?? 'unknown';
    
    $logLine = sprintf(
        "[%s] %s %s %s from %s to %s via %s - %s\n",
        $timestamp, $direction, $status, $eventType, $source, $destination, $endpoint, $reason
    );
    
    file_put_contents(SECURITY_LOG_FILE, $logLine, FILE_APPEND | LOCK_EX);
}

// Main execution
function main(): void {
    $agi = new AGI();
    
    // Get arguments from dialplan
    $args = $agi->getEnv('arg_1', '');
    $direction = $args ?: 'inbound';
    $did = $agi->getEnv('arg_2', '');
    $callerId = $agi->getEnv('arg_3', '');
    
    // Get channel variables
    $uniqueid = $agi->getEnv('uniqueid', '');
    $channel = $agi->getEnv('channel', '');
    $callerIdNum = $agi->getEnv('callerid', $callerId);
    $callerIdName = $agi->getEnv('calleridname', '');
    $extension = $agi->getEnv('extension', $did);
    
    // Try to get source IP from channel
    $sourceIp = $agi->getVariable('CHANNEL(pjsip,remote_addr)');
    $srcIp = '';
    $srcPort = '';
    if ($sourceIp && strpos($sourceIp, ':') !== false) {
        $parts = explode(':', $sourceIp);
        $srcIp = $parts[0];
        $srcPort = $parts[1] ?? '5060';
    }
    
    // Get endpoint name
    $endpoint = $agi->getVariable('CHANNEL(endpoint)');
    
    // Get SIP headers for metadata
    $sipCallId = $agi->getVariable('PJSIP_HEADER(read,Call-ID)');
    $sipFrom = $agi->getVariable('PJSIP_HEADER(read,From)');
    $sipTo = $agi->getVariable('PJSIP_HEADER(read,To)');
    
    // Connect to database
    $db = getDbConnection();
    
    // Prepare log data
    $logData = [
        'event_type' => 'INVITE',
        'direction' => $direction,
        'source_ip' => $srcIp,
        'source_port' => $srcPort ? (int)$srcPort : null,
        'destination_ip' => gethostbyname(gethostname()),
        'destination_port' => 5060,
        'from_uri' => $sipFrom,
        'to_uri' => $sipTo,
        'caller_id' => $callerIdNum,
        'caller_name' => $callerIdName,
        'callee_id' => $extension,
        'endpoint' => $endpoint,
        'call_id' => $sipCallId,
        'uniqueid' => $uniqueid,
        'metadata' => [
            'channel' => $channel,
            'agi_env' => $agi->getAllEnv(),
        ],
    ];
    
    // Default to invalid
    $didValid = false;
    $destinationType = '';
    $destinationId = '';
    $status = 'REJECTED';
    $rejectReason = 'DID not registered in system';
    $sipResponseCode = 404;
    
    if ($db) {
        // Get carrier ID if possible
        if ($endpoint) {
            $carrierId = getCarrierIdByEndpoint($db, $endpoint);
            $logData['carrier_id'] = $carrierId;
        }
        
        // Validate DID
        $didInfo = validateDid($db, $extension);
        
        if ($didInfo) {
            $didValid = true;
            $destinationType = $didInfo['destination_type'] ?? '';
            $destinationId = $didInfo['destination_id'] ?? '';
            $status = 'ALLOWED';
            $rejectReason = null;
            $sipResponseCode = null;
            
            $agi->verbose("SIP Security: DID {$extension} validated - routing to {$destinationType}:{$destinationId}", 2);
        } else {
            $agi->verbose("SIP Security: DID {$extension} NOT FOUND - rejecting call from {$srcIp}:{$srcPort}", 1);
        }
        
        // Update log data with status
        $logData['status'] = $status;
        $logData['reject_reason'] = $rejectReason;
        $logData['sip_response_code'] = $sipResponseCode;
        
        // Log to database
        logToDatabase($db, $logData);
    } else {
        $agi->verbose("SIP Security: Database unavailable - allowing call to proceed", 1);
        // If database is unavailable, allow call but log as unknown
        $didValid = true;
        $status = 'UNKNOWN';
        $logData['status'] = $status;
        $logData['reject_reason'] = 'Database unavailable';
    }
    
    // Always log to file
    logToFile($logData);
    
    // Set channel variables for dialplan
    $agi->setVariable('DID_VALID', $didValid ? '1' : '0');
    $agi->setVariable('DID_DESTINATION_TYPE', $destinationType);
    $agi->setVariable('DID_DESTINATION_ID', $destinationId);
    $agi->setVariable('SIP_LOG_STATUS', $status);
}

// Run
main();

