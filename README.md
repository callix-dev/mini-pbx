# Mini-PBX

A modern, web-based PBX management system built with Laravel 12 and integrated with Asterisk via PJSIP Realtime.

## Features

- **Extension Management**: Create, edit, and manage SIP extensions with automatic PJSIP sync
- **Extension Groups**: Organize extensions into groups with flexible ring strategies
- **Queue Management**: Configure call queues with agent management
- **DID Routing**: Route inbound DIDs to extensions, queues, IVRs, or ring trees
- **IVR Builder**: Create interactive voice response menus
- **Ring Trees**: Configure multi-level call routing with fallback options
- **Voicemail**: Voicemail with email notifications
- **Call Logs**: Comprehensive call history and analytics
- **Real-time Status**: Live extension registration and call status via AMI
- **User Management**: Role-based access control with Spatie permissions
- **API Keys**: Secure API access for external integrations

## Requirements

- PHP 8.3+
- PostgreSQL 14+
- Node.js 20+
- Asterisk 18+ with PJSIP support
- Redis (for queues and caching)

## Installation

1. Clone the repository:
```bash
git clone https://github.com/callix-dev/mini-pbx.git
cd mini-pbx
```

2. Install dependencies:
```bash
composer install
npm install
```

3. Configure environment:
```bash
cp .env.example .env
php artisan key:generate
```

4. Set up the database:
```bash
php artisan migrate
php artisan db:seed
```

5. Build frontend assets:
```bash
npm run build
```

6. Start the development server:
```bash
php artisan serve
```

## Asterisk Integration

Mini-PBX uses PJSIP Realtime to sync extensions directly to Asterisk's database. This means:

- No config file management needed
- Changes are immediate (no reload required)
- Single source of truth in Laravel
- Real-time status updates via AMI

### Setup Asterisk

1. Install PostgreSQL ODBC driver:
```bash
sudo apt-get install unixodbc odbc-postgresql
```

2. Copy the example Asterisk configuration files from `docs/asterisk-config/` to your Asterisk server.

3. Update database credentials in:
   - `/etc/odbc.ini`
   - `/etc/asterisk/res_odbc.conf`

4. Restart Asterisk:
```bash
sudo systemctl restart asterisk
```

5. Sync extensions:
```bash
php artisan asterisk:sync-extensions
```

6. Verify configuration:
```bash
php artisan asterisk:verify
```

### Configuration

Add to your `.env`:

```env
# AMI Configuration
AMI_HOST=127.0.0.1
AMI_PORT=5038
AMI_USERNAME=mini-pbx
AMI_PASSWORD=your_secure_password

# ARI Configuration (optional)
ARI_HOST=127.0.0.1
ARI_PORT=8088
ARI_USERNAME=admin
ARI_PASSWORD=your_ari_password

# PJSIP Configuration
PJSIP_DEFAULT_TRANSPORT=transport-udp
PJSIP_DEFAULT_CONTEXT=from-internal
PJSIP_ALLOWED_CODECS=ulaw,alaw,g722,opus
```

### AMI Event Listener

Start the AMI listener to receive real-time events:

```bash
php artisan ami:listen
```

For production, use Supervisor to keep it running:

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

## Artisan Commands

| Command | Description |
|---------|-------------|
| `php artisan asterisk:sync-extensions` | Sync all extensions to PJSIP tables |
| `php artisan asterisk:verify` | Verify Asterisk connectivity and config |
| `php artisan ami:listen` | Start AMI event listener |

## Default Credentials

After seeding, you can login with:

- **Email**: admin@example.com
- **Password**: password

## Directory Structure

```
app/
├── Console/Commands/      # Artisan commands (AMI listener, sync)
├── Events/               # Broadcasting events
├── Http/Controllers/     # Web controllers
├── Models/              # Eloquent models
├── Observers/           # Model observers (PJSIP sync)
├── Services/Asterisk/   # Asterisk integration services
config/
├── asterisk.php         # Asterisk configuration
database/
├── migrations/          # Including PJSIP realtime tables
docs/
├── asterisk-config/     # Example Asterisk configuration files
resources/
├── views/              # Blade templates
```

## License

This project is proprietary software. All rights reserved.
