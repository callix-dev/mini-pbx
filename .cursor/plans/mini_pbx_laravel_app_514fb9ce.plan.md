---
name: Mini PBX Laravel App
overview: Build a full-featured PBX management system using Laravel 12, Tailwind CSS, PostgreSQL 17, and Asterisk 22 with PJSIP integration. The application will feature real-time WebSocket communication, WebRTC softphone, visual IVR builder, and FOP2-style dashboard with spy/whisper/barge capabilities.
todos:
  - id: phase1-setup
    content: "Project setup: Laravel 12 + Breeze + PostgreSQL + Tailwind theme + sidebar layout"
    status: completed
  - id: phase1-auth
    content: Authentication system with roles, permissions, single session, timeout
    status: completed
    dependencies:
      - phase1-setup
  - id: phase1-services
    content: "Core services: AMI, ARI, config generator, audio converter"
    status: completed
    dependencies:
      - phase1-setup
  - id: phase2-extensions
    content: "Extensions module: PJSIP CRUD, bulk operations, config generation"
    status: completed
    dependencies:
      - phase1-services
  - id: phase2-groups
    content: Extension Groups with ring strategies
    status: completed
    dependencies:
      - phase2-extensions
  - id: phase2-dids
    content: DID management with time-based and caller ID routing
    status: completed
    dependencies:
      - phase1-services
  - id: phase2-queues
    content: Call Queues with VIP priority, agent assignment, settings
    status: completed
    dependencies:
      - phase2-extensions
      - phase2-groups
  - id: phase2-ringtrees
    content: Ring Trees builder (3 levels)
    status: completed
    dependencies:
      - phase2-extensions
      - phase2-groups
      - phase2-queues
  - id: phase2-ivr
    content: Visual IVR builder with drag-drop flow designer
    status: completed
    dependencies:
      - phase1-services
  - id: phase2-blockfilter
    content: Block Filter management with blacklist/whitelist and expiry
    status: completed
    dependencies:
      - phase1-setup
  - id: phase2-voicemail
    content: Voicemail management with visual interface
    status: completed
    dependencies:
      - phase2-extensions
  - id: phase2-audio
    content: Hold Music and Soundboard management
    status: completed
    dependencies:
      - phase1-services
  - id: phase3-carriers
    content: Outbound and Inbound Carrier configuration
    status: completed
    dependencies:
      - phase1-services
  - id: phase3-breaks
    content: Break Codes management
    status: completed
    dependencies:
      - phase1-setup
  - id: phase3-settings
    content: System Settings (AMI/ARI, timezone, SMTP, STUN/TURN, retention)
    status: completed
    dependencies:
      - phase1-setup
  - id: phase4-websocket
    content: WebSocket infrastructure with Laravel Reverb
    status: completed
    dependencies:
      - phase1-setup
  - id: phase4-ami-listener
    content: AMI event listener and real-time broadcasting
    status: completed
    dependencies:
      - phase4-websocket
      - phase1-services
  - id: phase4-webrtc
    content: WebRTC softphone with SIP.js
    status: completed
    dependencies:
      - phase4-websocket
      - phase2-extensions
  - id: phase4-dashboard
    content: FOP2-style dashboard with real-time updates, spy/whisper/barge
    status: completed
    dependencies:
      - phase4-ami-listener
      - phase4-webrtc
  - id: phase5-cdr
    content: Call Detail Records capture and display
    status: completed
    dependencies:
      - phase4-ami-listener
  - id: phase5-disposition
    content: Call disposition codes and notes
    status: completed
    dependencies:
      - phase5-cdr
  - id: phase5-callback
    content: Agent callback scheduling with reminders
    status: completed
    dependencies:
      - phase5-cdr
  - id: phase5-analytics
    content: Call Analytics with reports, export, scheduled emails
    status: completed
    dependencies:
      - phase5-cdr
  - id: phase6-users
    content: User Management with granular permissions
    status: completed
    dependencies:
      - phase1-auth
  - id: phase6-api
    content: API Keys and REST API
    status: completed
    dependencies:
      - phase1-auth
  - id: phase6-audit
    content: Audit Logging system
    status: completed
    dependencies:
      - phase1-setup
  - id: phase6-health
    content: System Health Monitoring for Superadmins
    status: completed
    dependencies:
      - phase1-services
  - id: phase6-backup
    content: Backup and Restore functionality
    status: completed
    dependencies:
      - phase1-setup
  - id: phase6-notifications
    content: Browser and Desktop notifications
    status: completed
    dependencies:
      - phase4-websocket
