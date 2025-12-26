# Local LLM Chat for Nextcloud

A powerful Nextcloud app that integrates local Large Language Models (LLMs) directly into your Nextcloud instance for AI-powered assistance with enterprise-grade security.

<img alt="Local LLM Chat Interface" src="https://github.com/user-attachments/assets/71405cb0-9721-44c7-8b0a-cfe1c2fe3f62" />

## Features

### 🤖 Local AI Integration
- **Privacy-First**: All data stays on your server—no cloud APIs required
- **Multiple LLM Support**: Works with Ollama, LM Studio, vLLM, and OpenAI-compatible endpoints
- **Persistent Conversations**: Chat history maintained and stored in Nextcloud database
- **Real-time Chat**: Interactive chat interface integrated into Nextcloud navigation

### 🔒 Enterprise-Grade Security
- **API Token Encryption**: All API tokens encrypted at rest using AES-256
- **CSRF Protection**: Full Cross-Site Request Forgery protection on all endpoints
- **Rate Limiting**: Configurable rate limits (default: 20 requests/minute per user)
- **Input Validation**: Comprehensive validation and sanitization of all user inputs
- **User-Level Security**: Users can only access their own conversations and configurations
- **Audit Logging**: Complete logging of all security events and user actions
- **Error Sanitization**: Safe error messages that don't leak system information

### 🎯 Core Functionality
- **Configuration Management**: Multiple LLM configurations (system-wide or user-specific)
- **Conversation History**: Track and manage all AI conversations with timestamps
- **Message Storage**: All messages stored in database with role tracking (user/assistant)
- **Test Connection**: Built-in tool to verify LLM server connectivity
- **Flexible Settings**: Configure temperature, max tokens, system prompts, and more
- **Model Selection**: Choose different models for different conversations

### 💼 Business Use Cases
- Product description generation
- Email drafting assistance
- Document summarization
- Data analysis and insights
- Customer service support
- Report generation
- General productivity automation

## Requirements

- **Nextcloud**: Version 28, 29, or 30
- **PHP**: 8.1 or higher
- **Node.js**: 20.x or higher (for building frontend)
- **npm**: 10.x or higher
- **Local LLM Server**: One of:
  - Ollama (recommended)
  - LM Studio
  - vLLM
  - Any OpenAI-compatible API endpoint

## Installation

### 1. Set Up Your Local LLM Server

#### Option A: Ollama (Recommended)
```bash
# Install Ollama from https://ollama.ai
# On macOS/Linux:
curl -fsSL https://ollama.ai/install.sh | sh

# Pull a model
ollama pull llama3.2

# Verify it's running (usually starts automatically)
ollama list
```

