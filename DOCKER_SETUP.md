# Docker Setup Guide

This guide will help you run the n8n-AI Laravel application using Docker.

## Prerequisites

- Docker and Docker Compose installed on your system
- OpenAI API key (GPT-4o)
- (Optional) Slack webhook URL for failure notifications

## Quick Start

1. **Copy environment file**:
   ```bash
   cp .env.example .env
   ```

2. **Update the `.env` file** with your credentials:
   ```env
   # OpenAI Configuration (used by n8n)
   # Add this in n8n UI after startup

   # n8n Configuration
   N8N_WEBHOOK_URL=http://n8n:5678/webhook/ad-script-refactor
   N8N_API_KEY=your-n8n-api-key-here
   N8N_WEBHOOK_SECRET=your-hmac-secret-key-here

   # n8n Retry Configuration
   N8N_RETRY_MAX_ATTEMPTS=3
   N8N_RETRY_DELAY_SECONDS=5
   N8N_RETRY_BACKOFF_MULTIPLIER=2

   # Slack Notifications (Optional)
   SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK/URL
   ```

3. **Start all services**:
   ```bash
   docker-compose up -d
   ```

4. **Access the applications**:
   - **Laravel App**: http://localhost:8000
   - **n8n Workflow**: http://localhost:5678
   - **MySQL**: localhost:3306

## Service Details

### Laravel Application
- **Port**: 8000
- **Container**: n8n-ai-laravel
- **Database**: MySQL (auto-configured)
- **Queue Worker**: Running automatically in background
- **Migrations**: Run automatically on startup

### MySQL Database
- **Port**: 3306
- **Database**: n8n_ai
- **Username**: laravel
- **Password**: password
- **Root Password**: rootpassword

### n8n Workflow Engine
- **Port**: 5678
- **Container**: n8n-ai-n8n
- **Username**: admin
- **Password**: admin

## n8n Configuration

After starting the containers:

1. Navigate to http://localhost:5678
2. Login with credentials: `admin` / `admin`
3. Configure OpenAI credentials:
   - Go to Settings → Credentials
   - Add new credential → OpenAI
   - Enter your OpenAI API key: `sk-proj-******`

4. Create the webhook workflow:
   - Create a new workflow
   - Add Webhook node:
     - Path: `ad-script-refactor`
     - Method: POST
   - Add OpenAI Agent node (or AI Agent with OpenAI)
   - Configure to receive `reference_script` and `outcome_description`
   - Return JSON with:
     ```json
     {
       "new_script": "improved script text",
       "analysis": "analysis of changes"
     }
     ```
   - Add HTTP Request node to send results back to Laravel:
     - URL: `http://laravel-app:8000/api/ad-scripts/{{ $json.task_id }}/result`
     - Method: POST
     - Headers:
       ```
       X-N8N-Signature: {{ $crypto.createHmac('sha256', 'YOUR_WEBHOOK_SECRET').update($json.body).digest('hex') }}
       X-N8N-API-KEY: your-n8n-api-key-here
       ```
     - Body: JSON with new_script and analysis
       ```json
       {
         "new_script": "{{ $json.new_script }}",
         "analysis": "{{ $json.analysis }}"
       }
       ```

## Security Configuration

### Generate Secure Keys

Before deploying, generate secure keys:

```bash
# Generate HMAC secret (32 bytes)
openssl rand -hex 32

# Generate API key (16 bytes)
openssl rand -hex 16
```

Add these to your `.env` file:
```env
N8N_API_KEY=<generated-api-key>
N8N_WEBHOOK_SECRET=<generated-hmac-secret>
```

### HMAC Signature Validation

The webhook endpoint uses HMAC-SHA256 for cryptographic verification:

1. **Primary**: HMAC signature validation (if secret configured)
2. **Fallback**: API key validation

See [SECURITY.md](SECURITY.md) for detailed security documentation.

### Authentication for API Endpoints

API endpoints use Laravel Sanctum:

```bash
# Create API token
docker-compose exec laravel-app php artisan tinker
>>> $user = App\Models\User::first();
>>> $token = $user->createToken('api-token')->plainTextToken;
>>> echo $token;
```

Use the token in API requests:
```bash
curl -H "Authorization: Bearer $token" \
     http://localhost:8000/api/ad-scripts
```

## Common Commands

### View logs
```bash
# All services
docker-compose logs -f

# Laravel only
docker-compose logs -f laravel-app

# n8n only
docker-compose logs -f n8n
```

### Restart services
```bash
docker-compose restart
```

### Stop all services
```bash
docker-compose down
```

### Stop and remove volumes (fresh start)
```bash
docker-compose down -v
```

### Run artisan commands
```bash
docker-compose exec laravel-app php artisan migrate
docker-compose exec laravel-app php artisan queue:work
docker-compose exec laravel-app php artisan test
```

### Access Laravel container shell
```bash
docker-compose exec laravel-app sh
```

### Access MySQL
```bash
docker-compose exec mysql mysql -u laravel -ppassword n8n_ai
```

## Testing the Setup

1. Open http://localhost:8000 in your browser
2. Navigate to "Ad Scripts" in the sidebar
3. Click "Create New Task"
4. Fill in:
   - **Reference Script**: "Buy now! Limited offer!"
   - **Desired Outcome**: "Make it more professional and engaging"
5. Submit the form
6. The task will be sent to n8n (check http://localhost:5678 for workflow execution)
7. Results will be displayed when processing completes

## Troubleshooting

### Laravel app won't start
- Check if MySQL is healthy: `docker-compose ps`
- View logs: `docker-compose logs laravel-app`
- Ensure APP_KEY is set: `docker-compose exec laravel-app php artisan key:generate`

### MySQL connection refused
- Wait for health check to pass (10-15 seconds after start)
- Verify credentials in `.env` match docker-compose.yml

### n8n webhook not working
- Verify webhook URL in Laravel `.env`: `N8N_WEBHOOK_URL=http://n8n:5678/webhook/ad-script-refactor`
- Check n8n workflow is activated
- View n8n logs: `docker-compose logs n8n`

### Queue not processing
- Queue worker runs automatically in the container
- Check logs: `docker-compose logs laravel-app | grep queue`
- Manually restart: `docker-compose restart laravel-app`

## Production Deployment

For production use, update the following:

1. Change MySQL passwords in `docker-compose.yml`
2. Set `APP_ENV=production` and `APP_DEBUG=false`
3. Use a proper `APP_KEY` (generate with `php artisan key:generate`)
4. Configure SSL/TLS for n8n
5. Use persistent volumes for production data
6. Set up proper backup strategies for MySQL and n8n volumes
7. Remove default n8n credentials and use secure authentication

## Next Steps

- Set up n8n workflow for AI script refactoring
- Configure Slack webhook for failure notifications
- Run test suite: `docker-compose exec laravel-app php artisan test`
- Review application logs for any issues
