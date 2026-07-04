import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'messages', 'widget'];

    connect() {
        this.isOpen = false;
        this.hasGreeted = false;
        this.history = JSON.parse(sessionStorage.getItem('chat_history') ?? '[]');

        const display = JSON.parse(sessionStorage.getItem('chat_display') ?? '[]');
        if (display.length > 0) {
            this.hasGreeted = true;
            display.forEach(msg => this.appendMessage(msg.role, msg.text));
        }
    }

    toggle() {
        this.isOpen = !this.isOpen;
        this.widgetTarget.classList.toggle('hidden', !this.isOpen);

        if (this.isOpen && !this.hasGreeted) {
            this.hasGreeted = true;
            this.playIntro();
        }
    }

    async playIntro() {
        try {
            const res = await fetch('/api/chat/config');
            const config = await res.json();

            if (!config.isActive) return;

            if (config.introMessage1) {
                this.appendMessage('assistant', '☕', true);
                await this.wait(1500);
                this.removeTypingIndicator();
                this.appendMessage('assistant', config.introMessage1);
                this.saveDisplay('assistant', config.introMessage1)
            }

            if (config.introMessage2) {
                this.appendMessage('assistant', '☕', true);
                await this.wait(1800);
                this.removeTypingIndicator();
                this.appendMessage('assistant', config.introMessage2);
                this.saveDisplay('assistant', config.introMessage2)
            }
        } catch (e) {
            // Silencieux si l'API config échoue
        }
    }

    wait(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    async send(event) {
        event.preventDefault();
        const text = this.inputTarget.value.trim();
        if (!text) return;

        this.inputTarget.value = '';
        this.appendMessage('user', text);
        this.saveDisplay('user', text);
        this.history.push({ role: 'user', content: text });
        sessionStorage.setItem('chat_history', JSON.stringify(this.history));

        this.appendMessage('assistant', '☕', true);

        try {
            const response = await fetch('/api/chat', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ messages: this.history }),
            });

            const data = await response.json();
            const reply = data.message ?? data.error ?? 'Une erreur est survenue.';

            this.removeTypingIndicator();
            this.history.push({ role: 'assistant', content: reply });
            sessionStorage.setItem('chat_history', JSON.stringify(this.history));
            this.appendMessage('assistant', reply);
            this.saveDisplay('assistant', reply);

        } catch (error) {
            this.removeTypingIndicator();
            this.appendMessage('assistant', "Le stag'IA'ire est parti chercher du café... et ne revient plus. Réessaie plus tard.");
        }
    }

    appendMessage(role, text, isTyping = false) {
        const div = document.createElement('div');
        const base = 'max-w-[80%] px-3.5 py-2.5 rounded-xl text-sm leading-snug';
        const variant = role === 'user'
            ? 'self-end bg-[var(--color-gold)] text-black'
            : 'self-start bg-neutral-800 text-neutral-200 border border-neutral-700';

        div.className = `${base} ${variant}`;
        if (isTyping) {
            div.dataset.typing = 'true';
            div.classList.add('animate-bounce');
        }
        div.textContent = text;
        this.messagesTarget.appendChild(div);
        this.messagesTarget.scrollTop = this.messagesTarget.scrollHeight;
    }

    removeTypingIndicator() {
        const typing = this.messagesTarget.querySelector('[data-typing="true"]');
        if (typing) typing.remove();
    }

    saveDisplay(role, text) {
        const display = JSON.parse(sessionStorage.getItem('chat_display') ?? '[]');
        display.push({ role, text });
        sessionStorage.setItem('chat_display', JSON.stringify(display));
    }

    clear() {
        sessionStorage.removeItem('chat_history');
        sessionStorage.removeItem('chat_display');
        this.history = [];
        this.messagesTarget.innerHTML = '';
        this.hasGreeted = false;
        this.playIntro();
    }
}