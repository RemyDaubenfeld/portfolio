import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'messages', 'widget', 'toggle'];

    connect() {
        this.history = [];
        this.isOpen = false;
        this.hasGreeted = false;
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
        this.appendMessage('assistant', '☕', true);
        await this.wait(1500);
        this.removeTypingIndicator();
        this.appendMessage('assistant', "Salut, je suis Stag'IA'ire, l'IA-ssistant de Rémy! Je suis là pour parler de ses compétences (et pour aller lui chercher du café virtuel pendant qu'il code).");

        this.appendMessage('assistant', '☕', true);
        await this.wait(1800);
        this.removeTypingIndicator();
        this.appendMessage('assistant', "Pose-moi tes questions sur son parcours, ses technos préférées ou demande-lui un entretien — je transmets (en théorie).");
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
        this.history.push({ role: 'user', content: text });

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
            this.appendMessage('assistant', reply);
        } catch (error) {
            this.removeTypingIndicator();
            this.appendMessage('assistant', 'Le stag\'IA\'ire est parti chercher du café... et ne revient plus. Réessaie plus tard.');
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
}