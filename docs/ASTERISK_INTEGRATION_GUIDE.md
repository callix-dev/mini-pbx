# Mini-PBX Asterisk PJSIP Realtime Integration Guide

## Overview

This document describes the PJSIP Realtime integration between the Mini-PBX Laravel application and Asterisk. The Laravel application writes extension configuration directly to PostgreSQL tables that Asterisk reads via the Realtime engine. This eliminates the need for config file management and provides immediate synchronization.

---

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                     Laravel Application                         │
│  ┌──────────────┐    ┌───────────────────┐    ┌──────────────┐ │
│  │  Extension   │───▶│ ExtensionObserver │───▶│ PjsipRealtime│ │
│  │    Model     │    │   (auto-trigger)  │    │   Service    │ │
│  └──────────────┘    └───────────────────┘    └──────────────┘ │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼ Writes to PostgreSQL
┌─────────────────────────────────────────────────────────────────┐
│                       PostgreSQL                                 │
│   ps_endpoints  │  ps_auths  │  ps_aors  │  ps_contacts         │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼ Asterisk reads via ODBC
┌─────────────────────────────────────────────────────────────────┐
│                        Asterisk                                  │
│   res_odbc  ───▶  sorcery  ───▶  res_pjsip                      │
└─────────────────────────────────────────────────────────────────┘
```

---

## Database Schema

The following tables are created in PostgreSQL and populated by Laravel:

| Table | Purpose | Written By |
|-------|---------|------------|
| `ps_endpoints` | PJSIP endpoint configuration (context, codecs, caller ID, etc.) | Laravel |
| `ps_auths` | Authentication credentials (username/password) | Laravel |
| `ps_aors` | Address of Record settings (max contacts, expiration, qualify) | Laravel |
| `ps_contacts` | Registration storage | **Asterisk** |
| `ps_transports` | SIP transports (optional - can be in config file) | Laravel |
| `ps_registrations` | Outbound trunk registrations | Laravel |
| `ps_domain_aliases` | Domain alias mappings | Laravel |
| `ps_endpoint_id_ips` | IP-based endpoint identification | Laravel |

---

## Asterisk Configuration

### Prerequisites

Install PostgreSQL ODBC driver:

```bash
sudo apt-get install unixodbc odbc-postgresql
```

### Step 1: Configure ODBC Connection

**File: `/etc/odbc.ini`**

```ini
[asterisk]
Description = PostgreSQL connection for Asterisk
Driver = PostgreSQL
Database = mini_pbx
Servername = 127.0.0.1
Port = 5432
UserName = postgres
Password = minipbx2025
Protocol = 9.6
ReadOnly = No
ShowSystemTables = No
```

**File: `/etc/odbcinst.ini`** (if not already configured)

```ini
[PostgreSQL]
Description = ODBC for PostgreSQL
Driver = /usr/lib/x86_64-linux-gnu/odbc/psqlodbcw.so
Setup = /usr/lib/x86_64-linux-gnu/odbc/libodbcpsqlS.so
FileUsage = 1
```

### Step 2: Configure Asterisk ODBC Resource

**File: `/etc/asterisk/res_odbc.conf`**

```ini
[asterisk]
enabled => yes
dsn => asterisk
username => postgres
password => minipbx2025
pre-connect => yes
max_connections => 5
pooling => yes
isolation => read_committed
idlecheck => 60
connect_timeout => 10
```

### Step 3: Configure Realtime Mapping

**File: `/etc/asterisk/extconfig.conf`**

```ini
[settings]
; PJSIP Realtime mappings
ps_endpoints => odbc,asterisk,ps_endpoints
ps_auths => odbc,asterisk,ps_auths
ps_aors => odbc,asterisk,ps_aors
ps_contacts => odbc,asterisk,ps_contacts
ps_domain_aliases => odbc,asterisk,ps_domain_aliases
ps_endpoint_id_ips => odbc,asterisk,ps_endpoint_id_ips
ps_transports => odbc,asterisk,ps_transports
ps_registrations => odbc,asterisk,ps_registrations
```

### Step 4: Configure Sorcery for PJSIP

**File: `/etc/asterisk/sorcery.conf`**

```ini
[res_pjsip]
; Priority order: realtime first, then config file
endpoint=realtime,ps_endpoints
auth=realtime,ps_auths
aor=realtime,ps_aors
contact=realtime,ps_contacts
domain_alias=realtime,ps_domain_aliases
identify=realtime,ps_endpoint_id_ips

