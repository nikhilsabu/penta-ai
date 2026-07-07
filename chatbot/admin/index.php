<?php

declare(strict_types=1);

require_once __DIR__ . '/../php/config.php';

if (!isAdminAuthenticated()) {
    header('Location: login.php');
    exit;
}
?><!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Pentame AI Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
  <link href="admin.css" rel="stylesheet" />
</head>
<body class="admin-body">
  <div class="admin-shell">
    <header class="admin-topbar">
      <div class="admin-brand">Pentame AI Dashboard</div>
      <div class="admin-topbar-actions">
        <button class="admin-icon-btn" type="button" aria-label="Notifications">
          <i class="bi bi-bell"></i>
        </button>
        <button class="admin-icon-btn" type="button" id="themeSwitch" aria-label="Toggle theme">
          <i class="bi bi-gear"></i>
        </button>
        <div class="admin-profile">
          <div class="admin-avatar" aria-hidden="true">PA</div>
          <div class="admin-profile-meta">
            <div class="admin-profile-name">Pentame Admin</div>
            <div class="admin-profile-email">admin@pentame.com</div>
          </div>
        </div>
        <a class="admin-btn admin-btn-secondary" href="logout.php">Logout</a>
      </div>
    </header>

    <main class="admin-main">
      <div class="admin-toolbar">
        <ul class="nav admin-nav-pills" id="adminTabs" role="tablist">
          <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabChats" type="button">Chat History</button></li>
          <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabLeads" type="button">Leads</button></li>
          <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabDocs" type="button">Documents</button></li>
          <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabFaq" type="button">FAQs</button></li>
          <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabSettings" type="button">Settings</button></li>
        </ul>
        <div class="admin-toolbar-actions">
          <button class="admin-btn admin-btn-success d-none" id="exportLeads" type="button">
            <i class="bi bi-download me-1"></i> Export Leads
          </button>
        </div>
      </div>

      <div class="admin-stats">
        <div class="admin-stat-card">
          <div class="admin-stat-value is-green" id="statConversations">0</div>
          <div class="admin-stat-label">Conversations</div>
        </div>
        <div class="admin-stat-card">
          <div class="admin-stat-value is-blue" id="statLeads">0</div>
          <div class="admin-stat-label">Total Leads</div>
        </div>
        <div class="admin-stat-card">
          <div class="admin-stat-value is-gray" id="statDocs">0</div>
          <div class="admin-stat-label">Documents</div>
        </div>
        <div class="admin-stat-card">
          <div class="admin-stat-value is-green" id="statToday">0</div>
          <div class="admin-stat-label">Today Conversations</div>
        </div>
      </div>

      <div class="tab-content">
        <section class="tab-pane fade show active" id="tabChats">
          <div class="admin-panel">
            <div class="admin-panel-head">
              <h2 class="admin-panel-title">Conversation Logs</h2>
              <div class="admin-search">
                <input id="searchChats" class="form-control" type="search" placeholder="Search conversations..." />
                <button class="admin-btn admin-btn-primary" id="btnSearchChats" type="button">Search</button>
              </div>
            </div>
            <div class="admin-table-wrap">
              <table class="admin-table" id="chatTable">
                <thead>
                  <tr>
                    <th>Conversation</th>
                    <th>Last Message</th>
                    <th>Messages</th>
                    <th>Status</th>
                    <th>Last Active</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </section>

        <section class="tab-pane fade" id="tabLeads">
          <div class="admin-panel">
            <div class="admin-panel-head">
              <h2 class="admin-panel-title">Captured Leads</h2>
              <button class="admin-btn admin-btn-success" id="exportLeadsTab" type="button">
                <i class="bi bi-download me-1"></i> Export to CSV
              </button>
            </div>
            <div class="admin-table-wrap">
              <table class="admin-table" id="leadTable">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Company</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Project</th>
                    <th>Budget</th>
                    <th>Timeline</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </section>

        <section class="tab-pane fade" id="tabDocs">
          <div class="admin-panel">
            <div class="admin-panel-head">
              <h2 class="admin-panel-title">Upload Knowledge Documents</h2>
            </div>
            <div class="admin-panel-body">
              <form id="docUploadForm" class="admin-form row g-3">
                <div class="col-md-7">
                  <label class="form-label" for="adminDocFile">File</label>
                  <input type="file" id="adminDocFile" class="form-control" accept=".pdf,.txt,.md" required />
                </div>
                <div class="col-md-3">
                  <label class="form-label" for="docCategory">Category</label>
                  <input type="text" id="docCategory" class="form-control" value="knowledge-base" />
                </div>
                <div class="col-md-2 d-flex align-items-end">
                  <button class="admin-btn admin-btn-primary w-100" type="submit">Upload</button>
                </div>
              </form>
              <p id="docStatus" class="admin-status-text mt-3 mb-0"></p>
            </div>
          </div>
        </section>

        <section class="tab-pane fade" id="tabFaq">
          <div class="admin-panel">
            <div class="admin-panel-head">
              <h2 class="admin-panel-title">FAQ Management</h2>
            </div>
            <div class="admin-panel-body">
              <form id="faqForm" class="admin-form">
                <div class="mb-3">
                  <label class="form-label" for="faqQuestion">Question</label>
                  <input id="faqQuestion" class="form-control" placeholder="Enter FAQ question" required />
                </div>
                <div class="mb-3">
                  <label class="form-label" for="faqAnswer">Answer</label>
                  <textarea id="faqAnswer" class="form-control" rows="4" placeholder="Enter FAQ answer" required></textarea>
                </div>
                <button class="admin-btn admin-btn-primary" type="submit">Save FAQ</button>
              </form>
              <ul id="faqList" class="admin-faq-list"></ul>
            </div>
          </div>
        </section>

        <section class="tab-pane fade" id="tabSettings">
          <div class="admin-panel">
            <div class="admin-panel-head">
              <h2 class="admin-panel-title">Chatbot Settings</h2>
            </div>
            <div class="admin-panel-body">
              <form id="settingsForm" class="admin-form">
                <div class="mb-3">
                  <label class="form-label" for="systemPrompt">System Prompt</label>
                  <textarea id="systemPrompt" class="form-control" rows="6"></textarea>
                </div>
                <div class="form-check form-switch mb-3">
                  <input class="form-check-input" type="checkbox" id="chatEnabled">
                  <label class="form-check-label" for="chatEnabled">Enable chatbot</label>
                </div>
                <div class="mb-3">
                  <label class="form-label" for="salesEmail">Sales Email</label>
                  <input type="email" id="salesEmail" class="form-control" />
                </div>
                <button class="admin-btn admin-btn-primary" type="submit">Update Settings</button>
              </form>
            </div>
          </div>
        </section>
      </div>
    </main>
  </div>

  <div class="modal fade conversation-modal" id="conversationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <div>
            <h5 class="modal-title mb-1">Conversation History</h5>
            <div class="admin-cell-sub" id="modalSessionId"></div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="conversation-meta" id="conversationMeta"></div>
        <div class="modal-body p-0">
          <div class="conversation-thread" id="conversationThread"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="admin-btn admin-btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="button" class="admin-btn admin-btn-danger" id="modalDeleteBtn">Remove Conversation</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const api = {
      async post(action, payload = {}) {
        const res = await fetch('api.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action, ...payload })
        });
        if (!res.ok) throw new Error('Request failed');
        return res.json();
      }
    };

    const escapeHtml = (value) => {
      const div = document.createElement('div');
      div.textContent = value ?? '';
      return div.innerHTML;
    };

    const formatRoleBadge = (role) => {
      const isUser = role === 'user';
      const cls = isUser ? 'is-user' : 'is-assistant';
      const label = isUser ? 'User' : 'Assistant';
      return `<span class="admin-status ${cls}"><span class="admin-status-dot"></span>${label}</span>`;
    };

    const formatStatusBadge = (status) => {
      const map = {
        active: { cls: 'is-active', label: 'Active' },
        idle: { cls: 'is-idle', label: 'Idle' },
        error: { cls: 'is-error', label: 'Error' },
        lead: { cls: 'is-lead', label: 'Lead Captured' },
      };
      const item = map[status] || map.idle;
      return `<span class="admin-status ${item.cls}"><span class="admin-status-dot"></span>${item.label}</span>`;
    };

    const truncateSession = (id) => {
      const text = id || '';
      return text.length > 22 ? `${text.slice(0, 22)}…` : text;
    };

    const truncateText = (text, max = 120) => {
      const value = text || '';
      return value.length > max ? `${value.slice(0, max)}…` : value;
    };

    let activeSessionId = null;
    const conversationModal = new bootstrap.Modal(document.getElementById('conversationModal'));

    async function loadAnalytics() {
      const data = await api.post('analytics');
      document.getElementById('statConversations').textContent = data.total_conversations || 0;
      document.getElementById('statLeads').textContent = data.total_leads || 0;
      document.getElementById('statDocs').textContent = data.total_documents || 0;
      document.getElementById('statToday').textContent = data.today_sessions || 0;
    }

    async function loadConversations(q = '') {
      const data = await api.post('conversations', { q });
      const tbody = document.querySelector('#chatTable tbody');
      tbody.innerHTML = '';

      if (!data.rows.length) {
        tbody.innerHTML = '<tr><td colspan="6"><div class="admin-empty">No conversations found.</div></td></tr>';
        return;
      }

      data.rows.forEach((row) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>
            <div class="admin-cell-primary">${escapeHtml(truncateSession(row.session_id))}</div>
            <div class="admin-cell-sub">Started ${escapeHtml(row.started_at || '—')}</div>
          </td>
          <td><div class="admin-cell-message">${escapeHtml(truncateText(row.last_message))}</div></td>
          <td>${escapeHtml(String(row.message_count || 0))}</td>
          <td>${formatStatusBadge(row.status)}</td>
          <td>${escapeHtml(row.last_active || '—')}</td>
          <td>
            <div class="admin-actions">
              <button class="admin-icon-btn btn-view" type="button" title="View history" aria-label="View history">
                <i class="bi bi-eye"></i>
              </button>
              <button class="admin-icon-btn is-danger btn-delete" type="button" title="Remove conversation" aria-label="Remove conversation">
                <i class="bi bi-trash"></i>
              </button>
            </div>
          </td>`;

        tr.querySelector('.btn-view').addEventListener('click', () => viewConversation(row.session_id));
        tr.querySelector('.btn-delete').addEventListener('click', () => deleteConversation(row.session_id));
        tbody.appendChild(tr);
      });
    }

    function renderConversationThread(messages) {
      const thread = document.getElementById('conversationThread');
      thread.innerHTML = '';

      messages.forEach((msg) => {
        const bubble = document.createElement('div');
        const role = msg.role === 'user' ? 'user' : (msg.role === 'system' ? 'system' : 'assistant');
        bubble.className = `thread-msg is-${role}`;
        bubble.innerHTML = `${escapeHtml(msg.content)}<span class="thread-msg-meta">${escapeHtml(msg.created_at)} · ${escapeHtml(msg.role)}</span>`;
        thread.appendChild(bubble);
      });

      thread.scrollTop = thread.scrollHeight;
    }

    async function viewConversation(sessionId) {
      if (!sessionId) return;
      activeSessionId = sessionId;

      const data = await api.post('conversation-detail', { session_id: sessionId });
      document.getElementById('modalSessionId').textContent = data.session_id;
      document.getElementById('conversationMeta').innerHTML = `
        <span><strong>Status:</strong> ${formatStatusBadge(data.status)}</span>
        <span><strong>Messages:</strong> ${escapeHtml(String(data.message_count || 0))}</span>
        <span><strong>Started:</strong> ${escapeHtml(data.started_at || '—')}</span>
        <span><strong>Last active:</strong> ${escapeHtml(data.last_active || '—')}</span>
        ${data.has_lead && data.lead ? `<span><strong>Lead:</strong> ${escapeHtml(data.lead.name)} (${escapeHtml(data.lead.email)})</span>` : ''}
      `;

      renderConversationThread(data.messages || []);
      conversationModal.show();
    }

    async function deleteConversation(sessionId) {
      if (!sessionId) return;
      const confirmed = confirm('Remove this conversation and any linked lead data?');
      if (!confirmed) return;

      await api.post('delete-conversation', { session_id: sessionId });

      if (activeSessionId === sessionId) {
        conversationModal.hide();
        activeSessionId = null;
      }

      await Promise.all([loadConversations(document.getElementById('searchChats').value.trim()), loadAnalytics()]);
    }

    async function loadLeads() {
      const data = await api.post('leads');
      const tbody = document.querySelector('#leadTable tbody');
      tbody.innerHTML = '';
      data.rows.forEach((row) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td><div class="admin-cell-primary">${escapeHtml(row.name)}</div></td>
          <td>${escapeHtml(row.company_name)}</td>
          <td>${escapeHtml(row.email)}</td>
          <td>${escapeHtml(row.phone)}</td>
          <td>${escapeHtml(row.project_type)}</td>
          <td>${escapeHtml(row.estimated_budget)}</td>
          <td>${escapeHtml(row.timeline)}</td>`;
        tbody.appendChild(tr);
      });
    }

    async function loadFaqs() {
      const data = await api.post('faqs');
      const list = document.getElementById('faqList');
      list.innerHTML = '';
      data.rows.forEach((row) => {
        const li = document.createElement('li');
        li.className = 'admin-faq-item';
        li.innerHTML = `<strong>${escapeHtml(row.question)}</strong>${escapeHtml(row.answer)}`;
        list.appendChild(li);
      });
    }

    async function loadSettings() {
      const data = await api.post('settings-get');
      document.getElementById('systemPrompt').value = data.system_prompt || '';
      document.getElementById('chatEnabled').checked = Number(data.chatbot_enabled) === 1;
      document.getElementById('salesEmail').value = data.sales_email || '';
    }

    document.getElementById('btnSearchChats').addEventListener('click', () => {
      loadConversations(document.getElementById('searchChats').value.trim());
    });

    document.getElementById('searchChats').addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        loadConversations(e.target.value.trim());
      }
    });

    document.getElementById('modalDeleteBtn').addEventListener('click', () => {
      if (activeSessionId) {
        deleteConversation(activeSessionId);
      }
    });

    const exportLeads = () => {
      window.location.href = 'api.php?export=leads';
    };

    document.getElementById('exportLeadsTab').addEventListener('click', exportLeads);

    document.getElementById('docUploadForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const file = document.getElementById('adminDocFile').files[0];
      if (!file) return;
      const form = new FormData();
      form.append('file', file);
      form.append('category', document.getElementById('docCategory').value || 'knowledge-base');
      form.append('session_id', 'admin_upload');
      form.append('api_key', <?= json_encode(CHATBOT_API_KEY, JSON_UNESCAPED_SLASHES) ?>);

      const res = await fetch('../php/upload-document.php', { method: 'POST', body: form });
      const json = await res.json();
      document.getElementById('docStatus').textContent = json.message || 'Uploaded';
      loadAnalytics();
    });

    document.getElementById('faqForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      await api.post('save-faq', {
        question: document.getElementById('faqQuestion').value,
        answer: document.getElementById('faqAnswer').value
      });
      document.getElementById('faqQuestion').value = '';
      document.getElementById('faqAnswer').value = '';
      loadFaqs();
    });

    document.getElementById('settingsForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      await api.post('settings-update', {
        updates: {
          system_prompt: document.getElementById('systemPrompt').value,
          chatbot_enabled: document.getElementById('chatEnabled').checked ? '1' : '0',
          sales_email: document.getElementById('salesEmail').value
        }
      });
      alert('Settings updated');
    });

    document.getElementById('themeSwitch').addEventListener('click', () => {
      const root = document.documentElement;
      root.setAttribute('data-bs-theme', root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark');
    });

    Promise.all([loadAnalytics(), loadConversations(), loadLeads(), loadFaqs(), loadSettings()]);
  </script>
</body>
</html>
