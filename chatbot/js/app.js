document.addEventListener("DOMContentLoaded", () => {
  const chatToggle = document.getElementById("chatToggle");
  const chatWidget = document.getElementById("chatWidget");
  const chatMinimize = document.getElementById("chatMinimize");
  const chatTheme = document.getElementById("chatTheme");
  const fileBtn = document.getElementById("fileBtn");
  const chatEmptyState = document.getElementById("chatEmptyState");
  const chatMessages = document.getElementById("chatMessages");
  const chatForm = document.getElementById("chatForm");
  const chatInput = document.getElementById("chatInput");
  const chatSuggestionsWrap = document.querySelector(".chat-suggestions-wrap");
  const quickActions = document.getElementById("quickActions");
  const suggestedResponses = document.getElementById("suggestedResponses");
  const fileInput = document.getElementById("fileInput");
  const voiceBtn = document.getElementById("voiceBtn");

  const api = new window.ChatApi("php");
  let history = [];
  let leadMode = false;
  let leadStep = 0;
  const leadFields = [
    { key: "name", label: "Your Name" },
    { key: "company_name", label: "Company Name" },
    { key: "email", label: "Email" },
    { key: "phone", label: "Phone Number" },
    { key: "project_type", label: "Project Type" },
    { key: "estimated_budget", label: "Estimated Budget" },
    { key: "timeline", label: "Timeline" },
    { key: "project_description", label: "Project Description" },
  ];
  const leadDraft = {};

  const quickActionItems = [
    "Build a Website",
    "Ecommerce Store",
    "SEO Services",
    "Digital Marketing",
    "Mobile App",
    "AI Solutions",
    "Portfolio",
    "Pricing",
    "Request a Quote",
    "Book a Meeting",
    "Contact Us",
  ];

  const suggestedItems = [
    "Tell me about your services",
    "Show Portfolio",
    "Pricing",
    "Book Consultation",
    "Talk to Sales",
    "Website Cost",
    "SEO Packages",
    "AI Development",
  ];

  marked.setOptions({
    breaks: true,
    gfm: true,
    highlight(code, language) {
      if (language && hljs.getLanguage(language)) {
        return hljs.highlight(code, { language }).value;
      }
      return hljs.highlightAuto(code).value;
    },
  });

  const toggleChat = (show) => {
    if (show) {
      chatWidget.hidden = false;
      chatWidget.classList.remove("closing");
      chatWidget.classList.add("opening");
      chatToggle.classList.add("is-open");
      chatToggle.setAttribute("aria-expanded", "true");
      chatInput.focus();
    } else {
      chatWidget.classList.add("closing");
      chatToggle.classList.remove("is-open");
      chatToggle.setAttribute("aria-expanded", "false");
      setTimeout(() => {
        chatWidget.hidden = true;
        chatWidget.classList.remove("closing", "opening");
      }, 240);
    }
  };

  const syncEmptyState = () => {
    if (!chatEmptyState) return;
    const hasMessages = chatMessages.children.length > 0;
    chatEmptyState.style.display = hasMessages ? "none" : "grid";
  };

  const syncSuggestions = () => {
    if (!chatSuggestionsWrap || !quickActions || !suggestedResponses) return;

    const hasMessages = chatMessages.children.length > 0;
    const hasSuggestions = suggestedResponses.children.length > 0;

    if (!hasMessages) {
      quickActions.hidden = false;
      suggestedResponses.hidden = true;
      chatSuggestionsWrap.hidden = false;
      return;
    }

    quickActions.hidden = true;
    suggestedResponses.hidden = !hasSuggestions;
    chatSuggestionsWrap.hidden = !hasSuggestions;
  };

  const formatTime = (d = new Date()) =>
    d.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });

  const sanitizeOutgoing = (text) => text.trim().slice(0, 2000);

  const escapeHtml = (value) => {
    const div = document.createElement("div");
    div.textContent = value ?? "";
    return div.innerHTML;
  };

  const withLinkTargets = (html) =>
    html.replace(/<a /g, '<a target="_blank" rel="noopener noreferrer" ');

  const renderAssistantHtml = (text) => {
    const parsed = marked.parse(text || "");
    if (window.DOMPurify) {
      return withLinkTargets(DOMPurify.sanitize(parsed));
    }
    return withLinkTargets(parsed);
  };

  const addMessage = (sender, text, options = {}) => {
    const message = document.createElement("article");
    message.className = `message ${sender === "user" ? "message-user" : "message-ai"}`;

    const content = document.createElement("div");
    if (sender === "assistant") {
      content.innerHTML = renderAssistantHtml(text);
    } else {
      content.textContent = text;
    }

    const meta = document.createElement("div");
    meta.className = "meta";

    if (sender === "assistant") {
      const makeActionBtn = (icon, label) => {
        const btn = document.createElement("button");
        btn.className = "message-action-btn";
        btn.type = "button";
        btn.setAttribute("aria-label", label);
        btn.innerHTML = `<i class="bi ${icon}"></i>`;
        return btn;
      };

      const likeBtn = makeActionBtn("bi-hand-thumbs-up", "Like reply");
      const dislikeBtn = makeActionBtn("bi-hand-thumbs-down", "Dislike reply");
      likeBtn.addEventListener("click", () => {
        likeBtn.classList.toggle("active");
        dislikeBtn.classList.remove("active");
      });
      dislikeBtn.addEventListener("click", () => {
        dislikeBtn.classList.toggle("active");
        likeBtn.classList.remove("active");
      });

      const copyBtn = document.createElement("button");
      copyBtn.className = "message-action-btn";
      copyBtn.type = "button";
      copyBtn.innerHTML = '<i class="bi bi-clipboard"></i>';
      copyBtn.setAttribute("aria-label", "Copy message");
      copyBtn.addEventListener("click", async () => {
        await navigator.clipboard.writeText(text);
        copyBtn.innerHTML = '<i class="bi bi-check2"></i>';
        setTimeout(() => {
          copyBtn.innerHTML = '<i class="bi bi-clipboard"></i>';
        }, 900);
      });
      meta.append(likeBtn, dislikeBtn, copyBtn);
    } else {
      meta.textContent = formatTime();
    }

    message.append(content, meta);
    chatMessages.appendChild(message);
    syncEmptyState();
    syncSuggestions();

    if (options.fade !== false) {
      window.ChatAnimation.fadeIn(message);
    }

    chatMessages
      .querySelectorAll("pre code")
      .forEach((el) => hljs.highlightElement(el));
    chatMessages.scrollTop = chatMessages.scrollHeight;
  };

  const addActionChips = (container, items) => {
    container.innerHTML = "";
    items.forEach((item) => {
      const btn = document.createElement("button");
      btn.className = "chip-btn";
      btn.type = "button";
      btn.textContent = item;
      btn.addEventListener("click", () => handleOutgoing(item));
      container.appendChild(btn);
    });
    syncSuggestions();
  };

  const askNextLeadQuestion = () => {
    if (leadStep >= leadFields.length) {
      submitLead();
      return;
    }
    addMessage("assistant", `Please share your ${leadFields[leadStep].label}.`);
  };

  const submitLead = async () => {
    try {
      const result = await api.saveLead(leadDraft);
      addMessage(
        "assistant",
        result.message || "Thanks. Our sales team will contact you shortly.",
      );
      leadMode = false;
      leadStep = 0;
      Object.keys(leadDraft).forEach((k) => delete leadDraft[k]);
    } catch (error) {
      addMessage(
        "assistant",
        "I could not save your details right now. Please contact Pentame directly via the Contact page.",
      );
    }
  };

  const sendToAssistant = async (input) => {
    const typingNode = window.ChatAnimation.showTyping(chatMessages);

    try {
      const response = await api.sendMessage(input, history, {
        page: window.location.pathname,
        user_agent: navigator.userAgent,
      });

      window.ChatAnimation.removeTyping(chatMessages);
      const answer =
        response.reply || "Please contact Pentame for more details.";
      addMessage("assistant", answer);

      history.push({ role: "user", content: input });
      history.push({ role: "assistant", content: answer });

      if (response.suggested_responses?.length) {
        addActionChips(suggestedResponses, response.suggested_responses);
      }

      if (response.lead_capture === true) {
        leadMode = true;
        leadStep = 0;
        addMessage(
          "assistant",
          "To guide you properly, may I collect a few project details?",
        );
        askNextLeadQuestion();
      }
    } catch (error) {
      window.ChatAnimation.removeTyping(chatMessages);
      typingNode?.remove();
      const errorMessage = error?.message
        ? `Connection issue: ${error.message}`
        : "I am having trouble connecting right now. Please try again in a moment.";
      addMessage("assistant", errorMessage);
      console.error(error);
    }
  };

  const handleOutgoing = async (rawText) => {
    const message = sanitizeOutgoing(rawText || "");
    if (!message) return;

    addMessage("user", message);

    if (leadMode) {
      const field = leadFields[leadStep];
      leadDraft[field.key] = message;
      leadStep += 1;
      askNextLeadQuestion();
      return;
    }

    await sendToAssistant(message);
  };

  const restoreTheme = () => {
    const storedTheme = localStorage.getItem("pentame_theme") || "light";
    document.documentElement.setAttribute("data-bs-theme", storedTheme);
  };

  const toggleTheme = () => {
    const current =
      document.documentElement.getAttribute("data-bs-theme") || "light";
    const next = current === "dark" ? "light" : "dark";
    document.documentElement.setAttribute("data-bs-theme", next);
    localStorage.setItem("pentame_theme", next);
  };

  const init = async () => {
    restoreTheme();
    addActionChips(quickActions, quickActionItems);
    suggestedResponses.innerHTML = "";
    syncSuggestions();

    try {
      const settings = await api.getSettings();
      if (settings.chatbot_enabled === 0) {
        chatToggle.hidden = true;
        return;
      }
    } catch (e) {
      console.warn("Could not load settings.", e);
    }

    syncEmptyState();

    try {
      const savedHistory = await api.getHistory();
      if (Array.isArray(savedHistory.history)) {
        savedHistory.history.slice(-10).forEach((item) => {
          addMessage(
            item.role === "user" ? "user" : "assistant",
            item.content,
            { fade: false },
          );
          history.push({ role: item.role, content: item.content });
        });
      }
    } catch (e) {
      console.warn("History unavailable.");
    }

    syncSuggestions();
  };

  chatToggle.addEventListener("click", () => toggleChat(chatWidget.hidden));
  chatMinimize.addEventListener("click", () => toggleChat(false));
  chatTheme?.addEventListener("click", toggleTheme);
  fileBtn?.addEventListener("click", () => fileInput.click());

  chatForm.addEventListener("submit", async (event) => {
    event.preventDefault();
    const value = chatInput.value;
    chatInput.value = "";
    await handleOutgoing(value);
  });

  fileInput.addEventListener("change", async (event) => {
    const file = event.target.files?.[0];
    if (!file) return;

    addMessage("user", `Uploaded file: ${file.name}`);
    try {
      const uploadResponse = await api.uploadDocument(file, "visitor-upload");
      addMessage(
        "assistant",
        uploadResponse.message || "File uploaded successfully.",
      );
    } catch (error) {
      addMessage("assistant", "File upload failed. Please try again.");
    } finally {
      fileInput.value = "";
    }
  });

  voiceBtn.addEventListener("click", () => {
    addMessage("assistant", "Voice input is planned for a future release.");
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && !chatWidget.hidden) {
      toggleChat(false);
    }
    if (event.altKey && event.key.toLowerCase() === "h") {
      document.body.classList.toggle("high-contrast");
    }
  });

  setTimeout(() => {
    init();
  }, 250);
});