; Keep transport in config file (usually static)
transport=config,pjsip.conf,criteria=type=transport

; Outbound registrations can be realtime too
registration=realtime,ps_registrations

[res_pjsip_endpoint_identifier_ip]
identify=realtime,ps_endpoint_id_ips
```

### Step 5: Configure PJSIP Transports

**File: `/etc/asterisk/pjsip.conf`**

> **Note:** Only transports are defined here. Endpoints, auths, and aors come from the database.

```ini
[global]
type=global
max_forwards=70
user_agent=MiniPBX-Asterisk
default_outbound_endpoint=default

[system]
type=system
timer_t1=500
timer_b=32000

; UDP Transport (Standard SIP)
[transport-udp]
type=transport
protocol=udp
bind=0.0.0.0:5060
; Uncomment and set your external IP if behind NAT
; external_media_address=YOUR.PUBLIC.IP.HERE
; external_signaling_address=YOUR.PUBLIC.IP.HERE
local_net=192.168.0.0/16
local_net=10.0.0.0/8
local_net=172.16.0.0/12

; TCP Transport
[transport-tcp]
type=transport
protocol=tcp
bind=0.0.0.0:5060

; WebSocket Transport (WebRTC)
[transport-wss]
type=transport
protocol=wss
bind=0.0.0.0:8089
; cert_file=/etc/asterisk/keys/asterisk.pem
; priv_key_file=/etc/asterisk/keys/asterisk.key

; Anonymous endpoint for unmatched inbound
[anonymous]
type=endpoint
context=from-external
disallow=all
allow=ulaw,alaw
transport=transport-udp
```

### Step 6: Configure AMI

**File: `/etc/asterisk/manager.conf`**

```ini
[general]
enabled = yes
port = 5038
bindaddr = 127.0.0.1

[mini-pbx]
secret = YOUR_SECURE_AMI_PASSWORD
deny = 0.0.0.0/0.0.0.0
permit = 127.0.0.1/255.255.255.255
read = system,call,log,verbose,agent,user,config,dtmf,reporting,cdr,dialplan
write = system,call,agent,user,config,command,reporting,originate
eventfilter = !Event: RTCPSent
eventfilter = !Event: RTCPReceived
eventfilter = !Event: VarSet
eventfilter = Event: *
```

### Step 7: Restart Asterisk

```bash
sudo systemctl restart asterisk
```

---

## Sample Data in Database

When an extension is created in Laravel, these rows are inserted:

### ps_endpoints

| Column | Example Value |
|--------|---------------|
| id | 1001 |
| transport | transport-udp |
| aors | 1001 |
| auth | 1001 |
| context | from-internal |
| disallow | all |
| allow | ulaw,alaw,g722,opus |
| direct_media | no |
| force_rport | yes |
| rewrite_contact | yes |
| callerid | "John Doe" <1001> |
| mailboxes | 1001@default |
| dtmf_mode | rfc4733 |

### ps_auths

| Column | Example Value |
|--------|---------------|
| id | 1001 |
| auth_type | userpass |
| username | 1001 |
| password | [plaintext password] |

### ps_aors

| Column | Example Value |
|--------|---------------|
| id | 1001 |
| max_contacts | 1 |
| remove_existing | yes |
| qualify_frequency | 60 |
| default_expiration | 3600 |

---

## Verification Commands

### Test ODBC Connection

```bash
isql asterisk -v
```

### Verify Realtime is Working

```bash
asterisk -rx "realtime show pjsip"
```

### List Endpoints from Database

```bash
asterisk -rx "pjsip show endpoints"
```

### Check Specific Endpoint

```bash
asterisk -rx "pjsip show endpoint 1001"
```

### Check Registrations

```bash
asterisk -rx "pjsip show contacts"
```

### Check AORs

```bash
asterisk -rx "pjsip show aors"
```

---

## Laravel Commands

### Sync All Extensions to PJSIP Tables

```bash
php artisan asterisk:sync-extensions
```

### Verify Asterisk Connectivity and Data

```bash
php artisan asterisk:verify
```

### Start AMI Event Listener

For real-time status updates (run as a daemon):

```bash
php artisan ami:listen
```

For production, use Supervisor:

```ini
[program:ami-listener]
process_name=%(program_name)s
command=php /path/to/mini-pbx/artisan ami:listen
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/mini-pbx/ami-listener.log
```

---

## AMI Events

The Laravel application listens for these AMI events to update extension status:

| Event | Purpose |
|-------|---------|
| `ContactStatus` | Tracks when contacts become reachable/unreachable |
| `PeerStatus` | Tracks registration/unregistration |
| `DeviceStateChange` | Tracks call state (idle, ringing, in-use) |
| `Newchannel` | New call started |
| `Hangup` | Call ended |
| `QueueMemberStatus` | Queue agent status changes |

---

## Database Connection Details

| Setting | Value |
|---------|-------|
| Host | 127.0.0.1 |
| Port | 5432 |
| Database | mini_pbx |
| Username | postgres |
| Password | minipbx2025 |

---

## Important Notes

1. **No config file reloads needed** - Changes are immediate via database lookup
2. **Asterisk writes to `ps_contacts`** - Do not modify this table from Laravel
3. **Passwords are plaintext** - Required by PJSIP userpass authentication
4. **Transports stay in pjsip.conf** - Only endpoints use realtime
5. **AMI must be enabled** - Required for real-time status updates back to Laravel
6. **Observer auto-syncs** - When extensions are created/updated/deleted in Laravel, they are automatically synced to PJSIP tables

---

## Troubleshooting

### ODBC Connection Fails

```bash
# Check ODBC configuration
odbcinst -q -d
odbcinst -q -s

