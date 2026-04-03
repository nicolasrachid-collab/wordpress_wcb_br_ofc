# Exemplo: enviar wp-content/wpvividbackups para o servidor via SFTP OpenSSH.
# Copia para upload-wpvivid-backups.ps1, preenche as variáveis e executa no PowerShell.
#
# Requer: OpenSSH Client (Windows: Opcional Features -> OpenSSH Client)

$RemoteUser = "UTILIZADOR_CPANEL"
$RemoteHost = "vps-14600522.srvbra.com.br"
$RemotePath = "/home/UTILIZADOR/public_html/wp-content/wpvividbackups"
$LocalPath  = "C:\xampp\htdocs\wcb\wp-content\wpvividbackups"

# Cria pasta remota (opcional) e sincroniza .zip/.part*.zip e ficheiros de lista se existirem
# scp -r não é incremental; para sites grandes prefere WinSCP, FileZilla ou rsync.
Write-Host "A enviar (pode demorar)..."
scp -r "${LocalPath}\*" "${RemoteUser}@${RemoteHost}:${RemotePath}/"
