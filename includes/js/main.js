jQuery(document).ready(function ($) {
  // Security: Check if chatly_data exists
  if (typeof chatly_data === 'undefined') {
    console.error('Chatly: Configuration data not found');
    return;
  }

  const data = chatly_data;
  const responses = JSON.parse(data.responses || "[]");
  const bubble = $("#chatly-chat-bubble");
  const chatWindow = $("#chatly-chat-window");
  const messages = $(".chatly-messages");
  const input = $("#chatly-user-input");
  const send = $("#chatly-send");

  bubble.on("click", function () {
    chatWindow.toggleClass("chatly-visible");
  });
  $("#chatly-close").on("click", function () {
    chatWindow.removeClass("chatly-visible");
  });

  // Quick action buttons
  $(document).on("click", ".chatly-chip", function() {
    const text = $(this).data("prompt") || $(this).text();
    input.val(text);
    sendMessage();
  });

  send.on("click", sendMessage);
  input.on("keypress", (e) => { if (e.which === 13) sendMessage(); });

  function sendMessage() {
    const text = input.val().trim();
    if (!text || text.length > 500) return; // Limit input length for security
    
    // Append user message safely (avoid DOM XSS)
    const userRow = $('<div class="chatly-row user"></div>');
    const userMsg = $('<div class="chatly-msg"></div>').text(text); // .text() prevents XSS
    userRow.append(userMsg);
    messages.append(userRow);
    input.val("");
    messages.scrollTop(messages[0].scrollHeight);

    // Try predefined responses first
    const match = responses.find(function(r) {
      return r.question && text.toLowerCase().includes(r.question.toLowerCase());
    });

    if (match && match.answer) {
      const avatarDiv = $('<div class="chatly-avatar" style="background-image:var(--chatly-avatar, var(--chatly-logo));"></div>');
      const botRow = $('<div class="chatly-row bot"></div>');
      botRow.append(avatarDiv);
      const botMsg = $('<div class="chatly-msg"></div>');
      // match.answer is sanitized server-side with wp_kses_post, so using .html() is safe
      botMsg.html(match.answer);
      botRow.append(botMsg);
      messages.append(botRow);
      messages.scrollTop(messages[0].scrollHeight);
      return;
    }

    // AI handler (if enabled in future)
    if (typeof aiEnabled !== 'undefined' && aiEnabled && data.has_ai) {
      const typingDiv = $('<div class="chatly-row bot"><div class="chatly-avatar" style="background-image:var(--chatly-avatar, var(--chatly-logo));"></div><div class="chatly-msg"><div class="chatly-typing"><span></span><span></span><span></span></div></div></div>');
      messages.append(typingDiv);
      messages.scrollTop(messages[0].scrollHeight);

      $.post(data.ajax_url, {
        action: 'chatly',
        nonce: data.nonce,
        message: text,
      })
      .done(function(res){
        typingDiv.remove();
        if (res && res.success && res.data && res.data.reply) {
          const avatarDiv = $('<div class="chatly-avatar" style="background-image:var(--chatly-avatar, var(--chatly-logo));"></div>');
          const botRow = $('<div class="chatly-row bot"></div>');
          botRow.append(avatarDiv);
          const botMsg = $('<div class="chatly-msg"></div>').text(res.data.reply); // Use .text() for AI response
          botRow.append(botMsg);
          messages.append(botRow);
        } else {
          showBotMessage('Sorry, I couldn\'t get an answer right now.');
        }
        messages.scrollTop(messages[0].scrollHeight);
      })
      .fail(function(){
        typingDiv.remove();
        showBotMessage('Network error. Please try again.');
        messages.scrollTop(messages[0].scrollHeight);
      });
      return;
    }

    // Show typing first, then error message
    const typingDiv = $('<div class="chatly-row bot"><div class="chatly-avatar" style="background-image:var(--chatly-avatar, var(--chatly-logo));"></div><div class="chatly-msg"><div class="chatly-typing"><span></span><span></span><span></span></div></div></div>');
    messages.append(typingDiv);
    messages.scrollTop(messages[0].scrollHeight);
    setTimeout(function() {
      typingDiv.remove();
      showBotMessage('I\'m sorry, I don\'t have that information yet.');
      messages.scrollTop(messages[0].scrollHeight);
    }, 1200);
  }

  function showBotMessage(message) {
    const avatarDiv = $('<div class="chatly-avatar" style="background-image:var(--chatly-avatar, var(--chatly-logo));"></div>');
    const botRow = $('<div class="chatly-row bot"></div>');
    botRow.append(avatarDiv);
    const botMsg = $('<div class="chatly-msg"></div>').text(message);
    botRow.append(botMsg);
    messages.append(botRow);
  }
});