# Test connection
isql asterisk -v

# Check PostgreSQL is accepting connections
psql -h 127.0.0.1 -U postgres -d mini_pbx
```

### Endpoints Not Showing

```bash
# Check sorcery configuration
asterisk -rx "sorcery show wizards"

# Check if realtime is configured
asterisk -rx "realtime show pjsip"

# Verify data exists in database
psql -h 127.0.0.1 -U postgres -d mini_pbx -c "SELECT id FROM ps_endpoints;"
```

### Registration Fails

```bash
# Check endpoint details
asterisk -rx "pjsip show endpoint 1001"

# Check auth details
asterisk -rx "pjsip show auth 1001"

# Check AOR details
asterisk -rx "pjsip show aor 1001"

# Enable PJSIP debug
asterisk -rx "pjsip set logger on"
```

---

## Configuration Files Reference

All example configuration files are available in the Laravel project:

```
docs/asterisk-config/
├── README.md
├── odbc.ini
├── odbcinst.ini
├── res_odbc.conf
├── extconfig.conf
├── sorcery.conf
├── pjsip.conf
├── extensions.conf
└── manager.conf
```

---

## Laravel Forge Daemon Setup

### AMI Listener Daemon

Add a daemon in Laravel Forge to listen for AMI events:

| Field | Value |
|-------|-------|
| **Command** | `/usr/bin/php /home/forge/your-site.com/artisan ami:listen` |
| **User** | `forge` |
| **Directory** | `/home/forge/your-site.com` |
| **Processes** | `1` |

### Reverb WebSocket Server Daemon

Add a daemon for Laravel Reverb (real-time broadcasting):

| Field | Value |
|-------|-------|
| **Command** | `/usr/bin/php /home/forge/your-site.com/artisan reverb:start` |
| **User** | `forge` |
| **Directory** | `/home/forge/your-site.com` |
| **Processes** | `1` |

### Queue Worker Daemon (Optional - for Horizon)

If using Laravel Horizon:

| Field | Value |
|-------|-------|
| **Command** | `/usr/bin/php /home/forge/your-site.com/artisan horizon` |
| **User** | `forge` |
| **Directory** | `/home/forge/your-site.com` |
| **Processes** | `1` |

> **Note:** Replace `your-site.com` with your actual site folder name.

### Environment Variables for Reverb

Add these to your `.env` on the production server:

```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=your-domain.com
REVERB_PORT=8080
REVERB_SCHEME=https

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### Nginx Configuration for Reverb WebSocket