---

# Mini PBX System - Laravel 12 + Asterisk 22

## Technology Stack

| Component | Technology |

|-----------|------------|

| Backend | Laravel 12, PHP 8.3 |

| Frontend | Tailwind CSS, Alpine.js, Livewire |

| Database | PostgreSQL 17 |

| PBX | Asterisk 22 with PJSIP |

| Real-time | Laravel Reverb (WebSockets), AMI, ARI |

| WebRTC | SIP.js |

| Auth | Laravel Breeze |

| Queue | Laravel Horizon (Redis) |

## Architecture Overview

```mermaid
graph TB
    subgraph frontend [Frontend Layer]
        UI[Laravel Blade + Tailwind]
        WebRTC[SIP.js Softphone]
        WS[WebSocket Client]
    end
    
    subgraph backend [Backend Layer]
        Laravel[Laravel 12 App]
        Reverb[Laravel Reverb]
        Jobs[Queue Jobs]
    end
    
    subgraph asterisk [Asterisk Layer]
        AMI[AMI Socket]
        ARI[ARI REST API]
        AST[Asterisk 22]
    end
    
    subgraph storage [Data Layer]
        PG[(PostgreSQL 17)]
        Redis[(Redis)]
        Files[Config Files]
    end
    
    UI --> Laravel
    WebRTC --> AST
    WS --> Reverb
    Laravel --> AMI
    Laravel --> ARI
    Laravel --> PG
    Laravel --> Redis
    Laravel --> Files
    Reverb --> Redis
    Jobs --> Redis
    AMI --> AST
    ARI --> AST
    Files --> AST
end
```

## Database Schema (Key Tables)

```mermaid
erDiagram
    users ||--o{ user_permissions : has
    users ||--o{ extensions : owns
    users }o--|| roles : belongs_to
    
    extensions ||--o{ queue_members : joins
    extensions }o--o{ extension_groups : belongs_to
    
    queues ||--o{ queue_members : has
    queues }o--o| block_filter_groups : uses
    queues }o--o| hold_music : uses
    queues }o--o| soundboards : uses
    
    dids ||--o{ did_routes : has
    dids }o--o| ivrs : routes_to
    
    ivrs ||--o{ ivr_nodes : contains
    
    ring_trees ||--o{ ring_tree_nodes : contains
    
    call_logs ||--o{ call_recordings : has
    call_logs }o--o| dispositions : tagged_with
    
    carriers ||--o{ dids : provides
```

## Theme Design

- **Primary Color**: Indigo (`#4F46E5` / `indigo-600`)
- **Accent Color**: Teal (`#14B8A6` / `teal-500`)
- **Dark Sidebar**: Slate-900 background with indigo highlights
- **Light/Dark Mode Toggle**: System preference detection + manual toggle
- **Layout**: Fixed left sidebar (collapsible on mobile), top header bar

## Module Breakdown

### Phase 1: Foundation (Core Setup)

1. **Project Setup**

   - Laravel 12 installation with Breeze
   - PostgreSQL 17 configuration
   - Tailwind CSS with custom theme (Indigo/Teal)
   - Dark/Light mode implementation
   - Responsive sidebar layout

2. **Authentication & Authorization**

   - Role-based permissions (Superadmin, Admin, Quality Analyst, Manager, Agent)
   - Granular permission system with Spatie Laravel Permission
   - Single session enforcement
   - 8-hour session timeout

