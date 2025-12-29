# CDR Troubleshooting Guide

## Quick Diagnosis

Run on your **production server**:

```bash
# Step 1: Check if AMI listener daemon is running
ps aux | grep ami:listen

# Step 2: Run diagnostic tool
php artisan ami:diagnose

# Step 3: Make a test call and watch events
php artisan ami:diagnose --test-call
```

---

## Common Issues

### Issue 1: AMI Listener Not Running

**Symptoms:**
- No call logs being recorded
- Dashboard shows no live calls

**Solution:**
```bash
# Check if running
ps aux | grep ami:listen

# Start it (foreground for testing)
php artisan ami:listen --debug

# Start as daemon (production)
nohup php artisan ami:listen >> storage/logs/ami.log 2>&1 &

# Or in Laravel Forge: Add as daemon
# Command: php artisan ami:listen
# Directory: /home/forge/your-site.com
```

### Issue 2: AMI Connection Failed

**Symptoms:**
```
AMI Error: fsockopen(): Unable to connect to 127.0.0.1:5038
```

**Solutions:**

1. **Check Asterisk is running:**
   ```bash
   systemctl status asterisk
   ```

2. **Check AMI is enabled:**
   ```bash
   cat /etc/asterisk/manager.conf | head -20
   ```
   
   Should have:
   ```ini
   [general]
   enabled = yes
   port = 5038
   bindaddr = 127.0.0.1
   ```

3. **Check port is listening:**
   ```bash
   netstat -tlnp | grep 5038
   ```

### Issue 3: AMI Authentication Failed

**Symptoms:**
```
Authentication failed: Invalid username or password
```

**Solutions:**

1. **Check credentials in System Settings** (`/platform/system-settings`)

2. **Verify Asterisk manager.conf:**
   ```bash
   cat /etc/asterisk/manager.conf
   ```
   
   Should have your user:
   ```ini
   [miniPbx]
   secret = your_password_here
   read = system,call,log,verbose,command,agent,user,config,cdr,reporting
   write = system,call,log,verbose,command,agent,user,config,originate
   permit = 127.0.0.1/255.255.255.255
   ```

3. **Reload manager:**
   ```bash
   asterisk -rx "manager reload"
   ```

### Issue 4: Events Received But No CDR Event

**Symptoms:**
- AMI listener shows Newchannel, Hangup events
- But no `Cdr` event after call ends

**Solutions:**

1. **Add CDR permission to AMI user:**
   Edit `/etc/asterisk/manager.conf`:
   ```ini
   read = system,call,log,verbose,command,agent,user,config,cdr,reporting
   ```
   Note the `cdr` in the read permissions!

2. **Enable CDR in Asterisk:**
   Edit `/etc/asterisk/cdr.conf`:
   ```ini
   [general]
   enable=yes
   unanswered=yes
   ```

3. **Reload:**
   ```bash
   asterisk -rx "manager reload"
   asterisk -rx "cdr reload"
   ```

4. **Verify CDR is enabled:**
   ```bash
   asterisk -rx "cdr show status"
   ```

### Issue 5: Dashboard Not Showing Live Calls

**Symptoms:**
- CDR records are being created
- But dashboard doesn't show active calls

**Solutions:**

1. **Check if AMI listener is broadcasting events:**
   - The AMI listener broadcasts `CallStarted` events
   - Check Laravel logs: `tail -f storage/logs/laravel.log`

2. **Check Reverb/WebSocket is running:**
   ```bash
   ps aux | grep reverb
   php artisan reverb:start
   ```

3. **Check browser console for WebSocket errors**

---

## Alternative: Direct PostgreSQL CDR

If AMI events are problematic, configure Asterisk to write CDR directly to PostgreSQL.

### Step 1: Install ODBC PostgreSQL Driver
```bash
apt-get install odbc-postgresql unixodbc
```

### Step 2: Configure ODBC
Edit `/etc/odbc.ini`:
```ini
[asterisk]
Driver = PostgreSQL Unicode
Description = PostgreSQL for Asterisk
Servername = 127.0.0.1
Port = 5432
Database = mini_pbx
Username = postgres
Password = MiniPbx@2025
```

### Step 3: Configure Asterisk res_odbc
Edit `/etc/asterisk/res_odbc.conf`:
```ini
[asterisk]
enabled => yes
dsn => asterisk
username => postgres
password => MiniPbx@2025
pre-connect => yes
```

### Step 4: Configure cdr_adaptive_odbc
Edit `/etc/asterisk/cdr_adaptive_odbc.conf`:
```ini
[minipbx]
connection=asterisk
table=call_logs

; Column mappings (Asterisk CDR field => Database column)
alias start => start_time
alias answer => answer_time  
alias end => end_time
alias src => caller_id
alias dst => callee_id
alias billsec => billable_duration
alias disposition => status
alias uniqueid => uniqueid
alias linkedid => linkedid
alias channel => hangup_cause
```

### Step 5: Set defaults in database
The `type` and `direction` columns need defaults since Asterisk doesn't provide them:
```sql
ALTER TABLE call_logs ALTER COLUMN type SET DEFAULT 'internal';
ALTER TABLE call_logs ALTER COLUMN direction SET DEFAULT 'internal';
```

### Step 6: Reload Asterisk
```bash
asterisk -rx "module reload cdr_adaptive_odbc.so"
asterisk -rx "cdr show status"
```

---

## Verification

After fixing, verify with:

```bash
# 1. Create test CDR manually
php artisan cdr:test --create

# 2. Check in database
php artisan cdr:test

# 3. Make a real call and check
php artisan ami:diagnose --test-call
```

Then check `/call-logs` in the web app.