Add this to your Nginx site configuration:

```nginx
location /app {
    proxy_http_version 1.1;
    proxy_set_header Host $http_host;
    proxy_set_header Scheme $scheme;
    proxy_set_header SERVER_PORT $server_port;
    proxy_set_header REMOTE_ADDR $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
    
    proxy_pass http://127.0.0.1:8080;
}
```

---

## CDR (Call Detail Records) Configuration

The Mini-PBX application captures CDR records via AMI events. For this to work, Asterisk must be configured to send CDR events.

### 1. Enable CDR in Asterisk

Edit `/etc/asterisk/cdr.conf`:

```ini
[general]
enable=yes
unanswered=yes
congestion=yes
endbeforehexten=no
initiatedseconds=no
batch=no
```

### 2. Configure AMI to Include CDR Events

Edit `/etc/asterisk/manager.conf`, ensure your AMI user has `cdr` in the read permissions:

```ini
[miniPbx]
secret = your_ami_secret
read = system,call,log,verbose,command,agent,user,config,cdr,reporting
write = system,call,log,verbose,command,agent,user,config,originate
deny = 0.0.0.0/0.0.0.0
permit = 127.0.0.1/255.255.255.255
```

**Key:** The `cdr` permission is essential for receiving CDR events.

### 3. Reload Asterisk

```bash
asterisk -rx "core reload"
asterisk -rx "cdr reload"
```

### 4. Verify CDR Events

Test that CDR events are being sent:

```bash
# In Asterisk CLI
asterisk -rx "cdr show status"

# Should show:
# CDR logging: enabled
# CDR mode: simple
```

Make a test call and check the AMI listener output:

```bash
php artisan ami:listen --debug
```

You should see `CDR` events in the output after each call ends.

### 5. Laravel CDR Test Command

The application includes a command to test CDR functionality:

```bash
# Show CDR statistics and recent logs
php artisan cdr:test

# Create a test CDR record
php artisan cdr:test --create
```

### Troubleshooting CDR

If call logs aren't appearing:

1. **Check AMI Listener is running:**
   ```bash
   php artisan ami:listen --debug
   ```

2. **Verify CDR events in Asterisk:**
   ```bash
   asterisk -rx "cdr show status"
   ```

3. **Check AMI permissions include `cdr`:**
   ```bash
   asterisk -rx "manager show user miniPbx"
   ```

4. **Test CDR directly:**
   ```bash
   php artisan cdr:test --create
   ```

5. **Check Laravel logs:**
   ```bash
   tail -f storage/logs/laravel.log | grep -i cdr
   ```

---

## Extension Groups (Queues) Configuration

Extension Groups in Mini-PBX are synced to Asterisk Queues automatically. This enables advanced call distribution with various ring strategies.

### Overview

When you create or update an Extension Group in the Laravel application:

1. **Laravel Observer** detects the change
2. **AsteriskQueueSyncService** writes to `asterisk_queues` and `asterisk_queue_members` tables
3. **Asterisk** reads queue configuration via Realtime ODBC
4. **AMI Listener** tracks queue events (caller join, leave, agent connect)
5. **Dashboard** displays waiting calls with pickup/redirect capabilities

### Ring Strategies Supported

| Laravel Strategy | Asterisk Strategy | Description |
|------------------|-------------------|-------------|
| `ringall` | `ringall` | Ring all available members simultaneously |
| `hunt` | `linear` | Ring members in order, one at a time |
| `memoryhunt` | `rrmemory` | Round-robin with memory |
| `leastrecent` | `leastrecent` | Ring member with longest idle time |
| `fewestcalls` | `fewestcalls` | Ring member who answered fewest calls |
| `random` | `random` | Ring a random member |

### Database Tables

The following tables store queue configuration for Asterisk Realtime:

#### `asterisk_queues`

| Column | Purpose |
|--------|---------|
| `name` | Queue name (e.g., `extgroup_1`) |
| `strategy` | Ring strategy |
| `timeout` | Member ring timeout |
| `musicclass` | Music on hold class |
| `extension_group_id` | Link to Laravel ExtensionGroup |