3. **Core Services**

   - AMI connection manager (configurable)
   - ARI connection manager (configurable)  
   - Asterisk config file generator service
   - Audio file converter (MP3 to Asterisk format using FFmpeg)

### Phase 2: Telephony Management

4. **Extensions Module**

   - PJSIP endpoint management (username, agent name, password)
   - Create, Update, Delete, Status toggle
   - Bulk create via CSV/Excel upload
   - Bulk actions (enable/disable/delete)
   - Email credentials to user
   - Config file generation (`pjsip_endpoints.conf`)

5. **Extension Groups Module**

   - Create groups with ring strategy (Ring All, Hunting)
   - Assign extensions to groups
   - Timeout and failover settings
   - Used for routing and organization

6. **DID Management**

   - DID CRUD with number, description, destination
   - Time-based routing rules (business hours / after hours)
   - Caller ID-based routing
   - Bulk import via CSV/Excel
   - Link to IVR, Queue, Extension, Ring Tree, Voicemail

7. **Call Queues**

   - Queue configuration with priority support
   - VIP caller list for priority routing
   - Agent assignment with manual login/logout
   - Active times and out-of-office settings
   - Hold music assignment
   - Block filter group assignment
   - Soundboard assignment
   - Config file generation (`queues.conf`)

8. **Ring Trees**

   - Up to 3 levels deep
   - Visual tree builder
   - Destinations: Extension, Extension Group, Queue, Hangup, Voicemail, Block Filter
   - Timeout per node

9. **IVR Builder**

   - Visual drag-drop flow designer (using interact.js or similar)
   - Node types: Play audio, Get digits, Route, Time condition, Hangup
   - Audio file upload (MP3 conversion)
   - Dialplan generation (`extensions.conf`)

10. **Block Filter Management**

    - Blacklist and Whitelist support
    - Expiry days per entry
    - Assign to Queues, DIDs, Ring Trees

11. **Voicemail Management**

    - Visual voicemail interface
    - Web audio player
    - Mark read/unread, forward, delete
    - Email notification with attachment
    - Voicemail greeting upload

12. **Hold Music & Soundboard**

    - Multiple hold music classes
    - Upload custom MP3 files (auto-convert)
    - Assign to queues
    - Soundboard: clips playable during calls (to both parties)

### Phase 3: Carriers & Settings

13. **Outbound Carriers**

    - SIP trunk configuration (IP-auth and registration-based)
    - Codec selection
    - Carrier selection on click-to-call

14. **Inbound Carriers**

    - Similar to outbound
    - Link DIDs to carriers

15. **Break Codes**

    - Predefined codes (Lunch, Tea, Training, etc.)
    - Custom code creation
    - Agent break tracking in reports

16. **System Settings**

    - AMI connection settings
    - ARI connection settings
    - Timezone selection (default UTC)
    - SMTP email configuration
    - STUN/TURN server configuration (self-hosted + Google fallback)
    - Data retention (auto-delete after X days, default: disabled)

### Phase 4: Real-time & WebRTC

17. **WebSocket Infrastructure**

    - Laravel Reverb setup
    - Real-time event broadcasting
    - Agent presence channel
    - Call events channel

18. **AMI Event Listener**

    - Background process listening to AMI events
    - Call start/end/state changes
    - Agent state changes
    - Queue events
    - Broadcast to WebSocket

19. **WebRTC Softphone**

    - SIP.js integration
    - Register as PJSIP endpoint
    - Make/receive calls
    - Hold, transfer (blind/attended), mute
    - 3-way ad-hoc conference
    - DTMF support
    - Soundboard playback during call

20. **FOP2-Style Dashboard**

    - Real-time extension status (BLF-like)
    - Active calls display
    - Agent states visualization
    - Visual parking lot
    - Click-to-call
    - Spy/Whisper/Barge buttons (permission-based)
    - Queue statistics
    - Agents in same queue visibility (configurable)

