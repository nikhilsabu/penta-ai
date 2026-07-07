# Pentame AI Chatbot

Floating AI chatbot widget for Pentame with OpenAI integration, RAG, lead capture, and admin dashboard.

## Quick start

1. Import `chatbot/database.sql` into MySQL
2. Copy `chatbot/.env.example` to `chatbot/.env` and configure keys
3. Open `http://localhost/penta-ai/chatbot/index.html`
4. Admin panel: `http://localhost/penta-ai/chatbot/admin/`

See [chatbot/README.md](chatbot/README.md) for full setup and API details.

## GitHub Pages

Live demo: [https://nikhilsabu.github.io/penta-ai/](https://nikhilsabu.github.io/penta-ai/)

1. In repo **Settings → Pages**, set **Source** to **GitHub Actions**
2. Push to `master` — the workflow deploys automatically
3. GitHub Pages serves the static UI only; PHP/MySQL features need XAMPP or PHP hosting
