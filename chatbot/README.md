# Pentame AI Chatbot

Production-ready floating AI chatbot built with HTML5, Bootstrap 5, SCSS, Vanilla JS, PHP, and MySQL.

## Features Included

- Modern floating chatbot with animated pulse button
- Glassmorphism chat window with smooth open/close animation
- Dark and light mode toggle
- Welcome message and quick actions
- Suggested responses
- Markdown rendering with syntax highlighting
- Message timestamps, copy-to-clipboard, and auto-scroll
- Optional file upload flow
- Lead capture flow for pricing/project intent
- OpenAI GPT integration via PHP backend
- RAG context injection using database knowledge chunks and uploaded docs
- Admin panel for analytics, conversations, leads, FAQ management, uploads, settings
- CSV export for leads (Excel-compatible)

## Setup

1. Create database and tables:
   - Import `database.sql` into MySQL.

2. Configure backend:
   - Copy `.env.example` to `.env`.
   - Update `.env` values:
     - `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
     - `OPENAI_API_KEY`
     - `OPENAI_MODEL`
   - `php/config.php` now loads variables from `.env` automatically.

3. Ensure write permission:
   - `uploads/` must be writable by web server.

4. Open chatbot UI:
   - `/chatbot/index.html`

5. Open admin panel:
   - `/chatbot/admin/index.php`

## Important Notes

- PDF upload is supported and stored in DB; automatic text chunk extraction is currently enabled for `.txt` and `.md` files.
- Admin panel requires login. Set `ADMIN_PASSWORD` in `chatbot/.env`.
- The chatbot widget uses a centralized API at `php/api.php` secured with `CHATBOT_API_KEY`.
- Copy `.env.example` to `.env` and configure `OPENAI_API_KEY`, `CHATBOT_API_KEY`, and `ADMIN_PASSWORD`.
- Enable sales lead email by setting `send_lead_email` to `1` in `chatbot_settings`.

## API Endpoints

- `php/api.php` (central router — use `action` in JSON body)
  - `send-message`
  - `chat-history`
  - `save-lead`
  - `settings` (read-only; updates require admin)
- `php/upload-document.php` (multipart upload)
- `php/bootstrap.js.php` (injects API config into the widget)