### Phase 5: Call Logs & Analytics

21. **Call Detail Records**

    - Real-time capture via AMI events
    - Periodic sync from Asterisk CDR table
    - Recording path storage
    - In-browser audio player + download

22. **Call Disposition**

    - Predefined codes (Sold, Callback, Not Interested, etc.)
    - Admin-configurable custom codes
    - Required after call wrap-up

23. **Agent Callback Scheduling**

    - Schedule callback with date/time
    - Reminder notifications (browser + desktop)
    - Callback list view

24. **Call Analytics**

    - Inbound/Outbound/Missed call reports
    - Agent performance metrics
    - Queue statistics
    - SLA metrics
    - Hourly/daily/weekly trends
    - Custom report builder
    - Export to CSV/Excel
    - Scheduled email reports (cron-style flexibility)

### Phase 6: Platform Administration

25. **User Management**

    - CRUD for all roles
    - Permission assignment
    - QA-specific permissions (spy/barge) per user
    - Agent queue assignment
    - Extension assignment

26. **API Keys**

    - Generate API keys per user
    - Full REST API access
    - Rate limiting
    - API documentation (auto-generated)

27. **Audit Logging**

    - All configuration changes logged
    - Before/after values stored
    - User, timestamp, IP address
    - Searchable audit trail

28. **System Health Monitoring (Superadmin)**

    - Asterisk connection status
    - Active channels count
    - SIP trunk registration status
    - System resources (if accessible)

29. **Backup & Restore**

    - Export configuration as JSON/ZIP
    - Import/restore from backup
    - Scheduled auto-backup to storage
    - Backup history management

30. **Notifications**

    - Browser push notifications (Web Push API)
    - Desktop notifications (Notification API)
    - Email notifications
    - Notification preferences per user

## Key Files Structure

```
app/
├── Http/Controllers/
│   ├── Telephony/
│   │   ├── ExtensionController.php
│   │   ├── QueueController.php
│   │   ├── DidController.php
│   │   ├── IvrController.php
│   │   └── ...
│   ├── CallLogs/
│   ├── Settings/
│   └── Platform/
├── Models/
│   ├── Extension.php
│   ├── Queue.php
│   ├── Did.php
│   ├── CallLog.php
│   └── ...
├── Services/
│   ├── Asterisk/
│   │   ├── AmiService.php
│   │   ├── AriService.php
│   │   ├── ConfigGenerator.php
│   │   └── AudioConverter.php
│   ├── WebRTC/
│   └── Reports/
├── Jobs/
│   ├── GenerateAsteriskConfig.php
│   ├── SyncCdrRecords.php
│   └── SendScheduledReport.php
├── Events/
│   ├── CallStarted.php
│   ├── AgentStateChanged.php
│   └── ...
└── Listeners/
    └── AmiEventListener.php

resources/views/
├── layouts/
│   └── app.blade.php (sidebar layout)
├── components/
│   ├── sidebar.blade.php
│   ├── softphone.blade.php
│   └── ...
├── dashboard/
├── telephony/
├── call-logs/
├── settings/
└── platform/
```

## Implementation Notes

1. **Asterisk Config Strategy**: Generate individual include files per module, main configs include them. Reload specific modules after changes.

2. **CDR Dual Strategy**: AMI events for real-time display, scheduled job syncs from Asterisk DB hourly for data integrity.

3. **WebRTC**: SIP.js connects directly to Asterisk WebSocket (wss). PJSIP endpoint auto-created for WebRTC users with proper codecs (opus, PCMU, PCMA).

4. **IVR Dialplan**: Store IVR structure in DB, generate `extensions.conf` context per IVR on save.

5. **Audio Conversion**: Use FFmpeg to convert uploaded MP3 to WAV (8kHz, mono, 16-bit) for Asterisk compatibility.

6. **Permissions**: Use Spatie Laravel Permission package. Create granular permissions for each action, assign to roles, allow per-user overrides for QA features.