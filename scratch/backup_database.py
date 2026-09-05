import paramiko
import os
from datetime import datetime

def backup():
    ip = "13.140.171.252"
    user = "root"
    key_path = r"C:\Users\Msi\.ssh\id_rsa"
    
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    private_key = paramiko.RSAKey.from_private_key_file(key_path)
    
    try:
        print("Connecting to VPS...")
        ssh.connect(ip, username=user, pkey=private_key, timeout=10)
        
        timestamp = datetime.now().strftime("%Y_%m_%d")
        remote_backup_dir = "/var/www/agromind/backend/database/backups"
        remote_file = f"{remote_backup_dir}/backup_agromind_{timestamp}.sql"
        latest_remote_file = f"{remote_backup_dir}/backup_agromind_latest.sql"
        
        local_dir = r"d:\projects\agromind\backend\database\backups"
        os.makedirs(local_dir, exist_ok=True)
        local_file = os.path.join(local_dir, f"backup_agromind_{timestamp}.sql")
        latest_local_file = os.path.join(local_dir, "backup_agromind_latest.sql")
        
        # 1. Create remote backup directory
        ssh.exec_command(f"mkdir -p {remote_backup_dir}")
        
        # 2. Run mysqldump inside MySQL container
        dump_cmd = f"docker exec agromind-mysql-1 mysqldump -u agromind_user -pagromind_prod_pass_9988 agromind > {remote_file} && cp {remote_file} {latest_remote_file}"
        print(f"Creating SQL dump on VPS: {remote_file}...")
        stdin, stdout, stderr = ssh.exec_command(dump_cmd, timeout=30)
        err = stderr.read().decode('utf-8', errors='ignore')
        if err and "password on the command line interface can be insecure" not in err:
            print(f"Dump warning/error: {err}")
            
        # 3. Download to local machine
        print("Downloading SQL backup to local machine...")
        sftp = ssh.open_sftp()
        sftp.get(remote_file, local_file)
        sftp.get(latest_remote_file, latest_local_file)
        sftp.close()
        
        size_kb = os.path.getsize(local_file) / 1024
        print(f"Backup downloaded successfully! Size: {size_kb:.2f} KB ({local_file})")
        
        ssh.close()
    except Exception as e:
        print(f"Failed: {e}")

if __name__ == "__main__":
    backup()