#### `asterisk_queue_members`

| Column | Purpose |
|--------|---------|
| `queue_name` | Queue name |
| `interface` | PJSIP/extension |
| `membername` | Display name |
| `penalty` | Priority (lower = higher priority) |
| `paused` | Member paused status |

### Dialplan Configuration

The dialplan routes calls to extension groups via the `[extgroup-handler]` context:

```ini
[extgroup-handler]
exten => _X,1,NoOp(Extension Group ${EXTEN})
 same => n,Set(QUEUE_NAME=extgroup_${EXTEN})
 same => n,Queue(${QUEUE_NAME},tTkK,,,300)
 same => n,Hangup()
```

### FUNC_ODBC Functions

Create `/etc/asterisk/func_odbc.conf` with these functions for dynamic routing:

```ini
[DID_DESTINATION]
dsn=asterisk
readsql=SELECT destination_type || ',' || destination_id FROM dids WHERE number='${ARG1}' AND is_active=true

[EXTGROUP_NAME]
dsn=asterisk
readsql=SELECT name FROM extension_groups WHERE id='${ARG1}' AND is_active=true

[EXTGROUP_STRATEGY]
dsn=asterisk
readsql=SELECT ring_strategy FROM extension_groups WHERE id='${ARG1}'
```

### Realtime Configuration

Add to `/etc/asterisk/extconfig.conf`:

```ini
[settings]
; Existing PJSIP mappings...

; Queue Realtime mappings
queues => odbc,asterisk,asterisk_queues
queue_members => odbc,asterisk,asterisk_queue_members
```

### Laravel Commands

#### Sync All Extension Groups

```bash
php artisan tinker --execute="
    \App\Services\Asterisk\AsteriskQueueSyncService::new()->syncAllExtensionGroups();
"
```

### Waiting Calls UI

The dashboard includes a **Waiting Calls** panel that shows:

- Caller ID and name
- Group/queue name
- Wait time
- **Pickup** button (answer the call)
- **Redirect** button (Admin/QA/Manager only - move to another group)

#### API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/waiting-calls` | GET | Get waiting calls (filtered by user role) |
| `/api/waiting-calls/{channel}/pickup` | POST | Answer a waiting call |
| `/api/waiting-calls/{channel}/redirect` | POST | Redirect to another group |
| `/api/waiting-calls/groups` | GET | Get available groups (admin only) |

### AMI Queue Events

The AMI Listener monitors these events:

| Event | Action |
|-------|--------|
| `QueueCallerJoin` | Caller added to waiting calls cache |
| `QueueCallerLeave` | Caller removed from cache |
| `QueueCallerAbandon` | Caller hung up, removed from cache |
| `AgentConnect` | Agent answered, removed from cache |

### Verification

Check queue configuration:

```bash
# List queues
asterisk -rx "queue show"

# Show specific queue
asterisk -rx "queue show extgroup_1"

# Check queue members
asterisk -rx "queue show members"
```

Check database:

```sql
-- List all queues
SELECT name, strategy, timeout FROM asterisk_queues;

-- List queue members
SELECT queue_name, interface, membername, penalty FROM asterisk_queue_members;
```

### Troubleshooting

#### Queues Not Loading

1. Check Realtime configuration:
   ```bash
   asterisk -rx "realtime show"
   ```

2. Verify ODBC connection:
   ```bash
   isql asterisk -v
   ```

3. Check if queues exist in database:
   ```sql
   SELECT * FROM asterisk_queues;
   ```

4. Reload queues:
   ```bash
   asterisk -rx "queue reload all"
   ```

#### Callers Not Entering Queue

1. Check dialplan is routing correctly:
   ```bash
   asterisk -rx "dialplan show extgroup-handler"
   ```

2. Enable queue debugging:
   ```bash
   asterisk -rx "queue set debug on"
   ```

#### Waiting Calls Not Showing

1. Verify AMI Listener is running:
   ```bash
   php artisan ami:listen --debug
   ```

2. Check if queue events are being sent:
   ```bash
   # In AMI, you should see QueueCallerJoin events
   ```

3. Check cache:
   ```bash
   php artisan tinker --execute="print_r(\Cache::get('waiting_calls'));"
   ```

