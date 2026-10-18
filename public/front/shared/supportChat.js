"use strict";

/*
 * Support ticket chat. Renders the conversation from #ppChatData, sends replies, and polls for new messages
 * so the other side's answer shows up without reloading. On the admin page there is also a solve / reopen button.
 */
document.addEventListener("DOMContentLoaded", () => {
  const chat = document.getElementById("ppChat");
  const dataNode = document.getElementById("ppChatData");
  if (!chat || !dataNode) return;

  const isAdmin = chat.dataset.admin === "1";
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || "";
  const box = document.getElementById("ppChatMessages");
  const form = document.getElementById("ppChatForm");
  const text = document.getElementById("ppChatText");
  const closedNote = document.getElementById("ppChatClosed");
  const statusBadge = document.getElementById("ppChatStatus");
  const resolveButton = document.getElementById("ppChatResolve");
  const statusLabels = { new: isAdmin ? "جديدة" : "قيد المتابعة", open: "قيد المعالجة", resolved: "تم الحل" };
  const POLL_MS = 8000;

  let ticket = JSON.parse(dataNode.textContent);
  let lastKey = "";

  function post(url, body) {
    return fetch(url, {
      method: "POST",
      headers: { Accept: "application/json", "Content-Type": "application/json", "X-CSRF-TOKEN": csrf },
      body: JSON.stringify(body),
    }).then((response) => response.json().then((data) => ({ ok: response.ok, data })));
  }

  function dayLabel(date) {
    const today = new Date();
    const yesterday = new Date();
    yesterday.setDate(today.getDate() - 1);
    if (date.toDateString() === today.toDateString()) return "اليوم";
    if (date.toDateString() === yesterday.toDateString()) return "أمس";
    return date.toLocaleDateString("ar", { day: "numeric", month: "long", year: "numeric" });
  }

  function render(next) {
    ticket = next;
    statusBadge.textContent = statusLabels[ticket.status] || "";
    statusBadge.className = "pp-chat__status is-" + ticket.status;

    if (resolveButton) {
      resolveButton.classList.toggle("is-reopen", ticket.closed);
      resolveButton.innerHTML = ticket.closed
        ? '<i class="bi bi-arrow-counterclockwise"></i> إعادة فتح'
        : '<i class="bi bi-check-circle"></i> تحديد كمحلولة';
    }
    // Nobody writes in a solved ticket: the admin reopens it first (the header button), the user opens a new one.
    form.hidden = ticket.closed;
    closedNote.hidden = !ticket.closed;

    const key = ticket.messages.map((message) => message.id).join(",");
    if (key === lastKey) return;
    lastKey = key;

    const nearBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 80;
    box.textContent = "";
    let lastDay = "";
    let lastSender = null;
    ticket.messages.forEach((message) => {
      const date = message.at ? new Date(message.at) : null;
      const day = date ? date.toDateString() : "";
      if (day && day !== lastDay) {
        const sep = document.createElement("div");
        sep.className = "pp-chat__day";
        sep.textContent = dayLabel(date);
        box.append(sep);
        lastDay = day;
        lastSender = null;
      }

      const mine = message.fromAdmin === isAdmin;
      const sender = message.fromAdmin ? "admin" : "user";
      const continued = lastSender === sender;
      lastSender = sender;

      const row = document.createElement("div");
      row.className = "pp-chat__row" + (mine ? " is-mine" : "") + (continued ? " is-cont" : " is-first");

      if (!mine) {
        const avatar = document.createElement("span");
        avatar.className = "pp-chat__avatar" + (message.fromAdmin ? " is-admin" : "") + (continued ? " is-ghost" : "");
        if (message.fromAdmin) avatar.innerHTML = '<i class="bi bi-headset"></i>';
        else avatar.textContent = (message.sender || "؟").trim().charAt(0);
        row.append(avatar);
      }

      const bubble = document.createElement("div");
      bubble.className = "pp-chat__bubble";
      if (!mine && !continued) {
        const who = document.createElement("span");
        who.className = "pp-chat__who";
        who.textContent = message.sender;
        bubble.append(who);
      }
      const body = document.createElement("p");
      // A long run of empty lines would make one message fill the screen.
      body.textContent = message.text.replace(/\n{3,}/g, "\n\n");
      const time = document.createElement("time");
      time.textContent = date ? date.toLocaleTimeString("ar", { hour: "numeric", minute: "2-digit" }) : message.time;
      bubble.append(body, time);
      row.append(bubble);
      box.append(row);
    });
    if (nearBottom || box.dataset.ready !== "1") box.scrollTop = box.scrollHeight;
    box.dataset.ready = "1";
  }

  function poll() {
    if (document.hidden) return;
    fetch(chat.dataset.messagesUrl, { headers: { Accept: "application/json" } })
      .then((response) => (response.ok ? response.json() : null))
      .then((data) => { if (data) render(data.ticket); })
      .catch(() => {});
  }

  function grow() {
    text.style.height = "auto";
    text.style.height = Math.min(text.scrollHeight, 140) + "px";
  }

  function send() {
    const message = text.value.trim();
    if (!message) return;
    const button = form.querySelector("button");
    button.disabled = true;
    post(chat.dataset.replyUrl, { message })
      .then((result) => {
        if (!result.ok) throw new Error(result.data.message || "تعذّر إرسال الرسالة.");
        text.value = "";
        grow();
        render(result.data.ticket);
        box.scrollTop = box.scrollHeight;
      })
      .catch((error) => window.alert(error.message || "تعذّر إرسال الرسالة."))
      .finally(() => { button.disabled = false; text.focus(); });
  }

  form.addEventListener("submit", (event) => { event.preventDefault(); send(); });
  text.addEventListener("input", grow);
  // Enter sends, Shift+Enter makes a new line.
  text.addEventListener("keydown", (event) => {
    if (event.key === "Enter" && !event.shiftKey && !event.isComposing) { event.preventDefault(); send(); }
  });

  if (resolveButton) {
    resolveButton.addEventListener("click", () => {
      resolveButton.disabled = true;
      post(chat.dataset.statusUrl, { status: ticket.closed ? "open" : "resolved" })
        .then((result) => {
          if (!result.ok) throw new Error(result.data.message || "تعذّر تحديث حالة الطلب.");
          render(result.data.ticket);
        })
        .catch((error) => window.alert(error.message))
        .finally(() => { resolveButton.disabled = false; });
    });
  }

  render(ticket);
  window.setInterval(poll, POLL_MS);
  document.addEventListener("visibilitychange", () => { if (!document.hidden) poll(); });
});
