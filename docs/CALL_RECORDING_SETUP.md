# Call Recording Setup Guide

This guide explains how to configure call recording in Asterisk so recordings appear in the Mini-PBX call logs.

## Overview

Call recordings work through:
1. Asterisk records calls using MixMonitor
2. Recording path is stored in CDR's UserField
3. AMI Listener captures the CDR event with recording path
4. Laravel stores the path in `call_logs.recording_path`
5. UI provides playback via the call logs page

## Asterisk Configuration

### 1. Recording Directory

Create and configure the recording directory:

```bash
# Create recording directory
sudo mkdir -p /var/spool/asterisk/monitor
sudo chown asterisk:asterisk /var/spool/asterisk/monitor
sudo chmod 755 /var/spool/asterisk/monitor
```

### 2. Dialplan for Recording

In `/etc/asterisk/extensions.conf`, add MixMonitor to record calls:

```ini
[from-internal]
; Internal call recording
exten => _XXXX,1,NoOp(Internal Call to ${EXTEN})
 same => n,Set(__RECORDING=${UNIQUEID}.wav)
 same => n,MixMonitor(/var/spool/asterisk/monitor/${RECORDING},ab)
 same => n,Set(CDR(userfield)=${RECORDING})
 same => n,Dial(PJSIP/${EXTEN},30,tTkK)
 same => n,Hangup()

[from-trunk]
; Inbound call recording
exten => _X.,1,NoOp(Inbound Call from ${CALLERID(num)})
 same => n,Set(__RECORDING=${UNIQUEID}.wav)
 same => n,MixMonitor(/var/spool/asterisk/monitor/${RECORDING},ab)
 same => n,Set(CDR(userfield)=${RECORDING})
 same => n,Goto(route-inbound,${EXTEN},1)

[to-trunk]
; Outbound call recording
exten => _X.,1,NoOp(Outbound Call to ${EXTEN})
 same => n,Set(__RECORDING=${UNIQUEID}.wav)
 same => n,MixMonitor(/var/spool/asterisk/monitor/${RECORDING},ab)
 same => n,Set(CDR(userfield)=${RECORDING})
 same => n,Dial(PJSIP/${TRUNK}/${EXTEN},60,tT)
 same => n,Hangup()
```

#### MixMonitor Options:
- `a` - Append to file if it exists
- `b` - Only save audio once the call is bridged

### 3. CDR Manager Configuration

Ensure CDR events include UserField. In `/etc/asterisk/cdr_manager.conf`:

```ini
[general]
enabled = yes

[mappings]
; Include userfield which contains recording path
userfield => ${CDR(userfield)}
```

### 4. Reload Asterisk

```bash
asterisk -rx "dialplan reload"
asterisk -rx "module reload cdr_manager.so"
```

## Laravel Configuration

### 1. Environment Variables

In `.env`:

```env
ASTERISK_RECORDINGS_PATH=/var/spool/asterisk/monitor
ASTERISK_RECORDING_FORMAT=wav
ASTERISK_AUTO_RECORD=true
ASTERISK_MIX_CHANNELS=true
```

### 2. Storage Symlink (Recommended)

If Asterisk runs on the same server, create a symlink:

```bash
# Create symlink from Laravel storage to Asterisk recordings
ln -s /var/spool/asterisk/monitor /home/forge/mini-pbx.on-forge.com/storage/app/recordings

# Verify
ls -la /home/forge/mini-pbx.on-forge.com/storage/app/recordings
```

### 3. For Remote Asterisk Servers

If Asterisk runs on a different server, you have options:

#### Option A: NFS Mount
```bash
# On web server
sudo mount -t nfs asterisk-server:/var/spool/asterisk/monitor /home/forge/mini-pbx.on-forge.com/storage/app/recordings
```

#### Option B: Sync with rsync (cron job)
```bash
# Add to crontab
*/5 * * * * rsync -avz asterisk-server:/var/spool/asterisk/monitor/ /home/forge/mini-pbx.on-forge.com/storage/app/recordings/
```

#### Option C: SFTP/SCP on demand
Configure the app to fetch recordings via SFTP when requested.

## Sync Existing Recordings

If you have existing call logs without recordings attached, run:

```bash
# Sync recordings for the last 7 days
php artisan recordings:sync

# Sync for the last 30 days
php artisan recordings:sync --days=30

# Re-check all (even those with recording_path set)
php artisan recordings:sync --force

# Dry run (see what would be updated)
php artisan recordings:sync --dry-run
```

## Recording File Naming

The system looks for recordings using these patterns:

1. **UniqueID based**: `{uniqueid}.wav` (e.g., `1766664623.76.wav`)
2. **Date subdirectory**: `YYYY/MM/DD/{uniqueid}.wav`
3. **Caller-based**: `{caller}-{timestamp}.wav`

### Recommended Naming

Use the UniqueID for simplicity:

```ini
Set(__RECORDING=${UNIQUEID}.wav)
MixMonitor(/var/spool/asterisk/monitor/${RECORDING},ab)
Set(CDR(userfield)=${RECORDING})
```

## Troubleshooting

### Recordings not appearing in UI

1. **Check CDR events include recording path:**
   ```bash
   asterisk -rx "cdr show status"
   asterisk -rx "core show function CDR"
   ```

2. **Verify AMI listener is capturing CDR:**
   ```bash
   php artisan ami:listen --debug
   # Look for CDR events with recording path
   ```

3. **Check file permissions:**
   ```bash
   ls -la /var/spool/asterisk/monitor/
   # Should be readable by www-data/forge user
   ```

4. **Verify recording exists:**
   ```bash
   # In Laravel
   php artisan tinker
   >>> App\Models\CallLog::whereNotNull('recording_path')->first()
   ```

5. **Re-sync recordings:**
   ```bash
   php artisan recordings:sync --days=1 --force
   ```

### Audio won't play

1. **Check file format** - Browser supports: WAV, MP3, OGG
2. **Check MIME types** - Server must send correct Content-Type
3. **Check file exists at path:**
   ```bash
   ls -la /var/spool/asterisk/monitor/{recording_path}
   ```

### Performance Considerations

For high call volumes:

1. **Use date-based subdirectories:**
   ```ini
   Set(__RECORDING=${STRFTIME(%Y/%m/%d,${EPOCH},)}/${UNIQUEID}.wav)
   ```

2. **Rotate old recordings:**
   ```bash
   # Delete recordings older than 90 days
   find /var/spool/asterisk/monitor -name "*.wav" -mtime +90 -delete
   ```

3. **Compress recordings:**
   ```bash
   # Convert WAV to MP3 for storage
   for f in /var/spool/asterisk/monitor/*.wav; do
     ffmpeg -i "$f" -codec:a libmp3lame -qscale:a 2 "${f%.wav}.mp3"
     rm "$f"
   done
   ```

## Security

- Recording directory should not be web-accessible
- Access recordings only through authenticated Laravel routes
- Consider encrypting recordings at rest for sensitive data
- Implement retention policies to delete old recordings

## Testing

1. Make a test call between two extensions
2. Check that recording file was created:
   ```bash
   ls -la /var/spool/asterisk/monitor/
   ```
3. Verify CDR event was received:
   ```bash
   php artisan ami:listen --debug
   ```
4. Check call log in UI has recording:
   - Go to `/call-logs`
   - Find the test call
   - Click Play button

## Support

If recordings aren't working:
1. Check Asterisk CLI: `asterisk -rvvv`
2. Check Laravel logs: `tail -f storage/logs/laravel.log`
3. Run AMI listener in debug: `php artisan ami:listen --debug`







