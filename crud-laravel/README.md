# Agenda Web (PHP + MySQL + FullCalendar)

### Requisitos
- PHP 7.4+ (PDO MySQL)
- MySQL / MariaDB
- Servidor web (Apache / Nginx)
- Composer (opcional se usar PHPMailer)

### Instalação
1. Crie o banco e tabelas:
   - `mysql -u root -p < db.sql`

2. Configure a conexão em `db.php` (host, user, pass, db_name).

3. Copie a pasta `public` para o diretório público do seu servidor (ex: `public_html` / `www`).

4. Garanta permissões e acesse `public/register.php` para criar um usuário.

5. Entre em `public/login.php` e, após logado, abrirá o calendário.

### Lembretes por email (opcional)
- Edite `scripts/send_reminders.php` para ajustar intervalo e formato do e-mail.
- Configure um cron para rodá-lo periodicamente.
- Recomendado usar PHPMailer/SMTP para maior confiabilidade.

### Observações de segurança (produça)
- Use HTTPS.
- Proteja variáveis sensíveis (não commit em repositório público).
- Implemente CSRF tokens nos forms.
- Valide/escape dados no front e backend.

