# Asterisk Configuration for PJSIP Realtime

This directory contains example Asterisk configuration files for integrating with the Mini-PBX Laravel application using PJSIP Realtime.

## Prerequisites

1. Asterisk with PJSIP support (Asterisk 13+)
2. PostgreSQL ODBC driver (`odbc-postgresql`)
3. Asterisk ODBC support (`res_odbc`, `res_config_odbc`)

### Install ODBC on Ubuntu/Debian

```bash
sudo apt-get install unixodbc unixodbc-dev odbc-postgresql
```

## Configuration Steps

### 1. Configure ODBC Connection

Copy `odbc.ini` to `/etc/odbc.ini` and update the database credentials.

### 2. Configure Asterisk ODBC Resource

Copy `res_odbc.conf` to `/etc/asterisk/res_odbc.conf`.

### 3. Configure Realtime Mapping

Copy `extconfig.conf` to `/etc/asterisk/extconfig.conf`.

### 4. Configure Sorcery for PJSIP

Copy `sorcery.conf` to `/etc/asterisk/sorcery.conf`.

### 5. Configure PJSIP Transports

Copy `pjsip.conf` to `/etc/asterisk/pjsip.conf`.

### 6. Configure Dialplan

Copy `extensions.conf` to `/etc/asterisk/extensions.conf` or merge with existing.

### 7. Configure AMI

Copy `manager.conf` to `/etc/asterisk/manager.conf`.

## Verify Configuration

After copying files, restart Asterisk:

```bash
sudo systemctl restart asterisk
```

Check realtime is working:

```bash
asterisk -rx "realtime show pjsip"
asterisk -rx "pjsip show endpoints"
```

## Sync Extensions

Run the Laravel sync command to populate PJSIP tables:

```bash
php artisan asterisk:sync-extensions
```

Verify with:

```bash
php artisan asterisk:verify
```