#### Option B: LM Studio
1. Download from [lmstudio.ai](https://lmstudio.ai)
2. Install and open the application
3. Download a model from the model library
4. Go to "Local Server" tab and start the server (default: `http://localhost:1234`)

### 2. Install the Nextcloud App

```bash
# Navigate to your Nextcloud apps directory
cd /var/www/nextcloud/apps  # Adjust path as needed

# Clone the repository
git clone https://github.com/markusbegerow/local-llm-nextcloud.git

# Or extract from archive
# unzip local-llm-nextcloud.zip
```

### 3. Build the Frontend

```bash
# Navigate to the app directory
cd local-llm-nextcloud

# Install dependencies
npm install

# Build for production
npm run build
```

### 4. Set Permissions

```bash
# Make sure the web server can read the files
# Adjust user/group as needed for your system
sudo chown -R www-data:www-data /var/www/nextcloud/apps/local-llm-nextcloud
sudo chmod -R 755 /var/www/nextcloud/apps/local-llm-nextcloud
```

### 5. Enable the App in Nextcloud

1. Log in to Nextcloud as an administrator
2. Click on your profile icon (top right)
3. Select **Apps**
4. Search for "Local LLM" or click **Your apps**
5. Find "Local LLM Chat for Nextcloud" in the list
6. Click **Enable**

The database tables will be created automatically on first activation.

## Configuration

### Initial Setup

1. Click **Local LLM** in the Nextcloud top navigation menu
2. Click the **Settings** icon (gear) in the bottom left sidebar
3. Click **Add Configuration**
4. Fill in the configuration details:

   **For Ollama:**
   - **Name**: `Ollama Llama 3.2`
   - **API URL**: `http://localhost:11434/v1/chat/completions`
   - **API Token**: `ollama`
   - **Model Name**: `llama3.2`
   - **Temperature**: `0.7` (0.0-2.0, controls randomness)
   - **Max Tokens**: `2048` (maximum response length)
   - **System Prompt**: (optional) Define AI behavior

   **For LM Studio:**
   - **Name**: `LM Studio`
   - **API URL**: `http://localhost:1234/v1/chat/completions`
   - **API Token**: `lm-studio`
   - **Model Name**: The model you loaded in LM Studio
   - **Temperature**: `0.7`
   - **Max Tokens**: `2048`

5. Click **Save**
6. Click the **Test** button to verify connectivity
7. If successful, you'll see "Connection successful!"

### Multiple Configurations

You can create multiple LLM configurations for:
- Different models (coding vs. general purpose)
- Different servers (local vs. remote)
- Different temperature settings for creativity vs. precision
- Testing vs. production environments

Set one as **Default** by clicking the star icon - it will be used automatically for new conversations.

## Usage

### Starting a Conversation

1. Go to **Local LLM** in the Nextcloud top menu
2. Click **New Conversation** in the left sidebar
3. Select your preferred model from the dropdown (if you have multiple)
4. Type your message in the text box
5. Press **Enter** or click **Send**
6. The AI will respond based on your configuration

### Managing Conversations

- View all your conversations in the left sidebar
- Click any conversation to view its full history
- Delete conversations using the trash icon in the conversation menu
- Conversations are automatically named based on the first message
- All messages include timestamps for reference

### Chat Features

- **Enter**: Send message
- **Shift+Enter**: New line in message (multi-line messages)
- Messages support formatting with line breaks
- Conversation history is maintained across sessions
- Each message shows sender (You/Assistant) and timestamp

## Architecture

### Frontend (Vue.js)

```
src/
├── App.vue                    # Main application component
├── main.js                    # Entry point
├── components/
│   ├── ChatWindow.vue         # Chat interface
│   └── ConfigSettings.vue     # Configuration management UI
└── services/
    └── api.js                 # API client for backend communication
```

### Backend (PHP)

```
lib/
├── AppInfo/
│   └── Application.php        # App registration
├── Controller/
│   ├── PageController.php     # Main page controller
│   ├── ChatController.php     # Chat API endpoints
│   └── ConfigController.php   # Configuration API endpoints
├── Db/
│   ├── LlmConfig.php          # LLM configuration entity
│   ├── LlmConfigMapper.php    # Configuration database operations
│   ├── Conversation.php       # Conversation entity
│   ├── ConversationMapper.php # Conversation database operations
│   ├── Message.php            # Message entity
│   └── MessageMapper.php      # Message database operations
├── Service/
│   ├── LlmService.php         # LLM API communication
│   ├── EncryptionService.php  # Token encryption/decryption
│   └── RateLimitService.php   # Rate limiting logic
└── Migration/
    └── Version1000Date*.php   # Database migrations
```

### Database Schema

**Tables:**
1. **`oc_localllm_configs`** - LLM server configurations
   - Stores API URLs, encrypted tokens, model settings
   - Supports multiple configs per user
   - Default config flagging

2. **`oc_localllm_conversations`** - User conversations
   - Links to config used
   - Conversation naming and metadata
   - User isolation

3. **`oc_localllm_messages`** - Chat messages
   - Stores all messages with role (user/assistant)
   - Message content and timestamps
   - Token usage tracking

### API Endpoints

#### Chat Endpoints
- `POST /apps/local-llm-nextcloud/api/chat` - Send a message
- `GET /apps/local-llm-nextcloud/api/conversations` - List user conversations
- `GET /apps/local-llm-nextcloud/api/conversations/{id}` - Get conversation details
- `GET /apps/local-llm-nextcloud/api/conversations/{id}/messages` - Get conversation messages
- `DELETE /apps/local-llm-nextcloud/api/conversations/{id}` - Delete conversation
- `POST /apps/local-llm-nextcloud/api/conversations/{id}/clear` - Clear all messages
- `PUT /apps/local-llm-nextcloud/api/conversations/{id}` - Update conversation name

#### Configuration Endpoints
- `GET /apps/local-llm-nextcloud/api/configs` - List configurations
- `POST /apps/local-llm-nextcloud/api/configs` - Create configuration
- `PUT /apps/local-llm-nextcloud/api/configs/{id}` - Update configuration
- `DELETE /apps/local-llm-nextcloud/api/configs/{id}` - Delete configuration
- `POST /apps/local-llm-nextcloud/api/configs/{id}/test` - Test connection
- `POST /apps/local-llm-nextcloud/api/configs/{id}/default` - Set as default

### Security

**Access Control**:
- Nextcloud authentication required for all endpoints
- User-level data isolation - users can only access their own data
- Database-enforced security via Nextcloud's mapper pattern
- No raw SQL queries (injection prevention)

**Data Protection**:
- API tokens encrypted at rest using AES-256 via Nextcloud's encryption service
- Encryption keys managed by Nextcloud
- Automatic encryption of new tokens
- CSRF protection on all endpoints

**Input Validation & Sanitization**:
- Maximum message length validation
- Type checking on all inputs
- Temperature and token range validation
- URL validation for API endpoints
- Safe error messages

**Rate Limiting**:
- Session-based rate limiting per user
- Default: 20 requests per minute
- Configurable in `RateLimitService.php`
- Prevents API abuse and DoS attacks

**Audit & Logging**:
- Comprehensive logging via Nextcloud's logger
- Security events logged
- API errors logged with context
- User actions tracked

## API Integration

### OpenAI-Compatible Format

The app uses the standard OpenAI API format for maximum compatibility:

```json
POST /v1/chat/completions
{
    "model": "llama3.2",
    "messages": [
        {"role": "system", "content": "You are a helpful assistant"},
        {"role": "user", "content": "Hello!"}
    ],
    "temperature": 0.7,
    "max_tokens": 2048
}
```

### Supported Endpoints

- **Ollama**: `http://localhost:11434/v1/chat/completions`
- **LM Studio**: `http://localhost:1234/v1/chat/completions`
- **vLLM**: `http://localhost:8000/v1/chat/completions`
- **Custom**: Any OpenAI-compatible endpoint

## Troubleshooting

### Connection Issues

**Error**: "Cannot connect to LLM server"

**Solution**:
```bash
# For Ollama - check if running
curl http://localhost:11434/api/tags

# For LM Studio - check if server is started
curl http://localhost:1234/v1/models

# Verify the service is running
ps aux | grep ollama
# or
ps aux | grep "LM Studio"
```

**Common causes:**
- LLM server not started
- Wrong port number in configuration
- Firewall blocking localhost connections
- Model not loaded in LM Studio

### Timeout Issues

**Error**: "Request timeout. The LLM took too long to respond."

**Solution**:
1. Increase **Request Timeout** in configuration (default: 120000ms)
2. Use a smaller/faster model
3. Reduce **Max Tokens** setting
4. Check system resources (CPU/RAM usage)

### API Format Issues

**Error**: "Unexpected API response format"

**Solution**:
- Verify your endpoint uses OpenAI-compatible format
- Check API documentation for your LLM server
- Test endpoint with curl:
```bash
curl -X POST http://localhost:11434/v1/chat/completions \
  -H "Content-Type: application/json" \
  -d '{
    "model": "llama3.2",
    "messages": [{"role": "user", "content": "Hello"}]
  }'
```

### Rate Limiting Issues

**Error**: "Too many requests. Please wait a moment and try again"

**Solution**:
1. Default limit is 20 messages per minute per user
2. Wait 60 seconds and try again
3. To adjust the limit, edit `lib/Service/RateLimitService.php`:
   ```php
   private const MAX_REQUESTS_PER_MINUTE = 50;  // Increase as needed
   ```
4. Restart web server after making changes

### Frontend Not Loading

**Error**: JavaScript errors or blank page

**Solution**:
```bash
# Clear JavaScript build and rebuild
cd /var/www/nextcloud/apps/local-llm-nextcloud
rm -rf js/
npm run build

# Clear Nextcloud cache
cd /var/www/nextcloud
php occ maintenance:mode --on
php occ app:disable local-llm-nextcloud
php occ app:enable local-llm-nextcloud
php occ maintenance:mode --off

# Clear browser cache (Ctrl+Shift+R or Cmd+Shift+R)
```

### Database Issues

**Error**: "Failed to load configurations" or migration errors

**Solution**:
```bash
# Check Nextcloud logs
tail -f /var/www/nextcloud/data/nextcloud.log

# Verify database tables exist
php occ db:list-tables | grep localllm

# Re-run migrations
php occ app:disable local-llm-nextcloud
php occ app:enable local-llm-nextcloud
```

## Development

### Building for Development

```bash
# Watch for changes and rebuild automatically
npm run watch

# Development build (faster, includes source maps)
npm run dev

# In another terminal, watch Nextcloud logs
tail -f /var/www/nextcloud/data/nextcloud.log
```

### Code Quality

```bash
# Lint JavaScript and Vue files
npm run lint

# Auto-fix linting issues
npm run lint:fix

# Lint CSS/SCSS
npm run stylelint

# Auto-fix style issues
npm run stylelint:fix
```

### Module Structure

```
local-llm-nextcloud/
├── appinfo/
│   ├── info.xml                 # App metadata
│   ├── routes.php               # URL routing configuration
│   └── database.xml             # Database schema definition
├── lib/
│   ├── AppInfo/
│   │   └── Application.php      # App registration
│   ├── Controller/              # HTTP controllers
│   ├── Db/                      # Database entities & mappers
│   ├── Service/                 # Business logic services
│   └── Migration/               # Database migrations
├── src/                         # Vue.js frontend source
│   ├── components/              # Vue components
│   ├── services/                # API services
│   ├── App.vue                  # Main app component
│   └── main.js                  # Entry point
├── js/                          # Built JavaScript bundle
├── css/                         # Stylesheets
├── templates/                   # PHP templates
├── img/                         # Images and icons
├── package.json                 # Node dependencies
├── webpack.config.js            # Build configuration
└── README.md                    # This file
```

### Adding New Features

Ideas for extending the app:

1. **Streaming Responses**: Implement Server-Sent Events (SSE) for real-time streaming
2. **File Upload**: Allow users to send files/documents to LLM for analysis
3. **RAG Integration**: Connect to Nextcloud Files for document search and context
4. **Multi-Modal**: Add image understanding capabilities
5. **Voice Input**: Integrate speech-to-text for voice messages
6. **Conversation Export**: Export conversations as PDF or Markdown
7. **Shared Conversations**: Allow conversation sharing between users
8. **Custom Prompts Library**: Save and reuse common system prompts

## Recommended Models

### For General Use
- **Llama 3.2 8B**: Fast, excellent all-around model
- **Mistral 7B**: Efficient and capable for most tasks
- **Phi-3 Mini**: Compact but powerful, good for resource-constrained systems
- **Gemma 7B**: Strong reasoning and instruction following

### For Coding Tasks
- **Llama 3.2 8B**: Good for general coding assistance
- **CodeLlama 13B**: Specialized for code generation and explanation
- **Qwen 2.5 Coder**: Excellent code understanding and generation
- **DeepSeek Coder**: Strong at algorithms and complex code analysis

### For Business/Writing
- **Llama 3.2**: Best all-around for business communications
- **Mistral 7B**: Professional writing and analysis
- **Zephyr 7B**: Good for conversational business tasks

### Model Size Considerations
- **Small models (3B-8B)**: Fast responses, lower resource usage, good for most tasks
- **Medium models (13B-14B)**: Better quality, still reasonable performance
- **Large models (30B+)**: Best quality, requires powerful hardware

## Security Best Practices

### ✅ Built-in Security Features

- **Data Privacy**: All data stays on your server - no external API calls (unless configured)
- **Encryption**: API tokens encrypted at rest using AES-256
- **Access Control**: User-level data isolation via Nextcloud authentication
- **CSRF Protection**: Full protection against cross-site request forgery
- **Rate Limiting**: Protection against API abuse (20 requests/min default)
- **Input Validation**: Comprehensive validation and sanitization
- **Audit Logging**: Complete logging of security events
- **No SQL Injection**: All queries use Nextcloud's QueryBuilder

### ⚠️ Production Deployment Checklist

**Before Going Live**:

1. **Network Security**:
   - ✅ Ensure LLM server is NOT exposed to the internet
   - ✅ Use firewall rules to restrict LLM server access to localhost
   - ✅ Run Nextcloud behind HTTPS with valid SSL certificates
   - ✅ Use a reverse proxy (nginx/Apache) for HTTPS termination
   - ✅ Consider VPN for remote LLM server access

2. **LLM Server Security**:
   - ✅ Keep LLM server software updated (Ollama, LM Studio, etc.)
   - ✅ Bind LLM server to localhost only (not 0.0.0.0)
   - ✅ Use authentication if your LLM server supports it
   - ✅ Monitor resource usage to prevent DoS

3. **Nextcloud Security**:
   - ✅ Keep Nextcloud updated to latest version
   - ✅ Enable two-factor authentication for admin users
   - ✅ Regular automated backups (at least daily)
   - ✅ Test backup restoration procedure
   - ✅ Review and restrict app permissions

4. **Database Security**:
   - ✅ Enable PostgreSQL/MySQL authentication
   - ✅ Restrict database access to localhost
   - ✅ Use strong database passwords
   - ✅ Regular database backups
   - ✅ Monitor database performance

5. **API Token Management**:
   - ✅ Use strong, unique API tokens for production
   - ✅ Rotate tokens periodically (every 90 days recommended)
   - ✅ Don't use default tokens like "ollama" in production
   - ✅ Never commit tokens to version control
   - ✅ Tokens are automatically encrypted by Nextcloud

6. **Monitoring & Logging**:
   - ✅ Monitor Nextcloud logs regularly
   - ✅ Set up alerts for critical errors
   - ✅ Review security events (failed access, rate limits)
   - ✅ Monitor system resources (CPU, RAM, disk)
   - ✅ Set up log rotation to prevent disk filling

7. **System Hardening**:
   - ✅ Keep OS and PHP updated
   - ✅ Run web server as non-root user
   - ✅ Configure proper file permissions
   - ✅ Disable unnecessary services
   - ✅ Enable SELinux/AppArmor if available

### 🔐 Compliance Considerations

- **GDPR**: User data is stored in your Nextcloud database - ensure proper data handling
- **Data Retention**: Implement conversation cleanup policies if required
- **User Privacy**: Users can delete their own conversations
- **Audit Trail**: All actions are logged for compliance auditing
- **Data Export**: Users can export their conversations (planned feature)

## License

This project is licensed under the **GPL-2.0** License - see the [LICENSE](LICENSE) file for details.

## Support

For issues and questions:
- 🐛 [Report bugs](https://github.com/markusbegerow/local-llm-nextcloud/issues)
- 💡 [Request features](https://github.com/markusbegerow/local-llm-nextcloud/issues)
- 📚 [Read the Installation Guide](INSTALLATION.md)
- 🔍 [Check Nextcloud community forums](https://help.nextcloud.com/)
- 📖 Review LLM server documentation (Ollama, LM Studio)

## Contributing

Contributions are welcome! Please:
1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Test thoroughly in a development environment
5. Commit your changes (`git commit -m 'Add amazing feature'`)
6. Push to the branch (`git push origin feature/amazing-feature`)
7. Submit a pull request

## Acknowledgments

- Ollama team for making local LLMs accessible and easy to use
- Nextcloud team for the excellent platform and development framework
- LM Studio for providing a user-friendly local inference platform
- The open-source LLM community for advancing local AI

## 🙋‍♂️ Get Involved

If you find this useful or have questions:
- ⭐ Star the repo if you find it useful!
- 🐛 [Report bugs](https://github.com/markusbegerow/local-llm-nextcloud/issues)
- 💡 [Request features](https://github.com/markusbegerow/local-llm-nextcloud/issues)
- 🤝 Contribute improvements via pull requests

## ☕ Support the Project

If you like this project, support further development:

<a href="https://www.linkedin.com/sharing/share-offsite/?url=https://github.com/markusbegerow/local-llm-nextcloud" target="_blank">
  <img src="https://img.shields.io/badge/💼-Share%20on%20LinkedIn-blue" />
</a>

[![Buy Me a Coffee](https://img.shields.io/badge/☕-Buy%20me%20a%20coffee-yellow)](https://paypal.me/MarkusBegerow?country.x=DE&locale.x=de_DE)

## 📬 Contact

- 🧑‍💻 [Markus Begerow](https://linkedin.com/in/markusbegerow)
- 💾 [GitHub](https://github.com/markusbegerow)
- ✉️ [Twitter](https://x.com/markusbegerow)

---

**Privacy Notice**: This app operates entirely locally by default. No data is sent to external servers unless you explicitly configure a remote API endpoint. All conversation data is stored in your Nextcloud database and never leaves your server.
